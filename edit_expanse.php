<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = "SAEED GUEST";
}

$id = $_GET['id'] ?? 0;
$id = (int)$id;

// Fetch expense
$stmt = $conn->prepare("SELECT * FROM expanses WHERE expanses_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$expense = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch categories
$categories_result = $conn->query("SELECT category_id, category_name FROM expanses_categories ORDER BY category_name ASC");
$all_categories = $categories_result ? $categories_result->fetch_all(MYSQLI_ASSOC) : [];

$message = "";

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expanses_date = $_POST['expanses_date'];
    $category_id = (int)$_POST['category_id'];
    $details = trim($_POST['details']);
    $amount = (float)$_POST['amount'];

    if($amount <= 0 || empty($expanses_date) || $category_id <= 0){
        $message = "❌ Please fill all fields correctly.";
    } else {
        $stmt = $conn->prepare("UPDATE expanses SET expanses_date=?, category_id=?, details=?, amount=? WHERE expanses_id=?");
        $stmt->bind_param("sisdi", $expanses_date, $category_id, $details, $amount, $id);
        if($stmt->execute()){
            $_SESSION['message'] = "✅ Expense updated successfully!";
            header("Location: list-expanse.php");
            exit();
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Expense</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans">
<div class="flex min-h-screen">
<?php include 'layout/sidebar.php'; ?>
<main class="flex-1 ml-0 lg:ml-64 pt-20 p-8">
<?php include 'layout/header.php'; ?>

<h1 class="text-4xl font-extrabold mb-6 text-snooker-green">✏️ Edit Expense</h1>

<?php if($message): ?>
<div class="mb-6 p-4 bg-red-100 text-red-700 rounded shadow"><?php echo $message; ?></div>
<?php endif; ?>

<div class="bg-white p-6 rounded-xl shadow-xl max-w-lg">
<form method="POST" class="space-y-4">
<div>
<label class="block mb-1 font-medium text-gray-700">Date</label>
<input type="date" name="expanses_date" required value="<?php echo $expense['expanses_date']; ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-snooker-accent">
</div>

<div>
<label class="block mb-1 font-medium text-gray-700">Category</label>
<select name="category_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-snooker-accent">
<option value="">-- Select Category --</option>
<?php foreach($all_categories as $cat): ?>
<option value="<?php echo $cat['category_id']; ?>" <?php if($expense['category_id']==$cat['category_id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
<?php endforeach; ?>
</select>
</div>

<div>
<label class="block mb-1 font-medium text-gray-700">Details</label>
<textarea name="details" rows="2" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-snooker-accent"><?php echo htmlspecialchars($expense['details']); ?></textarea>
</div>

<div>
<label class="block mb-1 font-medium text-gray-700">Amount (PKR)</label>
<input type="number" name="amount" step="0.01" min="0.01" required value="<?php echo $expense['amount']; ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-snooker-accent">
</div>

<button type="submit" class="w-full bg-blue-500 text-white font-bold py-3 rounded-lg hover:bg-blue-600 transition shadow-lg">Update Expense</button>
</form>
</div>
</main>
</div>
</body>
</html>
