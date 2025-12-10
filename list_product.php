<?php
session_start();
include 'db.php';

$message = '';

// --- 1. Handle Deletion Request ---
if (isset($_GET['delete_id'])) {
    $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
    if ($delete_id) {
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

// --- 2. Search Functionality ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Build query with search
// Build query with search - correctly using image_path
$sql = "
    SELECT 
        product_id, sku, name, cost_price, selling_price, stock_quantity, 
        category, is_service_product, description, alert_quantity, image_path
    FROM products WHERE 1=1
";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR sku LIKE ? OR category LIKE ? OR description LIKE ?)";
    $search_term = "%" . $search . "%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $types .= "ssss";
}

if (!empty($category_filter) && $category_filter !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

$sql .= " ORDER BY name ASC";

// Prepare statement
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Get unique categories for filter
$category_stmt = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = [];
while ($cat = $category_stmt->fetch_assoc()) {
    $categories[] = $cat['category'];
}
$category_stmt->close();
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage All Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            position: relative;
            max-height: 85vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .close-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }
        
        .close-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .form-input {
            padding-left: 40px;
            padding-top: 0.85rem;
            padding-bottom: 0.4rem;
            transition: all 0.2s ease;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.95rem;
            background: transparent;
            width: 100%;
        }
        
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 0.9rem;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-container input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        #message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            transition: transform 0.3s;
        }
        
        .product-image:hover {
            transform: scale(1.5);
        }
        
        .image-preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 12px;
            border: 3px solid #e5e7eb;
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">

  <!-- Dashboard Container -->
  <div class="flex min-h-screen">

    <!-- Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 ml-0 lg:ml-64 pt-20 p-8 main-content">
      
      <!-- Header -->
      <?php include "layout/header.php"; ?>

      <!-- Success/Error Message -->
      <div id="message" class="hidden"></div>

      <div id="content-area" class="space-y-8 bg-white p-6 rounded-lg shadow-xl mt-5">

        <h2 class="text-3xl font-bold mb-6 text-gray-800 border-b pb-2 text-center">📦 Product Inventory List (<?php echo count($products); ?>)</h2>
        
        <?php echo $message; ?>

        <!-- Search and Filter Section -->
        <div class="mb-8 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl shadow">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex-1 w-full">
                    <form method="GET" action="" class="flex flex-col md:flex-row gap-4">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition"
                                   placeholder="Search products by name, SKU, category, or description...">
                        </div>
                        
                        <div class="w-full md:w-64">
                            <select name="category" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none">
                                <option value="all" <?php echo ($category_filter == 'all' || empty($category_filter)) ? 'selected' : ''; ?>>All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                                            <?php echo ($category_filter == $cat) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="flex gap-2">
                            <button type="submit" 
                                    class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <?php if (!empty($search) || !empty($category_filter)): ?>
                                <a href="list_product.php" 
                                   class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition flex items-center gap-2">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <a href="add_product.php" 
                   class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-semibold rounded-lg hover:from-green-700 hover:to-emerald-700 transition flex items-center gap-2 shadow-md">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
            </div>
            
            <?php if (!empty($search)): ?>
                <div class="mt-4 text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-1"></i>
                    Showing results for: <span class="font-semibold">"<?php echo htmlspecialchars($search); ?>"</span>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($products)): ?>
            <div class="text-center py-16">
                <i class="fas fa-box-open text-5xl text-gray-300 mb-4"></i>
                <p class="text-xl text-gray-500 mb-2">No products found</p>
                <p class="text-gray-400 mb-6"><?php echo !empty($search) ? 'Try a different search term.' : 'Start by adding your first product.'; ?></p>
                <a href="add_product.php" 
                   class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition inline-flex items-center gap-2">
                    <i class="fas fa-plus"></i> Add Product
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto shadow-lg rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider rounded-tl-xl">Image</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Product Details</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Category</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Cost Price</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Selling Price</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider rounded-tr-xl">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($products as $product): ?>
                        <tr class="<?php echo ($product['stock_quantity'] <= 0 && $product['is_service_product'] == 0) ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50'; ?>" id="product-row-<?php echo $product['product_id']; ?>">
                            <!-- Product Image -->
                          <td class="px-6 py-4 whitespace-nowrap">
    <?php if (!empty($product['image_path'])): ?>
        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
             alt="<?php echo htmlspecialchars($product['name']); ?>"
             class="product-image">
    <?php else: ?>
        <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
            <i class="fas fa-box text-gray-400 text-xl"></i>
        </div>
    <?php endif; ?>
