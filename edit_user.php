<?php
session_start();
include 'db.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_name'])){
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Check if it's an AJAX request
if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    if($_SERVER['REQUEST_METHOD'] == "POST"){
        $id = intval($_POST['id']);
        $status = $_POST['status'];
        
        // Validate status
        $allowed_statuses = ['Active', 'Inactive'];
        if(!in_array($status, $allowed_statuses)) {
            echo json_encode(['success' => false, 'error' => 'Invalid status value']);
            exit;
        }
        
        // Update user
        $update = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $update->bind_param("si", $status, $id);
        
        if($update->execute()){
            echo json_encode(['success' => true, 'newStatus' => $status]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>