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
<body class="relative min-h-screen flex items-center justify-center">

    <!-- Background Image -->
    <div class="absolute inset-0 bg-[url('images/background.jpg')] bg-cover bg-center"></div>

    <!-- Dark Overlay -->
    <div class="absolute inset-0 bg-black/50"></div>

 <div class="relative w-full max-w-md p-8 
            bg-white/10 backdrop-blur-2xl 
            rounded-2xl shadow-2xl 
            border border-white/20 
           ">

    <div class="flex justify-center mb-4">
    <div class="p-3 rounded-full bg-white shadow-lg">
        <img src="images/logo.png" 
             alt="Logo" 
             class="w-24 h-24 object-contain">
    </div>
</div>


    <!-- Heading -->
    <h2 class="text-3xl font-bold text-center text-white tracking-wide drop-shadow">
        LOGIN
    </h2>
    <h5 class="text-center mb-4 text-blue-200 drop-shadow">
        Please login to continue
    </h5>

    <!-- Error -->
    <?php if(!empty($error)) { ?>
        <p class="text-red-400 mb-4 text-center font-semibold">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php } ?>

    <!-- Form -->
    <form method="post" class="space-y-5" autocomplete="off">

        <!-- Email -->
        <div>
            <label class="block text-blue-100 font-medium">Email</label>
            <input type="email" name="email" required
                class="w-full px-3 py-2 rounded-lg bg-white/20 text-white 
                       placeholder-gray-300 border border-white/20
                       focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>

        <!-- Password -->
        <div class="relative">
            <label class="block text-blue-100 font-medium">Password</label>
            <input type="password" id="password" name="password" required autocomplete="off"
                class="w-full px-3 py-2 rounded-lg bg-white/20 text-white 
                       placeholder-gray-300 border border-white/20
                       focus:outline-none focus:ring-2 focus:ring-blue-400">

            <button type="button" id="togglePassword"
                class="absolute right-3 top-1/2 transform -translate-y-1/2
                       text-blue-200 hover:text-white mt-3">
                👁️
            </button>
        </div>

        <!-- Login Button -->
        <button type="submit" name="login"
            class="w-full bg-blue-600/80 backdrop-blur-md 
                   text-white py-2 rounded-lg shadow-md
                   hover:bg-blue-700 transition">
            Login
        </button>

    </form>

</div>
<script src="script.js"></script>

</body>
