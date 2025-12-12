<?php
session_start();
include 'db.php';

$session_id = $_POST['session_id'] ?? 0;
$table_id = $_POST['table_id'] ?? 0;
$rate_type = $_POST['rate_type'] ?? 'Normal';

if (!$session_id || !$table_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Update rate type in session
$stmt = $conn->prepare("UPDATE snooker_sessions SET rate_type = ? WHERE session_id = ? AND table_id = ?");
$stmt->bind_param("sii", $rate_type, $session_id, $table_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Rate updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update rate']);
}

$stmt->close();
$conn->close();
?>