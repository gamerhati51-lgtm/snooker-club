<?php
session_start();
include 'db.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_name'])){
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit;
}

// ------------------- DELETE -------------------
if(isset($_POST['delete_id'])){
    $id = intval($_POST['delete_id']);

    // Check if table has any bookings
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM snooker_bookings WHERE table_id=?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();

    if($count > 0){
        echo json_encode(['status'=>'error', 'message'=>'Cannot delete: table has existing bookings!']);
        exit;
    }

    // Safe to delete
    $stmt = $conn->prepare("DELETE FROM snooker_tables WHERE id=?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()){
        echo json_encode(['status' => 'success', 'message' => 'Table deleted successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: '.$stmt->error]);
    }
    $stmt->close();
    exit;
}

// ------------------- UPDATE -------------------
if(isset($_POST['id'], $_POST['table_name'], $_POST['rate_hour'], $_POST['century_rate'])){
    $id = intval($_POST['id']);
    $table_name = trim($_POST['table_name']);
    $rate_hour = floatval($_POST['rate_hour']);
    $century_rate = floatval($_POST['century_rate']);

    $stmt = $conn->prepare("UPDATE snooker_tables SET table_name=?, rate_per_hour=?, century_rate=? WHERE id=?");
    $stmt->bind_param("sddi", $table_name, $rate_hour, $century_rate, $id);

    if($stmt->execute()){
        echo json_encode(['status'=>'success','message'=>'Table updated successfully!']);
    } else {
        echo json_encode(['status'=>'error','message'=>$stmt->error]);
    }
    $stmt->close();
    exit;
}

// If no valid action
echo json_encode(['status'=>'error','message'=>'Invalid request']);
exit;
?>
