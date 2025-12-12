<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$session_id = $_POST['session_id'] ?? 0;
$warning_shown = $_POST['warning_shown'] ?? 0;

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $stmt = $conn->prepare("
        UPDATE snooker_sessions 
        SET century_warning_shown = ? 
        WHERE session_id = ? AND status = 'Active'
    ");
    $stmt->bind_param("ii", $warning_shown, $session_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update warning']);
    }
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>