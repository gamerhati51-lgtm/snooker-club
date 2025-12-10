<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Check if request is AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die(json_encode(['success' => false, 'error' => 'Direct access not allowed']));
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Product ID is required']);
    exit;
}

$product_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if (!$product_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid Product ID']);
    exit;
}

// Check if image_path column exists
$check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'image_path'");
$has_image_path = $check_column->num_rows > 0;

// Build query dynamically
$sql = "SELECT product_id, sku, name, cost_price, selling_price, stock_quantity, 
               category, is_service_product, description, alert_quantity";

if ($has_image_path) {
    $sql .= ", image_path";
}

$sql .= " FROM products WHERE product_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'product' => $row]);
} else {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
}

$stmt->close();
$conn->close();
?>