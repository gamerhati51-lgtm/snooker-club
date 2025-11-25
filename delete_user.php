<?php
session_start();
include 'db.php';

// Must be logged in
if (!isset($_SESSION['admin_name'])) {
    header("Location: index.php");
    exit;
}

// Check if ID exists
if (!isset($_GET['id'])) {
    header("Location: user.php");
    exit;
}

$user_id = intval($_GET['id']);

// Delete user safely
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    header("Location: user.php?message=deleted");
    exit;
} else {
    echo "Error deleting user.";
}
?>
