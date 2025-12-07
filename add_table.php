<?php
session_start();
include 'db.php';

// Protect page
if (!isset($_SESSION['admin_name'])) {
    echo "❌ Unauthorized access!";
    exit;
}

$message = "";

if (isset($_POST['add_table'])) {
    $table_name = trim($_POST['table_name']);
    $rate_per_hour = trim($_POST['rate_hour']);
    $century_rate = trim($_POST['century_rate']);

    $stmt = $conn->prepare("
        INSERT INTO snooker_tables (table_name, rate_per_hour, century_rate, status) 
        VALUES (?, ?, ?, 'Free')
    ");
    $stmt->bind_param("sdd", $table_name, $rate_per_hour, $century_rate);

    if ($stmt->execute()) {
        $message = "🎉 Table added successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
    
    // Return only the message
    echo $message;
    exit;
}

// If accessed directly without POST, show nothing or redirect
echo "Invalid request";
?>