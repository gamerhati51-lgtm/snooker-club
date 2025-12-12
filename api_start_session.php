<?php
// api_start_session.php
session_start();
include 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_name'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Get POST data
$table_id = $_POST['table_id'] ?? 0;
$user_id = $_POST['user_id'] ?? 1; // Default user ID
$rate_type = $_POST['rate_type'] ?? 'Normal';
$booking_duration = $_POST['booking_duration'] ?? 1;

if (!$table_id) {
    echo json_encode(['success' => false, 'message' => 'Table ID is required']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // 1. Check if table exists and is free
    $table_stmt = $conn->prepare("SELECT * FROM snooker_tables WHERE id = ?");
    $table_stmt->bind_param("i", $table_id);
    $table_stmt->execute();
    $table_result = $table_stmt->get_result();
    $table = $table_result->fetch_assoc();
    
    if (!$table) {
        throw new Exception('Table not found');
    }
    
    // 2. Close any existing active session for this table (safety check)
    $close_stmt = $conn->prepare("
        UPDATE snooker_sessions 
        SET status = 'Completed',
            end_time = NOW()
        WHERE table_id = ? AND status = 'Active'
    ");
    $close_stmt->bind_param("i", $table_id);
    $close_stmt->execute();
    $close_stmt->close();
    
    // 3. Create new session
    $stmt = $conn->prepare("
        INSERT INTO snooker_sessions 
        (table_id, id, start_time, rate_type, status, booking_duration) 
        VALUES (?, ?, NOW(), ?, 'Active', ?)
    ");
    $stmt->bind_param("iisi", $table_id, $user_id, $rate_type, $booking_duration);
    $stmt->execute();
    
    $new_session_id = $conn->insert_id;
    
    // 4. Update table status to Occupied
    $update_stmt = $conn->prepare("UPDATE snooker_tables SET status = 'Occupied' WHERE id = ?");
    $update_stmt->bind_param("i", $table_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Return success with session ID
    echo json_encode([
        'success' => true,
        'message' => 'Session started successfully',
        'session_id' => $new_session_id,
        'table_id' => $table_id
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>