<?php
session_start();
header('Content-Type: application/json');

include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['admin_name'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_POST['action'] ?? '';
$session_id = $_POST['session_id'] ?? 0;

if ($session_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid session']);
    exit;
}

if ($action === 'add') {
    // ADD ITEM
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;
    
    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }
    
    // Get product details
    $stmt = $conn->prepare("SELECT name, selling_price FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Insert item
    $stmt = $conn->prepare("
        INSERT INTO session_items (session_id, item_name, quantity, price_per_unit) 
        VALUES (?, ?, ?, ?)
    ");
    $item_name = $product['name'];
    $price_per_unit = $product['selling_price'];
    $stmt->bind_param("isid", $session_id, $item_name, $quantity, $price_per_unit);
    
    if ($stmt->execute()) {
        // Get updated items list
        $items_data = getSessionItems($conn, $session_id);
        
        echo json_encode([
            'success' => true,
            'item_name' => $item_name,
            'new_items_total' => $items_data['total'],
            'items_list' => $items_data['items']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add item']);
    }
    $stmt->close();
    
} elseif ($action === 'remove') {
    // REMOVE ITEM
    $item_id = $_POST['item_id'] ?? 0;
    
    if ($item_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
        exit;
    }
    
    // Delete item
    $stmt = $conn->prepare("DELETE FROM session_items WHERE session_item_id = ? AND session_id = ?");
    $stmt->bind_param("ii", $item_id, $session_id);
    
    if ($stmt->execute()) {
        // Get updated items list
        $items_data = getSessionItems($conn, $session_id);
        
        echo json_encode([
            'success' => true,
            'new_items_total' => $items_data['total'],
            'items_list' => $items_data['items']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
    }
    $stmt->close();
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Helper function to get session items
function getSessionItems($conn, $session_id) {
    $stmt = $conn->prepare("
        SELECT 
            session_item_id,
            item_name, 
            quantity, 
            price_per_unit, 
            (quantity * price_per_unit) AS total_item_price
        FROM 
            session_items 
        WHERE 
            session_id = ?
        ORDER BY 
            session_item_id ASC
    ");
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    $total = 0.00;
    while ($item = $result->fetch_assoc()) {
        $items[] = $item;
        $total += (float)$item['total_item_price'];
    }
    $stmt->close();
    
    return ['items' => $items, 'total' => $total];
}
?>