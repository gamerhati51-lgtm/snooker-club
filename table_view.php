<?php
session_start();
include 'db.php';

// Ensure table_id and session_id are passed
$table_id = $_GET['table_id'] ?? die("Table ID required.");
$session_id = $_GET['session_id'] ?? die("Session ID required.");

// --- 1. Fetch Session and Table Rates ---
$stmt = $conn->prepare("
    SELECT 
        s.start_time, s.rate_type, 
        st.table_name, st.rate_per_hour, st.century_rate
    FROM 
        snooker_sessions s
    JOIN 
        snooker_tables st ON s.id = st.id
    WHERE 
        s.session_id = ? AND s.status = 'Active'
");
$stmt->bind_param("i", $session_id);
$stmt->execute();
$result = $stmt->get_result();
$session_data = $result->fetch_assoc();
$stmt->close();

if (!$session_data) {
    die("Error: No active session found for this ID.");
}

$start_time_db = $session_data['start_time'];
$start_time_display = date('g:i A', strtotime($start_time_db));
$rate_per_hour = (float)$session_data['rate_per_hour'];
$century_rate = (float)$session_data['century_rate'];

// --- 2. Fetch Items Added ---
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

// --- 3. Calculation Function ---
function calculate_table_charge($start_time, $rate_type, $rate_per_hour, $century_rate, $current_time = null) {
    if ($current_time === null) {
        $current_time = new DateTime();
    }
    $start = new DateTime($start_time);
    $interval = $start->diff($current_time);

    $duration_seconds =
        $interval->y * 365 * 24 * 60 * 60 +
        $interval->m * 30 * 24 * 60 * 60 +
        $interval->d * 24 * 60 * 60 +
        $interval->h * 60 * 60 +
        $interval->i * 60 +
        $interval->s;

    if ($rate_type == 'Normal') {
        $duration_hours = $duration_seconds / 3600;
        $table_charge = $duration_hours * $rate_per_hour;
    } else {
        $duration_minutes = $duration_seconds / 60;
        $table_charge = $duration_minutes * $century_rate;
    }

    return round($table_charge, 2);
}

$table_charge = calculate_table_charge($start_time_db, $session_data['rate_type'], $rate_per_hour, $century_rate);
$final_total = $table_charge + $items_total;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Active Session: <?php echo htmlspecialchars($session_data['table_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">
<div class="flex">

    <?php include 'layout/sidebar.php'; ?>

    <div class="flex-1 p-8">
        <div class="max-w-2xl mx-auto bg-white shadow-xl rounded-2xl p-8 border border-gray-200">

            <!-- Header -->
            <h1 class="text-3xl font-extrabold text-center mb-6 text-snooker-green">
                <?php echo htmlspecialchars($session_data['table_name']); ?>  
                <span class="text-red-600">• ACTIVE</span>
            </h1>

            <!-- Session Info -->
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <p class="text-lg"><strong>Start Time:</strong> <?php echo $start_time_display; ?></p>
                <p class="text-lg mt-1"><strong>Duration:</strong> 
                    <span id="duration-timer" class="font-mono text-xl text-blue-600">00:00:00</span>
                </p>
            </div>

            <!-- Rate Type -->
            <div class="mb-6">
                <strong class="block text-lg mb-2">Rate Type</strong>
                <form action="change_rate.php" method="POST" class="space-x-6 flex">
                    <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                    <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">

                    <label class="inline-flex items-center text-lg">
                        <input type="radio" name="rate_type" value="Normal"
                            <?php echo ($session_data['rate_type'] == 'Normal') ? 'checked' : ''; ?>
                            onchange="this.form.submit()">
                        <span class="ml-2">Normal (<?php echo $rate_per_hour; ?> PKR/hr)</span>
                    </label>

                    <label class="inline-flex items-center text-lg">
                        <input type="radio" name="rate_type" value="Century"
                            <?php echo ($session_data['rate_type'] == 'Century') ? 'checked' : ''; ?>
                            onchange="this.form.submit()">
                        <span class="ml-2">Century (<?php echo $century_rate; ?> PKR/min)</span>
                    </label>
                </form>
            </div>

            <!-- Items Section -->
            <h2 class="text-xl font-bold mb-2">
                Items Added ( <span class="text-green-700"><?php echo number_format($items_total, 2); ?></span> PKR )
            </h2>

            <ul class="ml-4 space-y-1">
                <?php if (empty($items_list)): ?>
                    <li class="text-gray-500">No items added yet.</li>
                <?php else: ?>
                    <?php foreach ($items_list as $item): ?>
                        <li class="flex justify-between text-lg">
                            <span>- <?php echo htmlspecialchars($item['item_name']); ?> x <?php echo $item['quantity']; ?></span>
                            <strong><?php echo number_format($item['total_item_price'], 2); ?> PKR</strong>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>

            <a href="add_item.php?session_id=<?php echo $session_id; ?>"
               class="mt-3 inline-block bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 text-lg">
                ➕ Add Item
            </a>

            <hr class="my-6">

            <!-- Billing Section -->
            <div class="bg-gray-50 p-6 rounded-xl shadow-inner border">

                <div class="flex justify-between text-2xl font-bold mb-3">
                    <span class="text-gray-700">Table Play Charge:</span>
                    <span id="table-charge-display" class="text-gray-900">
                        <?php echo number_format($table_charge, 2); ?> PKR
                    </span>
                </div>

                <div class="flex justify-between text-3xl font-extrabold mt-4 text-green-700">
                    <span>FINAL TOTAL BILL:</span>
                    <span id="final-total-display">
                        <?php echo number_format($final_total, 2); ?> PKR
                    </span>
                </div>

            </div>

            <!-- Buttons -->
            <div class="mt-8">
                <form action="generate_bill.php" method="POST">
                    <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                    <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">

                    <button type="submit" name="close_bill"
                        class="w-full bg-red-600 text-white text-xl py-3 rounded-lg hover:bg-red-700">
                        Close & Generate Bill
                    </button>

                    <a href="admin.php"
                       class="w-full block text-center mt-3 bg-gray-700 text-white text-xl py-3 rounded-lg hover:bg-gray-800">
                        ⬅ Back to Admin Panel
                    </a>
                </form>
            </div>

        </div>
    </div>

</div>

<!-- JS Timer -->
<script>
    const startTime = new Date("<?php echo $start_time_db; ?>").getTime();
    const itemsTotal = <?php echo $items_total; ?>;
    const ratePerHour = <?php echo $rate_per_hour; ?>;
    const centuryRate = <?php echo $century_rate; ?>;
    let rateType = "<?php echo $session_data['rate_type']; ?>";

    function updateTimerAndTotal() {
        const now = Date.now();
        const durationMs = now - startTime;

        const seconds = Math.floor((durationMs / 1000) % 60);
        const minutes = Math.floor((durationMs / 60000) % 60);
        const hours = Math.floor(durationMs / 3600000);

        document.getElementById('duration-timer').textContent =
            String(hours).padStart(2,'0') + ":" +
            String(minutes).padStart(2,'0') + ":" +
            String(seconds).padStart(2,'0');

        let tableCharge = 0;
        const durationHours = durationMs / 3600000;
        const durationMinutes = durationMs / 60000;

        tableCharge = (rateType === 'Normal')
            ? durationHours * ratePerHour
            : durationMinutes * centuryRate;

        const finalTotal = tableCharge + itemsTotal;

        document.getElementById('table-charge-display').textContent =
            tableCharge.toFixed(2) + " PKR";

        document.getElementById('final-total-display').textContent =
            finalTotal.toFixed(2) + " PKR";
    }

    updateTimerAndTotal();
    setInterval(updateTimerAndTotal, 1000);
</script>

</body>
</html>
