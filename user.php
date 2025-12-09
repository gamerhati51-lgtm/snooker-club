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
  <title>Users</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
      background-color: white;
      margin: 10% auto;
      padding: 20px;
      border-radius: 8px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body class="bg-gray-100 min-h-screen flex">

<!-- Sidebar -->
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/header.php'; ?>

<!-- Main Section -->
<div class="flex-1 p-8 ml-64 mt-9">

  <!-- Success/Error Messages -->
  <div id="message" class="hidden fixed top-4 right-4 z-50"></div>

  <div class="bg-white rounded shadow">
    <!-- Header -->
    <div class="px-6 py-4 border-b font-semibold text-lg text-gray-700 flex justify-between items-center">
      <span>Users</span>
      <a href="add_user.php"
         class="inline-block bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
        + Add User
      </a>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead class="border-b bg-gray-50">
          <tr class="text-gray-600 text-sm">
            <th class="px-6 py-3">Name</th>
            <th class="px-6 py-3">Role</th>
            <th class="px-6 py-3">Username</th>
            <th class="px-6 py-3">Status</th>
            <th class="px-6 py-3 text-center">Action</th>
          </tr>
        </thead>

        <tbody class="divide-y bg-white">
          <?php if ($users): ?>
            <?php foreach ($users as $u): ?>
              <tr class="hover:bg-gray-50" id="user-row-<?= $u['id'] ?>">
                <td class="px-6 py-3"><?= htmlspecialchars($u['name']) ?></td>
                <td class="px-6 py-3"><?= htmlspecialchars($u['role']) ?></td>
                <td class="px-6 py-3"><?= htmlspecialchars($u['username']) ?></td>

                <td class="px-6 py-3">
                  <span id="status-<?= $u['id'] ?>" class="font-medium <?= strtolower($u['status']) === 'active' ? 'text-green-600' : 'text-red-600' ?>">
                    <?= htmlspecialchars($u['status']) ?>
                  </span>
                </td>

                <td class="px-6 py-3 text-center">
                  <!-- EDIT BUTTON -->
                  <button 
                    onclick="openEditModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name']) ?>', '<?= htmlspecialchars($u['username']) ?>', '<?= htmlspecialchars($u['status']) ?>')"
                    class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition me-2">
                    Edit
                  </button>

                  <!-- DELETE BUTTON -->
                  <a 
                    href="delete_user.php?id=<?= $u['id'] ?>" 
                    onclick="return confirm('Are you sure you want to delete this user?')"
                    class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">
                    Delete
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="px-6 py-4 text-center text-gray-400">
                No users found.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-semibold">Edit User Status</h2>
      <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
    </div>
    
    <form id="editForm" method="POST">
      <input type="hidden" id="editUserId" name="id">
      
      <div class="mb-3">
        <label class="block text-gray-700 font-medium">Name:</label>
        <p id="editUserName" class="border px-3 py-2 rounded bg-gray-100"></p>
      </div>

      <div class="mb-3">
        <label class="block text-gray-700 font-medium">Username:</label>
        <p id="editUserUsername" class="border px-3 py-2 rounded bg-gray-100"></p>
      </div>

      <div class="mb-3">
        <label class="block text-gray-700 font-medium">Status:</label>
        <select name="status" id="editUserStatus" class="border px-3 py-2 rounded w-full">
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>
      </div>

      <div class="flex justify-end space-x-3 mt-6">
        <button 
          type="button"
          onclick="closeModal()"
          class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition">
          Cancel
        </button>
        <button 
          type="submit"
          class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
          Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<script>
let currentEditId = null;

function openEditModal(id, name, username, status) {
  currentEditId = id;
  document.getElementById('editUserId').value = id;
  document.getElementById('editUserName').textContent = name;
  document.getElementById('editUserUsername').textContent = username;
  document.getElementById('editUserStatus').value = status;
  document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
  document.getElementById('editModal').style.display = 'none';
  currentEditId = null;
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
          
          // Update color based on status
          if(response.newStatus === 'Active') {
            statusElement.className = 'font-medium text-green-600';
          } else {
            statusElement.className = 'font-medium text-red-600';
          }
          
          showMessage('User updated successfully!', 'success');
          closeModal();
        } else {
          showMessage('Error updating user: ' + response.error, 'error');
        }
      },
      error: function() {
        showMessage('Network error. Please try again.', 'error');
      }
    });
  });
});

function showMessage(text, type) {
  const messageDiv = document.getElementById('message');
  messageDiv.textContent = text;
  messageDiv.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
  messageDiv.classList.remove('hidden');
  
  setTimeout(() => {
    messageDiv.classList.add('hidden');
  }, 3000);
}
</script>
</body>
</html>