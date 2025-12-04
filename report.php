<?php
session_start();
include 'db.php'; 

// Basic session check (using a guest name if not logged in)
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = "SAEED GUEST"; 
}

// --- 1. Determine Report Scope ---

$report_type = $_GET['type'] ?? 'daily'; // Default to daily
$date_param = $_GET['date'] ?? date('Y-m-d'); // Default to current date

$report_title = ($report_type == 'monthly') ? 'Monthly Financial Report' : 'Daily Financial Report';
$date_display = '';
$sql_date_condition = '';
$sql_expense_condition = '';
$sql_bind_type = '';
$sql_bind_param = '';

if ($report_type == 'monthly') {
    // For monthly report (YYYY-MM)
    if (!preg_match('/^\d{4}-\d{2}$/', $date_param)) {
        $date_param = date('Y-m');
    }
    $date_display = date('F Y', strtotime($date_param . '-01'));
    $sql_date_condition = "DATE_FORMAT(end_time, '%Y-%m') = ?";
    $sql_expense_condition = "DATE_FORMAT(expanses_date, '%Y-%m') = ?";
    $sql_bind_type = 's';
    $sql_bind_param = $date_param;
    
} else { 
    // For daily report (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_param)) {
        $date_param = date('Y-m-d');
    }
    $date_display = date('D, d F Y', strtotime($date_param));
    $sql_date_condition = "DATE(end_time) = ?";
    $sql_expense_condition = "expanses_date = ?";
    $sql_bind_type = 's';
    $sql_bind_param = $date_param;
}


// --- 2. Fetch Core Financial Data (Income & Expenses) ---

$total_income = 0.00;
$total_expenses = 0.00;

