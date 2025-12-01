<?php
session_start();
include 'db.php';

// Protect page (optional: decide if this is admin-only or public)
if (!isset($_SESSION['admin_name'])) {
    // header("Location: index.php"); 
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
            (start_time < ? AND end_time > ?) OR 
            (start_time < ? AND end_time > ?) OR 
            (start_time = ? AND end_time = ?) 
        )
    ");
    
    // We bind parameters multiple times for comprehensive overlap check
    $stmt_check->bind_param("isssssss", 
        $table_id, $booking_date, 
        $end_time, $start_time, // existing booking covers new booking
        $start_time, $end_time, // new booking covers existing booking
        $start_time, $end_time  // exactly same time slot
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

// --- B. Fetch Tables for Form Dropdown ---
$tables_result = $conn->query("SELECT id, table_name FROM snooker_tables ORDER BY id ASC");
$all_tables = $tables_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snooker | Add Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Ensures main content moves over on desktop */
        .main-content {
            transition: margin-left 0.3s ease;
        }
        @media (min-width: 1024px) {
            .main-content {
                margin-left: 16rem; /* 64 (w-64) converted to rem */
            }
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    
    <!-- Dashboard Container -->
    <div class="flex">

        <!-- Sidebar -->
        <?php include 'layout/sidebar.php'; ?>

        <!-- Main Content Wrapper -->
        <main class="flex-1 ml-0 lg:ml-64 pt-20 p-6 sm:p-8 main-content">
            
            <!-- Header (Fixed at top) -->
            <?php include "layout/header.php"; ?>

            <h1 class="text-3xl sm:text-4xl font-extrabold mb-6 text-gray-800 border-b-2 pb-2 mt-9 text-center">
                🎱 New Table Reservation
            </h1>
            
            <!-- Message Alert -->
            <?php if (!empty($message)) { ?>
                <div class="mb-8 p-4 rounded-xl shadow-lg border 
                    <?php echo (strpos($message, 'Error') !== false || strpos($message, '❌') !== false) 
                        ? 'bg-red-50 text-red-700 border-red-300' 
                        : 'bg-green-50 text-green-700 border-green-300'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <!-- Booking Form Card (Now the main focus of the page) -->
            <div class="max-w-xl mx-auto bg-white shadow-2xl p-8 rounded-2xl border border-gray-200">
                <h2 class="text-2xl font-bold text-primary-blue mb-6">
                    Customer Details & Slot Selection
                </h2>

                <form method="post" class="space-y-6">
                    
                    <div>
                        <label for="customer_name" class="block text-gray-700 font-medium mb-1">Customer Name</label>
                        <input type="text" name="customer_name" id="customer_name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition shadow-sm"
                            placeholder="Enter full name">
                    </div>
                    
                    <div>
                        <label for="table_id" class="block text-gray-700 font-medium mb-1">Select Table</label>
                        <select name="table_id" id="table_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition shadow-sm appearance-none bg-white">
                            <option value="" disabled selected>-- Choose Snooker Table --</option>
                            <?php foreach ($all_tables as $table): ?>
                                <option value="<?php echo $table['id']; ?>">
                                    <?php echo htmlspecialchars($table['table_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="booking_date" class="block text-gray-700 font-medium mb-1">Date</label>
                            <input type="date" name="booking_date" id="booking_date" required min="<?php echo date('Y-m-d'); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition shadow-sm">
                        </div>
                        
                        <div>
                            <label for="start_time" class="block text-gray-700 font-medium mb-1">Start Time</label>
                            <input type="time" name="start_time" id="start_time" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition shadow-sm">
                        </div>
                    </div>

                    <div>
                        <label for="duration" class="block text-gray-700 font-medium mb-1">Duration</label>
                        <select name="duration" id="duration" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition shadow-sm appearance-none bg-white">
                            <option value="60">1 Hour</option>
                            <option value="90">1.5 Hours</option>
                            <option value="120">2 Hours</option>
                            <option value="180">3 Hours</option>
                            <option value="240">4 Hours</option>
                        </select>
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" name="submit_booking"
                            class="bg-green-600 text-white w-full py-3 rounded-xl hover:bg-green-700 transition duration-200 font-bold text-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-green-300">
                            Confirm Reservation
                        </button>
                    </div>
                </form>
            </div>
            
        </main>
        
    </div>
</body>
</html>