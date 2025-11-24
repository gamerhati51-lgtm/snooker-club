<?php
session_start();
include 'db.php'; // Ensure this file establishes your database connection ($conn)

// --- 1. Security Check ---
// Assuming you have an admin name set upon successful login
if (!isset($_SESSION['admin_name'])) {
    header("Location: index.php");
    exit;
}

// --- 2. Process POST Request ---
// This script expects the 'table_id' from the button submission
if (isset($_POST['start_session']) && isset($_POST['table_id'])) {
    
    $table_id = $_POST['table_id'];
    $start_time = date('Y-m-d H:i:s');
    
    // --- 3. Insert New Session ---
    // Sets initial rate_type to 'Normal' and status to 'Active'.
    // NOTE: 'id' is used here because your snooker_sessions table references snooker_tables.id
    $stmt = $conn->prepare("
        INSERT INTO snooker_sessions (id, start_time, rate_type, status) 
        VALUES (?, ?, 'Normal', 'Active')
    ");
    // 'is' stands for integer ($table_id) and string ($start_time)
    $stmt->bind_param("is", $table_id, $start_time);
    
    if (!$stmt->execute()) {
        // Handle error if session creation fails
        die("Error starting session: " . $stmt->error);
    }
    
    // Get the ID of the newly created session, which is needed for the redirect
    $session_id = $conn->insert_id;
    $stmt->close();
    
    // --- 4. Update Table Status ---
    // Change the status of the physical table from 'Free' to 'Occupied'
    $stmt_update = $conn->prepare("
        UPDATE snooker_tables SET status = 'Occupied' WHERE id = ?
    ");
    $stmt_update->bind_param("i", $table_id);
    $stmt_update->execute();
    $stmt_update->close();
    
    // --- 5. Redirect to Active Session View ---
    // Redirect to the detailed view page, passing the necessary IDs
    header("Location: table_view.php?table_id=" . $table_id . "&session_id=" . $session_id);
    exit;
    
} else {
    // If accessed directly or without POST data, redirect to the dashboard/home page
    header("Location: dashboard.php");
    exit;
}
?>