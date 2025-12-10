<?php
session_start();
include 'db.php';

// Protect page
if (!isset($_SESSION['admin_name'])) {
    header("Location: index.php");
    exit;
}

$success_message = "";
$error_message = "";

// Fetch current admin data
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['admin_email']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Get admin statistics - using proper null coalescing
$stats_stmt = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM products) as total_products,
        (SELECT COUNT(*) FROM snooker_tables) as total_tables,
        (SELECT COUNT(*) FROM snooker_sessions WHERE DATE(start_time) = CURDATE()) as today_sessions
");
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
if (isset($_POST['update_settings'])) {
    // Debug what's being submitted
    echo "<!-- DEBUG: Password value: " . htmlspecialchars($_POST['password']) . " -->";
    echo "<!-- DEBUG: Confirm password: " . htmlspecialchars($_POST['confirm_password']) . " -->";
    
    $new_email = trim($_POST['email']);
    $new_password = trim($_POST['password']);
    // ... rest of your code
    
    // Validate email
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        // Check if email already exists for another user
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_email->bind_param("si", $new_email, $admin['id']);
        $check_email->execute();
        $email_result = $check_email->get_result();
        
        if ($email_result->num_rows > 0) {
            $error_message = "This email is already registered to another user.";
        } else {
            // Prepare password update
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET email = ?, password = ?, updated_at = NOW() WHERE id = ?");
                $update_stmt->bind_param("ssi", $new_email, $hashed_password, $admin['id']);
            } else {
                // If no password change, only update email
                $update_stmt = $conn->prepare("UPDATE users SET email = ?, updated_at = NOW() WHERE id = ?");
                $update_stmt->bind_param("si", $new_email, $admin['id']);
            }
            
            if ($update_stmt->execute()) {
                $success_message = "Settings updated successfully!";
                $_SESSION['admin_email'] = $new_email;
                
                // Refresh admin data
                $refresh_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $refresh_stmt->bind_param("i", $admin['id']);
                $refresh_stmt->execute();
                $refresh_result = $refresh_stmt->get_result();
                $admin = $refresh_result->fetch_assoc();
            } else {
                $error_message = "Error updating settings: " . $update_stmt->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings | Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.1);
        }
        
        .settings-content {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.97);
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
        }
        
        .form-input {
            padding-left: 40px;
            padding-top: 0.85rem;
            padding-bottom: 0.4rem;
            transition: all 0.2s ease;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            background: transparent;
            width: 100%;
        }
        
        .form-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
        }
        
        .floating-label {
            position: absolute;
            left: 40px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            transition: all 0.2s ease;
            font-size: 0.95rem;
            background: transparent;
        }
        
        .form-input:focus + .floating-label,
        .form-input:not(:placeholder-shown) + .floating-label {
            top: 6px;
            left: 10px;
            font-size: 0.7rem;
            color: #667eea;
            font-weight: 500;
        }
        
        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .animate-success {
            animation: successPulse 1.5s ease;
        }
        
        /* Added missing progress bar styles */
        .progress-bar {
            width: 100%;
            height: 6px;
            background-color: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 4px;
        }
        
        .progress-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
            border-radius: 3px;
        }
        
        @keyframes successPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans">

<!-- Sidebar -->
<?php include 'layout/sidebar.php'; ?>

