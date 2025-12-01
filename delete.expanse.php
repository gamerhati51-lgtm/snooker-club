<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = "SAEED GUEST";
}

$id = $_GET['id'] ?? 0;
$id = (int)$id;

if($id > 0){
    $stmt = $conn->prepare("DELETE FROM expanses WHERE expanses_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['message'] = "✅ Expense deleted successfully!";
}

header("Location: list-expanse.php");
exit();
?>
