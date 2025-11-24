<?php
session_start();
include 'db.php'; 

// --- 1. Get Session ID and Validate Access ---
$session_id = $_GET['session_id'] ?? null;

if (!$session_id || !is_numeric($session_id)) {
    die("Error: Invalid session ID provided.");
}

$message = "";

// --- 2. Handle Form Submission (Adding Item) ---
if (isset($_POST['add_item'])) {
    $item_id = $_POST['item_id'];
    $quantity = (int)$_POST['quantity'];
    
    // Fetch item details (name and price) from inventory
    $stmt_fetch = $conn->prepare("SELECT item_name, sale_price FROM inventory WHERE item_id = ?");
    $stmt_fetch->bind_param("i", $item_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    $item_data = $result_fetch->fetch_assoc();
    $stmt_fetch->close();
    
    if ($item_data && $quantity > 0) {
        $item_name = $item_data['item_name'];
        $price_per_unit = $item_data['sale_price'];
        
        // Insert item into session_items table
        $stmt_insert = $conn->prepare("
            INSERT INTO session_items (session_id, item_name, quantity, price_per_unit) 
            VALUES (?, ?, ?, ?)
        ");
        // 'isid' stands for integer, string, integer, decimal (treated as double/float in bind_param)
        $stmt_insert->bind_param("isid", $session_id, $item_name, $quantity, $price_per_unit);
        
        if ($stmt_insert->execute()) {
            // Success: Redirect back to the active session view
            // You need to find the table_id to redirect properly.
            // A quick lookup is needed:
            $stmt_table = $conn->prepare("SELECT id FROM snooker_sessions WHERE session_id = ?");
            $stmt_table->bind_param("i", $session_id);
            $stmt_table->execute();
            $table_id_data = $stmt_table->get_result()->fetch_assoc();
            $table_id = $table_id_data['id'] ?? 0;
            $stmt_table->close();

            header("Location: table_view.php?table_id=" . $table_id . "&session_id=" . $session_id);
            exit;
        } else {
            $message = "❌ Error adding item: " . $stmt_insert->error;
        }
    } else {
        $message = "⚠️ Invalid item or quantity. Please select an item and a quantity greater than zero.";
    }
}

// --- 3. Fetch Inventory List for the Form Dropdown ---
$sql_inventory = "SELECT item_id, item_name, sale_price FROM inventory ORDER BY item_name ASC";
$inventory_result = $conn->query($sql_inventory);

// --- 4. Fetch Table Name for Display ---
$stmt_table_name = $conn->prepare("
    SELECT st.table_name FROM snooker_tables st
    JOIN snooker_sessions s ON st.id = s.id
    WHERE s.session_id = ?
");
$stmt_table_name->bind_param("i", $session_id);
$stmt_table_name->execute();
$table_name_data = $stmt_table_name->get_result()->fetch_assoc();
$table_name = $table_name_data['table_name'] ?? 'Session';
$stmt_table_name->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Item to <?php echo htmlspecialchars($table_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen"> 
    
    <div class="flex">

        <?php include 'layout/sidebar.php'; ?>

        <div class="flex-1 p-8">
    <div class="max-w-md mx-auto bg-white shadow-lg rounded-lg p-6">
        <h1 class="text-3xl font-bold mb-4 text-gray-700 border-b pb-2">
            🛒 Add Item to <?php echo htmlspecialchars($table_name); ?>
        </h1>
        <p class="mb-4 text-sm text-gray-500">
            Session ID: <?php echo $session_id; ?>
        </p>

        <?php if (!empty($message)) { ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 border border-red-300 rounded">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php } ?>

        <form method="post" class="space-y-4">

            <div>
                <label for="item_id" class="block text-gray-700 font-medium mb-1">Select Item</label>
                <select name="item_id" id="item_id" required
                        class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Select Snack or Drink --</option>
                    <?php if ($inventory_result->num_rows > 0): ?>
                        <?php while ($item = $inventory_result->fetch_assoc()): ?>
                            <option value="<?php echo $item['item_id']; ?>">
                                <?php echo htmlspecialchars($item['item_name']); ?> (<?php echo number_format($item['sale_price'], 2); ?> PKR)
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="" disabled>No Inventory Items Found</option>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label for="quantity" class="block text-gray-700 font-medium mb-1">Quantity</label>
                <input type="number" name="quantity" id="quantity" value="1" min="1" required
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex justify-between pt-4">
                <button type="submit" name="add_item"
                        class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 transition">
                    Add Item to Bill
                </button>

                <?php 
                    // To ensure the back button works if the form wasn't submitted yet
                    $stmt_back = $conn->prepare("SELECT id FROM snooker_sessions WHERE session_id = ?");
                    $stmt_back->bind_param("i", $session_id);
                    $stmt_back->execute();
                    $back_data = $stmt_back->get_result()->fetch_assoc();
                    $back_table_id = $back_data['id'] ?? 0;
                ?>
                <a href="table_view.php?table_id=<?php echo $back_table_id; ?>&session_id=<?php echo $session_id; ?>"
                   class="bg-gray-300 px-5 py-2 rounded hover:bg-gray-400 transition">
                    Cancel & Back
                </a>
            </div>

        </form>
        
        <?php if ($inventory_result->num_rows == 0): ?>
            <p class="mt-4 text-sm text-red-500">
                🚨 **Action Required:** Please add items to your `inventory` table before attempting to add items to a session.
            </p>
        <?php endif; ?>

    </div>
</body>
</html>