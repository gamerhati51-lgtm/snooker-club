<?php
include 'db.php';

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM snooker_bookings WHERE booking_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();
echo json_encode($data);
?>
