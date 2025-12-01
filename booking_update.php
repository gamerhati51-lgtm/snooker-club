<?php
include 'db.php';

$id = $_POST['id'];
$name = $_POST['customer_name'];
$date = $_POST['booking_date'];
$start = $_POST['start_time'];
$end = $_POST['end_time'];

$stmt = $conn->prepare("
    UPDATE snooker_bookings 
    SET customer_name=?, booking_date=?, start_time=?, end_time=? 
    WHERE booking_id=?
");

$stmt->bind_param("ssssi", $name, $date, $start, $end, $id);

echo $stmt->execute() 
    ? "Booking updated successfully" 
    : "Failed to update booking";

$stmt->close();
?>
