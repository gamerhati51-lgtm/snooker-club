<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Check if admin is logged in
if(!isset($_SESSION['admin_name'])){
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Check if it's an AJAX request
if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    if(isset($_GET['id'])) {
        $product_id = intval($_GET['id']);
        
        // Fetch product data
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
        
        if($product) {
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Product not found']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Product ID not provided']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>