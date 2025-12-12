<?php
session_start();
require_once 'db.php';

$tournament_id = $_GET['id'] ?? 0;

// Get tournament details
$tournament_query = "SELECT * FROM tournaments WHERE tournament_id = $tournament_id";
$tournament_result = mysqli_query($conn, $tournament_query);
$tournament = mysqli_fetch_assoc($tournament_result);

// Get winner
$winner_query = "SELECT p.* FROM tournament_players p
                 JOIN tournament_matches m ON p.tournament_player_id = m.winner_id
                 WHERE p.tournament_id = $tournament_id AND m.tournament_id = $tournament_id
                 ORDER BY m.round_number DESC LIMIT 1";
$winner_result = mysqli_query($conn, $winner_query);
$winner = mysqli_fetch_assoc($winner_result);

// Get final match
$final_match_query = "SELECT m.*, p1.player_name as player1_name, p2.player_name as player2_name
                      FROM tournament_matches m
                      LEFT JOIN tournament_players p1 ON m.player1_id = p1.tournament_player_id
                      LEFT JOIN tournament_players p2 ON m.player2_id = p2.tournament_player_id
                      WHERE m.tournament_id = $tournament_id 
                      ORDER BY m.round_number DESC, m.match_order DESC LIMIT 1";
$final_match_result = mysqli_query($conn, $final_match_query);
$final_match = mysqli_fetch_assoc($final_match_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Results</title>
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
        <?php if($tournament): ?>
        
        <div class="max-w-4xl mx-auto">
            <!-- Tournament Header -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-6 text-center">
                <h1 class="text-4xl font-bold text-gray-800 mb-2">Tournament Results</h1>
                <h2 class="text-2xl text-gray-600 mb-4"><?php echo $tournament['tournament_name']; ?></h2>
                
                <div class="flex justify-center items-center space-x-6 text-gray-600 mb-6">
                    <span>Type: <strong><?php echo $tournament['tournament_type']; ?></strong></span>
                    <span>Dates: 
                        <strong><?php echo date('M d', strtotime($tournament['start_date'])); ?></strong>
                        <?php if($tournament['end_date']): ?>
                        - <strong><?php echo date('M d, Y', strtotime($tournament['end_date'])); ?></strong>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            
            <!-- Winner Section -->
            <div class="bg-gradient-to-r from-yellow-100 to-yellow-50 rounded-lg shadow-md p-8 mb-6 text-center">
                <div class="mb-4">
                    <span class="text-5xl">🏆</span>
                </div>
                
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Tournament Winner</h3>
                
                <?php if($winner): ?>
                <div class="text-4xl font-bold text-yellow-600 mb-4"><?php echo $winner['player_name']; ?></div>
                
                <?php if($final_match): ?>
                <div class="text-lg text-gray-700 mb-2">
                    Defeated <?php echo $final_match['player1_name'] == $winner['player_name'] ? $final_match['player2_name'] : $final_match['player1_name']; ?>
                </div>
                <div class="text-2xl font-bold text-gray-800">
                    <?php echo $final_match['player1_score']; ?> - <?php echo $final_match['player2_score']; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="text-2xl font-bold text-gray-600">No winner determined</div>
                <?php endif; ?>
            </div>
            
            <!-- Final Match Details -->
            <?php if($final_match): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Final Match</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                    <div class="p-4">
                        <div class="text-xl font-bold text-gray-800 mb-2"><?php echo $final_match['player1_name']; ?></div>
                        <div class="text-3xl font-bold 
                            <?php echo $final_match['player1_score'] > $final_match['player2_score'] ? 'text-green-600' : 'text-gray-600'; ?>">
                            <?php echo $final_match['player1_score']; ?>
                        </div>
                    </div>
                    
                    <div class="p-4 flex items-center justify-center">
                        <span class="text-2xl font-bold text-gray-500">VS</span>
                    </div>
                    
                    <div class="p-4">
                        <div class="text-xl font-bold text-gray-800 mb-2"><?php echo $final_match['player2_name']; ?></div>
                        <div class="text-3xl font-bold 
                            <?php echo $final_match['player2_score'] > $final_match['player1_score'] ? 'text-green-600' : 'text-gray-600'; ?>">
                            <?php echo $final_match['player2_score']; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Tournament Statistics -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Tournament Statistics</h3>
                
                <?php
                // Get stats
                $players_count_query = "SELECT COUNT(*) as count FROM tournament_players WHERE tournament_id = $tournament_id";
                $matches_count_query = "SELECT COUNT(*) as count FROM tournament_matches WHERE tournament_id = $tournament_id";
                $completed_matches_query = "SELECT COUNT(*) as count FROM tournament_matches WHERE tournament_id = $tournament_id AND match_status = 'Completed'";
                
                $players_count = mysqli_fetch_assoc(mysqli_query($conn, $players_count_query))['count'];
                $matches_count = mysqli_fetch_assoc(mysqli_query($conn, $matches_count_query))['count'];
                $completed_matches = mysqli_fetch_assoc(mysqli_query($conn, $completed_matches_query))['count'];
                ?>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 p-4 rounded text-center">
                        <div class="text-2xl font-bold text-gray-800"><?php echo $players_count; ?></div>
                        <div class="text-gray-600">Total Players</div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded text-center">
                        <div class="text-2xl font-bold text-gray-800"><?php echo $matches_count; ?></div>
                        <div class="text-gray-600">Total Matches</div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded text-center">
                        <div class="text-2xl font-bold text-gray-800"><?php echo $completed_matches; ?></div>
                        <div class="text-gray-600">Completed Matches</div>
                    </div>
                </div>
            </div>
            
            <!-- Back Button -->
            <div class="text-center">
                <a href="tournament_list.php" 
                   class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                    ← Back to Tournament List
                </a>
            </div>
            
            <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Tournament Not Found</h2>
                <a href="tournament_list.php" class="text-blue-600 hover:text-blue-800">
                    ← Back to Tournament List
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>