// A. Fetch Total Income (from closed snooker sessions)
$stmt_income = $conn->prepare("
    SELECT SUM(final_amount) AS total_income 
    FROM snooker_sessions 
    WHERE status = 'Closed' AND $sql_date_condition
");
$stmt_income->bind_param($sql_bind_type, $sql_bind_param);
$stmt_income->execute();
$income_result = $stmt_income->get_result()->fetch_assoc();
$total_income = $income_result['total_income'] ?? 0.00;
$stmt_income->close();

// B. Fetch Total Expenses
$stmt_expenses = $conn->prepare("
    SELECT SUM(amount) AS total_expenses 
    FROM expanses 
    WHERE $sql_expense_condition
");
$stmt_expenses->bind_param($sql_bind_type, $sql_bind_param);
$stmt_expenses->execute();
$expenses_result = $stmt_expenses->get_result()->fetch_assoc();
$total_expenses = $expenses_result['total_expenses'] ?? 0.00;
$stmt_expenses->close();

$total_profit = $total_income - $total_expenses;


// --- 3. Fetch Table Usage Summary AND Graph Data ---

$table_usage = [];
$chart_labels = []; // For Table Names
$chart_data = [];   // For Table Income Values

$stmt_usage = $conn->prepare("
    SELECT 
        st.table_name,
        SUM(ss.total_time_minutes) AS total_minutes,
        SUM(ss.final_amount) AS table_income
    FROM snooker_sessions ss
    JOIN snooker_tables st ON ss.table_id = st.id
    WHERE ss.status = 'Closed' AND $sql_date_condition
    GROUP BY st.table_name
    ORDER BY table_income DESC
");
$stmt_usage->bind_param($sql_bind_type, $sql_bind_param);
$stmt_usage->execute();
$usage_result = $stmt_usage->get_result();

// Process results for both the report table and the JavaScript chart
while ($row = $usage_result->fetch_assoc()) {
    $table_usage[] = $row; // For the HTML report table
    $chart_labels[] = $row['table_name']; // For the Chart.js X-axis
    $chart_data[] = (float)$row['table_income']; // For the Chart.js Y-axis data
}

$stmt_usage->close();


// Helper function to format minutes to H:MM format for display
function format_minutes($minutes) {
    if ($minutes === null || $minutes <= 0) return '0 hrs';
    $total_hours = floor($minutes / 60);
    $remaining_minutes = $minutes % 60;
    
    $output = '';
    if ($total_hours > 0) {
        $output .= $total_hours . ' hr' . ($total_hours > 1 ? 's' : '');
    }
    if ($remaining_minutes > 0) {
        // Add a space only if hours were also included
        $output .= ($total_hours > 0 ? ' ' : '') . $remaining_minutes . ' min';
    }
    // If only minutes, ensure it shows up (e.g., 5 min)
    return trim($output) ?: '0 min';
}

// Convert PHP arrays to JSON for JavaScript consumption
$json_labels = json_encode($chart_labels);
$json_data = json_encode($chart_data);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $report_title; ?> | Snooker Club Reports</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'snooker-green': '#183a34',
                        'snooker-light': '#2a4d45',
                        'snooker-accent': '#ffb703',
                        'snooker-bg': '#f3ffec',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                },
            },
        };

        // Function to handle the date/type change
        function navigateReport(newType) {
            const url = new URL(window.location.href);
            url.searchParams.set('type', newType);
            
            // Reset date parameter based on new type for clean navigation
            if (newType === 'monthly') {
                url.searchParams.set('date', '<?php echo date('Y-m'); ?>');
            } else {
                url.searchParams.set('date', '<?php echo date('Y-m-d'); ?>');
            }
            window.location.href = url.toString();
        }
    </script>
    <style>
        .sidebar-link {
            border-left: 4px solid transparent;
            color: #ccc;
        }
        .sidebar-link:hover {
            color: white;
        }
        /* Highlight active link for reports */
        .sidebar-link[href="reports.php"] { 
            background-color: #2a4d45; 
            border-left-color: #ffb703; 
            color: white;
            font-weight: bold;
        }
        /* Style for the report buttons */
        .report-button {
            transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
        }
        /* Chart container styling */
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
            padding: 20px;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class=" min-h-screen font-sans bg-blue-100">
    
    <div class="relative min-h-screen"> 

        <?php include 'layout/sidebar.php'; ?>
        
        <?php include 'layout/header.php'; ?>

        <div class="ml-64 p-8 max-w-full">
            
            <h1 class="text-3xl font-bold mb-0 text-gray-600 border-b pb-2 text-center ">
              CLUB ALL REPORT DAILY AND MONTHLY REPOSRTS
            </h1>
              
            <h1 class="text-3xl font-bold mb-0 text-gray-600 border-b pb-2 text-center ">
              CLUB ALL REPORT DAILY AND MONTHLY REPOSRTS
            </h1>

           <div class="flex justify-center space-x-3 mb-8">
    <button onclick="navigateReport('daily')" 
        class="report-button px-5 py-2 rounded-lg font-semibold text-white 
        <?php echo $report_type == 'daily' ? 'bg-snooker-accent text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-200'; ?>">
        Daily Report
    </button>
    <button onclick="navigateReport('monthly')" 
        class="report-button px-5 py-2 rounded-lg font-semibold 
        <?php echo $report_type == 'monthly' ? 'bg-snooker-accent text-snooker-green' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-200'; ?>">
        Monthly Report
    </button>
    <button class="report-button px-5 py-2 rounded-lg font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-200 cursor-default">
        Tables Usage
    </button>
