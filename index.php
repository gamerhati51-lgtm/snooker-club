<?php
session_start();
include 'db.php';

// If already logged in, redirect to admin
if (isset($_SESSION['admin_name'])) {
    header("Location: admin.php");
    exit;
}

$error = "";
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();                                                                                                                                                               
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();         

        // Login check
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_name'] = $user['name'];
            $_SESSION['admin_email'] = $user['email'];
            header("Location: admin.php");
            exit;
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "Email not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Club Snoker Login</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="w-full max-w-md p-8 bg-white rounded-lg shadow">

      <!-- Logo -->
    <div class="flex justify-center mb-0">
        <img src="images/logo.png" 
             alt="Logo" 
             class="w-28 h-28 object-contain mb-0">
    </div>
    <h2 class="text-2xl font-bold mb-1 text-center text-black-700 mt-0">LOGIN</h2>
<h5 class="text-center mb-2 text-blue-400">Please login to continue</h5>
    <?php if(!empty($error)) { ?>
        <p class="text-red-500 mb-4"><?php echo htmlspecialchars($error); ?></p>
    <?php } ?>

    <form method="post" class="space-y-4" autocomplete="off">

        <div>
            <label class="block text-gray-700">Email</label>
            <input type="email" name="email" required
                class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-400">
        </div>

        <div class="relative">
            <label class="block text-gray-700 mb-1">Password</label>
            <input type="password" id="password" name="password" required autocomplete="off"
                class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-400">

            <!-- Eye Button -->
            <button type="button" id="togglePassword"
                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 mt-3">
                👁️
            </button>
        </div>

        <button type="submit" name="login"
            class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
            Login
        </button>

    </form>

</div>

</body>

<script src="script.js"></script>
</body>
</html>
