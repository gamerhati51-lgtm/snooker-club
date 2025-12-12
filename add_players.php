<?php
session_start();
require_once 'db.php';

$tournament_id = $_GET['tournament_id'] ?? 0;

// Get tournament info
$tournament_query = "SELECT * FROM tournaments WHERE tournament_id = $tournament_id";
$tournament_result = mysqli_query($conn, $tournament_query);
$tournament = mysqli_fetch_assoc($tournament_result);

// Get existing players for this tournament
$existing_players_query = "SELECT user_id FROM tournament_players WHERE tournament_id = $tournament_id";
$existing_players_result = mysqli_query($conn, $existing_players_query);
$existing_user_ids = [];
while($row = mysqli_fetch_assoc($existing_players_result)) {
    $existing_user_ids[] = $row['user_id'];
}

// Get available users (excluding already added)
$available_users_query = "SELECT id, name, username, email FROM users WHERE status = 'Active'";
if(!empty($existing_user_ids)) {
    $available_users_query .= " AND id NOT IN (" . implode(',', $existing_user_ids) . ")";
}
$available_users_result = mysqli_query($conn, $available_users_query);

// Add player from form
if(isset($_POST['add_player'])) {
    $player_name = mysqli_real_escape_string($conn, $_POST['player_name']);
    $player_phone = mysqli_real_escape_string($conn, $_POST['player_phone']);
    $user_id = $_POST['user_id'] ? intval($_POST['user_id']) : NULL;
    
    $add_query = "INSERT INTO tournament_players (tournament_id, user_id, player_name, player_phone) 
                  VALUES ($tournament_id, " . ($user_id ? $user_id : 'NULL') . ", '$player_name', '$player_phone')";
    
    if(mysqli_query($conn, $add_query)) {
        $success = "Player added successfully!";
        header("Location: add_players.php?tournament_id=$tournament_id&success=1");
        exit();
    } else {
        $error = "Error adding player: " . mysqli_error($conn);
    }
}

// Add selected user as player
if(isset($_POST['add_selected_user'])) {
    $selected_user_id = intval($_POST['selected_user_id']);
    
    $user_query = "SELECT name FROM users WHERE id = $selected_user_id";
    $user_result = mysqli_query($conn, $user_query);
    $user = mysqli_fetch_assoc($user_result);
    
    $add_query = "INSERT INTO tournament_players (tournament_id, user_id, player_name) 
                  VALUES ($tournament_id, $selected_user_id, '{$user['name']}')";
    
    if(mysqli_query($conn, $add_query)) {
        header("Location: add_players.php?tournament_id=$tournament_id&success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Players to Tournament</title>
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
            <h1 class="text-3xl font-bold text-gray-800">Add Players to: <?php echo $tournament['tournament_name']; ?></h1>
            <div class="flex items-center mt-2 space-x-4 text-gray-600">
                <span>Type: <strong><?php echo $tournament['tournament_type']; ?></strong></span>
                <span>Max Players: <strong><?php echo $tournament['max_players']; ?></strong></span>
                <span>Current Players: <strong>
                    <?php 
                        $count_query = "SELECT COUNT(*) as count FROM tournament_players WHERE tournament_id = $tournament_id";
                        $count_result = mysqli_query($conn, $count_query);
                        $count = mysqli_fetch_assoc($count_result);
                        echo $count['count'];
                    ?>
                </strong></span>
            </div>
        </div>
        
        <?php if(isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            Player added successfully!
        </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Add New Player Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Add New Player</h2>
                
                <form method="POST" action="">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="player_name">
                                Player Name *
                            </label>
                            <input type="text" id="player_name" name="player_name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="player_phone">
                                Phone Number
                            </label>
                            <input type="tel" id="player_phone" name="player_phone"
                                   class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="user_id">
                                Link to User Account (Optional)
                            </label>
                            <select id="user_id" name="user_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select User</option>
                                <?php while($user = mysqli_fetch_assoc($available_users_result)): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo $user['name']; ?> (<?php echo $user['username']; ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div>
                            <button type="submit" name="add_player" 
                                    class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                                Add Player
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Available Users -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Available Users</h2>
                
                <?php if(mysqli_num_rows($available_users_result) > 0): ?>
                <div class="space-y-3">
                    <?php 
                    // Reset pointer
                    mysqli_data_seek($available_users_result, 0);
                    while($user = mysqli_fetch_assoc($available_users_result)): 
                    ?>
                    <div class="border border-gray-200 rounded p-3 flex justify-between items-center">
                        <div>
                            <div class="font-medium"><?php echo $user['name']; ?></div>
                            <div class="text-sm text-gray-600"><?php echo $user['email']; ?></div>
                        </div>
                        <form method="POST" action="" class="inline">
                            <input type="hidden" name="selected_user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="add_selected_user" 
                                    class="text-sm bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">
                                Add as Player
                            </button>
                        </form>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <p class="text-gray-500">No available users to add.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Back Button -->
        <div class="mt-6">
            <a href="tournament_details.php?id=<?php echo $tournament_id; ?>" 
               class="text-blue-600 hover:text-blue-800">
                ← Back to Tournament Details
            </a>
        </div>
        
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