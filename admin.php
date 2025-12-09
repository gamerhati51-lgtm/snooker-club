<?php
session_start();
if (!isset($_SESSION['admin_name'])) {
    header("Location: index.php");
    exit;
    
}
?>
<?php
// ... existing code ...
include 'db.php'; 
// Get admin name from session
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
// --- 1. Get Total Number of Tables ---
$total_tables_query = "SELECT COUNT(id) AS total FROM snooker_tables";
$total_tables_result = $conn->query($total_tables_query);
$total_tables_data = $total_tables_result->fetch_assoc();
$total_tables = (int)($total_tables_data['total'] ?? 0);

$active_tables_query = "SELECT COUNT(id) AS active_count FROM snooker_tables WHERE status = 'Occupied'";
$active_tables_result = $conn->query($active_tables_query);
$active_tables_data = $active_tables_result->fetch_assoc();
$active_tables = (int)($active_tables_data['active_count'] ?? 0);

$free_tables = $total_tables - $active_tables;
$availability_percent = 0;

if ($total_tables > 0) {

    $availability_percent = round(($free_tables / $total_tables) * 100);
}


// Get today's date range
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');

// Query to sum up session_cost for all sessions completed today
$stmt_revenue = $conn->prepare("
    SELECT 
        SUM(session_cost) AS total_revenue 
    FROM 
        snooker_sessions 
    WHERE 
        status = 'Completed' 
    AND 
        end_time >= ? 
    AND 
        end_time <= ?
");
$stmt_revenue->bind_param("ss", $today_start, $today_end);
$stmt_revenue->execute();
$revenue_result = $stmt_revenue->get_result();
$revenue_data = $revenue_result->fetch_assoc();
$stmt_revenue->close();

$total_revenue_today = (float)($revenue_data['total_revenue'] ?? 0.00);
$daily_target = 600.00; 

// --- Currency Note ---
// We will use PKR based on your previous usage, but you can change the symbol below.
$currency_symbol = 'PKR';


$upcoming_bookings_count = 0;

// SQL to count confirmed bookings starting now() and up to 24 hours from now
$stmt_upcoming = $conn->prepare("
    SELECT COUNT(*) 
    FROM snooker_bookings
    WHERE status = 'Confirmed'
    AND CONCAT(booking_date, ' ', start_time) > NOW()
    AND CONCAT(booking_date, ' ', start_time) <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
");

if ($stmt_upcoming) {
    $stmt_upcoming->execute();
    $result = $stmt_upcoming->get_result();
    $row = $result->fetch_row();
    $upcoming_bookings_count = $row[0];
    $stmt_upcoming->close();
}
// $upcoming_bookings_count now holds the dynamic data (e.g., 7)
?>
<?php


// Protect page
if(!isset($_SESSION['admin_name'])){
    header("Location: index.php");
    exit;
}

// Fetch total users
$result = $conn->query("SELECT COUNT(*) AS total_users FROM users");
$total_users = 0;
if($result){
    $row = $result->fetch_assoc();
    $total_users = $row['total_users'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>DASHBOARD</title>
    <script src="https://cdn.tailwindcss.com"></script>
  
    <!-- Using Inter font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-blue-100 font-sans">

  <!-- Sidebar -->
  <?php include 'layout/sidebar.php'; ?>

  <!-- Main Content Area -->
  <div class="min-h-screen lg:ml-64"> <!-- Only margin on large screens -->
    
    <!-- Header -->
    <?php include "layout/header.php"; ?>

    <!-- Main Content -->
    <main class="pt-16 p-6">
      
      <!-- Page Content -->
      <div id="content-area" class="space-y-2 bg-blue-100 p-6 rounded-lg">
          <h1 class="text-3xl font-bold mb-1 text-gray-800">
            Welcome, <?php echo htmlspecialchars($admin_name); ?>! 👋
          </h1>
          <p class="text-gray-600">Here's your quick overview dashboard</p>
    <!-- 1. Key Metrics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2">
    <!-- Card 1: Active Tables -->
    <div class="bg-white p-4 rounded-lg snooker-shadow card-glow border-t-4 border-snooker-accent">
        <p class="text-xs text-gray-500 font-medium">Active Tables</p>
        
        <p class="text-3xl font-extrabold text-snooker-green mt-1">
            <span id="active-count-display"><?php echo $active_tables; ?></span> 
            / 
            <span id="total-count-display"><?php echo $total_tables; ?></span>
        </p>
        
        <p class="text-[10px] text-gray-400 mt-1">
            <span id="availability-percent-display"><?php echo $availability_percent; ?></span>% availability 
            (<span id="free-count-display"><?php echo $free_tables; ?></span> free)
        </p>
    </div>

    <!-- Card 2: Total Revenue Today -->
    <div class="bg-white p-4 rounded-lg snooker-shadow card-glow border-t-4 border-green-500">
        <p class="text-xs text-gray-500 font-medium">Revenue Today</p>
        
        <p class="text-3xl font-extrabold text-green-700 mt-1">
            <?php 
                $formatted_revenue = number_format($total_revenue_today, 2);
                $parts = explode('.', $formatted_revenue);
                echo $currency_symbol . $parts[0];
            ?>
            <span class="text-xl">.<?php echo $parts[1]; ?></span>
        </p>
        
        <p class="text-[10px] text-gray-400 mt-1">
            Target: <?php echo $currency_symbol . number_format($daily_target, 2); ?>
        </p>
    </div>

    <!-- Card 3: Upcoming Bookings -->
    <div class="bg-white p-4 rounded-lg shadow-lg card-glow border-t-4 border-blue-500">
        <p class="text-xs text-gray-500 font-medium">Upcoming Bookings</p>
        
        <p class="text-3xl font-extrabold text-blue-700 mt-1"><?php echo $upcoming_bookings_count; ?></p>
        
        <p class="text-[10px] text-gray-400 mt-1">For the next 24 hours</p>
    </div>

    <!-- Card 4: All Users -->
    <div class="bg-white p-3 rounded-lg snooker-shadow card-glow border-t-4 border-purple-500">
        <p class="text-xs text-gray-500 font-medium">All Users</p>
        
        <p class="text-3xl font-extrabold text-purple-700 mt-1"><?php echo $total_users; ?></p>
        
        <p class="text-[10px] text-gray-400 mt-1">Active sign-ups</p>
    </div>
</div>



                <!-- 2. Detailed Table Status Table -->
              <div class="bg-white rounded-xl p-6 snooker-shadow mt-2 ">

    <!-- Header -->
    <div class="flex justify-between items-center mb-9 ">
        <h2 class="text-2xl font-bold text-snooker-green">Tables Management</h2>
<!-- Replace this button -->
<button onclick="openAddTableModal()" 
   class="px-4 py-2 bg-orange-600 text-white rounded-lg font-semibold hover:bg-green-700 transition">
    + Add Table
</button>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300 rounded-lg">
            
            <!-- Table Head -->
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 border-b text-left font-semibold text-gray-600">Table Name</th>
                    <th class="px-6 py-3 border-b text-left font-semibold text-gray-600">Rate / Hour</th>
                    <th class="px-6 py-3 border-b text-left font-semibold text-gray-600">Century Rate </th>
                    
                    <th class="px-6 py-3 border-b text-left font-semibold text-gray-600">Status</th>
                
                 <th class="px-6 py-3 border-b text-left font-semibold text-gray-600" >Edit</th>
                 <th class="px-6 py-3 border-b text-left font-semibold text-gray-600">Action</th>
                </tr>

            </thead>

            <!-- Table Body -->
            <tbody>
<?php
include 'db.php';

$sql = "SELECT * FROM snooker_tables ORDER BY id ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $table_id = $row['id'];
        $status = $row['status'];

        // --- 1. Determine Status Badge ---
        $status_badge = '';
        if ($status == "Occupied") {
            // Check for the active session ID needed for the link
            $session_stmt = $conn->prepare("SELECT session_id FROM snooker_sessions WHERE id = ? AND status = 'Active'");
            $session_stmt->bind_param("i", $table_id);
            $session_stmt->execute();
            $session_result = $session_stmt->get_result();
            $session_data = $session_result->fetch_assoc();
            $session_id = $session_data['session_id'] ?? null;
            $session_stmt->close();
            
            $status_badge = '<span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded">Occupied</span>';
            
            // --- 2. Action Button for OCCUPIED Table ---
            $action_button = '
                <a href="table_view.php?table_id=' . $table_id . '&session_id=' . $session_id . '" 
                   class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600 transition">
                    Manage Session
                </a>';
            
        } else {
            // Status is 'Free'
            $status_badge = '<span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-bold rounded">Free</span>';
            
            // --- 3. Action Button for FREE Table ---
            $action_button = '
                <form action="start_session.php" method="POST">
                    <input type="hidden" name="table_id" value="' . $table_id . '">
                    <button type="submit" name="start_session"
                            class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-900 transition">
                        Start Session
                    </button>
                </form>';
        }
        
        // Use htmlspecialchars() for security when echoing user-input data
        ?>
        <tr>
            <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($row['table_name']); ?></td>
            <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($row['rate_per_hour']); ?> PKR</td>
            <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($row['century_rate']); ?> PKR</td>
            <td class="px-6 py-4 text-gray-700 "><?php echo $status_badge; ?></td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
        <a href="view_tables.php?id=<?php echo $row['id']; ?>" class="text-indigo-600 hover:text-indigo-900 transition duration-150 ease-in-out" title="Edit">
            ✏️ Edit
        </a>
    </td>
            <td class="px-6 py-4 text-gray-700" title="start session">
                <?php echo $action_button; ?>
            </td>
        </tr>
        <?php
    }
} else {
    // ... (No tables added yet code remains the same)
    ?>
    <tr>
        <td colspan="5" class="text-center py-4 text-gray-500">
            No Tables Added Yet
        </td>
    </tr>
    <?php
}
?>

            </tbody>    
        </table>
    </div>

</div>


            </div>
        </main>
    </div>
 
<!-- Add Table Modal -->
<div id="addTableModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-2xl font-bold text-gray-800">➕ Add New Snooker Table</h3>
                <button onclick="closeAddTableModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Message Display -->
            <div id="addTableMessageContainer" class="mb-4 hidden"></div>
            
            <!-- Form -->
            <form id="addTableForm" class="space-y-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Table Name</label>
                    <input type="text" name="table_name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-600">
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Rate (per hour)</label>
                    <input type="number" name="rate_hour" step="0.01" required
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-600">
                </div>
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Century Rate (per minute)</label>
                    <input type="number" name="century_rate" step="0.01" required
                           class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-600">
                </div>
                
                <div class="flex justify-between pt-4 border-t">
                    <button type="button" onclick="closeAddTableModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-900 text-white rounded hover:bg-blue-700 transition">
                        Save Table
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
   <script>
       
            // Function to update the clock
            const updateClock = () => {
                const now = new Date();
                const timeOptions = { hour: '2-digit', minute: '2-digit', hour12: true };
                const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };

                document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', timeOptions);
                document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', dateOptions);
            };

            // Initialize clock and set interval
            updateClock();
            setInterval(updateClock, 60000); // Update every minute

    </script>
    <script>
    // --- Function to Fetch and Update Data ---
    function updateDashboardData() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'fetch_data_ajax.php', true);
        xhr.onload = function () {
            if (this.status === 200) {
                try {
                    const data = JSON.parse(this.responseText);
                    
                    // 1. Update the Active Tables Card
                    document.getElementById('active-count-display').textContent = data.active_tables;
                    document.getElementById('total-count-display').textContent = data.total_tables;
                    document.getElementById('availability-percent-display').textContent = data.availability_percent;
                    document.getElementById('free-count-display').textContent = data.free_tables;

                    // 2. Update the main table list (if you included the HTML in the AJAX response)
                    // document.getElementById('snooker-table-body').innerHTML = data.table_list_html;
                    
                    console.log('Dashboard data updated successfully.');

                } catch (e) {
                    console.error('Error parsing JSON response:', e);
                }
            } else {
                console.error('AJAX request failed. Status:', this.status);
            }
        };
        xhr.send();
    }

    // --- Start the Polling ---
    // Update the data every 5 seconds (5000 milliseconds)
    setInterval(updateDashboardData, 5000); 

    // Run once on page load
    updateDashboardData();
    function createToaster(config) {
    return function(notification) {
        let div = document.createElement("div");
        div.className = `fixed ${config.theme === "dark" ? "bg-gray-800 text-white":"bg-grey-100 text-black"}  px-6 py-3 rounded shadow-lg pointer-events-none 
            ${config.positionX === "right" ? "right-10" : "left-10"} 
            ${config.positionY === "top" ? "top-10" : "bottom-10"}`;
        div.textContent = notification;
        document.body.appendChild(div);
        setTimeout(() => {
            div.remove();
        }, (config.duration || 3) * 1000);
    };
}

