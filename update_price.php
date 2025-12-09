<?php
session_start();
if (!isset($_SESSION['admin_name'])) {
    header("Location: index.php");
    exit;
}

include 'db.php';
$message = '';
$message_type = '';
$active_section = isset($_GET['section']) ? $_GET['section'] : 'products';

// Handle form submission for bulk product price update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_prices'])) {
        // Check if any products were selected
        if (!empty($_POST['product_ids']) && is_array($_POST['product_ids'])) {
            $updated_count = 0;
            
            foreach ($_POST['product_ids'] as $product_id) {
                $new_price = $_POST['new_price'][$product_id];
                $new_cost_price = $_POST['new_cost_price'][$product_id];
                
                // Validate price
                if (is_numeric($new_price) && $new_price >= 0) {
                    // Update both selling_price and cost_price
                    $stmt = $conn->prepare("UPDATE products SET selling_price = ?, cost_price = ? WHERE product_id = ?");
                    $stmt->bind_param("ddi", $new_price, $new_cost_price, $product_id);
                    
                    if ($stmt->execute()) {
                        $updated_count++;
                    }
                    $stmt->close();
                }
            }
            
            $message = "✅ Successfully updated prices for $updated_count products!";
            $message_type = 'success';
            $active_section = 'products';
        } else {
            $message = "⚠️ Please select at least one product to update.";
            $message_type = 'warning';
            $active_section = 'products';
        }
    }
    
    // Handle table price updates
    if (isset($_POST['update_table_prices'])) {
        if (!empty($_POST['table_ids']) && is_array($_POST['table_ids'])) {
            $updated_count = 0;
            
            foreach ($_POST['table_ids'] as $table_id) {
                $new_rate_per_hour = $_POST['new_rate_per_hour'][$table_id];
                $new_century_rate = $_POST['new_century_rate'][$table_id];
                
                // Validate rates
                if (is_numeric($new_rate_per_hour) && $new_rate_per_hour >= 0) {
                    $stmt = $conn->prepare("UPDATE snooker_tables SET rate_per_hour = ?, century_rate = ? WHERE id = ?");
                    $stmt->bind_param("ddi", $new_rate_per_hour, $new_century_rate, $table_id);
                    
                    if ($stmt->execute()) {
                        $updated_count++;
                    }
                    $stmt->close();
                }
            }
            
            $message = "✅ Successfully updated rates for $updated_count tables!";
            $message_type = 'success';
            $active_section = 'tables';
        } else {
            $message = "⚠️ Please select at least one table to update.";
            $message_type = 'warning';
            $active_section = 'tables';
        }
    }
}

// PRODUCTS SECTION
// Handle search for products
$search = '';
$category_filter = '';
$where_conditions = [];
$params = [];
$param_types = '';

if (isset($_GET['search']) && $active_section == 'products') {
    $search = $_GET['search'];
    $where_conditions[] = "(name LIKE ? OR category LIKE ? OR description LIKE ? OR sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= 'ssss';
}

if (isset($_GET['category']) && $_GET['category'] != 'all' && $active_section == 'products') {
    $category_filter = $_GET['category'];
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
    $param_types .= 's';
}

// Build products query
$products_sql = "SELECT 
            product_id,
            sku,
            name,
            description,
            image_path,
            cost_price,
            selling_price,
            stock_quantity,
            category,
            is_active,
            brand,
            sub_category,
            alert_quantity
        FROM products 
        WHERE is_active = 1";

if (!empty($where_conditions)) {
    $products_sql .= " AND " . implode(" AND ", $where_conditions);
}
$products_sql .= " ORDER BY name ASC";

// Get total products count for pagination
$count_sql = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
if (!empty($where_conditions)) {
    $count_sql .= " AND " . implode(" AND ", $where_conditions);
}
$count_result = $conn->query($count_sql);
$total_products = $count_result ? $count_result->fetch_assoc()['total'] : 0;

// Products pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_products / $limit);

$products_sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$param_types .= 'ii';

// Prepare and execute products query
$products_stmt = $conn->prepare($products_sql);
if ($products_stmt) {
    if (!empty($params)) {
        $products_stmt->bind_param($param_types, ...$params);
    }
    $products_stmt->execute();
    $products_result = $products_stmt->get_result();
} else {
    // If prepare fails, use simple query
    $products_result = $conn->query($products_sql);
}

// Get distinct categories for filter
$categories = [];
$category_result = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' AND is_active = 1 ORDER BY category");
if ($category_result) {
    while ($cat = $category_result->fetch_assoc()) {
        $categories[] = $cat['category'];
    }
}

// TABLES SECTION
// Handle search for tables
$table_search = '';
$table_where_conditions = [];
$table_params = [];
$table_param_types = '';

