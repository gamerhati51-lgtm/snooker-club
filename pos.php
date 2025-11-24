<?php
session_start();
include 'db.php'; // Includes your database connection



if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = "SAEED GUEST"; 
}

$message = "";

// --- Configuration ---
$rate_config = [
    'Normal' => 500.00, // PKR per hour
    'Century' => 700.00  // PKR per hour (premium rate)
];

// --- 1. Fetch Products for POS ---
$products_result = $conn->query("SELECT product_id, name, price FROM pos_products ORDER BY name ASC");
$products = $products_result ? $products_result->fetch_all(MYSQLI_ASSOC) : [];

// --- 2. Action Handlers ---

// Handle START SESSION
if (isset($_POST['action']) && $_POST['action'] === 'start_session') {
    $table_id = (int)$_POST['table_id'];
    $start_time = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("INSERT INTO snooker_sessions (table_id, start_time, rate_type, session_items, status) VALUES (?, ?, 'Normal', '[]', 'Active')");
    $stmt->bind_param("is", $table_id, $start_time);
    
    if ($stmt->execute()) {
        $message = "✅ Session started for Table ID $table_id at " . date('g:i A');
    } else {
        $message = "❌ Error starting session: " . $stmt->error;
    }
    $stmt->close();
}

// Handle ADD ITEM
if (isset($_POST['action']) && $_POST['action'] === 'add_item') {
    $session_id = (int)$_POST['session_id'];
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    $product_data = array_filter($products, fn($p) => $p['product_id'] === $product_id);
    $product = reset($product_data);
    
    if ($product && $quantity > 0) {
        // Fetch current items from session
        $stmt_fetch = $conn->prepare("SELECT session_items FROM snooker_sessions WHERE session_id = ? AND status = 'Active'");
        $stmt_fetch->bind_param("i", $session_id);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();
        $session = $result->fetch_assoc();
        $stmt_fetch->close();

        if ($session) {
            $items = json_decode($session['session_items'], true) ?: [];
            
            $item_name = $product['name'];
            $item_price = $product['price'];
            $found = false;

            // Check if item already exists to increment quantity
            foreach ($items as &$item) {
                if ($item['name'] === $item_name) {
                    $item['quantity'] += $quantity;
                    $item['total'] = $item['quantity'] * $item['unit_price'];
                    $found = true;
                    break;
                }
            }
            unset($item); // Important to break reference

            // If new item, add it
            if (!$found) {
                $items[] = [
                    'name' => $item_name,
                    'quantity' => $quantity,
                    'unit_price' => (float)$item_price,
                    'total' => $quantity * (float)$item_price
                ];
            }

            $new_items_json = json_encode($items);

            // Update session_items in DB
            $stmt_update = $conn->prepare("UPDATE snooker_sessions SET session_items = ? WHERE session_id = ?");
            $stmt_update->bind_param("si", $new_items_json, $session_id);
            if ($stmt_update->execute()) {
                $message = "✅ Added $quantity x " . htmlspecialchars($item_name) . " to the order for Session $session_id.";
            } else {
                $message = "❌ Error updating session: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
             $message = "❌ Error: Active session not found.";
        }
    }
}

// Handle CLOSE SESSION (Simplified: updates status)
if (isset($_POST['action']) && $_POST['action'] === 'close_session') {
    $session_id = (int)$_POST['session_id'];
    
    // In a real app, bill details and total would be calculated here and logged to a sales table.
    
    $stmt = $conn->prepare("UPDATE snooker_sessions SET status = 'Closed' WHERE session_id = ? AND status = 'Active'");
    $stmt->bind_param("i", $session_id);
    
    if ($stmt->execute()) {
        $message = "✅ Bill generated and Session $session_id closed successfully.";
    } else {
        $message = "❌ Error closing session: " . $stmt->error;
    }
    $stmt->close();
}

// Handle RATE CHANGE
if (isset($_POST['action']) && $_POST['action'] === 'change_rate') {
    $session_id = (int)$_POST['session_id'];
    $new_rate = $_POST['rate_type'];
    
    $stmt = $conn->prepare("UPDATE snooker_sessions SET rate_type = ? WHERE session_id = ?");
    $stmt->bind_param("si", $new_rate, $session_id);
    
    if ($stmt->execute()) {
        $message = "✅ Rate for Session $session_id changed to " . htmlspecialchars($new_rate);
    } else {
        $message = "❌ Error changing rate: " . $stmt->error;
    }
    $stmt->close();
}


// --- 3. Fetch All Tables and Active Sessions ---
$sessions_query = $conn->query("
    SELECT 
        st.id as table_id, st.table_name, ss.session_id, ss.start_time, ss.rate_type, ss.session_items, ss.status
    FROM 
        snooker_tables st
    LEFT JOIN 
        snooker_sessions ss ON st.id = ss.table_id AND ss.status = 'Active'
    WHERE 
        st.is_active = 1
    ORDER BY 
        st.id ASC
");
$tables_with_sessions = $sessions_query ? $sessions_query->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>POS | Saeed Snooker Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CRITICAL: Tailwind Configuration must be in the head -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'snooker-green': '#183a34',
                        'snooker-light': '#2a4d45',
                        'snooker-accent': '#ffb703',
                        'snooker-bg': '#f3ffec',
                        'active-red': '#dc2626',
                        'inactive-green': '#16a34a',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <!-- End of Tailwind Config -->

    <style>
        /* Shared link styling for the sidebar */
        .sidebar-link {
            border-left: 4px solid transparent;
            color: #ccccccff;
        }
        .sidebar-link:hover {
            color: white;
        }
        .pos-card {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s;
        }
        .pos-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        /* Custom radio button styles for rate type */
        .rate-radio-label {
            transition: all 0.2s;
            cursor: pointer;
        }
        .rate-radio:checked + .rate-radio-label {
            @apply bg-snooker-accent text-snooker-green font-bold shadow-md;
        }
    </style>
</head>
<body class="bg-snooker-bg min-h-screen font-sans">
    
    <div class="relative min-h-screen"> 

        <!-- 1. Include the FIXED Sidebar (w-64) -->
        <?php include 'layout/sidebar.php'; ?>

        <!-- 2. Main Content Area -->
        <div class="ml-64 p-8 max-w-full">
            
            <h1 class="text-4xl font-extrabold mb-8 text-snooker-green">💰 Point of Sale (POS)</h1>

            <!-- Message Alert -->
            <?php if (!empty($message)) { ?>
                <div class="mb-6 p-4 <?php echo (strpos($message, 'Error') !== false || strpos($message, '❌') !== false) ? 'bg-red-100 text-red-700 border border-red-300' : 'bg-snooker-light/10 text-snooker-green border border-snooker-light'; ?> rounded-lg shadow-md font-medium transition duration-300 ease-in-out">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <!-- Table Status Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="pos-grid">
                
                <?php foreach ($tables_with_sessions as $table): 
                    $is_active = $table['status'] === 'Active';
                    $rate_normal_checked = $is_active && $table['rate_type'] === 'Normal';
                    $rate_century_checked = $is_active && $table['rate_type'] === 'Century';
                    $current_items = $is_active ? (json_decode($table['session_items'], true) ?: []) : [];
                    
                    // Initial total item price calculation
                    $items_total = array_reduce($current_items, fn($carry, $item) => $carry + $item['total'], 0.00);
                ?>
                    <div class="bg-white p-5 rounded-xl pos-card <?php echo $is_active ? 'border-t-8 border-active-red' : 'border-t-8 border-inactive-green'; ?>" 
                         data-table-id="<?php echo $table['table_id']; ?>"
                         data-session-id="<?php echo $table['session_id']; ?>"
                         data-start-time-iso="<?php echo $is_active ? date(DATE_ISO8601, strtotime($table['start_time'])) : ''; ?>"
                         id="table-card-<?php echo $table['table_id']; ?>">
                        
                        <!-- Table Header -->
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h2 class="text-2xl font-extrabold text-snooker-green">
                                <?php echo htmlspecialchars($table['table_name']); ?>
                            </h2>
                            <span class="text-xs px-3 py-1 rounded-full font-bold uppercase <?php echo $is_active ? 'bg-active-red text-white' : 'bg-inactive-green text-white'; ?>">
                                <?php echo $is_active ? 'Active' : 'Free'; ?>
                            </span>
                        </div>

                        <?php if ($is_active): ?>
                            <!-- Active Session Details -->
                            <div class="space-y-3 mb-4">
                                <p class="text-sm text-gray-700 flex justify-between">
                                    <span class="font-semibold">Start Time:</span> 
                                    <span class="font-mono text-active-red" id="start-time-<?php echo $table['session_id']; ?>">
                                        <?php echo date('g:i A', strtotime($table['start_time'])); ?>
                                    </span>
                                </p>
                                <p class="text-lg font-bold text-gray-800 flex justify-between">
                                    <span class="font-semibold">Duration:</span>
                                    <!-- Dynamic Duration (Updated by JS) -->
                                    <span class="font-mono text-xl text-snooker-green" id="duration-<?php echo $table['session_id']; ?>">
                                        00:00:00
                                    </span>
                                </p>
                            </div>
                            
                            <!-- Rate Type Toggle -->
                            <div class="mb-4 pt-3 border-t">
                                <span class="block text-xs font-semibold text-gray-600 mb-2">Rate Type (PKR <?php echo $rate_config['Normal']; ?>/hr vs <?php echo $rate_config['Century']; ?>/hr):</span>
                                <div class="flex space-x-2">
                                    <?php foreach ($rate_config as $type => $rate): ?>
                                        <form method="POST" class="flex-1" onsubmit="return confirm('Are you sure you want to change the rate to <?php echo $type; ?>?');">
                                            <input type="hidden" name="action" value="change_rate">
                                            <input type="hidden" name="session_id" value="<?php echo $table['session_id']; ?>">
                                            <input type="hidden" name="rate_type" value="<?php echo $type; ?>">
                                            
                                            <button type="submit" 
                                                    class="rate-radio-label w-full text-center py-2 rounded-lg text-sm transition
                                                           <?php echo $table['rate_type'] === $type ? 'bg-snooker-accent text-snooker-green font-bold shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                                                <?php echo $type; ?>
                                            </button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Items Added -->
                            <div class="mb-4 pt-3 border-t">
                                <h3 class="text-sm font-semibold text-gray-600 mb-2">Items Added:</h3>
                                <ul class="space-y-1 text-sm" id="items-list-<?php echo $table['session_id']; ?>">
                                    <?php if (empty($current_items)): ?>
                                        <li class="text-gray-400 italic">No items yet.</li>
                                    <?php else: ?>
                                        <?php foreach ($current_items as $item): ?>
                                            <li class="flex justify-between text-gray-700">
                                                <span>- <?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                                                <span class="font-semibold text-red-600"><?php echo number_format($item['total'], 2); ?> PKR</span>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                                
                                <button onclick="openAddItemModal(<?php echo $table['session_id']; ?>, '<?php echo htmlspecialchars($table['table_name']); ?>')"
                                        class="mt-3 w-full py-2 bg-snooker-green text-white text-sm font-semibold rounded-lg hover:bg-snooker-light transition shadow-md">
                                    + Add Item
                                </button>
                            </div>

                            <!-- Total & Bill Button -->
                            <div class="mt-4 pt-4 border-t border-dashed border-gray-300">
                                <p class="text-lg font-bold flex justify-between">
                                    <span>Table Fee:</span> 
                                    <span class="text-blue-700" id="table-fee-<?php echo $table['session_id']; ?>">0.00 PKR</span>
                                </p>
                                <p class="text-lg font-bold flex justify-between">
                                    <span>Item Total:</span> 
                                    <span class="text-red-700"><?php echo number_format($items_total, 2); ?> PKR</span>
                                </p>
                                <p class="text-2xl font-extrabold flex justify-between mt-2">
                                    <span>GRAND TOTAL:</span> 
                                    <span class="text-snooker-green" id="grand-total-<?php echo $table['session_id']; ?>"><?php echo number_format($items_total, 2); ?> PKR</span>
                                </p>
                                
                                <form method="POST" onsubmit="return confirm('Are you sure you want to close the session and generate the bill for <?php echo htmlspecialchars($table['table_name']); ?>?');">
                                    <input type="hidden" name="action" value="close_session">
                                    <input type="hidden" name="session_id" value="<?php echo $table['session_id']; ?>">
                                    <button type="submit"
                                            class="mt-4 w-full py-3 bg-blue-600 text-white font-extrabold rounded-xl hover:bg-blue-700 transition transform hover:scale-[1.01] shadow-lg">
                                        Close & Generate Bill
                                    </button>
                                </form>
                            </div>

                        <?php else: ?>
                            <!-- Free Table Action -->
                            <div class="h-28 flex flex-col justify-center items-center">
                                <p class="text-gray-500 mb-3">Table is currently free.</p>
                                <form method="POST">
                                    <input type="hidden" name="action" value="start_session">
                                    <input type="hidden" name="table_id" value="<?php echo $table['table_id']; ?>">
                                    <button type="submit"
                                            class="bg-inactive-green text-white px-5 py-2 rounded-lg font-bold hover:bg-green-700 transition shadow-md">
                                        Start Session
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($tables_with_sessions)): ?>
                <div class="text-center p-10 bg-white rounded-xl mt-8 text-gray-500">
                    <p class="text-xl font-semibold">No active tables found or all tables are free.</p>
                    <p>Make sure you have active tables configured in your database.</p>
                </div>
            <?php endif; ?>

        </div>
        
        <!-- Add Item Modal -->
        <div id="addItemModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden flex items-center justify-center p-4 z-50">
            <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-md">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-xl font-bold text-snooker-green">Add Item to <span id="modal-table-name"></span></h3>
                    <button onclick="closeAddItemModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
                </div>
                
                <form id="addItemForm" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_item">
                    <input type="hidden" name="session_id" id="modal-session-id">

                    <div>
                        <label for="product_id" class="block text-gray-700 font-medium mb-1">Product</label>
                        <select name="product_id" id="product_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-snooker-accent transition bg-white">
                            <option value="">-- Select Item --</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['product_id']; ?>" data-price="<?php echo $product['price']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?> (PKR <?php echo number_format($product['price'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="quantity" class="block text-gray-700 font-medium mb-1">Quantity</label>
                        <input type="number" name="quantity" id="quantity" value="1" min="1" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-snooker-accent transition">
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit"
                                class="w-full py-3 bg-snooker-accent text-snooker-green font-bold rounded-lg hover:bg-yellow-400 transition shadow-md">
                            Add to Order
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        const rateConfig = <?php echo json_encode($rate_config); ?>;
        const currency = 'PKR';

        /**
         * Converts total minutes to PKR table fee based on the rate.
         * @param {number} minutesElapsed
         * @param {string} rateType 'Normal' or 'Century'
         * @returns {number} Calculated fee
         */
        function calculateTableFee(minutesElapsed, rateType) {
            const hourlyRate = rateConfig[rateType] || rateConfig['Normal'];
            // Fee = (Minutes / 60) * Hourly Rate
            return (minutesElapsed / 60) * hourlyRate;
        }

        /**
         * Formats seconds into HH:MM:SS string.
         * @param {number} totalSeconds
         * @returns {string}
         */
        function formatDuration(totalSeconds) {
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = Math.floor(totalSeconds % 60);

            const pad = (num) => String(num).padStart(2, '0');
            return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
        }

        /**
         * Main function to update time, fees, and totals for an active session card.
         * @param {HTMLElement} card
         */
        function updateCard(card) {
            const sessionId = card.dataset.sessionId;
            const startTimeISO = card.dataset.startTimeIso;
            
            if (!startTimeISO) return;

            const startTime = new Date(startTimeISO);
            const now = new Date();
            const elapsedSeconds = (now - startTime) / 1000;
            const elapsedMinutes = elapsedSeconds / 60;
            
            const rateType = card.querySelector('input[name="rate_type"].rate-radio:checked') ? 
                             card.querySelector('input[name="rate_type"].rate-radio:checked').value : 
                             card.querySelector('.rate-radio-label.bg-snooker-accent').innerText.trim();

            // 1. Update Duration
            document.getElementById(`duration-${sessionId}`).innerText = formatDuration(elapsedSeconds);

            // 2. Calculate Table Fee
            const tableFee = calculateTableFee(elapsedMinutes, rateType);
            const tableFeeElement = document.getElementById(`table-fee-${sessionId}`);
            tableFeeElement.innerText = `${tableFee.toFixed(2)} ${currency}`;

            // 3. Calculate Grand Total
            const itemTotalElement = card.querySelector('.text-red-700');
            const itemTotal = parseFloat(itemTotalElement.innerText.replace(currency, '').trim());

            const grandTotal = tableFee + itemTotal;
            document.getElementById(`grand-total-${sessionId}`).innerText = `${grandTotal.toFixed(2)} ${currency}`;
        }

        // --- Real-time Updates ---
        document.addEventListener('DOMContentLoaded', () => {
            const activeCards = document.querySelectorAll('.pos-card[data-session-id]');
            
            activeCards.forEach(card => {
                // Initialize card display
                updateCard(card);

                // Set up interval for real-time updates
                setInterval(() => {
                    updateCard(card);
                }, 1000); // Update every second
            });
        });


        // --- Modal Control Functions ---
        function openAddItemModal(sessionId, tableName) {
            document.getElementById('modal-session-id').value = sessionId;
            document.getElementById('modal-table-name').innerText = tableName;
            document.getElementById('addItemModal').classList.remove('hidden');
        }

        function closeAddItemModal() {
            document.getElementById('addItemModal').classList.add('hidden');
            document.getElementById('addItemForm').reset();
        }

        // Close modal when clicking outside
        document.getElementById('addItemModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('addItemModal')) {
                closeAddItemModal();
            }
        });
    </script>
</body>
</html>