<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$item_id = $_POST['item_id'] ?? 0;
$session_id = $_POST['session_id'] ?? 0;

if (!$item_id || !$session_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // 1. Get item details to restore stock - FIXED: using session_item_id instead of id
    $stmt = $conn->prepare("
        SELECT si.*, p.product_id, p.stock_quantity 
        FROM sale_items si
        LEFT JOIN products p ON si.product_id = p.product_id
        WHERE si.sale_item_id = ? AND si.session_id = ?
    ");
    $stmt->bind_param("ii", $item_id, $session_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$item) {
        throw new Exception('Item not found');
    }
    
    // 2. Restore product stock if product exists
    if ($item['product_id']) {
        $restore_stmt = $conn->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity + ? 
            WHERE product_id = ?
        ");
        $restore_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        $restore_stmt->execute();
        $restore_stmt->close();
    }
    
    // 3. Remove the item - FIXED: using session_item_id
    $delete_stmt = $conn->prepare("DELETE FROM _items WHERE session_item_id = ?");
    $delete_stmt->bind_param("i", $item_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // 4. Calculate new items total
    $total_stmt = $conn->prepare("
        SELECT SUM(quantity * price_per_unit) as total 
        FROM session_items 
        WHERE session_id = ?
    ");
    $total_stmt->bind_param("i", $session_id);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $new_total = $total_result->fetch_assoc()['total'] ?? 0;
    $total_stmt->close();
    
    // 5. Get updated items list - FIXED: using session_item_id and correct WHERE clause
    $items_stmt = $conn->prepare("
        SELECT session_item_id, item_name, quantity, price_per_unit, 
               (quantity * price_per_unit) AS total_item_price
        FROM session_items 
        WHERE session_id = ?
        ORDER BY session_item_id DESC
    ");
    $items_stmt->bind_param("i", $session_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $items_list = [];
    while ($row = $items_result->fetch_assoc()) {
        $items_list[] = $row;
    }
    $items_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Item removed successfully',
        'new_items_total' => (float)$new_total,
        'items_list' => $items_list
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>