if (isset($_GET['table_search']) && $active_section == 'tables') {
    $table_search = $_GET['table_search'];
    $table_where_conditions[] = "(table_name LIKE ? OR status LIKE ?)";
    $table_params[] = "%$table_search%";
    $table_params[] = "%$table_search%";
    $table_param_types .= 'ss';
}

// Build tables query
$tables_sql = "SELECT 
            id,
            table_name,
            rate_per_hour,
            century_rate,
            status
        FROM snooker_tables 
        WHERE 1=1";

if (!empty($table_where_conditions)) {
    $tables_sql .= " AND " . implode(" AND ", $table_where_conditions);
}
$tables_sql .= " ORDER BY table_name ASC";

// Get total tables count
$tables_count_sql = "SELECT COUNT(*) as total FROM snooker_tables WHERE 1=1";
if (!empty($table_where_conditions)) {
    $tables_count_sql .= " AND " . implode(" AND ", $table_where_conditions);
}
$tables_count_result = $conn->query($tables_count_sql);
$total_tables = $tables_count_result ? $tables_count_result->fetch_assoc()['total'] : 0;

// Tables pagination
$tables_page = isset($_GET['tables_page']) ? (int)$_GET['tables_page'] : 1;
$tables_offset = ($tables_page - 1) * $limit;
$tables_total_pages = ceil($total_tables / $limit);

$tables_sql .= " LIMIT ? OFFSET ?";
$table_params[] = $limit;
$table_params[] = $tables_offset;
$table_param_types .= 'ii';

