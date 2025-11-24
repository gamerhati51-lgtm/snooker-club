<?php
session_start();
include 'db.php';

// Protect page (optional: decide if this is admin-only or public)
if (!isset($_SESSION['admin_name'])) {
    // header("Location: index.php"); // Uncomment if admin-only
    // exit;
}

$message = "";

// --- A. Handle Form Submission ---
if (isset($_POST['submit_booking'])) {
    $table_id = $_POST['table_id'];
    $customer_name = trim($_POST['customer_name']);
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $duration_minutes = (int)$_POST['duration'];
    
    // Calculate End Time
    $start_datetime = new DateTime($booking_date . ' ' . $start_time);
    $end_datetime = clone $start_datetime;
    $end_datetime->modify('+' . $duration_minutes . ' minutes');
    
    $end_time = $end_datetime->format('H:i:s');
    
    // --- 1. Conflict Check ---
    $stmt_check = $conn->prepare("
        SELECT COUNT(*) 
        FROM snooker_bookings 
        WHERE table_id = ? 
        AND booking_date = ? 
        AND (
            (start_time < ? AND end_time > ?) OR  -- New booking starts during an existing one
            (start_time < ? AND end_time > ?) OR  -- New booking ends during an existing one
            (start_time >= ? AND end_time <= ?)  -- New booking is entirely within an existing one
        )
    ");
    $stmt_check->bind_param("isssssss", 
        $table_id, $booking_date, 
        $end_time, $start_time, // Check 1 & 2
        $start_time, $end_time, // Check 3 & 4
        $start_time, $end_time  // Check 5 & 6 (or just use the first 4 parameters if logic is simple)
    );
    
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_row();
    $conflict_count = $row_check[0];
    $stmt_check->close();
    
    if ($conflict_count > 0) {
        $message = "❌ Error: The selected table is already booked during that time slot.";
    } else {
        // --- 2. Insert Booking ---
        $stmt_insert = $conn->prepare("
            INSERT INTO snooker_bookings (table_id, customer_name, booking_date, start_time, end_time, status)
            VALUES (?, ?, ?, ?, ?, 'Confirmed')
        ");
        $stmt_insert->bind_param("issss", $table_id, $customer_name, $booking_date, $start_time, $end_time);

        if ($stmt_insert->execute()) {
            $message = "🎉 Booking confirmed for **" . htmlspecialchars($customer_name) . "** on " . $booking_date . " from " . date('g:i A', strtotime($start_time)) . " to " . date('g:i A', strtotime($end_time));
        } else {
            $message = "❌ Database Error: " . $stmt_insert->error;
        }
        $stmt_insert->close();
    }
}

// --- B. Fetch Data for Dynamic Form/View ---

// 1. Fetch all available snooker tables
$tables_result = $conn->query("SELECT id, table_name FROM snooker_tables ORDER BY id ASC");
$all_tables = $tables_result->fetch_all(MYSQLI_ASSOC);

// 2. Fetch all upcoming bookings for display
$upcoming_bookings_query = $conn->prepare("
    SELECT 
        sb.booking_id, sb.customer_name, sb.booking_date, sb.start_time, sb.end_time, st.table_name
    FROM 
        snooker_bookings sb
    JOIN 
        snooker_tables st ON sb.table_id = st.id
    WHERE 
        sb.booking_date >= CURDATE()
    ORDER BY 
        sb.booking_date ASC, sb.start_time ASC
");
$upcoming_bookings_query->execute();
$upcoming_bookings_result = $upcoming_bookings_query->get_result();
$upcoming_bookings_query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Snooker Table Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex">

<!-- Sidebar -->
<?php include 'layout/sidebar.php'; ?>

<!-- Main Section -->
<div class="flex-1 p-8 ml-64">

    <h1 class="text-4xl font-bold mb-6 text-gray-700">🗓️ Snooker Table Booking</h1>

   

            <?php if (!empty($message)) { ?>
                <div class="mb-6 p-4 <?php echo (strpos($message, 'Error') !== false || strpos($message, '❌') !== false) ? 'bg-red-100 text-red-700 border border-red-300' : 'bg-green-100 text-green-700 border border-green-300'; ?> rounded">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="bg-white shadow p-6 rounded-lg lg:col-span-1 h-fit">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">
                        Reserve a Table
                    </h2>

                    <form method="post" class="space-y-4">
                        
                        <div>
                            <label for="customer_name" class="block text-gray-700 font-medium">Customer Name</label>
                            <input type="text" name="customer_name" id="customer_name" required
                                   class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="table_id" class="block text-gray-700 font-medium">Select Table</label>
                            <select name="table_id" id="table_id" required
                                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Choose Table --</option>
                                <?php foreach ($all_tables as $table): ?>
                                    <option value="<?php echo $table['id']; ?>">
                                        <?php echo htmlspecialchars($table['table_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="booking_date" class="block text-gray-700 font-medium">Date</label>
                            <input type="date" name="booking_date" id="booking_date" required min="<?php echo date('Y-m-d'); ?>"
                                   class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="start_time" class="block text-gray-700 font-medium">Start Time</label>
                            <input type="time" name="start_time" id="start_time" required
                                   class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="duration" class="block text-gray-700 font-medium">Duration (Minutes)</label>
                            <select name="duration" id="duration" required
                                    class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="60">1 Hour</option>
                                <option value="90">1.5 Hours</option>
                                <option value="120">2 Hours</option>
                                <option value="180">3 Hours</option>
                                </select>
                        </div>
                        
                        <div class="pt-4">
                            <button type="submit" name="submit_booking"
                                    class="bg-blue-600 text-white w-full px-5 py-2 rounded hover:bg-blue-700 transition">
                                Confirm Booking
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="bg-white shadow p-6 rounded-lg lg:col-span-2">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">
                        Upcoming Reservations
                    </h2>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Table
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Customer
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Time Slot
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($upcoming_bookings_result->num_rows > 0): ?>
                                    <?php while ($booking = $upcoming_bookings_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($booking['table_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                <?php echo htmlspecialchars($booking['customer_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-semibold">
                                                <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - 
                                                <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No upcoming bookings found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>
        </div>
        
    </div>
</body>
</html>