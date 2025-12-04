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
</head>
<body class="bg-gray-100 min-h-screen flex">

<!-- Sidebar -->
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/header.php'; ?>
<!-- Main Section -->
<div class="flex-1 p-8 ml-64 mt-9">

  

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
        <th class="px-6 py-3 text-center">Action</th> <!-- NEW -->
      </tr>
    </thead>

    <tbody class="divide-y bg-white">
      <?php if ($users): ?>
        <?php foreach ($users as $u): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-6 py-3"><?= htmlspecialchars($u['name']) ?></td>
            <td class="px-6 py-3"><?= htmlspecialchars($u['role']) ?></td>
            <td class="px-6 py-3"><?= htmlspecialchars($u['username']) ?></td>

            <td class="px-6 py-3">
              <?= strtolower($u['status']) === 'active' 
                  ? '<span class="text-green-600 font-medium">Active</span>'
                  : '<span class="text-red-600 font-medium">Inactive</span>' ?>
            </td>

            <!-- DELETE BUTTON HERE -->
            <td class="px-6 py-3 text-center">
             <!-- EDIT BUTTON -->
    <a 
        href="edit_user.php?id=<?= $u['id'] ?>"
        class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition me-2">
        Edit
    </a>

    <!-- DELETE BUTTON -->
    <a 
        href="delete_user.php?id=<?= $u['id'] ?>" 
        onclick="return confirm('Are you sure you want to delete this user?')"
        class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">
        Delete
    </a>

</td>
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

</body>
</html>
