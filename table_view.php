<?php
session_start();
include 'db.php'; // Include database connection
// include 'layout/sidebar.php'; // Include your layout

// Ensure table_id and session_id are passed
$table_id = $_GET['table_id'] ?? die("Table ID required.");
$session_id = $_GET['session_id'] ?? die("Session ID required.");

// --- 1. Fetch Session and Table Rates ---
// Note: We use the `id` column for table reference in snooker_sessions
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

// --- 2. Fetch Items Added and Calculate Items Total ---
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

// --- 3. Calculation Function (Used for Bill Generation) ---
function calculate_table_charge($start_time, $rate_type, $rate_per_hour, $century_rate, $current_time = null) {
    if ($current_time === null) {
        $current_time = new DateTime();
    }
    $start = new DateTime($start_time);
    $interval = $start->diff($current_time);
    
    // CORRECTED DURATION CALCULATION:
    // Calculate total duration in seconds from the DateInterval properties
    $duration_seconds = (
        $interval->y * 365 * 24 * 60 * 60 + // Years
        $interval->m * 30 * 24 * 60 * 60 +  // Months (approximate)
        $interval->d * 24 * 60 * 60 +       // Days
        $interval->h * 60 * 60 +            // Hours
        $interval->i * 60 +                 // Minutes
        $interval->s                         // Seconds
    );
    // End of CORRECTED DURATION CALCULATION

    if ($rate_type == 'Normal') {
        // Rate is per hour
        $duration_hours = $duration_seconds / 3600;
        $table_charge = $duration_hours * $rate_per_hour;
    } else {
        // Century rate is per minute
        $duration_minutes = $duration_seconds / 60;
        $table_charge = $duration_minutes * $century_rate;
    }
    
    // Use ceil() or round() based on your club's rounding rule
    return round($table_charge, 2); 
}

// Calculate the current table charge
$table_charge = calculate_table_charge($start_time_db, $session_data['rate_type'], $rate_per_hour, $century_rate);
$final_total = $table_charge + $items_total;

// --- 4. The HTML/Layout (You can add the HTML structure from the previous answer here) ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Active Session: <?php echo htmlspecialchars($session_data['table_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100 min-h-screen"> 
    
    <div class="flex">

        <?php include 'layout/sidebar.php'; ?>

        <div class="flex-1 p-8">
    <div class="max-w-xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <h1 class="text-3xl font-bold mb-4 border-b pb-2">
            <?php echo htmlspecialchars($session_data['table_name']); ?> - <span class="text-red-600">ACTIVE</span>
        </h1>

        <div class="text-lg space-y-2 mb-4">
            <p><strong>Start Time:</strong> <?php echo $start_time_display; ?></p>
            <p><strong>Duration:</strong> <span id="duration-timer" class="font-mono text-xl">Loading...</span></p>
        </div>

        <div class="mb-4 p-3 bg-gray-50 rounded">
            <strong class="block mb-2">Rate Type:</strong>
            <form action="change_rate.php" method="POST" class="inline-flex space-x-4">
                <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
                
                <label class="inline-flex items-center">
                    <input type="radio" name="rate_type" value="Normal" 
                           <?php echo ($session_data['rate_type'] == 'Normal') ? 'checked' : ''; ?> 
                           class="form-radio text-green-600" onchange="this.form.submit()">
                    <span class="ml-2">Normal (<?php echo $rate_per_hour; ?> PKR/hr)</span>
                </label>
                
                <label class="inline-flex items-center">
                    <input type="radio" name="rate_type" value="Century" 
                           <?php echo ($session_data['rate_type'] == 'Century') ? 'checked' : ''; ?> 
                           class="form-radio text-green-600" onchange="this.form.submit()">
                    <span class="ml-2">Century (<?php echo $century_rate; ?> PKR/min)</span>
                </label>
            </form>
        </div>

        <h2 class="text-xl font-semibold mb-2 mt-6">Items Added: (Total: <?php echo number_format($items_total, 2); ?> PKR)</h2>
        <ul class="list-disc ml-6 space-y-1">
            <?php if (empty($items_list)): ?>
                <li class="text-gray-500">No items added yet.</li>
            <?php else: ?>
                <?php foreach ($items_list as $item): ?>
                    <li class="flex justify-between pr-4">
                        <span>- <?php echo htmlspecialchars($item['item_name']); ?> x <?php echo $item['quantity']; ?></span>
                        <span class="font-medium"><?php echo number_format($item['total_item_price'], 2); ?> PKR</span>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        
        <div class="mt-4">
            <a href="add_item.php?session_id=<?php echo $session_id; ?>" 
               class="bg-blue-500 text-white px-4 py-2 rounded inline-block hover:bg-blue-600">
                ➕ Add Item
            </a>
        </div>
        
        <hr class="my-6">

        <div class="text-2xl font-bold flex justify-between">
            <span>Table Charge:</span>
            <span id="table-charge-display" class="text-gray-700"><?php echo number_format($table_charge, 2); ?> PKR</span>
        </div>
         <div class="text-2xl font-bold flex justify-between mt-2">
            <span>TOTAL BILL:</span>
            <span id="final-total-display" class="text-green-700"><?php echo number_format($final_total, 2); ?> PKR</span>
        </div>

        <div class="mt-6">
            <form action="generate_bill.php" method="POST">
                <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
                <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
                <button type="submit" name="close_bill"
                        class="w-full bg-red-600 text-white text-lg py-3 rounded hover:bg-red-700 transition">
                    Close & Generate Bill
                </button>
              <a href="admin.php"
   class="w-full block text-center mt-3 bg-gray-700 text-white text-lg py-3 rounded hover:bg-gray-800 transition mb-3">
    ⬅ Back to Admin Panel
</a>
            </form>
        </div>
    </div>

    <script>
        const startTime = new Date("<?php echo $start_time_db; ?>").getTime();
        const itemsTotal = <?php echo $items_total; ?>;
        const ratePerHour = <?php echo $rate_per_hour; ?>;
        const centuryRate = <?php echo $century_rate; ?>;
        let rateType = "<?php echo $session_data['rate_type']; ?>";

        function updateTimerAndTotal() {
            const now = new Date().getTime();
            const durationMs = now - startTime;

            // 1. Duration Display (HH:MM:SS)
            const seconds = Math.floor((durationMs / 1000) % 60);
            const minutes = Math.floor((durationMs / (1000 * 60)) % 60);
            const hours = Math.floor((durationMs / (1000 * 60 * 60)));
            
            const durationString = 
                String(hours).padStart(2, '0') + ":" + 
                String(minutes).padStart(2, '0') + ":" + 
                String(seconds).padStart(2, '0');

            document.getElementById('duration-timer').textContent = durationString;

            // 2. Dynamic Table Charge Calculation
            let tableCharge = 0;
            const durationHours = durationMs / (1000 * 60 * 60);
            const durationMinutes = durationMs / (1000 * 60);

            if (rateType === 'Normal') {
                tableCharge = durationHours * ratePerHour;
            } else { // Century Rate
                tableCharge = durationMinutes * centuryRate;
            }

            // 3. Update Totals
            const finalTotal = tableCharge + itemsTotal;

            document.getElementById('table-charge-display').textContent = 
                (Math.round(tableCharge * 100) / 100).toFixed(2) + " PKR";
            document.getElementById('final-total-display').textContent = 
                (Math.round(finalTotal * 100) / 100).toFixed(2) + " PKR";
        }

        // Run immediately and then every second
        updateTimerAndTotal();
        setInterval(updateTimerAndTotal, 1000);
    </script>
</body>
</html>