<!-- Main Content -->
<div class="ml-64 min-h-screen">
    <!-- Header -->
    <?php include 'layout/header.php'; ?>
    
    <!-- Main Content Area -->
    <main class="pt-20 p-6">
        <div class="max-w-6xl mx-auto">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">
                            <i class="fas fa-cog text-blue-600 mr-2"></i>
                            Admin Settings
                        </h1>
                        <p class="text-gray-600 mt-2">Manage your account settings and preferences</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                            <i class="fas fa-user-shield mr-1"></i>
                            Administrator
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($success_message): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg animate-success">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                        <div>
                            <p class="text-green-800 font-medium">Success!</p>
                            <p class="text-green-700"><?php echo htmlspecialchars($success_message); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                        <div>
                            <p class="text-red-800 font-medium">Error!</p>
                            <p class="text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Admin Info & Stats -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Admin Profile Card -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <!-- Profile Info -->
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                                <?php echo strtoupper(substr($admin['name'] ?? 'A', 0, 1)); ?>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($admin['name'] ?? 'Administrator'); ?></h3>
                                <p class="text-gray-600"><?php echo htmlspecialchars($admin['email'] ?? ''); ?></p>
                                <span class="inline-block mt-1 px-3 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                                    <?php echo htmlspecialchars($admin['role'] ?? 'Admin'); ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- System Statistics -->
                        <div class="bg-gradient-to-br from-gray-800 to-gray-900 text-white rounded-xl shadow-md p-6">
                            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                                <i class="fas fa-chart-bar text-blue-300"></i>
                                System Overview
                            </h3>
                            
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-users text-white"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-300">Total Users</p>
                                            <p class="text-lg font-bold"><?php echo $stats['total_users'] ?? 0; ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-table-tennis text-white"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-300">Snooker Tables</p>
                                            <p class="text-lg font-bold"><?php echo $stats['total_tables'] ?? 0; ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-box text-white"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-300">Products</p>
                                            <p class="text-lg font-bold"><?php echo $stats['total_products'] ?? 0; ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-calendar-day text-white"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-300">Today's Sessions</p>
                                            <p class="text-lg font-bold"><?php echo $stats['today_sessions'] ?? 0; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <a href="user.php" class="flex items-center gap-3 p-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition">
                                <i class="fas fa-users"></i>
                                <span>Manage Users</span>
                            </a>
                            <a href="list_product.php" class="flex items-center gap-3 p-3 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition">
                                <i class="fas fa-box"></i>
                                <span>Product Inventory</span>
                            </a>
                            <a href="dashboard.php" class="flex items-center gap-3 p-3 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>View Dashboard</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Settings Form -->
                <div class="lg:col-span-2">
                    <div class="settings-card">
                        <div class="p-1">
                            <div class="settings-content rounded-xl p-8">
                                <!-- Form Header -->
                                <div class="flex items-center gap-4 mb-8">
                                    <div class="p-3 bg-blue-100 rounded-lg">
                                        <i class="fas fa-user-cog text-blue-600 text-2xl"></i>
                                    </div>
                                    <div>
                                        <h2 class="text-2xl font-bold text-gray-800">Account Settings</h2>
                                        <p class="text-gray-600">Update your account information and security settings</p>
                                    </div>
                                </div>
                                
                                <form method="post" id="settingsForm" class="space-y-6">
                                    <!-- Account Information Section -->
                                    <div class="border-b pb-6">
                                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                            <i class="fas fa-user-circle text-blue-500"></i>
                                            Account Information
                                        </h3>
                                        
                                       <!-- Name Field - FIXED -->
<div class="input-group">
    <div class="input-icon">
        <i class="fas fa-user"></i>
    </div>
    <input 
        type="text" 
        class="form-input bg-gray-50"
        value="<?php echo htmlspecialchars($admin['name'] ?? ''); ?>"
        disabled
    >
    <span class="floating-label">Full Name</span>
    <span class="text-xs text-gray-500 mt-1 block">Contact admin to change name</span>
</div>

<!-- Email Field -->
<div class="input-group">
    <div class="input-icon">
        <i class="fas fa-envelope"></i>
    </div>
    <input 
        type="email" 
        name="email" 
        id="email"
        class="form-input"
        placeholder=" "
        value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>"
        required
    >
    <label for="email" class="floating-label">Email Address</label>
