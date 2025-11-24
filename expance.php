<?php
session_start();
include 'db.php'; // Includes your database connection





// Simulate a logged-in admin
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = "SAEED GUEST"; 
}

$message = "";

// --- 1. Handle Form Submission (Add New Expense) ---
if (isset($_POST['action']) && $_POST['action'] === 'add_expense') {
    $expanses_date = $_POST['expanses_date'];
    $category_id = (int)$_POST['category_id'];
    $details = trim($_POST['details']);
    $amount = (float)$_POST['amount'];
    
    // Validation (basic check)
    if ($amount <= 0 || empty($expanses_date) || $category_id <= 0) {
        $message = "❌ Error: Please ensure all required fields (Date, Category, Amount) are filled correctly.";
    } else {
        $stmt = $conn->prepare("INSERT INTO expanses (expanses_date, category_id, details, amount) VALUES (?, ?, ?, ?)");
     $stmt->bind_param("sisd", $expanses_date, $category_id, $details, $amount);
if ($stmt->execute()) {
    $_SESSION['message'] = "✅ New expense recorded successfully: PKR " . number_format($amount, 2);

    // Prevent duplicate on page refresh
    header("Location: expance.php");
    exit();
}

        $stmt->close();
    }
}


// --- 2. Fetch Data for Display ---

// A. Fetch all expense categories
$categories_result = $conn->query("SELECT category_id, category_name FROM expanses_categories ORDER BY category_name ASC");
$all_categories = $categories_result ? $categories_result->fetch_all(MYSQLI_ASSOC) : [];

// B. Fetch all expenses with category name (last 30 days or all)
$expanses_query = $conn->query("
    SELECT 
        e.expanses_date, 
        e.details, 
        e.amount, 
        ec.category_name
    FROM 
        expanses e
    JOIN 
        expanses_categories ec ON e.category_id = ec.category_id
    -- WHERE e.expanses_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) -- Uncomment to limit to last 30 days
    ORDER BY 
        e.expanses_date DESC, e.expanses_id DESC
");
$all_expanses = $expanses_query ? $expanses_query->fetch_all(MYSQLI_ASSOC) : [];

// C. Calculate Total Expenses
$total_expenses_result = $conn->query("SELECT SUM(amount) AS total FROM expanses");
$total_expenses = $total_expenses_result ? $total_expenses_result->fetch_assoc()['total'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expenses | Saeed Snooker Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CRITICAL: Tailwind Configuration must be in the head -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'snooker-green': '#183a34',
                        'snooker-light': '#2a4d45',
                        'snooker-accent': '#ffb703',
                        'snooker-bg': '#f3ffec',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <!-- End of Tailwind Config -->

    <style>
        /* Shared link styling for the sidebar */
        .sidebar-link {
            border-left: 4px solid transparent;
            color: #ccc;
        }
        .sidebar-link:hover {
            color: white;
        }
    </style>
</head>
<body class="bg-snooker-bg min-h-screen font-sans">
    
    <div class="relative min-h-screen"> 

        <!-- 1. Include the FIXED Sidebar (w-64) -->
        <?php include 'layout/sidebar.php'; ?>

        <!-- 2. Main Content Area: ml-64 shifts all content away from the sidebar's w-64 space -->
        <div class="ml-64 p-8 max-w-full">
            
            <h1 class="text-4xl font-extrabold mb-4 text-snooker-green">💸 Expenses Tracker</h1>

            <!-- Message Alert -->
            <?php if (!empty($message)) { ?>
                <div class="mb-6 p-4 <?php echo (strpos($message, 'Error') !== false || strpos($message, '❌') !== false) ? 'bg-red-100 text-red-700 border border-red-300' : 'bg-snooker-light/10 text-snooker-green border border-snooker-light'; ?> rounded-lg shadow-md font-medium transition duration-300 ease-in-out">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <!-- Expense Summary Card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-lg border-l-8 border-red-500 md:col-span-2">
                    <p class="text-sm text-gray-500 font-medium uppercase">Total Recorded Expenses (All Time)</p>
                    <p class="text-4xl font-extrabold text-red-700 mt-2">
                        PKR <?php echo number_format($total_expenses, 2); ?>
                    </p>
                </div>
                
                <!-- Add Expense Button -->
                <div class="flex items-center">
                    <button onclick="openAddExpenseModal()"
                            class="w-full bg-snooker-accent text-snooker-green py-3 rounded-xl font-bold text-lg shadow-md hover:bg-yellow-400 transition transform hover:scale-[1.01] flex items-center justify-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        + Add Expense
                    </button>
                </div>
            </div>


            <!-- Expenses List -->
            <div class="bg-white shadow-xl p-6 rounded-xl border border-gray-100">
                <h2 class="text-2xl font-bold text-snooker-green mb-5 border-b pb-3">
                    Expense History
                </h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Category
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Details
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Amount (PKR)
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($all_expanses)): ?>
                                <?php foreach ($all_expanses as $expense): ?>
                                    <tr class="hover:bg-gray-50 transition duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700">
                                            <?php echo date('d M', strtotime($expense['expanses_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-snooker-green">
                                            <?php echo htmlspecialchars($expense['category_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <?php echo htmlspecialchars($expense['details'] ?: '-'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-extrabold text-red-600">
                                            <?php echo number_format($expense['amount'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 italic">
                                        No expenses recorded yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        
        <!-- Add Expense Modal -->
        <div id="addExpenseModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden flex items-center justify-center p-4 z-50">
            <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-lg">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-xl font-bold text-snooker-green">Record New Expense</h3>
                    <button onclick="closeAddExpenseModal()" class="text-gray-500 hover:text-gray-800 text-2xl font-bold">&times;</button>
                </div>
                
                <form id="addExpenseForm" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_expense">

                    <div>
                        <label for="expanses_date" class="block text-gray-700 font-medium mb-1">Date</label>
                        <input type="date" name="expanses_date" id="expanses_date" required value="<?php echo date('Y-m-d'); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-snooker-accent transition">
                    </div>

                    <div>
                        <label for="category_id" class="block text-gray-700 font-medium mb-1">Category</label>
                        <select name="category_id" id="category_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-snooker-accent transition bg-white">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($all_categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="details" class="block text-gray-700 font-medium mb-1">Details (Optional)</label>
                        <textarea name="details" id="details" rows="2"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-snooker-accent transition"
                               placeholder="e.g., Monthly electricity bill, staff bonus, purchase of new cue sticks."></textarea>
                    </div>
                    
                    <div>
                        <label for="amount" class="block text-gray-700 font-medium mb-1">Amount (PKR)</label>
                        <input type="number" name="amount" id="amount" step="0.01" min="0.01" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-snooker-accent transition"
                               placeholder="e.g., 24000.00">
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit"
                                class="w-full py-3 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition shadow-lg transform hover:scale-[1.01]">
                            Save Expense
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        // --- Modal Control Functions ---
        function openAddExpenseModal() {
            document.getElementById('addExpenseModal').classList.remove('hidden');
        }

        function closeAddExpenseModal() {
            document.getElementById('addExpenseModal').classList.add('hidden');
            document.getElementById('addExpenseForm').reset();
        }

        // Close modal when clicking outside
        document.getElementById('addExpenseModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('addExpenseModal')) {
                closeAddExpenseModal();
            }
        });
    </script>
</body>
</html>