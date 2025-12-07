<?php
session_start();
header('Content-Type: application/json');

include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['admin_name'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$session_id = $_POST['session_id'] ?? 0;
$item_id = $_POST['item_id'] ?? 0;

if ($session_id <= 0 || $item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Delete the item
$stmt = $conn->prepare("DELETE FROM session_items WHERE session_item_id = ? AND session_id = ?");
$stmt->bind_param("ii", $item_id, $session_id);

if ($stmt->execute()) {
    // Get updated items list
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
    
    echo json_encode([
        'success' => true,
        'new_items_total' => $total,
        'items_list' => $items
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
}
?>