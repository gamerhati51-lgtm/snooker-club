<?php
session_start();
include 'db.php';

$session_id = $_POST['session_id'] ?? 0;
$product_id = $_POST['product_id'] ?? 0;
$quantity = $_POST['quantity'] ?? 1;

if (!$session_id || !$product_id || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$conn->begin_transaction();

try {
    // Get product
    $stmt = $conn->prepare("SELECT name, selling_price, stock_quantity FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    if ($product['stock_quantity'] < $quantity) {
        throw new Exception('Insufficient stock');
    }
    
    // Reduce stock
    $update_stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
    $update_stmt->bind_param("ii", $quantity, $product_id);
    $update_stmt->execute();
    
    // Add to session items
    $item_name = $product['name'];
    $price_per_unit = $product['selling_price'];
    
    $insert_stmt = $conn->prepare("INSERT INTO session_items (session_id, item_name, quantity, price_per_unit) VALUES (?, ?, ?, ?)");
    $insert_stmt->bind_param("isid", $session_id, $item_name, $quantity, $price_per_unit);
    $insert_stmt->execute();
    
    // Get updated items list
    $items_stmt = $conn->prepare("SELECT item_name, quantity, price_per_unit, (quantity * price_per_unit) AS total_item_price FROM session_items WHERE session_id = ?");
    $items_stmt->bind_param("i", $session_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $items_list = [];
    $new_items_total = 0.00;
    while ($item = $items_result->fetch_assoc()) {
        $items_list[] = $item;
        $new_items_total += (float)$item['total_item_price'];
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Item added successfully',
        'items_list' => $items_list,
        'new_items_total' => $new_items_total
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>