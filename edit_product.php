<?php
session_start();
include 'db.php'; 

$message = '';
$product = null;
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// --- 1. Fetch Product Data ---
if ($product_id) {
    // Select ALL relevant columns
    $stmt = $conn->prepare("
        SELECT 
            product_id, sku, name, description, cost_price, selling_price, 
            stock_quantity, category, alert_quantity, is_service_product
        FROM 
            products 
        WHERE product_id = ?
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
}

if (!$product) {
    die('<div class="text-center p-10 text-red-600">Error: Product ID not found or invalid.</div>');
}

// --- 2. Handle Form Submission (Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    
    // Sanitize and collect input for updated fields
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $cost_price = (float)($_POST['cost_price'] ?? 0);
    $selling_price = (float)($_POST['selling_price'] ?? 0);
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $alert_quantity = (int)($_POST['alert_quantity'] ?? 0);
    // Checkbox value needs special handling
    $is_service_product = isset($_POST['is_service_product']) ? 1 : 0; 
    
    if (empty($name) || $cost_price < 0 || $selling_price < 0) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Please enter a valid product name and prices.</div>';
    } else {
        // Prepare UPDATE statement with all necessary fields
        $stmt = $conn->prepare("
            UPDATE products SET 
                sku = ?, name = ?, description = ?, cost_price = ?, selling_price = ?, 
                stock_quantity = ?, category = ?, alert_quantity = ?, is_service_product = ?, updated_at = NOW() 
            WHERE product_id = ?
        ");
        
        // Bind parameters: sssssdiiiii (string, string, string, float, float, int, string, int, int, int)
        $stmt->bind_param(
            "sssddisiii", 
            $sku, $name, $description, $cost_price, $selling_price, 
            $stock_quantity, $category, $alert_quantity, $is_service_product, $product_id
        );

        if ($stmt->execute()) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">Product **' . htmlspecialchars($name) . '** updated successfully!</div>';
            
            // Re-fetch or update the $product array to show latest data in the form
            $product = array_merge($product, compact(
                'sku', 'name', 'description', 'cost_price', 'selling_price', 
                'stock_quantity', 'category', 'alert_quantity', 'is_service_product'
            ));
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Error updating product: ' . $stmt->error . '</div>';
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product: <?php echo htmlspecialchars($product['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-100 font-sans">
  <div class="flex min-h-screen">

    <?php include 'layout/sidebar.php'; ?>

    <main class="flex-1 ml-0 lg:ml-64 pt-7 p-8 main-conten mb-3"> 
      
      <?php include "layout/header.php" ;  ?>

      <div id="content-area" class="space-y-8 bg-blue-50 p-6 rounded-lg shadow-xl mt-9">
        <h2 class="text-3xl font-bold mb-6 text-gray-800 border-b pb-2 mt-3">✏️ Edit Product: <?php echo htmlspecialchars($product['name']); ?></h2>
        
        <?php echo $message; ?>

        <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST" class="space-y-6">
            <input type="hidden" name="update_product" value="1">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Product Name</label>
                    <input type="text" name="name" id="name" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"
                           value="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                
                <div>
                    <label for="sku" class="block text-sm font-medium text-gray-700">SKU / Item Code</label>
                    <input type="text" name="sku" id="sku"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"
                           value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>">
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <input type="text" name="category" id="category"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"
                           value="<?php echo htmlspecialchars($product['category'] ?? ''); ?>">
                </div>
                
                <div>
                    <label for="stock_quantity" class="block text-sm font-medium text-gray-700">Current Stock Quantity</label>
                    <input type="number" min="0" name="stock_quantity" id="stock_quantity"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"
                           value="<?php echo (int)$product['stock_quantity']; ?>" <?php echo ($product['is_service_product'] ? 'disabled' : ''); ?>>
                </div>

                <div>
                    <label for="cost_price" class="block text-sm font-medium text-gray-700">Cost Price (PKR)</label>
                    <input type="number" step="0.01" min="0" name="cost_price" id="cost_price" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"
                           value="<?php echo htmlspecialchars($product['cost_price']); ?>">
                </div>
                
                <div>
                    <label for="selling_price" class="block text-sm font-medium text-gray-700">Selling Price (PKR)</label>
                    <input type="number" step="0.01" min="0" name="selling_price" id="selling_price" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"
                           value="<?php echo htmlspecialchars($product['selling_price']); ?>">
                </div>
                
            </div>
            
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="description" rows="3"
                          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm resize-none"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            </div>

            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold mb-3 text-gray-800">Inventory Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="alert_quantity" class="block text-sm font-medium text-gray-700">Low Stock Alert Quantity</label>
                        <input type="number" min="0" name="alert_quantity" id="alert_quantity"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"
                               value="<?php echo (int)$product['alert_quantity']; ?>">
                    </div>
                    
                    <div class="flex items-center pt-5">
                        <input id="is_service_product" name="is_service_product" type="checkbox" value="1"
                               class="h-4 w-4 text-indigo-600 border-gray-300 rounded"
                               <?php echo ($product['is_service_product'] ? 'checked' : ''); ?>
                               onchange="document.getElementById('stock_quantity').disabled = this.checked;">
                        <label for="is_service_product" class="ml-2 block text-sm font-medium text-gray-700">
                            Is a Service Product (Don't track inventory)
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center pt-6 border-t mt-6">
                <button type="submit"
                        class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-md 
                        shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2
                         focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                    💾 Save Changes
                </button>
                <a href="list_product.php" class="text-orange-600 hover:text-gray-800 font-medium">
                    &larr; Back to Product List
                </a>
            </div>
        </form>
    </div>
</body>
</html>