<?php
include 'db.php'; 

$error_message = '';
$success_message = '';
$form_data = ['name' => '', 'email' => '', 'role' => 'Cashier', 'status' => 'Active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];
    $status   = $_POST['status'];
    
    $form_data = ['name' => $name, 'email' => $email, 'role' => $role, 'status' => $status];

    if (empty($name) || empty($email) || empty($password)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE name = ? OR email = ?");
        $check->bind_param("ss", $name, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error_message = 'User already exists with same Name or Email!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status) 
                                    VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $status);

            if ($stmt->execute()) {
                $success_message = 'User added successfully!';
                $form_data = ['name' => '', 'email' => '', 'role' => 'Cashier', 'status' => 'Active'];
            } else {
                $error_message = 'Error adding user. Please try again.';
            }
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add New User</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .form-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 10px 25px rgba(102, 126, 234, 0.1);
    }
    
    .form-content {
      backdrop-filter: blur(10px);
      background: rgba(255, 255, 255, 0.97);
    }
    
    .input-group {
      position: relative;
      margin-bottom: 1rem; /* Reduced from 1.2rem */
    }
    
    .input-icon {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #667eea;
      font-size: 0.85rem; /* Smaller icon */
      z-index: 10;
    }
    
    .form-input {
      padding-left: 36px; /* Reduced from 38px */
      padding-top: 0.7rem; /* Reduced from 0.85rem */
      padding-bottom: 0.3rem; /* Reduced from 0.4rem */
      transition: all 0.2s ease;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      font-size: 0.9rem; /* Smaller font */
      background: transparent;
      width: 100%;
      position: relative;
      z-index: 5;
      height: 42px; /* Fixed height */
    }
    
    .form-input:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      outline: none;
    }
    
    .password-toggle {
      position: absolute;
      right: 10px; /* Reduced from 12px */
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #94a3b8;
      font-size: 0.85rem; /* Smaller icon */
      z-index: 10;
    }
    
    .role-badge {
      display: inline-flex;
      align-items: center;
      padding: 0.2rem 0.6rem; /* Smaller padding */
      border-radius: 1rem;
      font-size: 0.75rem; /* Smaller font */
      font-weight: 500;
    }
    
    .role-badge.admin {
      background-color: #fef3c7;
      color: #92400e;
    }
    
    .role-badge.cashier {
      background-color: #dbeafe;
      color: #1e40af;
    }
    
    .role-badge.staff {
      background-color: #dcfce7;
      color: #166534;
    }
    
    .status-dot {
      width: 5px;
      height: 5px;
      border-radius: 50%;
      display: inline-block;
      margin-right: 3px;
    }
    
    .status-dot.active {
      background-color: #10b981;
    }
    
    .status-dot.inactive {
      background-color: #ef4444;
    }
    
    .floating-label {
      position: absolute;
      left: 36px; /* Adjusted to match input padding */
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
      pointer-events: none;
      transition: all 0.2s ease;
      font-size: 0.9rem; /* Smaller font */
      z-index: 1;
      background: transparent;
    }
    
    .form-input:focus + .floating-label,
    .form-input:not(:placeholder-shown) + .floating-label {
      top: 4px; /* Reduced from 6px */
      left: 8px; /* Reduced from 10px */
      font-size: 0.65rem; /* Smaller font */
      color: #667eea;
      font-weight: 500;
      z-index: 10;
    }
    
    /* For select elements */
    select.form-input + .floating-label {
      top: 4px; /* Reduced from 6px */
      left: 8px; /* Reduced from 10px */
      font-size: 0.65rem; /* Smaller font */
      color: #667eea;
      font-weight: 500;
      z-index: 10;
    }
    
    select.form-input:focus + .floating-label,
    select.form-input:valid + .floating-label {
      top: 4px; /* Reduced from 6px */
      left: 8px; /* Reduced from 10px */
      font-size: 0.65rem; /* Smaller font */
      color: #667eea;
      font-weight: 500;
      z-index: 10;
    }
    
    .animate-success {
      animation: successPulse 1.5s ease;
    }
    
    @keyframes successPulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.8; }
    }
  </style>
