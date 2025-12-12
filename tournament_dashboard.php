<?php
session_start();
require_once 'db.php';

// Get active tournaments
$active_query = "SELECT t.*, COUNT(tp.tournament_player_id) as player_count 
                 FROM tournaments t 
                 LEFT JOIN tournament_players tp ON t.tournament_id = tp.tournament_id 
                 WHERE t.status IN ('Upcoming', 'Active') 
                 GROUP BY t.tournament_id 
                 ORDER BY t.start_date ASC";
$active_result = mysqli_query($conn, $active_query);

// Get completed tournaments
$completed_query = "SELECT t.*, COUNT(tp.tournament_player_id) as player_count 
                    FROM tournaments t 
                    LEFT JOIN tournament_players tp ON t.tournament_id = tp.tournament_id 
                    WHERE t.status = 'Completed' 
                    GROUP BY t.tournament_id 
                    ORDER BY t.end_date DESC 
                    LIMIT 5";
$completed_result = mysqli_query($conn, $completed_query);

// Get tournament statistics
$stats_query = "SELECT 
    COUNT(CASE WHEN status = 'Active' THEN 1 END) as active_count,
    COUNT(CASE WHEN status = 'Upcoming' THEN 1 END) as upcoming_count,
    COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_count,
    COUNT(*) as total_tournaments,
    SUM(CASE WHEN status IN ('Active', 'Completed') THEN 1 ELSE 0 END) as total_players
    FROM tournaments t
    LEFT JOIN tournament_players tp ON t.tournament_id = tp.tournament_id";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get upcoming tournaments (next 7 days)
$upcoming_query = "SELECT * FROM tournaments 
                   WHERE status = 'Upcoming' 
                   AND start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                   ORDER BY start_date ASC 
                   LIMIT 3";
$upcoming_result = mysqli_query($conn, $upcoming_query);

// Get recent activities
$activities_query = "SELECT 
    'match' as type, 
    CONCAT(p1.player_name, ' vs ', p2.player_name) as description,
    m.created_at,
    'Completed' as status
    FROM tournament_matches m
    LEFT JOIN tournament_players p1 ON m.player1_id = p1.tournament_player_id
    LEFT JOIN tournament_players p2 ON m.player2_id = p2.tournament_player_id
    WHERE m.match_status = 'Completed'
    UNION ALL
    SELECT 
    'tournament' as type,
    CONCAT('New tournament: ', tournament_name) as description,
    created_at,
    status
    FROM tournaments
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY created_at DESC 
    LIMIT 5";
