<?php
// api_generate_bill.php
session_start();
include 'db.php';

// Get POST data
$session_id = $_POST['session_id'] ?? null;
$table_id = $_POST['table_id'] ?? null;
$end_time = date('Y-m-d H:i:s');

if (!$session_id || !$table_id) {
    echo json_encode(['success' => false, 'message' => 'Session or Table ID missing']);
    exit;
}

// --- Calculation function ---
function calculate_table_charge($start_time, $rate_type, $rate_per_hour, $century_rate, $end_time) {
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $interval = $start->diff($end);

    $duration_seconds = (
        $interval->y * 365 * 24 * 60 * 60 +
        $interval->m * 30 * 24 * 60 * 60 +
        $interval->d * 24 * 60 * 60 +
        $interval->h * 60 * 60 +
        $interval->i * 60 +
        $interval->s
    );

    if ($rate_type == 'Normal') {
        $duration_hours = $duration_seconds / 3600;
        $table_charge = $duration_hours * $rate_per_hour;
    } else {
        $duration_minutes = $duration_seconds / 60;
        $table_charge = $duration_minutes * $century_rate;
    }

    return round($table_charge, 2);
}

// --- Fetch session data ---
$stmt = $conn->prepare("
    SELECT s.start_time, s.rate_type, st.table_name, st.rate_per_hour, st.century_rate
    FROM snooker_sessions s
    JOIN snooker_tables st ON s.id = st.id
    WHERE s.session_id = ?
");
$stmt->bind_param("i", $session_id);
$stmt->execute();
$session_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$session_data) {
    echo json_encode(['success' => false, 'message' => 'Session not found']);
    exit;
}

// Calculate table charge
$table_charge = calculate_table_charge(
    $session_data['start_time'], 
    $session_data['rate_type'], 
    (float)$session_data['rate_per_hour'],
    (float)$session_data['century_rate'], 
    $end_time
);

// --- Fetch items ---
$stmt_items = $conn->prepare("
    SELECT item_name, quantity, price_per_unit, (quantity * price_per_unit) AS total_item_price
    FROM session_items
    WHERE session_id = ?
");
$stmt_items->bind_param("i", $session_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

$items_total = 0;
$items_list = [];
while ($item = $items_result->fetch_assoc()) {
    $items_list[] = $item;
    $items_total += (float)$item['total_item_price'];
}
$stmt_items->close();

$final_total = $table_charge + $items_total;

// --- Update database ---
$update_success = false;
try {
    $conn->begin_transaction();
    
    // Update session
    $stmt_session = $conn->prepare("
        UPDATE snooker_sessions 
        SET end_time = ?, session_cost = ?, status = 'Completed' 
        WHERE session_id = ?
    ");
    $stmt_session->bind_param("sdi", $end_time, $final_total, $session_id);
    $stmt_session->execute();
    $stmt_session->close();
    
    // Update table status
    $stmt_table = $conn->prepare("
        UPDATE snooker_tables 
        SET status = 'Free' 
        WHERE id = ?
    ");
    $stmt_table->bind_param("i", $table_id);
    $stmt_table->execute();
    $stmt_table->close();
    
    $conn->commit();
    $update_success = true;
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Prepare response
$response = [
    'success' => $update_success,
    'message' => $update_success ? 'Bill generated successfully!' : 'Error generating bill',
    'bill_data' => [
        'session_id' => $session_id,
        'table_name' => $session_data['table_name'],
        'start_time' => $session_data['start_time'],
        'end_time' => $end_time,
        'rate_type' => $session_data['rate_type'],
        'rate_per_hour' => $session_data['rate_per_hour'],
        'century_rate' => $session_data['century_rate'],
        'table_charge' => $table_charge,
        'items_total' => $items_total,
        'final_total' => $final_total,
        'items_list' => $items_list
    ]
];

header('Content-Type: application/json');
echo json_encode($response);
?>