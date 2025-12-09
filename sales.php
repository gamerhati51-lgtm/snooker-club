<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['admin_name'])) {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'];
$today = date('Y-m-d');
$current_time = date('H:i:s');

// Initialize variables
$sale_type = 'snooker'; // Default sale type
$table_id = '';
$customer_name = '';
$mobile_number = '';
$discount = 0;
$payment_method = 'cash';
$transaction_id = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Add to cart
    if (isset($_POST['add_to_cart'])) {
        $product_id = $_POST['product_id'];
        $quantity = max(1, intval($_POST['quantity']));
        
        // Get product details - updated for your table structure
        $product_query = "SELECT product_id, name, category, cost_price, selling_price, 
                                 stock_quantity, is_service_product, unit 
                          FROM products 
                          WHERE product_id = ? AND is_active = 1";
        $stmt = $conn->prepare($product_query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($product) {
            // Check stock for non-service products
            if ($product['is_service_product'] == 0 && $product['stock_quantity'] < $quantity) {
                $_SESSION['error_msg'] = "Insufficient stock! Only " . $product['stock_quantity'] . " items available.";
            } else {
                // Initialize cart if not exists
                if (!isset($_SESSION['sale_cart'])) {
                    $_SESSION['sale_cart'] = [];
                }
                
                // Check if product exists in cart
                $found = false;
                foreach ($_SESSION['sale_cart'] as &$item) {
                    if ($item['product_id'] == $product_id) {
                        $item['quantity'] += $quantity;
                        $item['total'] = $item['quantity'] * $item['price'];
                        $found = true;
                        break;
                    }
                }
                
                // Add new item
                if (!$found) {
                    $_SESSION['sale_cart'][] = [
                        'product_id' => $product_id,
                        'name' => $product['name'],
                        'category' => $product['category'],
                        'price' => $product['selling_price'],
                        'cost' => $product['cost_price'],
                        'quantity' => $quantity,
                        'total' => $product['selling_price'] * $quantity,
                        'unit' => $product['unit'],
                        'is_service' => $product['is_service_product']
                    ];
                }
                
                $_SESSION['success_msg'] = "Product added to cart!";
            }
        }
    }
    
    // Update cart
    if (isset($_POST['update_cart'])) {
        if (isset($_POST['quantities']) && !empty($_SESSION['sale_cart'])) {
            foreach ($_POST['quantities'] as $index => $quantity) {
                if (isset($_SESSION['sale_cart'][$index])) {
                    $quantity = max(1, intval($quantity));
                    
                    // Check stock for non-service products
                    if ($_SESSION['sale_cart'][$index]['is_service'] == 0) {
                        $product_id = $_SESSION['sale_cart'][$index]['product_id'];
                        $stock_query = "SELECT stock_quantity FROM products WHERE product_id = ?";
                        $stmt = $conn->prepare($stock_query);
                        $stmt->bind_param("i", $product_id);
                        $stmt->execute();
                        $stock_result = $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                        
                        if ($stock_result['stock_quantity'] < $quantity) {
                            $_SESSION['error_msg'] = "Insufficient stock for " . $_SESSION['sale_cart'][$index]['name'] . 
                                                   ". Only " . $stock_result['stock_quantity'] . " items available.";
                            continue;
                        }
                    }
                    
                    $_SESSION['sale_cart'][$index]['quantity'] = $quantity;
                    $_SESSION['sale_cart'][$index]['total'] = $quantity * $_SESSION['sale_cart'][$index]['price'];
                }
            }
            if (!isset($_SESSION['error_msg'])) {
                $_SESSION['success_msg'] = "Cart updated!";
            }
        }
    }
    
    // Remove item from cart
    if (isset($_POST['remove_item'])) {
        $index = intval($_POST['item_index']);
        if (isset($_SESSION['sale_cart'][$index])) {
            array_splice($_SESSION['sale_cart'], $index, 1);
            $_SESSION['success_msg'] = "Item removed from cart!";
        }
    }
    
    // Clear cart
    if (isset($_POST['clear_cart'])) {
        unset($_SESSION['sale_cart']);
        $_SESSION['success_msg'] = "Cart cleared!";
    }
    
    // Process sale
    if (isset($_POST['process_sale'])) {
        $sale_type = $_POST['sale_type'];
        $table_id = $_POST['table_id'];
        $customer_name = $_POST['customer_name'];
        $mobile_number = $_POST['mobile_number'];
        $discount = floatval($_POST['discount']);
        $payment_method = $_POST['payment_method'];
        $transaction_id = $_POST['transaction_id'];
        $amount_paid = floatval($_POST['amount_paid']);
        
        // Calculate totals
        $subtotal = 0;
        $total_quantity = 0;
        $total_cost = 0;
        
        if (!empty($_SESSION['sale_cart'])) {
            foreach ($_SESSION['sale_cart'] as $item) {
                $subtotal += $item['total'];
                $total_quantity += $item['quantity'];
                $total_cost += $item['cost'] * $item['quantity'];
            }
        }
        
        $tax = 0;
        $total_amount = $subtotal - $discount + $tax;
        $change = $amount_paid - $total_amount;
        
        if ($amount_paid < $total_amount) {
            $_SESSION['error_msg'] = "Insufficient payment! Amount due: PKR " . number_format($total_amount, 2);
        } elseif (empty($_SESSION['sale_cart'])) {
            $_SESSION['error_msg'] = "Cart is empty! Add items before processing sale.";
        } else {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Insert into sales_transactions using YOUR table structure
                $sale_query = "INSERT INTO sales_transactions 
                              (transaction_date, total_amount, payment_method) 
                              VALUES (NOW(), ?, ?)";
                
                $stmt = $conn->prepare($sale_query);
                $stmt->bind_param("ds", $total_amount, $payment_method);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create sale transaction");
                }
                
                $transaction_id = $conn->insert_id;
                
                // Create a separate table for sale details to store additional information
                // First check if sale_details table exists
                $check_table = $conn->query("SHOW TABLES LIKE 'sale_details'");
                if ($check_table->num_rows == 0) {
                    // Create sale_details table
                    $create_details = "CREATE TABLE sale_details (
                        detail_id INT PRIMARY KEY AUTO_INCREMENT,
                        transaction_id INT NOT NULL,
                        sale_type ENUM('snooker', 'retail') DEFAULT 'retail',
                        table_id INT,
                        customer_name VARCHAR(100),
                        mobile_number VARCHAR(20),
                        discount DECIMAL(10,2) DEFAULT 0,
                        transaction_ref VARCHAR(100),
                        amount_paid DECIMAL(10,2),
                        change_amount DECIMAL(10,2) DEFAULT 0,
                        cashier_name VARCHAR(100),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (transaction_id) REFERENCES sales_transactions(transaction_id) ON DELETE CASCADE
                    )";
                    $conn->query($create_details);
                }
                
                // Insert into sale_details
                $details_query = "INSERT INTO sale_details 
                                 (transaction_id, sale_type, table_id, customer_name, mobile_number,
                                  discount, transaction_ref, amount_paid, change_amount, cashier_name) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt2 = $conn->prepare($details_query);
                $stmt2->bind_param("isssssddss", 
                    $transaction_id, $sale_type, $table_id, $customer_name, $mobile_number,
                    $discount, $_POST['transaction_id'], $amount_paid, $change, $admin_name
                );
                
                if (!$stmt2->execute()) {
                    throw new Exception("Failed to save sale details");
                }
                
                // Create sale_items table if not exists
                $check_items = $conn->query("SHOW TABLES LIKE 'sale_items'");
                if ($check_items->num_rows == 0) {
                    $create_items = "CREATE TABLE sale_items (
                        item_id INT PRIMARY KEY AUTO_INCREMENT,
                        transaction_id INT NOT NULL,
                        product_id INT NOT NULL,
                        product_name VARCHAR(255) NOT NULL,
                        category VARCHAR(100),
                        quantity INT NOT NULL,
                        unit VARCHAR(50),
                        unit_price DECIMAL(10,2) NOT NULL,
                        total_price DECIMAL(10,2) NOT NULL,
                        cost_price DECIMAL(10,2),
                        is_service_product BOOLEAN DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (transaction_id) REFERENCES sales_transactions(transaction_id) ON DELETE CASCADE,
                        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT
                    )";
                    $conn->query($create_items);
                }
                
                // Insert sale items
                if (!empty($_SESSION['sale_cart'])) {
                    foreach ($_SESSION['sale_cart'] as $item) {
                        $item_query = "INSERT INTO sale_items 
                                      (transaction_id, product_id, product_name, category, quantity, unit,
                                       unit_price, total_price, cost_price, is_service_product) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        $stmt3 = $conn->prepare($item_query);
                        $stmt3->bind_param("iissisdddi", 
                            $transaction_id, $item['product_id'], $item['name'], $item['category'],
                            $item['quantity'], $item['unit'], $item['price'], $item['total'], 
                            $item['cost'], $item['is_service']
                        );
                        
                        if (!$stmt3->execute()) {
                            throw new Exception("Failed to add sale items");
                        }
                        
                        // Update product stock for non-service products
                        if ($item['is_service'] == 0) {
                            $update_stock = "UPDATE products SET stock_quantity = stock_quantity - ? 
                                            WHERE product_id = ?";
                            $stmt4 = $conn->prepare($update_stock);
                            $stmt4->bind_param("ii", $item['quantity'], $item['product_id']);
                            
                            if (!$stmt4->execute()) {
                                throw new Exception("Failed to update stock for product ID: " . $item['product_id']);
                            }
                            
                            $stmt4->close();
                        }
                        
                        $stmt3->close();
                    }
                }
                
                // If it's a snooker sale with table, start session
                if ($sale_type == 'snooker' && !empty($table_id)) {
                    // Get table rate
                    $table_query = "SELECT rate_per_hour, century_rate FROM snooker_tables WHERE id = ?";
                    $stmt5 = $conn->prepare($table_query);
                    $stmt5->bind_param("i", $table_id);
                    $stmt5->execute();
                    $table = $stmt5->get_result()->fetch_assoc();
                    
                    if ($table) {
                        // Create snooker session
                        $session_query = "INSERT INTO snooker_sessions 
                                         (table_id, customer_name, mobile_number, start_time, 
                                          status, created_by) 
                                         VALUES (?, ?, ?, NOW(), 'Active', ?)";
                        
                        $stmt6 = $conn->prepare($session_query);
                        $stmt6->bind_param("isss", $table_id, $customer_name, $mobile_number, $admin_name);
                        
                        if (!$stmt6->execute()) {
                            throw new Exception("Failed to create snooker session");
                        }
                        
                        $session_id = $conn->insert_id;
                        $stmt6->close();
                        
                        // Update table status
                        $update_table = "UPDATE snooker_tables SET status = 'Occupied' WHERE id = ?";
                        $stmt7 = $conn->prepare($update_table);
                        $stmt7->bind_param("i", $table_id);
                        $stmt7->execute();
                        $stmt7->close();
                        
                        // Link sale to session in sale_details
                        $link_query = "UPDATE sale_details SET session_id = ? WHERE transaction_id = ?";
                        $stmt8 = $conn->prepare($link_query);
                        $stmt8->bind_param("ii", $session_id, $transaction_id);
                        $stmt8->execute();
                        $stmt8->close();
                    }
                    $stmt5->close();
                }
                
                // Commit transaction
                $conn->commit();
                
                // Clear cart and show success
                unset($_SESSION['sale_cart']);
                
                $_SESSION['success_msg'] = "Sale completed successfully! Transaction ID: #" . $transaction_id;
                $_SESSION['last_sale'] = [
                    'id' => $transaction_id,
                    'total' => $total_amount,
                    'change' => $change,
                    'items' => $total_quantity
                ];
                
                // Reset form
                $table_id = '';
                $customer_name = '';
                $mobile_number = '';
                $discount = 0;
                $payment_method = 'cash';
                $transaction_id = '';
                
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_msg'] = "Transaction failed: " . $e->getMessage();
            }
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: sales.php");
    exit();
}