</head>
<body class="bg-blue-50 min-h-screen">

<!-- Sidebar -->
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/header.php'; ?>

<!-- Main Content -->
<div class="flex-1 p-4 ml-64 mt-4 mb-4"> <!-- Reduced padding -->
  <!-- Page Header -->
  <div class="mb-1"> <!-- Reduced margin -->
    <div class="flex items-center">
      <!-- Title centered in remaining space -->
      <div class="flex-1 text-center">
        <h1 class="text-2xl font-bold text-gray-800">Add New User</h1>
        <p class="text-gray-600 text-sm mt-1">Create a new user account with appropriate permissions</p>
      </div>
      
      <!-- Button on right -->
      <div class="ml-auto">
        <a href="user.php" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-indigo-600 transition bg-white px-4 py-2 rounded-lg border border-gray-300 hover:border-indigo-300 shadow-sm">
          <i class="fas fa-arrow-left"></i>
          Back to Users
        </a>
      </div>
    </div>
  </div>

  <!-- Success/Error Messages -->
  <?php if ($success_message): ?>
  <div class="mb-2 p-3 bg-green-50 border border-green-200 rounded-lg animate-success max-w-4xl mx-auto"> <!-- Increased width -->
    <div class="flex items-center gap-3">
      <i class="fas fa-check-circle text-green-500"></i>
      <div class="text-left">
        <p class="text-green-800 font-medium">Success!</p>
        <p class="text-green-700 text-sm"><?php echo htmlspecialchars($success_message); ?></p>
      </div>
    </div>
  </div>
  <?php endif; ?>
  
  <?php if ($error_message): ?>
  <div class="mb-2 p-3 bg-red-50 border border-red-200 rounded-lg max-w-4xl mx-auto"> <!-- Increased width -->
    <div class="flex items-center gap-3">
      <i class="fas fa-exclamation-circle text-red-500"></i>
      <div class="text-left">
        <p class="text-red-800 font-medium">Error!</p>
        <p class="text-red-700 text-sm"><?php echo htmlspecialchars($error_message); ?></p>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="max-w-4xl mx-auto"> <!-- Increased from max-w-3xl to max-w-4xl -->
    <!-- Form Card -->
    <div class="form-card">
      <div class="p-1">
        <div class="form-content rounded-lg p-5"> <!-- Reduced padding from p-6 to p-5 -->
          <!-- Form Header -->
          <div class="flex items-center gap-2 mb-2 text-left"> <!-- Reduced margin and gap -->
            <div class="p-2 bg-indigo-100 rounded-lg">
              <i class="fas fa-user-plus text-indigo-600"></i> <!-- Removed text-lg -->
            </div>
            <div>
              <h2 class="text-base font-semibold text-gray-800">User Information</h2> <!-- Reduced font size -->
              <p class="text-gray-600 text-xs">Fill in the details below to create a new user account</p> <!-- Smaller text -->
            </div>
          </div>
          
          <form method="post" id="addUserForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left"> <!-- Reduced gap from 5 to 4 -->
              <!-- Name Field -->
              <div class="input-group">
                <div class="input-icon">
                  <i class="fas fa-user"></i>
                </div>
                <input 
                  type="text" 
                  name="name" 
                  id="name"
                  class="form-input"
                  placeholder=" "
                  value="<?php echo htmlspecialchars($form_data['name']); ?>"
                  required
                >
                <label for="name" class="floating-label">Full Name</label>
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
                  value="<?php echo htmlspecialchars($form_data['email']); ?>"
                  required
                >
                <label for="email" class="floating-label">Email Address</label>
              </div>
              
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
                  required
                >
                <label for="password" class="floating-label">Password</label>
                <div class="password-toggle" id="togglePassword">
                  <i class="fas fa-eye"></i>
                </div>
              </div>
              
              <!-- Role Selection -->
              <div class="input-group">
                <div class="input-icon">
                  <i class="fas fa-user-tag"></i>
                </div>
                <select name="role" id="role" class="form-input" required>
                  <option value="" disabled style="display:none;"></option>
                  <option value="Cashier" <?php echo $form_data['role'] == 'Cashier' ? 'selected' : ''; ?>>Cashier</option>
                  <option value="Admin" <?php echo $form_data['role'] == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                  <option value="Staff" <?php echo $form_data['role'] == 'Staff' ? 'selected' : ''; ?>>Staff</option>
                </select>
                <label for="role" class="floating-label">User Role</label>
              </div>
              
              <!-- Status Selection -->
              <div class="input-group">
                <div class="input-icon">
                  <i class="fas fa-circle"></i>
                </div>
                <select name="status" id="status" class="form-input" required>
                  <option value="" disabled style="display:none;"></option>
                  <option value="Active" <?php echo $form_data['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                  <option value="Inactive" <?php echo $form_data['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                <label for="status" class="floating-label">Account Status</label>
              </div>
            </div>
            
            <!-- Preview Card -->
            <div class="mt-2 p-4 bg-gradient-to-r from-gray-50 to-indigo-50 rounded-lg border border-gray-200 text-left"> <!-- Reduced margin and padding -->
              <h3 class="text-sm font-semibold text-gray-700 mb-2">User Preview</h3> <!-- Smaller font -->
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3"> <!-- Reduced gap -->
                <div>
                  <p class="text-xs text-gray-500 mb-1">Name</p>
                  <p id="previewName" class="font-medium text-gray-800 text-sm">
                    <?php echo htmlspecialchars($form_data['name']) ?: 'John Doe'; ?>
                  </p>
                </div>
                <div>
                  <p class="text-xs text-gray-500 mb-1">Email</p>
                  <p id="previewEmail" class="font-medium text-gray-800 text-sm">
                    <?php echo htmlspecialchars($form_data['email']) ?: 'example@email.com'; ?>
                  </p>
                </div>
                <div>
                  <p class="text-xs text-gray-500 mb-1">Role</p>
                  <span id="previewRole" class="role-badge <?php echo strtolower($form_data['role']); ?>">
                    <?php echo htmlspecialchars($form_data['role']) ?: 'Cashier'; ?>
                  </span>
                </div>
                <div>
                  <p class="text-xs text-gray-500 mb-1">Status</p>
                  <div class="flex items-center">
                    <span class="status-dot <?php echo strtolower($form_data['status']) ?: 'active'; ?>"></span>
                    <span id="previewStatus" class="font-medium text-gray-800 text-sm">
                      <?php echo htmlspecialchars($form_data['status']) ?: 'Active'; ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Form Actions -->
            <div class="mt-2 flex flex-col sm:flex-row gap-2"> <!-- Reduced margin and gap -->
              <button type="submit" class="flex-1 bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-2 rounded-lg font-medium hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg text-center text-sm"> <!-- Smaller padding and font -->
                <i class="fas fa-user-plus mr-2"></i>
                Create User
              </button>
              <a href="user.php" class="flex-1 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-50 transition text-center text-sm"> <!-- Smaller padding and font -->
                <i class="fas fa-times mr-2"></i>
                Cancel
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- Information Cards -->
    <div class="mt-9 grid grid-cols-1 md:grid-cols-3 gap-3 text-left"> <!-- Reduced margin and gap -->
      <div class="bg-white p-3 rounded-lg border border-gray-200"> <!-- Reduced padding -->
        <div class="flex items-center gap-2"> <!-- Reduced gap -->
          <div class="p-1.5 bg-blue-50 rounded"> <!-- Smaller padding -->
            <i class="fas fa-shield-alt text-blue-600 text-xs"></i> <!-- Smaller icon -->
          </div>
          <div>
            <h4 class="text-xs font-semibold text-gray-800">Secure Access</h4> <!-- Smaller font -->
            <p class="text-xs text-gray-600 mt-0.5">Passwords are securely hashed</p> <!-- Smaller margin -->
          </div>
        </div>
      </div>
      
      <div class="bg-white p-3 rounded-lg border border-gray-200">
        <div class="flex items-center gap-2">
          <div class="p-1.5 bg-green-50 rounded">
            <i class="fas fa-user-check text-green-600 text-xs"></i>
          </div>
          <div>
            <h4 class="text-xs font-semibold text-gray-800">Role Management</h4>
            <p class="text-xs text-gray-600 mt-0.5">Assign appropriate roles</p>
          </div>
        </div>
      </div>
      
      <div class="bg-white p-3 rounded-lg border border-gray-200">
        <div class="flex items-center gap-2">
          <div class="p-1.5 bg-purple-50 rounded">
            <i class="fas fa-history text-purple-600 text-xs"></i>
          </div>
          <div>
            <h4 class="text-xs font-semibold text-gray-800">Quick Setup</h4>
            <p class="text-xs text-gray-600 mt-0.5">Activate users immediately</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Password toggle
  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');
  
  togglePassword.addEventListener('click', function() {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
  });
  
  // Real-time preview updates
  const nameInput = document.getElementById('name');
  const emailInput = document.getElementById('email');
  const roleSelect = document.getElementById('role');
  const statusSelect = document.getElementById('status');
  
  const previewName = document.getElementById('previewName');
  const previewEmail = document.getElementById('previewEmail');
  const previewRole = document.getElementById('previewRole');
  const previewStatus = document.getElementById('previewStatus');
  
  function updatePreview() {
    previewName.textContent = nameInput.value || 'John Doe';
    previewEmail.textContent = emailInput.value || 'example@email.com';
    
    const role = roleSelect.value;
    previewRole.textContent = role || 'Cashier';
    previewRole.className = `role-badge ${(role || 'cashier').toLowerCase()}`;
    
    const status = statusSelect.value;
    previewStatus.textContent = status || 'Active';
    
    // Update all status dots on page
    document.querySelectorAll('.status-dot').forEach(dot => {
      dot.className = `status-dot ${(status || 'active').toLowerCase()}`;
    });
  }
  
  // Initialize floating labels for select elements
  function initSelectLabels() {
    const selects = document.querySelectorAll('select.form-input');
    selects.forEach(select => {
      // Set the floating label position if a value is selected
      if (select.value) {
        const label = select.nextElementSibling;
        if (label && label.classList.contains('floating-label')) {
          label.style.top = '4px';
          label.style.left = '8px';
          label.style.fontSize = '0.65rem';
          label.style.color = '#667eea';
          label.style.fontWeight = '500';
          label.style.zIndex = '10';
        }
      }
      
      // Update on change
      select.addEventListener('change', function() {
        const label = this.nextElementSibling;
        if (label && label.classList.contains('floating-label')) {
          if (this.value) {
            label.style.top = '4px';
            label.style.left = '8px';
            label.style.fontSize = '0.65rem';
            label.style.color = '#667eea';
            label.style.fontWeight = '500';
            label.style.zIndex = '10';
          }
        }
        updatePreview();
      });
    });
  }
  
  // Add event listeners for input fields
  [nameInput, emailInput].forEach(element => {
    element.addEventListener('input', updatePreview);
  });
  
  // Initialize select labels
  initSelectLabels();
  
  // Also call updatePreview on role/status change
  roleSelect.addEventListener('change', updatePreview);
  statusSelect.addEventListener('change', updatePreview);
  
  // Form validation
  const form = document.getElementById('addUserForm');
  form.addEventListener('submit', function(e) {
    const password = passwordInput.value;
    
    if (password.length < 6) {
      e.preventDefault();
      alert('Password must be at least 6 characters long.');
      passwordInput.focus();
      return;
    }
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Creating User...';
    submitBtn.disabled = true;
    
    setTimeout(() => {
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    }, 4000);
  });
  
  // Initialize preview
  updatePreview();
  
  // Fix for floating labels on page load with prefilled data
  setTimeout(() => {
    if (nameInput.value) {
      const event = new Event('input');
      nameInput.dispatchEvent(event);
    }
    if (emailInput.value) {
      const event = new Event('input');
      emailInput.dispatchEvent(event);
    }
  }, 100);
});
</script>
</body>
</html>