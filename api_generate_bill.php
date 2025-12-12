<?php
session_start();
include 'db.php';

$session_id = $_POST['session_id'] ?? 0;
$table_id = $_POST['table_id'] ?? 0;

if (!$session_id || !$table_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // 1. Get session and table data with century mode minutes
    $stmt = $conn->prepare("
        SELECT 
            s.*, 
            st.table_name, 
            st.rate_per_hour, 
            st.century_rate,
            COALESCE(s.century_mode_minutes, 0) as century_minutes
        FROM snooker_sessions s
        JOIN snooker_tables st ON s.table_id = st.id
        WHERE s.session_id = ? AND s.status = 'Active'
    ");
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $session = $stmt->get_result()->fetch_assoc();
    
    if (!$session) {
        throw new Exception('Active session not found');
    }
    
    // 2. Calculate end time and duration
    $end_time = date('Y-m-d H:i:s');
    $start_time = $session['start_time'];
    
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $interval = $start->diff($end);
    $total_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
    
    // 3. Calculate table charge based on rate type
    $booking_duration = $session['booking_duration'] ?? 1;
    $rate_type = $session['rate_type'];
    $century_minutes = $session['century_minutes'] ?? 0;
    
    if ($rate_type == 'Normal') {
        // Normal rate: charge per hour (round up)
        $hours_played = ceil($total_minutes / 60);
        $table_charge = $hours_played * $session['rate_per_hour'];
    } else {
        // Century rate: base hours + per-minute charge
        $base_hours = $booking_duration;
        $base_charge = $base_hours * $session['rate_per_hour'];
        
        // Calculate additional minutes beyond base hours
        $minutes_played = max(0, $total_minutes - ($base_hours * 60));
        $additional_minutes = max($century_minutes, $minutes_played);
        $additional_charge = $additional_minutes * $session['century_rate'];
        
        $table_charge = $base_charge + $additional_charge;
    }
    
    // 4. Get items total
    $items_stmt = $conn->prepare("
        SELECT 
            item_name, 
            quantity, 
            price_per_unit, 
            (quantity * price_per_unit) AS total_item_price
        FROM session_items 
        WHERE session_id = ?
    ");
    $items_stmt->bind_param("i", $session_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $items_list = [];
    $items_total = 0.00;
    while ($item = $items_result->fetch_assoc()) {
        $items_list[] = $item;
        $items_total += (float)$item['total_item_price'];
    }
    $items_stmt->close();
    
    // 5. Calculate final total
    $final_total = $table_charge + $items_total;
    
    // 6. Update session as completed
    $update_stmt = $conn->prepare("
        UPDATE snooker_sessions 
        SET 
            end_time = ?, 
            total_time_minutes = ?,
            session_cost = ?,
            final_amount = ?,
            status = 'Completed'
        WHERE session_id = ?
    ");
    $update_stmt->bind_param("siddi", 
        $end_time, 
        $total_minutes, 
        $items_total, 
        $final_total, 
        $session_id
    );
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update session: ' . $conn->error);
    }
    $update_stmt->close();
    
    // 7. Free up the table
    $table_stmt = $conn->prepare("UPDATE snooker_tables SET status = 'Free' WHERE id = ?");
    $table_stmt->bind_param("i", $table_id);
    
    if (!$table_stmt->execute()) {
        throw new Exception('Failed to free table: ' . $conn->error);
    }
    $table_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // 8. Return bill data
    echo json_encode([
        'success' => true,
        'message' => 'Bill generated successfully!',
        'bill_data' => [
            'session_id' => $session_id,
            'table_name' => $session['table_name'],
            'start_time' => $start_time,
            'end_time' => $end_time,
            'total_minutes' => $total_minutes,
            'rate_type' => $rate_type,
            'table_charge' => $table_charge,
            'items_total' => $items_total,
            'final_total' => $final_total,
            'items_list' => $items_list,
            'century_minutes' => $century_minutes
        ],
        'redirect' => true
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>