// Get available products
$products_query = "SELECT product_id, name, category, cost_price, selling_price, 
                          stock_quantity, is_service_product, unit, alert_quantity,
                          sku, brand 
                   FROM products 
                   WHERE is_active = 1 
                   AND (is_service_product = 1 OR stock_quantity > 0) 
                   ORDER BY category, name";
$products_result = $conn->query($products_query);

// Get available tables
$tables_query = "SELECT id, table_name, rate_per_hour, century_rate 
                 FROM snooker_tables 
                 WHERE is_active = 1 AND status = 'Free' 
                 ORDER BY table_name";
$tables_result = $conn->query($tables_query);

// Get recent sales - UPDATED FOR YOUR TABLE STRUCTURE
$recent_sales_query = "SELECT st.transaction_id, st.transaction_date, st.total_amount, 
                              st.payment_method, sd.customer_name, sd.sale_type, 
                              sd.table_id, stbl.table_name
                       FROM sales_transactions st
                       LEFT JOIN sale_details sd ON st.transaction_id = sd.transaction_id
                       LEFT JOIN snooker_tables stbl ON sd.table_id = stbl.id
                       ORDER BY st.transaction_date DESC 
                       LIMIT 10";
$recent_sales_result = $conn->query($recent_sales_query);

// Calculate cart totals
$cart_total = 0;
$cart_items_count = 0;
$cart_profit = 0;
if (!empty($_SESSION['sale_cart'])) {
    foreach ($_SESSION['sale_cart'] as $item) {
        $cart_total += $item['total'];
        $cart_items_count += $item['quantity'];
        $cart_profit += ($item['price'] - $item['cost']) * $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Sales | Snooker Club Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-card:hover {
            transform: translateY(-2px);
            transition: transform 0.2s;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .cart-item:hover {
            background-color: #f9fafb;
        }
        .low-stock {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        .out-of-stock {
            opacity: 0.7;
            background-color: #fecaca;
            border-left: 4px solid #ef4444;
        }
        .service-product {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
        }
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Include Sidebar -->
    <?php include 'layout/sidebar.php'; ?>
    
    <!-- Include Header -->
    <?php include 'layout/header.php'; ?>
    
    <main class="ml-0 lg:ml-64 pt-20 p-6">
        
        <!-- Page Header -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-cash-register text-green-600 mr-2"></i>
                        Point of Sale (POS)
                    </h1>
                    <p class="text-gray-600 mt-2">Process sales, manage billing, and handle transactions</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-2 bg-blue-100 text-blue-800 rounded-lg">
                        <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($admin_name); ?>
                    </span>
                    <span class="px-3 py-2 bg-green-100 text-green-800 rounded-lg">
                        <i class="far fa-calendar mr-1"></i><?php echo date('F d, Y'); ?>
                    </span>
                    <span class="px-3 py-2 bg-purple-100 text-purple-800 rounded-lg">
                        <i class="fas fa-clock mr-1"></i><?php echo date('h:i A'); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700"><?php echo $_SESSION['success_msg']; ?></p>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo $_SESSION['error_msg']; ?></p>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column: Products -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-store mr-2 text-blue-600"></i>
                            Available Products
                        </h2>
                        <div class="flex space-x-2">
                            <div class="relative">
                                <input type="text" id="productSearch" placeholder="Search by name, SKU or category..." 
                                       class="w-64 px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                            </div>
                            <select id="categoryFilter" onchange="filterByCategory(this.value)" 
                                    class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="all">All Categories</option>
                                <?php
                                // Get unique categories
                                $cat_query = "SELECT DISTINCT category FROM products WHERE is_active = 1 AND category IS NOT NULL ORDER BY category";
                                $cat_result = $conn->query($cat_query);
                                while ($cat = $cat_result->fetch_assoc()):
                                ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Product Status Indicators -->
                    <div class="flex flex-wrap gap-3 mb-4">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">In Stock</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">Low Stock</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">Out of Stock</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                            <span class="text-sm text-gray-600">Service Product</span>
                        </div>
                    </div>
                    
                    <!-- Products Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="productsGrid">
                        <?php while ($product = $products_result->fetch_assoc()): 
                            $is_low_stock = ($product['is_service_product'] == 0 && $product['stock_quantity'] <= $product['alert_quantity'] && $product['stock_quantity'] > 0);
                            $is_out_of_stock = ($product['is_service_product'] == 0 && $product['stock_quantity'] <= 0);
                            $is_service = ($product['is_service_product'] == 1);
                            
                            $card_class = "product-card bg-white border border-gray-200 rounded-lg p-4 hover:shadow-lg transition-shadow";
                            if ($is_service) {
                                $card_class .= " service-product";
                            } elseif ($is_out_of_stock) {
                                $card_class .= " out-of-stock";
                            } elseif ($is_low_stock) {
                                $card_class .= " low-stock";
                            }
                        ?>
                        <div class="<?php echo $card_class; ?>" 
                             data-category="<?php echo htmlspecialchars($product['category'] ?: 'Uncategorized'); ?>"
                             data-name="<?php echo htmlspecialchars(strtolower($product['name'])); ?>"
                             data-sku="<?php echo htmlspecialchars(strtolower($product['sku'] ?: '')); ?>"
                             data-brand="<?php echo htmlspecialchars(strtolower($product['brand'] ?: '')); ?>">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex-1">
                                    <h3 class="font-bold text-gray-800 truncate" title="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </h3>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <?php if ($product['category']): ?>
                                        <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                            <?php echo htmlspecialchars($product['category']); ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if ($product['brand']): ?>
                                        <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                            <?php echo htmlspecialchars($product['brand']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <?php if ($is_service): ?>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full" title="Service Product">
                                        <i class="fas fa-concierge-bell"></i>
                                    </span>
                                    <?php elseif ($is_out_of_stock): ?>
                                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">
                                        Out of Stock
                                    </span>
                                    <?php elseif ($is_low_stock): ?>
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full animate-pulse" 
                                          title="Low Stock - Reorder!">
                                        Low: <?php echo $product['stock_quantity']; ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                        Stock: <?php echo $product['stock_quantity']; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($product['sku']): ?>
                            <div class="mb-2">
                                <span class="text-xs text-gray-500">SKU: <?php echo htmlspecialchars($product['sku']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <p class="text-2xl font-bold text-green-600">
                                        PKR <?php echo number_format($product['selling_price'], 2); ?>
                                    </p>
                                    <?php if ($product['cost_price'] > 0): ?>
                                    <p class="text-xs text-gray-500">
                                        Cost: PKR <?php echo number_format($product['cost_price'], 2); ?>
                                        <?php if ($product['selling_price'] > $product['cost_price']): ?>
                                        <span class="text-green-600 ml-2">
                                            Margin: <?php echo number_format((($product['selling_price'] - $product['cost_price']) / $product['cost_price']) * 100, 1); ?>%
                                        </span>
                                        <?php endif; ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($product['unit'] ?: 'Pc(s)'); ?>
                                </div>
                            </div>
                            
                            <form method="POST" class="flex items-center space-x-2">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <div class="flex-1">
                                    <input type="number" name="quantity" value="1" 
                                           min="1" 
                                           max="<?php echo $is_service ? 999 : $product['stock_quantity']; ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                           <?php echo $is_out_of_stock ? 'disabled' : ''; ?>>
                                </div>
                                <button type="submit" name="add_to_cart" 
                                        class="px-4 py-2 <?php echo $is_out_of_stock ? 'bg-gray-400' : 'bg-green-600 hover:bg-green-700'; ?> text-white rounded-lg transition"
                                        <?php echo $is_out_of_stock ? 'disabled' : ''; ?>
                                        title="<?php echo $is_out_of_stock ? 'Out of Stock' : 'Add to Cart'; ?>">
                                    <i class="fas <?php echo $is_out_of_stock ? 'fa-ban' : 'fa-cart-plus'; ?>"></i>
                                </button>
                            </form>
                        </div>
                        <?php endwhile; ?>
                        
                        <?php if ($products_result->num_rows == 0): ?>
                        <div class="col-span-3 text-center py-8">
                            <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">No products available.</p>
                            <p class="text-sm mt-2">Add products in inventory first.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Sales -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-history mr-2 text-purple-600"></i>
                        Recent Sales
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Trans ID</th>
                                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Date & Time</th>
                                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Customer</th>
                                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Type</th>
                                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Total</th>
                                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_sales_result): 
                                    while ($sale = $recent_sales_result->fetch_assoc()): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <span class="font-medium">#<?php echo $sale['transaction_id']; ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php echo date('M d', strtotime($sale['transaction_date'])); ?>
                                        <span class="text-gray-500 text-sm"><?php echo date('h:i A', strtotime($sale['transaction_date'])); ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php echo htmlspecialchars($sale['customer_name'] ?: 'Walk-in'); ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php if ($sale['sale_type']): ?>
                                        <span class="px-2 py-1 rounded-full text-xs 
                                            <?php echo $sale['sale_type'] == 'snooker' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                            <?php echo ucfirst($sale['sale_type']); ?>
                                            <?php if ($sale['sale_type'] == 'snooker' && !empty($sale['table_name'])): ?>
                                            (<?php echo htmlspecialchars($sale['table_name']); ?>)
                                            <?php endif; ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-gray-500 text-xs">Not specified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-green-700">
                                        PKR <?php echo number_format($sale['total_amount'], 2); ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">
                                            <?php echo ucfirst($sale['payment_method'] ?: 'Cash'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-gray-500">
                                        <i class="fas fa-receipt text-3xl mb-2"></i>
                                        <p>No sales recorded yet.</p>
                                        <p class="text-sm mt-2">Complete your first sale to see records here.</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Cart & Billing -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-shopping-cart mr-2 text-orange-600"></i>
                        Shopping Cart
                        <?php if ($cart_items_count > 0): ?>
                        <span class="ml-2 px-2 py-1 bg-orange-100 text-orange-800 text-sm rounded-full">
                            <?php echo $cart_items_count; ?> items
                        </span>
                        <?php endif; ?>
                    </h2>
                    
                    <!-- Cart Items -->
                    <div class="mb-6 max-h-96 overflow-y-auto">
                        <?php if (!empty($_SESSION['sale_cart'])): ?>
                        <form method="POST" id="cartForm">
                            <?php foreach ($_SESSION['sale_cart'] as $index => $item): ?>
                            <div class="cart-item border-b border-gray-200 py-3">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-800 truncate" title="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </h4>
                                        <div class="flex items-center space-x-2 mt-1">
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($item['category']); ?></p>
                                            <?php if ($item['is_service']): ?>
                                            <span class="text-xs bg-blue-100 text-blue-800 px-1 rounded">Service</span>
                                            <?php endif; ?>
                                            <?php if ($item['unit']): ?>
                                            <span class="text-xs text-gray-500">(<?php echo htmlspecialchars($item['unit']); ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="item_index" value="<?php echo $index; ?>">
                                        <button type="submit" name="remove_item" 
                                                class="text-red-500 hover:text-red-700 ml-2" title="Remove item">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center space-x-2">
                                        <input type="number" name="quantities[<?php echo $index; ?>]" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" class="w-16 px-2 py-1 border border-gray-300 rounded text-center">
                                        <span class="text-gray-600">×</span>
                                        <span class="text-gray-700">PKR <?php echo number_format($item['price'], 2); ?></span>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-bold text-green-700 block">
                                            PKR <?php echo number_format($item['total'], 2); ?>
                                        </span>
                                        <?php if ($item['cost'] > 0): ?>
                                        <span class="text-xs text-gray-500">
                                            Cost: PKR <?php echo number_format($item['cost'] * $item['quantity'], 2); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="mt-4 flex space-x-2">
                                <button type="submit" name="update_cart" 
                                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    <i class="fas fa-sync-alt mr-2"></i>Update Cart
                                </button>
                                <button type="submit" name="clear_cart" onclick="return confirm('Clear entire cart?')"
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-shopping-cart text-4xl mb-3"></i>
                            <p>Your cart is empty</p>
                            <p class="text-sm mt-2">Add products from the left panel</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Cart Summary -->
                    <?php if (!empty($_SESSION['sale_cart'])): ?>
                    <div class="border-t border-gray-200 pt-6">
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">PKR <?php echo number_format($cart_total, 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Items:</span>
                                <span class="font-medium"><?php echo $cart_items_count; ?></span>
                            </div>
                            <?php if ($cart_profit > 0): ?>
                            <div class="flex justify-between border-t border-gray-100 pt-2">
                                <span class="text-gray-600">Estimated Profit:</span>
                                <span class="font-bold text-green-700">PKR <?php echo number_format($cart_profit, 2); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Billing Form -->
                        <form method="POST" id="billingForm">
                            <div class="space-y-4 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sale Type</label>
                                    <select name="sale_type" id="saleType" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="snooker" <?php echo $sale_type == 'snooker' ? 'selected' : ''; ?>>Snooker Session</option>
                                        <option value="retail" <?php echo $sale_type == 'retail' ? 'selected' : ''; ?>>Retail Only</option>
                                    </select>
                                </div>
                                
                                <div id="tableSelection" style="display: <?php echo $sale_type == 'snooker' ? 'block' : 'none'; ?>">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Table</label>
                                    <select name="table_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">-- Select Table --</option>
                                        <?php 
                                        $tables_result->data_seek(0); // Reset pointer
                                        while ($table = $tables_result->fetch_assoc()): ?>
                                        <option value="<?php echo $table['id']; ?>" <?php echo $table_id == $table['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($table['table_name']); ?> 
                                            (PKR <?php echo number_format($table['rate_per_hour'], 2); ?>/hr)
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <?php if ($tables_result->num_rows == 0): ?>
                                    <p class="text-xs text-red-500 mt-1">No free tables available</p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name</label>
                                        <input type="text" name="customer_name" value="<?php echo htmlspecialchars($customer_name); ?>" 
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                               placeholder="Walk-in">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                                        <input type="text" name="mobile_number" value="<?php echo htmlspecialchars($mobile_number); ?>" 
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                               placeholder="Optional">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount (PKR)</label>
                                    <input type="number" name="discount" id="discountInput" value="<?php echo $discount; ?>" 
                                           min="0" step="0.01" max="<?php echo $cart_total; ?>"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           oninput="calculateTotals()">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                                    <select name="payment_method" id="paymentMethod" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="cash" <?php echo $payment_method == 'cash' ? 'selected' : ''; ?>>Cash</option>
                                        <option value="card" <?php echo $payment_method == 'card' ? 'selected' : ''; ?>>Credit/Debit Card</option>
                                        <option value="easypaisa" <?php echo $payment_method == 'easypaisa' ? 'selected' : ''; ?>>EasyPaisa</option>
                                        <option value="jazzcash" <?php echo $payment_method == 'jazzcash' ? 'selected' : ''; ?>>JazzCash</option>
                                        <option value="bank_transfer" <?php echo $payment_method == 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                        <option value="credit" <?php echo $payment_method == 'credit' ? 'selected' : ''; ?>>Credit (Pay Later)</option>
                                    </select>
                                </div>
                                
                                <div id="transactionField" style="display: <?php echo $payment_method != 'cash' ? 'block' : 'none'; ?>">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Reference</label>
                                    <input type="text" name="transaction_id" value="<?php echo htmlspecialchars($transaction_id); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           placeholder="Enter transaction reference">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount Paid (PKR)</label>
                                    <input type="number" name="amount_paid" id="amountPaid" value="<?php echo $cart_total; ?>" 
                                           min="0" step="0.01" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           oninput="calculateChange()">
                                </div>
                                
                                <div id="changeDisplay" class="hidden">
                                    <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                        <p class="text-sm text-green-800 flex justify-between">
                                            <span>Change:</span>
                                            <span id="changeAmount" class="font-bold">PKR 0.00</span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div id="amountDueDisplay" class="hidden">
                                    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                                        <p class="text-sm text-red-800 flex justify-between">
                                            <span>Amount Due:</span>
                                            <span id="amountDue" class="font-bold">PKR 0.00</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex justify-between items-center mb-4">
                                    <span class="text-lg font-bold text-gray-800">Total Amount:</span>
                                    <span class="text-2xl font-bold text-green-700" id="totalAmount">
                                        PKR <?php echo number_format($cart_total, 2); ?>
                                    </span>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <button type="button" onclick="setExactPayment()" 
                                            class="flex-1 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition text-sm">
                                        Exact Amount
                                    </button>
                                    <button type="button" onclick="addToPayment(100)" 
                                            class="flex-1 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition text-sm">
                                        +100
                                    </button>
                                    <button type="button" onclick="addToPayment(500)" 
                                            class="flex-1 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition text-sm">
                                        +500
                                    </button>
                                    <button type="button" onclick="addToPayment(1000)" 
                                            class="flex-1 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition text-sm">
                                        +1000
                                    </button>
                                </div>
                                
                                <button type="submit" name="process_sale" id="processSaleBtn"
                                        class="w-full py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition text-lg mt-4">
                                    <i class="fas fa-check-circle mr-2"></i>Complete Sale
                                </button>
                                
                                <p class="text-xs text-gray-500 text-center mt-2">
                                    Press <kbd class="px-1 py-0.5 bg-gray-100 rounded">Ctrl + S</kbd> to complete sale quickly
                                </p>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Print Receipt Modal -->
        <?php if (isset($_SESSION['last_sale'])): ?>
        <div id="receiptModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl p-6 w-full max-w-md">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-check text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800">Sale Completed!</h3>
                    <p class="text-gray-600">Transaction ID: #<?php echo $_SESSION['last_sale']['id']; ?></p>
                </div>
                
                <div class="mb-6 p-4 bg-gray-50 rounded-lg space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Amount:</span>
                        <span class="font-bold">PKR <?php echo number_format($_SESSION['last_sale']['total'], 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Items:</span>
                        <span class="font-medium"><?php echo $_SESSION['last_sale']['items']; ?></span>
                    </div>
                    <?php if ($_SESSION['last_sale']['change'] > 0): ?>
                    <div class="flex justify-between border-t border-gray-200 pt-2 mt-2">
                        <span class="text-gray-600">Change:</span>
                        <span class="font-bold text-green-700">PKR <?php echo number_format($_SESSION['last_sale']['change'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="flex space-x-3">
                    <button onclick="printReceipt(<?php echo $_SESSION['last_sale']['id']; ?>)" 
                            class="flex-1 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-print mr-2"></i>Print Receipt
                    </button>
                    <button onclick="closeModal()" 
                            class="flex-1 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                        Close
                    </button>
                </div>
                
                <p class="text-xs text-gray-500 text-center mt-4">
                    Modal auto-closes in <span id="countdown">10</span> seconds
                </p>
            </div>
        </div>
        <?php unset($_SESSION['last_sale']); endif; ?>
    </main>
    
    <script>
        // Product filtering
        function filterProducts() {
            const searchTerm = document.getElementById('productSearch').value.toLowerCase();
            const products = document.querySelectorAll('.product-card');
            
            products.forEach(product => {
                const productName = product.getAttribute('data-name');
                const productSKU = product.getAttribute('data-sku');
                const productBrand = product.getAttribute('data-brand');
                
                const matchesSearch = productName.includes(searchTerm) || 
                                     productSKU.includes(searchTerm) || 
                                     productBrand.includes(searchTerm);
                product.style.display = matchesSearch ? 'block' : 'none';
            });
        }
        
        function filterByCategory(category) {
            const products = document.querySelectorAll('.product-card');
            
            products.forEach(product => {
                const productCategory = product.getAttribute('data-category');
                if (category === 'all' || productCategory.toLowerCase() === category.toLowerCase()) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        }
        
        // Sale type toggle
        document.getElementById('saleType').addEventListener('change', function() {
            const tableSelection = document.getElementById('tableSelection');
            tableSelection.style.display = this.value === 'snooker' ? 'block' : 'none';
        });
        
        // Payment method toggle
        document.getElementById('paymentMethod').addEventListener('change', function() {
            const transactionField = document.getElementById('transactionField');
            transactionField.style.display = this.value !== 'cash' ? 'block' : 'none';
            
            // If credit, show different message
            if (this.value === 'credit') {
                document.getElementById('amountPaid').value = 0;
                calculateChange();
            }
        });
        
        // Calculate totals
        function calculateTotals() {
            const subtotal = <?php echo $cart_total; ?>;
            const discount = parseFloat(document.getElementById('discountInput').value) || 0;
            const finalTotal = Math.max(0, subtotal - discount);
            
            document.getElementById('totalAmount').textContent = 'PKR ' + finalTotal.toFixed(2);
            calculateChange();
        }
        
        // Calculate change
        function calculateChange() {
            const subtotal = <?php echo $cart_total; ?>;
            const discount = parseFloat(document.getElementById('discountInput').value) || 0;
            const finalTotal = Math.max(0, subtotal - discount);
            const paid = parseFloat(document.getElementById('amountPaid').value) || 0;
            const paymentMethod = document.getElementById('paymentMethod').value;
            
            const changeDisplay = document.getElementById('changeDisplay');
            const amountDueDisplay = document.getElementById('amountDueDisplay');
            const changeAmount = document.getElementById('changeAmount');
            const amountDue = document.getElementById('amountDue');
            
            if (paymentMethod === 'credit') {
                changeDisplay.classList.add('hidden');
                amountDueDisplay.classList.remove('hidden');
                amountDue.textContent = 'PKR ' + finalTotal.toFixed(2);
            } else if (paid > finalTotal) {
                const change = paid - finalTotal;
                changeAmount.textContent = 'PKR ' + change.toFixed(2);
                changeDisplay.classList.remove('hidden');
                amountDueDisplay.classList.add('hidden');
            } else if (paid < finalTotal) {
                const due = finalTotal - paid;
                amountDue.textContent = 'PKR ' + due.toFixed(2);
                amountDueDisplay.classList.remove('hidden');
                changeDisplay.classList.add('hidden');
            } else {
                changeDisplay.classList.add('hidden');
                amountDueDisplay.classList.add('hidden');
            }
        }
        
        // Payment helpers
        function setExactPayment() {
            const subtotal = <?php echo $cart_total; ?>;
            const discount = parseFloat(document.getElementById('discountInput').value) || 0;
            const finalTotal = Math.max(0, subtotal - discount);
            document.getElementById('amountPaid').value = finalTotal.toFixed(2);
            calculateChange();
        }
        
        function addToPayment(amount) {
            const current = parseFloat(document.getElementById('amountPaid').value) || 0;
            document.getElementById('amountPaid').value = (current + amount).toFixed(2);
            calculateChange();
        }
        
        // Receipt modal functions
        function printReceipt(transactionId) {
            // Open receipt in new window
            const receiptWindow = window.open('receipt.php?transaction_id=' + transactionId, '_blank');
            receiptWindow.focus();
        }
        
        function closeModal() {
            document.getElementById('receiptModal').style.display = 'none';
        }
        
        // Auto close modal countdown
        <?php if (isset($_SESSION['last_sale'])): ?>
        let countdown = 10;
        const countdownElement = document.getElementById('countdown');
        const countdownInterval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                closeModal();
            }
        }, 1000);
        <?php endif; ?>
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + S to complete sale
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                const processBtn = document.getElementById('processSaleBtn');
                if (processBtn) processBtn.click();
            }
            
            // Ctrl + F to focus search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('productSearch').focus();
            }
            
            // Ctrl + C to clear cart
            if (e.ctrlKey && e.key === 'c') {
                e.preventDefault();
                if (confirm('Clear cart?')) {
                    document.querySelector('button[name="clear_cart"]').click();
                }
            }
            
            // Esc to close modal
            if (e.key === 'Escape') {
                const modal = document.getElementById('receiptModal');
                if (modal && modal.style.display !== 'none') {
                    closeModal();
                }
            }
        });
        
        // Initialize calculations
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotals();
            calculateChange();
            
            // Auto-focus search on page load
            setTimeout(() => {
                document.getElementById('productSearch').focus();
            }, 100);
        });
        
        // Prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>