</td>
                            
                            <!-- Product Details -->
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </span>
                                    <span class="text-xs text-gray-500 mt-1">
                                        SKU: <?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?>
                                    </span>
                                    <?php if ($product['is_service_product']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 mt-2">
                                            <i class="fas fa-concierge-bell mr-1"></i> Service Product
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <!-- Category -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?>
                                </span>
                            </td>
                            
                            <!-- Prices -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" id="cost-price-<?php echo $product['product_id']; ?>">
                                <span class="font-medium"><?php echo number_format($product['cost_price'], 2); ?></span> PKR
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-700" id="selling-price-<?php echo $product['product_id']; ?>">
                                <span class="font-bold"><?php echo number_format($product['selling_price'], 2); ?></span> PKR
                            </td>
                            
                            <!-- Stock -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php if ($product['is_service_product']): ?>
                                    <span class="text-gray-400 text-sm">—</span>
                                <?php elseif ($product['stock_quantity'] <= 0): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-red-100 text-red-800">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Out of Stock
                                    </span>
                                <?php elseif ($product['alert_quantity'] && $product['stock_quantity'] <= $product['alert_quantity']): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-yellow-100 text-yellow-800" id="stock-quantity-<?php echo $product['product_id']; ?>">
                                        <i class="fas fa-exclamation-circle mr-1"></i> <?php echo (int)$product['stock_quantity']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-green-100 text-green-800" id="stock-quantity-<?php echo $product['product_id']; ?>">
                                        <i class="fas fa-check-circle mr-1"></i> <?php echo (int)$product['stock_quantity']; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-3">
                                    <a href="view_product.php?id=<?php echo $product['product_id']; ?>" 
                                       class="text-gray-600 hover:text-blue-600 transition p-2 rounded-lg hover:bg-blue-50"
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button onclick="openEditModal(<?php echo $product['product_id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-800 transition p-2 rounded-lg hover:bg-blue-50"
                                            title="Edit Product">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="list_product.php?delete_id=<?php echo $product['product_id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete <?php echo addslashes($product['name']); ?>? This action cannot be undone.');"
                                       class="text-red-600 hover:text-red-800 transition p-2 rounded-lg hover:bg-red-50"
                                       title="Delete Product">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Results Count -->
            <div class="mt-6 text-sm text-gray-600 flex justify-between items-center">
                <div>
                    <span class="font-semibold"><?php echo count($products); ?></span> product<?php echo count($products) !== 1 ? 's' : ''; ?> found
                </div>
                <div class="flex gap-4">
                    <button onclick="window.print()" 
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition flex items-center gap-2">
                        <i class="fas fa-print"></i> Print List
                    </button>
                    <a href="export_products.php" 
                       class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                        <i class="fas fa-file-export"></i> Export
                    </a>
                </div>
            </div>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <!-- Edit Product Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="text-xl font-bold">✏️ Edit Product</h3>
        <button class="close-btn" onclick="closeModal()">&times;</button>
      </div>
      <div class="modal-body">
        <form id="editProductForm" method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="product_id" id="editProductId">
            <input type="hidden" name="update_product" value="1">
            <input type="hidden" name="current_image" id="currentImage">

            <!-- Current Image Preview -->
            <div class="mb-6 text-center">
                <img id="currentImagePreview" src="" alt="Current Image" class="image-preview mx-auto mb-4">
                <div id="noImageMessage" class="text-gray-500 text-sm">No image uploaded</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Product Name -->
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-tag"></i>
                    </div>
                    <input type="text" name="name" id="editName" required
                           class="form-input" placeholder="Product Name">
                    <label class="text-sm text-gray-500 mt-1 block">Product Name *</label>
                </div>
                
                <!-- SKU -->
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-barcode"></i>
                    </div>
                    <input type="text" name="sku" id="editSku"
                           class="form-input" placeholder="SKU / Item Code">
                    <label class="text-sm text-gray-500 mt-1 block">SKU / Item Code</label>
                </div>

                <!-- Category -->
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-folder"></i>
                    </div>
                    <input type="text" name="category" id="editCategory" list="categories"
                           class="form-input" placeholder="Category">
                    <datalist id="categories">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <label class="text-sm text-gray-500 mt-1 block">Category</label>
                </div>
                
                <!-- Stock Quantity -->
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <input type="number" min="0" name="stock_quantity" id="editStockQuantity"
                           class="form-input" placeholder="Stock Quantity">
                    <label class="text-sm text-gray-500 mt-1 block">Current Stock Quantity</label>
                </div>

                <!-- Cost Price -->
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-money-bill"></i>
                    </div>
                    <input type="number" step="0.01" min="0" name="cost_price" id="editCostPrice" required
                           class="form-input" placeholder="Cost Price">
                    <label class="text-sm text-gray-500 mt-1 block">Cost Price (PKR) *</label>
                </div>
                
                <!-- Selling Price -->
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <input type="number" step="0.01" min="0" name="selling_price" id="editSellingPrice" required
                           class="form-input" placeholder="Selling Price">
                    <label class="text-sm text-gray-500 mt-1 block">Selling Price (PKR) *</label>
                </div>
            </div>
            
            <!-- Image Upload -->
            <div class="border-t pt-6 mt-6">
                <h3 class="text-lg font-semibold mb-3 text-gray-800">Product Image</h3>
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-image"></i>
                    </div>
                    <input type="file" name="product_image" id="editProductImage" accept="image/*"
                           class="form-input" onchange="previewImage(this)">
                    <label class="text-sm text-gray-500 mt-1 block">Upload new image (Max 2MB, JPG/PNG)</label>
                </div>
                <img id="imagePreview" class="image-preview mt-4 mx-auto" alt="Image Preview">
            </div>
            
            <!-- Description -->
            <div class="input-group">
                <div class="input-icon">
                    <i class="fas fa-align-left"></i>
                </div>
                <textarea name="description" id="editDescription" rows="3"
                          class="form-input resize-none" placeholder="Product Description"></textarea>
                <label class="text-sm text-gray-500 mt-1 block">Description</label>
            </div>

            <!-- Inventory Settings -->
            <div class="border-t pt-6 mt-6">
                <h3 class="text-lg font-semibold mb-3 text-gray-800">Inventory Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Alert Quantity -->
                    <div class="input-group">
                        <div class="input-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <input type="number" min="0" name="alert_quantity" id="editAlertQuantity"
                               class="form-input" placeholder="Low Stock Alert">
                        <label class="text-sm text-gray-500 mt-1 block">Low Stock Alert Quantity</label>
                    </div>
                    
                    <!-- Service Product Checkbox -->
                    <div class="checkbox-container">
                        <input id="editIsServiceProduct" name="is_service_product" type="checkbox" value="1"
                               class="h-4 w-4 text-indigo-600 border-gray-300 rounded"
                               onchange="toggleStockField()">
                        <label for="editIsServiceProduct" class="text-sm font-medium text-gray-700">
                            Is a Service Product (Don't track inventory)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-between items-center pt-6 border-t mt-6">
                <button type="button" onclick="closeModal()"
                        class="px-6 py-3 bg-gray-300 text-gray-700 font-semibold rounded-md 
                        hover:bg-gray-400 transition duration-150 ease-in-out flex items-center gap-2">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" id="submitButton"
                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-md 
                        shadow-md hover:from-blue-700 hover:to-indigo-700 focus:outline-none transition duration-150 ease-in-out flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
      </div>
    </div>
  </div>

<script>
let currentProductId = null;

// Open Edit Modal
function openEditModal(productId) {
    currentProductId = productId;
    
    // Show loading state
    $('#submitButton').html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
    
    // Fetch product data via AJAX
    $.ajax({
        url: 'ajax_get_product.php',
        type: 'GET',
        data: { id: productId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate form fields
                $('#editProductId').val(response.product.product_id);
                $('#editName').val(response.product.name);
                $('#editSku').val(response.product.sku);
                $('#editCategory').val(response.product.category);
                $('#editStockQuantity').val(response.product.stock_quantity);
                $('#editCostPrice').val(response.product.cost_price);
                $('#editSellingPrice').val(response.product.selling_price);
                $('#editDescription').val(response.product.description);
                $('#editAlertQuantity').val(response.product.alert_quantity);
                $('#editIsServiceProduct').prop('checked', response.product.is_service_product == 1);
                $('#currentImage').val(response.product.image_url);
                
                // Handle image preview
                if (response.product.image_url) {
                    $('#currentImagePreview').show().attr('src', response.product.image_url);
                    $('#noImageMessage').hide();
                } else {
                    $('#currentImagePreview').hide();
                    $('#noImageMessage').show();
                }
                
                // Update modal title
                $('.modal-header h3').text('✏️ Edit Product: ' + response.product.name);
                
                // Show modal
                $('#editModal').show();
                
                // Toggle stock field
                toggleStockField();
            } else {
                showMessage('Error loading product data: ' + response.error, 'error');
            }
            $('#submitButton').html('<i class="fas fa-save"></i> Save Changes').prop('disabled', false);
        },
        error: function(xhr, status, error) {
            showMessage('Network error. Please try again. ' + error, 'error');
            $('#submitButton').html('<i class="fas fa-save"></i> Save Changes').prop('disabled', false);
        }
    });
}

// Close Modal
function closeModal() {
    $('#editModal').hide();
    currentProductId = null;
    $('#editProductForm')[0].reset();
    $('#imagePreview').hide();
    $('#currentImagePreview').hide();
    $('#noImageMessage').show();
}

// Toggle stock field based on service product checkbox
function toggleStockField() {
    const isService = $('#editIsServiceProduct').is(':checked');
    const stockField = $('#editStockQuantity');
    
    if (isService) {
        stockField.prop('disabled', true);
        stockField.val('0');
        stockField.addClass('opacity-50 cursor-not-allowed');
    } else {
        stockField.prop('disabled', false);
        stockField.removeClass('opacity-50 cursor-not-allowed');
    }
}

// Preview uploaded image
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Handle form submission
$(document).ready(function() {
    $('#editProductForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = $('#submitButton');
        
        // Show loading state
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        
        $.ajax({
            url: 'ajax_update_product.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update the table row with new data
                    updateProductRow(response.product);
                    
                    // Show success message
                    showMessage('Product updated successfully!', 'success');
                    
                    // Close modal
                    setTimeout(() => {
                        closeModal();
                        submitBtn.html('<i class="fas fa-save"></i> Save Changes').prop('disabled', false);
                    }, 1500);
                } else {
                    showMessage('Error updating product: ' + response.error, 'error');
                    submitBtn.html('<i class="fas fa-save"></i> Save Changes').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                showMessage('Network error. Please try again. ' + error, 'error');
                submitBtn.html('<i class="fas fa-save"></i> Save Changes').prop('disabled', false);
            }
        });
    });
});

