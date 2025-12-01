<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = "SAEED GUEST";
}

// Fetch all expenses with category
$expanses_query = $conn->query("
    SELECT e.expanses_id, e.expanses_date, e.details, e.amount, ec.category_name
    FROM expanses e
    JOIN expanses_categories ec ON e.category_id = ec.category_id
    ORDER BY e.expanses_date DESC, e.expanses_id DESC
");
$all_expanses = $expanses_query ? $expanses_query->fetch_all(MYSQLI_ASSOC) : [];


// Total expenses
$total_expenses_result = $conn->query("SELECT SUM(amount) AS total FROM expanses");
$total_expenses = $total_expenses_result ? $total_expenses_result->fetch_assoc()['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>List Expenses | Snooker Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans">

<div class="flex min-h-screen">
    <?php include 'layout/sidebar.php'; ?>

    <main class="flex-1 ml-0 lg:ml-64 pt-20 p-8">
        <?php include "layout/header.php"; ?>

        <h1 class="text-4xl font-extrabold mb-4 text-snooker-green text-center">💰Club Expense History</h1>

        <!-- Summary -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-6 border-l-8 border-red-500">
            <p class="text-sm text-gray-500 font-medium uppercase text-center">Total Recorded Expenses</p>
            <p class="text-4xl font-extrabold text-red-700 mt-2 text-center">PKR <?php echo number_format($total_expenses, 2); ?></p>
        </div>

        <!-- Table -->
        <div class="bg-white shadow-xl p-6 rounded-xl border border-gray-100">
            <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
    <tr>
        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Category</th>
        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Details</th>
        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Amount (PKR)</th>
        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Actions</th>
    </tr>
</thead>
<tbody class="bg-white divide-y divide-gray-200">
<?php if(!empty($all_expanses)): ?>
    <?php foreach($all_expanses as $expense): ?>
        <tr class="hover:bg-gray-50 transition">
            <td class="px-6 py-4 text-sm font-medium text-gray-700"><?php echo date('d M, Y', strtotime($expense['expanses_date'])); ?></td>
            <td class="px-6 py-4 text-sm font-semibold text-snooker-green"><?php echo htmlspecialchars($expense['category_name']); ?></td>
            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($expense['details'] ?: '-'); ?></td>
            <td class="px-6 py-4 text-sm text-right font-extrabold text-red-600"><?php echo number_format($expense['amount'], 2); ?></td>
            <td class="px-6 py-4 text-center flex justify-center gap-2">
                <a href="edit-expanse.php?id=<?php echo $expense['expanses_id']; ?>" 
                   class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition">Edit</a>
                <a href="delete-expanse.php?id=<?php echo $expense['expanses_id']; ?>" 
                   onclick="return confirm('Are you sure you want to delete this expense?');" 
                   class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="5" class="px-6 py-4 text-center text-gray-500 italic">No expenses recorded yet.</td>
    </tr>
<?php endif; ?>
</tbody>


            </table>
        </div>

    </main>
</div>

</body>
</html>
