<?php
session_start();
include 'db.php'; 

// Protect page
if (!isset($_SESSION['admin_name'])) {
    header("Location: index.php");
    exit;
}

$message = "";
$table_data = null;
$table_id = $_GET['id'] ?? null;

// Ensure a valid table ID is provided
if (!$table_id || !is_numeric($table_id)) {
    die("Invalid table ID.");
}

// --- A. Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_table'])) {
    
    $table_id = $_POST['table_id'];
    $table_name = trim($_POST['table_name']);
    $rate_per_hour = (float)$_POST['rate_per_hour'];
    $century_rate = (float)$_POST['century_rate'];

    // Validation (optional, but recommended)
    if (empty($table_name) || $rate_per_hour <= 0 || $century_rate <= 0) {
        $message = "❌ Error: All fields must be filled, and rates must be positive numbers.";
    } else {
        // Prepare and execute the UPDATE statement
        $stmt = $conn->prepare("
            UPDATE snooker_tables 
            SET table_name = ?, rate_per_hour = ?, century_rate = ? 
            WHERE id = ?
        ");
        // 'sddi' stands for string, double, double, integer
        $stmt->bind_param("sddi", $table_name, $rate_per_hour, $century_rate, $table_id);
        
        if ($stmt->execute()) {
            $message = "🎉 **" . htmlspecialchars($table_name) . "** details updated successfully!";
        } else {
            $message = "❌ Database Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// --- B. Fetch Current Table Data (GET Request or after POST update) ---
$stmt_fetch = $conn->prepare("SELECT id, table_name, rate_per_hour, century_rate FROM snooker_tables WHERE id = ?");
$stmt_fetch->bind_param("i", $table_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
$table_data = $result_fetch->fetch_assoc();
$stmt_fetch->close();

if (!$table_data) {
    die("Table not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Table: <?php echo htmlspecialchars($table_data['table_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-8">

    <div class="max-w-xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <h1 class="text-3xl font-bold mb-4 text-gray-700 border-b pb-2">
            ✏️ Edit Table: <?php echo htmlspecialchars($table_data['table_name']); ?>
        </h1>

        <?php if (!empty($message)) { ?>
            <div class="mb-4 p-3 <?php echo (strpos($message, 'Error') !== false || strpos($message, '❌') !== false) ? 'bg-red-100 text-red-700 border border-red-300' : 'bg-green-100 text-green-700 border border-green-300'; ?> rounded">
                <?php echo $message; ?>
            </div>
        <?php } ?>

        <form method="post" class="space-y-6">
            <input type="hidden" name="table_id" value="<?php echo htmlspecialchars($table_data['id']); ?>">

            <div>
                <label for="table_name" class="block text-gray-700 font-medium mb-1">Table Name</label>
                <input type="text" name="table_name" id="table_name" required
                       value="<?php echo htmlspecialchars($table_data['table_name']); ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="rate_per_hour" class="block text-gray-700 font-medium mb-1">Rate Per Hour (PKR)</label>
                <input type="number" name="rate_per_hour" id="rate_per_hour" step="0.01" min="0.01" required
                       value="<?php echo htmlspecialchars($table_data['rate_per_hour']); ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="century_rate" class="block text-gray-700 font-medium mb-1">Century Rate (PKR)</label>
                <input type="number" name="century_rate" id="century_rate" step="0.01" min="0.01" required
                       value="<?php echo htmlspecialchars($table_data['century_rate']); ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex justify-between pt-4">
                <button type="submit" name="update_table"
                        class="bg-green-600 text-white px-5 py-2 rounded hover:bg-green-700 transition">
                    Update Table Details
                </button>

                <a href="admin.php"
                   class="bg-gray-300 px-5 py-2 rounded hover:bg-gray-400 transition">
                    Cancel & Back
                </a>
            </div>
        </form>
    </div>
</body>
</html>