// Prepare and execute tables query
$tables_stmt = $conn->prepare($tables_sql);
if ($tables_stmt) {
    if (!empty($table_params)) {
        $tables_stmt->bind_param($table_param_types, ...$table_params);
    }
    $tables_stmt->execute();
    $tables_result = $tables_stmt->get_result();
} else {
    $tables_result = $conn->query($tables_sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Prices - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .snooker-shadow {
            box-shadow: 0 4px 12px rgba(24, 58, 52, 0.15);
        }
        
        .price-input:focus {
            box-shadow: 0 0 0 3px rgba(24, 58, 52, 0.1);
        }
        
        .checkbox-custom:checked {
            background-color: #183a34;
            border-color: #183a34;
        }
        
        .pagination-link.active {
            background-color: #183a34;
            color: white;
        }
        
        .category-badge {
            background-color: #e8f4f2;
            color: #183a34;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .sticky-header {
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 10;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .section-tab {
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .section-tab.active {
            border-bottom-color: #183a34;
            color: #183a34;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-occupied {
            background-color: #fee2e2;
            color: #dc2626;
        }
        
        .status-free {
            background-color: #e2dcfcff;
            color: #16a34a;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<!-- Sidebar -->
<?php include 'layout/sidebar.php'; ?>

<!-- Main Content Area -->
<div class="min-h-screen lg:ml-64">
    
    <!-- Header -->
    <?php include "layout/header.php"; ?>

    <!-- Main Content -->
    <main class="pt-16 p-6">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2 text-center mt-3">
                <i class="fas fa-tags mr-2 text-snooker-green text-center"></i>Update Prices & Rates
            </h1>
            <p class="text-gray-600 text-center">Update product prices and snooker table rates in bulk.</p>
            
            <!-- Success/Error Message -->
            <?php if ($message): ?>
                <div class="mt-4 p-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-yellow-100 text-yellow-700 border border-yellow-300'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section Tabs -->
        <div class="bg-white rounded-xl snooker-shadow mb-6 overflow-hidden">
            <div class="flex border-b">
                <button class="section-tab flex-1 py-4 text-center <?php echo $active_section == 'products' ? 'active' : 'text-gray-500 hover:text-gray-700'; ?>" 
                        onclick="switchSection('products')">
                    <i class="fas fa-boxes mr-2"></i> Products
                    <span class="ml-2 text-xs bg-gray-100 px-2 py-1 rounded-full"><?php echo $total_products; ?></span>
                </button>
                <button class="section-tab flex-1 py-4 text-center <?php echo $active_section == 'tables' ? 'active' : 'text-gray-500 hover:text-gray-700'; ?>" 
                        onclick="switchSection('tables')">
                    <i class="fas fa-table-tennis mr-2"></i> Snooker Tables
                    <span class="ml-2 text-xs bg-gray-100 px-2 py-1 rounded-full"><?php echo $total_tables; ?></span>
                </button>
            </div>
        </div>

        <!-- PRODUCTS SECTION -->
        <div id="productsSection" class="<?php echo $active_section == 'products' ? 'block' : 'hidden'; ?>">
            <!-- Search and Filter Section -->
            <div class="bg-white rounded-xl p-6 snooker-shadow mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Search Box -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Products</label>
                        <div class="relative">
                            <input type="text" 
                                   id="searchInput" 
                                   placeholder="Search by name, SKU, category..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-snooker-green focus:border-transparent">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                    
                    <!-- Category Filter -->
                    <?php if (!empty($categories)): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Category</label>
                        <select id="categoryFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-snooker-green focus:border-transparent">
                            <option value="all" <?php echo $category_filter == '' ? 'selected' : ''; ?>>All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" 
                                        <?php echo $category_filter == $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-end space-x-3">
                        <button onclick="applyProductFilters()" 
                                class="px-3 py-2 bg-snooker-green text-white rounded-lg font-semibold hover:bg-snooker-light transition flex items-center">
                            <i class="fas fa-filter mr-2"></i> Apply Filters
                        </button>
                        <button onclick="resetProductFilters()" 
                                class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition flex items-center">
                            <i class="fas fa-redo mr-2"></i> Reset
                        </button>
                    </div>
                </div>
                
                <!-- Bulk Action Panel -->
                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-blue-800 mb-1">Bulk Price Update</h3>
                            <p class="text-sm text-blue-600">Select products and update their prices simultaneously</p>
                        </div>
                        <div class="mt-3 md:mt-0">
                            <button onclick="selectAllProducts()" 
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition mr-2">
                                <i class="fas fa-check-square mr-1"></i> Select All Products
                            </button>
                            <button onclick="deselectAllProducts()" 
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition">
                                <i class="far fa-square mr-1"></i> Deselect All
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Table Form -->
            <form method="POST" action="update_price.php?section=products" id="productPriceForm">
                <div class="bg-white rounded-xl snooker-shadow overflow-hidden">
                    <!-- Table Header (Sticky) -->
                    <div class="sticky-header px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-800">
                                Products <span class="text-gray-500 font-normal">(<?php echo $total_products; ?> found)</span>
                            </h2>
                            <button type="submit" 
                                    name="update_prices" 
                                    class="px-6 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition flex items-center">
                                <i class="fas fa-save mr-2"></i> Update Selected Product Prices
                            </button>
                        </div>
                    </div>
                    
                    <!-- Products Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                        <input type="checkbox" id="selectAllProducts" onchange="toggleAllProductSelection(this)">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Product Details
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Current Price
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Cost Price
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        New Selling Price
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        New Cost Price
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Stock
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($products_result && $products_result->num_rows > 0): ?>
                                    <?php while ($product = $products_result->fetch_assoc()): ?>
                                        <?php
                                        $product_id = $product['product_id'];
                                        $product_name = $product['name'];
                                        $category = $product['category'] ?? '';
                                        $selling_price = $product['selling_price'] ?? 0;
                                        $cost_price = $product['cost_price'] ?? 0;
                                        $stock = $product['stock_quantity'] ?? 0;
                                        $image_path = $product['image_path'] ?? '';
                                        $description = $product['description'] ?? '';
                                        $sku = $product['sku'] ?? 'N/A';
                                        $brand = $product['brand'] ?? '';
                                        $sub_category = $product['sub_category'] ?? '';
                                        $alert_quantity = $product['alert_quantity'] ?? 10;
                                        ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox" 
                                                       name="product_ids[]" 
                                                       value="<?php echo $product_id; ?>"
                                                       class="product-checkbox checkbox-custom h-4 w-4 text-snooker-green rounded border-gray-300 focus:ring-snooker-green">
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <?php if (!empty($image_path)): ?>
                                                        <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                                             alt="<?php echo htmlspecialchars($product_name); ?>"
                                                             class="h-12 w-12 rounded-lg object-cover mr-4">
                                                    <?php else: ?>
                                                        <div class="h-12 w-12 rounded-lg bg-gray-200 flex items-center justify-center mr-4">
                                                            <i class="fas fa-box text-gray-400"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($product_name); ?>
                                                        </div>
                                                        <div class="text-sm text-gray-500 mt-1">
                                                            <?php if (!empty($category)): ?>
                                                                <span class="category-badge"><?php echo htmlspecialchars($category); ?></span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($brand)): ?>
                                                                <span class="category-badge ml-1"><?php echo htmlspecialchars($brand); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="text-xs text-gray-400 mt-1">
                                                            ID: <?php echo $product_id; ?> | 
                                                            SKU: <?php echo htmlspecialchars($sku); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-lg font-bold text-gray-900">
                                                    PKR <?php echo number_format($selling_price, 2); ?>
                                                </div>
                                                <?php if ($cost_price > 0): ?>
                                                <div class="text-sm text-gray-500">
                                                    Margin: <?php echo number_format((($selling_price - $cost_price) / $cost_price) * 100, 0); ?>%
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-lg font-bold text-blue-600">
                                                    PKR <?php echo number_format($cost_price, 2); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="relative">
                                                    <span class="absolute left-3 top-2 text-gray-500">PKR</span>
                                                    <input type="number" 
                                                           name="new_price[<?php echo $product_id; ?>]" 
                                                           value="<?php echo $selling_price; ?>"
                                                           step="0.01" 
                                                           min="0" 
                                                           class="product-price-input pl-12 pr-3 py-2 border border-gray-300 rounded-lg w-32 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="relative">
                                                    <span class="absolute left-3 top-2 text-gray-500">PKR</span>
                                                    <input type="number" 
                                                           name="new_cost_price[<?php echo $product_id; ?>]" 
                                                           value="<?php echo $cost_price; ?>"
                                                           step="0.01" 
                                                           min="0" 
                                                           class="product-cost-input pl-12 pr-3 py-2 border border-gray-300 rounded-lg w-32 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="w-24">
                                                        <span class="px-3 py-1 rounded-full text-sm font-medium 
                                                            <?php echo $stock > $alert_quantity ? 'bg-green-100 text-green-800' : 
                                                                   ($stock > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                                            <?php echo $stock; ?> units
                                                        </span>
                                                    </div>
                                                    <?php if ($stock <= $alert_quantity): ?>
                                                        <span class="ml-2 text-red-500" title="Low stock">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center">
                                            <div class="text-gray-500">
                                                <i class="fas fa-box-open text-4xl mb-4"></i>
                                                <p class="text-lg">No products found</p>
                                                <p class="text-sm mt-2">Try adjusting your search or filter criteria</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="px-6 py-4 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?php echo min(($page - 1) * $limit + 1, $total_products); ?></span> 
                                    to <span class="font-medium"><?php echo min($page * $limit, $total_products); ?></span> 
                                    of <span class="font-medium"><?php echo $total_products; ?></span> products
                                </div>
                                <div class="flex space-x-1">
                                    <!-- Previous Button -->
                                    <a href="?section=products&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>"
                                       class="px-3 py-2 border border-gray-300 rounded-lg <?php echo $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'; ?>"
                                       <?php echo $page <= 1 ? 'onclick="return false;"' : ''; ?>>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    
                                    <!-- Page Numbers -->
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                            <a href="?section=products&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>"
                                               class="px-3 py-2 border border-gray-300 rounded-lg <?php echo $page == $i ? 'pagination-link active' : 'hover:bg-gray-50'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                            <span class="px-3 py-2">...</span>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <!-- Next Button -->
                                    <a href="?section=products&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>"
                                       class="px-3 py-2 border border-gray-300 rounded-lg <?php echo $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'; ?>"
                                       <?php echo $page >= $total_pages ? 'onclick="return false;"' : ''; ?>>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Quick Actions Panel for Products -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Apply Percentage Increase to Selling Price -->
                <div class="bg-white p-6 rounded-xl snooker-shadow">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-percentage mr-2 text-blue-600"></i> % Increase (Selling Price)
                    </h3>
                    <div class="space-y-4">
                        <div class="flex space-x-2">
                            <input type="number" 
                                   id="productPercentIncrease" 
                                   placeholder="%" 
                                   min="0" 
                                   max="1000"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                            <button onclick="applyProductPercentageIncrease()" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                Apply
                            </button>
                        </div>
                        <p class="text-sm text-gray-500">Increase selling prices by percentage</p>
                    </div>
                </div>
                
                <!-- Apply Fixed Amount to Selling Price -->
                <div class="bg-white p-6 rounded-xl snooker-shadow">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-coins mr-2 text-green-600"></i> Fixed Increase (Selling Price)
                    </h3>
                    <div class="space-y-4">
                        <div class="flex space-x-2">
                            <input type="number" 
                                   id="productFixedIncrease" 
                                   placeholder="PKR" 
                                   step="0.01" 
                                   min="0"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                            <button onclick="applyProductFixedIncrease()" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                Apply
                            </button>
                        </div>
                        <p class="text-sm text-gray-500">Add fixed amount to selling prices</p>
                    </div>
                </div>
                
                <!-- Set Same Selling Price -->
                <div class="bg-white p-6 rounded-xl snooker-shadow">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-equals mr-2 text-purple-600"></i> Set Same Selling Price
                    </h3>
                    <div class="space-y-4">
                        <div class="flex space-x-2">
                            <input type="number" 
                                   id="productSamePrice" 
                                   placeholder="PKR" 
                                   step="0.01" 
                                   min="0"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                            <button onclick="applyProductSamePrice()" 
                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                                Apply
                            </button>
                        </div>
                        <p class="text-sm text-gray-500">Set same selling price for selected</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLES SECTION -->
        <div id="tablesSection" class="<?php echo $active_section == 'tables' ? 'block' : 'hidden'; ?>">
            <!-- Search Section for Tables -->
            <div class="bg-white rounded-xl p-6 snooker-shadow mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Search Box -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Tables</label>
                        <div class="relative">
                            <input type="text" 
                                   id="tableSearchInput" 
                                   placeholder="Search by table name, status..." 
                                   value="<?php echo htmlspecialchars($table_search); ?>"
                                   class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-snooker-green focus:border-transparent">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-end space-x-3">
                        <button onclick="applyTableFilters()" 
                                class="px-6 py-2 bg-snooker-green text-white rounded-lg font-semibold hover:bg-snooker-light transition flex items-center">
                            <i class="fas fa-filter mr-2"></i> Apply Filters
                        </button>
                        <button onclick="resetTableFilters()" 
                                class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition flex items-center">
                            <i class="fas fa-redo mr-2"></i> Reset
                        </button>
                    </div>
                </div>
                
                <!-- Bulk Action Panel -->
                <div class="mt-6 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-orange-800 mb-1">Bulk Table Rate Update</h3>
                            <p class="text-sm text-orange-600">Select tables and update their rates simultaneously</p>
                        </div>
                        <div class="mt-3 md:mt-0">
                            <button onclick="selectAllTables()" 
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition mr-2">
                                <i class="fas fa-check-square mr-1"></i> Select All Tables
                            </button>
                            <button onclick="deselectAllTables()" 
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition">
                                <i class="far fa-square mr-1"></i> Deselect All
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tables Form -->
            <form method="POST" action="update_price.php?section=tables" id="tablePriceForm">
                <div class="bg-white rounded-xl snooker-shadow overflow-hidden">
                    <!-- Table Header (Sticky) -->
                    <div class="sticky-header px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-800">
                                Snooker Tables <span class="text-gray-500 font-normal">(<?php echo $total_tables; ?> found)</span>
                            </h2>
                            <button type="submit" 
                                    name="update_table_prices" 
                                    class="px-6 py-2 bg-orange-600 text-white rounded-lg font-semibold hover:bg-orange-700 transition flex items-center">
                                <i class="fas fa-save mr-2"></i> Update Selected Table Rates
                            </button>
                        </div>
                    </div>
                    
                    <!-- Tables Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                        <input type="checkbox" id="selectAllTables" onchange="toggleAllTableSelection(this)">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Table Details
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Current Rates
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        New Hourly Rate
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        New Century Rate
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($tables_result && $tables_result->num_rows > 0): ?>
                                    <?php while ($table = $tables_result->fetch_assoc()): ?>
                                        <?php
                                        $table_id = $table['id'];
                                        $table_name = $table['table_name'];
                                        $rate_per_hour = $table['rate_per_hour'] ?? 0;
                                        $century_rate = $table['century_rate'] ?? 0;
                                        $status = $table['status'] ?? 'Free';
                                        ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox" 
                                                       name="table_ids[]" 
                                                       value="<?php echo $table_id; ?>"
                                                       class="table-checkbox checkbox-custom h-4 w-4 text-snooker-green rounded border-gray-300 focus:ring-snooker-green">
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center mr-4">
                                                        <i class="fas fa-table-tennis text-blue-600"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($table_name); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-400 mt-1">
                                                            Table ID: <?php echo $table_id; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="space-y-2">
                                                    <div>
                                                        <div class="text-sm text-gray-500">Hourly Rate:</div>
                                                        <div class="text-lg font-bold text-gray-900">
                                                            PKR <?php echo number_format($rate_per_hour, 2); ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm text-gray-500">Century Rate:</div>
                                                        <div class="text-lg font-bold text-purple-600">
                                                            PKR <?php echo number_format($century_rate, 2); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="relative">
                                                    <span class="absolute left-3 top-2 text-gray-500">PKR</span>
                                                    <input type="number" 
                                                           name="new_rate_per_hour[<?php echo $table_id; ?>]" 
                                                           value="<?php echo $rate_per_hour; ?>"
                                                           step="0.01" 
                                                           min="0" 
                                                           class="table-hourly-input pl-12 pr-3 py-2 border border-gray-300 rounded-lg w-32 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="relative">
                                                    <span class="absolute left-3 top-2 text-gray-500">PKR</span>
                                                    <input type="number" 
                                                           name="new_century_rate[<?php echo $table_id; ?>]" 
                                                           value="<?php echo $century_rate; ?>"
                                                           step="0.01" 
                                                           min="0" 
                                                           class="table-century-input pl-12 pr-3 py-2 border border-gray-300 rounded-lg w-32 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="status-badge <?php echo $status == 'Occupied' ? 'status-occupied' : 'status-free'; ?>">
                                                    <?php echo $status; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center">
                                            <div class="text-gray-500">
                                                <i class="fas fa-table-tennis text-4xl mb-4"></i>
                                                <p class="text-lg">No tables found</p>
                                                <p class="text-sm mt-2">Try adjusting your search criteria</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination for Tables -->
                    <?php if ($tables_total_pages > 1): ?>
                        <div class="px-6 py-4 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?php echo min(($tables_page - 1) * $limit + 1, $total_tables); ?></span> 
                                    to <span class="font-medium"><?php echo min($tables_page * $limit, $total_tables); ?></span> 
                                    of <span class="font-medium"><?php echo $total_tables; ?></span> tables
                                </div>
                                <div class="flex space-x-1">
                                    <!-- Previous Button -->
                                    <a href="?section=tables&tables_page=<?php echo $tables_page - 1; ?>&table_search=<?php echo urlencode($table_search); ?>"
                                       class="px-3 py-2 border border-gray-300 rounded-lg <?php echo $tables_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'; ?>"
                                       <?php echo $tables_page <= 1 ? 'onclick="return false;"' : ''; ?>>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    
                                    <!-- Page Numbers -->
                                    <?php for ($i = 1; $i <= $tables_total_pages; $i++): ?>
                                        <?php if ($i == 1 || $i == $tables_total_pages || ($i >= $tables_page - 2 && $i <= $tables_page + 2)): ?>
                                            <a href="?section=tables&tables_page=<?php echo $i; ?>&table_search=<?php echo urlencode($table_search); ?>"
                                               class="px-3 py-2 border border-gray-300 rounded-lg <?php echo $tables_page == $i ? 'pagination-link active' : 'hover:bg-gray-50'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php elseif ($i == $tables_page - 3 || $i == $tables_page + 3): ?>
                                            <span class="px-3 py-2">...</span>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <!-- Next Button -->
                                    <a href="?section=tables&tables_page=<?php echo $tables_page + 1; ?>&table_search=<?php echo urlencode($table_search); ?>"
                                       class="px-3 py-2 border border-gray-300 rounded-lg <?php echo $tables_page >= $tables_total_pages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'; ?>"
                                       <?php echo $tables_page >= $tables_total_pages ? 'onclick="return false;"' : ''; ?>>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Quick Actions Panel for Tables -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Apply Percentage Increase to Hourly Rate -->
                <div class="bg-white p-6 rounded-xl snooker-shadow">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-percentage mr-2 text-orange-600"></i> % Increase (Hourly Rate)
                    </h3>
                    <div class="space-y-4">
                        <div class="flex space-x-2">
                            <input type="number" 
                                   id="tablePercentIncrease" 
                                   placeholder="%" 
                                   min="0" 
                                   max="1000"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                            <button onclick="applyTablePercentageIncrease()" 
                                    class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                                Apply
                            </button>
                        </div>
                        <p class="text-sm text-gray-500">Increase hourly rates by percentage</p>
                    </div>
                </div>
                
                <!-- Apply Fixed Amount to Hourly Rate -->
                <div class="bg-white p-6 rounded-xl snooker-shadow">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-coins mr-2 text-teal-600"></i> Fixed Increase (Hourly Rate)
                    </h3>
                    <div class="space-y-4">
                        <div class="flex space-x-2">
                            <input type="number" 
                                   id="tableFixedIncrease" 
                                   placeholder="PKR" 
                                   step="0.01" 
                                   min="0"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                            <button onclick="applyTableFixedIncrease()" 
                                    class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                                Apply
                            </button>
                        </div>
                        <p class="text-sm text-gray-500">Add fixed amount to hourly rates</p>
                    </div>
                </div>
                
                <!-- Set Same Hourly Rate -->
                <div class="bg-white p-6 rounded-xl snooker-shadow">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-equals mr-2 text-indigo-600"></i> Set Same Hourly Rate
                    </h3>
                    <div class="space-y-4">
                        <div class="flex space-x-2">
                            <input type="number" 
                                   id="tableSamePrice" 
                                   placeholder="PKR" 
                                   step="0.01" 
                                   min="0"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                            <button onclick="applyTableSamePrice()" 
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                Apply
                            </button>
                        </div>
                        <p class="text-sm text-gray-500">Set same hourly rate for selected tables</p>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
    // Section switching
    function switchSection(section) {
        window.location.href = `update_price.php?section=${section}`;
    }

    // PRODUCT FUNCTIONS
    function applyProductFilters() {
        const search = document.getElementById('searchInput').value;
        const category = document.getElementById('categoryFilter') ? document.getElementById('categoryFilter').value : 'all';
        let url = 'update_price.php?section=products&';
        
        if (search) url += `search=${encodeURIComponent(search)}&`;
        if (category !== 'all') url += `category=${encodeURIComponent(category)}&`;
        
        window.location.href = url.slice(0, -1);
    }
    
    function resetProductFilters() {
        window.location.href = 'update_price.php?section=products';
    }
    
    // Handle Enter key in product search
    document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            applyProductFilters();
        }
    });

    function toggleAllProductSelection(checkbox) {
        const productCheckboxes = document.querySelectorAll('.product-checkbox');
        productCheckboxes.forEach(cb => {
            cb.checked = checkbox.checked;
        });
    }
    
    function selectAllProducts() {
        document.querySelectorAll('.product-checkbox').forEach(cb => {
            cb.checked = true;
        });
        document.getElementById('selectAllProducts').checked = true;
    }
    
    function deselectAllProducts() {
        document.querySelectorAll('.product-checkbox').forEach(cb => {
            cb.checked = false;
        });
        document.getElementById('selectAllProducts').checked = false;
    }
    
    // Product bulk price adjustment
    function applyProductPercentageIncrease() {
        const percent = parseFloat(document.getElementById('productPercentIncrease').value);
        if (isNaN(percent) || percent < 0) {
            alert('Please enter a valid percentage');
            return;
        }
        
        const selectedProducts = getSelectedProducts();
        if (selectedProducts.length === 0) {
            alert('Please select at least one product');
            return;
        }
        
        selectedProducts.forEach(productId => {
            const currentPriceInput = document.querySelector(`input[name="new_price[${productId}]"]`);
            const currentPrice = parseFloat(currentPriceInput.value) || 0;
            const newPrice = currentPrice * (1 + percent / 100);
            currentPriceInput.value = newPrice.toFixed(2);
        });
        
        showNotification(`Applied ${percent}% increase to ${selectedProducts.length} products`, 'success');
    }
    
    function applyProductFixedIncrease() {
        const amount = parseFloat(document.getElementById('productFixedIncrease').value);
        if (isNaN(amount) || amount < 0) {
            alert('Please enter a valid amount');
            return;
        }
        
        const selectedProducts = getSelectedProducts();
        if (selectedProducts.length === 0) {
            alert('Please select at least one product');
            return;
        }
        
        selectedProducts.forEach(productId => {
            const currentPriceInput = document.querySelector(`input[name="new_price[${productId}]"]`);
            const currentPrice = parseFloat(currentPriceInput.value) || 0;
            const newPrice = currentPrice + amount;
            currentPriceInput.value = newPrice.toFixed(2);
        });
        
        showNotification(`Added PKR ${amount} to ${selectedProducts.length} products`, 'success');
    }
    
    function applyProductSamePrice() {
        const price = parseFloat(document.getElementById('productSamePrice').value);
        if (isNaN(price) || price < 0) {
            alert('Please enter a valid price');
            return;
        }
        
        const selectedProducts = getSelectedProducts();
        if (selectedProducts.length === 0) {
            alert('Please select at least one product');
            return;
        }
        
        selectedProducts.forEach(productId => {
            const priceInput = document.querySelector(`input[name="new_price[${productId}]"]`);
            priceInput.value = price.toFixed(2);
        });
        
        showNotification(`Set selling price to PKR ${price} for ${selectedProducts.length} products`, 'success');
    }
    
    function getSelectedProducts() {
        const selected = [];
        document.querySelectorAll('.product-checkbox:checked').forEach(cb => {
            selected.push(cb.value);
        });
        return selected;
    }

    // TABLE FUNCTIONS
    function applyTableFilters() {
        const search = document.getElementById('tableSearchInput').value;
        let url = 'update_price.php?section=tables&';
        
        if (search) url += `table_search=${encodeURIComponent(search)}&`;
        
        window.location.href = url.slice(0, -1);
    }
    
    function resetTableFilters() {
        window.location.href = 'update_price.php?section=tables';
    }
    
    // Handle Enter key in table search
    document.getElementById('tableSearchInput')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            applyTableFilters();
        }
    });

    function toggleAllTableSelection(checkbox) {
        const tableCheckboxes = document.querySelectorAll('.table-checkbox');
        tableCheckboxes.forEach(cb => {
            cb.checked = checkbox.checked;
        });
    }
    
    function selectAllTables() {
        document.querySelectorAll('.table-checkbox').forEach(cb => {
            cb.checked = true;
        });
        document.getElementById('selectAllTables').checked = true;
    }
    
    function deselectAllTables() {
        document.querySelectorAll('.table-checkbox').forEach(cb => {
            cb.checked = false;
        });
        document.getElementById('selectAllTables').checked = false;
    }
    
    // Table bulk rate adjustment
    function applyTablePercentageIncrease() {
        const percent = parseFloat(document.getElementById('tablePercentIncrease').value);
        if (isNaN(percent) || percent < 0) {
            alert('Please enter a valid percentage');
            return;
        }
        
        const selectedTables = getSelectedTables();
        if (selectedTables.length === 0) {
            alert('Please select at least one table');
            return;
        }
        
        selectedTables.forEach(tableId => {
            const currentRateInput = document.querySelector(`input[name="new_rate_per_hour[${tableId}]"]`);
            const currentRate = parseFloat(currentRateInput.value) || 0;
            const newRate = currentRate * (1 + percent / 100);
            currentRateInput.value = newRate.toFixed(2);
        });
        
        showNotification(`Applied ${percent}% increase to ${selectedTables.length} tables`, 'success');
    }
    
    function applyTableFixedIncrease() {
        const amount = parseFloat(document.getElementById('tableFixedIncrease').value);
        if (isNaN(amount) || amount < 0) {
            alert('Please enter a valid amount');
            return;
        }
        
        const selectedTables = getSelectedTables();
        if (selectedTables.length === 0) {
            alert('Please select at least one table');
            return;
        }
        
        selectedTables.forEach(tableId => {
            const currentRateInput = document.querySelector(`input[name="new_rate_per_hour[${tableId}]"]`);
            const currentRate = parseFloat(currentRateInput.value) || 0;
            const newRate = currentRate + amount;
            currentRateInput.value = newRate.toFixed(2);
        });
        
        showNotification(`Added PKR ${amount} to ${selectedTables.length} tables`, 'success');
    }
    
    function applyTableSamePrice() {
        const price = parseFloat(document.getElementById('tableSamePrice').value);
        if (isNaN(price) || price < 0) {
            alert('Please enter a valid price');
            return;
        }
        
        const selectedTables = getSelectedTables();
        if (selectedTables.length === 0) {
            alert('Please select at least one table');
            return;
        }
        
        selectedTables.forEach(tableId => {
            const priceInput = document.querySelector(`input[name="new_rate_per_hour[${tableId}]"]`);
            priceInput.value = price.toFixed(2);
        });
        
        showNotification(`Set hourly rate to PKR ${price} for ${selectedTables.length} tables`, 'success');
    }
    
    function getSelectedTables() {
        const selected = [];
        document.querySelectorAll('.table-checkbox:checked').forEach(cb => {
            selected.push(cb.value);
        });
        return selected;
    }

    // Common functions
    function showNotification(message, type) {
        const div = document.createElement('div');
        div.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 
            'bg-yellow-100 text-yellow-700 border border-yellow-300'
        }`;
        div.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} mr-2"></i>
                ${message}
            </div>
        `;
        document.body.appendChild(div);
        
        setTimeout(() => {
            div.remove();
        }, 3000);
    }
    
    // Form validation for products
    document.getElementById('productPriceForm')?.addEventListener('submit', function(e) {
        const selectedProducts = getSelectedProducts();
        if (selectedProducts.length === 0) {
            e.preventDefault();
            alert('Please select at least one product to update');
            return false;
        }
        
        let hasError = false;
        selectedProducts.forEach(productId => {
            const priceInput = document.querySelector(`input[name="new_price[${productId}]"]`);
            const price = parseFloat(priceInput.value);
            
            if (isNaN(price) || price < 0) {
                priceInput.style.borderColor = 'red';
                hasError = true;
            } else {
                priceInput.style.borderColor = '';
            }
        });
        
        if (hasError) {
            e.preventDefault();
            alert('Please enter valid prices (numbers greater than or equal to 0)');
            return false;
        }
        
        if (!confirm(`Are you sure you want to update prices for ${selectedProducts.length} products?`)) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    // Form validation for tables
    document.getElementById('tablePriceForm')?.addEventListener('submit', function(e) {
        const selectedTables = getSelectedTables();
        if (selectedTables.length === 0) {
            e.preventDefault();
            alert('Please select at least one table to update');
            return false;
        }
        
        let hasError = false;
        selectedTables.forEach(tableId => {
            const rateInput = document.querySelector(`input[name="new_rate_per_hour[${tableId}]"]`);
            const rate = parseFloat(rateInput.value);
            
            if (isNaN(rate) || rate < 0) {
                rateInput.style.borderColor = 'red';
                hasError = true;
            } else {
                rateInput.style.borderColor = '';
            }
        });
        
        if (hasError) {
            e.preventDefault();
            alert('Please enter valid rates (numbers greater than or equal to 0)');
            return false;
        }
        
        if (!confirm(`Are you sure you want to update rates for ${selectedTables.length} tables?`)) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    // Auto-select when clicking on price/rate inputs
    document.querySelectorAll('.product-price-input, .table-hourly-input').forEach(input => {
        input.addEventListener('focus', function() {
            this.select();
            
            // Also check the checkbox
            const match = this.name.match(/\[(\d+)\]/);
            if (match) {
                const id = match[1];
                if (this.classList.contains('product-price-input')) {
                    const checkbox = document.querySelector(`.product-checkbox[value="${id}"]`);
                    if (checkbox) checkbox.checked = true;
                } else if (this.classList.contains('table-hourly-input')) {
                    const checkbox = document.querySelector(`.table-checkbox[value="${id}"]`);
                    if (checkbox) checkbox.checked = true;
                }
            }
        });
    });
</script>

</body>
</html>