$activities_result = mysqli_query($conn, $activities_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
      <div id="content-area" class="space-y-2 bg-blue-100 p-6 rounded-lg">  <div class="flex flex-col md:flex-row justify-between items-center">
          <div>
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Tournament Dashboard</h1>
            <p class="text-blue-100">Manage all your tournaments in one place</p>
          </div>
          <div class="mt-4 md:mt-0">
            <a href="create_tournament.php" 
               class="inline-flex items-center bg-white text-blue-600 font-bold px-6 py-3 rounded-xl hover:bg-gray-100 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
              <i class="fas fa-plus mr-2"></i>
              Create Tournament
            </a>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Tournaments -->
        <div class="bg-white rounded-2xl shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
          <div class="flex items-center">
            <div class="p-3 rounded-xl bg-blue-100 text-blue-600 mr-4">
              <i class="fas fa-trophy text-xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Total Tournaments</p>
              <h3 class="text-3xl font-bold text-gray-800"><?php echo $stats['total_tournaments'] ?? 0; ?></h3>
            </div>
          </div>
        </div>

        <!-- Active Tournaments -->
        <div class="bg-white rounded-2xl shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
          <div class="flex items-center">
            <div class="p-3 rounded-xl bg-green-100 text-green-600 mr-4">
              <i class="fas fa-play-circle text-xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Active Tournaments</p>
              <h3 class="text-3xl font-bold text-gray-800"><?php echo $stats['active_count'] ?? 0; ?></h3>
            </div>
          </div>
        </div>

        <!-- Upcoming Tournaments -->
        <div class="bg-white rounded-2xl shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
          <div class="flex items-center">
            <div class="p-3 rounded-xl bg-yellow-100 text-yellow-600 mr-4">
              <i class="fas fa-calendar-alt text-xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Upcoming</p>
              <h3 class="text-3xl font-bold text-gray-800"><?php echo $stats['upcoming_count'] ?? 0; ?></h3>
            </div>
          </div>
        </div>

        <!-- Total Players -->
        <div class="bg-white rounded-2xl shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
          <div class="flex items-center">
            <div class="p-3 rounded-xl bg-purple-100 text-purple-600 mr-4">
              <i class="fas fa-users text-xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Total Players</p>
              <h3 class="text-3xl font-bold text-gray-800"><?php echo $stats['total_players'] ?? 0; ?></h3>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Active Tournaments -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-2xl shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b">
              <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800">
                  <i class="fas fa-running mr-2 text-blue-600"></i>
                  Active Tournaments
                </h2>
                <a href="tournament_list.php?status=Active" class="text-blue-600 text-sm font-medium hover:text-blue-800">
                  View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
              </div>
            </div>
            
            <?php if(mysqli_num_rows($active_result) > 0): ?>
            <div class="divide-y divide-gray-100">
              <?php while($tournament = mysqli_fetch_assoc($active_result)): 
                $progress = min(100, ($tournament['player_count'] / $tournament['max_players']) * 100);
                $days_until_start = ceil((strtotime($tournament['start_date']) - time()) / (60 * 60 * 24));
              ?>
              <div class="p-6 hover:bg-gray-50 transition-colors duration-200">
                <div class="flex justify-between items-start mb-3">
                  <div>
                    <h3 class="font-bold text-lg text-gray-800"><?php echo $tournament['tournament_name']; ?></h3>
                    <div class="flex items-center mt-1 space-x-3 text-sm text-gray-600">
                      <span class="flex items-center">
                        <i class="fas fa-chess-king mr-1"></i>
                        <?php echo $tournament['tournament_type']; ?>
                      </span>
                      <span class="flex items-center">
                        <i class="fas fa-users mr-1"></i>
                        <?php echo $tournament['player_count']; ?>/<?php echo $tournament['max_players']; ?>
                      </span>
                      <span class="flex items-center">
                        <i class="fas fa-calendar mr-1"></i>
                        <?php echo date('M d', strtotime($tournament['start_date'])); ?>
                      </span>
                    </div>
                  </div>
                  <span class="px-3 py-1 text-xs rounded-full font-semibold 
                    <?php echo $tournament['status'] == 'Active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                    <?php echo $tournament['status']; ?>
                  </span>
                </div>
                
                <!-- Progress Bar -->
                <div class="mb-4">
                  <div class="flex justify-between text-sm text-gray-600 mb-1">
                    <span>Registration Progress</span>
                    <span><?php echo $progress; ?>%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" 
                         style="width: <?php echo $progress; ?>%"></div>
                  </div>
                </div>
                
                <div class="flex justify-between items-center">
                  <div class="text-sm text-gray-600">
                    <?php if($tournament['status'] == 'Upcoming'): ?>
                    <span class="flex items-center">
                      <i class="fas fa-clock mr-1"></i>
                      Starts in <?php echo $days_until_start; ?> day<?php echo $days_until_start != 1 ? 's' : ''; ?>
                    </span>
                    <?php else: ?>
                    <span class="flex items-center">
                      <i class="fas fa-fire mr-1 text-orange-500"></i>
                      Live Now
                    </span>
                    <?php endif; ?>
                  </div>
                  <div class="flex space-x-2">
                    <a href="tournament_details.php?id=<?php echo $tournament['tournament_id']; ?>" 
                       class="px-4 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors duration-200 font-medium">
                      View Details
                    </a>
                    <a href="add_players.php?tournament_id=<?php echo $tournament['tournament_id']; ?>" 
                       class="px-4 py-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-colors duration-200">
                      <i class="fas fa-user-plus"></i>
                    </a>
                  </div>
                </div>
              </div>
              <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="p-8 text-center">
              <div class="text-gray-400 mb-4">
                <i class="fas fa-trophy text-5xl"></i>
              </div>
              <h3 class="text-lg font-medium text-gray-700 mb-2">No Active Tournaments</h3>
              <p class="text-gray-500 mb-4">Create your first tournament to get started</p>
              <a href="create_tournament.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-plus mr-2"></i>
                Create Tournament
              </a>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6">
          <!-- Upcoming Tournaments -->
          <div class="bg-white rounded-2xl shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b">
              <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-calendar-check mr-2 text-yellow-600"></i>
                Upcoming Tournaments
              </h2>
            </div>
            
            <?php if(mysqli_num_rows($upcoming_result) > 0): ?>
            <div class="divide-y divide-gray-100">
              <?php while($upcoming = mysqli_fetch_assoc($upcoming_result)): 
                $days_until = ceil((strtotime($upcoming['start_date']) - time()) / (60 * 60 * 24));
              ?>
              <div class="p-4 hover:bg-gray-50 transition-colors duration-200">
                <div class="flex justify-between items-start mb-2">
                  <h4 class="font-semibold text-gray-800 truncate"><?php echo $upcoming['tournament_name']; ?></h4>
                  <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                    <?php echo $days_until; ?> day<?php echo $days_until != 1 ? 's' : ''; ?>
                  </span>
                </div>
                <div class="text-sm text-gray-600 flex items-center">
                  <i class="fas fa-calendar-alt mr-2 text-gray-400"></i>
                  <?php echo date('D, M d', strtotime($upcoming['start_date'])); ?>
                </div>
                <div class="mt-2 text-sm text-gray-600 flex items-center">
                  <i class="fas fa-users mr-2 text-gray-400"></i>
                  <?php echo $upcoming['max_players']; ?> players max
                </div>
              </div>
              <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="p-6 text-center">
              <div class="text-gray-400 mb-2">
                <i class="fas fa-calendar-times text-3xl"></i>
              </div>
              <p class="text-gray-500">No upcoming tournaments</p>
            </div>
            <?php endif; ?>
          </div>

          <!-- Quick Actions -->
          <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 gap-3">
              <a href="create_tournament.php" 
                 class="bg-white p-4 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 text-center group">
                <div class="text-blue-600 mb-2">
                  <i class="fas fa-plus-circle text-2xl group-hover:scale-110 transition-transform"></i>
                </div>
                <span class="text-sm font-medium text-gray-700">New Tournament</span>
              </a>
              <a href="tournament_list.php" 
                 class="bg-white p-4 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 text-center group">
                <div class="text-green-600 mb-2">
                  <i class="fas fa-list text-2xl group-hover:scale-110 transition-transform"></i>
                </div>
                <span class="text-sm font-medium text-gray-700">View All</span>
              </a>
              <a href="add_players.php" 
                 class="bg-white p-4 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 text-center group">
                <div class="text-purple-600 mb-2">
                  <i class="fas fa-user-plus text-2xl group-hover:scale-110 transition-transform"></i>
                </div>
                <span class="text-sm font-medium text-gray-700">Add Players</span>
              </a>
              <a href="generate_brackets.php" 
                 class="bg-white p-4 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 text-center group">
                <div class="text-orange-600 mb-2">
                  <i class="fas fa-sitemap text-2xl group-hover:scale-110 transition-transform"></i>
                </div>
                <span class="text-sm font-medium text-gray-700">Generate Brackets</span>
              </a>
            </div>
          </div>

          <!-- Recent Activities -->
          <div class="bg-white rounded-2xl shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b">
              <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-history mr-2 text-gray-600"></i>
                Recent Activities
              </h2>
            </div>
            
            <?php if(mysqli_num_rows($activities_result) > 0): ?>
            <div class="divide-y divide-gray-100">
              <?php while($activity = mysqli_fetch_assoc($activities_result)): 
                $time_ago = time_ago($activity['created_at']);
              ?>
              <div class="p-4 hover:bg-gray-50 transition-colors duration-200">
                <div class="flex items-start">
                  <div class="flex-shrink-0 mt-1">
                    <?php if($activity['type'] == 'match'): ?>
                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                      <i class="fas fa-table-tennis text-green-600 text-sm"></i>
                    </div>
                    <?php else: ?>
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                      <i class="fas fa-trophy text-blue-600 text-sm"></i>
                    </div>
                    <?php endif; ?>
                  </div>
                  <div class="ml-3 flex-1">
                    <p class="text-sm text-gray-800"><?php echo $activity['description']; ?></p>
                    <p class="text-xs text-gray-500 mt-1"><?php echo $time_ago; ?></p>
                  </div>
                </div>
              </div>
              <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="p-6 text-center">
              <div class="text-gray-400 mb-2">
                <i class="fas fa-bell-slash text-3xl"></i>
              </div>
              <p class="text-gray-500">No recent activities</p>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Completed Tournaments -->
      <div class="mt-6 bg-white rounded-2xl shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">
              <i class="fas fa-flag-checkered mr-2 text-green-600"></i>
              Recent Completed Tournaments
            </h2>
            <a href="tournament_list.php?status=Completed" class="text-blue-600 text-sm font-medium hover:text-blue-800">
              View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
          </div>
        </div>
        
        <?php if(mysqli_num_rows($completed_result) > 0): ?>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tournament</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Players</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Winner</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php 
              mysqli_data_seek($completed_result, 0);
              while($tournament = mysqli_fetch_assoc($completed_result)): 
                // Get winner for this tournament
                $winner_query = "SELECT p.player_name FROM tournament_players p
                               JOIN tournament_matches m ON p.tournament_player_id = m.winner_id
                               WHERE p.tournament_id = {$tournament['tournament_id']}
                               ORDER BY m.round_number DESC LIMIT 1";
                $winner_result = mysqli_query($conn, $winner_query);
                $winner = mysqli_fetch_assoc($winner_result);
                
                $duration = date_diff(date_create($tournament['start_date']), date_create($tournament['end_date']))->days;
              ?>
              <tr class="hover:bg-gray-50 transition-colors duration-200">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="font-medium text-gray-900"><?php echo $tournament['tournament_name']; ?></div>
                  <div class="text-sm text-gray-500">
                    <?php echo date('M d - ', strtotime($tournament['start_date'])) . date('M d, Y', strtotime($tournament['end_date'])); ?>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                    <?php echo $tournament['tournament_type']; ?>
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <i class="fas fa-users text-gray-400 mr-2"></i>
                    <span class="text-gray-700"><?php echo $tournament['player_count']; ?></span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                  <?php echo $duration; ?> day<?php echo $duration != 1 ? 's' : ''; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <?php if($winner): ?>
                  <div class="flex items-center">
                    <i class="fas fa-crown text-yellow-500 mr-2"></i>
                    <span class="font-medium"><?php echo $winner['player_name']; ?></span>
                  </div>
                  <?php else: ?>
                  <span class="text-gray-400">N/A</span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <a href="tournament_results.php?id=<?php echo $tournament['tournament_id']; ?>" 
                     class="inline-flex items-center px-3 py-1.5 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors duration-200">
                    <i class="fas fa-chart-bar mr-1"></i>
                    Results
                  </a>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="p-8 text-center">
          <div class="text-gray-400 mb-4">
            <i class="fas fa-flag text-5xl"></i>
          </div>
          <h3 class="text-lg font-medium text-gray-700 mb-2">No Completed Tournaments</h3>
          <p class="text-gray-500">Complete tournaments will appear here</p>
        </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
    
    <script>
        // Simple animation for statistics cards
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.bg-white.rounded-2xl');
            
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Progress bar animation
            const progressBars = document.querySelectorAll('.bg-blue-600.h-2');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.transition = 'width 1.5s ease-in-out';
                    bar.style.width = width;
                }, 500);
            });
        });
    </script>
</body>
</html>

<?php
// Helper function for time ago
function time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins != 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}
?>