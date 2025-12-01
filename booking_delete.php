<?php
include 'db.php';

$id = $_POST['id'];

$stmt = $conn->prepare("DELETE FROM snooker_bookings WHERE booking_id=?");
$stmt->bind_param("i", $id);

echo $stmt->execute() ? "Booking deleted successfully" : "Failed to delete booking";
$stmt->close();
?>
