<?php
session_start();
include 'db.php';
if(!isset($_SESSION['admin_name'])) header("Location:index.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snooker Tables Management | Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f0ff 100%);
            min-height: 100vh;
        }
        
        .table-card {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }
        
        .table-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
            border-color: #3b82f6;
        }
        
        .table-header {
            background: linear-gradient(90deg, #1e40af, #3b82f6);
            color: white;
            padding: 1.5rem;
            border-radius: 16px 16px 0 0;
        }
        
        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .status-active {
            background: linear-gradient(90deg, #10b981, #34d399);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .status-inactive {
            background: linear-gradient(90deg, #ef4444, #f87171);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }
        
        .edit-form {
            background: linear-gradient(145deg, #f8fafc, #e2e8f0);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
            border: 2px solid #dbeafe;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .price-tag {
            background: linear-gradient(90deg, #fef3c7, #fde68a);
            color: #92400e;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);
        }
        
        .action-btn {
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
        }
        
        .edit-btn {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .edit-btn:hover {
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }
        
        .save-btn {
            background: linear-gradient(90deg, #10b981, #34d399);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .save-btn:hover {
            background: linear-gradient(90deg, #059669, #10b981);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .cancel-btn {
            background: linear-gradient(90deg, #6b7280, #9ca3af);
            color: white;
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
        }
        
        .cancel-btn:hover {
            background: linear-gradient(90deg, #4b5563, #6b7280);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 114, 128, 0.4);
        }
        
        .dashboard-btn {
            background: linear-gradient(90deg, #f97316, #fb923c);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
            border: none;
            cursor: pointer;
        }
        
        .dashboard-btn:hover {
            background: linear-gradient(90deg, #ea580c, #f97316);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(249, 115, 22, 0.4);
        }
        
        .form-input {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .table-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #f8f9faff;
       
    
        }
        
        .century-badge {
            background: linear-gradient(90deg, #7c3aed, #a78bfa);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            animation: slideInRight 0.3s ease-out;
        }
        
        .toast-success {
            background: linear-gradient(90deg, #10b981, #34d399);
        }
        
        .toast-error {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>

<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/header.php'; ?>

<main class="flex-1 ml-0 lg:ml-64 pt-20 p-6 md:p-8">
    <!-- Page Header -->
    <div class="mb-12 mt-12"> <!-- Increased margin-bottom -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold text-gray-800">
                    <i class="fas fa-table-tennis text-blue-600 mr-3"></i>
                    Snooker Tables Management
                </h1>
                <p class="text-gray-600 mt-2">Manage and configure all your snooker tables</p>
            </div>
            
            <div class="flex items-center gap-4">
                <span class="status-badge status-active">
                    <i class="fas fa-circle text-xs"></i>
                    <span>Active Tables: 
                        <?php 
                            $active_count = $conn->query("SELECT COUNT(*) as count FROM snooker_tables WHERE is_active = 1")->fetch_assoc()['count'];
                            echo $active_count;
                        ?>
                    </span>
                </span>
                
                <a href="admin.php">
                    <button class="dashboard-btn">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </button>
                </a>
            </div>
        </div>
    </div>

    <!-- Tables Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <?php
        $result = $conn->query("SELECT * FROM snooker_tables ORDER BY id DESC");
        while($row = $result->fetch_assoc()):
            $status_class = $row['is_active'] == 1 ? 'status-active' : 'status-inactive';
            $status_text = $row['is_active'] == 1 ? 'Active' : 'Inactive';
            $status_icon = $row['is_active'] == 1 ? 'fa-check-circle' : 'fa-times-circle';
        ?>
        <div class="table-card">
            <div class="table-header">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="table-name text-2xl font-bold"><?php echo htmlspecialchars($row['table_name']); ?></h3>
                        <p class="text-blue-100 text-sm mt-1">Table ID: #<?php echo $row['id']; ?></p>
                    </div>
                    <span class="<?php echo $status_class; ?> status-badge">
                        <i class="fas <?php echo $status_icon; ?>"></i>
                        <?php echo $status_text; ?>
                    </span>
                </div>
            </div>
            
            <div class="p-6">
                <!-- Pricing Info -->
                <div class="space-y-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="fas fa-clock text-blue-600 text-lg"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Hourly Rate</p>
                                <div class="price-tag">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span><?php echo number_format($row['rate_per_hour'], 2); ?> PKR</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="century-badge">
                            <i class="fas fa-crown"></i>
                            <span>Century: <?php echo $row['century_rate']; ?> PKR/min</span>
                        </div>
                    </div>
                </div>
                
                <!-- Current Status -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <p class="text-gray-700 text-sm mb-2">Current Status</p>
                    <div class="flex items-center justify-between">
                        <span class="text-xl font-bold <?php echo $row['status'] == 'Free' ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                        <i class="fas fa-<?php echo $row['status'] == 'Free' ? 'smile' : 'users'; ?> text-2xl <?php echo $row['status'] == 'Free' ? 'text-green-500' : 'text-red-500'; ?>"></i>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3">
                    <button onclick="showEditRow(<?php echo $row['id']; ?>)"
                            class="action-btn edit-btn">
                        <i class="fas fa-edit"></i>
                        <span>Edit Table</span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Edit Modal (Hidden by default) -->
        <div id="edit-modal-<?php echo $row['id']; ?>" 
             class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 edit-form">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-edit text-blue-600 mr-2"></i>
                        Edit Table
                    </h3>
                    <button onclick="hideEditModal(<?php echo $row['id']; ?>)"
                            class="text-gray-400 hover:text-gray-600 text-xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form onsubmit="updateTable(event, <?php echo $row['id']; ?>)" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">Table Name</label>
                        <input type="text" 
                               id="table_name_<?php echo $row['id']; ?>" 
                               value="<?php echo htmlspecialchars($row['table_name']); ?>"
                               class="form-input w-full"
                               placeholder="Enter table name"
                               required>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">Hourly Rate (PKR)</label>
                            <div class="relative">
                                <input type="number" 
                                       id="rate_hour_<?php echo $row['id']; ?>" 
                                       value="<?php echo $row['rate_per_hour']; ?>" 
                                       step="0.01"
                                       min="0"
                                       class="form-input w-full pl-10"
                                       required>
                                <span class="absolute left-3 top-3 text-gray-500">PKR</span>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">Century Rate (PKR/min)</label>
                            <div class="relative">
                                <input type="number" 
                                       id="century_rate_<?php echo $row['id']; ?>" 
                                       value="<?php echo $row['century_rate']; ?>" 
                                       step="0.01"
                                       min="0"
                                       class="form-input w-full pl-10"
                                       required>
                                <span class="absolute left-3 top-3 text-gray-500">PKR</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">Status</label>
                        <select class="form-input w-full" id="status_<?php echo $row['id']; ?>">
                            <option value="Free" <?php echo $row['status'] == 'Free' ? 'selected' : ''; ?>>Free</option>
                            <option value="Occupied" <?php echo $row['status'] == 'Occupied' ? 'selected' : ''; ?>>Occupied</option>
                            <option value="Maintenance" <?php echo $row['status'] == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2 font-medium">Active Status</label>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="radio" 
                                       name="is_active_<?php echo $row['id']; ?>" 
                                       value="1" 
                                       <?php echo $row['is_active'] == 1 ? 'checked' : ''; ?>
                                       class="mr-2">
                                <span class="text-green-600">Active</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" 
                                       name="is_active_<?php echo $row['id']; ?>" 
                                       value="0"
                                       <?php echo $row['is_active'] == 0 ? 'checked' : ''; ?>
                                       class="mr-2">
                                <span class="text-red-600">Inactive</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" 
                                onclick="hideEditModal(<?php echo $row['id']; ?>)" 
                                class="action-btn cancel-btn">
                            <i class="fas fa-times"></i>
                            <span>Cancel</span>
                        </button>
                        
                        <button type="submit" 
                                class="action-btn save-btn">
                            <i class="fas fa-save"></i>
                            <span>Save Changes</span>
                            <div class="loading-spinner" id="loading-<?php echo $row['id']; ?>"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl p-6 shadow border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Total Tables</p>
                    <p class="text-3xl font-bold text-gray-800">
                        <?php 
                            $total = $conn->query("SELECT COUNT(*) as count FROM snooker_tables")->fetch_assoc()['count'];
                            echo $total;
                        ?>
                    </p>
                </div>
                <i class="fas fa-table text-blue-500 text-3xl"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Active Tables</p>
                    <p class="text-3xl font-bold text-gray-800"><?php echo $active_count; ?></p>
                </div>
                <i class="fas fa-check-circle text-green-500 text-3xl"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Average Rate</p>
                    <p class="text-3xl font-bold text-gray-800">
                        <?php 
                            $avg_rate = $conn->query("SELECT AVG(rate_per_hour) as avg FROM snooker_tables WHERE is_active = 1")->fetch_assoc()['avg'];
                            echo number_format($avg_rate, 2); 
                        ?> PKR
                    </p>
                </div>
                <i class="fas fa-chart-line text-orange-500 text-3xl"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Free Tables</p>
                    <p class="text-3xl font-bold text-gray-800">
                        <?php 
                            $free = $conn->query("SELECT COUNT(*) as count FROM snooker_tables WHERE status = 'Free' AND is_active = 1")->fetch_assoc()['count'];
                            echo $free;
                        ?>
                    </p>
                </div>
                <i class="fas fa-smile text-purple-500 text-3xl"></i>
            </div>
        </div>
    </div>

</main>

<!-- Toast Notification -->
<div id="toast" class="toast hidden">
    <i class="fas fa-check-circle"></i>
    <span id="toast-message"></span>
</div>

<script>
// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    
    toastMessage.textContent = message;
    toast.className = `toast toast-${type}`;
    toast.classList.remove('hidden');
    
    setTimeout(() => {
        toast.classList.add('hidden');
    }, 3000);
}

// Modal functions
function showEditRow(id) {
    const modal = document.getElementById(`edit-modal-${id}`);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function hideEditModal(id) {
    const modal = document.getElementById(`edit-modal-${id}`);
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = 'auto';
}

// Update table function
function updateTable(event, id) {
    event.preventDefault();
    
    const tableName = document.getElementById(`table_name_${id}`).value;
    const rateHour = document.getElementById(`rate_hour_${id}`).value;
    const centuryRate = document.getElementById(`century_rate_${id}`).value;
    const status = document.getElementById(`status_${id}`).value;
    const isActive = document.querySelector(`input[name="is_active_${id}"]:checked`).value;
    
    // Show loading spinner
    const loadingSpinner = document.getElementById(`loading-${id}`);
    const saveBtn = event.target.querySelector('.save-btn');
    saveBtn.disabled = true;
    loadingSpinner.style.display = 'block';
    
    $.ajax({
        url: 'ajax_update_table.php',
        type: 'POST',
        data: {
            id: id,
            table_name: tableName,
            rate_hour: rateHour,
            century_rate: centuryRate,
            status: status,
            is_active: isActive
        },
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                showToast(response.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast(response.message, 'error');
                saveBtn.disabled = false;
                loadingSpinner.style.display = 'none';
            }
        },
        error: function() {
            showToast('An error occurred. Please try again.', 'error');
            saveBtn.disabled = false;
            loadingSpinner.style.display = 'none';
        }
    });
}

// Close modal on outside click
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('fixed')) {
        const modals = document.querySelectorAll('[id^="edit-modal-"]');
        modals.forEach(modal => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
        document.body.style.overflow = 'auto';
    }
});

// Add keyboard shortcut for escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('[id^="edit-modal-"]');
        modals.forEach(modal => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
        document.body.style.overflow = 'auto';
    }
});

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to cards
    const cards = document.querySelectorAll('.table-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

</body>
</html>