</div>
                                         
                                        
                                    </div>
                                    
                                    <!-- Security Settings Section -->
                                    <div class="border-b pb-6">
                                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                            <i class="fas fa-shield-alt text-green-500"></i>
                                            Security Settings
                                        </h3>
                                        
                                        <!-- Password Field -->
                                        <div class="input-group">
                                            <div class="input-icon">
                                                <i class="fas fa-lock"></i>
                                            </div>
                                            <input 
                                                type="password" 
                                                name="password" 
                                                id="password"
                                                class="form-input"
                                                placeholder=" "
                                            >
                                            <label for="password" class="floating-label">New Password (leave blank to keep current)</label>
                                            <div class="password-toggle" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </div>
                                        </div>
                                        
                                        <!-- Confirm Password Field -->
                                        <div class="input-group">
                                            <div class="input-icon">
                                                <i class="fas fa-lock"></i>
                                            </div>
                                            <input 
                                                type="password" 
                                                name="confirm_password" 
                                                id="confirm_password"
                                                class="form-input"
                                                placeholder=" "
                                            >
                                            <label for="confirm_password" class="floating-label">Confirm New Password</label>
                                            <div class="password-toggle" id="toggleConfirmPassword">
                                                <i class="fas fa-eye"></i>
                                            </div>
                                        </div>
                                        
                                        <!-- Password Strength Indicator -->
                                        <div class="mt-2">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs text-gray-600">Password Strength</span>
                                                <span class="text-xs text-gray-600" id="passwordStrength"></span>
                                            </div>
                                            <div class="progress-bar">
                                                <div class="progress-fill" id="passwordStrengthBar"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    
                                    <!-- Form Actions -->
                                    <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t">
                                        <button type="submit" name="update_settings"
                                                class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:from-blue-700 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg">
                                            <i class="fas fa-save mr-2"></i>
                                            Save Changes
                                        </button>
                                        <button type="button" onclick="resetForm()"
                                                class="flex-1 bg-white border border-gray-300 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-50 transition">
                                            <i class="fas fa-redo mr-2"></i>
                                            Reset
                                        </button>
                                        <a href="dashboard.php"
                                           class="flex-1 bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-200 transition text-center">
                                            <i class="fas fa-times mr-2"></i>
                                            Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }
    
    if (toggleConfirmPassword && confirmPasswordInput) {
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }
    
    // Password strength checker
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
    }
    
    function checkPasswordStrength(password) {
        const strengthBar = document.getElementById('passwordStrengthBar');
        const strengthText = document.getElementById('passwordStrength');
        if (!strengthBar || !strengthText) return;
        
        let strength = 0;
        
        if (password.length >= 8) strength += 25;
        if (/[A-Z]/.test(password)) strength += 25;
        if (/[0-9]/.test(password)) strength += 25;
        if (/[^A-Za-z0-9]/.test(password)) strength += 25;
        
        strengthBar.style.width = strength + '%';
        
        if (strength < 25) {
            strengthBar.style.backgroundColor = '#ef4444';
            strengthText.textContent = 'Very Weak';
            strengthText.className = 'text-xs text-red-600';
        } else if (strength < 50) {
            strengthBar.style.backgroundColor = '#f97316';
            strengthText.textContent = 'Weak';
            strengthText.className = 'text-xs text-orange-600';
        } else if (strength < 75) {
            strengthBar.style.backgroundColor = '#eab308';
            strengthText.textContent = 'Good';
            strengthText.className = 'text-xs text-yellow-600';
        } else {
            strengthBar.style.backgroundColor = '#10b981';
            strengthText.textContent = 'Strong';
            strengthText.className = 'text-xs text-green-600';
        }
    }
    
    // Form validation
    const form = document.getElementById('settingsForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const password = passwordInput ? passwordInput.value : '';
            const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';
            
            if (password && password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                if (passwordInput) passwordInput.focus();
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                if (confirmPasswordInput) confirmPasswordInput.focus();
                return;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving Changes...';
                submitBtn.disabled = true;
                
                // Re-enable after 3 seconds in case of error
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    }
    
    // Reset form function
    window.resetForm = function() {
        if (confirm('Are you sure you want to reset all changes?')) {
            const emailField = document.getElementById('email');
            if (emailField) {
                emailField.value = '<?php echo htmlspecialchars($admin['email'] ?? ''); ?>';
            }
            
            if (passwordInput) passwordInput.value = '';
            if (confirmPasswordInput) confirmPasswordInput.value = '';
            
            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('passwordStrength');
            if (strengthBar) strengthBar.style.width = '0%';
            if (strengthText) strengthText.textContent = '';
        }
    };
});
</script>
</body>
</html>