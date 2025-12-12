<?php
session_start();
require_once 'db.php';

$tournament_id = $_GET['tournament_id'] ?? 0;

// Get tournament info
$tournament_query = "SELECT * FROM tournaments WHERE tournament_id = $tournament_id";
$tournament_result = mysqli_query($conn, $tournament_query);
$tournament = mysqli_fetch_assoc($tournament_result);

// Get tournament players
$players_query = "SELECT * FROM tournament_players 
                  WHERE tournament_id = $tournament_id AND player_status != 'Eliminated'
                  ORDER BY RAND()"; // Randomize for bracket generation
$players_result = mysqli_query($conn, $players_query);
$players = [];
while($row = mysqli_fetch_assoc($players_result)) {
    $players[] = $row;
}
$player_count = count($players);

// Generate brackets
if(isset($_POST['generate_brackets'])) {
    // Clear existing matches
    $clear_query = "DELETE FROM tournament_matches WHERE tournament_id = $tournament_id";
    mysqli_query($conn, $clear_query);
    
    // Generate matches based on tournament type
    if($tournament['tournament_type'] == 'Knockout') {
        generateKnockoutBrackets($tournament_id, $players, $conn);
    } elseif($tournament['tournament_type'] == 'League') {
        generateLeagueMatches($tournament_id, $players, $conn);
    }
    
    header("Location: tournament_details.php?id=$tournament_id&generated=1");
    exit();
}

function generateKnockoutBrackets($tournament_id, $players, $conn) {
    $player_count = count($players);
    $rounds = ceil(log($player_count, 2));
    $total_slots = pow(2, $rounds);
    
    // Create first round matches
    $match_order = 1;
    for($i = 0; $i < $player_count; $i += 2) {
        $player1_id = $players[$i]['tournament_player_id'] ?? NULL;
        $player2_id = isset($players[$i + 1]) ? $players[$i + 1]['tournament_player_id'] : NULL;
        
        $insert_query = "INSERT INTO tournament_matches 
                        (tournament_id, round_number, match_order, player1_id, player2_id, match_status) 
                        VALUES ($tournament_id, 1, $match_order, ";
        $insert_query .= $player1_id ? $player1_id : 'NULL';
        $insert_query .= ", ";
        $insert_query .= $player2_id ? $player2_id : 'NULL';
        $insert_query .= ", 'Scheduled')";
        
        mysqli_query($conn, $insert_query);
        $match_order++;
    }
    
    // Create empty slots for byes
    for($i = $player_count; $i < $total_slots; $i += 2) {
        $insert_query = "INSERT INTO tournament_matches 
                        (tournament_id, round_number, match_order, match_status) 
                        VALUES ($tournament_id, 1, $match_order, 'Scheduled')";
        mysqli_query($conn, $insert_query);
        $match_order++;
    }
}

function generateLeagueMatches($tournament_id, $players, $conn) {
    $player_count = count($players);
    $match_order = 1;
    
    // Generate round-robin matches (each player plays every other player)
    for($i = 0; $i < $player_count; $i++) {
        for($j = $i + 1; $j < $player_count; $j++) {
            $player1_id = $players[$i]['tournament_player_id'];
            $player2_id = $players[$j]['tournament_player_id'];
            
            $insert_query = "INSERT INTO tournament_matches 
                            (tournament_id, round_number, match_order, player1_id, player2_id, match_status) 
                            VALUES ($tournament_id, 1, $match_order, $player1_id, $player2_id, 'Scheduled')";
            
            mysqli_query($conn, $insert_query);
            $match_order++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Brackets</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

  <!-- Sidebar -->
  <?php include 'layout/sidebar.php'; ?>

  <!-- Main Content Area -->
  <div class="min-h-screen lg:ml-64"> <!-- Only margin on large screens -->
    
    <!-- Header -->
    <?php include "layout/header.php"; ?>

    <!-- Main Content -->
    <main class="pt-16 p-6">
      
      <!-- Page Content -->
      <div id="content-area" class="bg-white p-6 rounded-lg shadow-md">
        <?php if($tournament): ?>
        
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Generate Brackets: <?php echo $tournament['tournament_name']; ?></h1>
            <div class="text-gray-600 mt-2">
                <span class="font-medium">Tournament Type:</span> <?php echo $tournament['tournament_type']; ?>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Tournament Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 p-4 rounded">
                    <div class="text-sm text-gray-500">Total Players</div>
                    <div class="text-2xl font-bold"><?php echo $player_count; ?></div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded">
                    <div class="text-sm text-gray-500">Tournament Type</div>
                    <div class="text-2xl font-bold"><?php echo $tournament['tournament_type']; ?></div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded">
                    <div class="text-sm text-gray-500">Status</div>
                    <div class="text-2xl font-bold"><?php echo $tournament['status']; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Players List -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Players in Tournament (<?php echo $player_count; ?>)</h2>
            
            <?php if($player_count > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <?php foreach($players as $player): ?>
                <div class="border border-gray-200 rounded p-3">
                    <div class="font-medium"><?php echo $player['player_name']; ?></div>
                    <div class="text-sm text-gray-600">Rank: <?php echo $player['player_rank'] ?: '-'; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-500">No players in tournament. Add players first.</p>
            <a href="add_players.php?tournament_id=<?php echo $tournament_id; ?>" 
               class="inline-block mt-2 text-blue-600 hover:text-blue-800">
                Add players now
            </a>
            <?php endif; ?>
        </div>
        
        <!-- Generate Brackets Form -->
        <?php if($player_count >= 2): ?>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Generate Tournament Brackets</h2>
            
            <div class="mb-4">
                <h3 class="font-medium text-gray-700 mb-2">Bracket Preview:</h3>
                <?php if($tournament['tournament_type'] == 'Knockout'): ?>
                <div class="text-gray-600">
                    <p>• Knockout tournament with <?php echo $player_count; ?> players</p>
                    <p>• <?php echo ceil(log($player_count, 2)); ?> rounds</p>
                    <p>• Single elimination</p>
                </div>
                <?php elseif($tournament['tournament_type'] == 'League'): ?>
                <div class="text-gray-600">
                    <p>• League tournament with <?php echo $player_count; ?> players</p>
                    <p>• Round-robin format</p>
                    <p>• Each player plays against every other player</p>
                    <p>• Total matches: <?php echo ($player_count * ($player_count - 1)) / 2; ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="confirm_generate" required class="mr-2">
                        <span>I confirm I want to generate brackets. This will clear any existing matches.</span>
                    </label>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" name="generate_brackets" 
                            class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                        Generate Brackets
                    </button>
                    
                    <a href="tournament_details.php?id=<?php echo $tournament_id; ?>" 
                       class="bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-700">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded p-4">
            <p class="text-yellow-800">Need at least 2 players to generate brackets.</p>
            <a href="add_players.php?tournament_id=<?php echo $tournament_id; ?>" 
               class="inline-block mt-2 text-blue-600 hover:text-blue-800">
                Add more players
            </a>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Tournament Not Found</h2>
            <a href="tournament_dashboard.php" class="text-blue-600 hover:text-blue-800">
                ← Back to Tournament Dashboard
            </a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>