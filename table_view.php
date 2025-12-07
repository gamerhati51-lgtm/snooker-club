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
        s.start_time, s.rate_type, total_time_minutes,
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
$booking_duration = isset($session_data['booking_duration']) ? (int)$session_data['booking_duration'] : 1; // Default 1 hour

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

// --- 4. Calculation Function ---
function calculate_table_charge($rate_type, $rate_per_hour, $century_rate, $booking_duration = 1) {
    if ($rate_type == 'Normal') {
        $table_charge = $booking_duration * $rate_per_hour;
    } else {
        // Century rate: 20% increase from normal rate
        $normal_charge = $booking_duration * $rate_per_hour;
        $increase_amount = $normal_charge * 0.20; // 20% increase
        $table_charge = $normal_charge + $increase_amount;
    }

    return round($table_charge, 2);
}

// Get current rate type
$rate_type = $session_data['rate_type'] ?? 'Normal';

// Calculate charges
$table_charge = calculate_table_charge($rate_type, $rate_per_hour, $century_rate, $booking_duration);
$final_total = $table_charge + $items_total;

// Calculate what century rate would be (for display)
$normal_charge = $booking_duration * $rate_per_hour;
$century_charge = $normal_charge + ($normal_charge * 0.20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Active Session: <?php echo htmlspecialchars($session_data['table_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    </style>
</head>

<body class="bg-blue-100 font-sans">

  <!-- Dashboard Container -->
  <div class="flex min-h-screen">

    <!-- Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 ml-0 lg:ml-64 pt-20 p-8 main-content"> <!-- pt-20 = header height -->
      
      <!-- Header -->
      <?php include "layout/header.php"; ?>

      <!-- Page Content -->
      <div id="content-area" class="space-y-8 bg-blue-200 p-6 rounded-lg">
            <div class="mb-4">
                <h1 class="text-xl md:text-2xl font-bold text-center text-orange-700">
                    <?php echo htmlspecialchars($session_data['table_name']); ?>
                    <span class="text-blue-700 text-xs ml-1">• ACTIVE</span>
                </h1>
                
                <div class="bg-blue-100 p-3 rounded-lg mt-2 border border-blue-300">
                    <p class="text-xs md:text-sm text-gray-900">
                        <strong class="text-gray-800">Start Time:</strong> <?php echo $start_time_display; ?>
                    </p>
                    <p class="text-xs md:text-sm text-gray-900 mt-1">
                        <strong class="text-gray-800">Duration:</strong> 
                        <span class="font-bold text-blue-700">
                            <?php echo $booking_duration; ?> hour(s)
                        </span>
                    </p>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <!-- Rate Selection -->
                    <div>
                        <h3 class="text-sm font-semibold mb-1 text-gray-800">Select Rate Type</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="rate-option p-3 rounded-lg cursor-pointer border-2 transition-all duration-200 <?php echo $rate_type == 'Normal' ? 'border-green-600 bg-green-100 shadow-sm' : 'border-gray-300 hover:border-green-500'; ?>"
                                 data-rate-type="Normal" 
                                 onclick="changeRateType('Normal')">
                                <div class="text-center">
                                    <div class="text-sm font-bold text-gray-900">Normal Rate</div>
                                    <div class="text-sm font-semibold text-green-700 mt-1"><?php echo number_format($rate_per_hour, 2); ?> PKR/hr</div>
                                    <div class="text-xs text-gray-700 mt-0.5">Total: <?php echo number_format($normal_charge, 2); ?> PKR</div>
                                </div>
                            </div>
                            
                            <div class="rate-option p-3 rounded-lg cursor-pointer border-2 transition-all duration-200 <?php echo $rate_type == 'Century' ? 'border-purple-600 bg-purple-100 shadow-sm' : 'border-gray-300 hover:border-purple-500'; ?>"
                                 data-rate-type="Century" 
                                 onclick="changeRateType('Century')">
                                <div class="text-center">
                       <div class="text-sm font-bold text-gray-900">Century Rate</div>
                                    <div class="text-sm font-semibold text-purple-700 mt-1"><?php echo number_format($century_rate, 2); ?> PKR/min</div>
                       <div class="text-xs text-gray-700 mt-0.5">Total: <?php echo number_format($century_charge, 2); ?> PKR (+20%)</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden form for rate change -->
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
                                    <li class="flex justify-between items-center text-sm py-2 border-b border-gray-300 last:border-0">
                                        <div class="flex items-center">
                                            <span class="text-gray-900 font-medium">
                                                <?php echo htmlspecialchars($item['item_name']); ?>
                                            </span>
                                            <span class="text-xs text-gray-600 ml-2">
                                                ×<?php echo $item['quantity']; ?>
                                            </span>
                                        </div>
                                        <strong class="text-gray-900 font-bold">
                                            <?php echo number_format($item['total_item_price'], 2); ?> PKR
                                        </strong>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                        
                        <button id="open-add-item-modal"
                            class="mt-4 w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white text-sm py-2.5 rounded-lg font-bold transition-all duration-200 shadow-sm">
                            ➕ Add Item to Bill
                        </button>
                    </div>
                </div>

              <!-- Right Column - Bill Summary -->
<div class="bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-800 dark:to-blue-900/20 
            p-5 rounded-lg border-2 border-blue-200 dark:border-blue-700">
    <h3 class="text-lg font-bold mb-1 text-gray-900 dark:text-gray-100 border-b 
               border-gray-300 dark:border-gray-600 pb-2">Bill Summary</h3>
    
    <div class="space-y-3 mb-2">
        <div class="flex justify-between items-center">
            <span class="text-sm text-gray-800 dark:text-gray-300">Base Rate:</span>
            <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                <?php echo number_format($rate_per_hour, 2); ?> PKR/hr
            </span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-sm text-gray-800 dark:text-gray-300">Duration:</span>
            <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                <?php echo $booking_duration; ?> hour(s)
            </span>
        </div>
        <?php if ($rate_type == 'Century'): ?>
        <div class="flex justify-between items-center bg-red-50 dark:bg-red-900/20 
                    p-2 rounded border border-red-200 dark:border-red-700">
            <span class="text-sm text-gray-800 dark:text-gray-300">20% Increase:</span>
            <span class="text-sm font-bold text-red-700 dark:text-red-400">
                +<?php echo number_format($normal_charge * 0.20, 2); ?> PKR
            </span>
        </div>
        <?php endif; ?>
    </div>

    <div class="py-4 border-t border-gray-400 dark:border-gray-600">
        <div class="flex justify-between items-center mb-1">
            <span class="text-base font-bold text-gray-900 dark:text-gray-100">
                Table Play Charge:
            </span>
            <span id="table-charge-display" 
                  class="text-lg font-extrabold text-gray-900 dark:text-gray-100">
                <?php echo number_format($table_charge, 2); ?> PKR
            </span>
        </div>

        <div class="bg-gradient-to-r from-green-50 to-emerald-100 
                    dark:from-green-900/20 dark:to-emerald-900/20 
                    p-4 rounded-lg border border-green-300 dark:border-green-700">
            <div class="flex justify-between items-center">
                <span class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    FINAL TOTAL:
                </span>
                <span id="final-total-display" 
                      class="text-2xl font-extrabold text-green-800 dark:text-green-400">
                    <?php echo number_format($final_total, 2); ?> PKR
                </span>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="mt-6 pt-4 border-t border-gray-400 dark:border-gray-600 space-y-3">
   <!-- Replace the existing form with this -->
<div class="mt-6 pt-4 border-t border-gray-400 dark:border-gray-600 space-y-3">
    <button type="button" onclick="generateBill()"
            class="w-full bg-gradient-to-r from-green-600 to-emerald-700
                   hover:from-green-700 hover:to-emerald-800
                   dark:from-green-700 dark:to-emerald-800
                   dark:hover:from-green-800 dark:hover:to-emerald-900
                   text-white text-sm py-2.5 rounded-md font-semibold 
                   transition-all duration-200 shadow-md mb-2">
        💰 CLOSE & GENERATE BILL
    </button>

    <a href="admin.php"
       class="block w-full text-center bg-gradient-to-r from-gray-700 to-gray-900
              hover:from-gray-800 hover:to-black
              dark:from-gray-800 dark:to-gray-900
              dark:hover:from-gray-900 dark:hover:to-black
              text-white text-sm py-2.5 rounded-md font-semibold 
              transition-all duration-200 shadow-md">
        ⬅ BACK TO ADMIN PANEL
    </a>
</div>
    </div>
</div>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div id="addItemModal" class="fixed inset-0 bg-gray-900/80 hidden flex items-center justify-center z-50">
    <div class="bg-white p-5 rounded-xl shadow-2xl w-full max-w-md mx-4 border-2 border-blue-300">
        <h2 class="text-lg font-bold mb-2 pb-3 border-b border-gray-300 text-gray-900">Add Product to Order</h2>
        
        <form id="addItemForm" method="POST" class="space-y-4">
            <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
            
            <div>
                <label for="product_id" class="block text-sm font-bold mb-2 text-gray-800">Select Product</label>
                <select name="product_id" id="product_id" required
                        class="w-full px-4 py-2.5 text-sm border-2 border-gray-400 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-300">
                    <option value="" data-price="0" data-stock="0">-- Select Product --</option>
                    <?php foreach ($products_for_modal as $product): ?>
                        <option 
                            value="<?php echo $product['product_id']; ?>" 
                            data-price="<?php echo $product['selling_price']; ?>"
                            data-stock="<?php echo $product['stock_quantity']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?> 
                            (<?php echo number_format($product['selling_price'], 2); ?> PKR)
                            (Stock: <?php echo (int)$product['stock_quantity']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="quantity" class="block text-sm font-bold mb-2 text-gray-800">Quantity</label>
                <input type="number" name="quantity" id="quantity" value="1" min="1" required
                       class="w-full px-4 py-2.5 text-sm border-2 border-gray-400 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-300">
                <p id="stock-warning" class="text-sm text-red-600 font-bold mt-1 hidden">⚠ Low Stock Warning!</p>
            </div>

            <div class="pt-3">
                <p class="text-base font-bold text-gray-900">
                    Sub-Total: <span id="modal-subtotal" class="text-green-700 text-lg">0.00 PKR</span>
                </p>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" id="close-add-item-modal"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-900 px-4 py-2.5 text-sm font-bold rounded-lg transition border-2 border-gray-400">
                    Cancel
                </button>
                <button type="submit"
                        class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-2.5 text-sm font-bold rounded-lg transition shadow-sm">
                    Add Item
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bill Generation Modal -->
<div id="billModal" class="fixed inset-0 bg-gray-900/90 hidden flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-y-auto border-2 border-green-500">
        <div class="flex justify-between items-center mb-4 border-b pb-4">
            <h2 class="text-2xl font-bold text-gray-900">🧾 Final Bill Receipt</h2>
            <button id="closeBillModal" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
        </div>
        
        <!-- Bill Content will be loaded here -->
        <div id="billContent"></div>
        
        <!-- Loading indicator -->
        <div id="billLoading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
            <p class="mt-4 text-gray-600">Generating bill...</p>
        </div>
        
        <!-- Actions -->
        <div class="mt-6 pt-4 border-t border-gray-300 flex justify-between space-x-4">
            <button id="printBillBtn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-lg font-semibold transition hidden">
                🖨️ Print Bill
            </button>
            <button id="closeSessionBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-lg font-semibold transition hidden">
                ✅ Return to Dashboard
            </button>
        </div>
    </div>
</div>
<script>
    // PHP Variables passed to JS
    const sessionID = <?php echo $session_id; ?>;
    const bookingDuration = <?php echo $booking_duration; ?>;
    let itemsTotal = <?php echo $items_total; ?>;
    const ratePerHour = <?php echo $rate_per_hour; ?>;
    let currentRateType = "<?php echo $rate_type; ?>";
    const tableID = <?php echo $table_id; ?>;

    // DOM Elements
    const tableChargeDisplay = document.getElementById('table-charge-display');
    const finalTotalDisplay = document.getElementById('final-total-display');
    const itemsTotalDisplay = document.getElementById('items-total-display');
    const itemsListUL = document.getElementById('items-list-ul');
    const rateTypeInput = document.getElementById('rateTypeInput');

    // Modal Elements
    const addItemModal = document.getElementById('addItemModal');
    const openModalBtn = document.getElementById('open-add-item-modal');
    const closeModalBtn = document.getElementById('close-add-item-modal');
    const addItemForm = document.getElementById('addItemForm');
    const productSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity');
    const modalSubtotalDisplay = document.getElementById('modal-subtotal');
    const stockWarning = document.getElementById('stock-warning');

    // Bill modal elements
    const billModal = document.getElementById('billModal');
    const billContent = document.getElementById('billContent');
    const billLoading = document.getElementById('billLoading');
    const printBillBtn = document.getElementById('printBillBtn');
    const closeSessionBtn = document.getElementById('closeSessionBtn');
    const closeBillModal = document.getElementById('closeBillModal');

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize calculations
        calculateTotal();
        
        // Set up rate option selection
        document.querySelectorAll('.rate-option').forEach(option => {
            if (option.dataset.rateType === currentRateType) {
                option.classList.add('selected');
            }
        });
        
        // Set up modal event listeners
        setupModalListeners();
        
        // Set up bill generation
        setupBillGeneration();
    });

    // =======================================================
    // CALCULATION FUNCTIONS
    // =======================================================

    function calculateTotal() {
        let tableCharge = 0;
        
        if (currentRateType === 'Normal') {
            tableCharge = bookingDuration * ratePerHour;
        } else {
            // Century rate: 20% increase
            const normalCharge = bookingDuration * ratePerHour;
            const increaseAmount = normalCharge * 0.20;
            tableCharge = normalCharge + increaseAmount;
        }
        
        const finalTotal = tableCharge + itemsTotal;
        
        tableChargeDisplay.textContent = tableCharge.toFixed(2) + " PKR";
        finalTotalDisplay.textContent = finalTotal.toFixed(2) + " PKR";
        itemsTotalDisplay.textContent = itemsTotal.toFixed(2) + " PKR";
        
        return { tableCharge, finalTotal };
    }

    // =======================================================
    // RATE TYPE FUNCTIONS
    // =======================================================

    function changeRateType(newRateType) {
        // Update UI selection
        document.querySelectorAll('.rate-option').forEach(option => {
            if (option.dataset.rateType === newRateType) {
                option.classList.add('selected');
            } else {
                option.classList.remove('selected');
            }
        });
        
        // Update hidden form input
        rateTypeInput.value = newRateType;
        
        // Update current rate type and recalculate
        currentRateType = newRateType;
        calculateTotal();
        
        // Submit form to save to database
        document.getElementById('rateChangeForm').submit();
    }

    // =======================================================
    // ITEM MANAGEMENT FUNCTIONS
    // =======================================================

    function setupModalListeners() {
        // Open modal
        if (openModalBtn) {
            openModalBtn.addEventListener('click', () => {
                addItemModal.classList.remove('hidden');
                addItemForm.reset();
                updateModalCalculations();
            });
        }

        // Close modal
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => {
                addItemModal.classList.add('hidden');
            });
        }

        // Calculate subtotal in modal and check stock
        if (productSelect) {
            productSelect.addEventListener('change', updateModalCalculations);
        }
        
        if (quantityInput) {
            quantityInput.addEventListener('input', updateModalCalculations);
        }

        // Handle form submission
        if (addItemForm) {
            addItemForm.addEventListener('submit', handleAddItemSubmit);
        }
    }

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

    function renderItemList(items) {
        itemsListUL.innerHTML = '';
        
        if (!items || items.length === 0) {
            itemsListUL.innerHTML = `
                <li class="text-xs text-gray-600 text-center py-2 border-b border-gray-300">
                    No items added yet
                </li>
            `;
        } else {
            items.forEach(item => {
                const li = document.createElement('li');
                li.className = 'flex justify-between items-center text-sm py-2 border-b border-gray-300 last:border-0';
                li.innerHTML = `
                    <div class="flex items-center">
                        <span class="text-gray-900 font-medium">
                            ${item.item_name}
                        </span>
                        <span class="text-xs text-gray-600 ml-2">
                            ×${item.quantity}
                        </span>
                    </div>
                    <strong class="text-gray-900 font-bold">
                        ${parseFloat(item.total_item_price).toFixed(2)} PKR
                    </strong>
                `;
                itemsListUL.appendChild(li);
            });
        }
    }

    async function handleAddItemSubmit(event) {
        event.preventDefault();
        
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const stock = parseInt(selectedOption.dataset.stock || 0);
        const quantity = parseInt(quantityInput.value) || 0;
        const productName = selectedOption.text.split('(')[0].trim();

        if (quantity <= 0 || productSelect.value === "") {
            alert("Please select a product and enter a quantity greater than zero.");
            return;
        }

        if (quantity > stock) {
            const confirmAdd = confirm(`Warning: You are adding ${quantity}, but only ${stock} is in stock. Continue anyway?`);
            if (!confirmAdd) return;
        }

        const formData = new FormData(addItemForm);
        
        try {
            const response = await fetch('api_add_item.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update items total
                itemsTotal = data.new_items_total;
                
                // Recalculate and update displays
                calculateTotal();
                
                // Update items list
                renderItemList(data.items_list);
                
                // Close modal and reset form
                addItemModal.classList.add('hidden');
                addItemForm.reset();
                
                // Show success message (optional)
                // alert(`Item '${productName}' added successfully!`);
                
                // Update product stock in dropdown
                updateProductStockInDropdown(parseInt(productSelect.value), stock - quantity);
                
            } else {
                alert('Error adding item: ' + data.message);
            }
        } catch (error) {
            console.error('AJAX Error:', error);
            alert('An unexpected network error occurred.');
        }
    }

    function updateProductStockInDropdown(productId, newStock) {
        const option = productSelect.querySelector(`option[value="${productId}"]`);
        if (option) {
            option.dataset.stock = newStock;
            // Update display text
            const price = parseFloat(option.dataset.price);
            const name = option.text.split('(')[0].trim();
            option.text = `${name} (${price.toFixed(2)} PKR) (Stock: ${newStock})`;
            
            // Re-check current selection
            if (parseInt(productSelect.value) === productId) {
                updateModalCalculations();
            }
        }
    }

    // =======================================================
    // BILL GENERATION FUNCTIONS
    // =======================================================

    function setupBillGeneration() {
        // Close bill modal
        if (closeBillModal) {
            closeBillModal.addEventListener('click', () => {
                billModal.classList.add('hidden');
            });
        }

        // Print bill button
        if (printBillBtn) {
            printBillBtn.addEventListener('click', printBill);
        }

        // Close session button
        if (closeSessionBtn) {
            closeSessionBtn.addEventListener('click', () => {
                window.location.href = 'admin.php';
            });
        }

        // Handle close bill button click
        const closeBillBtn = document.querySelector('button[onclick="generateBill()"]');
        if (closeBillBtn) {
            closeBillBtn.addEventListener('click', generateBill);
        }
    }

    async function generateBill() {
        // Show modal with loading state
        billModal.classList.remove('hidden');
        billContent.innerHTML = '';
        billLoading.classList.remove('hidden');
        printBillBtn.classList.add('hidden');
        closeSessionBtn.classList.add('hidden');
        
        // Prepare form data
        const formData = new FormData();
        formData.append('session_id', sessionID);
        formData.append('table_id', tableID);
        
        try {
            const response = await fetch('api_generate_bill.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            billLoading.classList.add('hidden');
            
            if (data.success) {
                displayBill(data.bill_data);
                printBillBtn.classList.remove('hidden');
                closeSessionBtn.classList.remove('hidden');
                
                // Disable rate change buttons and add item button
                document.querySelectorAll('.rate-option').forEach(btn => {
                    btn.style.opacity = '0.5';
                    btn.style.pointerEvents = 'none';
                    btn.classList.add('cursor-not-allowed');
                });
                
                if (openModalBtn) {
                    openModalBtn.disabled = true;
                    openModalBtn.style.opacity = '0.5';
                    openModalBtn.style.pointerEvents = 'none';
                    openModalBtn.classList.add('cursor-not-allowed');
                }
                
                // Update the main bill total to match generated bill
                itemsTotal = data.bill_data.items_total;
                calculateTotal();
                
            } else {
                billContent.innerHTML = `
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <strong>Error:</strong> ${data.message}
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error:', error);
            billLoading.classList.add('hidden');
            billContent.innerHTML = `
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <strong>Network Error:</strong> Could not generate bill.
                </div>
            `;
        }
    }

    function displayBill(billData) {
        // Calculate duration
        const startTime = new Date(billData.start_time);
        const endTime = new Date(billData.end_time);
        const durationMs = endTime - startTime;
        const hours = Math.floor(durationMs / (1000 * 60 * 60));
        const minutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((durationMs % (1000 * 60)) / 1000);
        
        const durationDisplay = `${hours} hours, ${minutes} minutes, ${seconds} seconds`;
        
        // Format bill HTML
        const billHTML = `
            <div class="bill-receipt">
                <!-- Header -->
                <div class="text-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-900">Snooker Club Bill</h1>
                    <p class="text-gray-600">Transaction ID: #${billData.session_id}</p>
                    <p class="text-green-600 font-semibold mt-2">✓ Bill Generated Successfully</p>
                </div>
                
                <!-- Session Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-bold text-lg mb-2">Session Details</h3>
                        <div class="space-y-1">
                            <p><strong>Table:</strong> ${billData.table_name}</p>
                            <p><strong>Start:</strong> ${new Date(billData.start_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                            <p><strong>End:</strong> ${new Date(billData.end_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                            <p><strong>Duration:</strong> ${durationDisplay}</p>
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
                
                <!-- Items List -->
                <div class="mb-6">
                    <h3 class="font-bold text-lg mb-3">Items Purchased</h3>
                    ${billData.items_list.length > 0 ? `
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
                    ` : '<p class="text-gray-500">No additional items purchased</p>'}
                </div>
                
                <!-- Footer -->
                <div class="text-center text-gray-500 text-sm border-t pt-4">
                    <p>Thank you for playing at Snooker Club!</p>
                    <p>Bill generated on ${new Date().toLocaleString()}</p>
                </div>
            </div>
        `;
        
        billContent.innerHTML = billHTML;
    }

    function printBill() {
        const printContent = document.querySelector('.bill-receipt').outerHTML;
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

    // Make functions globally available
    window.changeRateType = changeRateType;
    window.generateBill = generateBill;
</script>
</body>
</html>