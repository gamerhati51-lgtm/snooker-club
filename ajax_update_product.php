<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Check if request is AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die(json_encode(['success' => false, 'error' => 'Direct access not allowed']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);

if (!$product_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid Product ID']);
    exit;
}

// Check if image_path column exists
$check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'image_path'");
$has_image_path = $check_column->num_rows > 0;

// Handle image upload
$image_path = isset($_POST['current_image']) ? $_POST['current_image'] : '';

if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $_FILES['product_image']['type'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (in_array($file_type, $allowed_types) && $_FILES['product_image']['size'] <= $max_size) {
        $upload_dir = 'uploads/products/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $filename = 'product_' . $product_id . '_' . time() . '.' . $file_extension;
        $destination = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $destination)) {
            $image_path = $destination;
            
            // Delete old image if exists
            if (isset($_POST['current_image']) && !empty($_POST['current_image']) && file_exists($_POST['current_image'])) {
                unlink($_POST['current_image']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid image file or file too large (max 2MB)']);
        exit;
    }
}

// Prepare update data
$name = trim($_POST['name']);
$sku = isset($_POST['sku']) ? trim($_POST['sku']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';
$cost_price = floatval($_POST['cost_price']);
$selling_price = floatval($_POST['selling_price']);
$stock_quantity = intval($_POST['stock_quantity']);
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$alert_quantity = isset($_POST['alert_quantity']) && !empty($_POST['alert_quantity']) ? intval($_POST['alert_quantity']) : null;
$is_service_product = isset($_POST['is_service_product']) ? 1 : 0;

// Validate required fields
if (empty($name) || $cost_price < 0 || $selling_price < 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

// If it's a service product, set stock to 0
if ($is_service_product) {
    $stock_quantity = 0;
}

// Build dynamic SQL query
$sql = "UPDATE products SET
        name = ?,
        sku = ?,
        category = ?,
        cost_price = ?,
        selling_price = ?,
        stock_quantity = ?,
        description = ?,
        alert_quantity = ?,
        is_service_product = ?";

if ($has_image_path) {
    $sql .= ", image_path = ?";
}

$sql .= " WHERE product_id = ?";

$stmt = $conn->prepare($sql);

if ($has_image_path) {
    $stmt->bind_param(
        "sssddiisisi",
        $name,
        $sku,
        $category,
        $cost_price,
        $selling_price,
        $stock_quantity,
        $description,
        $alert_quantity,
        $is_service_product,
        $image_path,
        $product_id
    );
} else {
    $stmt->bind_param(
        "sssddiisii",
        $name,
        $sku,
        $category,
        $cost_price,
        $selling_price,
        $stock_quantity,
        $description,
        $alert_quantity,
        $is_service_product,
        $product_id
    );
}

if ($stmt->execute()) {
    // Fetch updated product data
    $select_sql = "SELECT product_id, sku, name, cost_price, selling_price, stock_quantity, 
                          category, is_service_product, description, alert_quantity";
    
    if ($has_image_path) {
        $select_sql .= ", image_path";
    }
    
    $select_sql .= " FROM products WHERE product_id = ?";
    
    $select_stmt = $conn->prepare($select_sql);
    $select_stmt->bind_param("i", $product_id);
    $select_stmt->execute();
    $result = $select_stmt->get_result();
    $updated_product = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Product updated successfully',
        'product' => $updated_product
    ]);
    
    $select_stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>