<?php
// Set the content type header to application/json
header('Content-Type: application/json');
include 'db.php'; 

// --- 1. Get Total Number of Tables ---
$total_tables_query = "SELECT COUNT(id) AS total FROM snooker_tables";
$total_tables_result = $conn->query($total_tables_query);
$total_tables_data = $total_tables_result->fetch_assoc();
$total_tables = (int)($total_tables_data['total'] ?? 0);

// --- 2. Get Number of Active/Occupied Tables ---
$active_tables_query = "SELECT COUNT(id) AS active_count FROM snooker_tables WHERE status = 'Occupied'";
$active_tables_result = $conn->query($active_tables_query);
$active_tables_data = $active_tables_result->fetch_assoc();
$active_tables = (int)($active_tables_data['active_count'] ?? 0);

// --- 3. Calculate Availability ---
$free_tables = $total_tables - $active_tables;
$availability_percent = 0;

if ($total_tables > 0) {
    $availability_percent = round(($free_tables / $total_tables) * 100);
}

// --- 4. Get HTML for the entire table list (optional, but helpful) ---
ob_start(); // Start output buffering
// Insert the PHP code snippet that loops through snooker_tables and generates the <tr>...</tr> rows here.
// You would ideally move that table rendering logic into a separate file like 'table_list_template.php' 
// and include it here. For simplicity, assume you run the SQL and loop here:
// Example:
/*
$table_list_query = "SELECT * FROM snooker_tables ORDER BY id ASC";
$table_list_result = $conn->query($table_list_query);
while ($row = $table_list_result->fetch_assoc()) {
    // ... render <tr>... logic, similar to your dashboard loop ...
}
*/
$table_html = ob_get_clean(); // Capture the rendered HTML

// --- 5. Output JSON data ---
echo json_encode([
    'active_tables' => $active_tables,
    'total_tables' => $total_tables,
    'free_tables' => $free_tables,
    'availability_percent' => $availability_percent,
    'table_list_html' => $table_html // Send the updated table rows as well
]);
?>