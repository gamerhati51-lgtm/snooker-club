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

// A. Fetch Total Income (from completed snooker sessions)
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
    // Check if expanses_categories table exists
    $check_cat = $conn->query("SHOW TABLES LIKE 'expanses_categories'");
    
    if ($check_cat->num_rows > 0) {
        // Join with expanses_categories table to get category names
        $stmt_expenses = $conn->prepare("
            SELECT 
                ec.category_name,
                SUM(e.amount) as total_amount
            FROM expanses e
            LEFT JOIN expanses_categories ec ON e.category_id = ec.category_id
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
            p.category,
            p.selling_price,
            SUM(si.quantity) as total_quantity,
            SUM(si.quantity * si.price_per_unit) as total_revenue
        FROM session_items si
        JOIN snooker_sessions ss ON si.session_id = ss.session_id
        LEFT JOIN products p ON si.item_name = p.name
        WHERE ss.status = 'Completed' AND $sql_date_condition
        GROUP BY si.item_name, p.category, p.selling_price
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
// --- 6. Fetch Low Stock Items ---
$low_stock_items = [];

// Use products table since it has stock_quantity
$check_products = $conn->query("SHOW TABLES LIKE 'products'");
if ($check_products->num_rows > 0) {
    // Check if products table has the needed columns
    $col_check = $conn->query("SHOW COLUMNS FROM products");
    $has_stock = false;
    $has_alert = false;
    
    while($col = $col_check->fetch_assoc()) {
        if ($col['Field'] == 'stock_quantity') $has_stock = true;
        if ($col['Field'] == 'alert_quantity') $has_alert = true;
    }
    
    if ($has_stock) {
        $alert_col = $has_alert ? 'alert_quantity' : '5';
        
        $stmt_low_stock = $conn->prepare("
            SELECT 
                name as product_name,
                stock_quantity as current_stock,
                $alert_col as reorder_level,
                category,
                selling_price
            FROM products 
            WHERE is_active = 1 
                AND stock_quantity <= $alert_col
            ORDER BY stock_quantity ASC
            LIMIT 10
        ");
        
        if ($stmt_low_stock) {
            $stmt_low_stock->execute();
            $low_stock_result = $stmt_low_stock->get_result();
            while($row = $low_stock_result->fetch_assoc()) {
                $low_stock_items[] = $row;
            }
            $stmt_low_stock->close();
        }
    }
}
// --- 7. Fetch Product Performance ---
$product_performance = [];
$check_products = $conn->query("SHOW TABLES LIKE 'products'");
if ($check_products->num_rows > 0) {
    $stmt_products = $conn->prepare("
        SELECT 
            p.name as product_name,
            p.selling_price,
            p.stock_quantity,
            p.category,
            COALESCE(SUM(si.quantity), 0) as total_sold,
            COALESCE(SUM(si.quantity * si.price_per_unit), 0) as total_revenue
        FROM products p
        LEFT JOIN session_items si ON p.name = si.item_name
        LEFT JOIN snooker_sessions ss ON si.session_id = ss.session_id
            AND ss.status = 'Completed' 
            AND $sql_date_condition
        WHERE p.is_active = 1
        GROUP BY p.product_id, p.name, p.selling_price, p.stock_quantity, p.category
        ORDER BY total_revenue DESC, total_sold DESC
        LIMIT 10
    ");
    
    if ($stmt_products) {
        $stmt_products->bind_param($sql_bind_type, $sql_bind_param);
        $stmt_products->execute();
        $products_result = $stmt_products->get_result();
        while($row = $products_result->fetch_assoc()) {
            $product_performance[] = $row;
        }
        $stmt_products->close();
    }
}

// --- 8. Fetch Booking Statistics ---
$booking_stats = [];
$check_bookings = $conn->query("SHOW TABLES LIKE 'snooker_bookings'");
if ($check_bookings->num_rows > 0) {
    $daily_param = ($report_type == 'monthly') ? $date_param . '-01' : $date_param;
    
    $stmt_bookings = $conn->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
            SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_bookings
        FROM snooker_bookings 
        WHERE DATE(booking_date) = ?
    ");
    
    if ($stmt_bookings) {
        $stmt_bookings->bind_param('s', $daily_param);
        $stmt_bookings->execute();
        $booking_stats_result = $stmt_bookings->get_result();
        $booking_stats = $booking_stats_result->fetch_assoc() ?? [];
        $stmt_bookings->close();
    }
}

// --- 9. Fetch POS Sales Data ---
$pos_sales = [];
$check_pos = $conn->query("SHOW TABLES LIKE 'sales_transactions'");
if ($check_pos->num_rows > 0) {
    $stmt_pos = $conn->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            SUM(total_amount) as pos_revenue,
            AVG(total_amount) as avg_transaction
        FROM sales_transactions 
        WHERE DATE(transaction_date) = ?
    ");
    
    if ($stmt_pos) {
        $daily_param = ($report_type == 'monthly') ? $date_param . '-01' : $date_param;
        $stmt_pos->bind_param('s', $daily_param);
        $stmt_pos->execute();
        $pos_result = $stmt_pos->get_result();
        $pos_sales = $pos_result->fetch_assoc() ?? [];
        $stmt_pos->close();
    }
}

// --- 10. Fetch Purchase Costs ---
$purchase_costs = [];
$check_purchases = $conn->query("SHOW TABLES LIKE 'stock_purchases'");
if ($check_purchases->num_rows > 0) {
    $stmt_purchases = $conn->prepare("
        SELECT 
            COUNT(*) as total_purchases,
            SUM(quantity_bought * price_per_unit) as total_purchase_cost,
            SUM(quantity_bought) as total_items_purchased
        FROM stock_purchases 
        WHERE DATE(purchase_date) = ?
    ");
    
    if ($stmt_purchases) {
        $daily_param = ($report_type == 'monthly') ? $date_param . '-01' : $date_param;
        $stmt_purchases->bind_param('s', $daily_param);
        $stmt_purchases->execute();
        $purchases_result = $stmt_purchases->get_result();
        $purchase_costs = $purchases_result->fetch_assoc() ?? [];
        $stmt_purchases->close();
    }
}
// --- 11. Fetch User/Staff Performance ---
$staff_performance = [];
$check_users = $conn->query("SHOW TABLES LIKE 'users'");
if ($check_users->num_rows > 0) {
    // Try to join with sessions, fall back to just users if join fails
    try {
        $stmt_staff = $conn->prepare("
            SELECT 
                u.username,
                u.name,
                u.role,
                COUNT(ss.session_id) as sessions_handled,
                COALESCE(SUM(ss.session_cost), 0) as revenue_generated
            FROM users u
            LEFT JOIN snooker_sessions ss ON 
                (u.id = ss.staff_id OR 
                 u.id = ss.user_id OR 
                 u.id = ss.created_by OR 
                 u.id = ss.admin_id)
                AND ss.status = 'Completed' 
                AND $sql_date_condition
            WHERE u.role IN ('Admin', 'Cashier', 'Staff')
            GROUP BY u.id, u.username, u.name, u.role
            ORDER BY revenue_generated DESC
            LIMIT 5
        ");
        
        if ($stmt_staff) {
            $stmt_staff->bind_param($sql_bind_type, $sql_bind_param);
            $stmt_staff->execute();
            $staff_result = $stmt_staff->get_result();
            while($row = $staff_result->fetch_assoc()) {
                $staff_performance[] = $row;
            }
            $stmt_staff->close();
        }
    } catch (Exception $e) {
        // If join fails, just get active staff users
        $stmt_users = $conn->prepare("
            SELECT 
                username,
                name,
                role,
                0 as sessions_handled,
                0 as revenue_generated
            FROM users 
            WHERE role IN ('Admin', 'Cashier', 'Staff') 
                AND status = 'Active'
            ORDER BY name ASC
            LIMIT 5
        ");
        
        if ($stmt_users) {
            $stmt_users->execute();
            $users_result = $stmt_users->get_result();
            while($row = $users_result->fetch_assoc()) {
                $staff_performance[] = $row;
            }
            $stmt_users->close();
        }
    }
}
// --- 12. Add Sample Data if No Real Data Exists ---
if (!$real_data_exists) {
    // Check if snooker_tables table has any active tables
    $check_tables = $conn->query("SELECT COUNT(*) as table_count FROM snooker_tables WHERE is_active = 1");
    $table_count_result = $check_tables->fetch_assoc();
    $table_count = $table_count_result['table_count'] ?? 0;
    
    // If no active tables exist, create sample tables first
    if ($table_count == 0) {
        // Create sample tables
        $sample_tables_data = [
            ['Table 1', 200.00, 50.00],
            ['Table 2', 180.00, 45.00],
            ['Table 3', 220.00, 55.00],
            ['Table 4', 190.00, 48.00],
            ['Table 5', 210.00, 52.00]
        ];
        
        $insert_stmt = $conn->prepare("INSERT INTO snooker_tables (table_name, rate_per_hour, century_rate, status) VALUES (?, ?, ?, 'Free')");
        
        foreach ($sample_tables_data as $table_data) {
            $insert_stmt->bind_param("sdd", $table_data[0], $table_data[1], $table_data[2]);
            $insert_stmt->execute();
        }
        $insert_stmt->close();
    }
    
    // Now check for table usage data again
    if (empty($table_usage)) {
        // Fetch table names from database to use real table names
        $tables_stmt = $conn->query("SELECT table_name FROM snooker_tables WHERE is_active = 1 ORDER BY id LIMIT 5");
        $db_tables = [];
        while ($row = $tables_stmt->fetch_assoc()) {
            $db_tables[] = $row['table_name'];
        }
        $tables_stmt->close();
        
        // Use database table names if available, otherwise use default names
        $sample_tables = !empty($db_tables) ? $db_tables : ['Table 1', 'Table 2', 'Table 3', 'Table 4', 'Table 5'];
        $sample_income = [2500, 1800, 3200, 1500, 2700];
        $sample_sessions = [8, 6, 10, 5, 9];
        
        // Clear arrays first to avoid duplicates
        $table_usage = [];
        $chart_labels = [];
        $chart_data = [];
        $usage_chart_labels = [];
        $usage_chart_data = [];
        
        foreach($sample_tables as $index => $table) {
            $income = isset($sample_income[$index]) ? $sample_income[$index] : 1000;
            $sessions = isset($sample_sessions[$index]) ? $sample_sessions[$index] : 5;
            
            $table_usage[] = [
                'table_name' => $table,
                'total_minutes' => $sessions * 60,
                'total_sessions' => $sessions,
                'table_income' => $income
            ];
            $chart_labels[] = $table;
            $chart_data[] = $income;
            $usage_chart_labels[] = $table;
            $usage_chart_data[] = $sessions;
        }
    }
    
    // Sample peak hours (only if no peak data)
    if (empty($peak_hours)) {
        $sample_hours = ['10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'];
        $sample_peak_data = [3, 5, 8, 10, 12, 9, 4];
        
        // Clear arrays first
        $peak_hours = [];
        $peak_hour_labels = [];
        $peak_hour_data = [];
        
        foreach($sample_hours as $index => $hour) {
            $peak_hours[] = [
                'hour' => (int)str_replace(':00', '', $hour),
                'session_count' => $sample_peak_data[$index],
                'revenue' => $sample_peak_data[$index] * 250
            ];
            $peak_hour_labels[] = $hour;
            $peak_hour_data[] = $sample_peak_data[$index];
        }
    }
    
    // Only add sample items if no products table AND no session_items
    $check_products = $conn->query("SHOW TABLES LIKE 'products'");
    $check_session_items = $conn->query("SHOW TABLES LIKE 'session_items'");
    
    if ($check_products->num_rows == 0 && $check_session_items->num_rows == 0 && empty($top_items)) {
        // Clear arrays first
        $top_items = [];
        $top_item_labels = [];
        $top_item_data = [];
        
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
                        'income-blue': '#3b82f6',
                        'inventory-orange': '#f97316',
                        'booking-purple': '#8b5cf6',
                        'pos-teal': '#0d9488'
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
        .scrollable-table {
            max-height: 400px;
            overflow-y: auto;
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
                <div class="bg-white rounded-xl shadow-md p-6 border-l-8 border-snooker-green">
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800">
                                <i class="fas fa-chart-line text-snooker-accent mr-2"></i>
                                Complete Club Dashboard
                            </h1>
                            <p class="text-gray-600 mt-2">
                                Comprehensive Reports using ALL Database Tables
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
                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">
                                <i class="fas fa-database mr-1"></i>
                                All Tables
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
                
                <!-- COMPREHENSIVE FINANCIAL SUMMARY CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    
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
                        <h3 class="text-lg font-semibold text-gray-600">Total Session Income</h3>
                        <p class="text-3xl font-bold text-income-blue mt-2">
                            PKR <?php echo number_format($total_income, 2); ?>
                        </p>
                        <div class="mt-4">
                            <div class="progress-bar">
                                <div class="progress-fill bg-income-blue" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- POS Sales Card -->
                    <div class="stat-card bg-white rounded-xl shadow-md p-6 border-t-4 border-pos-teal">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-teal-50 rounded-lg">
                                <i class="fas fa-cash-register text-pos-teal text-2xl"></i>
                            </div>
                            <span class="px-3 py-1 bg-teal-100 text-teal-800 rounded-full text-sm font-medium">
                                <?php echo $pos_sales['total_transactions'] ?? 0; ?> transactions
                            </span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-600">POS Sales</h3>
                        <p class="text-3xl font-bold text-pos-teal mt-2">
                            PKR <?php echo number_format($pos_sales['pos_revenue'] ?? 0, 2); ?>
                        </p>
                        <div class="mt-4">
                            <div class="progress-bar">
                                <div class="progress-fill bg-pos-teal" style="width: 100%"></div>
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
                </div>
                
                <!-- ADDITIONAL METRICS ROW -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <!-- Booking Statistics -->
                    <div class="stat-card bg-white rounded-xl shadow-md p-6 border-t-4 border-booking-purple">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-purple-50 rounded-lg">
                                <i class="fas fa-calendar-check text-booking-purple text-2xl"></i>
                            </div>
                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">
                                Bookings
                            </span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-600">Booking Stats</h3>
                        <p class="text-2xl font-bold text-booking-purple mt-2">
                            <?php echo $booking_stats['confirmed_bookings'] ?? 0; ?> Confirmed
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            Total: <?php echo $booking_stats['total_bookings'] ?? 0; ?> | 
                            Cancelled: <?php echo $booking_stats['cancelled_bookings'] ?? 0; ?>
                        </p>
                    </div>
                    
                 <!-- Purchase Costs Card -->
<div class="stat-card bg-white rounded-xl shadow-md p-6 border-t-4 border-orange-500">
    <div class="flex items-center justify-between mb-4">
        <div class="p-3 bg-orange-50 rounded-lg">
            <i class="fas fa-shopping-cart text-orange-500 text-2xl"></i>
        </div>
        <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-medium">
            Purchases
        </span>
    </div>
    <h3 class="text-lg font-semibold text-gray-600">Stock Purchases</h3>
    <p class="text-2xl font-bold text-orange-600 mt-2">
        PKR <?php echo number_format($purchase_costs['total_purchase_cost'] ?? 0, 2); ?>
    </p>
    <p class="text-sm text-gray-600 mt-1">
        <?php echo $purchase_costs['total_purchases'] ?? 0; ?> orders • 
        <?php echo $purchase_costs['total_items_purchased'] ?? 0; ?> items
    </p>
</div>
                    <!-- Inventory Alert -->
                    <div class="stat-card bg-white rounded-xl shadow-md p-6 border-t-4 border-inventory-orange">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-orange-50 rounded-lg">
                                <i class="fas fa-boxes text-inventory-orange text-2xl"></i>
                            </div>
                            <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-medium">
                                Low Stock
                            </span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-600">Inventory Alerts</h3>
                        <p class="text-2xl font-bold text-inventory-orange mt-2">
                        Items
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            Needs restocking
                        </p>
                    </div>
                    
                    <!-- Average Transaction -->
                    <div class="stat-card bg-white rounded-xl shadow-md p-6 border-t-4 border-cyan-500">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-cyan-50 rounded-lg">
                                <i class="fas fa-chart-pie text-cyan-500 text-2xl"></i>
                            </div>
                            <span class="px-3 py-1 bg-cyan-100 text-cyan-800 rounded-full text-sm font-medium">
                                Avg. Sale
                            </span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-600">Avg Transaction</h3>
                        <p class="text-2xl font-bold text-cyan-600 mt-2">
                            PKR <?php echo number_format($pos_sales['avg_transaction'] ?? 0, 2); ?>
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            Per POS transaction
                        </p>
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
                                <span class="px-3 py-1 bg-red-100 text-green-800 rounded-full text-sm">
                                    Total: PKR <?php echo number_format(array_sum($chart_data), 2); ?>
                                </span>
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
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                    Total: <?php echo array_sum($usage_chart_data); ?> sessions
                                </span>
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
<!-- STAFF PERFORMANCE SECTION -->
<?php if (!empty($staff_performance)): ?>
<div class="bg-white rounded-xl shadow-md p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-6">
        <i class="fas fa-users mr-2 text-snooker-accent"></i>
        Staff Performance
    </h3>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Staff Member</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Role</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Sessions Handled</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Revenue Generated</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Performance</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $max_revenue = max(array_column($staff_performance, 'revenue_generated'));
                    foreach ($staff_performance as $staff): 
                        $percentage = $max_revenue > 0 ? ($staff['revenue_generated'] / $max_revenue) * 100 : 0;
                        $display_name = !empty($staff['name']) ? $staff['name'] : $staff['username'];
                ?>
                    <tr class="table-row border-b hover:bg-gray-50 transition">
                        <td class="py-3 px-4">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-user text-purple-600 text-sm"></i>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-800 block"><?php echo htmlspecialchars($display_name); ?></span>
                                    <span class="text-xs text-gray-500">@<?php echo htmlspecialchars($staff['username']); ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 
                                <?php echo $staff['role'] == 'Admin' ? 'bg-red-100 text-red-800' : 
                                       ($staff['role'] == 'Cashier' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'); ?>
                                rounded-full text-xs font-medium">
                                <?php echo $staff['role']; ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                <?php echo $staff['sessions_handled']; ?> sessions
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-green-700">PKR <?php echo number_format($staff['revenue_generated'], 2); ?></span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center">
                                <div class="w-32 bg-gray-200 rounded-full h-2.5 mr-2">
                                    <div class="bg-purple-600 h-2.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <span class="text-sm text-gray-600"><?php echo number_format($percentage, 1); ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
                <!-- Tables Usage Details -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-table mr-2 text-snooker-accent"></i>
                            Tables Usage Summary
                        </h3>
                        <div class="flex space-x-2">
                          <button onclick="printReport()" class="px-4 py-2 bg-snooker-green text-white rounded-lg text-sm font-semibold hover:bg-snooker-light transition">
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
                
                <!-- LOW STOCK ALERTS -->
                <?php if (!empty($low_stock_items)): ?>
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-exclamation-triangle mr-2 text-inventory-orange"></i>
                        Low Stock Alerts
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <?php foreach($low_stock_items as $item): ?>
                            <div class="bg-orange-50 p-4 rounded-lg border-l-4 border-orange-500">
                                <h4 class="font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-sm text-gray-600">Current Stock:</span>
                                    <span class="font-bold text-orange-600"><?php echo $item['current_stock']; ?></span>
                                </div>
                                <div class="flex justify-between items-center mt-1">
                                    <span class="text-sm text-gray-600">Reorder Level:</span>
                                    <span class="font-bold text-red-600"><?php echo $item['reorder_level']; ?></span>
                                </div>
                                <div class="mt-3">
                                    <div class="progress-bar">
                                        <div class="progress-fill bg-orange-500" 
                                             style="width: <?php echo $item['reorder_level'] > 0 ? min(100, ($item['current_stock'] / $item['reorder_level']) * 100) : 0; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
               
               <!-- PRODUCT PERFORMANCE -->
<?php if (!empty($product_performance)): ?>
<div class="bg-white rounded-xl shadow-md p-6">
    <h3 class="text-xl font-bold text-gray-800 mb-6">
        <i class="fas fa-chart-line mr-2 text-snooker-accent"></i>
        Product Performance (Real Data)
    </h3>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Product</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Category</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Price</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Stock</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Sold</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Revenue</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700 border-b">Performance</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $max_product_revenue = max(array_column($product_performance, 'total_revenue'));
                    foreach ($product_performance as $product): 
                        $percentage = $max_product_revenue > 0 ? ($product['total_revenue'] / $max_product_revenue) * 100 : 0;
                        $stock_status = $product['stock_quantity'] <= ($product['alert_quantity'] ?? 5) ? 'text-orange-600' : 'text-green-600';
                ?>
                    <tr class="table-row border-b hover:bg-gray-50 transition">
                        <td class="py-3 px-4">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-cyan-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-box text-cyan-600 text-sm"></i>
                                </div>
                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($product['product_name']); ?></span>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">
                                <?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-medium text-gray-700">PKR <?php echo number_format($product['selling_price'], 2); ?></span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 <?php echo $stock_status; ?> rounded-full text-sm font-medium">
                                <?php echo $product['stock_quantity']; ?> units
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                <?php echo $product['total_sold']; ?> sold
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-green-700">PKR <?php echo number_format($product['total_revenue'], 2); ?></span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center">
                                <div class="w-32 bg-gray-200 rounded-full h-2.5 mr-2">
                                    <div class="bg-cyan-600 h-2.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <span class="text-sm text-gray-600"><?php echo number_format($percentage, 1); ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
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
    <!-- Print Report Functionality -->
<script>
// Print Report Function
function printReport() {
    // Get current date and time
    const now = new Date();
    const dateTime = now.toLocaleString();
    
    // Create a print-friendly version of the page
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title><?php echo $report_title; ?> - Print</title>
            <style>
                @media print {
                    @page {
                        margin: 20mm;
                        size: A4 portrait;
                    }
                    body {
                        font-family: Arial, sans-serif;
                        margin: 0;
                        padding: 0;
                        color: #333;
                        font-size: 12px;
                    }
                    .print-header {
                        text-align: center;
                        border-bottom: 3px solid #183a34;
                        padding-bottom: 20px;
                        margin-bottom: 30px;
                    }
                    .print-header h1 {
                        color: #183a34;
                        margin: 0 0 10px 0;
                        font-size: 24px;
                    }
                    .print-header h2 {
                        color: #666;
                        margin: 5px 0;
                        font-size: 16px;
                    }
                    .print-info-grid {
                        display: grid;
                        grid-template-columns: repeat(2, 1fr);
                        gap: 15px;
                        margin-bottom: 20px;
                    }
                    .print-info-box {
                        border: 1px solid #ddd;
                        padding: 10px;
                        border-radius: 5px;
                    }
                    .print-info-box h3 {
                        margin: 0 0 5px 0;
                        color: #555;
                        font-size: 12px;
                        text-transform: uppercase;
                    }
                    .print-info-box p {
                        margin: 3px 0;
                        font-size: 11px;
                    }
                    .financial-summary-print {
                        background: #f9f9f9;
                        padding: 15px;
                        border-radius: 8px;
                        margin-bottom: 20px;
                        border-left: 5px solid #183a34;
                    }
                    .financial-numbers-print {
                        display: grid;
                        grid-template-columns: repeat(3, 1fr);
                        gap: 15px;
                        text-align: center;
                    }
                    .financial-item-print {
                        padding: 10px;
                    }
                    .financial-item-print h4 {
                        margin: 0 0 5px 0;
                        font-size: 11px;
                        color: #666;
                    }
                    .financial-item-print .amount {
                        font-size: 18px;
                        font-weight: bold;
                    }
                    .income-print { color: #3b82f6; }
                    .expenses-print { color: #ef4444; }
                    .profit-print { color: <?php echo $total_profit >= 0 ? '#10b981' : '#ef4444'; ?>; }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 15px 0;
                        font-size: 11px;
                    }
                    table th {
                        background-color: #183a34;
                        color: white;
                        padding: 8px;
                        text-align: left;
                        font-weight: bold;
                    }
                    table td {
                        padding: 6px 8px;
                        border-bottom: 1px solid #ddd;
                    }
                    table tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                    .section-title {
                        margin: 20px 0 10px 0;
                        padding-bottom: 5px;
                        border-bottom: 2px solid #183a34;
                        color: #183a34;
                        font-size: 14px;
                        font-weight: bold;
                    }
                    .print-footer {
                        margin-top: 30px;
                        padding-top: 15px;
                        border-top: 1px solid #ddd;
                        text-align: center;
                        color: #666;
                        font-size: 10px;
                    }
                    .no-print {
                        display: none;
                    }
                }
                body {
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1><?php echo $report_title; ?></h1>
                <h2>Snooker Club Management System</h2>
                <p>Report Period: <?php echo $date_display; ?> (<?php echo $report_type == 'monthly' ? 'Monthly' : 'Daily'; ?>)</p>
                <p>Generated on: ${dateTime} | By: <?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
            </div>
            
            <div class="financial-summary-print">
                <h3>Financial Summary</h3>
                <div class="financial-numbers-print">
                    <div class="financial-item-print">
                        <h4>Total Income</h4>
                        <div class="amount income-print">PKR <?php echo number_format($total_income, 2); ?></div>
                        <p><?php echo $total_sessions; ?> sessions</p>
                    </div>
                    <div class="financial-item-print">
                        <h4>Total Expenses</h4>
                        <div class="amount expenses-print">PKR <?php echo number_format($total_expenses, 2); ?></div>
                        <p><?php echo count($expense_categories); ?> categories</p>
                    </div>
                    <div class="financial-item-print">
                        <h4>Net <?php echo $total_profit >= 0 ? 'Profit' : 'Loss'; ?></h4>
                        <div class="amount profit-print">PKR <?php echo number_format($total_profit, 2); ?></div>
                        <p><?php echo $total_profit >= 0 ? 'Profit' : 'Loss'; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="print-info-grid">
                <div class="print-info-box">
                    <h3>POS Sales</h3>
                    <p><strong>Transactions:</strong> <?php echo $pos_sales['total_transactions'] ?? 0; ?></p>
                    <p><strong>Revenue:</strong> PKR <?php echo number_format($pos_sales['pos_revenue'] ?? 0, 2); ?></p>
                    <p><strong>Avg. Transaction:</strong> PKR <?php echo number_format($pos_sales['avg_transaction'] ?? 0, 2); ?></p>
                </div>
                
                <div class="print-info-box">
                    <h3>Bookings</h3>
                    <p><strong>Total:</strong> <?php echo $booking_stats['total_bookings'] ?? 0; ?></p>
                    <p><strong>Confirmed:</strong> <?php echo $booking_stats['confirmed_bookings'] ?? 0; ?></p>
                    <p><strong>Cancelled:</strong> <?php echo $booking_stats['cancelled_bookings'] ?? 0; ?></p>
                </div>
                
                <div class="print-info-box">
                    <h3>Stock Purchases</h3>
                    <p><strong>Orders:</strong> <?php echo $purchase_costs['total_purchases'] ?? 0; ?></p>
                    <p><strong>Cost:</strong> PKR <?php echo number_format($purchase_costs['total_purchase_cost'] ?? 0, 2); ?></p>
                    <p><strong>Items:</strong> <?php echo $purchase_costs['total_items_purchased'] ?? 0; ?></p>
                </div>
                
                <div class="print-info-box">
                    <h3>Report Info</h3>
                    <p><strong>Data Type:</strong> <?php echo $real_data_exists ? 'Real Data' : 'Sample Data'; ?></p>
                    <p><strong>Generated:</strong> <?php echo date('F j, Y h:i A'); ?></p>
                    <p><strong>Admin:</strong> <?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
                </div>
            </div>
            
            <?php if (!empty($table_usage)): ?>
            <div class="section-title">Table Usage Summary</div>
            <table>
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Sessions</th>
                        <th>Usage Time</th>
                        <th>Income</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($table_usage as $usage): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($usage['table_name']); ?></td>
                        <td><?php echo $usage['total_sessions']; ?></td>
                        <td><?php echo format_minutes($usage['total_minutes']); ?></td>
                        <td>PKR <?php echo number_format($usage['table_income'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight: bold; background-color: #e8f5e8;">
                        <td>Total</td>
                        <td><?php echo array_sum(array_column($table_usage, 'total_sessions')); ?></td>
                        <td><?php echo format_minutes(array_sum(array_column($table_usage, 'total_minutes'))); ?></td>
                        <td>PKR <?php echo number_format(array_sum(array_column($table_usage, 'table_income')), 2); ?></td>
                    </tr>
                </tbody>
            </table>
            <?php endif; ?>
            
            <?php if (!empty($expense_categories)): ?>
            <div class="section-title">Expense Breakdown</div>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($expense_categories as $expense): 
                        $percentage = $total_expenses > 0 ? ($expense['total'] / $total_expenses) * 100 : 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($expense['category']); ?></td>
                        <td>PKR <?php echo number_format($expense['total'], 2); ?></td>
                        <td><?php echo number_format($percentage, 1); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight: bold; background-color: #ffeaea;">
                        <td>Total Expenses</td>
                        <td>PKR <?php echo number_format($total_expenses, 2); ?></td>
                        <td>100%</td>
                    </tr>
                </tbody>
            </table>
            <?php endif; ?>
            
            <?php if (!empty($staff_performance)): ?>
            <div class="section-title">Staff Performance</div>
            <table>
                <thead>
                    <tr>
                        <th>Staff Member</th>
                        <th>Role</th>
                        <th>Sessions Handled</th>
                        <th>Revenue Generated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($staff_performance as $staff): 
                        $display_name = !empty($staff['name']) ? $staff['name'] : $staff['username'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($display_name); ?></td>
                        <td><?php echo $staff['role']; ?></td>
                        <td><?php echo $staff['sessions_handled']; ?></td>
                        <td>PKR <?php echo number_format($staff['revenue_generated'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <?php if (!empty($product_performance)): ?>
            <div class="section-title">Top Products</div>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($product_performance as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?></td>
                        <td>PKR <?php echo number_format($product['selling_price'], 2); ?></td>
                        <td><?php echo $product['total_sold']; ?></td>
                        <td>PKR <?php echo number_format($product['total_revenue'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <?php if (!empty($low_stock_items)): ?>
            <div class="section-title">Low Stock Alerts</div>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Current Stock</th>
                        <th>Reorder Level</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($low_stock_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td style="color: <?php echo $item['current_stock'] <= $item['reorder_level'] ? '#ef4444' : '#666'; ?>">
                            <?php echo $item['current_stock']; ?>
                        </td>
                        <td><?php echo $item['reorder_level']; ?></td>
                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <div class="print-footer">
                <p>Report generated by Snooker Club Management System</p>
                <p>This is <?php echo $real_data_exists ? 'a real data report' : 'a sample data report'; ?> for <?php echo $date_display; ?></p>
                <p>Page generated on: <?php echo date('F j, Y h:i A'); ?></p>
            </div>
        </body>
        </html>
    `;
    
    // Open print window
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    printWindow.document.open();
    printWindow.document.write(printContent);
    printWindow.document.close();
    
    // Wait for content to load then print
    printWindow.onload = function() {
        setTimeout(function() {
            printWindow.print();
            // Close window after printing
            printWindow.onafterprint = function() {
                printWindow.close();
            };
        }, 500);
    };
}

// Add click event to the print button
document.addEventListener('DOMContentLoaded', function() {
    const printButton = document.querySelector('button:has(.fa-print)');
    if (printButton) {
        printButton.addEventListener('click', printReport);
    }
});
</script>
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