<?php
session_start();
include 'db.php'; 

$product = null;
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id) {
    die('<div class="min-h-screen flex items-center justify-center bg-gray-50"><div class="text-center p-10 bg-white rounded-xl shadow-lg max-w-md"><i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i><h2 class="text-2xl font-bold text-gray-800 mb-2">Invalid Request</h2><p class="text-gray-600">Product ID is required to view details.</p></div></div>');
}

// Fetch ALL Product Data
$stmt = $conn->prepare("
    SELECT 
        product_id, sku, barcode_type, unit, name, description, cost_price, 
        selling_price, stock_quantity, category, is_active, created_at, 
        updated_at, brand, sub_category, alert_quantity, is_service_product, 
        weight, service_time_minutes, tax_id, selling_price_tax_type, product_type,
        image_path
    FROM 
        products 
    WHERE product_id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    die('<div class="min-h-screen flex items-center justify-center bg-gray-50"><div class="text-center p-10 bg-white rounded-xl shadow-lg max-w-md"><i class="fas fa-box-open text-4xl text-yellow-500 mb-4"></i><h2 class="text-2xl font-bold text-gray-800 mb-2">Product Not Found</h2><p class="text-gray-600">The requested product doesn\'t exist or has been removed.</p><a href="list_product.php" class="mt-4 inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Back to Products</a></div></div>');
}

// Helper function for display
function format_detail($value, $default = 'N/A') {
    return htmlspecialchars($value ?? $default);
}

// Determine stock status
$stock_quantity = (int)$product['stock_quantity'];
$alert_quantity = (int)$product['alert_quantity'];
$stock_status = 'healthy';
if ($product['is_service_product']) {
    $stock_status = 'service';
} elseif ($stock_quantity <= 0) {
    $stock_status = 'out-of-stock';
} elseif ($stock_quantity <= $alert_quantity) {
    $stock_status = 'low';
}

