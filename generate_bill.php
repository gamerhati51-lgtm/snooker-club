<?php
session_start();
include 'db.php'; // Database connection

// --- Get POST data ---
$session_id = $_POST['session_id'] ?? null;
$table_id = $_POST['table_id'] ?? null;
$end_time = date('Y-m-d H:i:s');
$message = "";

if (!$session_id || !$table_id) {
    die("Error: Session or Table ID is missing.");
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

// --- Fetch session and table data ---
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
    die("Error: Session not found.");
}

$start_time_db = $session_data['start_time'];
$rate_per_hour = (float)$session_data['rate_per_hour'];
$century_rate = (float)$session_data['century_rate'];

// --- Calculate table charge ---
$table_charge = calculate_table_charge(
    $start_time_db, 
    $session_data['rate_type'], 
    $rate_per_hour, 
    $century_rate, 
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
$stmt_session_update = $conn->prepare("
    UPDATE snooker_sessions 
    SET end_time = ?, session_cost = ?, status = 'Completed' 
    WHERE session_id = ?
");
$stmt_session_update->bind_param("sdi", $end_time, $final_total, $session_id);

$stmt_table_update = $conn->prepare("
    UPDATE snooker_tables 
    SET status = 'Free' 
    WHERE id = ?
");
$stmt_table_update->bind_param("i", $table_id);

if ($stmt_session_update->execute() && $stmt_table_update->execute()) {
    $message = "Bill generated and table freed successfully!";
} else {
    $message = "Error finalizing bill or freeing table.";
}

$stmt_session_update->close();
$stmt_table_update->close();

$start_time_obj = new DateTime($start_time_db);
$end_time_obj = new DateTime($end_time);
$interval_display = $start_time_obj->diff($end_time_obj);
$duration_display = $interval_display->format('%H hours, %I minutes, %S seconds');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Snooker Bill - <?php echo htmlspecialchars($session_data['table_name']); ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <style>
    @media print {
        body * { visibility: hidden; }           /* hide everything */
        #bill-container, #bill-container * { visibility: visible; } /* show bill */
        #bill-container { position: absolute; left: 0; top: 0; width: 100%; }
        .screen-only { display: none !important; } /* hide buttons and messages during print */
    }
</style>


    <!-- Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Main content wrapper -->
    <div class="flex-1 flex flex-col ml-0 lg:ml-64">

        <!-- Header -->
        <div class="fixed top-0 left-0 right-0 z-20">
            <?php include 'layout/header.php'; ?>
        </div>

        <!-- Bill content -->
        <main class="flex-1 mt-8 p-10 overflow-y-auto">
           <!-- Wrap your bill in this container -->
<div id="bill-container" class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg p-6">

    <h1 class="text-3xl font-bold mb-2 text-gray-800 text-center">🧾 Final Bill</h1>
    <p class="text-sm text-gray-500 border-b pb-4 mb-4 text-center">
        Transaction ID: #<?php echo $session_id; ?>
    </p>

    <!-- Success message -->
    <div class="mb-4 p-3 bg-green-100 text-green-700 border border-green-300 rounded screen-only">
        🎉 <?php echo htmlspecialchars($message); ?>
    </div>

    <!-- Session Details -->
    <h2 class="text-xl font-semibold mb-3">Session Details:</h2>
    <div class="space-y-1 mb-6 text-gray-700">
        <p><strong>Table Name:</strong> <?php echo htmlspecialchars($session_data['table_name']); ?></p>
        <p><strong>Start Time:</strong> <?php echo date('g:i:s A', strtotime($start_time_db)); ?></p>
        <p><strong>End Time:</strong> <?php echo date('g:i:s A', strtotime($end_time)); ?></p>
        <p><strong>Total Duration:</strong> <?php echo $duration_display; ?></p>
        <p><strong>Rate Used:</strong> <?php echo $session_data['rate_type']; ?> (<?php echo $session_data['rate_type']=='Normal'? $rate_per_hour.' PKR/hr':$century_rate.' PKR/min'; ?>)</p>
    </div>

    <!-- Bill Breakdown -->
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
            <?php if(empty($items_list)): ?>
                <li>(No additional items sold)</li>
            <?php else: ?>
                <?php foreach($items_list as $item): ?>
                    <li class="flex justify-between pr-4">
                        <span>- <?php echo htmlspecialchars($item['item_name']); ?> x <?php echo $item['quantity']; ?> @ <?php echo number_format($item['price_per_unit'], 2); ?>/unit</span>
                        <span class="font-normal"><?php echo number_format($item['total_item_price'], 2); ?> PKR</span>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>

    <div class="flex flex-col md:flex-row md:justify-between md:items-center pt-2">
        <!-- Grand Total -->
        <span class="text-3xl font-extrabold text-white md:text-left mb-4 md:mb-0">
            GRAND TOTAL: <?php echo number_format($final_total, 2); ?> PKR
        </span>

        <!-- Buttons -->
       <div class="flex space-x-4 screen-only">
        <a href="admin.php" 
           class="bg-blue-600 text-white px-6 py-3 rounded-lg text-lg hover:bg-blue-700 transition">
            Return to Dashboard
        </a>
        <button onclick="printBill()" 
                class="bg-green-600 text-white px-6 py-3 rounded-lg text-lg hover:bg-green-700 transition">
            🖨️ Print Bill
        </button>
        </div>
    </div>

</div>
<script>
function printBill() {
    const bill = document.getElementById('bill-container');
    const printWindow = window.open('', '', 'width=800,height=600');
    printWindow.document.write('<html><head><title>Print Bill</title>');
    printWindow.document.write('<link href="https://cdn.tailwindcss.com" rel="stylesheet">');
    printWindow.document.write('<style>@media print { .screen-only { display: none !important; } }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(bill.outerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}
</script>
            </div>
        </main>
    </div>

</body>
</html>
