<?php
session_start();
include 'db.php';

// Ensure table_id and session_id are passed
$table_id = $_GET['table_id'] ?? die("Table ID required.");
$session_id = $_GET['session_id'] ?? die("Session ID required.");

// --- 1. Fetch Session and Table Rates ---
$stmt = $conn->prepare("
    SELECT 
        s.start_time, s.rate_type, s.booking_duration,
        s.century_mode_start, s.century_mode_minutes, s.century_warning_shown,
        s.status,
        st.table_name, st.rate_per_hour, st.century_rate
    FROM 
        snooker_sessions s
    JOIN 
        snooker_tables st ON s.table_id = st.id
    WHERE 
        s.session_id = ? AND s.status = 'Active'
");
$stmt->bind_param("i", $session_id);
$stmt->execute();
$result = $stmt->get_result();
$session_data = $result->fetch_assoc();
$stmt->close();

if (!$session_data) {
    die("Error: No active session found for ID: $session_id on Table: $table_id");
}

$start_time_db = $session_data['start_time'];
$start_time_display = date('g:i A', strtotime($start_time_db));
$rate_per_hour = (float)$session_data['rate_per_hour'];
$century_rate = (float)$session_data['century_rate'];
$booking_duration = isset($session_data['booking_duration']) ? (int)$session_data['booking_duration'] : 1;
$rate_type = $session_data['rate_type'] ?? 'Normal';

// Century mode data - FIXED CALCULATION
$century_mode_active = !empty($session_data['century_mode_start']);
$century_mode_start = $session_data['century_mode_start'] ?? null;

// Calculate century minutes correctly
$century_elapsed_minutes = 0;
if ($century_mode_start) {
    $now = new DateTime();
    $century_start = new DateTime($century_mode_start);
    $interval = $century_start->diff($now);
    $century_elapsed_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
    
    // Also use stored minutes as fallback
    $stored_minutes = $session_data['century_mode_minutes'] ?? 0;
    if ($stored_minutes > 0) {
        $century_elapsed_minutes = max($century_elapsed_minutes, $stored_minutes);
    }
}

$century_warning_shown = $session_data['century_warning_shown'] ?? 0;

// --- 2. Fetch Items Added ---
$stmt_items = $conn->prepare("
    SELECT 
       session_item_id, item_name, quantity, price_per_unit, (quantity * price_per_unit) AS total_item_price
    FROM 
        session_items
    WHERE 
        session_id = ?
    ORDER BY session_item_id DESC
");
$stmt_items->bind_param("i", $session_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

$items_total = 0.00;
$items_list = [];
while ($item = $items_result->fetch_assoc()) {
    $items_list[] = $item;
    $items_total += (float)$item['total_item_price'];
}
$stmt_items->close();

// --- 3. Fetch All Products for Add Item Modal ---
$products_for_modal = [];
$stmt_products = $conn->prepare("
    SELECT product_id, name, selling_price, stock_quantity 
    FROM products 
    WHERE is_active = 1
    ORDER BY name ASC
");
$stmt_products->execute();
$products_result = $stmt_products->get_result();

while ($product = $products_result->fetch_assoc()) {
    $products_for_modal[] = $product;
}
$stmt_products->close();

// --- 4. Calculation Functions ---
function calculate_table_charge($rate_type, $rate_per_hour, $century_rate, $booking_duration = 1, $century_minutes = 0) {
    if ($rate_type == 'Normal') {
        $table_charge = $booking_duration * $rate_per_hour;
    } else {
        // Century rate: charge per minute after booking duration
        $base_charge = $booking_duration * $rate_per_hour;
        $century_charge = $century_minutes * $century_rate;
        $table_charge = $base_charge + $century_charge;
    }
    return round($table_charge, 2);
}

// Calculate elapsed time
function calculate_elapsed_time($start_time, $century_mode_start = null) {
    $now = new DateTime();
    $start = new DateTime($start_time);
    
    $interval = $start->diff($now);
    $total_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
    
    $century_minutes = 0;
    if ($century_mode_start) {
        $century_start = new DateTime($century_mode_start);
        $century_interval = $century_start->diff($now);
        $century_minutes = ($century_interval->days * 24 * 60) + ($century_interval->h * 60) + $century_interval->i;
    }
    
    return [
        'total_minutes' => $total_minutes,
        'century_minutes' => $century_minutes,
        'hours' => floor($total_minutes / 60),
        'remaining_minutes' => $total_minutes % 60
    ];
}

// Get elapsed time
$elapsed_time = calculate_elapsed_time($start_time_db, $century_mode_start);
$total_elapsed_minutes = $elapsed_time['total_minutes'];

// Use calculated century minutes instead of stored
$century_elapsed_minutes = $elapsed_time['century_minutes'];

// Calculate charges
$table_charge = calculate_table_charge($rate_type, $rate_per_hour, $century_rate, $booking_duration, $century_elapsed_minutes);
$final_total = $table_charge + $items_total;

// Calculate what century rate would be (for display)
$normal_charge = $booking_duration * $rate_per_hour;

// Debug info
error_log("Session Debug: century_mode_start=$century_mode_start, century_elapsed_minutes=$century_elapsed_minutes, rate_type=$rate_type");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Active Session: <?php echo htmlspecialchars($session_data['table_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .text-snooker-green { color: #1e8449; }
        .rate-option {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .rate-option.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }
        .century-active {
            animation: pulse 2s infinite;
            border-color: #9333ea !important;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(147, 51, 234, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(147, 51, 234, 0); }
            100% { box-shadow: 0 0 0 0 rgba(147, 51, 234, 0); }
        }
        .warning-alert {
            animation: shake 0.5s;
            animation-iteration-count: 3;
        }
        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
            100% { transform: translateX(0); }
        }
        .item-remove-btn {
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .item-row:hover .item-remove-btn {
            opacity: 1;
        }
    </style>
</head>

<body class="bg-blue-100 font-sans">
  <div class="flex min-h-screen">
    <?php include 'layout/sidebar.php'; ?>
    <main class="flex-1 ml-0 lg:ml-64 pt-20 p-8 main-content">
      <?php include "layout/header.php"; ?>
      <div id="content-area" class="space-y-8 bg-blue-200 p-6 rounded-lg">
            <!-- Session Header -->
            <div class="mb-4">
                <h1 class="text-xl md:text-2xl font-bold text-center text-orange-700">
                    <?php echo htmlspecialchars($session_data['table_name']); ?>
                    <span class="text-blue-700 text-xs ml-1">• ACTIVE</span>
                    <?php if ($rate_type == 'Century'): ?>
                        <span class="text-purple-600 text-xs ml-1">• CENTURY MODE</span>
                    <?php endif; ?>
                </h1>
                
                <div class="bg-blue-100 p-3 rounded-lg mt-2 border border-blue-300">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        <div>
                            <p class="text-xs md:text-sm text-gray-900">
                                <strong class="text-gray-800">Start Time:</strong> <?php echo $start_time_display; ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs md:text-sm text-gray-900">
                                <strong class="text-gray-800">Duration:</strong> 
                                <span class="font-bold text-blue-700" id="duration-display">
                                    <?php echo $elapsed_time['hours']; ?>h <?php echo $elapsed_time['remaining_minutes']; ?>m
                                </span>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs md:text-sm text-gray-900">
                                <strong class="text-gray-800">Session ID:</strong> 
                                <span class="font-bold text-blue-700">#<?php echo $session_id; ?></span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Century Mode Timer -->
                    <?php if ($rate_type == 'Century' && $century_mode_active): ?>
                    <div class="mt-2 p-2 bg-purple-50 border border-purple-200 rounded">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-purple-700">
                                <i class="fas fa-clock mr-1"></i> Century Mode Active
                            </span>
                            <span class="text-xs font-bold text-purple-800" id="century-timer">
                                <?php echo $century_elapsed_minutes; ?> minutes
                            </span>
                        </div>
                        <div class="w-full bg-purple-200 rounded-full h-1.5 mt-1">
                            <div id="century-progress" class="bg-purple-600 h-1.5 rounded-full" 
                                 style="width: <?php echo min(($century_elapsed_minutes / 60) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Century Warning Alert - FIXED LOGIC -->
            <div id="century-warning" class="<?php echo ($rate_type == 'Century' && $century_mode_active && $century_elapsed_minutes >= 20 && !$century_warning_shown) ? '' : 'hidden'; ?> 
                 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded warning-alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3 text-xl"></i>
                    <div>
                        <p class="font-bold">⚠ Century Mode Warning</p>
                        <p class="text-sm">Century mode has been active for <?php echo $century_elapsed_minutes; ?> minutes. 
                        Additional charges are being applied at <?php echo number_format($century_rate, 2); ?> PKR per minute.</p>
                    </div>
                    <button onclick="hideCenturyWarning()" class="ml-auto text-yellow-700 hover:text-yellow-900">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <!-- Rate Selection -->
                    <div>
                        <h3 class="text-sm font-semibold mb-1 text-gray-800">Select Rate Type</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="rate-option p-3 rounded-lg cursor-pointer border-2 transition-all duration-200 <?php echo $rate_type == 'Normal' ? 'border-green-600 bg-green-100 shadow-sm selected' : 'border-gray-300 hover:border-green-500'; ?>"
                                 data-rate-type="Normal" 
                                 onclick="changeRateType('Normal')">
                                <div class="text-center">
                                    <div class="text-sm font-bold text-gray-900">Normal Rate</div>
                                    <div class="text-sm font-semibold text-green-700 mt-1"><?php echo number_format($rate_per_hour, 2); ?> PKR/hr</div>
                                    <div class="text-xs text-gray-700 mt-0.5">Flat hourly rate</div>
                                </div>
                            </div>
                            
                            <div class="rate-option p-3 rounded-lg cursor-pointer border-2 transition-all duration-200 <?php echo $rate_type == 'Century' ? 'border-purple-600 bg-purple-100 shadow-sm selected century-active' : 'border-gray-300 hover:border-purple-500'; ?>"
                                 data-rate-type="Century" 
                                 onclick="changeRateType('Century')">
                                <div class="text-center">
                                    <div class="text-sm font-bold text-gray-900">Century Rate</div>
                                    <div class="text-sm font-semibold text-purple-700 mt-1"><?php echo number_format($century_rate, 2); ?> PKR/min</div>
                                    <div class="text-xs text-gray-700 mt-0.5">After initial <?php echo $booking_duration; ?> hour(s)</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Century Mode Controls -->
                        <?php if ($rate_type == 'Century'): ?>
                        <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700">Century Mode:</span>
                                <div class="flex space-x-2">
                                    <?php if (!$century_mode_active): ?>
                                    <button onclick="startCenturyMode()" 
                                            class="px-3 py-1 bg-purple-600 text-white text-xs rounded hover:bg-purple-700 transition">
                                        <i class="fas fa-play mr-1"></i> Start Timer
                                    </button>
                                    <?php else: ?>
                                    <button onclick="pauseCenturyMode()" 
                                            class="px-3 py-1 bg-yellow-600 text-white text-xs rounded hover:bg-yellow-700 transition">
                                        <i class="fas fa-pause mr-1"></i> Pause
                                    </button>
                                    <button onclick="resetCenturyMode()" 
                                            class="px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700 transition">
                                        <i class="fas fa-redo mr-1"></i> Reset
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($century_mode_active): ?>
                            <div class="mt-2 text-xs text-gray-600">
                                <p><i class="fas fa-info-circle mr-1"></i> Timer started at: <?php echo date('h:i A', strtotime($century_mode_start)); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <form id="rateChangeForm" action="change_rate.php" method="POST" class="hidden">
                            <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                            <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
                            <input type="hidden" name="rate_type" id="rateTypeInput" value="<?php echo $rate_type; ?>">
                        </form>
                    </div>

                    <!-- Items List -->
                    <div class="bg-gray-100 p-4 rounded-lg border border-gray-300">
                        <div class="flex justify-between items-center mb-1">
                            <h3 class="text-sm font-semibold text-gray-900">Items Added</h3>
                            <span class="text-sm font-bold text-green-700" id="items-total-display">
                                <?php echo number_format($items_total, 2); ?> PKR
                            </span>
                        </div>
                        
                        <ul class="space-y-2 max-h-48 overflow-y-auto pr-2" id="items-list-ul">
                            <?php if (empty($items_list)): ?>
                                <li class="text-xs text-gray-600 text-center py-2 border-b border-gray-300">No items added yet</li>
                            <?php else: ?>
                                <?php foreach ($items_list as $item): ?>
                                    <li class="flex justify-between items-center text-sm py-2 border-b
                                     border-gray-300 last:border-0 item-row" 
                                     data-item-id="<?php echo $item['session_item_id']; ?>">
                                        <div class="flex items-center">
                                            <button onclick="removeItem(<?php echo $item['session_item_id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>')" 
                                                    class="item-remove-btn mr-2 text-red-500 hover:text-red-700 transition" 
                                                    title="Remove item">
                                                <i class="fas fa-times-circle text-xs"></i>
                                            </button>
                                            <span class="text-gray-900 font-medium">
                                                <?php echo htmlspecialchars($item['item_name']); ?>
                                            </span>
                                            <span class="text-xs text-gray-600 ml-2">
                                                ×<?php echo $item['quantity']; ?>
                                            </span>
                                        </div>
                                        <div class="flex items-center">
                                            <strong class="text-gray-900 font-bold mr-2">
                                                <?php echo number_format($item['total_item_price'], 2); ?> PKR
                                            </strong>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                        
                        <button id="open-add-item-modal"
                            class="mt-4 w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white text-sm py-2.5 rounded-lg font-bold transition-all duration-200 shadow-sm">
                            <i class="fas fa-plus mr-2"></i> Add Item to Bill
                        </button>
                    </div>
                </div>

                <!-- Right Column - Bill Summary -->
                <div class="bg-gradient-to-br from-gray-50 to-blue-50 p-5 rounded-lg border-2 border-blue-200">
                    <h3 class="text-lg font-bold mb-1 text-gray-900 border-b border-gray-300 pb-2">Bill Summary</h3>
                    
                    <div class="space-y-3 mb-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-800">Base Rate:</span>
                            <span class="text-sm font-bold text-gray-900">
                                <?php echo number_format($rate_per_hour, 2); ?> PKR/hr
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-800">Duration:</span>
                            <span class="text-sm font-bold text-gray-900">
                                <?php echo $booking_duration; ?> hour(s) included
                            </span>
                        </div>
                        
                        <?php if ($rate_type == 'Century' && $century_elapsed_minutes > 0): ?>
                        <div class="bg-purple-50 p-2 rounded border border-purple-200">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-purple-800">Century Mode Minutes:</span>
                                <span class="text-sm font-bold text-purple-700">
                                    <?php echo $century_elapsed_minutes; ?> min
                                </span>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-xs text-purple-700">Additional Charge:</span>
                                <span class="text-xs font-bold text-purple-800">
                                    <?php echo number_format($century_elapsed_minutes * $century_rate, 2); ?> PKR
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="py-4 border-t border-gray-400">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-base font-bold text-gray-900">
                                Table Play Charge:
                            </span>
                            <span id="table-charge-display" class="text-lg font-extrabold text-gray-900">
                                <?php echo number_format($table_charge, 2); ?> PKR
                            </span>
                        </div>

                        <div class="bg-gradient-to-r from-green-50 to-emerald-100 p-4 rounded-lg border border-green-300">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-gray-900">
                                    FINAL TOTAL:
                                </span>
                                <span id="final-total-display" class="text-2xl font-extrabold text-green-800">
                                    <?php echo number_format($final_total, 2); ?> PKR
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-6 pt-4 border-t border-gray-400 space-y-3">
                        <button type="button" onclick="generateBill()"
                                class="w-full bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white text-sm py-2.5 rounded-md font-semibold transition-all duration-200 shadow-md mb-2">
                            <i class="fas fa-money-bill-wave mr-2"></i> CLOSE & GENERATE BILL
                        </button>

                        <a href="admin.php"
                           class="block w-full text-center bg-gradient-to-r from-gray-700 to-gray-900 hover:from-gray-800 hover:to-black text-white text-sm py-2.5 rounded-md font-semibold transition-all duration-200 shadow-md">
                            <i class="fas fa-arrow-left mr-2"></i> BACK TO ADMIN PANEL
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Enhanced Add Item Modal -->
<div id="addItemModal" class="fixed inset-0 bg-gray-900/80 hidden flex items-center justify-center z-50">
    <div class="bg-white p-5 rounded-xl shadow-2xl w-full max-w-2xl mx-4 border-2 border-blue-300 max-h-[90vh] overflow-hidden flex flex-col">
        <h2 class="text-lg font-bold mb-2 pb-3 border-b border-gray-300 text-gray-900">
            <i class="fas fa-shopping-cart mr-2"></i> Add Product to Order
        </h2>
        
        <div class="mb-4">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="productSearch" placeholder="Search products by name..." 
                       class="w-full pl-10 pr-4 py-2.5 text-sm border-2 border-gray-400 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-300"
                       onkeyup="searchProducts()">
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto pr-2">
            <form id="addItemForm" method="POST" class="space-y-4">
                <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                
                <div id="productGrid" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <?php foreach ($products_for_modal as $product): ?>
                        <div class="product-card border border-gray-300 rounded-lg p-3 hover:border-blue-400 hover:shadow-md transition-all cursor-pointer"
                             data-product-id="<?php echo $product['product_id']; ?>"
                             data-name="<?php echo htmlspecialchars($product['name']); ?>"
                             data-price="<?php echo $product['selling_price']; ?>"
                             data-stock="<?php echo $product['stock_quantity']; ?>"
                             onclick="selectProduct(this)">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h4 class="font-bold text-sm text-gray-900"><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <p class="text-xs text-gray-600 mt-1">
                                        Price: <span class="font-bold text-green-700"><?php echo number_format($product['selling_price'], 2); ?> PKR</span>
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        Stock: <span class="font-medium <?php echo $product['stock_quantity'] <= 10 ? 'text-red-600' : 'text-gray-700'; ?>">
                                            <?php echo $product['stock_quantity']; ?>
                                        </span>
                                    </p>
                                </div>
                                <button type="button" onclick="selectProduct(this.parentElement.parentElement, event)"
                                        class="ml-2 px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded hover:bg-blue-200 transition">
                                    Select
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="pt-4 border-t border-gray-300">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="selectedProduct" class="block text-sm font-bold mb-2 text-gray-800">Selected Product</label>
                            <input type="text" id="selectedProduct" readonly
                                   class="w-full px-4 py-2.5 text-sm border-2 border-gray-400 rounded-lg bg-gray-50">
                            <input type="hidden" name="product_id" id="product_id">
                        </div>
                        
                        <div>
                            <label for="quantity" class="block text-sm font-bold mb-2 text-gray-800">Quantity</label>
                            <div class="flex items-center">
                                <button type="button" onclick="adjustQuantity(-1)" 
                                        class="px-3 py-2 bg-gray-200 text-gray-700 rounded-l-lg hover:bg-gray-300">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" name="quantity" id="quantity" value="1" min="1" required
                                       class="w-full px-4 py-2.5 text-sm border-y-2 border-gray-400 text-center">
                                <button type="button" onclick="adjustQuantity(1)" 
                                        class="px-3 py-2 bg-gray-200 text-gray-700 rounded-r-lg hover:bg-gray-300">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <p id="stock-warning" class="text-sm text-red-600 font-bold mt-1 hidden"></p>
                        </div>
                    </div>
                </div>

                <div class="pt-3">
                    <p class="text-base font-bold text-gray-900">
                        Sub-Total: <span id="modal-subtotal" class="text-green-700 text-lg">0.00 PKR</span>
                    </p>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" id="close-add-item-modal"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-900 px-4 py-2.5 text-sm font-bold rounded-lg transition border-2 border-gray-400">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </button>
                    <button type="submit" id="submit-add-item"
                            class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-2.5 text-sm font-bold rounded-lg transition shadow-sm">
                        <i class="fas fa-check mr-2"></i> Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bill Generation Modal -->
<div id="billModal" class="fixed inset-0 bg-gray-900/90 hidden flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-y-auto border-2 border-green-500">
        <div class="flex justify-between items-center mb-4 border-b pb-4">
            <h2 class="text-2xl font-bold text-gray-900">🧾 Final Bill Receipt</h2>
            <button id="closeBillModal" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
        </div>
        
        <div id="billContent"></div>
        
        <div id="billLoading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
            <p class="mt-4 text-gray-600">Generating bill...</p>
        </div>
        
        <div class="mt-6 pt-4 border-t border-gray-300 flex justify-between space-x-4">
            <button id="printBillBtn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-lg font-semibold transition hidden">
                <i class="fas fa-print mr-2"></i> Print Bill
            </button>
            <button id="closeSessionBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-lg font-semibold transition hidden">
                <i class="fas fa-check mr-2"></i> Return to Dashboard
            </button>
        </div>
    </div>
</div>

<script>
// Global variables - FIXED
const sessionID = <?php echo $session_id; ?>;
const tableID = <?php echo $table_id; ?>;
let bookingDuration = <?php echo $booking_duration; ?>;
let itemsTotal = <?php echo $items_total; ?>;
const ratePerHour = <?php echo $rate_per_hour; ?>;
const centuryRate = <?php echo $century_rate; ?>;
let currentRateType = "<?php echo $rate_type; ?>";
let centuryModeActive = <?php echo $century_mode_active ? 'true' : 'false'; ?>;
let centuryElapsedMinutes = <?php echo $century_elapsed_minutes; ?>;
let centuryWarningShown = <?php echo $century_warning_shown ? 'true' : 'false'; ?>;
let totalElapsedMinutes = <?php echo $total_elapsed_minutes; ?>;

// Timer variables
let centuryTimerInterval = null;
let centuryStartTime = <?php echo $century_mode_start ? "'" . $century_mode_start . "'" : 'null'; ?>;

// Initialize
$(document).ready(function() {
    console.log('Page loaded - Debug Info:', {
        sessionID: sessionID,
        centuryModeActive: centuryModeActive,
        centuryStartTime: centuryStartTime,
        centuryElapsedMinutes: centuryElapsedMinutes,
        currentRateType: currentRateType
    });
    
    updateTotals();
    setupEventListeners();
    startTimers();
    
    // Check for century warning
    if (currentRateType === 'Century' && centuryModeActive && centuryElapsedMinutes >= 20 && !centuryWarningShown) {
        console.log('Showing century warning:', centuryElapsedMinutes);
        showCenturyWarning();
    }
});

function setupEventListeners() {
    console.log('Setting up event listeners');
    
    // Add Item Modal
    $('#open-add-item-modal').click(function() {
        $('#addItemModal').removeClass('hidden');
        $('#addItemForm')[0].reset();
        $('#selectedProduct').val('');
        $('#product_id').val('');
        updateModalCalculations();
    });
    
    $('#close-add-item-modal').click(function() {
        $('#addItemModal').addClass('hidden');
    });
    
    $('#quantity').on('input', updateModalCalculations);
    
    $('#addItemForm').submit(function(e) {
        e.preventDefault();
        handleAddItemSubmit(e);
        return false;
    });
    
    // Bill Modal
    $('#closeBillModal').click(function() {
        $('#billModal').addClass('hidden');
    });
    
    $('#printBillBtn').click(function() {
        printBill();
    });
    
    $('#closeSessionBtn').click(function() {
        window.location.href = 'admin.php';
    });
}

// Timer Functions
function startTimers() {
    console.log('Starting timers, centuryModeActive:', centuryModeActive);
    
    // Update duration display every minute
    setInterval(updateDurationDisplay, 60000);
    
    // Update century timer if active
    if (centuryModeActive && centuryStartTime && centuryStartTime !== 'null') {
        console.log('Starting century timer with start time:', centuryStartTime);
        updateCenturyTimer();
        centuryTimerInterval = setInterval(updateCenturyTimer, 60000); // Update every minute
    } else {
        console.log('Century timer not started:', {
            centuryModeActive: centuryModeActive,
            centuryStartTime: centuryStartTime
        });
    }
}

function updateDurationDisplay() {
    totalElapsedMinutes++;
    const hours = Math.floor(totalElapsedMinutes / 60);
    const minutes = totalElapsedMinutes % 60;
    $('#duration-display').text(`${hours}h ${minutes}m`);
}

function updateCenturyTimer() {
    if (!centuryStartTime || centuryStartTime === 'null') {
        console.log('No century start time available');
        return;
    }
    
    const startTime = new Date(centuryStartTime);
    const now = new Date();
    const elapsedMs = now - startTime;
    centuryElapsedMinutes = Math.floor(elapsedMs / 60000);
    
    console.log('Updating century timer:', {
        startTime: startTime,
        now: now,
        elapsedMs: elapsedMs,
        centuryElapsedMinutes: centuryElapsedMinutes
    });
    
    $('#century-timer').text(`${centuryElapsedMinutes} minutes`);
    
    // Update progress bar
    const progressPercent = Math.min((centuryElapsedMinutes / 60) * 100, 100);
    $('#century-progress').css('width', `${progressPercent}%`);
    
    // Show warning after 20 minutes
    if (centuryElapsedMinutes >= 20 && !centuryWarningShown) {
        showCenturyWarning();
    }
    
    // Update totals if century mode is active
    if (currentRateType === 'Century') {
        updateTotals();
    }
}

function showCenturyWarning() {
    console.log('Showing century warning');
    $('#century-warning').removeClass('hidden').addClass('warning-alert');
    centuryWarningShown = true;
    
    // Update database
    $.post('update_century_warning.php', {
        session_id: sessionID,
        warning_shown: 1
    }).fail(function(error) {
        console.error('Failed to update century warning:', error);
    });
}

function hideCenturyWarning() {
    $('#century-warning').addClass('hidden');
}

// Century Mode Controls - FIXED
function startCenturyMode() {
    console.log('Starting century mode for session:', sessionID);
    
    if (confirm('Start Century Mode timer? Additional charges will apply per minute.')) {
        // Show loading
        $('.rate-option[data-rate-type="Century"]').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Starting...</div>');
        
        $.ajax({
            url: 'update_century_mode.php',
            method: 'POST',
            data: {
                session_id: sessionID,
                action: 'start'
            },
            dataType: 'json',
            success: function(response) {
                console.log('Start century mode response:', response);
                if (response && response.success) {
                    // Reload page to show updated state
                    location.reload();
                } else {
                    alert('Error: ' + (response ? response.message : 'Unknown error'));
                    location.reload(); // Reload anyway to get fresh state
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('Network error. Please try again.');
                location.reload(); // Reload on error
            }
        });
    }
}

function pauseCenturyMode() {
    console.log('Pausing century mode');
    
    if (confirm('Pause Century Mode timer?')) {
        $.ajax({
            url: 'update_century_mode.php',
            method: 'POST',
            data: {
                session_id: sessionID,
                action: 'pause',
                elapsed_minutes: centuryElapsedMinutes
            },
            dataType: 'json',
            success: function(response) {
                console.log('Pause century mode response:', response);
                if (response && response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (response ? response.message : 'Unknown error'));
                    location.reload();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('Network error. Please try again.');
                location.reload();
            }
        });
    }
}

function resetCenturyMode() {
    console.log('Resetting century mode');
    
    if (confirm('Reset Century Mode timer? This will clear all additional minute charges.')) {
        $.ajax({
            url: 'update_century_mode.php',
            method: 'POST',
            data: {
                session_id: sessionID,
                action: 'reset'
            },
            dataType: 'json',
            success: function(response) {
                console.log('Reset century mode response:', response);
                if (response && response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (response ? response.message : 'Unknown error'));
                    location.reload();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('Network error. Please try again.');
                location.reload();
            }
        });
    }
}

// Product Search
function searchProducts() {
    const searchTerm = $('#productSearch').val().toLowerCase();
    $('.product-card').each(function() {
        const productName = $(this).data('name').toLowerCase();
        if (productName.includes(searchTerm)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

function selectProduct(element, event) {
    if (event) event.stopPropagation();
    
    // Remove selection from all cards
    $('.product-card').removeClass('border-blue-500 bg-blue-50').addClass('border-gray-300');
    
    // Add selection to clicked card
    $(element).removeClass('border-gray-300').addClass('border-blue-500 bg-blue-50');
    
    // Set form values
    const productId = $(element).data('product-id');
    const productName = $(element).data('name');
    const price = $(element).data('price');
    const stock = $(element).data('stock');
    
    $('#selectedProduct').val(productName);
    $('#product_id').val(productId);
    
    // Update quantity input max attribute
    $('#quantity').attr('max', stock);
    $('#quantity').val(1);
    
    updateModalCalculations();
}

function adjustQuantity(change) {
    const quantityInput = $('#quantity');
    let currentVal = parseInt(quantityInput.val()) || 0;
    const maxVal = parseInt(quantityInput.attr('max')) || 9999;
    const newVal = currentVal + change;
    
    if (newVal >= 1 && newVal <= maxVal) {
        quantityInput.val(newVal);
        updateModalCalculations();
    }
}

function updateModalCalculations() {
    const productId = $('#product_id').val();
    const quantity = parseInt($('#quantity').val()) || 0;
    
    if (!productId || quantity <= 0) {
        $('#modal-subtotal').text('0.00 PKR');
        $('#stock-warning').addClass('hidden');
        return;
    }
    
    // Find selected product
    const selectedCard = $(`.product-card[data-product-id="${productId}"]`);
    const price = parseFloat(selectedCard.data('price') || 0);
    const stock = parseInt(selectedCard.data('stock') || 0);
    
    const subtotal = price * quantity;
    $('#modal-subtotal').text(subtotal.toFixed(2) + ' PKR');
    
    // Stock warning
    if (quantity > stock) {
        $('#stock-warning').text(`⚠ Only ${stock} left in stock!`).removeClass('hidden');
        $('#submit-add-item').prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
    } else {
        $('#stock-warning').addClass('hidden');
        $('#submit-add-item').prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
    }
}

function changeRateType(newRateType) {
    if (currentRateType === newRateType) return;
    
    if (newRateType === 'Century') {
        if (!confirm('Switch to Century Mode?\n\n• First ' + bookingDuration + ' hour(s) at normal rate\n• Additional minutes charged at ' + centuryRate + ' PKR per minute\n• 20-minute warning will appear')) {
            return;
        }
    }
    
    currentRateType = newRateType;
    $('#rateTypeInput').val(newRateType);
    
    // Update UI selection
    $('.rate-option').removeClass('selected');
    $(`.rate-option[data-rate-type="${newRateType}"]`).addClass('selected');
    
    // Recalculate and submit form
    updateTotals();
    $('#rateChangeForm').submit();
}

function updateTotals() {
    console.log('Updating totals:', {
        currentRateType: currentRateType,
        bookingDuration: bookingDuration,
        ratePerHour: ratePerHour,
        centuryElapsedMinutes: centuryElapsedMinutes,
        centuryRate: centuryRate,
        itemsTotal: itemsTotal
    });
    
    let tableCharge = 0;
    
    if (currentRateType === 'Normal') {
        tableCharge = bookingDuration * ratePerHour;
    } else {
        // Century rate: base hours + additional minutes
        const baseCharge = bookingDuration * ratePerHour;
        const additionalCharge = centuryElapsedMinutes * centuryRate;
        tableCharge = baseCharge + additionalCharge;
    }
    
    const finalTotal = tableCharge + itemsTotal;
    
    console.log('Calculated charges:', {
        tableCharge: tableCharge,
        finalTotal: finalTotal
    });
    
    $('#table-charge-display').text(tableCharge.toFixed(2) + ' PKR');
    $('#final-total-display').text(finalTotal.toFixed(2) + ' PKR');
    $('#items-total-display').text(itemsTotal.toFixed(2) + ' PKR');
}

// Remove Item Function
function removeItem(itemId, itemName) {
    if (!confirm(`Are you sure you want to remove "${itemName}" from the bill?`)) {
        return;
    }
    
    // Show loading on the item
    const itemRow = $(`[data-item-id="${itemId}"]`);
    const originalContent = itemRow.html();
    itemRow.html('<div class="text-center py-2 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i> Removing...</div>');
    
    $.ajax({
        url: 'api_remove_item.php',
        method: 'POST',
        data: {
            item_id: itemId,
            session_id: sessionID
        },
        dataType: 'json',
        success: function(response) {
            console.log('Remove item response:', response);
            if (response && response.success) {
                // Update items total
                itemsTotal = response.new_items_total || 0;
                
                // Remove the item from the list
                itemRow.fadeOut(300, function() {
                    $(this).remove();
                    
                    // If no items left, show empty message
                    if ($('#items-list-ul li').length === 0) {
                        $('#items-list-ul').html(`
                            <li class="text-xs text-gray-600 text-center py-2 border-b border-gray-300">
                                No items added yet
                            </li>
                        `);
                    }
                    
                    // Update totals
                    updateTotals();
                    
                    // Show success notification
                    showNotification('Item removed successfully!', 'success');
                });
            } else {
                // Restore original content
                itemRow.html(originalContent);
                alert('Error removing item: ' + (response ? response.message : 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            itemRow.html(originalContent);
            alert('Network error. Please try again.');
        }
    });
}

async function handleAddItemSubmit(event) {
    event.preventDefault();
    
    const productId = $('#product_id').val();
    const quantity = parseInt($('#quantity').val()) || 0;
    
    if (!productId || quantity <= 0) {
        alert('Please select a product and enter a valid quantity.');
        return;
    }
    
    // Get stock from selected card
    const selectedCard = $(`.product-card[data-product-id="${productId}"]`);
    const stock = parseInt(selectedCard.data('stock') || 0);
    
    if (quantity > stock) {
        if (!confirm(`Warning: You are adding ${quantity}, but only ${stock} is in stock. Continue anyway?`)) {
            return;
        }
    }
    
    const formData = new FormData(event.target);
    
    // Show loading
    const submitBtn = $('#submit-add-item');
    const originalText = submitBtn.html();
    submitBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Adding...');
    submitBtn.prop('disabled', true);
    
    try {
        const response = await fetch('api_add_item.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Reload the page to show updated items
            location.reload();
        } else {
            alert('Error adding item: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('AJAX Error:', error);
        alert('An unexpected network error occurred.');
    } finally {
        submitBtn.html(originalText);
        submitBtn.prop('disabled', false);
    }
}

// Bill Generation Functions
async function generateBill() {
    if (!confirm('Are you sure you want to close this session and generate the bill?\n\nThis will:' + 
                 '\n• Mark the session as Completed' +
                 '\n• Calculate final charges' +
                 '\n• Free up the table' +
                 '\n• Generate a printable bill')) {
        return;
    }
    
    // Show modal with loading state
    $('#billModal').removeClass('hidden');
    $('#billContent').empty();
    $('#billLoading').removeClass('hidden');
    $('#printBillBtn, #closeSessionBtn').addClass('hidden');
    
    // Disable UI elements to prevent further actions
    $('.rate-option, #open-add-item-modal').addClass('disabled').css('pointer-events', 'none');
    $('button').prop('disabled', true);
    
    try {
        // Send request to generate bill
        const response = await fetch('api_generate_bill.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `session_id=${sessionID}&table_id=${tableID}`
        });
        
        // Check if response is OK
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        $('#billLoading').addClass('hidden');
        
        if (data.success) {
            // Success - display bill
            displayBill(data.bill_data);
            $('#printBillBtn, #closeSessionBtn').removeClass('hidden');
            
            // Update main display with final amounts
            itemsTotal = data.bill_data.items_total || itemsTotal;
            updateTotals();
            
            // Show success notification
            showNotification('Bill generated successfully!', 'success');
            
        } else {
            // Error - show message
            $('#billContent').html(`
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <strong>Error:</strong> ${data.message || 'Unknown error'}
                </div>
                <div class="text-center mt-4">
                    <button onclick="closeBillModal()" 
                            class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                        Close
                    </button>
                </div>
            `);
        }
        
    } catch (error) {
        console.error('Error generating bill:', error);
        $('#billLoading').addClass('hidden');
        $('#billContent').html(`
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <strong>Network Error:</strong> ${error.message}
            </div>
            <div class="text-center mt-4">
                <button onclick="closeBillModal()" 
                        class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    Close
                </button>
                <button onclick="generateBill()" 
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 ml-2">
                    Retry
                </button>
            </div>
        `);
    } finally {
        // Re-enable buttons
        $('.rate-option, #open-add-item-modal').removeClass('disabled').css('pointer-events', 'auto');
        $('button').prop('disabled', false);
    }
}

function closeBillModal() {
    $('#billModal').addClass('hidden');
}

function displayBill(billData) {
    if (!billData) {
        $('#billContent').html('<div class="text-red-600">No bill data received</div>');
        return;
    }
    
    const startTime = new Date(billData.start_time);
    const endTime = new Date(billData.end_time);
    const durationMs = endTime - startTime;
    const hours = Math.floor(durationMs / (1000 * 60 * 60));
    const minutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));
    
    const billHTML = `
        <div class="bill-receipt">
            <div class="text-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Snooker Club Bill</h1>
                <p class="text-gray-600">Transaction ID: #${billData.session_id || sessionID}</p>
                <p class="text-green-600 font-semibold mt-2">✓ Bill Generated Successfully</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-bold text-lg mb-2">Session Details</h3>
                    <div class="space-y-1">
                        <p><strong>Table:</strong> ${billData.table_name}</p>
                        <p><strong>Start:</strong> ${startTime.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                        <p><strong>End:</strong> ${endTime.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                        <p><strong>Duration:</strong> ${hours}h ${minutes}m</p>
                        <p><strong>Rate Type:</strong> ${billData.rate_type}</p>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-bold text-lg mb-2">Bill Summary</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>Table Charge:</span>
                            <span class="font-semibold">${billData.table_charge.toFixed(2)} PKR</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Items Total:</span>
                            <span class="font-semibold">${billData.items_total.toFixed(2)} PKR</span>
                        </div>
                        <div class="border-t pt-2 mt-2">
                            <div class="flex justify-between text-xl font-bold">
                                <span>GRAND TOTAL:</span>
                                <span class="text-green-700">${billData.final_total.toFixed(2)} PKR</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            ${billData.items_list && billData.items_list.length > 0 ? `
            <div class="mb-6">
                <h3 class="font-bold text-lg mb-3">Items Purchased</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 text-left">Item</th>
                                <th class="p-2 text-left">Qty</th>
                                <th class="p-2 text-left">Price</th>
                                <th class="p-2 text-left">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${billData.items_list.map(item => `
                                <tr class="border-b">
                                    <td class="p-2">${item.item_name}</td>
                                    <td class="p-2">${item.quantity}</td>
                                    <td class="p-2">${parseFloat(item.price_per_unit).toFixed(2)} PKR</td>
                                    <td class="p-2 font-semibold">${parseFloat(item.total_item_price).toFixed(2)} PKR</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
            ` : ''}
            
            <div class="text-center text-gray-500 text-sm border-t pt-4">
                <p>Thank you for playing at Snooker Club!</p>
                <p>Bill generated on ${new Date().toLocaleString()}</p>
            </div>
        </div>
    `;
    
    $('#billContent').html(billHTML);
}

function printBill() {
    const printContent = $('.bill-receipt').html();
    const printWindow = window.open('', '', 'width=800,height=600');
    printWindow.document.write(`
        <html>
            <head>
                <title>Print Bill - Session #${sessionID}</title>
                <script src="https://cdn.tailwindcss.com"><\/script>
                <style>
                    @media print {
                        body { padding: 20px; }
                        .no-print { display: none !important; }
                    }
                </style>
            </head>
            <body class="p-6">
                ${printContent}
                <div class="no-print mt-6 text-center">
                    <button onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded">🖨️ Print Now</button>
                    <button onclick="window.close()" class="bg-gray-600 text-white px-4 py-2 rounded ml-4">Close</button>
                </div>
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
}

// Notification function
function showNotification(message, type = 'info') {
    const colors = {
        success: 'bg-green-100 border-green-400 text-green-700',
        error: 'bg-red-100 border-red-400 text-red-700',
        info: 'bg-blue-100 border-blue-400 text-blue-700'
    };
    
    const notification = $(`
        <div class="notification fixed top-4 right-4 z-50 px-6 py-3 rounded-lg border ${colors[type]} animate-fade-in shadow-lg">
            <div class="flex items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-3"></i>
                <span>${message}</span>
            </div>
        </div>
    `);
    
    $('body').append(notification);
    
    setTimeout(() => {
        notification.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}

// Make functions available globally
window.changeRateType = changeRateType;
window.generateBill = generateBill;
window.startCenturyMode = startCenturyMode;
window.pauseCenturyMode = pauseCenturyMode;
window.resetCenturyMode = resetCenturyMode;
window.selectProduct = selectProduct;
window.adjustQuantity = adjustQuantity;
window.searchProducts = searchProducts;
window.hideCenturyWarning = hideCenturyWarning;
window.removeItem = removeItem;
</script>
</body>
</html>