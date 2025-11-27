<?php
session_start();
include 'db.php';

if(!isset($_SESSION['admin_name'])){
    header("Location: index.php");
    exit;
}

if(!isset($_GET['id'])){
    header("Location: user.php");
    exit;
}

$id = intval($_GET['id']);

// Fetch selected user
$stmt = $conn->prepare("SELECT id, name, username, role, status FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if(!$user){
    echo "User not found!";
    exit;
}

// Update user
if($_SERVER['REQUEST_METHOD'] == "POST"){
    $status = $_POST['status'];

    $update = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $update->bind_param("si", $status, $id);

    if($update->execute()){
        header("Location: user.php?updated=1");
        exit;
    } else {
        echo "Error updating user.";
    }
}
?>
<!doctype html>
<html>
<head>
    <title>Edit User</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">

<!-- Sidebar -->
<?php include 'layout/sidebar.php'; ?>

<!-- Main Section -->
<div class="flex-1 p-8 ml-64">

   
<div class="max-w-md mx-auto bg-white shadow p-6 rounded">
    <h2 class="text-xl font-semibold mb-4">Edit User Status</h2>

    <form method="POST">

        <div class="mb-3">
            <label class="block text-gray-700 font-medium">Name:</label>
            <p class="border px-3 py-2 rounded bg-gray-100"><?= $user['name']; ?></p>
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 font-medium">Username:</label>
            <p class="border px-3 py-2 rounded bg-gray-100"><?= $user['username']; ?></p>
        </div>

        <div class="mb-3">
            <label class="block text-gray-700 font-medium">Status:</label>
            <select name="status" class="border px-3 py-2 rounded w-full">
                <option value="Active"   <?= $user['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= $user['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <button 
            type="submit"
            class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
            Save Changes
        </button>

    </form>

</div>

</body>
</html>