// Update product row in table
function updateProductRow(product) {
    const row = $('#product-row-' + product.product_id);
    
    // Update image
    const imageCell = row.find('td:first-child');
    if (product.image_url) {
        imageCell.html(`<img src="${product.image_url}" alt="${product.name}" class="product-image">`);
    }
    
    // Update product details
    const detailsCell = row.find('td:nth-child(2)');
    detailsCell.html(`
        <div class="flex flex-col">
            <span class="text-sm font-semibold text-gray-900">${product.name}</span>
            <span class="text-xs text-gray-500 mt-1">SKU: ${product.sku || 'N/A'}</span>
            ${product.is_service_product == 1 ? 
                '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 mt-2">' +
                '<i class="fas fa-concierge-bell mr-1"></i> Service Product</span>' : ''}
        </div>
    `);
    
    // Update category
    row.find('td:nth-child(3)').html(`
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
            ${product.category || 'Uncategorized'}
        </span>
    `);
    
    // Update prices
    $('#cost-price-' + product.product_id).html(`<span class="font-medium">${parseFloat(product.cost_price).toFixed(2)}</span> PKR`);
    $('#selling-price-' + product.product_id).html(`<span class="font-bold">${parseFloat(product.selling_price).toFixed(2)}</span> PKR`);
    
    // Update stock display
    const stockCell = row.find('td:nth-child(6)');
    let stockHtml = '';
    if (product.is_service_product == 1) {
        stockHtml = '<span class="text-gray-400 text-sm">—</span>';
    } else if (product.stock_quantity <= 0) {
        stockHtml = '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-red-100 text-red-800">' +
                   '<i class="fas fa-exclamation-triangle mr-1"></i> Out of Stock</span>';
    } else if (product.alert_quantity && product.stock_quantity <= product.alert_quantity) {
        stockHtml = '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-yellow-100 text-yellow-800">' +
                   `<i class="fas fa-exclamation-circle mr-1"></i> ${product.stock_quantity}</span>`;
    } else {
        stockHtml = '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-green-100 text-green-800">' +
                   `<i class="fas fa-check-circle mr-1"></i> ${product.stock_quantity}</span>`;
    }
    stockCell.html(stockHtml);
    
    // Update row background
    if (product.stock_quantity <= 0 && product.is_service_product == 0) {
        row.removeClass('hover:bg-gray-50').addClass('bg-red-50 hover:bg-red-100');
    } else {
        row.removeClass('bg-red-50 hover:bg-red-100').addClass('hover:bg-gray-50');
    }
}

