<?php
session_start();
include 'db.php'; 

// Basic session check
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = "SAEED GUEST"; 
}

// --- 1. Determine Report Scope ---
$report_type = $_GET['type'] ?? 'daily'; // Default to daily
$date_param = $_GET['date'] ?? date('Y-m-d'); // Default to current date

$report_title = ($report_type == 'monthly') ? 'Monthly Financial Report' : 'Daily Financial Report';
$date_display = '';
$sql_date_condition = '';
$sql_bind_type = '';
$sql_bind_param = '';

if ($report_type == 'monthly') {
    // For monthly report (YYYY-MM)
    if (!preg_match('/^\d{4}-\d{2}$/', $date_param)) {
        $date_param = date('Y-m');
    }
    $date_display = date('F Y', strtotime($date_param . '-01'));
    $sql_date_condition = "DATE_FORMAT(end_time, '%Y-%m') = ?";
    $sql_bind_type = 's';
    $sql_bind_param = $date_param;
    $expense_date_condition = "DATE_FORMAT(expanses_date, '%Y-%m') = ?";
    
} else { 
    // For daily report (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_param)) {
        $date_param = date('Y-m-d');
    }
    $date_display = date('D, d F Y', strtotime($date_param));
    $sql_date_condition = "DATE(end_time) = ?";
    $sql_bind_type = 's';
    $sql_bind_param = $date_param;
    $expense_date_condition = "expanses_date = ?";
}

// --- 2. Fetch Core Financial Data ---
$total_income = 0.00;
$total_expenses = 0.00;
$total_sessions = 0;
$real_data_exists = false;

