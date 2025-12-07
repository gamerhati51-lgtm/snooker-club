<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['admin_name'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$session_id = $_POST['session_id'] ?? 0;
$product_id = $_POST['product_id'] ?? 0;
$quantity = $_POST['quantity'] ?? 1;

// Validate input
if ($session_id <= 0 || $product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // 1. Get product details
    $stmt = $conn->prepare("SELECT name, selling_price, stock_quantity FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Product not found");
    }
    
    $product = $result->fetch_assoc();
    $item_name = $product['name'];
    $price_per_unit = $product['selling_price'];
    $stock_quantity = $product['stock_quantity'];
    $stmt->close();
    
    // 2. Check stock
    if ($stock_quantity < $quantity) {
        throw new Exception("Insufficient stock. Only $stock_quantity available.");
    }
    
    // 3. Insert item into session_items
    $stmt = $conn->prepare("
        INSERT INTO session_items (session_id, item_name, quantity, price_per_unit) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isid", $session_id, $item_name, $quantity, $price_per_unit);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to add item: " . $stmt->error);
    }
    $stmt->close();
    
    // 4. Update product stock
    $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
    $stmt->bind_param("ii", $quantity, $product_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update stock: " . $stmt->error);
    }
    $stmt->close();
    
    // 5. Get updated items list and total
    $stmt = $conn->prepare("
        SELECT item_name, quantity, price_per_unit, (quantity * price_per_unit) AS total_item_price
        FROM session_items 
        WHERE session_id = ?
    ");
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    
    $items_list = [];
    $new_items_total = 0;
    while ($item = $items_result->fetch_assoc()) {
        $items_list[] = $item;
        $new_items_total += (float)$item['total_item_price'];
    }
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'item_name' => $item_name,
        'new_items_total' => $new_items_total,
        'items_list' => $items_list,
        'message' => 'Item added successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>