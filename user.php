<?php
session_start();
include 'db.php'; // includes $conn (MySQLi)

// Protect page
if(!isset($_SESSION['admin_name'])){
    header("Location: index.php");
    exit;
}

// Fetch users
$sql = "SELECT id, name, username, role, status FROM users ORDER BY id DESC";
$result = $conn->query($sql);

$users = [];
if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $users[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management | Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      font-family: 'Inter', sans-serif;
    }
    
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .modal.active {
      opacity: 1;
    }
    
    .modal-content {
      background-color: white;
      margin: 10% auto;
      padding: 0;
      border-radius: 16px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.2);
      transform: translateY(-20px);
      transition: transform 0.4s ease;
      overflow: hidden;
    }
    
    .modal.active .modal-content {
      transform: translateY(0);
    }
    
    .status-badge {
      display: inline-flex;
      align-items: center;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 500;
    }
    
    .status-active {
      background-color: #f0fdf4;
      color: #16a34a;
    }
    
    .status-inactive {
      background-color: #fef2f2;
      color: #dc2626;
    }
    
    .user-table tbody tr {
      transition: all 0.2s ease;
    }
    
    .user-table tbody tr:hover {
      background-color: #f8fafc;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    .action-btn {
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    
    .action-btn:hover {
      transform: translateY(-2px);
    }
    
    .fade-in {
      animation: fadeIn 0.5s ease forwards;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .page-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 16px;
      position: relative;
      overflow: hidden;
    }
    
    .page-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    
    .glass-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">

<!-- Sidebar -->
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/header.php'; ?>

<!-- Main Section -->
<div class="flex-1 p-8 ml-64 mt-9">

  <!-- Success/Error Messages -->
  <div id="message" class="hidden fixed top-6 right-6 z-50 max-w-sm"></div>

  <!-- Header Section -->
  <div class="page-header p-8 mb-8 text-white fade-in">
    <div class="relative z-10">
      <h1 class="text-3xl font-bold mb-2">User Management</h1>
      <p class="text-white/90">Manage all user accounts, roles, and permissions in one place</p>
    </div>
    <div class="absolute top-4 right-6 z-10">
      <span class="bg-white/20 backdrop-blur-sm px-4 py-2 rounded-full text-sm">
        <i class="fas fa-users mr-2"></i>
        <?php echo count($users); ?> Users
      </span>
    </div>
  </div>

  <!-- Main Content Card -->
  <div class="glass-card rounded-2xl shadow-xl overflow-hidden mb-8 fade-in" style="animation-delay: 0.1s">
    
    <!-- Card Header -->
    <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">All Users</h2>
        <p class="text-gray-500 text-sm mt-1">View, edit, and manage user accounts</p>
      </div>
      <a href="add_user.php"
         class="inline-flex items-center bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-5 py-2.5 rounded-lg hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5">
        <i class="fas fa-plus-circle mr-2"></i>
        Add New User
      </a>
    </div>

    <!-- Table Container -->
    <div class="overflow-x-auto">
      <table class="w-full user-table">
        <thead class="bg-gray-50/80">
          <tr class="text-gray-600 text-sm">
            <th class="px-8 py-4 font-medium text-gray-700">User</th>
            <th class="px-8 py-4 font-medium text-gray-700">Role</th>
            <th class="px-8 py-4 font-medium text-gray-700">Username</th>
            <th class="px-8 py-4 font-medium text-gray-700">Status</th>
            <th class="px-8 py-4 font-medium text-gray-700 text-center">Actions</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-100">
          <?php if ($users): ?>
            <?php foreach ($users as $index => $u): ?>
              <tr class="fade-in" id="user-row-<?= $u['id'] ?>" style="animation-delay: <?= 0.2 + ($index * 0.05) ?>s">
                <td class="px-8 py-4">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-full flex items-center justify-center mr-4">
                      <span class="font-semibold text-indigo-600">
                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                      </span>
                    </div>
                    <div>
                      <div class="font-medium text-gray-800"><?= htmlspecialchars($u['name']) ?></div>
                      <div class="text-gray-500 text-sm">ID: #<?= $u['id'] ?></div>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-4">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                    <?= $u['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' ?>">
                    <i class="fas <?= $u['role'] === 'admin' ? 'fa-crown' : 'fa-user' ?> mr-2 text-xs"></i>
                    <?= htmlspecialchars($u['role']) ?>
                  </span>
                </td>
                <td class="px-8 py-4">
                  <div class="flex items-center text-gray-700">
                    <i class="fas fa-at text-gray-400 mr-2"></i>
                    <?= htmlspecialchars($u['username']) ?>
                  </div>
                </td>
                <td class="px-8 py-4">
                  <span id="status-<?= $u['id'] ?>" class="status-badge <?= strtolower($u['status']) === 'active' ? 'status-active' : 'status-inactive' ?>">
                    <i class="fas fa-circle text-xs mr-2 <?= strtolower($u['status']) === 'active' ? 'text-green-500' : 'text-red-500' ?>"></i>
                    <?= htmlspecialchars($u['status']) ?>
                  </span>
                </td>
                <td class="px-8 py-4">
                  <div class="flex justify-center space-x-3">
                    <!-- EDIT BUTTON -->
                    <button 
                      onclick="openEditModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name']) ?>', '<?= htmlspecialchars($u['username']) ?>', '<?= htmlspecialchars($u['status']) ?>')"
                      class="action-btn bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-600 px-4 py-2 rounded-lg border border-blue-100 hover:shadow-md hover:border-blue-200">
                      <i class="fas fa-edit mr-2"></i>
                      Edit
                    </button>

                    <!-- DELETE BUTTON -->
                    <a 
                      href="delete_user.php?id=<?= $u['id'] ?>" 
                      onclick="return confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name']) ?>')"
                      class="action-btn bg-gradient-to-r from-red-50 to-pink-50 text-red-600 px-4 py-2 rounded-lg border border-red-100 hover:shadow-md hover:border-red-200">
                      <i class="fas fa-trash-alt mr-2"></i>
                      Delete
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="px-8 py-12 text-center">
                <div class="flex flex-col items-center justify-center text-gray-400">
                  <i class="fas fa-users text-5xl mb-4 opacity-50"></i>
                  <h3 class="text-lg font-medium mb-2">No Users Found</h3>
                  <p class="mb-6">Get started by adding your first user</p>
                  <a href="add_user.php" class="text-indigo-600 hover:text-indigo-700 font-medium">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Add New User
                  </a>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    
    <!-- Footer Stats -->
    <?php if ($users): ?>
    <div class="px-8 py-4 border-t border-gray-100 bg-gray-50/50 text-sm text-gray-600">
      <div class="flex justify-between items-center">
        <div>
          Showing <span class="font-medium"><?= count($users) ?></span> user<?= count($users) !== 1 ? 's' : '' ?>
        </div>
        <div class="flex items-center space-x-6">
          <div>
            <i class="fas fa-circle text-green-500 mr-2"></i>
            <span class="font-medium">
              <?= count(array_filter($users, fn($u) => strtolower($u['status']) === 'active')) ?>
            </span> Active
          </div>
          <div>
            <i class="fas fa-circle text-red-500 mr-2"></i>
            <span class="font-medium">
              <?= count(array_filter($users, fn($u) => strtolower($u['status']) === 'inactive')) ?>
            </span> Inactive
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-8 py-6 text-white">
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-bold">Edit User Status</h2>
          <p class="text-white/80 text-sm mt-1">Update user account status</p>
        </div>
        <button onclick="closeModal()" class="text-white/80 hover:text-white text-2xl transition">
          &times;
        </button>
      </div>
    </div>
    
    <!-- Modal Body -->
    <div class="px-8 py-6">
      <form id="editForm" method="POST">
        <input type="hidden" id="editUserId" name="id">
        
        <!-- User Info -->
        <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200">
          <div class="flex items-center mb-4">
            <div class="flex-shrink-0 h-12 w-12 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-full flex items-center justify-center mr-4">
              <i class="fas fa-user text-indigo-600 text-lg"></i>
            </div>
            <div>
              <div class="font-medium text-gray-800" id="editUserName"></div>
              <div class="text-gray-500 text-sm">Username: <span id="editUserUsername" class="font-medium"></span></div>
            </div>
          </div>
        </div>

        <!-- Status Selection -->
        <div class="mb-8">
          <label class="block text-gray-700 font-medium mb-3">Account Status</label>
          <div class="grid grid-cols-2 gap-4">
            <label class="relative">
              <input type="radio" name="status" value="Active" class="hidden peer">
              <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-green-500 peer-checked:bg-green-50 cursor-pointer transition-all">
                <div class="flex items-center">
                  <div class="h-5 w-5 rounded-full border-2 border-gray-300 peer-checked:border-green-500 peer-checked:bg-green-500 mr-3 flex items-center justify-center">
                    <i class="fas fa-check text-white text-xs"></i>
                  </div>
                  <div>
                    <div class="font-medium text-gray-800">Active</div>
                    <div class="text-gray-500 text-sm mt-1">User can access the system</div>
                  </div>
                </div>
              </div>
            </label>
            
            <label class="relative">
              <input type="radio" name="status" value="Inactive" class="hidden peer">
              <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-red-500 peer-checked:bg-red-50 cursor-pointer transition-all">
                <div class="flex items-center">
                  <div class="h-5 w-5 rounded-full border-2 border-gray-300 peer-checked:border-red-500 peer-checked:bg-red-500 mr-3 flex items-center justify-center">
                    <i class="fas fa-check text-white text-xs"></i>
                  </div>
                  <div>
                    <div class="font-medium text-gray-800">Inactive</div>
                    <div class="text-gray-500 text-sm mt-1">User access is restricted</div>
                  </div>
                </div>
              </div>
            </label>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
          <button 
            type="button"
            onclick="closeModal()"
            class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition font-medium">
            Cancel
          </button>
          <button 
            type="submit"
            class="px-6 py-3 rounded-lg bg-gradient-to-r from-indigo-600 to-purple-600 text-white hover:shadow-lg transition font-medium flex items-center">
            <i class="fas fa-save mr-2"></i>
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let currentEditId = null;

// Open modal with smooth animation
function openEditModal(id, name, username, status) {
  currentEditId = id;
  document.getElementById('editUserId').value = id;
  document.getElementById('editUserName').textContent = name;
  document.getElementById('editUserUsername').textContent = username;
  
  // Set the radio button based on status
  document.querySelectorAll('input[name="status"]').forEach(radio => {
    radio.checked = radio.value === status;
  });
  
  const modal = document.getElementById('editModal');
  modal.style.display = 'block';
  setTimeout(() => {
    modal.classList.add('active');
  }, 10);
}

// Close modal with smooth animation
function closeModal() {
  const modal = document.getElementById('editModal');
  modal.classList.remove('active');
  setTimeout(() => {
    modal.style.display = 'none';
    currentEditId = null;
  }, 300);
}

// Enhanced delete confirmation
function confirmDelete(id, name) {
  Swal.fire({
    title: 'Are you sure?',
    html: `<div class="text-center">
             <div class="mx-auto mb-4 h-16 w-16 bg-red-100 rounded-full flex items-center justify-center">
               <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
             </div>
             <p class="text-gray-700 mb-2">You are about to delete:</p>
             <p class="font-bold text-lg text-gray-900">${name}</p>
             <p class="text-gray-500 text-sm mt-3">This action cannot be undone.</p>
           </div>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel',
    reverseButtons: true,
    customClass: {
      confirmButton: 'px-6 py-3 rounded-lg',
      cancelButton: 'px-6 py-3 rounded-lg'
    }
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = `delete_user.php?id=${id}`;
    }
  });
  return false;
}

// Close modal when clicking outside
window.onclick = function(event) {
  const modal = document.getElementById('editModal');
  if (event.target == modal) {
    closeModal();
  }
}

// Handle form submission with AJAX
$(document).ready(function() {
  $('#editForm').submit(function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.html();
    
    // Show loading state
    submitBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Saving...');
    submitBtn.prop('disabled', true);
    
    $.ajax({
      url: 'ajax_update_user.php',
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function(response) {
        if(response.success) {
          // Update the status display
          const statusElement = document.getElementById(`status-${currentEditId}`);
          statusElement.textContent = response.newStatus;
          
          // Update status badge
          if(response.newStatus === 'Active') {
            statusElement.className = 'status-badge status-active';
            statusElement.innerHTML = '<i class="fas fa-circle text-xs mr-2 text-green-500"></i>' + response.newStatus;
          } else {
            statusElement.className = 'status-badge status-inactive';
            statusElement.innerHTML = '<i class="fas fa-circle text-xs mr-2 text-red-500"></i>' + response.newStatus;
          }
          
          showMessage('User updated successfully!', 'success');
          closeModal();
        } else {
          showMessage('Error updating user: ' + response.error, 'error');
        }
      },
      error: function() {
        showMessage('Network error. Please try again.', 'error');
      },
      complete: function() {
        submitBtn.html(originalText);
        submitBtn.prop('disabled', false);
      }
    });
  });
});

// Enhanced notification system
function showMessage(text, type) {
  const messageDiv = document.getElementById('message');
  
  // Create icon based on type
  const icon = type === 'success' 
    ? '<i class="fas fa-check-circle mr-3"></i>' 
    : '<i class="fas fa-exclamation-circle mr-3"></i>';
  
  messageDiv.innerHTML = `
    <div class="flex items-center p-4 rounded-xl shadow-lg ${type === 'success' ? 'bg-green-50 border-l-4 border-green-500' : 'bg-red-50 border-l-4 border-red-500'}">
      ${icon}
      <span class="${type === 'success' ? 'text-green-800' : 'text-red-800'} font-medium">${text}</span>
    </div>
  `;
  
  messageDiv.classList.remove('hidden');
  
  // Add animation
  messageDiv.style.animation = 'fadeIn 0.3s ease forwards';
  
  setTimeout(() => {
    messageDiv.style.animation = 'fadeIn 0.3s ease reverse forwards';
    setTimeout(() => {
      messageDiv.classList.add('hidden');
      messageDiv.style.animation = '';
    }, 300);
  }, 3000);
}

// Add row hover effects
document.addEventListener('DOMContentLoaded', function() {
  const rows = document.querySelectorAll('.user-table tbody tr');
  rows.forEach(row => {
    row.addEventListener('mouseenter', function() {
      this.style.zIndex = '1';
    });
    row.addEventListener('mouseleave', function() {
      this.style.zIndex = '0';
    });
  });
});

// Initialize SweetAlert if not already loaded
if (typeof Swal === 'undefined') {
  const script = document.createElement('script');
  script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
  document.head.appendChild(script);
}
</script>
</body>
</html>