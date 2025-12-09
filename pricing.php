<?php
session_start();
if (!isset($_SESSION['admin_name'])) {
    header("Location: index.php");
    exit;
}

include 'db.php';

// Fetch all snooker tables with their rates
$tables_result = $conn->query("SELECT * FROM snooker_tables ORDER BY id ASC");

// Fetch active products with pricing
$products_result = $conn->query("
    SELECT product_id, name, category, selling_price, cost_price, stock_quantity 
    FROM products 
    WHERE is_active = 1 
    ORDER BY category, name
");

// Count tables by status
$status_result = $conn->query("
    SELECT 
        COUNT(CASE WHEN status = 'Occupied' THEN 1 END) as occupied,
        COUNT(CASE WHEN status = 'Free' THEN 1 END) as free,
        COUNT(*) as total
    FROM snooker_tables
");
$status_data = $status_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing & Rates - Snooker Club Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .snooker-shadow {
            box-shadow: 0 4px 12px rgba(24, 58, 52, 0.15);
        }
        
        .price-card {
            border-left: 4px solid #183a34;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .price-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(24, 58, 52, 0.2);
        }
        
        .peak-badge {
            background: linear-gradient(45deg, #ff6b6b, #ff8e53);
            color: white;
            animation: pulse 2s infinite;
        }
        
        .offpeak-badge {
            background: linear-gradient(45deg, #4ecdc4, #44a08d);
            color: white;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.8; }
            100% { opacity: 1; }
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-occupied {
            background-color: #fee2e2;
            color: #dc2626;
        }
        
        .status-free {
            background-color: #dcfce7;
            color: #16a34a;
        }
        
        .category-header {
            background: linear-gradient(90deg, #183a34, #2a4d45);
            color: white;
        }
        
        .time-slot {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .time-slot:hover {
            border-color: #183a34;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<!-- Sidebar -->
<?php include 'layout/sidebar.php'; ?>

<!-- Main Content Area -->
<div class="min-h-screen lg:ml-64">
    
    <!-- Header -->
    <?php include "layout/header.php"; ?>

    <!-- Main Content -->
    <main class="pt-16 p-6">
        
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2 mt-5">
                        <i class="fas fa-tag mr-2 text-snooker-green "></i>Pricing & Rates
                    </h1>
                    <p class="text-gray-600">Complete pricing information for tables, products, and time slots</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Last Updated</div>
                    <div class="font-semibold"><?php echo date('F j, Y'); ?></div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-lg snooker-shadow">
                    <div class="text-sm text-gray-500">Total Tables</div>
                    <div class="text-2xl font-bold text-snooker-green"><?php echo $status_data['total']; ?></div>
                </div>
                <div class="bg-white p-4 rounded-lg snooker-shadow">
                    <div class="text-sm text-gray-500">Available Now</div>
                    <div class="text-2xl font-bold text-green-600"><?php echo $status_data['free']; ?></div>
                </div>
                <div class="bg-white p-4 rounded-lg snooker-shadow">
                    <div class="text-sm text-gray-500">Occupied</div>
                    <div class="text-2xl font-bold text-red-600"><?php echo $status_data['occupied']; ?></div>
                </div>
                <div class="bg-white p-4 rounded-lg snooker-shadow">
                    <div class="text-sm text-gray-500">Active Products</div>
                    <div class="text-2xl font-bold text-purple-600"><?php echo $products_result->num_rows; ?></div>
                </div>
            </div>
        </div>

        <!-- Main Pricing Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- LEFT COLUMN: Table Pricing & Time Slots -->
            <div class="space-y-8">
                
                <!-- Snooker Tables Pricing -->
                <div class="bg-white rounded-xl snooker-shadow overflow-hidden">
                    <div class="category-header px-6 py-4">
                        <h2 class="text-xl font-bold flex items-center">
                            <i class="fas fa-table-tennis mr-3"></i>Snooker Tables Pricing
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-gray-800 text-lg">Table Rates</h3>
                                <span class="text-sm text-gray-500">All rates in PKR</span>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Table</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hourly Rate</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Century Rate</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weekend Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php if ($tables_result->num_rows > 0): ?>
                                            <?php while ($table = $tables_result->fetch_assoc()): ?>
                                                <?php
                                                $hourly_rate = $table['rate_per_hour'] ?? 0;
                                                $century_rate = $table['century_rate'] ?? 0;
                                                $weekend_rate = $hourly_rate * 1.25; // 25% increase on weekends
                                                $status = $table['status'] ?? 'Free';
                                                ?>
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <td class="px-4 py-3">
                                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($table['table_name']); ?></div>
                                                        <div class="text-xs text-gray-500">ID: <?php echo $table['id']; ?></div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <div class="text-lg font-bold text-snooker-green">
                                                            PKR <?php echo number_format($hourly_rate, 2); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500">per hour</div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <div class="text-lg font-bold text-purple-600">
                                                            PKR <?php echo number_format($century_rate, 2); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500">per minute</div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="status-badge <?php echo $status == 'Occupied' ? 'status-occupied' : 'status-free'; ?>">
                                                            <?php echo $status; ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <div class="text-lg font-bold text-orange-600">
                                                            PKR <?php echo number_format($weekend_rate, 2); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500">Fri-Sun</div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                                    <i class="fas fa-table-tennis text-3xl mb-2"></i>
                                                    <p>No tables configured</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Peak Hours Notice -->
                        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mr-3"></i>
                                <div>
                                    <h4 class="font-semibold text-yellow-800">Peak Hours Surcharge</h4>
                                    <p class="text-sm text-yellow-700">7:00 PM - 12:00 AM: <span class="font-bold">+20%</span> on all table rates</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Time-Based Pricing -->
                <div class="bg-white rounded-xl snooker-shadow overflow-hidden">
                    <div class="category-header px-6 py-4">
                        <h2 class="text-xl font-bold flex items-center">
                            <i class="fas fa-clock mr-3"></i>Time Slot Pricing
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Morning Session -->
                            <div class="time-slot p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <span class="offpeak-badge px-3 py-1 rounded-full text-xs font-bold">OFF-PEAK</span>
                                        <h4 class="font-bold text-gray-800 mt-2">Morning Session</h4>
                                    </div>
                                    <div class="text-2xl font-bold text-green-600">50% OFF</div>
                                </div>
                                <div class="text-sm text-gray-600 mb-2">
                                    <i class="far fa-clock mr-1"></i> 8:00 AM - 12:00 PM
                                </div>
                                <div class="space-y-1">
                                    <div class="flex justify-between text-sm">
                                        <span>Standard Table:</span>
                                        <span class="font-semibold">PKR 300/hour</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span>Premium Table:</span>
                                        <span class="font-semibold">PKR 400/hour</span>
                                    </div>
                                </div>
                                <div class="mt-3 text-xs text-gray-500">
                                    <i class="fas fa-info-circle mr-1"></i> Perfect for practice sessions
                                </div>
                            </div>
                            
                            <!-- Afternoon Session -->
                            <div class="time-slot p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">REGULAR</span>
                                        <h4 class="font-bold text-gray-800 mt-2">Afternoon Session</h4>
                                    </div>
                                    <div class="text-2xl font-bold text-blue-600">20% OFF</div>
                                </div>
                                <div class="text-sm text-gray-600 mb-2">
                                    <i class="far fa-clock mr-1"></i> 12:00 PM - 5:00 PM
                                </div>
                                <div class="space-y-1">
                                    <div class="flex justify-between text-sm">
                                        <span>Standard Table:</span>
                                        <span class="font-semibold">PKR 400/hour</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span>Happy Hour (3-5 PM):</span>
                                        <span class="font-semibold text-green-600">PKR 320/hour</span>
                                    </div>
                                </div>
                                <div class="mt-3 text-xs text-gray-500">
                                    <i class="fas fa-info-circle mr-1"></i> Happy Hour discount available
                                </div>
                            </div>
                            
                            <!-- Evening Session -->
                            <div class="time-slot p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <span class="peak-badge px-3 py-1 rounded-full text-xs font-bold">PEAK HOURS</span>
                                        <h4 class="font-bold text-gray-800 mt-2">Evening Session</h4>
                                    </div>
                                    <div class="text-2xl font-bold text-red-600">+20%</div>
                                </div>
                                <div class="text-sm text-gray-600 mb-2">
                                    <i class="far fa-clock mr-1"></i> 5:00 PM - 12:00 AM
                                </div>
                                <div class="space-y-1">
                                    <div class="flex justify-between text-sm">
                                        <span>Standard Table:</span>
                                        <span class="font-semibold">PKR 600/hour</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span>Premium Table:</span>
                                        <span class="font-semibold">PKR 960/hour</span>
                                    </div>
                                </div>
                                <div class="mt-3 text-xs text-gray-500">
                                    <i class="fas fa-info-circle mr-1"></i> Minimum 2-hour booking required
                                </div>
                            </div>
                            
                            <!-- Night Session -->
                            <div class="time-slot p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">SPECIAL</span>
                                        <h4 class="font-bold text-gray-800 mt-2">Night Session</h4>
                                    </div>
                                    <div class="text-2xl font-bold text-indigo-600">FLAT RATE</div>
                                </div>
                                <div class="text-sm text-gray-600 mb-2">
                                    <i class="far fa-clock mr-1"></i> 12:00 AM - 8:00 AM
                                </div>
                                <div class="space-y-1">
                                    <div class="flex justify-between text-sm">
                                        <span>Overnight (8 hours):</span>
                                        <span class="font-semibold">PKR 2,000</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span>Per Additional Hour:</span>
                                        <span class="font-semibold">PKR 250</span>
                                    </div>
                                </div>
                                <div class="mt-3 text-xs text-gray-500">
                                    <i class="fas fa-info-circle mr-1"></i> Includes complimentary drinks
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- RIGHT COLUMN: Products & Memberships -->
            <div class="space-y-8">
                
                <!-- Food & Beverage Pricing -->
                <div class="bg-white rounded-xl snooker-shadow overflow-hidden">
                    <div class="category-header px-6 py-4">
                        <h2 class="text-xl font-bold flex items-center">
                            <i class="fas fa-utensils mr-3"></i>Food & Beverage Menu
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <?php
                        // Organize products by category
                        $categories = [];
                        if ($products_result->num_rows > 0) {
                            $products_result->data_seek(0); // Reset pointer
                            while ($product = $products_result->fetch_assoc()) {
                                $category = $product['category'] ?: 'Uncategorized';
                                if (!isset($categories[$category])) {
                                    $categories[$category] = [];
                                }
                                $categories[$category][] = $product;
                            }
                        }
                        ?>
                        
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category_name => $category_products): ?>
                                <div class="mb-6 last:mb-0">
                                    <h3 class="font-bold text-gray-800 text-lg mb-3 border-b pb-2">
                                        <?php echo htmlspecialchars(strtoupper($category_name)); ?>
                                    </h3>
                                    <div class="space-y-2">
                                        <?php foreach ($category_products as $product): ?>
                                            <div class="flex justify-between items-center p-2 hover:bg-gray-50 rounded">
                                                <div>
                                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                                    <div class="text-xs text-gray-500">
                                                        Stock: <?php echo $product['stock_quantity']; ?> units
                                                        <?php if ($product['cost_price'] > 0): ?>
                                                            | Cost: PKR <?php echo number_format($product['cost_price'], 2); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="font-bold text-snooker-green">
                                                        PKR <?php echo number_format($product['selling_price'], 2); ?>
                                                    </div>
                                                    <?php if ($product['cost_price'] > 0 && $product['selling_price'] > $product['cost_price']): ?>
                                                        <?php $margin = (($product['selling_price'] - $product['cost_price']) / $product['cost_price']) * 100; ?>
                                                        <div class="text-xs <?php echo $margin >= 50 ? 'text-green-600' : ($margin >= 30 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                                            Margin: <?php echo number_format($margin, 0); ?>%
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-box-open text-4xl mb-2"></i>
                                <p>No products available</p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Popular Items -->
                        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                            <h4 class="font-semibold text-blue-800 mb-2 flex items-center">
                                <i class="fas fa-star mr-2"></i>Popular Items
                            </h4>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="text-sm">
                                    <span class="text-blue-600">✓</span> Pepsi/7up
                                </div>
                                <div class="text-sm">
                                    <span class="text-blue-600">✓</span> French Fries
                                </div>
                                <div class="text-sm">
                                    <span class="text-blue-600">✓</span> Chicken Wings
                                </div>
                                <div class="text-sm">
                                    <span class="text-blue-600">✓</span> Mineral Water
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Equipment Rental & Extras -->
                <div class="bg-white rounded-xl snooker-shadow overflow-hidden">
                    <div class="category-header px-6 py-4">
                        <h2 class="text-xl font-bold flex items-center">
                            <i class="fas fa-tools mr-3"></i>Equipment & Extras
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Equipment Rental -->
                            <div>
                                <h3 class="font-bold text-gray-800 mb-3">Equipment Rental</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center p-2 border rounded">
                                        <div class="flex items-center">
                                            <i class="fas fa-baseball-ball text-blue-500 mr-3"></i>
                                            <div>
                                                <div class="font-medium">Cue Stick</div>
                                                <div class="text-xs text-gray-500">Professional quality</div>
                                            </div>
                                        </div>
                                        <div class="font-bold text-blue-600">PKR 50/hour</div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center p-2 border rounded">
                                        <div class="flex items-center">
                                            <i class="fas fa-palette text-purple-500 mr-3"></i>
                                            <div>
                                                <div class="font-medium">Chalk</div>
                                                <div class="text-xs text-gray-500">Per piece</div>
                                            </div>
                                        </div>
                                        <div class="font-bold text-purple-600">PKR 20</div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center p-2 border rounded">
                                        <div class="flex items-center">
                                            <i class="fas fa-hand-paper text-green-500 mr-3"></i>
                                            <div>
                                                <div class="font-medium">Glove</div>
                                                <div class="text-xs text-gray-500">Per session</div>
                                            </div>
                                        </div>
                                        <div class="font-bold text-green-600">PKR 30</div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center p-2 border rounded">
                                        <div class="flex items-center">
                                            <i class="fas fa-basketball-ball text-red-500 mr-3"></i>
                                            <div>
                                                <div class="font-medium">Ball Set</div>
                                                <div class="text-xs text-gray-500">Replacement</div>
                                            </div>
                                        </div>
                                        <div class="font-bold text-red-600">PKR 100</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Additional Services -->
                            <div>
                                <h3 class="font-bold text-gray-800 mb-3">Additional Services</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center p-2 border rounded">
                                        <div class="flex items-center">
                                            <i class="fas fa-user-tie text-orange-500 mr-3"></i>
                                            <div>
                                                <div class="font-medium">Coach Session</div>
                                                <div class="text-xs text-gray-500">1 hour with pro</div>
                                            </div>
                                        </div>
                                        <div class="font-bold text-orange-600">PKR 1,000</div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center p-2 border rounded">
                                        <div class="flex items-center">
                                            <i class="fas fa-trophy text-yellow-500 mr-3"></i>
                                            <div>
                                                <div class="font-medium">Tournament Entry</div>
                                                <div class="text-xs text-gray-500">Weekly competition</div>
                                            </div>
                                        </div>
                                        <div class="font-bold text-yellow-600">PKR 500</div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center p-2 border rounded">
                                        <div class="flex items-center">
                                            <i class="fas fa-camera text-indigo-500 mr-3"></i>
                                            <div>
                                                <div class="font-medium">Photography</div>
                                                <div class="text-xs text-gray-500">Professional shots</div>
                                            </div>
                                        </div>
                                        <div class="font-bold text-indigo-600">PKR 2,000</div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center p-2 border rounded">
                                        <div class="flex items-center">
                                            <i class="fas fa-birthday-cake text-pink-500 mr-3"></i>
                                            <div>
                                                <div class="font-medium">Birthday Package</div>
                                                <div class="text-xs text-gray-500">4 hours + decor</div>
                                            </div>
                                        </div>
                                        <div class="font-bold text-pink-600">PKR 5,000</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Special Offer -->
                        <div class="mt-6 p-4 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-bold text-lg">🎉 Special Offer!</h4>
                                    <p class="text-sm opacity-90">Rent 3 equipment items, get 4th free!</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold">25% OFF</div>
                                    <div class="text-xs">Limited time</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Membership Plans -->
                <div class="bg-white rounded-xl snooker-shadow overflow-hidden">
                    <div class="category-header px-6 py-4">
                        <h2 class="text-xl font-bold flex items-center">
                            <i class="fas fa-crown mr-3"></i>Membership Plans
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Basic Plan -->
                            <div class="border rounded-lg p-4 text-center hover:border-snooker-green transition-colors">
                                <div class="mb-4">
                                    <h3 class="font-bold text-gray-800 text-lg">Basic</h3>
                                    <div class="text-3xl font-bold text-snooker-green mt-2">PKR 5,000</div>
                                    <div class="text-sm text-gray-500">per month</div>
                                </div>
                                <ul class="text-sm text-gray-600 space-y-2 mb-4">
                                    <li>✓ 20 hours table time</li>
                                    <li>✓ 10% discount on F&B</li>
                                    <li>✓ Free cue rental</li>
                                    <li>✗ Priority booking</li>
                                    <li>✗ Coach sessions</li>
                                </ul>
                                <button class="w-full py-2 bg-gray-100 text-gray-700 rounded font-semibold hover:bg-gray-200 transition">
                                    Select Plan
                                </button>
                            </div>
                            
                            <!-- Premium Plan -->
                            <div class="border-2 border-snooker-green rounded-lg p-4 text-center relative">
                                <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                                    <span class="bg-snooker-green text-white px-3 py-1 rounded-full text-xs font-bold">POPULAR</span>
                                </div>
                                <div class="mb-4">
                                    <h3 class="font-bold text-gray-800 text-lg">Premium</h3>
                                    <div class="text-3xl font-bold text-snooker-green mt-2">PKR 10,000</div>
                                    <div class="text-sm text-gray-500">per month</div>
                                </div>
                                <ul class="text-sm text-gray-600 space-y-2 mb-4">
                                    <li>✓ 50 hours table time</li>
                                    <li>✓ 20% discount on F&B</li>
                                    <li>✓ Free equipment rental</li>
                                    <li>✓ Priority booking</li>
                                    <li>✓ 2 coach sessions</li>
                                </ul>
                                <button class="w-full py-2 bg-snooker-green text-white rounded font-semibold hover:bg-snooker-light transition">
                                    Select Plan
                                </button>
                            </div>
                            
                            <!-- VIP Plan -->
                            <div class="border rounded-lg p-4 text-center hover:border-purple-500 transition-colors">
                                <div class="mb-4">
                                    <h3 class="font-bold text-gray-800 text-lg">VIP</h3>
                                    <div class="text-3xl font-bold text-purple-600 mt-2">PKR 20,000</div>
                                    <div class="text-sm text-gray-500">per month</div>
                                </div>
                                <ul class="text-sm text-gray-600 space-y-2 mb-4">
                                    <li>✓ Unlimited table time</li>
                                    <li>✓ 30% discount on F&B</li>
                                    <li>✓ Personal locker</li>
                                    <li>✓ VIP lounge access</li>
                                    <li>✓ 4 coach sessions</li>
                                </ul>
                                <button class="w-full py-2 bg-purple-100 text-purple-700 rounded font-semibold hover:bg-purple-200 transition">
                                    Select Plan
                                </button>
                            </div>
                        </div>
                        
                        <!-- Annual Discount -->
                        <div class="mt-6 text-center">
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-gift text-green-500 mr-1"></i>
                                Pay annually and get <span class="font-bold text-green-600">2 months FREE!</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Notes -->
        <div class="mt-8 p-6 bg-gray-50 rounded-xl border">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h4 class="font-bold text-gray-800 mb-2 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>Payment Terms
                    </h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• All prices are in PKR</li>
                        <li>• GST included where applicable</li>
                        <li>• Advance booking recommended</li>
                        <li>• Refund policy: 24 hours notice</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-gray-800 mb-2 flex items-center">
                        <i class="fas fa-clock text-orange-500 mr-2"></i>Operating Hours
                    </h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Monday - Thursday: 8 AM - 2 AM</li>
                        <li>• Friday - Sunday: 8 AM - 4 AM</li>
                        <li>• 24/7 booking available online</li>
                        <li>• Last entry: 1 hour before closing</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-gray-800 mb-2 flex items-center">
                        <i class="fas fa-phone text-green-500 mr-2"></i>Contact & Support
                    </h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Phone: (021) 123-4567</li>
                        <li>• Email: info@snookerclub.com</li>
                        <li>• WhatsApp: +92 300 1234567</li>
                        <li>• Live chat: Available 24/7</li>
                    </ul>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
    // Print pricing function
    function printPricing() {
        window.print();
    }
    
    // Export to PDF function (placeholder)
    function exportToPDF() {
        alert('PDF export feature will be implemented soon!');
    }
    
    // Toggle view mode
    function toggleViewMode() {
        const body = document.body;
        body.classList.toggle('view-compact');
        const isCompact = body.classList.contains('view-compact');
        localStorage.setItem('pricingViewMode', isCompact ? 'compact' : 'detailed');
        showNotification(`Switched to ${isCompact ? 'compact' : 'detailed'} view`);
    }
    
    // Show notification
    function showNotification(message) {
        const div = document.createElement('div');
        div.className = 'fixed top-4 right-4 px-6 py-3 bg-green-100 text-green-700 border border-green-300 rounded-lg shadow-lg z-50';
        div.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                ${message}
            </div>
        `;
        document.body.appendChild(div);
        
        setTimeout(() => {
            div.remove();
        }, 3000);
    }
    
    // Load saved view mode
    document.addEventListener('DOMContentLoaded', function() {
        const savedMode = localStorage.getItem('pricingViewMode');
        if (savedMode === 'compact') {
            document.body.classList.add('view-compact');
        }
        
        // Add print button to header
        const headerActions = document.querySelector('main .mb-8');
        if (headerActions) {
            const actionDiv = document.createElement('div');
            actionDiv.className = 'flex space-x-3 mt-4';
            actionDiv.innerHTML = `
                <button onclick="printPricing()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition flex items-center">
                    <i class="fas fa-print mr-2"></i> Print Pricing
                </button>
                <button onclick="exportToPDF()" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg font-medium hover:bg-red-200 transition flex items-center">
                    <i class="fas fa-file-pdf mr-2"></i> Export PDF
                </button>
                <button onclick="toggleViewMode()" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg font-medium hover:bg-blue-200 transition flex items-center">
                    <i class="fas fa-eye mr-2"></i> Toggle View
                </button>
            `;
            headerActions.appendChild(actionDiv);
        }
    });
</script>

</body>