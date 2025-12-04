<?php
session_start();
// --- Configuration and Connection ---
include 'db.php';

// Ensure table_id and session_id are passed
$table_id = $_GET['table_id'] ?? die("Table ID required.");
$session_id = $_GET['session_id'] ?? die("Session ID required.");

// --- 1. Fetch Session and Table Rates ---
$stmt = $conn->prepare("
    SELECT 
        s.start_time, s.rate_type, 
        st.table_name, st.rate_per_hour, st.century_rate
    FROM 
        snooker_sessions s
    JOIN 
        snooker_tables st ON s.id = st.id
    WHERE 
        s.session_id = ? AND s.status = 'Active'
");
$stmt->bind_param("i", $session_id);
$stmt->execute();
$result = $stmt->get_result();
$session_data = $result->fetch_assoc();
$stmt->close();

if (!$session_data) {
    die("Error: No active session found for this ID.");
}

$start_time_db = $session_data['start_time'];
$start_time_display = date('g:i A', strtotime($start_time_db));
$rate_per_hour = (float)$session_data['rate_per_hour'];
$century_rate = (float)$session_data['century_rate'];

// --- 2. Fetch Items Added ---
$stmt_items = $conn->prepare("
    SELECT 
        item_name, quantity, price_per_unit, (quantity * price_per_unit) AS total_item_price
    FROM 
        session_items
    WHERE 
        session_id = ?
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
    ORDER BY name ASC
");
$stmt_products->execute();
$products_result = $stmt_products->get_result();

while ($product = $products_result->fetch_assoc()) {
    $products_for_modal[] = $product;
}
$stmt_products->close();


// --- 4. Calculation Function (PHP version for initial display) ---
function calculate_table_charge($start_time, $rate_type, $rate_per_hour, $century_rate, $current_time = null) {
    if ($current_time === null) {
        $current_time = new DateTime();
    }
    $start = new DateTime($start_time);
    $interval = $start->diff($current_time);

    $duration_seconds =
        $interval->y * 365 * 24 * 60 * 60 +
        $interval->m * 30 * 24 * 60 * 60 +
        $interval->d * 24 * 60 * 60 +
        $interval->h * 60 * 60 +
        $interval->i * 60 +
        $interval->s;

    if ($rate_type == 'Normal') {
        $duration_hours = $duration_seconds / 3600;
        $table_charge = $duration_hours * $rate_per_hour;
    } else {
        $duration_minutes = $duration_seconds / 60;
        $table_charge = $duration_minutes * $century_rate;
    }

    return round($table_charge, 2);
}

$table_charge = calculate_table_charge($start_time_db, $session_data['rate_type'], $rate_per_hour, $century_rate);
$final_total = $table_charge + $items_total;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Active Session: <?php echo htmlspecialchars($session_data['table_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Optional: Tailwind doesn't have a 'snooker-green' color by default */
        .text-snooker-green { color: #1e8449; } 
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
<div class="flex">

    <?php include 'layout/sidebar.php'; ?>
    <?php include 'layout/header.php';?>

    <div class="flex-1 p-8 mt-8">
        <div class="max-w-2xl mx-auto bg-white shadow-xl rounded-2xl p-8 border border-gray-200 ">

            <h1 class="text-3xl font-extrabold text-center mb-3 text-orange-600">
                <?php echo htmlspecialchars($session_data['table_name']); ?>  
                <span class="text-blue-600">• ACTIVE</span>
            </h1>

            <div class="bg-gray-50 p-4 rounded-lg mb-3">
                <p class="text-lg"><strong>Start Time:</strong> <?php echo $start_time_display; ?></p>
                <p class="text-lg mt-1"><strong>Duration:</strong> 
                    <span id="duration-timer" class="font-mono text-xl text-blue-600">00:00:00</span>
                </p>
            </div>

            <div class="mb-3">
                <strong class="block text-lg mb-2">Rate Type</strong>
                <form action="change_rate.php" method="POST" class="space-x-6 flex">
                    <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                    <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">

                    <label class="inline-flex items-center text-lg">
                        <input type="radio" name="rate_type" value="Normal"
                            <?php echo ($session_data['rate_type'] == 'Normal') ? 'checked' : ''; ?>
                            onchange="this.form.submit()">
                        <span class="ml-2">Normal (<?php echo $rate_per_hour; ?> PKR/hr)</span>
                    </label>

                    <label class="inline-flex items-center text-lg">
                        <input type="radio" name="rate_type" value="Century"
                            <?php echo ($session_data['rate_type'] == 'Century') ? 'checked' : ''; ?>
                            onchange="this.form.submit()">
                        <span class="ml-2">Century (<?php echo $century_rate; ?> PKR/min)</span>
                    </label>
                </form>
            </div>

            <h2 class="text-xl font-bold mb-1">
                Items Added ( <span class="text-green-700"><?php echo number_format($items_total, 2); ?></span> PKR )
            </h2>

            <ul class="ml-4 space-y-1" id="items-list-ul">
                <?php if (empty($items_list)): ?>
                    <li class="text-gray-500">No items added yet.</li>
                <?php else: ?>
                    <?php foreach ($items_list as $item): ?>
                        <li class="flex justify-between text-lg">
                            <span>- <?php echo htmlspecialchars($item['item_name']); ?> x <?php echo $item['quantity']; ?></span>
                            <strong><?php echo number_format($item['total_item_price'], 2); ?> PKR</strong>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>

            <button id="open-add-item-modal"
                class="mt-1 w-full bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 text-lg">
                ➕ Add Item to Bill
            </button>

            <hr class="my-6">

            <div class="bg-gray-50 p-6 rounded-xl shadow-inner border">

                <div class="flex justify-between text-2xl font-bold mb-3">
                    <span class="text-gray-700">Table Play Charge:</span>
                    <span id="table-charge-display" class="text-gray-900">
                        <?php echo number_format($table_charge, 2); ?> PKR
                    </span>
                </div>

                <div class="flex justify-between text-3xl font-extrabold mt-4 text-green-700">
                    <span>FINAL TOTAL BILL:</span>
                    <span id="final-total-display">
                        <?php echo number_format($final_total, 2); ?> PKR
                    </span>
                </div>

            </div>
<div class="mt-8">
    <form action="generate_bill.php" method="POST" class="flex items-center gap-4">
        <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
        <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
  <!-- Right Button -->
        <a href="admin.php"
           class="flex-1 block text-center bg-orange-700 text-white text-xl py-3 rounded-lg hover:bg-gray-800">
            ⬅ Back to Admin Panel
        </a>
        <!-- Left Button -->
        <button type="submit" name="close_bill"
            class="flex-1 bg-blue-600 text-white text-xl py-3 rounded-lg hover:bg-red-700">
            Close & Generate Bill
        </button>

       
    </form>
</div>


        </div>
    </div>
</div>


<div id="addItemModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-2xl w-full max-w-md">
        <h2 class="text-2xl font-bold mb-4 border-b pb-2">Add Product to Order</h2>
        
        <form id="addItemForm" method="POST" class="space-y-4">
            <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
            
            <div>
                <label for="product_id" class="block text-gray-700 font-medium mb-1">Select Product</label>
                <select name="product_id" id="product_id" required
                        class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="" data-price="0" data-stock="0">-- Select Product --</option>
                    <?php foreach ($products_for_modal as $product): ?>
                        <option 
                            value="<?php echo $product['product_id']; ?>" 
                            data-price="<?php echo $product['selling_price']; ?>"
                            data-stock="<?php echo $product['stock_quantity']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?> (<?php echo number_format($product['selling_price'], 2); ?> PKR) 
                            (Stock: <?php echo (int)$product['stock_quantity']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="quantity" class="block text-gray-700 font-medium mb-1">Quantity</label>
                <input type="number" name="quantity" id="quantity" value="1" min="1" required
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p id="stock-warning" class="text-sm text-red-500 mt-1 hidden">Low Stock!</p>
            </div>

            <div class="pt-2">
                <p class="text-xl font-bold">Sub-Total: <span id="modal-subtotal" class="text-green-600">0.00 PKR</span></p>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" id="close-add-item-modal"
                        class="bg-gray-300 px-5 py-2 rounded hover:bg-gray-400 transition">
                    Cancel
                </button>
                <button type="submit"
                        class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 transition">
                    Add Item
                </button>
            </div>
        </form>
    </div>
</div>


<script>
    // PHP Variables passed to JS
    const sessionID = <?php echo $session_id; ?>;
    const startTime = new Date("<?php echo $start_time_db; ?>").getTime();
    let itemsTotal = <?php echo $items_total; ?>; // 'let' because we update it via AJAX
    const ratePerHour = <?php echo $rate_per_hour; ?>;
    const centuryRate = <?php echo $century_rate; ?>;
    let rateType = "<?php echo $session_data['rate_type']; ?>";

    // DOM Elements
    const durationTimer = document.getElementById('duration-timer');
    const tableChargeDisplay = document.getElementById('table-charge-display');
    const finalTotalDisplay = document.getElementById('final-total-display');
    const itemsTotalDisplay = document.querySelector('h2 span.text-green-700'); 
    const itemsListUL = document.getElementById('items-list-ul'); 
    const initialEmptyItem = '<li class="text-gray-500">No items added yet.</li>';

    // Modal Elements
    const addItemModal = document.getElementById('addItemModal');
    const openModalBtn = document.getElementById('open-add-item-modal');
    const closeModalBtn = document.getElementById('close-add-item-modal');
    const addItemForm = document.getElementById('addItemForm');
    const productSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity');
    const modalSubtotalDisplay = document.getElementById('modal-subtotal');
    const stockWarning = document.getElementById('stock-warning');


    // =======================================================
    // 1. TIMER & TOTAL CALCULATION LOGIC
    // =======================================================

    function updateTimerAndTotal() {
        const now = Date.now();
        const durationMs = now - startTime;

        const seconds = Math.floor((durationMs / 1000) % 60);
        const minutes = Math.floor((durationMs / 60000) % 60);
        const hours = Math.floor(durationMs / 3600000);

        durationTimer.textContent =
            String(hours).padStart(2,'0') + ":" +
            String(minutes).padStart(2,'0') + ":" +
            String(seconds).padStart(2,'0');

        let tableCharge = 0;
        const durationHours = durationMs / 3600000;
        const durationMinutes = durationMs / 60000;

        tableCharge = (rateType === 'Normal')
            ? durationHours * ratePerHour
            : durationMinutes * centuryRate;

        const finalTotal = tableCharge + itemsTotal;

        tableChargeDisplay.textContent = tableCharge.toFixed(2) + " PKR";
        finalTotalDisplay.textContent = finalTotal.toFixed(2) + " PKR";
        itemsTotalDisplay.textContent = itemsTotal.toFixed(2);
    }

    updateTimerAndTotal();
    setInterval(updateTimerAndTotal, 1000);


    // =======================================================
    // 2. MODAL INTERACTION LOGIC
    // =======================================================

    openModalBtn.addEventListener('click', () => {
        addItemModal.classList.remove('hidden');
        addItemForm.reset(); 
        modalSubtotalDisplay.textContent = "0.00 PKR";
        stockWarning.classList.add('hidden');
    });

    closeModalBtn.addEventListener('click', () => {
        addItemModal.classList.add('hidden');
    });

    // Calculate subtotal in modal and check stock
    function updateModalCalculations() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const price = parseFloat(selectedOption.dataset.price || 0);
        const stock = parseInt(selectedOption.dataset.stock || 0);
        const quantity = parseInt(quantityInput.value) || 0;
        
        const subtotal = price * quantity;
        modalSubtotalDisplay.textContent = subtotal.toFixed(2) + " PKR";
        
        // Stock warning
        if (quantity > stock) {
            stockWarning.textContent = `Warning: Only ${stock} left in stock!`;
            stockWarning.classList.remove('hidden');
        } else {
            stockWarning.classList.add('hidden');
        }
    }

    productSelect.addEventListener('change', updateModalCalculations);
    quantityInput.addEventListener('input', updateModalCalculations);


    // =======================================================
    // 3. AJAX SUBMISSION LOGIC (for Add Item)
    // =======================================================

    function renderItemList(items) {
        itemsListUL.innerHTML = ''; 
        if (items.length === 0) {
            itemsListUL.innerHTML = initialEmptyItem;
        } else {
            items.forEach(item => {
                const li = document.createElement('li');
                li.className = 'flex justify-between text-lg';
                li.innerHTML = `
                    <span>- ${item.item_name} x ${item.quantity}</span>
                    <strong>${parseFloat(item.total_item_price).toFixed(2)} PKR</strong>
                `;
                itemsListUL.appendChild(li);
            });
        }
    }

    addItemForm.addEventListener('submit', function(event) {
        event.preventDefault(); 
        
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const stock = parseInt(selectedOption.dataset.stock || 0);
        const quantity = parseInt(quantityInput.value) || 0;

        if (quantity <= 0 || productSelect.value === "") {
            alert("Please select a product and enter a quantity greater than zero.");
            return;
        }

        if (quantity > stock && !confirm(`Warning: You are adding ${quantity}, but only ${stock} is in stock. Continue anyway?`)) {
            return;
        }

        const formData = new FormData(this);
        const endpoint = 'api_add_item.php'; 

        fetch(endpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update total variables and display
                itemsTotal = data.new_items_total; 
                updateTimerAndTotal(); 

                // Re-render the item list
                renderItemList(data.items_list);

                // Close modal and clear form
                addItemModal.classList.add('hidden');
                addItemForm.reset();
                alert(`Item '${data.item_name}' added successfully!`);
                
            } else {
                alert('Error adding item: ' + data.message);
            }
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            alert('An unexpected network error occurred.');
        });
    });
</script>

</body>
</html>