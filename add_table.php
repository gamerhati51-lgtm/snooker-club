<?php
session_start();
include 'db.php';

// Protect page
if (!isset($_SESSION['admin_name'])) {
    header("Location: index.php");
    exit;
}

$message = "";

if (isset($_POST['add_table'])) {
    $table_name = trim($_POST['table_name']);
    $rate_per_hour = trim($_POST['rate_hour']);
    $century_rate = trim($_POST['century_rate']);

    // Insert query
    $stmt = $conn->prepare("
        INSERT INTO snooker_tables (table_name, rate_per_hour, century_rate, status) 
        VALUES (?, ?, ?, 'Free')
    ");
    $stmt->bind_param("sdd", $table_name, $rate_per_hour, $century_rate);

    if ($stmt->execute()) {
        $message = "🎉 Table added successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Snooker Table</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-50 font-sans">

  <!-- Dashboard Container -->
  <div class="flex min-h-screen">

    <!-- Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 ml-0 lg:ml-64 pt-20 p-8 main-content"> <!-- pt-20 = header height -->
      
      <!-- Header -->
      <?php include "layout/header.php"; ?>

      <!-- Page Content -->
      <div id="content-area" class="space-y-8 bg-gray-100 p-20 rounded-lg bg-grey-200 ">
    <h1 class="text-4xl font-bold mb-6 text-gray-700 mt-2">➕ Add New Snooker Table</h1>

    <!-- Alert Message -->
    <?php if (!empty($message)) { ?>
        <div class="mb-4 p-3 bg-green-100 text-green-700 border border-green-300 rounded">
            <?php echo $message; ?>
        </div>
    <?php } ?>

    <!-- Card UI -->
    <div class="bg-white shadow p-6 rounded-lg max-w-lg">

        <h2 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">
            Table Information
        </h2>

        <form method="post" class="space-y-4">

            <!-- Table Name -->
            <div>
                <label class="block text-gray-700 font-medium">Table Name</label>
                <input type="text" name="table_name" required
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-orange-700">
            </div>

            <!-- Rate Per Hour -->
            <div>
                <label class="block text-gray-700 font-medium">Rate (per hour)</label>
                <input type="number" name="rate_hour" step="0.01" required
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-orange-700">
            </div>

            <!-- Century Rate -->
            <div>
                <label class="block text-gray-700 font-medium">Century Rate (per minute)</label>
                <input type="number" name="century_rate" step="0.01" required
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-orange-700">
            </div>

            <!-- Buttons -->
            <div class="flex justify-between pt-4">
                <button type="submit" name="add_table"
                        class="bg-blue-900 text-white px-5 py-2 rounded hover:bg-blue-700 transition">
                    Save
                </button>

                <a href="add_table.php"
                   class="bg-orange-500 px-5 py-2 rounded  text-white hover:bg-red-400 transition">
                    Cancel
                </a>
            </div>

        </form>

    </div>

</div>
</body>
</html>