let toaster = createToaster({
    positionX: "right",
    positionY: "bottom",
    theme:"dark",
    duration: 3
});

toaster("Software is under Amir development!");


</script>
<script>
// Add Table Modal Functions
function openAddTableModal() {
    document.getElementById('addTableModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeAddTableModal() {
    document.getElementById('addTableModal').classList.add('hidden');
    document.body.style.overflow = 'auto'; // Restore scrolling
    resetAddTableForm();
}

function resetAddTableForm() {
    document.getElementById('addTableForm').reset();
    const messageContainer = document.getElementById('addTableMessageContainer');
    messageContainer.classList.add('hidden');
    messageContainer.innerHTML = '';
}

// Handle form submission with AJAX
document.addEventListener('DOMContentLoaded', function() {
    const addTableForm = document.getElementById('addTableForm');
    if (addTableForm) {
        addTableForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = document.querySelector('#addTableForm button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = `
                <span class="flex items-center">
                    <svg class="animate-spin h-5 w-5 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                </span>
            `;
            submitBtn.disabled = true;
            
            // Get form data
            const formData = new FormData(this);
            formData.append('add_table', 'true');
            
            // Send AJAX request
            fetch('add_table.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Restore button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Show message
                const messageContainer = document.getElementById('addTableMessageContainer');
                messageContainer.classList.remove('hidden');
                
                if (data.includes('🎉') || data.includes('successfully')) {
                    messageContainer.innerHTML = `<div class="p-3 bg-green-100 text-green-700 border border-green-300 rounded">${data}</div>`;
                    
                    // Reset form and close modal after 2 seconds
                    setTimeout(() => {
                        resetAddTableForm();
                        closeAddTableModal();
                        
                        // Refresh the page to show new table
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    }, 2000);
                } else {
                    messageContainer.innerHTML = `<div class="p-3 bg-red-100 text-red-700 border border-red-300 rounded">${data}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                const messageContainer = document.getElementById('addTableMessageContainer');
                messageContainer.classList.remove('hidden');
                messageContainer.innerHTML = `<div class="p-3 bg-red-100 text-red-700 border border-red-300 rounded">Network error. Please try again.</div>`;
            });
        });
    }
    
    // Close modal when clicking outside
    const modal = document.getElementById('addTableModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target.id === 'addTableModal') {
                closeAddTableModal();
            }
        });
    }
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAddTableModal();
        }
    });
});
</script>
</body>
</html>