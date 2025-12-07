<?php
session_start();
include 'db.php';
if(!isset($_SESSION['admin_name'])) header("Location:index.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Snooker Tables</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 font-sans">

<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/header.php'; ?>

<main class="flex-1 ml-0 lg:ml-64 pt-20 p-8">
<h1 class="text-3xl font-bold mb-6 text-gray-700 text-center">🎱 Snooker Tables</h1>

<div class="overflow-x-auto bg-white rounded shadow">
    <div class="flex justify-end mb-4">
   <a href="admin.php"><button onclick="openAddTableModal()" 
        class="flex items-center space-x-2  me-9 mt-6 bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg shadow transition">
             <svg class="w-4 h-4 inline mr-1 -mt-0.5" fill="none"
             stroke="currentColor" viewBox="0 0 24 24" 
             xmlns="http://www.w3.org/2000/svg">
             <path stroke-linecap="round" stroke-linejoin="round" 

            stroke-width="2" 
            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
        </path></svg>
      
        <span>DASHBOARD</span>
    </button></a>
</div>
<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Table Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate/Hour</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Century Rate</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
    <?php
    $result = $conn->query("SELECT * FROM snooker_tables ORDER BY id DESC");
    while($row = $result->fetch_assoc()):
    ?>
        <tr id="table-row-<?php echo $row['id']; ?>">
            <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($row['table_name']); ?></td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo $row['rate_per_hour']; ?> PKR</td>
            <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo $row['century_rate']; ?> PKR/min</td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
                <button onclick="showEditRow(<?php echo $row['id']; ?>)"
                        class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">Edit</button>
            </td>
        </tr>
        <!-- Inline Edit Row -->
        <tr id="edit-row-<?php echo $row['id']; ?>" class="hidden bg-gray-50">
            <td class="px-6 py-4" colspan="4">
                <form class="flex flex-wrap items-center space-x-4" onsubmit="updateTable(event, <?php echo $row['id']; ?>)">
                    <input type="text" id="table_name_<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($row['table_name']); ?>"
                           class="border px-2 py-1 rounded flex-1" required>
                    <input type="number" id="rate_hour_<?php echo $row['id']; ?>" value="<?php echo $row['rate_per_hour']; ?>" step="0.01"
                           class="border px-2 py-1 rounded w-28" required>
                    <input type="number" id="century_rate_<?php echo $row['id']; ?>" value="<?php echo $row['century_rate']; ?>" step="0.01"
                           class="border px-2 py-1 rounded w-28" required>
                    <button type="submit" class="bg-green-600 text-white px-4 py-1 rounded hover:bg-green-700 transition">Save</button>
                    <button type="button" onclick="hideEditRow(<?php echo $row['id']; ?>)"
                            class="bg-gray-400 text-white px-4 py-1 rounded hover:bg-gray-500 transition">Cancel</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>

<script>
function showEditRow(id){
    document.getElementById('edit-row-' + id).classList.remove('hidden');
}
function hideEditRow(id){
    document.getElementById('edit-row-' + id).classList.add('hidden');
}
function updateTable(event, id){
    event.preventDefault();
    let table_name = document.getElementById('table_name_' + id).value;
    let rate_hour = document.getElementById('rate_hour_' + id).value;
    let century_rate = document.getElementById('century_rate_' + id).value;

    $.ajax({
        url: 'ajax_update_table.php',
        type: 'POST',
        data: {id:id, table_name:table_name, rate_hour:rate_hour, century_rate:century_rate},
        dataType: 'json',
        success: function(response){
            if(response.status === 'success'){
                alert(response.message);
                location.reload();
            } else {
                alert(response.message);
            }
        }
    });
}

function deleteTable(id){
    if(!confirm("Are you sure you want to delete this table?")) return;

    $.ajax({
        url: 'ajax_update_table.php',
        type: 'POST',
        data: {delete_id:id},
        dataType: 'json',
        success: function(response){
            if(response.status === 'success'){
                alert(response.message);
                $('#table-row-' + id).remove();
            } else {
                alert(response.message);
            }
        }
    });
}

        
</script>

</main>
</body>
</html>