// A. Fetch Total Income (from closed snooker sessions)
$stmt_income = $conn->prepare("
    SELECT COUNT(*) as total_sessions, SUM(session_cost) AS total_income 
    FROM snooker_sessions 
    WHERE status = 'Completed' AND $sql_date_condition
");
if ($stmt_income) {
    $stmt_income->bind_param($sql_bind_type, $sql_bind_param);
    $stmt_income->execute();
    $income_result = $stmt_income->get_result()->fetch_assoc();
    $total_income = $income_result['total_income'] ?? 0.00;
    $total_sessions = $income_result['total_sessions'] ?? 0;
    $stmt_income->close();
    
    if ($total_sessions > 0) {
        $real_data_exists = true;
    }
}

// B. Fetch Total Expenses with Category Names
$total_expenses = 0.00;
$expense_categories = [];

// Check if expanses table exists
$check_expanses = $conn->query("SHOW TABLES LIKE 'expanses'");
if ($check_expanses->num_rows > 0) {
    // Check if expanses_category table exists
    $check_cat = $conn->query("SHOW TABLES LIKE 'expanses_category'");
    
    if ($check_cat->num_rows > 0) {
        // Join with category table to get category names
        $stmt_expenses = $conn->prepare("
            SELECT 
                ec.category_name,
                SUM(e.amount) as total_amount
            FROM expanses e
            LEFT JOIN expanses_category ec ON e.category_id = ec.category_id
            WHERE $expense_date_condition
            GROUP BY e.category_id, ec.category_name
            ORDER BY total_amount DESC
        ");
    } else {
        // Use just the expanses table with category_id
        $stmt_expenses = $conn->prepare("
            SELECT 
                CONCAT('Category ', category_id) as category_name,
                SUM(amount) as total_amount
            FROM expanses 
            WHERE $expense_date_condition
            GROUP BY category_id
            ORDER BY total_amount DESC
        ");
    }
    
    if ($stmt_expenses) {
        $stmt_expenses->bind_param($sql_bind_type, $sql_bind_param);
        $stmt_expenses->execute();
        $expenses_result = $stmt_expenses->get_result();
        
        while ($row = $expenses_result->fetch_assoc()) {
            $expense_categories[] = [
                'category' => $row['category_name'],
                'total' => (float)$row['total_amount']
            ];
            $total_expenses += (float)$row['total_amount'];
        }
        $stmt_expenses->close();
    }
}

$total_profit = $total_income - $total_expenses;

// --- 3. Fetch Table Usage Summary ---
$table_usage = [];
$chart_labels = [];
$chart_data = [];
$usage_chart_labels = [];
$usage_chart_data = [];

// Check if snooker_tables table exists
$check_tables = $conn->query("SHOW TABLES LIKE 'snooker_tables'");

if ($check_tables->num_rows > 0) {
    // Join with snooker_tables table
    $stmt_usage = $conn->prepare("
        SELECT 
            st.table_name,
            COALESCE(SUM(TIMESTAMPDIFF(MINUTE, ss.start_time, ss.end_time)), 0) AS total_minutes,
            COUNT(ss.session_id) as total_sessions,
            COALESCE(SUM(ss.session_cost), 0) AS table_income
        FROM snooker_sessions ss
        LEFT JOIN snooker_tables st ON ss.table_id = st.id
        WHERE ss.status = 'Completed' AND $sql_date_condition
        GROUP BY st.table_name
        ORDER BY table_income DESC
    ");
} else {
    // Get data from sessions only
    $stmt_usage = $conn->prepare("
        SELECT 
            CONCAT('Table ', table_id) as table_name,
            COALESCE(SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)), 0) AS total_minutes,
            COUNT(session_id) as total_sessions,
            COALESCE(SUM(session_cost), 0) AS table_income
        FROM snooker_sessions 
        WHERE status = 'Completed' AND $sql_date_condition
        GROUP BY table_id
        ORDER BY table_income DESC
    ");
}

if ($stmt_usage) {
    $stmt_usage->bind_param($sql_bind_type, $sql_bind_param);
    $stmt_usage->execute();
    $usage_result = $stmt_usage->get_result();
    
    while ($row = $usage_result->fetch_assoc()) {
        $table_usage[] = $row;
        $chart_labels[] = $row['table_name'];
        $chart_data[] = (float)$row['table_income'];
        $usage_chart_labels[] = $row['table_name'];
        $usage_chart_data[] = (float)$row['total_sessions'];
    }
    $stmt_usage->close();
}

// --- 4. Fetch Peak Hours Data ---
$peak_hours = [];
$peak_hour_labels = [];
$peak_hour_data = [];

$stmt_peak = $conn->prepare("
    SELECT 
        HOUR(start_time) as hour,
        COUNT(*) as session_count,
        SUM(session_cost) as revenue
    FROM snooker_sessions 
    WHERE status = 'Completed' AND $sql_date_condition
    GROUP BY HOUR(start_time)
    ORDER BY hour
");
if ($stmt_peak) {
    $stmt_peak->bind_param($sql_bind_type, $sql_bind_param);
    $stmt_peak->execute();
    $peak_result = $stmt_peak->get_result();
    
    while($row = $peak_result->fetch_assoc()) {
        $peak_hours[] = $row;
        $peak_hour_labels[] = $row['hour'] . ':00';
        $peak_hour_data[] = (int)$row['session_count'];
    }
    $stmt_peak->close();
}

// --- 5. Fetch Top Selling Items ---
$top_items = [];
$top_item_labels = [];
$top_item_data = [];

// Check if session_items table exists
$check_items = $conn->query("SHOW TABLES LIKE 'session_items'");
if ($check_items->num_rows > 0) {
    // Use multiplication to calculate total revenue
    $stmt_items = $conn->prepare("
        SELECT 
            si.item_name,
            SUM(si.quantity) as total_quantity,
            SUM(si.quantity * si.price_per_unit) as total_revenue
        FROM session_items si
        JOIN snooker_sessions ss ON si.session_id = ss.session_id
        WHERE ss.status = 'Completed' AND $sql_date_condition
        GROUP BY si.item_name
        ORDER BY total_revenue DESC
        LIMIT 10
    ");
    
    if ($stmt_items) {
        $stmt_items->bind_param($sql_bind_type, $sql_bind_param);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();
        
        while($row = $items_result->fetch_assoc()) {
            $top_items[] = $row;
            $top_item_labels[] = $row['item_name'];
            $top_item_data[] = (float)$row['total_revenue'];
        }
        $stmt_items->close();
    }
}

// --- 6. Add Sample Data if No Real Data Exists ---
if (!$real_data_exists) {
    // Sample table data
    $sample_tables = ['Table 1', 'Table 2', 'Table 3', 'Table 4', 'Table 5'];
    $sample_income = [2500, 1800, 3200, 1500, 2700];
    $sample_sessions = [8, 6, 10, 5, 9];
    
    foreach($sample_tables as $index => $table) {
        $table_usage[] = [
            'table_name' => $table,
            'total_minutes' => $sample_sessions[$index] * 60,
            'total_sessions' => $sample_sessions[$index],
            'table_income' => $sample_income[$index]
        ];
        $chart_labels[] = $table;
        $chart_data[] = $sample_income[$index];
        $usage_chart_labels[] = $table;
        $usage_chart_data[] = $sample_sessions[$index];
    }
    
    // Sample peak hours
    $sample_hours = ['10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'];
    $sample_peak_data = [3, 5, 8, 10, 12, 9, 4];
    
    foreach($sample_hours as $index => $hour) {
        $peak_hours[] = [
            'hour' => (int)str_replace(':00', '', $hour),
            'session_count' => $sample_peak_data[$index],
            'revenue' => $sample_peak_data[$index] * 250
        ];
        $peak_hour_labels[] = $hour;
        $peak_hour_data[] = $sample_peak_data[$index];
    }
    
    // Sample items
    $sample_items = ['Coke', 'Water', 'Chips', 'Coffee', 'Snickers'];
    $sample_item_sales = [450, 300, 280, 200, 180];
    
    foreach($sample_items as $index => $item) {
        $top_items[] = [
            'item_name' => $item,
            'total_quantity' => ceil($sample_item_sales[$index] / 50),
            'total_revenue' => $sample_item_sales[$index]
        ];
        $top_item_labels[] = $item;
        $top_item_data[] = $sample_item_sales[$index];
    }
    
    // Sample expense categories if none exist
    if (empty($expense_categories)) {
        $expense_categories = [
            ['category' => 'Electricity', 'total' => 1500],
            ['category' => 'Staff', 'total' => 8000],
            ['category' => 'Maintenance', 'total' => 2000],
            ['category' => 'Supplies', 'total' => 1200]
        ];
        $total_expenses = 1500 + 8000 + 2000 + 1200;
    }
    
    // Sample income if none exists
    if ($total_income == 0) {
        $total_income = 25000;
        $total_sessions = 42;
    }
    
    $total_profit = $total_income - $total_expenses;
}

// Helper function to format minutes
function format_minutes($minutes) {
    if ($minutes === null || $minutes <= 0) return '0 hrs';
    $total_hours = floor($minutes / 60);
    $remaining_minutes = $minutes % 60;
    
    $output = '';
    if ($total_hours > 0) {
        $output .= $total_hours . ' hr' . ($total_hours > 1 ? 's' : '');
    }
    if ($remaining_minutes > 0) {
        $output .= ($total_hours > 0 ? ' ' : '') . $remaining_minutes . ' min';
    }
    return trim($output) ?: '0 min';
}

// Convert to JSON for JavaScript
$json_labels = json_encode($chart_labels);
$json_data = json_encode($chart_data);
$json_usage_labels = json_encode($usage_chart_labels);
$json_usage_data = json_encode($usage_chart_data);
$json_peak_labels = json_encode($peak_hour_labels);
$json_peak_data = json_encode($peak_hour_data);
$json_item_labels = json_encode($top_item_labels);
$json_item_data = json_encode($top_item_data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $report_title; ?> | Snooker Club Reports</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'snooker-green': '#183a34',
                        'snooker-light': '#2a4d45',
                        'snooker-accent': '#ffb703',
                        'snooker-bg': '#f3ffec',
                        'profit-green': '#10b981',
                        'expense-red': '#ef4444',
                        'income-blue': '#3b82f6'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <style>
        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
            background: white;
            border-radius: 0.75rem;
        }
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background: #e5e7eb;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 1s ease-in-out;
        }
        .table-row:hover {
            background-color: #f9fafb;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    
    <!-- Dashboard Container -->
    <div class="flex min-h-screen">
        
        <!-- Sidebar -->
        <?php include 'layout/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 ml-0 lg:ml-64 pt-20 p-6 main-content">
            
            <!-- Header -->
            <?php include "layout/header.php"; ?>
            
            <!-- Page Content -->
            <div class="space-y-6">
                
                <!-- Page Header -->
                <div class="bg-white rounded-xl shadow-md p-6 ">
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800">
                                <i class="fas fa-chart-line text-snooker-accent mr-2"></i>
                                Club Financial Reports
                            </h1>
                            <p class="text-gray-600 mt-2">
                                Daily and Monthly Reports for Snooker Club Management
                            </p>
                        </div>
                        <div class="flex items-center space-x-2 mt-4 md:mt-0">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                <i class="fas fa-user mr-1"></i>
                                <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                            </span>
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                                <i class="far fa-calendar mr-1"></i>
                                <?php echo $report_type == 'monthly' ? 'Monthly' : 'Daily'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Report Type Selector -->
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex flex-wrap justify-center gap-3">
                        <button onclick="navigateReport('daily')" 
                                class="px-6 py-3 rounded-lg font-semibold transition-all duration-300
                                <?php echo $report_type == 'daily' ? 'bg-snooker-green text-white shadow-lg' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            <i class="fas fa-sun mr-2"></i>Daily Report
                        </button>
                        <button onclick="navigateReport('monthly')" 
                                class="px-6 py-3 rounded-lg font-semibold transition-all duration-300
                                <?php echo $report_type == 'monthly' ? 'bg-snooker-green text-white shadow-lg' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            <i class="fas fa-calendar-alt mr-2"></i>Monthly Report
                        </button>
                        <button class="px-6 py-3 rounded-lg font-semibold bg-snooker-accent text-snooker-green">
                            <i class="fas fa-table-tennis mr-2"></i>Tables Usage
                        </button>
                        <button class="px-6 py-3 rounded-lg font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200">
                            <i class="fas fa-history mr-2"></i>Historical Data
                        </button>
                    </div>
                </div>
                
                <!-- Date Selector -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <form method="GET" class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0 md:space-x-4">
                        <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                        
                        <div class="w-full md:w-auto">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                <i class="far fa-calendar-check mr-2 text-snooker-accent"></i>
                                View Report for:
                            </h3>
                            <div class="flex items-center space-x-3">
                                <input type="<?php echo $report_type == 'monthly' ? 'month' : 'date'; ?>" 
                                       name="date" id="date_input" 
                                       value="<?php echo htmlspecialchars($date_param); ?>" required
                                       class="px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-snooker-accent focus:border-transparent w-full md:w-auto">
                                <button type="submit"
                                        class="px-6 py-3 bg-snooker-green text-white font-bold rounded-lg hover:bg-snooker-light transition shadow-md whitespace-nowrap">
                                    <i class="fas fa-eye mr-2"></i>View Report
                                </button>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">
                                Currently viewing: <span class="font-semibold text-snooker-green"><?php echo $date_display; ?></span>
                            </p>
                        </div>
                        
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Report Generated:</p>
                            <p class="text-lg font-bold text-gray-800">
                                <?php echo date('h:i A'); ?>
                            </p>
                        </div>
                    </form>
                </div>
                
                <!-- Financial Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Income Card -->
                    <div class="stat-card bg-white rounded-xl shadow-md p-6 border-t-4 border-income-blue">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-blue-50 rounded-lg">
                                <i class="fas fa-money-bill-wave text-income-blue text-2xl"></i>
                            </div>
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                <?php echo $total_sessions; ?> sessions
                            </span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-600">Total Income</h3>
                        <p class="text-3xl font-bold text-income-blue mt-2">
                            PKR <?php echo number_format($total_income, 2); ?>
                        </p>
                        <div class="mt-4">
                            <div class="progress-bar">
                                <div class="progress-fill bg-income-blue" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Expenses Card -->
                    <div class="stat-card bg-white rounded-xl shadow-md p-6 border-t-4 border-expense-red">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-red-50 rounded-lg">
                                <i class="fas fa-receipt text-expense-red text-2xl"></i>
                            </div>
                            <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
                                <?php echo count($expense_categories); ?> categories
                            </span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-600">Total Expenses</h3>
                        <p class="text-3xl font-bold text-expense-red mt-2">
                            PKR <?php echo number_format($total_expenses, 2); ?>
                        </p>
                        <div class="mt-4">
                            <div class="progress-bar">
                                <div class="progress-fill bg-expense-red" 
                                     style="width: <?php echo $total_income > 0 ? min(100, ($total_expenses / $total_income) * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profit Card -->
                    <div class="stat-card bg-white rounded-xl shadow-md p-6 border-t-4 border-profit-green">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-green-50 rounded-lg">
                                <i class="fas fa-chart-line text-profit-green text-2xl"></i>
                            </div>
                            <?php if($total_profit >= 0): ?>
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                                    <i class="fas fa-arrow-up mr-1"></i>Profit
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                                    <i class="fas fa-arrow-down mr-1"></i>Loss
                                </span>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-600">Net Profit</h3>
                        <p class="text-3xl font-bold <?php echo $total_profit >= 0 ? 'text-profit-green' : 'text-yellow-600'; ?> mt-2">
                            PKR <?php echo number_format($total_profit, 2); ?>
                        </p>
                        <div class="mt-4">
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $total_profit >= 0 ? 'bg-profit-green' : 'bg-yellow-500'; ?>" 
                                     style="width: <?php echo $total_income > 0 ? min(100, (abs($total_profit) / $total_income) * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profit Margin Card -->
                    <div class="stat-card bg-white rounded-xl shadow-md p-6 border-t-4 border-purple-500">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-purple-50 rounded-lg">
                                <i class="fas fa-percentage text-purple-500 text-2xl"></i>
                            </div>
                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">
                                Margin
                            </span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-600">Profit Margin</h3>
                        <p class="text-3xl font-bold text-purple-600 mt-2">
                            <?php 
                                if($total_income > 0) {
                                    echo number_format(($total_profit / $total_income) * 100, 1);
                                } else {
                                    echo "0.0";
                                }
                            ?>%
                        </p>
                        <div class="mt-4">
                            <div class="progress-bar">
                                <div class="progress-fill bg-purple-500" 
                                     style="width: <?php echo $total_income > 0 ? max(0, min(100, ($total_profit / $total_income) * 100)) : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Table Income Chart -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-800">
                                <i class="fas fa-chart-bar text-snooker-accent mr-2"></i>
                                Table Income Distribution
                            </h3>
                            <div class="flex space-x-2">
                                <button class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-sm">
                                    <i class="fas fa-download mr-1"></i>Export
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="tableIncomeChart"></canvas>
                        </div>
                        <p class="text-sm text-gray-500 mt-4 text-center">
                            Income generated by each table for <?php echo $date_display; ?>
                        </p>
                    </div>
                    
                    <!-- Table Usage Pie Chart -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-800">
                                <i class="fas fa-chart-pie text-snooker-accent mr-2"></i>
                                Table Sessions Distribution
                            </h3>
                            <div class="flex space-x-2">
                                <button class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-sm">
                                    <i class="fas fa-filter mr-1"></i>Filter
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="tableUsageChart"></canvas>
                        </div>
                        <p class="text-sm text-gray-500 mt-4 text-center">
                            Number of sessions per table for <?php echo $date_display; ?>
                        </p>
                    </div>
                </div>
                
                <!-- Additional Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Peak Hours Chart -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-800">
                                <i class="fas fa-clock text-snooker-accent mr-2"></i>
                                Peak Hours Analysis
                            </h3>
                            <div class="flex space-x-2">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                    Busiest: <?php 
                                        if (!empty($peak_hour_data)) {
                                            $busiest = max($peak_hour_data);
                                            $busiest_index = array_search($busiest, $peak_hour_data);
                                            echo $peak_hour_labels[$busiest_index] ?? 'N/A';
                                        } else {
                                            echo 'No data';
                                        }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="peakHoursChart"></canvas>
                        </div>
                        <p class="text-sm text-gray-500 mt-4 text-center">
                            Session distribution throughout the day
                        </p>
                    </div>
                    
                    <!-- Top Selling Items -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-800">
                                <i class="fas fa-shopping-cart text-snooker-accent mr-2"></i>
                                Top Selling Items
                            </h3>
                            <div class="flex space-x-2">
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                                    Top: <?php echo $top_items[0]['item_name'] ?? 'No data'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="topItemsChart"></canvas>
                        </div>
                        <p class="text-sm text-gray-500 mt-4 text-center">
                            Revenue from food and beverage items
                        </p>
                    </div>
                </div>
                
                <!-- Tables Usage Details -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-table mr-2 text-snooker-accent"></i>
                            Tables Usage Summary
                        </h3>
                        <div class="flex space-x-2">
                            <button class="px-4 py-2 bg-snooker-green text-white rounded-lg text-sm font-semibold hover:bg-snooker-light transition">
                                <i class="fas fa-print mr-1"></i>Print Report
                            </button>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Table Name</th>
                                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Sessions</th>
                                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Usage Time</th>
                                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Income</th>
                                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Avg. Session</th>
                                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($table_usage)): ?>
                                    <?php 
                                        $max_income = max(array_column($table_usage, 'table_income'));
                                        foreach ($table_usage as $usage): 
                                            $percentage = $max_income > 0 ? ($usage['table_income'] / $max_income) * 100 : 0;
                                            $avg_minutes = $usage['total_sessions'] > 0 ? $usage['total_minutes'] / $usage['total_sessions'] : 0;
                                    ?>
                                        <tr class="table-row border-b hover:bg-gray-50 transition">
                                            <td class="py-3 px-4">
                                                <div class="flex items-center">
                                                    <div class="w-8 h-8 bg-snooker-green rounded-lg flex items-center justify-center mr-3">
                                                        <i class="fas fa-table text-white text-sm"></i>
                                                    </div>
                                                    <span class="font-medium text-gray-800"><?php echo htmlspecialchars($usage['table_name']); ?></span>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                                    <?php echo $usage['total_sessions']; ?> sessions
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-medium text-gray-700"><?php echo format_minutes($usage['total_minutes']); ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-bold text-green-700">PKR <?php echo number_format($usage['table_income'], 2); ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="text-gray-600"><?php echo format_minutes($avg_minutes); ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex items-center">
                                                    <div class="w-32 bg-gray-200 rounded-full h-2.5 mr-2">
                                                        <div class="bg-green-600 h-2.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                                    </div>
                                                    <span class="text-sm text-gray-600"><?php echo number_format($percentage, 1); ?>%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <i class="fas fa-table text-gray-300 text-4xl mb-3"></i>
                                                <p class="text-lg">No table usage data available for this period.</p>
                                                <p class="text-sm mt-1">Try selecting a different date or month.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (!empty($table_usage)): ?>
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex flex-wrap justify-between items-center">
                                <div class="mb-4 md:mb-0">
                                    <p class="text-sm text-gray-600">Summary Statistics:</p>
                                    <p class="text-sm text-gray-800">
                                        Total Tables: <span class="font-bold"><?php echo count($table_usage); ?></span> | 
                                        Total Sessions: <span class="font-bold"><?php echo array_sum(array_column($table_usage, 'total_sessions')); ?></span> | 
                                        Total Income: <span class="font-bold text-green-700">PKR <?php echo number_format(array_sum(array_column($table_usage, 'table_income')), 2); ?></span>
                                    </p>
                                </div>
                                <div>
                                    <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                                        <i class="fas fa-download mr-1"></i>Export as CSV
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Expense Categories -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-list-alt mr-2 text-snooker-accent"></i>
                        Expense Categories Breakdown
                    </h3>
                    
                    <?php if (!empty($expense_categories)): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <?php foreach($expense_categories as $expense): ?>
                                <div class="bg-gray-50 p-4 rounded-lg border-l-4 border-red-500">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($expense['category']); ?></h4>
                                        <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">
                                            <?php echo number_format($expense['total'], 0); ?> PKR
                                        </span>
                                    </div>
                                    <p class="text-2xl font-bold text-red-600">PKR <?php echo number_format($expense['total'], 2); ?></p>
                                    <div class="mt-3">
                                        <div class="progress-bar">
                                            <div class="progress-fill bg-red-500" 
                                                 style="width: <?php echo $total_expenses > 0 ? ($expense['total'] / $total_expenses) * 100 : 0; ?>%"></div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?php echo $total_expenses > 0 ? number_format(($expense['total'] / $total_expenses) * 100, 1) : 0; ?>% of total expenses
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-receipt text-4xl mb-3 text-gray-300"></i>
                            <p class="text-lg">No expense data available for this period.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Footer Note -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="text-center">
                        <p class="text-gray-600 mb-2">
                            <i class="fas fa-info-circle text-snooker-accent mr-2"></i>
                            Report generated on <?php echo date('F j, Y'); ?> at <?php echo date('h:i A'); ?>
                        </p>
                        <p class="text-sm text-gray-500">
                            Note: This report includes <?php echo $real_data_exists ? 'real data' : 'sample data'; ?> for <?php echo $date_display; ?>.
                            <?php if(!$real_data_exists): ?>
                                <span class="text-yellow-600 font-medium">No real session data found for this period. Showing sample data.</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
            </div>
            
        </main>
        
    </div>
    
    <script>
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
        
        // Initialize all charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Chart 1: Table Income Distribution (Bar Chart)
            const ctx1 = document.getElementById('tableIncomeChart').getContext('2d');
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: <?php echo $json_labels; ?>,
                    datasets: [{
                        label: 'Income (PKR)',
                        data: <?php echo $json_data; ?>,
                        backgroundColor: [
                            'rgba(24, 58, 52, 0.8)',   // snooker-green
                            'rgba(42, 77, 69, 0.8)',   // snooker-light
                            'rgba(255, 183, 3, 0.8)',  // snooker-accent
                            'rgba(59, 130, 246, 0.8)', // blue
                            'rgba(16, 185, 129, 0.8)', // green
                            'rgba(239, 68, 68, 0.8)',  // red
                            'rgba(139, 92, 246, 0.8)', // purple
                        ],
                        borderColor: [
                            'rgba(24, 58, 52, 1)',
                            'rgba(42, 77, 69, 1)',
                            'rgba(255, 183, 3, 1)',
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(239, 68, 68, 1)',
                            'rgba(139, 92, 246, 1)',
                        ],
                        borderWidth: 1,
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'PKR ' + context.raw.toLocaleString('en-PK', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Income (PKR)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'PKR ' + value.toLocaleString('en-PK');
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Tables'
                            }
                        }
                    }
                }
            });
            
            // Chart 2: Table Usage Pie Chart
            const ctx2 = document.getElementById('tableUsageChart').getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: <?php echo $json_usage_labels; ?>,
                    datasets: [{
                        data: <?php echo $json_usage_data; ?>,
                        backgroundColor: [
                            'rgba(24, 58, 52, 0.8)',
                            'rgba(42, 77, 69, 0.8)',
                            'rgba(255, 183, 3, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(139, 92, 246, 0.8)',
                        ],
                        borderColor: [
                            'rgba(24, 58, 52, 1)',
                            'rgba(42, 77, 69, 1)',
                            'rgba(255, 183, 3, 1)',
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(239, 68, 68, 1)',
                            'rgba(139, 92, 246, 1)',
                        ],
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} sessions (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Chart 3: Peak Hours Line Chart
            const ctx3 = document.getElementById('peakHoursChart').getContext('2d');
            new Chart(ctx3, {
                type: 'line',
                data: {
                    labels: <?php echo $json_peak_labels; ?>,
                    datasets: [{
                        label: 'Number of Sessions',
                        data: <?php echo $json_peak_data; ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Sessions'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Time of Day'
                            }
                        }
                    }
                }
            });
            
            // Chart 4: Top Selling Items Horizontal Bar Chart
            const ctx4 = document.getElementById('topItemsChart').getContext('2d');
            new Chart(ctx4, {
                type: 'bar',
                data: {
                    labels: <?php echo $json_item_labels; ?>,
                    datasets: [{
                        label: 'Revenue (PKR)',
                        data: <?php echo $json_item_data; ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'PKR ' + context.raw.toLocaleString('en-PK', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Revenue (PKR)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'PKR ' + value.toLocaleString('en-PK');
                                }
                            }
                        }
                    }
                }
            });
            
            // Animate progress bars on scroll
            const progressBars = document.querySelectorAll('.progress-fill');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const width = entry.target.style.width;
                        entry.target.style.width = '0%';
                        setTimeout(() => {
                            entry.target.style.width = width;
                        }, 100);
                    }
                });
            }, { threshold: 0.5 });
            
            progressBars.forEach(bar => observer.observe(bar));
        });
    </script>
</body>
</html>