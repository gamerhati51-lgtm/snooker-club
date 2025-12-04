<?php
session_start();
include 'db.php';

// Protect page
if (!isset($_SESSION['admin_name'])) {
    header("Location: index.php");
    exit;
}

$message = "";
$error = "";

// Fetch current admin data
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['admin_email']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (isset($_POST['update_settings'])) {
    $new_email = trim($_POST['email']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $update_stmt = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $new_email, $hashed_password, $admin['id']);

        if ($update_stmt->execute()) {
            $message = "Settings updated successfully!";
            $_SESSION['admin_email'] = $new_email;
        } else {
            $error = "Error: " . $update_stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Settings</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex">

<!-- Sidebar -->
<?php include __DIR__ . '/layout/sidebar.php'; ?>
<?php include __DIR__ . '/layout/header.php'; ?>

<!-- Main content -->
<div class="flex-1 ml-64 p-6 flex flex-col items-center justify-center">

    <!-- Page Title -->
    <h1 class="text-3xl font-bold mb-6 text-center">Admin Settings</h1>

    <!-- Success / Error Messages -->
    <?php if (!empty($message)) { ?>
        <p class="mb-4 text-green-600 text-center"><?php echo $message; ?></p>
    <?php } ?>

    <?php if (!empty($error)) { ?>
        <p class="mb-4 text-red-600 text-center"><?php echo $error; ?></p>
    <?php } ?>

    <!-- Centered Form -->
    <form method="post" class="space-y-4 bg-white p-8 rounded-xl shadow-lg w-full max-w-md">

        <h2 class="text-2xl font-bold mb-4 text-center text-blue-700">Update Password</h2>

        <div>
            <label class="block text-gray-700">Email</label>
            <input type="email" name="email" required
                   value="<?php echo htmlspecialchars($admin['email']); ?>"
                   class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-400">
        </div>

        <div>
            <label class="block text-gray-700">New Password</label>
            <input type="password" name="password" required
                   class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-400">
        </div>

        <div>
            <label class="block text-gray-700">Confirm Password</label>
            <input type="password" name="confirm_password" required
                   class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-400">
        </div>

        <button type="submit" name="update_settings"
                class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-orange-600 transition">
            Update Settings
        </button>
    </form>

</div>
</body>
</html>