// Show notification message
function showMessage(text, type) {
    const messageDiv = $('#message');
    messageDiv.removeClass('hidden');
    messageDiv.removeClass('bg-green-100 border-green-400 text-green-700');
    messageDiv.removeClass('bg-red-100 border-red-400 text-red-700');
    
    if (type === 'success') {
        messageDiv.addClass('bg-green-100 border-green-400 text-green-700');
        messageDiv.html(`
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center gap-3">
                    <i class="fas fa-check-circle text-lg"></i>
                    <span>${text}</span>
                </div>
                <button onclick="$(this).parent().parent().addClass('hidden')" 
                        class="text-green-700 hover:text-green-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `);
    } else {
        messageDiv.addClass('bg-red-100 border-red-400 text-red-700');
        messageDiv.html(`
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-lg"></i>
                    <span>${text}</span>
                </div>
                <button onclick="$(this).parent().parent().addClass('hidden')" 
                        class="text-red-700 hover:text-red-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `);
    }
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        messageDiv.addClass('hidden');
    }, 5000);
}

// Close modal when clicking outside
$(document).on('click', function(event) {
    if ($(event.target).is('#editModal')) {
        closeModal();
    }
});

// Initialize categories datalist
function initCategories() {
    const categories = <?php echo json_encode($categories); ?>;
    const datalist = $('#categories');
    datalist.empty();
    categories.forEach(cat => {
        datalist.append(`<option value="${cat}">`);
    });
}

// Initialize on page load
$(function() {
    initCategories();
});
</script>

</body>
</html>