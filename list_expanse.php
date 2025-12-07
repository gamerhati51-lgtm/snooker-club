<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = "SAEED GUEST";
}

/* -----------------------------------------------------
   AJAX HANDLER – RETURN SINGLE EXPENSE DATA
----------------------------------------------------- */
if (isset($_GET['ajax_get'])) {
    $id = $_GET['ajax_get'];
    $q = $conn->query("
        SELECT e.expanses_id, e.expanses_date, e.details, e.amount, ec.category_name 
        FROM expanses e
        JOIN expanses_categories ec ON e.category_id = ec.category_id
        WHERE e.expanses_id = '$id'
    ");
    echo json_encode($q->fetch_assoc());
    exit;
}

/* -----------------------------------------------------
   AJAX HANDLER – UPDATE EXPENSE
----------------------------------------------------- */
if (isset($_POST['ajax_update'])) {
    $id = $_POST['id'];
    $date = $_POST['date'];
    $category = $_POST['category'];
    $details = $_POST['details'];
    $amount = $_POST['amount'];

    // Get category ID
    $catQ = $conn->query("SELECT category_id FROM expanses_categories WHERE category_name='$category'");
    $catID = $catQ->fetch_assoc()['category_id'] ?? 1;

    $conn->query("
        UPDATE expanses SET 
            expanses_date='$date',
            category_id='$catID',
            details='$details',
            amount='$amount'
        WHERE expanses_id='$id'
    ");

    echo "success";
    exit;
}
// -------------------------
// AJAX DELETE HANDLER
// -------------------------
if (isset($_POST['ajax_delete'])) {
    $id = $_POST['id'];
    $conn->query("DELETE FROM expanses WHERE expanses_id='$id'");
    echo "deleted";
    exit;
}

/* -----------------------------------------------------
   FETCH ALL EXPENSES + TOTAL
----------------------------------------------------- */
$expanses_query = $conn->query("
    SELECT e.expanses_id, e.expanses_date, e.details, e.amount, ec.category_name
    FROM expanses e
    JOIN expanses_categories ec ON e.category_id = ec.category_id
    ORDER BY e.expanses_date DESC, e.expanses_id DESC
");
$all_expanses = $expanses_query ? $expanses_query->fetch_all(MYSQLI_ASSOC) : [];

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
<body class="bg-blue-100 font-sans">

  <!-- Dashboard Container -->
  <div class="flex min-h-screen">

    <!-- Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 ml-0 lg:ml-64 pt-20 p-8 main-content"> <!-- pt-20 = header height -->
      
      <!-- Header -->
      <?php include "layout/header.php"; ?>

        <h1 class="text-4xl font-extrabold mb-4 text-snooker-green text-center">💰Club Expense History</h1>

        <div class="bg-white p-6 rounded-xl shadow-lg mb-6 border-l-8 border-red-500">
            <p class="text-sm text-gray-500 font-medium uppercase text-center">Total Recorded Expenses</p>
            <p class="text-4xl font-extrabold text-red-700 mt-2 text-center">PKR <?php echo number_format($total_expenses, 2); ?></p>
        </div>

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
                            <td class="px-6 py-4 text-sm font-medium text-gray-700">
                                <?= date('d M, Y', strtotime($expense['expanses_date'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-snooker-green">
                                <?= htmlspecialchars($expense['category_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($expense['details'] ?: '-'); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-right font-extrabold text-red-600">
                                <?= number_format($expense['amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4 text-center flex justify-center gap-2">

                                <!-- EDIT BUTTON -->
                                <button onclick="openModal(<?= $expense['expanses_id']; ?>)"
                                    class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition">
                                    Edit
                                </button>

                            <button onclick="deleteExpense(<?= $expense['expanses_id']; ?>)"
    class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition">
    Delete
</button>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 italic">No expenses recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- =======================
     EDIT MODAL
=========================== -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-xl shadow-xl w-full max-w-md">
        <h2 class="text-2xl font-bold mb-4">Edit Expense</h2>

        <form id="editForm">
            <input type="hidden" id="edit_id">

            <label class="block mb-2 font-semibold">Date</label>
            <input type="date" id="edit_date" class="w-full p-2 border rounded mb-3">

            <label class="block mb-2 font-semibold">Category</label>
            <input type="text" id="edit_category" class="w-full p-2 border rounded mb-3">

            <label class="block mb-2 font-semibold">Details</label>
            <input type="text" id="edit_details" class="w-full p-2 border rounded mb-3">

            <label class="block mb-2 font-semibold">Amount</label>
            <input type="number" id="edit_amount" class="w-full p-2 border rounded mb-4">

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-500 text-white rounded">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById("editModal").classList.remove("hidden");

    fetch("?ajax_get=" + id)
        .then(res => res.json())
        .then(data => {
            document.getElementById("edit_id").value = data.expanses_id;
            document.getElementById("edit_date").value = data.expanses_date;
            document.getElementById("edit_category").value = data.category_name;
            document.getElementById("edit_details").value = data.details;
            document.getElementById("edit_amount").value = data.amount;
        });
}

function closeModal() {
    document.getElementById("editModal").classList.add("hidden");
}

document.getElementById("editForm").addEventListener("submit", function(e) {
    e.preventDefault();

    let formData = new FormData();
    formData.append("ajax_update", 1);
    formData.append("id", document.getElementById("edit_id").value);
    formData.append("date", document.getElementById("edit_date").value);
    formData.append("category", document.getElementById("edit_category").value);
    formData.append("details", document.getElementById("edit_details").value);
    formData.append("amount", document.getElementById("edit_amount").value);

    fetch("", { method: "POST", body: formData })
        .then(res => res.text())
        .then(result => {
            alert("Expense updated successfully!");
            location.reload();
        });
});
function deleteExpense(id) {
    if (!confirm('Are you sure you want to delete this expense?')) return;

    let formData = new FormData();
    formData.append("ajax_delete", 1);
    formData.append("id", id);

    fetch("", { method: "POST", body: formData })
        .then(res => res.text())
        .then(result => {
            if(result === "deleted") {
                alert("Expense deleted successfully!");
                location.reload();
            } else {
                alert("Failed to delete expense.");
            }
        });
}

</script>

</body>
</html>
