<?php
include 'db.php'; // $conn

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST values
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // hashed
    $role     = $_POST['role'];
    $status   = $_POST['status'];

    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("sssss", $name, $email, $password, $role, $status);

    // Execute statement
    if ($stmt->execute()) {
        header('Location: user.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
<!doctype html>
<html>
<head>
  <title>Add User</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">

<!-- Sidebar -->
<?php include 'layout/sidebar.php'; ?>

<!-- Main Section -->
<div class="flex-1 p-8 ml-64">


<div class="max-w-xl mx-auto mt-10 bg-white p-6 rounded shadow">
  <h1 class="text-xl font-semibold mb-4">Add User</h1>
  <form method="post">
    <input name="name"     class="w-full mb-3 px-3 py-2 border rounded" placeholder="Full Name" required>
    <input name="email"    class="w-full mb-3 px-3 py-2 border rounded" placeholder="Email" type="email" required>
    <input name="password" class="w-full mb-3 px-3 py-2 border rounded" placeholder="Password" type="password" required>
    <select name="role"    class="w-full mb-3 px-3 py-2 border rounded">
      <option>Admin</option>
      <option>Cashier</option>
      <option>Staff</option>
    </select>
    <select name="status"  class="w-full mb-3 px-3 py-2 border rounded">
      <option>Active</option>
      <option>Inactive</option>
    </select>
    <button class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Save</button>
    <a href="user.php" class="ml-2 text-gray-600 hover:underline">Cancel</a>
  </form>
</div>
</body>
</html>
