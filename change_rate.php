<?php
session_start();
include 'db.php'; // Includes your database connection

// Protect page (Optional, but recommended)
if (!isset($_SESSION['admin_name'])) {
    header("Location: index.php");
    exit;
}

// Check if all necessary POST data is present
if (isset($_POST['session_id'], $_POST['table_id'], $_POST['rate_type'])) {
    
    $session_id = $_POST['session_id'];
    $table_id = $_POST['table_id'];
    $new_rate_type = $_POST['rate_type'];
    
    // --- 1. Update the Rate Type in the Database ---
    $stmt = $conn->prepare("
        UPDATE snooker_sessions 
        SET rate_type = ? 
        WHERE session_id = ?
    ");
    
    // 'si' stands for string ($new_rate_type) and integer ($session_id)
    $stmt->bind_param("si", $new_rate_type, $session_id);
    
    if ($stmt->execute()) {
        // Success: Rate type updated.
        // --- 2. Redirect back to the Active Session View ---
        header("Location: table_view.php?table_id=" . $table_id . "&session_id=" . $session_id);
        exit;
    } else {
        // Handle database error
        die("Error updating rate type: " . $stmt->error);
    }
    
    $stmt->close();
    
} else {
    // If required POST data is missing, redirect to the dashboard
    header("Location: dashboard.php");
    exit;
}
?>