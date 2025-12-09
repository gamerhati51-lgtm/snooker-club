<?php
session_start();
include 'db.php'; 

$product = null;
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id) {
    die('<div class="text-center p-10 text-red-600">Error: Product ID is required.</div>');
}

// --- Fetch ALL Product Data (including description and image_path) ---
$stmt = $conn->prepare("
    SELECT 
        product_id, sku, barcode_type, unit, name, description, cost_price, 
        selling_price, stock_quantity, category, is_active, created_at, 
        updated_at, brand, sub_category, alert_quantity, is_service_product, 
        weight, service_time_minutes, tax_id, selling_price_tax_type, product_type,
        -- *** Add image_path to the select query ***
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
    die('<div class="text-center p-10 text-red-600">Error: Product not found.</div>');
}

// Helper function for display
function format_detail($value, $default = 'N/A') {
    return htmlspecialchars($value ?? $default);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details: <?php echo format_detail($product['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans">

  <div class="flex min-h-screen">

    <?php include 'layout/sidebar.php'; ?>
<main class="flex-1 ml-0 lg:ml-64 pt-7 p-8 main-content"> 

    <!-- Header with bottom margin -->
    <div class="mb-6">
        <?php include "layout/header.php"; ?>
    </div>

    <!-- Page Content -->
    <div id="content-area" class="space-y-8 bg-white p-6 rounded-lg shadow-xl mt-6">
        <!-- Your page content goes here -->
 

            <h2 class="text-4xl font-extrabold text-gray-800">
                Details: <?php echo format_detail($product['name']); ?>
            </h2>
            <div class="space-x-3">
               
                <a href="list_product.php" class="px-4 py-2 text-gray-600 border rounded-lg hover:bg-gray-100 transition">
                    &larr; Back to List
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1 space-y-6">
                
                <div class="p-4 border rounded-xl bg-gray-100 shadow-inner">
                    <h3 class="text-xl font-bold mb-3 text-gray-700">Product Image</h3>
                    <?php 
                    $image_src = format_detail($product['image_path'], 'images/default/no_image.png');
                    // Check if a path exists and if the file exists (optional, depends on environment)
                    if ($product['image_path'] && file_exists($product['image_path'])): 
                    ?>
                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                             alt="Image of <?php echo format_detail($product['name']); ?>" 
                             class="w-full h-auto object-cover rounded-lg border-2 border-gray-300">
                    <?php else: ?>
                        <div class="w-full h-48 bg-gray-200 flex items-center justify-center rounded-lg text-gray-500 border border-dashed">
                            
                            <p class="text-center font-medium">No Image Uploaded</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-4 border rounded-xl bg-gray-50">
                    <h3 class="text-xl font-bold mb-3 text-gray-700">Description</h3>
                    <p class="text-gray-700 whitespace-pre-wrap">
                        <?php echo format_detail($product['description'], 'No detailed description available.'); ?>
                    </p>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                
                <div class="bg-indigo-50 p-6 rounded-xl border border-indigo-200">
                    <h3 class="text-2xl font-bold mb-4 text-indigo-800">💰 Pricing & Cost</h3>
                    <div class="grid grid-cols-2 gap-4 text-lg">
                        <div class="font-medium text-gray-600">Cost Price (Club Pays):</div>
                        <div class="font-bold text-gray-800"><?php echo number_format($product['cost_price'], 2); ?> PKR</div>
                        
                        <div class="font-medium text-gray-600">Selling Price (Customer Pays):</div>
                        <div class="font-extrabold text-green-700 text-2xl"><?php echo number_format($product['selling_price'], 2); ?> PKR</div>
                        
                        <div class="font-medium text-gray-600">Tax Type:</div>
                        <div class="text-gray-800"><?php echo format_detail($product['selling_price_tax_type']); ?></div>
                    </div>
                </div>

                <div class="bg-yellow-50 p-6 rounded-xl border border-yellow-200">
                    <h3 class="text-2xl font-bold mb-4 text-yellow-800">📦 Inventory & Stock</h3>
                    <div class="grid grid-cols-2 gap-4 text-lg">
                        <div class="font-medium text-gray-600">Product Type:</div>
                        <div class="font-bold text-gray-800">
                            <?php 
                                if ($product['is_service_product']) {
                                    echo '<span class="text-indigo-600">Service (No Stock)</span>';
                                } else {
                                    echo format_detail($product['product_type']);
                                }
                            ?>
                        </div>

                        <div class="font-medium text-gray-600">Current Stock:</div>
                        <div class="font-bold <?php echo $product['stock_quantity'] <= $product['alert_quantity'] ? 'text-red-600' : 'text-gray-800'; ?>">
                            <?php echo $product['is_service_product'] ? 'N/A' : (int)$product['stock_quantity'] . ' ' . format_detail($product['unit']); ?>
                        </div>

                        <div class="font-medium text-gray-600">Low Alert Quantity:</div>
                        <div class="text-gray-800"><?php echo $product['is_service_product'] ? 'N/A' : (int)$product['alert_quantity']; ?></div>
                        
                        <div class="font-medium text-gray-600">Weight:</div>
                        <div class="text-gray-800"><?php echo format_detail($product['weight']); ?></div>
                    </div>
                </div>

                <div class="bg-gray-100 p-6 rounded-xl border border-gray-200">
                    <h3 class="text-2xl font-bold mb-4 text-gray-700">🏷️ General Information</h3>
                    <div class="grid grid-cols-2 gap-4 text-base">
                        <div class="font-medium text-gray-600">Product ID / SKU:</div>
                        <div class="text-gray-800 font-mono"><?php echo format_detail($product['sku']); ?> (DB ID: <?php echo $product_id; ?>)</div>
                        
                        <div class="font-medium text-gray-600">Category / Sub-Category:</div>
                        <div class="text-gray-800"><?php echo format_detail($product['category']); ?> / <?php echo format_detail($product['sub_category']); ?></div>
                        
                        <div class="font-medium text-gray-600">Brand:</div>
                        <div class="text-gray-800"><?php echo format_detail($product['brand']); ?></div>
                        
                        <div class="font-medium text-gray-600">Barcode Type:</div>
                        <div class="text-gray-800"><?php echo format_detail($product['barcode_type']); ?></div>
                        
                        <div class="font-medium text-gray-600">Status:</div>
                        <div class="font-bold <?php echo $product['is_active'] ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                        </div>
                        
                        <div class="font-medium text-gray-600">Created At:</div>
                        <div class="text-gray-800"><?php echo date('Y-m-d H:i A', strtotime($product['created_at'])); ?></div>
                        
                        <div class="font-medium text-gray-600">Last Updated:</div>
                        <div class="text-gray-800"><?php echo date('Y-m-d H:i A', strtotime($product['updated_at'])); ?></div>
                    </div>
                </div>
                
            </div>
            
        </div>
        
    </div>
</body>
</html>