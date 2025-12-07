<?php
session_start();
include 'db.php';

$message = '';

// --- 1. Handle Deletion Request (Using product_id) ---
if (isset($_GET['delete_id'])) {
    $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
    if ($delete_id) {
        // Use product_id for deletion
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $delete_id);
        
        if ($stmt->execute()) {
            $message = '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">Product ID **' . $delete_id . '** deleted successfully.</div>';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Error deleting product: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    }
}

// --- 2. Fetch All Products (Including the new 'image_path' and 'description' columns for detailed view) ---
$products = [];
// *** IMPORTANT: Make sure you add a 'image_path' column (VARCHAR) to your 'products' table. ***
// We are selecting the core fields, but only displaying summary fields in the table.
$stmt = $conn->prepare("
    SELECT 
        product_id, sku, name, cost_price, selling_price, stock_quantity, category, is_service_product
    FROM 
        products 
    ORDER BY name ASC
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage All Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
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



      <div id="content-area" class="space-y-8 bg-white p-6 rounded-lg shadow-xl mt-5">

        <h2 class="text-3xl font-bold mb-6 text-gray-800 border-b pb-2 text-center">📦 Product Inventory List (<?php echo count($products); ?>)</h2>
        
        <?php echo $message; ?>

        <div class="mb-4 flex justify-between items-center">
             <p class="text-gray-600">Overview of all active and service products.</p>
             <a href="add_product.php" class="px-4 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition">
                + Add New Product
            </a>
        </div>

        <?php if (empty($products)): ?>
            <p class="text-center py-10 text-gray-500 italic">No products found. Please add a product first.</p>
        <?php else: ?>
            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-600 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider rounded-tl-lg">SKU</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Product Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Cost Price</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Selling Price</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Stock</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider rounded-tr-lg">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($products as $product): ?>
                        <tr class="<?php echo ($product['stock_quantity'] <= 0 && $product['is_service_product'] == 0) ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50'; ?>">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($product['name']); ?>
                                <?php if ($product['is_service_product']): ?>
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                        Service
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700"><?php echo number_format($product['cost_price'], 2); ?> PKR</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-green-700"><?php echo number_format($product['selling_price'], 2); ?> PKR</td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-bold">
                                <?php 
                                    if ($product['is_service_product']) {
                                        echo '<span class="text-gray-400">N/A</span>';
                                    } elseif ($product['stock_quantity'] <= 0) {
                                        echo '<span class="text-red-600">Out of Stock</span>';
                                    } else {
                                        echo (int)$product['stock_quantity'];
                                    }
                                ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium space-x-2">
                                <a href="view_product.php?id=<?php echo $product['product_id']; ?>" class="text-gray-600 hover:text-gray-900 mr-2">
                                    Details
                                </a>
                                <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="text-blue-600 hover:text-blue-800">
                                    Edit
                                </a>
                                <a href="list_product.php?delete_id=<?php echo $product['product_id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete <?php echo addslashes($product['name']); ?>?');"
                                   class="text-red-600 hover:text-red-800 ml-4">
                                    Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>