</div>


            <form method="GET" class="mb-10 p-4 bg-white rounded-xl shadow-md border-t-4 border-snooker-green flex items-end space-x-4">
                <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                
                <div class="flex-grow">
                    <label for="date_input" class="block text-sm font-medium text-gray-700 mb-1">
                        Viewing Report for: (<?php echo $report_type == 'monthly' ? 'Month' : 'Date'; ?>)
                    </label>
                    <input type="<?php echo $report_type == 'monthly' ? 'month' : 'date'; ?>" 
                            name="date" id="date_input" 
                            value="<?php echo htmlspecialchars($date_param); ?>" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-snooker-accent transition">
                </div>
                
                <button type="submit"
                        class="px-6 py-2 bg-snooker-green text-white font-bold rounded-lg hover:bg-snooker-light transition shadow-md">
                    View
                </button>
            </form>


            <div class="bg-white shadow-xl p-6 rounded-xl mb-10 border border-gray-200">
                <h3 class="text-xl font-bold text-gray-800 mb-5 border-b pb-3">
                    Financial Summary for <?php echo $date_display; ?>
                </h3>
                

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-600">
                        <p class="text-sm text-gray-600 font-medium">Total Income:</p>
                        <p class="text-2xl font-extrabold text-green-700 mt-1">
                            PKR <?php echo number_format($total_income, 2); ?>
                        </p>
                    </div>
                    
                    <div class="bg-red-50 p-4 rounded-lg border-l-4 border-red-600">
                        <p class="text-sm text-gray-600 font-medium">Total Expenses:</p>
                        <p class="text-2xl font-extrabold text-red-700 mt-1">
                            PKR <?php echo number_format($total_expenses, 2); ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-lg border-l-4 
                        <?php echo $total_profit >= 0 ? 'bg-blue-50 border-blue-600' : 'bg-yellow-50 border-yellow-600'; ?>">
                        <p class="text-sm font-medium text-gray-600">Profit:</p>
                        <p class="text-2xl font-extrabold mt-1 
                            <?php echo $total_profit >= 0 ? 'text-blue-800' : 'text-yellow-800'; ?>">
                            PKR <?php echo number_format($total_profit, 2); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-xl p-6 rounded-xl mb-10 border border-gray-200">
                <h3 class="text-xl font-bold text-gray-800 mb-5 border-b pb-3">
                    Table Income Distribution
                </h3>
                <p class="text-sm text-gray-500 mb-4">
                    Visual summary of income generated by each snooker table for <?php echo $date_display; ?>.
                </p>

                <div class="chart-container">
                    <canvas id="tableIncomeChart"></canvas>
                </div>
            </div>

            <div class="bg-white shadow-xl p-6 rounded-xl border border-gray-200">
                <h3 class="text-xl font-bold text-gray-800 mb-5 border-b pb-3">
                    Table Usage Summary
                </h3>
  <div class="space-y-3">
                    <?php if (!empty($table_usage)): ?>
                        <?php foreach ($table_usage as $usage): ?>
                            <div class="p-3 rounded-lg bg-gray-50 border-l-4 border-snooker-accent flex justify-between items-center transition hover:bg-gray-100">
                                <p class="text-lg font-semibold text-gray-700">
                                    <?php echo htmlspecialchars($usage['table_name']); ?>
                                </p>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">
                                        Usage: <span class="font-bold text-snooker-green"><?php echo format_minutes($usage['total_minutes']); ?></span>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        Income: <span class="font-bold text-blue-700">PKR <?php echo number_format($usage['table_income'], 0); ?></span>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-4 text-center text-lg text-gray-500 bg-gray-50 rounded-lg italic border border-dashed border-gray-300">
                            No closed table sessions found for this period (<?php echo $date_display; ?>).
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        
    </div>

    <script>
        // Get the PHP data that was converted to JSON
        const chartLabels = <?php echo $json_labels; ?>;
        const chartData = <?php echo $json_data; ?>;

        const ctx = document.getElementById('tableIncomeChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'bar', // Bar chart is best for comparing discrete table income
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Income (PKR)',
                    data: chartData,
                    backgroundColor: 'rgba(24, 58, 52, 0.8)', // snooker-green with transparency
                    borderColor: 'rgba(24, 58, 52, 1)',
                    borderWidth: 1,
                    hoverBackgroundColor: 'rgba(255, 183, 3, 1)', // snooker-accent on hover
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Total Income (PKR)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Snooker Table'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Table Income Comparison'
                    }
                }
            }
        });
    </script>
</body>
</html>