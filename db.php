<?php
// db.php
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";         // change if your MySQL root has a password
$DB_NAME = "club_snoker";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
