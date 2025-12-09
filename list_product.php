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

// --- 2. Fetch All Products ---
$products = [];
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    </style>
</head>
<body class="bg-blue-100 font-sans">

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
                        <tr class="<?php echo ($product['stock_quantity'] <= 0 && $product['is_service_product'] == 0) ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50'; ?>" id="product-row-<?php echo $product['product_id']; ?>">
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
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700" id="cost-price-<?php echo $product['product_id']; ?>"><?php echo number_format($product['cost_price'], 2); ?> PKR</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-green-700" id="selling-price-<?php echo $product['product_id']; ?>"><?php echo number_format($product['selling_price'], 2); ?> PKR</td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-bold">
                                <?php 
                                    if ($product['is_service_product']) {
                                        echo '<span class="text-gray-400">N/A</span>';
                                    } elseif ($product['stock_quantity'] <= 0) {
                                        echo '<span class="text-red-600">Out of Stock</span>';
                                    } else {
                                        echo '<span id="stock-quantity-' . $product['product_id'] . '">' . (int)$product['stock_quantity'] . '</span>';
                                    }
                                ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium space-x-2">
                                <a href="view_product.php?id=<?php echo $product['product_id']; ?>" class="text-gray-600 hover:text-gray-900 mr-2">
                                    Details
                                </a>
                                <button onclick="openEditModal(<?php echo $product['product_id']; ?>)" 
                                        class="text-blue-600 hover:text-blue-800">
                                    Edit
                                </button>
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

  <!-- Edit Product Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="text-xl font-bold">✏️ Edit Product</h3>
        <button class="close-btn" onclick="closeModal()">&times;</button>
      </div>
      <div class="modal-body">
        <form id="editProductForm" method="POST" class="space-y-6">
            <input type="hidden" name="product_id" id="editProductId">
            <input type="hidden" name="update_product" value="1">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Product Name -->
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-tag"></i>
                    </div>
                    <input type="text" name="name" id="editName" required
                           class="form-input" placeholder="Product Name">
                    <label class="text-sm text-gray-500 mt-1 block">Product Name</label>
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
                    <input type="text" name="category" id="editCategory"
                           class="form-input" placeholder="Category">
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
                    <label class="text-sm text-gray-500 mt-1 block">Cost Price (PKR)</label>
                </div>
                
                <!-- Selling Price -->
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <input type="number" step="0.01" min="0" name="selling_price" id="editSellingPrice" required
                           class="form-input" placeholder="Selling Price">
                    <label class="text-sm text-gray-500 mt-1 block">Selling Price (PKR)</label>
                </div>
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
                        hover:bg-gray-400 transition duration-150 ease-in-out">
                    Cancel
                </button>
                <button type="submit"
                        class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-md 
                        shadow-md hover:bg-blue-700 focus:outline-none transition duration-150 ease-in-out">
                    💾 Save Changes
                </button>
            </div>
        </form>
      </div>
    </div>
  </div>

<script>
// Font Awesome Icons (if not already loaded)
if (!document.querySelector('link[href*="font-awesome"]')) {
    const faLink = document.createElement('link');
    faLink.rel = 'stylesheet';
    faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
    document.head.appendChild(faLink);
}

let currentProductId = null;

// Open Edit Modal
function openEditModal(productId) {
    currentProductId = productId;
    
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
                
                // Update modal title
                $('.modal-header h3').text('✏️ Edit Product: ' + response.product.name);
                
                // Show modal
                $('#editModal').show();
                
                // Toggle stock field based on service product status
                toggleStockField();
            } else {
                showMessage('Error loading product data: ' + response.error, 'error');
            }
        },
        error: function() {
            showMessage('Network error. Please try again.', 'error');
        }
    });
}

// Close Modal
function closeModal() {
    $('#editModal').hide();
    currentProductId = null;
    $('#editProductForm')[0].reset();
}

// Toggle stock field based on service product checkbox
function toggleStockField() {
    const isService = $('#editIsServiceProduct').is(':checked');
    const stockField = $('#editStockQuantity');
    
    if (isService) {
        stockField.prop('disabled', true);
        stockField.val('0');
    } else {
        stockField.prop('disabled', false);
    }
}

// Close modal when clicking outside
$(document).click(function(event) {
    if ($(event.target).is('#editModal')) {
        closeModal();
    }
});

// Handle form submission
$(document).ready(function() {
    $('#editProductForm').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'ajax_update_product.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update the table row with new data
                    updateProductRow(response.product);
                    
                    // Show success message
                    showMessage('Product updated successfully!', 'success');
                    
                    // Close modal
                    closeModal();
                } else {
                    showMessage('Error updating product: ' + response.error, 'error');
                }
            },
            error: function() {
                showMessage('Network error. Please try again.', 'error');
            }
        });
    });
});

// Update product row in table
function updateProductRow(product) {
    // Update stock quantity display
    const stockElement = $('#stock-quantity-' + product.product_id);
    if (product.is_service_product == 1) {
        stockElement.html('<span class="text-gray-400">N/A</span>');
    } else if (product.stock_quantity <= 0) {
        stockElement.html('<span class="text-red-600">Out of Stock</span>');
    } else {
        stockElement.text(product.stock_quantity);
    }
    
    // Update price displays
    $('#cost-price-' + product.product_id).text(parseFloat(product.cost_price).toFixed(2) + ' PKR');
    $('#selling-price-' + product.product_id).text(parseFloat(product.selling_price).toFixed(2) + ' PKR');
    
    // Update service badge if needed
    const productNameCell = $('#product-row-' + product.product_id).find('td:nth-child(2)');
    let currentHtml = productNameCell.html();
    
    // Remove existing service badge
    currentHtml = currentHtml.replace(/<span[^>]*class="[^"]*bg-indigo-100[^"]*"[^>]*>Service<\/span>/, '');
    
    // Add service badge if needed
    if (product.is_service_product == 1) {
        currentHtml += '<span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">Service</span>';
    }
    
    productNameCell.html(currentHtml);
    
    // Update row background if out of stock
    const row = $('#product-row-' + product.product_id);
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
        messageDiv.html('<div class="flex items-center gap-2"><i class="fas fa-check-circle"></i><span>' + text + '</span></div>');
    } else {
        messageDiv.addClass('bg-red-100 border-red-400 text-red-700');
        messageDiv.html('<div class="flex items-center gap-2"><i class="fas fa-exclamation-circle"></i><span>' + text + '</span></div>');
    }
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        messageDiv.addClass('hidden');
    }, 3000);
}
</script>

</body>
</html>