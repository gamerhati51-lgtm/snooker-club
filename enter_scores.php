<?php
session_start();
require_once 'db.php';

$match_id = $_GET['match_id'] ?? 0;

// Get match details
$match_query = "SELECT m.*, t.tournament_name, t.tournament_type, 
                       p1.player_name as player1_name, p2.player_name as player2_name,
                       st.table_name, st.id as table_id
                FROM tournament_matches m
                JOIN tournaments t ON m.tournament_id = t.tournament_id
                LEFT JOIN tournament_players p1 ON m.player1_id = p1.tournament_player_id
                LEFT JOIN tournament_players p2 ON m.player2_id = p2.tournament_player_id
                LEFT JOIN snooker_tables st ON m.table_id = st.id
                WHERE m.match_id = $match_id";
$match_result = mysqli_query($conn, $match_query);
$match = mysqli_fetch_assoc($match_result);

// Get available tables
$tables_query = "SELECT id, table_name, status FROM snooker_tables WHERE status = 'Free' OR id = " . ($match['table_id'] ?? 0);
$tables_result = mysqli_query($conn, $tables_query);

// Update score
if(isset($_POST['submit_score'])) {
    $player1_score = intval($_POST['player1_score']);
    $player2_score = intval($_POST['player2_score']);
    $table_id = $_POST['table_id'] ? intval($_POST['table_id']) : NULL;
    $match_date = mysqli_real_escape_string($conn, $_POST['match_date']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    
    // Determine winner
    $winner_id = NULL;
    if($player1_score > $player2_score) {
        $winner_id = $match['player1_id'];
    } elseif($player2_score > $player1_score) {
        $winner_id = $match['player2_id'];
    }
    
    $update_query = "UPDATE tournament_matches SET 
                     player1_score = $player1_score,
                     player2_score = $player2_score,
                     table_id = " . ($table_id ? $table_id : 'NULL') . ",
                     match_date = " . ($match_date ? "'$match_date'" : 'NULL') . ",
                     start_time = " . ($start_time ? "'$start_time'" : 'NULL') . ",
                     match_status = 'Completed',
                     winner_id = " . ($winner_id ? $winner_id : 'NULL') . ",
                     end_time = NOW()
                     WHERE match_id = $match_id";
    
    if(mysqli_query($conn, $update_query)) {
        // Update player status if knockout
        if($match['tournament_type'] == 'Knockout' && $winner_id) {
            $loser_id = ($winner_id == $match['player1_id']) ? $match['player2_id'] : $match['player1_id'];
            $update_loser = "UPDATE tournament_players SET player_status = 'Eliminated' 
                            WHERE tournament_player_id = $loser_id";
            mysqli_query($conn, $update_loser);
        }
        
        // Update table status if assigned
        if($table_id) {
            $update_table = "UPDATE snooker_tables SET status = 'Occupied' WHERE id = $table_id";
            mysqli_query($conn, $update_table);
        }
        
        header("Location: tournament_details.php?id={$match['tournament_id']}&score_updated=1");
        exit();
    } else {
        $error = "Error updating score: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Match Score</title>
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
        <?php if($match): ?>
        
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Enter Match Score</h1>
            <div class="text-gray-600 mt-2">
                <span class="font-medium">Tournament:</span> <?php echo $match['tournament_name']; ?>
                <span class="mx-2">•</span>
                <span class="font-medium">Round:</span> <?php echo $match['round_number']; ?>
            </div>
        </div>
        
        <div class="max-w-2xl mx-auto">
            <?php if(isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <!-- Match Info -->
                <div class="text-center mb-6 p-4 bg-gray-50 rounded">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">
                        <?php echo $match['player1_name'] ?: 'TBD'; ?> 
                        vs 
                        <?php echo $match['player2_name'] ?: 'TBD'; ?>
                    </h2>
                    <div class="text-gray-600">
                        Round <?php echo $match['round_number']; ?> • Match <?php echo $match['match_order']; ?>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <div class="space-y-6">
                        <!-- Match Details -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Match Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="match_date">
                                        Match Date
                                    </label>
                                    <input type="date" id="match_date" name="match_date" 
                                           value="<?php echo date('Y-m-d'); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="start_time">
                                        Start Time
                                    </label>
                                    <input type="time" id="start_time" name="start_time" 
                                           value="<?php echo date('H:i'); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="table_id">
                                        Table Number
                                    </label>
                                    <select id="table_id" name="table_id"
                                            class="w-full px-3 py-2 border border-gray-300 rounded">
                                        <option value="">Select Table</option>
                                        <?php while($table = mysqli_fetch_assoc($tables_result)): ?>
                                        <option value="<?php echo $table['id']; ?>" 
                                            <?php echo ($table['id'] == $match['table_id']) ? 'selected' : ''; ?>>
                                            <?php echo $table['table_name']; ?> 
                                            (<?php echo $table['status']; ?>)
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Scores -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Enter Scores</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Player 1 Score -->
                                <div class="text-center p-4 border border-gray-200 rounded">
                                    <div class="text-xl font-bold text-gray-800 mb-2">
                                        <?php echo $match['player1_name'] ?: 'Player 1'; ?>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="player1_score">
                                            Score
                                        </label>
                                        <input type="number" id="player1_score" name="player1_score" 
                                               min="0" max="100" value="<?php echo $match['player1_score']; ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded text-center text-xl font-bold">
                                    </div>
                                </div>
                                
                                <!-- Player 2 Score -->
                                <div class="text-center p-4 border border-gray-200 rounded">
                                    <div class="text-xl font-bold text-gray-800 mb-2">
                                        <?php echo $match['player2_name'] ?: 'Player 2'; ?>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="player2_score">
                                            Score
                                        </label>
                                        <input type="number" id="player2_score" name="player2_score" 
                                               min="0" max="100" value="<?php echo $match['player2_score']; ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded text-center text-xl font-bold">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Buttons -->
                        <div class="flex space-x-4">
                            <button type="submit" name="submit_score" 
                                    class="flex-1 bg-green-600 text-white py-3 px-4 rounded hover:bg-green-700 text-lg font-semibold">
                                Submit Score
                            </button>
                            
                            <a href="tournament_details.php?id=<?php echo $match['tournament_id']; ?>" 
                               class="flex-1 bg-gray-600 text-white py-3 px-4 rounded hover:bg-gray-700 text-lg font-semibold text-center">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Preview -->
            <div class="mt-6 bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Match Preview</h3>
                <div id="preview" class="text-center p-4 bg-gray-50 rounded">
                    <div class="text-2xl font-bold mb-2">
                        <span id="preview_player1"><?php echo $match['player1_name'] ?: 'Player 1'; ?></span>
                        <span class="mx-2">vs</span>
                        <span id="preview_player2"><?php echo $match['player2_name'] ?: 'Player 2'; ?></span>
                    </div>
                    <div class="text-3xl font-bold text-blue-600" id="preview_score">
                        0 - 0
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Match Not Found</h2>
                <a href="tournament_dashboard.php" class="text-blue-600 hover:text-blue-800">
                    ← Back to Tournament Dashboard
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Live preview
        document.getElementById('player1_score').addEventListener('input', updatePreview);
        document.getElementById('player2_score').addEventListener('input', updatePreview);
        
        function updatePreview() {
            const score1 = document.getElementById('player1_score').value || 0;
            const score2 = document.getElementById('player2_score').value || 0;
            
            document.getElementById('preview_score').textContent = `${score1} - ${score2}`;
        }
        
        // Set default time to now
        const now = new Date();
        const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                          now.getMinutes().toString().padStart(2, '0');
        document.getElementById('start_time').value = timeString;
    </script>
</body>
</html>