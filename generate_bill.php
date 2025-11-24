<?php
session_start();
include 'db.php'; // Ensure this file establishes your database connection ($conn)

// --- 1. Get IDs and Current Time ---
$session_id = $_POST['session_id'] ?? null;
$table_id = $_POST['table_id'] ?? null;
$end_time = date('Y-m-d H:i:s');
$message = "";

if (!$session_id || !$table_id) {
    die("Error: Session or Table ID is missing.");
}

// --- 2. Calculation Function (Re-used from table_view.php) ---
// We use the same calculation function but ensure we calculate based on the fixed $end_time
function calculate_table_charge($start_time, $rate_type, $rate_per_hour, $century_rate, $end_time) {
    
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $interval = $start->diff($end);
    
    // Calculate total duration in seconds from the DateInterval properties
    // This correction handles the fatal error from previous steps
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
    
    // Round the final charge for billing
    return round($table_charge, 2); 
}

// --- 3. Fetch All Session Data (Start time, rates, table name) ---
$stmt = $conn->prepare("
    SELECT 
        s.start_time, s.rate_type, 
        st.table_name, st.rate_per_hour, st.century_rate
    FROM 
        snooker_sessions s
    JOIN 
        snooker_tables st ON s.id = st.id
    WHERE 
        s.session_id = ?
");
$stmt->bind_param("i", $session_id);
$stmt->execute();
$session_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$session_data) {
    die("Error: Session not found.");
}

$start_time_db = $session_data['start_time'];
$rate_per_hour = (float)$session_data['rate_per_hour'];
$century_rate = (float)$session_data['century_rate'];

// --- 4. Final Table Charge Calculation ---
$table_charge = calculate_table_charge(
    $start_time_db, 
    $session_data['rate_type'], 
    $rate_per_hour, 
    $century_rate, 
    $end_time
);

// --- 5. Fetch Items and Calculate Items Total ---
$stmt_items = $conn->prepare("
    SELECT 
        item_name, quantity, price_per_unit, (quantity * price_per_unit) AS total_item_price
    FROM 
        session_items
    WHERE 
        session_id = ?
");
$stmt_items->bind_param("i", $session_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

$items_total = 0.00;
$items_list = [];
while ($item = $items_result->fetch_assoc()) {
    $items_list[] = $item;
    $items_total += (float)$item['total_item_price'];
}
$stmt_items->close();

$final_total = $table_charge + $items_total;

// --- 6. Final Database Updates (Transaction) ---

// a. Update snooker_sessions record (Set end time, cost, and status)
$stmt_session_update = $conn->prepare("
    UPDATE snooker_sessions 
    SET end_time = ?, session_cost = ?, status = 'Completed' 
    WHERE session_id = ?
");
$stmt_session_update->bind_param("sdi", $end_time, $final_total, $session_id);

// b. Update snooker_tables status (Set table back to 'Free')
$stmt_table_update = $conn->prepare("
    UPDATE snooker_tables 
    SET status = 'Free' 
    WHERE id = ?
");
$stmt_table_update->bind_param("i", $table_id);

// Execute both updates
if ($stmt_session_update->execute() && $stmt_table_update->execute()) {
    $message = "Bill generated and table freed successfully!";
} else {
    $message = "Error finalizing bill or freeing table.";
}

$stmt_session_update->close();
$stmt_table_update->close();

// Calculate total duration for display
$start_time_obj = new DateTime($start_time_db);
$end_time_obj = new DateTime($end_time);
$interval_display = $start_time_obj->diff($end_time_obj);
$duration_display = $interval_display->format('%H hours, %I minutes, %S seconds');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Snooker Bill - <?php echo htmlspecialchars($session_data['table_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen"> 
    
    <div class="flex">

        <?php include 'layout/sidebar.php'; ?>

        <div class="flex-1 p-8">
    <div class="max-w-xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <h1 class="text-3xl font-bold mb-2 text-gray-800">
            🧾 Final Bill
        </h1>
        <p class="text-sm text-gray-500 border-b pb-4 mb-4">
            Transaction ID: #<?php echo $session_id; ?>
        </p>

        <div class="mb-4 p-3 bg-green-100 text-green-700 border border-green-300 rounded">
            🎉 <?php echo htmlspecialchars($message); ?>
        </div>

        <h2 class="text-xl font-semibold mb-3">Session Details:</h2>
        <div class="space-y-1 mb-6 text-gray-700">
            <p><strong>Table Name:</strong> <?php echo htmlspecialchars($session_data['table_name']); ?></p>
            <p><strong>Start Time:</strong> <?php echo date('g:i:s A', strtotime($start_time_db)); ?></p>
            <p><strong>End Time:</strong> <?php echo date('g:i:s A', strtotime($end_time)); ?></p>
            <p><strong>Total Duration:</strong> <?php echo $duration_display; ?></p>
            <p><strong>Rate Used:</strong> <?php echo $session_data['rate_type']; ?> (<?php echo $session_data['rate_type'] == 'Normal' ? $rate_per_hour . ' PKR/hr' : $century_rate . ' PKR/min'; ?>)</p>
        </div>

        <h2 class="text-xl font-semibold mb-3 border-t pt-4">Bill Breakdown:</h2>
        <div class="space-y-2 mb-6">
            
            <div class="flex justify-between text-lg font-medium">
                <span>Table Charge:</span>
                <span class="text-gray-700"><?php echo number_format($table_charge, 2); ?> PKR</span>
            </div>

            <div class="flex justify-between text-lg font-medium border-b pb-2">
                <span>Items & Services:</span>
                <span class="text-gray-700"><?php echo number_format($items_total, 2); ?> PKR</span>
            </div>
            
            <ul class="list-disc ml-6 space-y-1 text-sm text-gray-600">
                <?php if (empty($items_list)): ?>
                    <li>(No additional items sold)</li>
                <?php else: ?>
                    <?php foreach ($items_list as $item): ?>
                        <li class="flex justify-between pr-4">
                            <span>- <?php echo htmlspecialchars($item['item_name']); ?> x <?php echo $item['quantity']; ?> @ <?php echo number_format($item['price_per_unit'], 2); ?>/unit</span>
                            <span class="font-normal"><?php echo number_format($item['total_item_price'], 2); ?> PKR</span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        
        <hr class="my-4">

        <div class="text-3xl font-extrabold flex justify-between pt-2">
            <span>GRAND TOTAL:</span>
            <span class="text-green-700"><?php echo number_format($final_total, 2); ?> PKR</span>
        </div>

        <div class="mt-8 text-center">
            <a href="admin.php"
               class="bg-blue-600 text-white px-6 py-3 rounded-lg text-lg hover:bg-blue-700 transition">
                Return to Dashboard
            </a>
        </div>
    </div>
</body>
</html>