// Calculate profit margin if possible
$profit_margin = 0;
if ($product['cost_price'] > 0 && $product['selling_price'] > 0) {
    $profit_margin = (($product['selling_price'] - $product['cost_price']) / $product['cost_price']) * 100;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details: <?php echo format_detail($product['name']); ?> | Inventory System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stock-healthy { background-color: #10b981; }
        .stock-low { background-color: #f59e0b; }
        .stock-out-of-stock { background-color: #ef4444; }
        .stock-service { background-color: #8b5cf6; }
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 font-sans">

<body class="bg-gray-50 font-sans">

  <!-- Dashboard Container -->
  <div class="flex min-h-screen">

    <!-- Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 ml-0 lg:ml-64 pt-20 p-8 main-content">
      
      <!-- Header -->
      <?php include "layout/header.php"; ?>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Product Details</h1>
                    <p class="text-gray-600 mt-1">Complete information about <?php echo format_detail($product['name']); ?></p>
                </div>
                <div class="flex flex-wrap gap-3 mt-4 md:mt-0">
                        <a href="list_product.php" class="px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center gap-2">
                        <i class="fas fa-list"></i>
                        View All Products
                    </a>
                
                    <button onclick="window.print()" class="px-6 py-3 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition flex items-center gap-2">
                        <i class="fas fa-print"></i>
                        Print Details
                    </button>
                    <a href="list_product.php" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        Back to Products
                    </a>
                 
                </div>
            </div>

            <!-- Product Overview Card -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8 card-hover">
                <div class="gradient-bg p-6 text-white">
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <div>
                            <div class="flex items-center gap-4">
                                <div class="bg-white/20 p-3 rounded-xl">
                                    <i class="fas fa-box-open text-2xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-2xl md:text-3xl font-bold"><?php echo format_detail($product['name']); ?></h2>
                                    <p class="text-blue-100 mt-1">SKU: <?php echo format_detail($product['sku']); ?> • ID: <?php echo $product_id; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 md:mt-0 flex flex-wrap gap-3">
                            <span class="badge <?php echo $product['is_active'] ? 'bg-green-500' : 'bg-red-500'; ?>">
                                <span class="status-dot bg-white"></span>
                                <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <span class="badge stock-<?php echo $stock_status; ?>">
                                <span class="status-dot bg-white"></span>
                                <?php 
                                switch($stock_status) {
                                    case 'healthy': echo 'In Stock'; break;
                                    case 'low': echo 'Low Stock'; break;
                                    case 'out-of-stock': echo 'Out of Stock'; break;
                                    case 'service': echo 'Service'; break;
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Grid -->
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Left Column: Image & Description -->
                        <div class="lg:col-span-1 space-y-6">
                            <!-- Product Image Card -->
                            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                                <div class="p-4 border-b border-gray-200">
                                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-image text-blue-500"></i>
                                        Product Image
                                    </h3>
                                </div>
                                <div class="p-4">
                                    <?php 
                                    $image_src = format_detail($product['image_path'], 'images/default/no_image.png');
                                    if ($product['image_path'] && file_exists($product['image_path'])): 
                                    ?>
                                        <div class="relative overflow-hidden rounded-lg bg-gray-100">
                                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                                 alt="Image of <?php echo format_detail($product['name']); ?>" 
                                                 class="w-full h-64 object-contain transition-transform duration-500 hover:scale-105">
                                        </div>
                                    <?php else: ?>
                                        <div class="w-full h-64 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg flex flex-col items-center justify-center text-gray-400 border-2 border-dashed border-gray-300">
                                            <i class="fas fa-camera text-5xl mb-4"></i>
                                            <p class="text-center font-medium">No Image Available</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Description Card -->
                            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                                <div class="p-4 border-b border-gray-200">
                                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-align-left text-purple-500"></i>
                                        Description
                                    </h3>
                                </div>
                                <div class="p-4">
                                    <p class="text-gray-700 whitespace-pre-wrap bg-gray-50 p-4 rounded-lg min-h-[150px]">
                                        <?php echo format_detail($product['description'], 'No detailed description available.'); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Quick Stats Card -->
                            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                                <div class="p-4 border-b border-gray-200">
                                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-chart-bar text-green-500"></i>
                                        Quick Stats
                                    </h3>
                                </div>
                                <div class="p-4">
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Product Type</span>
                                            <span class="font-medium text-gray-800">
                                                <?php 
                                                if ($product['is_service_product']) {
                                                    echo '<span class="text-purple-600">Service</span>';
                                                } else {
                                                    echo format_detail($product['product_type']);
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Unit</span>
                                            <span class="font-medium text-gray-800"><?php echo format_detail($product['unit']); ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Weight</span>
                                            <span class="font-medium text-gray-800"><?php echo format_detail($product['weight']); ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Service Time</span>
                                            <span class="font-medium text-gray-800">
                                                <?php 
                                                echo $product['service_time_minutes'] 
                                                    ? $product['service_time_minutes'] . ' minutes' 
                                                    : 'N/A'; 
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Details -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Pricing & Cost Card -->
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-xl overflow-hidden shadow-sm">
                                <div class="p-4 border-b border-blue-200 bg-blue-50">
                                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-money-bill-wave text-blue-600"></i>
                                        Pricing & Cost Analysis
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="bg-white p-5 rounded-lg border border-blue-100">
                                            <div class="text-sm text-gray-500 mb-1">Cost Price</div>
                                            <div class="text-3xl font-bold text-gray-800"><?php echo number_format($product['cost_price'], 2); ?> <span class="text-lg">PKR</span></div>
                                            <div class="text-sm text-gray-500 mt-2">Amount the club pays</div>
                                        </div>
                                        <div class="bg-white p-5 rounded-lg border border-green-100">
                                            <div class="text-sm text-gray-500 mb-1">Selling Price</div>
                                            <div class="text-3xl font-bold text-green-600"><?php echo number_format($product['selling_price'], 2); ?> <span class="text-lg">PKR</span></div>
                                            <div class="text-sm text-gray-500 mt-2">Customer pays this amount</div>
                                        </div>
                                     
                                    </div>
                                </div>
                            </div>

                            <!-- Inventory & Stock Card -->
                            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-100 rounded-xl overflow-hidden shadow-sm">
                                <div class="p-4 border-b border-amber-200 bg-amber-50">
                                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-warehouse text-amber-600"></i>
                                        Inventory & Stock Details
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="bg-white p-5 rounded-lg border border-amber-100">
                                            <div class="text-sm text-gray-500 mb-1">Current Stock</div>
                                            <div class="text-4xl font-bold <?php 
                                                echo $stock_status == 'healthy' ? 'text-green-600' : 
                                                ($stock_status == 'low' ? 'text-amber-600' : 
                                                ($stock_status == 'out-of-stock' ? 'text-red-600' : 'text-purple-600')); 
                                            ?>">
                                                <?php echo $product['is_service_product'] ? 'N/A' : (int)$product['stock_quantity']; ?>
                                            </div>
                                            <div class="text-sm text-gray-500 mt-2"><?php echo format_detail($product['unit']); ?></div>
                                        </div>
                                        <div class="bg-white p-5 rounded-lg border border-amber-100">
                                            <div class="text-sm text-gray-500 mb-1">Low Stock Alert Level</div>
                                            <div class="text-3xl font-bold text-amber-600"><?php echo $product['is_service_product'] ? 'N/A' : (int)$product['alert_quantity']; ?></div>
                                            <div class="text-sm text-gray-500 mt-2">Notifications trigger below this</div>
                                        </div>
                                        <div class="bg-white p-5 rounded-lg border border-amber-100">
                                            <div class="text-sm text-gray-500 mb-1">Stock Status</div>
                                            <div class="text-xl font-bold flex items-center gap-2">
                                                <span class="badge stock-<?php echo $stock_status; ?>">
                                                    <span class="status-dot bg-white"></span>
                                                    <?php 
                                                    switch($stock_status) {
                                                        case 'healthy': echo 'Healthy Stock'; break;
                                                        case 'low': echo 'Low Stock - Reorder Soon'; break;
                                                        case 'out-of-stock': echo 'Out of Stock'; break;
                                                        case 'service': echo 'Service Product'; break;
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                            <div class="text-sm text-gray-500 mt-2">
                                                <?php 
                                                if ($stock_status == 'low') {
                                                    echo 'Only ' . ($stock_quantity - $alert_quantity) . ' units above alert level';
                                                } elseif ($stock_status == 'out-of-stock') {
                                                    echo 'No units available for sale';
                                                } elseif ($stock_status == 'service') {
                                                    echo 'This is a service, not a physical product';
                                                } else {
                                                    echo 'Stock level is sufficient';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <div class="bg-white p-5 rounded-lg border border-amber-100">
                                            <div class="text-sm text-gray-500 mb-1">Barcode Type</div>
                                            <div class="text-xl font-bold text-gray-800"><?php echo format_detail($product['barcode_type']); ?></div>
                                            <div class="text-sm text-gray-500 mt-2">Used for inventory scanning</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Information Card -->
                            <div class="bg-gradient-to-r from-gray-50 to-gray-100 border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                                <div class="p-4 border-b border-gray-300 bg-gray-100">
                                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-info-circle text-gray-600"></i>
                                        Product Information
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-5">
                                            <div>
                                                <div class="text-sm text-gray-500 mb-1">Category</div>
                                                <div class="font-medium text-gray-800 flex items-center gap-2">
                                                    <i class="fas fa-tag text-blue-500"></i>
                                                    <?php echo format_detail($product['category']); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500 mb-1">Sub-Category</div>
                                                <div class="font-medium text-gray-800"><?php echo format_detail($product['sub_category']); ?></div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500 mb-1">Brand</div>
                                                <div class="font-medium text-gray-800 flex items-center gap-2">
                                                    <i class="fas fa-copyright text-purple-500"></i>
                                                    <?php echo format_detail($product['brand']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="space-y-5">
                                            <div>
                                                <div class="text-sm text-gray-500 mb-1">Created Date</div>
                                                <div class="font-medium text-gray-800 flex items-center gap-2">
                                                    <i class="fas fa-calendar-plus text-green-500"></i>
                                                    <?php echo date('F j, Y', strtotime($product['created_at'])); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500 mb-1">Last Updated</div>
                                                <div class="font-medium text-gray-800 flex items-center gap-2">
                                                    <i class="fas fa-calendar-check text-amber-500"></i>
                                                    <?php echo date('F j, Y \a\t g:i A', strtotime($product['updated_at'])); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500 mb-1">Tax ID</div>
                                                <div class="font-medium text-gray-800"><?php echo format_detail($product['tax_id']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer Actions -->
            <div class="flex justify-center mt-8 pt-6 border-t border-gray-200">
                <div class="flex flex-wrap gap-4">
                
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Print Styles -->
<style>
    @media print {
        .no-print { display: none !important; }
        body { background: white !important; }
        .gradient-bg { background: #667eea !important; }
        .shadow-xl, .shadow-sm { box-shadow: none !important; }
        .border { border: 1px solid #ddd !important; }
        .card-hover:hover { transform: none !important; }
    }
</style>

</body>
</html>