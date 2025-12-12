<?php
session_start();
require_once 'db.php';

// Filter variables
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT t.*, COUNT(tp.tournament_player_id) as player_count, u.name as creator_name 
          FROM tournaments t 
          LEFT JOIN tournament_players tp ON t.tournament_id = tp.tournament_id 
          LEFT JOIN users u ON t.created_by = u.id 
          WHERE 1=1";

if($status_filter != 'all') {
    $query .= " AND t.status = '$status_filter'";
}

if($type_filter != 'all') {
    $query .= " AND t.tournament_type = '$type_filter'";
}

if(!empty($search)) {
    $query .= " AND (t.tournament_name LIKE '%$search%' OR u.name LIKE '%$search%')";
}

$query .= " GROUP BY t.tournament_id ORDER BY t.created_at DESC";

$result = mysqli_query($conn, $query);
$total_tournaments = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament List</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-blue-100 font-sans">

  <!-- Sidebar -->
  <?php include 'layout/sidebar.php'; ?>

  <!-- Main Content Area -->
  <div class="min-h-screen lg:ml-64"> <!-- Only margin on large screens -->
    
    <!-- Header -->
    <?php include "layout/header.php"; ?>

    <!-- Main Content -->
    <main class="pt-16 p-6">
      
      <!-- Page Content -->
      <div id="content-area" class="space-y-2 bg-blue-100 p-6 rounded-lg">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Tournament List</h1>
            <a href="create_tournament.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                + Create Tournament
            </a>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <form method="GET" action="" class="space-y-4 md:space-y-0 md:grid md:grid-cols-4 md:gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="search">
                        Search
                    </label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search by name or creator..."
                           class="w-full px-3 py-2 border border-gray-300 rounded">
                </div>
                
                <!-- Status Filter -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="status">
                        Status
                    </label>
                    <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="Upcoming" <?php echo $status_filter == 'Upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <!-- Type Filter -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="type">
                        Type
                    </label>
                    <select id="type" name="type" class="w-full px-3 py-2 border border-gray-300 rounded">
                        <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="Knockout" <?php echo $type_filter == 'Knockout' ? 'selected' : ''; ?>>Knockout</option>
                        <option value="League" <?php echo $type_filter == 'League' ? 'selected' : ''; ?>>League</option>
                        <option value="Round Robin" <?php echo $type_filter == 'Round Robin' ? 'selected' : ''; ?>>Round Robin</option>
                    </select>
                </div>
                
                <!-- Filter Buttons -->
                <div class="flex items-end space-x-2">
                    <button type="submit" 
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Filter
                    </button>
                    <a href="tournament_list.php" 
                       class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                        Clear
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Tournament List -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <?php if($total_tournaments > 0): ?>
            
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Showing <?php echo $total_tournaments; ?> tournament(s)</span>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Players</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Creator</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while($tournament = mysqli_fetch_assoc($result)): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900"><?php echo $tournament['tournament_name']; ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-gray-700"><?php echo $tournament['tournament_type']; ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-gray-700"><?php echo $tournament['player_count']; ?>/<?php echo $tournament['max_players']; ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600">
                                    <div>Start: <?php echo date('M d', strtotime($tournament['start_date'])); ?></div>
                                    <?php if($tournament['end_date']): ?>
                                    <div>End: <?php echo date('M d', strtotime($tournament['end_date'])); ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php 
                                        switch($tournament['status']) {
                                            case 'Active': echo 'bg-green-100 text-green-800'; break;
                                            case 'Upcoming': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'Completed': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'Cancelled': echo 'bg-red-100 text-red-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                    ?>">
                                    <?php echo $tournament['status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-gray-700"><?php echo $tournament['creator_name']; ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <a href="tournament_details.php?id=<?php echo $tournament['tournament_id']; ?>" 
                                       class="text-blue-600 hover:text-blue-800">
                                        View
                                    </a>
                                    <?php if($tournament['status'] == 'Upcoming'): ?>
                                    <a href="add_players.php?tournament_id=<?php echo $tournament['tournament_id']; ?>" 
                                       class="text-green-600 hover:text-green-800">
                                        Add Players
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <?php else: ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">No tournaments found</h3>
                <p class="mt-1 text-gray-500">
                    <?php echo (!empty($search) || $status_filter != 'all' || $type_filter != 'all') 
                        ? 'Try changing your filters.' 
                        : 'Get started by creating a new tournament.'; ?>
                </p>
                <div class="mt-6">
                    <a href="create_tournament.php" class="text-blue-600 hover:text-blue-800">
                        + Create Tournament
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>