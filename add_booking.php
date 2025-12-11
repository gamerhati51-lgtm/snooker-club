<?php
session_start();
include 'db.php';

// Protect page (optional: decide if this is admin-only or public)
if (!isset($_SESSION['admin_name'])) {
    // header("Location: index.php"); 
    // exit;
}

$message = "";

// Use POST-REDIRECT-GET pattern to prevent form resubmission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
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
        $_SESSION['message'] = "❌ Error: The selected table is already booked during that time slot.";
        $_SESSION['message_type'] = "error";
    } else {
        // --- 2. Insert Booking ---
        $stmt_insert = $conn->prepare("
            INSERT INTO snooker_bookings (table_id, customer_name, booking_date, start_time, end_time, status)
            VALUES (?, ?, ?, ?, ?, 'Confirmed')
        ");
        $stmt_insert->bind_param("issss", $table_id, $customer_name, $booking_date, $start_time, $end_time);

        if ($stmt_insert->execute()) {
            $_SESSION['message'] = "🎉 Booking confirmed for **" . htmlspecialchars($customer_name) . "** on " . $booking_date . " from " . date('g:i A', strtotime($start_time)) . " to " . date('g:i A', strtotime($end_time));
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "❌ Database Error: " . $stmt_insert->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt_insert->close();
    }
    
    // Redirect to prevent form resubmission on page refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get message from session if exists
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// --- B. Fetch Tables for Form Dropdown ---
$tables_result = $conn->query("SELECT id, table_name FROM snooker_tables ORDER BY id ASC");
$all_tables = $tables_result->fetch_all(MYSQLI_ASSOC);

// Get today's bookings for the sidebar summary
$today = date('Y-m-d');
$today_bookings_result = $conn->query("
    SELECT COUNT(*) as total_bookings 
    FROM snooker_bookings 
    WHERE booking_date = '$today'
");
$today_bookings = $today_bookings_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snooker | Add New Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --secondary: #3b82f6;
            --dark: #1f2937;
            --light: #f8fafc;
        }
        
        body { 
            font-family: 'Poppins', 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%);
            min-height: 100vh;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
            border-radius: 20px;
        }
        
        .form-input {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid #e5e7eb;
            background: white;
            font-size: 15px;
        }
        
        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.35);
        }
        
        .sidebar {
            background: linear-gradient(165deg, var(--dark) 0%, #111827 100%);
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%);
            border-left: 4px solid var(--secondary);
        }
        
        .highlight-text {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header-gradient {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.95) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
        }
        
        .floating-label {
            position: absolute;
            top: -10px;
            left: 12px;
            background: white;
            padding: 0 8px;
            font-size: 12px;
            color: var(--primary);
            font-weight: 600;
            z-index: 10;
        }
        
        .input-container {
            position: relative;
            padding-top: 8px;
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        
        .badge {
            background: linear-gradient(135deg, var(--secondary) 0%, #1d4ed8 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .tab-active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .message-alert {
            transition: opacity 0.5s ease-out;
        }
    </style>
</head>

<body class="min-h-screen">
    
    <!-- Dashboard Container -->
    <div class="flex">
        <!-- Sidebar -->
        <?php include 'layout/sidebar.php'; ?>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            <?php include 'layout/header.php'; ?>

            <!-- Main Content Area -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 mt-10 lg:ml-64 transition-all duration-300">
                <div class="max-w-4xl mx-auto">
                    
                    <!-- Page Header -->
                    <div class="mb-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-2">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                                    <i class="fas fa-plus-circle text-green-500 mr-2"></i>
                                    <span class="highlight-text">New Booking</span>
                                </h1>
                                <p class="text-gray-600">Create a new reservation for snooker tables</p>
                            </div>
                            <div class="mt-2 sm:mt-0">
                                <div class="flex items-center space-x-4">
                                    <div class="stats-card px-4 py-2 rounded-lg">
                                       
                                    </div>
                                    <a href="bookings.php" class="inline-flex items-center px-4 py-2.5 bg-white text-gray-700 font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-all">
                                        <i class="fas fa-list mr-2"></i>
                                        View Bookings
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                    <!-- Message Alert -->
                    <?php if (!empty($message)) { ?>
                        <div id="message-alert" class="mb-2 glass-card p-4 border-l-4 message-alert
                            <?php echo (isset($message_type) && $message_type === 'error') 
                                ? 'border-red-500 bg-red-50' 
                                : 'border-green-500 bg-green-50'; ?>">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas <?php echo (isset($message_type) && $message_type === 'error') ? 'fa-exclamation-circle text-red-500' : 'fa-check-circle text-green-500'; ?> text-xl mt-0.5"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="<?php echo (isset($message_type) && $message_type === 'error') ? 'text-red-700' : 'text-green-700'; ?> font-medium">
                                        <?php echo str_replace(['**', '*'], '', $message); ?>
                                    </p>
                                    <?php if (!isset($message_type) || $message_type !== 'error') { ?>
                                        <p class="text-green-600 text-sm mt-1">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Booking has been added to the system. You can view it in the bookings list.
                                        </p>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <!-- Booking Form Card -->
                    <div class="glass-card p-6 sm:p-8 mb-8">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-100 to-green-50 flex items-center justify-center mr-4">
                                <i class="fas fa-calendar-plus text-2xl text-green-500"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Booking Information</h3>
                                <p class="text-gray-600 text-sm">Fill in customer details and select preferred time slot</p>
                            </div>
                        </div>

                        <form method="post" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Customer Name -->
                                <div class="input-container">
                                    <div class="floating-label">
                                        <i class="fas fa-user mr-1"></i>
                                        Customer Name
                                    </div>
                                    <input type="text" name="customer_name" id="customer_name" required
                                        class="w-full px-5 py-3.5 rounded-xl form-input"
                                        placeholder="Enter customer full name"
                                        autofocus>
                                    <p class="text-gray-500 text-xs mt-2 flex items-center">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Enter the customer's full name for the booking
                                    </p>
                                </div>
                                
                                <!-- Table Selection -->
                                <div class="input-container">
                                    <div class="floating-label">
                                        <i class="fas fa-hashtag mr-1"></i>
                                        Select Table
                                    </div>
                                    <div class="relative">
                                        <select name="table_id" id="table_id" required
                                                class="w-full px-5 py-3.5 rounded-xl form-input appearance-none cursor-pointer">
                                            <option value="" disabled selected>Choose a snooker table...</option>
                                            <?php foreach ($all_tables as $table): ?>
                                                <option value="<?php echo $table['id']; ?>">
                                                    <?php echo htmlspecialchars($table['table_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                            <i class="fas fa-chevron-down text-gray-400"></i>
                                        </div>
                                    </div>
                                    <p class="text-gray-500 text-xs mt-2 flex items-center">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Total of <?php echo count($all_tables); ?> tables available
                                    </p>
                                </div>
                            </div>

                            <!-- Date & Time Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="input-container">
                                    <div class="floating-label">
                                        <i class="fas fa-calendar-alt mr-1"></i>
                                        Booking Date
                                    </div>
                                    <div class="relative">
                                        <input type="date" name="booking_date" id="booking_date" required 
                                            min="<?php echo date('Y-m-d'); ?>"
                                            class="w-full px-5 py-3.5 rounded-xl form-input">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                            <i class="far fa-calendar text-gray-400"></i>
                                        </div>
                                    </div>
                                    <p class="text-gray-500 text-xs mt-2 flex items-center">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Bookings can be made from today onwards
                                    </p>
                                </div>
                                
                                <div class="input-container">
                                    <div class="floating-label">
                                        <i class="fas fa-clock mr-1"></i>
                                        Start Time
                                    </div>
                                    <div class="relative">
                                        <input type="time" name="start_time" id="start_time" required
                                            class="w-full px-5 py-3.5 rounded-xl form-input"
                                            step="900" min="08:00" max="23:00">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                            <i class="far fa-clock text-gray-400"></i>
                                        </div>
                                    </div>
                                    <p class="text-gray-500 text-xs mt-2 flex items-center">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Opening hours: 8:00 AM - 11:00 PM
                                    </p>
                                </div>
                            </div>

                            <!-- Duration and Submit Button -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="input-container">
                                    <div class="floating-label">
                                        <i class="fas fa-hourglass-half mr-1"></i>
                                        Duration
                                    </div>
                                    <div class="relative">
                                        <select name="duration" id="duration" required
                                                class="w-full px-5 py-3.5 rounded-xl form-input appearance-none cursor-pointer">
                                            <option value="60">1 Hour</option>
                                            <option value="90" selected>1.5 Hours</option>
                                            <option value="120">2 Hours</option>
                                            <option value="180">3 Hours</option>
                                            <option value="240">4 Hours</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                            <i class="fas fa-history text-gray-400"></i>
                                        </div>
                                    </div>
                                    <p class="text-gray-500 text-xs mt-2 flex items-center">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Most popular: 1.5 hours
                                    </p>
                                    
                                    <!-- Submit Button -->
                                    <button type="submit" name="submit_booking"
                                        class="btn-primary mt-3 text-white w-full py-4 rounded-xl font-semibold text-lg shadow-lg pulse-animation">
                                        <i class="fas fa-check-circle mr-3"></i>
                                        Confirm Booking Reservation
                                    </button>
                                    <p class="text-center text-gray-500 text-sm mt-3">
                                        <i class="fas fa-shield-alt mr-1"></i>
                                        Booking will be confirmed instantly after submission
                                    </p>
                                </div>
                                
                                <!-- Preview Card -->
                                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-5 border border-blue-100">
                                    <h4 class="font-bold text-gray-800 mb-3 flex items-center">
                                        <i class="fas fa-eye mr-2 text-blue-500"></i>
                                        Booking Preview
                                    </h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Customer:</span>
                                            <span id="preview-customer" class="font-medium text-gray-800">Not set</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Date:</span>
                                            <span id="preview-date" class="font-medium text-gray-800">Not set</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Time:</span>
                                            <span id="preview-time" class="font-medium text-gray-800">Not set</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Duration:</span>
                                            <span id="preview-duration" class="font-medium text-gray-800">1.5 Hours</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Help Tips -->
                    <div class="glass-card p-6 mb-8">
                        <h4 class="font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-lightbulb mr-2 text-yellow-500"></i>
                            Booking Tips & Information
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="flex items-start p-3 bg-blue-50 rounded-lg">
                                <i class="fas fa-clock text-blue-500 mt-1 mr-3"></i>
                                <div>
                                    <p class="font-medium text-gray-800 text-sm">Peak Hours</p>
                                    <p class="text-gray-600 text-xs">Evenings (6 PM - 10 PM) are busiest. Consider off-peak discounts.</p>
                                </div>
                            </div>
                            <div class="flex items-start p-3 bg-green-50 rounded-lg">
                                <i class="fas fa-calendar-check text-green-500 mt-1 mr-3"></i>
                                <div>
                                    <p class="font-medium text-gray-800 text-sm">Advance Booking</p>
                                    <p class="text-gray-600 text-xs">Customers can book up to 30 days in advance.</p>
                                </div>
                            </div>
                            <div class="flex items-start p-3 bg-purple-50 rounded-lg">
                                <i class="fas fa-user-friends text-purple-500 mt-1 mr-3"></i>
                                <div>
                                    <p class="font-medium text-gray-800 text-sm">Group Bookings</p>
                                    <p class="text-gray-600 text-xs">For large groups, consider booking adjacent tables.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Preview update functionality
        document.addEventListener('DOMContentLoaded', function() {
            const customerInput = document.getElementById('customer_name');
            const dateInput = document.getElementById('booking_date');
            const timeInput = document.getElementById('start_time');
            const durationInput = document.getElementById('duration');
            
            // Format time to AM/PM
            function formatTime(timeString) {
                if (!timeString) return 'Not set';
                const [hours, minutes] = timeString.split(':');
                const hour = parseInt(hours);
                const ampm = hour >= 12 ? 'PM' : 'AM';
                const formattedHour = hour % 12 || 12;
                return `${formattedHour}:${minutes} ${ampm}`;
            }
            
            // Format date
            function formatDate(dateString) {
                if (!dateString) return 'Not set';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', { 
                    weekday: 'short', 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric' 
                });
            }
            
            // Format duration
            function formatDuration(minutes) {
                if (!minutes) return 'Not set';
                const hours = Math.floor(minutes / 60);
                const mins = minutes % 60;
                if (mins === 0) return `${hours} Hour${hours > 1 ? 's' : ''}`;
                if (hours === 0) return `${minutes} Minutes`;
                return `${hours}.${mins === 30 ? '5' : mins} Hour${hours > 1 ? 's' : ''}`;
            }
            
            // Update preview
            function updatePreview() {
                document.getElementById('preview-customer').textContent = 
                    customerInput.value || 'Not set';
                document.getElementById('preview-date').textContent = 
                    formatDate(dateInput.value);
                document.getElementById('preview-time').textContent = 
                    formatTime(timeInput.value);
                document.getElementById('preview-duration').textContent = 
                    formatDuration(durationInput.value);
            }
            
            // Add event listeners
            [customerInput, dateInput, timeInput, durationInput].forEach(input => {
                input.addEventListener('input', updatePreview);
                input.addEventListener('change', updatePreview);
            });
            
            // Set default date to today
            if (!dateInput.value) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.value = today;
                dateInput.min = today;
            }
            
            // Initialize preview
            updatePreview();
            
            // Set default time to next hour
            if (!timeInput.value) {
                const now = new Date();
                const nextHour = new Date(now.getTime() + 60 * 60 * 1000);
                const hours = nextHour.getHours().toString().padStart(2, '0');
                const minutes = '00';
                timeInput.value = `${hours}:${minutes}`;
            }
            
            // Auto-hide message after 2 seconds
            const messageAlert = document.getElementById('message-alert');
            if (messageAlert) {
                setTimeout(() => {
                    messageAlert.style.opacity = '0';
                    setTimeout(() => {
                        messageAlert.style.display = 'none';
                    }, 500);
                }, 2000);
            }
            
            // Add visual feedback on form focus
            const formInputs = document.querySelectorAll('.form-input');
            formInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('ring-2', 'ring-green-200', 'ring-opacity-50');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('ring-2', 'ring-green-200', 'ring-opacity-50');
                });
            });
        });
    </script>
</body>
</html>