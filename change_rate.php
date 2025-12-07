<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['admin_name'])) {
    header("Location: index.php");
    exit;
}

// Get POST data
$session_id = $_POST['session_id'] ?? 0;
$table_id = $_POST['table_id'] ?? 0;
$rate_type = $_POST['rate_type'] ?? 'Normal';

// Validate
if ($session_id <= 0 || $table_id <= 0) {
    die("Invalid session or table ID");
}

// Update rate type
$stmt = $conn->prepare("UPDATE snooker_sessions SET rate_type = ? WHERE session_id = ?");
$stmt->bind_param("si", $rate_type, $session_id);

if ($stmt->execute()) {
    // Redirect back to session page
    header("Location: table_view.php?table_id=$table_id&session_id=$session_id");
} else {
    echo "Error updating rate type: " . $stmt->error;
}
$stmt->close();
?>