<?php
session_start();
require_once 'db.php';

// Get tournament ID from URL
$tournament_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($tournament_id == 0) {
    die("Error: No tournament ID provided!");
}

// Get tournament details
$tournament_query = "SELECT t.*, u.name as creator_name 
                     FROM tournaments t 
                     LEFT JOIN users u ON t.created_by = u.id 
                     WHERE t.tournament_id = $tournament_id";
$tournament_result = mysqli_query($conn, $tournament_query);

if(!$tournament_result || mysqli_num_rows($tournament_result) == 0) {
    die("Error: Tournament not found or database error!");
}

$tournament = mysqli_fetch_assoc($tournament_result);

// Get tournament players
$players_query = "SELECT * FROM tournament_players 
                  WHERE tournament_id = $tournament_id 
                  ORDER BY player_status, registration_date";
$players_result = mysqli_query($conn, $players_query);

// Get tournament matches
$matches_query = "SELECT m.*, p1.player_name as player1_name, p2.player_name as player2_name, 
                         st.table_name, w.player_name as winner_name
                  FROM tournament_matches m
                  LEFT JOIN tournament_players p1 ON m.player1_id = p1.tournament_player_id
                  LEFT JOIN tournament_players p2 ON m.player2_id = p2.tournament_player_id
                  LEFT JOIN tournament_players w ON m.winner_id = w.tournament_player_id
                  LEFT JOIN snooker_tables st ON m.table_id = st.id
                  WHERE m.tournament_id = $tournament_id 
                  ORDER BY m.round_number, m.match_order";
$matches_result = mysqli_query($conn, $matches_query);

// Start tournament
if(isset($_POST['start_tournament'])) {
    $update_query = "UPDATE tournaments SET status = 'Active' WHERE tournament_id = $tournament_id";
    if(mysqli_query($conn, $update_query)) {
        header("Location: tournament_details.php?id=$tournament_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Details - <?php echo htmlspecialchars($tournament['tournament_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50 font-sans">

  <!-- Sidebar -->
  <?php include 'layout/sidebar.php'; ?>

  <!-- Main Content Area -->
  <div class="min-h-screen lg:ml-64">
    
    <!-- Header -->
    <?php include "layout/header.php"; ?>

    <!-- Main Content -->
    <main class="pt-16 p-4 md:p-6">
           <!-- Page Content -->
      <div id="content-area" class="space-y-2 bg-blue-100 p-6 rounded-lg"></div>
        <?php if(isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            Tournament created successfully!
        </div>
        <?php endif; ?>
        
        <!-- Tournament Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($tournament['tournament_name']); ?></h1>
                    <div class="flex items-center mt-2 space-x-4">
                        <span class="text-gray-600">Type: <strong><?php echo htmlspecialchars($tournament['tournament_type']); ?></strong></span>
                        <span class="text-gray-600">Max Players: <strong><?php echo htmlspecialchars($tournament['max_players']); ?></strong></span>
                        <span class="text-gray-600">Start Date: <strong><?php echo date('M d, Y', strtotime($tournament['start_date'])); ?></strong></span>
                        <span class="px-3 py-1 text-sm rounded-full 
                            <?php 
                                switch($tournament['status']) {
                                    case 'Active': echo 'bg-green-100 text-green-800'; break;
                                    case 'Upcoming': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'Completed': echo 'bg-blue-100 text-blue-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                            ?>">
                            <?php echo htmlspecialchars($tournament['status']); ?>
                        </span>
                    </div>
                    <?php if($tournament['creator_name']): ?>
                    <div class="mt-2 text-gray-600">
                        Created by: <strong><?php echo htmlspecialchars($tournament['creator_name']); ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex space-x-2">
                    <a href="add_players.php?tournament_id=<?php echo $tournament_id; ?>" 
                       class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Add Players
                    </a>
                    
                    <?php if($tournament['status'] == 'Upcoming'): ?>
                    <form method="POST" class="inline">
                        <button type="submit" name="start_tournament" 
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Start Tournament
                        </button>
                    </form>
                    <?php elseif($tournament['status'] == 'Active'): ?>
                    <a href="generate_brackets.php?tournament_id=<?php echo $tournament_id; ?>" 
                       class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                        Generate Matches
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Players List -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Players List</h2>
                
                <?php if(mysqli_num_rows($players_result) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Registered</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while($player = mysqli_fetch_assoc($players_result)): ?>
                            <tr>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($player['player_name']); ?></td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php 
                                            switch($player['player_status']) {
                                                case 'Active': echo 'bg-green-100 text-green-800'; break;
                                                case 'Winner': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'Eliminated': echo 'bg-red-100 text-red-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                        ?>">
                                        <?php echo htmlspecialchars($player['player_status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2"><?php echo date('M d', strtotime($player['registration_date'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-gray-500">No players added yet.</p>
                <a href="add_players.php?tournament_id=<?php echo $tournament_id; ?>" 
                   class="inline-block mt-2 text-blue-600 hover:text-blue-800">
                    Add players now
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Matches List -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Matches</h2>
                
                <?php if(mysqli_num_rows($matches_result) > 0): ?>
                <div class="space-y-3">
                    <?php while($match = mysqli_fetch_assoc($matches_result)): ?>
                    <div class="border border-gray-200 rounded p-3">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="text-sm text-gray-500">Round <?php echo $match['round_number']; ?></span>
                                <div class="font-medium">
                                    <?php echo $match['player1_name'] ? htmlspecialchars($match['player1_name']) : 'TBD'; ?> 
                                    vs 
                                    <?php echo $match['player2_name'] ? htmlspecialchars($match['player2_name']) : 'TBD'; ?>
                                </div>
                                <?php if($match['table_name']): ?>
                                <span class="text-sm text-gray-600">Table: <?php echo htmlspecialchars($match['table_name']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="text-right">
                                <div class="text-lg font-bold">
                                    <?php echo $match['player1_score']; ?> - <?php echo $match['player2_score']; ?>
                                </div>
                                <span class="text-sm px-2 py-1 rounded 
                                    <?php 
                                        switch($match['match_status']) {
                                            case 'Completed': echo 'bg-green-100 text-green-800'; break;
                                            case 'In Progress': echo 'bg-yellow-100 text-yellow-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                    ?>">
                                    <?php echo htmlspecialchars($match['match_status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if($match['match_status'] == 'Scheduled'): ?>
                        <div class="mt-2">
                            <a href="enter_scores.php?match_id=<?php echo $match['match_id']; ?>" 
                               class="text-blue-600 text-sm hover:text-blue-800">
                                Enter Score
                            </a>
                        </div>
                        <?php elseif($match['winner_name']): ?>
                        <div class="mt-2 text-sm">
                            Winner: <strong><?php echo htmlspecialchars($match['winner_name']); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <p class="text-gray-500">No matches scheduled yet.</p>
                <?php if($tournament['status'] == 'Active'): ?>
                <a href="generate_brackets.php?tournament_id=<?php echo $tournament_id; ?>" 
                   class="inline-block mt-2 text-blue-600 hover:text-blue-800">
                    Generate matches
                </a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Back Button -->
        <div class="mt-6">
            <a href="tournament_dashboard.php" 
               class="text-blue-600 hover:text-blue-800">
                ← Back to Tournament Dashboard
            </a>
        </div>
    </div>
</body>
</html>