<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = "SAEED GUEST";
}

$message = "";

// --- Add Expense ---
if(isset($_POST['action']) && $_POST['action'] === 'add_expense'){
    $expanses_date = $_POST['expanses_date'];
    $category_id = (int)$_POST['category_id'];
    $details = trim($_POST['details']);
    $amount = (float)$_POST['amount'];

    if($amount <= 0 || empty($expanses_date) || $category_id <= 0){
        $message = "❌ Please fill all required fields correctly.";
    } else {
        $stmt = $conn->prepare("INSERT INTO expanses (expanses_date, category_id, details, amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sisd", $expanses_date, $category_id, $details, $amount);
        if($stmt->execute()){
            $_SESSION['message'] = "✅ Expense added successfully!";
            header("Location: list_expance.php");
            exit();
        }
        $stmt->close();
    }
}

// Fetch categories
$categories_result = $conn->query("SELECT category_id, category_name FROM expanses_categories ORDER BY category_name ASC");
$all_categories = $categories_result ? $categories_result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Expense | Snooker Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans">

<div class="flex min-h-screen">
    <?php include 'layout/sidebar.php'; ?>

    <main class="flex-1 ml-0 lg:ml-64 pt-20 p-8">
        <?php include "layout/header.php"; ?>

        <h1 class="text-4xl font-extrabold mb-6 text-snooker-green">💸 Add New Expense</h1>

        <?php if(!empty($message)): ?>
            <div class="mb-6 p-4 <?php echo (strpos($message, '❌')!==false) ? 'bg-red-100 text-red-700' : 'bg-snooker-light/10 text-snooker-green'; ?> rounded-lg shadow-md">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-xl shadow-xl border border-gray-100 max-w-lg">
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_expense">

                <div>
                    <label class="block mb-1 font-medium text-gray-700">Date</label>
                    <input type="date" name="expanses_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-snooker-accent">
                </div>

                <div>
                    <label class="block mb-1 font-medium text-gray-700">Category</label>
                    <select name="category_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-snooker-accent">
                        <option value="">-- Select Category --</option>
                        <?php foreach($all_categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block mb-1 font-medium text-gray-700">Details (Optional)</label>
                    <textarea name="details" rows="2" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-snooker-accent" placeholder="e.g., Monthly electricity bill, staff bonus"></textarea>
                </div>

                <div>
                    <label class="block mb-1 font-medium text-gray-700">Amount (PKR)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="e.g., 24000.00" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-snooker-accent">
                </div>

 <button type="submit" 
 class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-red-700 transition shadow-lg">Save Expense</button>

            </form>
        </div>

    </main>
</div>

</body>
</html>
