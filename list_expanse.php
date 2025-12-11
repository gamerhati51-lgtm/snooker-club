<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = "SAEED GUEST";
}

/* -----------------------------------------------------
   AJAX HANDLER – RETURN SINGLE EXPENSE DATA
----------------------------------------------------- */
if (isset($_GET['ajax_get'])) {
    $id = $_GET['ajax_get'];
    $q = $conn->query("
        SELECT e.expanses_id, e.expanses_date, e.details, e.amount, ec.category_name 
        FROM expanses e
        JOIN expanses_categories ec ON e.category_id = ec.category_id
        WHERE e.expanses_id = '$id'
    ");
    echo json_encode($q->fetch_assoc());
    exit;
}

/* -----------------------------------------------------
   AJAX HANDLER – UPDATE EXPENSE
----------------------------------------------------- */
if (isset($_POST['ajax_update'])) {
    $id = $_POST['id'];
    $date = $_POST['date'];
    $category = $_POST['category'];
    $details = $_POST['details'];
    $amount = $_POST['amount'];

    // Get category ID
    $catQ = $conn->query("SELECT category_id FROM expanses_categories WHERE category_name='$category'");
    $catID = $catQ->fetch_assoc()['category_id'] ?? 1;

    $conn->query("
        UPDATE expanses SET 
            expanses_date='$date',
            category_id='$catID',
            details='$details',
            amount='$amount'
        WHERE expanses_id='$id'
    ");

    echo "success";
    exit;
}
// -------------------------
// AJAX DELETE HANDLER
// -------------------------
if (isset($_POST['ajax_delete'])) {
    $id = $_POST['id'];
    $conn->query("DELETE FROM expanses WHERE expanses_id='$id'");
    echo "deleted";
    exit;
}

/* -----------------------------------------------------
   FETCH ALL EXPENSES + TOTAL
----------------------------------------------------- */
$expanses_query = $conn->query("
    SELECT e.expanses_id, e.expanses_date, e.details, e.amount, ec.category_name
    FROM expanses e
    JOIN expanses_categories ec ON e.category_id = ec.category_id
    ORDER BY e.expanses_date DESC, e.expanses_id DESC
");
$all_expanses = $expanses_query ? $expanses_query->fetch_all(MYSQLI_ASSOC) : [];

$total_expenses_result = $conn->query("SELECT SUM(amount) AS total FROM expanses");
$total_expenses = $total_expenses_result ? $total_expenses_result->fetch_assoc()['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💰 Expense Dashboard | Snooker Club Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        .compact-table th {
            padding: 12px 16px !important;
            font-size: 13px !important;
        }
        
        .compact-table td {
            padding: 12px 16px !important;
            font-size: 14px !important;
        }
        
        .amount-cell {
            min-width: 140px;
            max-width: 160px;
        }
        
        .action-cell {
            min-width: 140px;
            max-width: 160px;
        }
        
        .date-cell {
            min-width: 140px;
            max-width: 160px;
        }
        
        .category-cell {
            min-width: 120px;
            max-width: 150px;
        }
        
        .details-cell {
            min-width: 200px;
            max-width: 300px;
        }
        
        .table-container {
            width: 100%;
            overflow-x: auto;
        }
        
        .table-wrapper {
            min-width: 900px;
        }
        
        /* Ensure table doesn't exceed container */
        .table-fit {
            width: auto;
            min-width: 100%;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .compact-table th,
            .compact-table td {
                padding: 10px 12px !important;
                font-size: 12px !important;
            }
            
            .table-wrapper {
                min-width: 800px;
            }
            
            .date-cell,
            .category-cell,
            .amount-cell,
            .action-cell {
                min-width: 120px;
            }
        }
        
        /* Animation classes */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }
        
        /* Gradient background */
        .gradient-bg {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }
        
        /* Card hover effects */
        .hover-lift:hover {
            transform: translateY(-2px);
            transition: transform 0.2s ease;
        }
        
        /* Status badges */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen font-sans">

    <!-- Dashboard Container -->
    <div class="flex min-h-screen">

        <!-- Sidebar -->
        <?php include 'layout/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 ml-0 lg:ml-64 pt-20 p-4 lg:p-6 main-content">
            
            <!-- Header -->
            <?php include "layout/header.php"; ?>

            <!-- Dashboard Header -->
            <div class="animate-fade-in-up mt-6">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mt-9 mb-4">
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-2">
                            <i class="fas fa-coins text-blue-600 mr-2"></i>Expense Management
                        </h1>
                        <p class="text-gray-600 text-sm">Track and manage all club expenses</p>
                    </div>
                    
                    <!-- Total Stats -->
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-blue-100 w-full lg:w-auto mt-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Total Expenses</p>
                                <p class="text-2xl lg:text-3xl font-bold text-blue-600 mt-1">
                                    PKR <?php echo number_format($total_expenses, 2); ?>
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center ml-4">
                                <i class="fas fa-chart-bar text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards (Compact) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover-lift">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Transactions</p>
                            <p class="text-xl font-bold text-gray-800 mt-1"><?php echo count($all_expanses); ?></p>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                            <i class="fas fa-receipt text-blue-500"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover-lift">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Avg. Expense</p>
                            <p class="text-xl font-bold text-gray-800 mt-1">
                                PKR <?php echo count($all_expanses) > 0 ? number_format($total_expenses / count($all_expanses), 2) : '0.00'; ?>
                            </p>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                            <i class="fas fa-chart-pie text-green-500"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover-lift">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">This Month</p>
                            <?php 
                                $current_month = date('m');
                                $month_query = $conn->query("SELECT SUM(amount) as month_total FROM expanses WHERE MONTH(expanses_date) = '$current_month'");
                                $month_total = $month_query ? $month_query->fetch_assoc()['month_total'] : 0;
                            ?>
                            <p class="text-xl font-bold text-gray-800 mt-1">PKR <?php echo number_format($month_total, 2); ?></p>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-purple-500"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover-lift">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Last Month</p>
                            <?php 
                                $last_month = date('m', strtotime('-1 month'));
                                $last_month_query = $conn->query("SELECT SUM(amount) as last_month_total FROM expanses WHERE MONTH(expanses_date) = '$last_month'");
                                $last_month_total = $last_month_query ? $last_month_query->fetch_assoc()['last_month_total'] : 0;
                            ?>
                            <p class="text-xl font-bold text-gray-800 mt-1">PKR <?php echo number_format($last_month_total, 2); ?></p>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center">
                            <i class="fas fa-calendar-minus text-orange-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-fade-in-up">
                <!-- Table Header with Actions -->
                <div class="p-4 border-b border-gray-100 bg-gray-50">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">
                                <i class="fas fa-list-alt text-gray-600 mr-2"></i>Recent Expenses
                            </h2>
                            <p class="text-sm text-gray-500 mt-1">
                                <?php echo count($all_expanses); ?> records found
                            </p>
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <div class="relative flex-1 sm:flex-none">
                                <input type="text" placeholder="Search expenses..." 
                                       class="w-full sm:w-48 pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                            </div>
                       <button onclick="window.location.href='add_expance.php'" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
    <i class="fas fa-plus"></i>
    <span>Add Expense</span>
</button>

                        </div>
                    </div>
                </div>

                <!-- Table Container with Controlled Width -->
                <div class="table-container">
                    <div class="table-wrapper">
                        <table class="min-w-full divide-y divide-gray-200 compact-table table-fit">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider date-cell">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider category-cell">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider details-cell">Details</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider amount-cell">Amount</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider action-cell">Actions</th>
                                </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-100">
                            <?php if(!empty($all_expanses)): ?>
                                <?php foreach($all_expanses as $expense): ?>
                                    <tr class="hover:bg-blue-50 transition-colors duration-150">
                                        <td class="px-4 py-3 whitespace-nowrap date-cell">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                                                    <i class="fas fa-calendar text-blue-600 text-sm"></i>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-800 text-sm">
                                                        <?= date('d M Y', strtotime($expense['expanses_date'])); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500"><?= date('D', strtotime($expense['expanses_date'])); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="px-4 py-3 whitespace-nowrap category-cell">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <?= htmlspecialchars($expense['category_name']); ?>
                                            </span>
                                        </td>
                                        
                                        <td class="px-4 py-3 details-cell">
                                            <div class="max-w-xs">
                                                <p class="text-sm text-gray-800 truncate" title="<?= htmlspecialchars($expense['details'] ?: 'No details'); ?>">
                                                    <?= htmlspecialchars($expense['details'] ?: 'No details'); ?>
                                                </p>
                                                <?php if(strlen($expense['details']) > 40): ?>
                                                    <p class="text-xs text-gray-500 mt-0.5">Hover to view full details</p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        
                                        <td class="px-4 py-3 whitespace-nowrap amount-cell">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center mr-2">
                                                    <i class="fas fa-rupee-sign text-red-600 text-sm"></i>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-red-600 text-sm">
                                                        PKR <?= number_format($expense['amount'], 2); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="px-4 py-3 whitespace-nowrap text-center action-cell">
                                            <div class="flex justify-center gap-1">
                                                <!-- Edit Button -->
                                                <button onclick="openModal(<?= $expense['expanses_id']; ?>)"
                                                        class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition-colors duration-200 flex items-center justify-center"
                                                        title="Edit">
                                                    <i class="fas fa-edit text-xs"></i>
                                                </button>

                                             

                                                <!-- Delete Button -->
                                                <button onclick="deleteExpense(<?= $expense['expanses_id']; ?>)"
                                                        class="w-8 h-8 rounded-lg bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition-colors duration-200 flex items-center justify-center"
                                                        title="Delete">
                                                    <i class="fas fa-trash-alt text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                                <i class="fas fa-receipt text-gray-400 text-2xl"></i>
                                            </div>
                                            <h3 class="text-lg font-semibold text-gray-600 mb-2">No expenses found</h3>
                                            <p class="text-gray-500 mb-4 text-sm">Get started by adding your first expense</p>
                                            <button class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                                                <i class="fas fa-plus"></i>
                                                <span>Add Expense</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Table Footer -->
                <?php if(!empty($all_expanses)): ?>
                <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                        <div class="text-sm text-gray-600">
                            Showing <span class="font-semibold"><?php echo count($all_expanses); ?></span> of <?php echo count($all_expanses); ?> records
                        </div>
                        <div class="flex items-center gap-1">
                            <button class="w-8 h-8 rounded border border-gray-300 hover:bg-gray-100 transition flex items-center justify-center">
                                <i class="fas fa-chevron-left text-xs"></i>
                            </button>
                            <button class="w-8 h-8 rounded bg-blue-600 text-white hover:bg-blue-700 transition text-sm font-medium">
                                1
                            </button>
                            <button class="w-8 h-8 rounded border border-gray-300 hover:bg-gray-100 transition flex items-center justify-center">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-5 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-edit text-blue-600 mr-2"></i>Edit Expense
                    </h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <form id="editForm" class="p-5">
                <input type="hidden" id="edit_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-calendar text-gray-500 mr-1"></i>Date
                        </label>
                        <input type="date" id="edit_date" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-tag text-gray-500 mr-1"></i>Category
                        </label>
                        <input type="text" id="edit_category" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                               placeholder="Enter category">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-align-left text-gray-500 mr-1"></i>Details
                        </label>
                        <textarea id="edit_details" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm resize-none"
                                  placeholder="Enter details"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-rupee-sign text-gray-500 mr-1"></i>Amount (PKR)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500 font-medium">PKR</span>
                            <input type="number" id="edit_amount" step="0.01"
                                   class="w-full pl-12 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                   placeholder="0.00">
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-6 pt-5 border-t border-gray-200">
                    <button type="button" onclick="closeModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                        <i class="fas fa-save"></i>
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50 text-sm hidden">
        <div class="flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <span id="toast-message"></span>
        </div>
    </div>

<script>
// Modal Functions
function openModal(id) {
    const modal = document.getElementById("editModal");
    modal.classList.remove("hidden");
    
    // Show loading
    showToast('Loading...', 'info');
    
    fetch("?ajax_get=" + id)
        .then(res => res.json())
        .then(data => {
            document.getElementById("edit_id").value = data.expanses_id;
            document.getElementById("edit_date").value = data.expanses_date;
            document.getElementById("edit_category").value = data.category_name;
            document.getElementById("edit_details").value = data.details;
            document.getElementById("edit_amount").value = data.amount;
            showToast('Expense loaded', 'success');
        })
        .catch(err => {
            showToast('Failed to load', 'error');
        });
}

function closeModal() {
    document.getElementById("editModal").classList.add("hidden");
}

// Toast Notification
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    
    // Set message
    toastMessage.textContent = message;
    
    // Set color based on type
    if (type === 'success') {
        toast.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50 text-sm flex items-center gap-2';
        toast.querySelector('i').className = 'fas fa-check-circle';
    } else if (type === 'error') {
        toast.className = 'fixed top-4 right-4 bg-red-600 text-white px-4 py-2 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50 text-sm flex items-center gap-2';
        toast.querySelector('i').className = 'fas fa-exclamation-circle';
    } else if (type === 'info') {
        toast.className = 'fixed top-4 right-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50 text-sm flex items-center gap-2';
        toast.querySelector('i').className = 'fas fa-info-circle';
    }
    
    // Show toast
    toast.classList.remove('translate-x-full');
    toast.classList.add('translate-x-0');
    
    // Hide after 3 seconds
    setTimeout(() => {
        toast.classList.remove('translate-x-0');
        toast.classList.add('translate-x-full');
    }, 3000);
}

// Form Submission
document.getElementById("editForm").addEventListener("submit", function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    submitBtn.disabled = true;
    
    let formData = new FormData();
    formData.append("ajax_update", 1);
    formData.append("id", document.getElementById("edit_id").value);
    formData.append("date", document.getElementById("edit_date").value);
    formData.append("category", document.getElementById("edit_category").value);
    formData.append("details", document.getElementById("edit_details").value);
    formData.append("amount", document.getElementById("edit_amount").value);

    fetch("", { method: "POST", body: formData })
        .then(res => res.text())
        .then(result => {
            if(result.trim() === "success") {
                showToast('Updated successfully!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast('Update failed!', 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(err => {
            showToast('Network error!', 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
});

// Delete Function
function deleteExpense(id) {
    if (!confirm('Delete this expense? This cannot be undone.')) return;
    
    showToast('Deleting...', 'info');
    
    let formData = new FormData();
    formData.append("ajax_delete", 1);
    formData.append("id", id);

    fetch("", { method: "POST", body: formData })
        .then(res => res.text())
        .then(result => {
            if(result.trim() === "deleted") {
                showToast('Deleted successfully!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast('Delete failed!', 'error');
            }
        })
        .catch(err => {
            showToast('Network error!', 'error');
        });
}

// View Function
function viewExpense(id) {
    // Show a simple alert for now - can be enhanced with a view modal
    alert('View details for expense ID: ' + id + '\n\nThis feature is under development.');
}

// Search Functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[placeholder="Search expenses..."]');
    const rows = document.querySelectorAll('tbody tr');
    
    searchInput?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>

</body>
</html>