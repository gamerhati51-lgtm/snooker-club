<?php
session_start();
require_once 'db.php';

// Debug: Check what users exist in the database
$users_query = "SELECT id, name FROM users";
$users_result = mysqli_query($conn, $users_query);
$users = [];
while($user = mysqli_fetch_assoc($users_result)) {
    $users[] = $user;
}

// Get the first available user ID (in case session user doesn't exist)
$first_user_id = !empty($users) ? $users[0]['id'] : 1;

// Use session user ID if exists, otherwise use first available user
$created_by = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0 ? $_SESSION['user_id'] : $first_user_id;

// Check if the user exists in database
$user_exists = false;
foreach($users as $user) {
    if ($user['id'] == $created_by) {
        $user_exists = true;
        break;
    }
}

// If user doesn't exist, use the first available user
if (!$user_exists && !empty($users)) {
    $created_by = $users[0]['id'];
} elseif (!$user_exists) {
    // No users exist - this is the problem!
    // Let's temporarily remove the foreign key constraint or create a dummy user
    die("Error: No users exist in the database. Please add at least one user first.");
}

if(isset($_POST['create_tournament'])) {
    $tournament_name = mysqli_real_escape_string($conn, $_POST['tournament_name']);
    $tournament_type = mysqli_real_escape_string($conn, $_POST['tournament_type']);
    $max_players = intval($_POST['max_players']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);

    // Handle NULL for end_date if empty
    $end_date_sql = (!empty($end_date)) ? "'$end_date'" : 'NULL';

    $query = "INSERT INTO tournaments (tournament_name, tournament_type, max_players, start_date, end_date, created_by, status) 
              VALUES ('$tournament_name', '$tournament_type', $max_players, '$start_date', $end_date_sql, $created_by, 'Upcoming')";
    
    if(mysqli_query($conn, $query)) {
        $tournament_id = mysqli_insert_id($conn);
        header("Location: tournament_details.php?id=$tournament_id&success=1");
        exit();
    } else {
        $error = "Error creating tournament: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Tournament</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .input-field {
            transition: all 0.3s ease;
        }
        .input-field:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .preview-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
    </style>
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
      <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold text-gray-800">Create Tournament</h1>
              <p class="text-gray-600 mt-2">Set up a new snooker tournament</p>
            </div>
            <a href="tournament_dashboard.php" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
              <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
          </div>
          
          <!-- Debug Info (Remove in production) -->
          <?php if(isset($users) && count($users) > 0): ?>
          <div class="mt-4 text-sm text-gray-500">
            Available users: 
            <?php foreach($users as $user): ?>
              ID: <?php echo $user['id']; ?> (<?php echo $user['name']; ?>), 
            <?php endforeach; ?>
            Using user ID: <?php echo $created_by; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Tournament Details</h2>
            <p class="text-gray-600 text-sm mt-1">Fill in the tournament information</p>
          </div>
          
          <?php if(isset($error)): ?>
          <div class="m-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
            <div class="flex items-center">
              <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
              <div>
                <p class="font-medium text-red-800"><?php echo $error; ?></p>
              </div>
            </div>
          </div>
          <?php endif; ?>
          
          <form method="POST" action="" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              
              <!-- Tournament Name -->
              <div>
                <label class="block text-gray-700 text-sm font-medium mb-2">
                  <i class="fas fa-trophy text-blue-500 mr-2"></i>
                  Tournament Name *
                </label>
                <input type="text" 
                       name="tournament_name" 
                       required
                       placeholder="e.g., Winter Championship"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none"
                       value="<?php echo isset($_POST['tournament_name']) ? htmlspecialchars($_POST['tournament_name']) : ''; ?>">
              </div>
              
              <!-- Tournament Type -->
              <div>
                <label class="block text-gray-700 text-sm font-medium mb-2">
                  <i class="fas fa-chess-board text-purple-500 mr-2"></i>
                  Tournament Type *
                </label>
                <select name="tournament_type" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none">
                  <option value="">Select format</option>
                  <option value="Knockout" <?php echo (isset($_POST['tournament_type']) && $_POST['tournament_type'] == 'Knockout') ? 'selected' : ''; ?>>Knockout</option>
                  <option value="League" <?php echo (isset($_POST['tournament_type']) && $_POST['tournament_type'] == 'League') ? 'selected' : ''; ?>>League</option>
                  <option value="Round Robin" <?php echo (isset($_POST['tournament_type']) && $_POST['tournament_type'] == 'Round Robin') ? 'selected' : ''; ?>>Round Robin</option>
                </select>
              </div>
              
              <!-- Max Players -->
              <div>
                <label class="block text-gray-700 text-sm font-medium mb-2">
                  <i class="fas fa-users text-green-500 mr-2"></i>
                  Maximum Players *
                </label>
                <div class="space-y-2">
                  <input type="number" 
                         name="max_players" 
                         min="2" 
                         max="128" 
                         value="<?php echo isset($_POST['max_players']) ? $_POST['max_players'] : '16'; ?>"
                         required
                         class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none">
                  <div class="text-xs text-gray-500">Minimum 2, maximum 128 players</div>
                </div>
              </div>
              
              <!-- Start Date -->
              <div>
                <label class="block text-gray-700 text-sm font-medium mb-2">
                  <i class="fas fa-calendar-alt text-red-500 mr-2"></i>
                  Start Date *
                </label>
                <input type="date" 
                       name="start_date" 
                       required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none"
                       value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d'); ?>">
              </div>
              
              <!-- End Date -->
              <div>
                <label class="block text-gray-700 text-sm font-medium mb-2">
                  <i class="fas fa-calendar-check text-orange-500 mr-2"></i>
                  End Date
                </label>
                <input type="date" 
                       name="end_date"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field focus:outline-none"
                       value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : ''; ?>">
              </div>
            </div>
            
            <!-- Form Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-center mt-10 pt-6 border-t border-gray-200">
              <div class="mb-4 sm:mb-0">
                <button type="reset" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                  <i class="fas fa-redo mr-2"></i>Reset Form
                </button>
              </div>
              <div class="flex space-x-4">
                <a href="tournament_dashboard.php" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                  Cancel
                </a>
                <button type="submit" name="create_tournament" class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                  <i class="fas fa-plus-circle mr-2"></i>Create Tournament
                </button>
              </div>
            </div>
          </form>
        </div>
        
        <!-- Live Preview -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Live Preview</h3>
              <div id="preview-content" class="preview-card rounded-lg p-6">
                <div class="text-center mb-6">
                  <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 mb-4">
                    <i class="fas fa-trophy text-blue-600"></i>
                  </div>
                  <h4 id="preview-name" class="text-xl font-bold text-gray-800 mb-2">Tournament Name</h4>
                  <div id="preview-type" class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
                    Type
                  </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                  <div class="bg-white rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600" id="preview-players">16</div>
                    <div class="text-sm text-gray-600">Players</div>
                  </div>
                  <div class="bg-white rounded-lg p-4 text-center">
                    <div id="preview-date" class="text-lg font-bold text-gray-800"><?php echo date('M d'); ?></div>
                    <div class="text-sm text-gray-600">Start Date</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="bg-blue-50 rounded-xl border border-blue-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Tips</h3>
            <ul class="space-y-3">
              <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                <span class="text-sm text-gray-700">Knockout tournaments are single elimination</span>
              </li>
              <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                <span class="text-sm text-gray-700">League format ensures everyone plays</span>
              </li>
              <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                <span class="text-sm text-gray-700">Round Robin guarantees matches for all</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </main>
  </div>
    
    <script>
        // Initialize date inputs
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const startDateInput = document.querySelector('input[name="start_date"]');
            const endDateInput = document.querySelector('input[name="end_date"]');
            
            if (!startDateInput.value) {
                startDateInput.value = today;
            }
            
            // Set end date to 7 days from now if empty
            if (!endDateInput.value) {
                const nextWeek = new Date();
                nextWeek.setDate(nextWeek.getDate() + 7);
                endDateInput.value = nextWeek.toISOString().split('T')[0];
            }
            
            updatePreview();
        });

        // Live preview functionality
        const inputs = document.querySelectorAll('input[name], select[name]');
        inputs.forEach(input => {
            input.addEventListener('input', updatePreview);
            input.addEventListener('change', updatePreview);
        });

        function updatePreview() {
            const name = document.querySelector('input[name="tournament_name"]')?.value || 'Tournament Name';
            const type = document.querySelector('select[name="tournament_type"]')?.value || 'Knockout';
            const players = document.querySelector('input[name="max_players"]')?.value || '16';
            const date = document.querySelector('input[name="start_date"]')?.value || new Date().toISOString().split('T')[0];

            // Update preview content
            document.getElementById('preview-name').textContent = name;
            
            // Update type badge
            const typeBadge = document.getElementById('preview-type');
            typeBadge.textContent = type;
            typeBadge.className = 'inline-block px-3 py-1 text-sm rounded-full ';
            switch(type) {
                case 'Knockout':
                    typeBadge.classList.add('bg-yellow-100', 'text-yellow-800');
                    break;
                case 'League':
                    typeBadge.classList.add('bg-blue-100', 'text-blue-800');
                    break;
                case 'Round Robin':
                    typeBadge.classList.add('bg-purple-100', 'text-purple-800');
                    break;
                default:
                    typeBadge.classList.add('bg-gray-100', 'text-gray-800');
            }

            document.getElementById('preview-players').textContent = players;
            
            if (date) {
                const formattedDate = new Date(date).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });
                document.getElementById('preview-date').textContent = formattedDate;
            }
        }

        // Form validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                let valid = true;
                const required = this.querySelectorAll('[required]');
                
                required.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('border-red-500');
                        valid = false;
                    } else {
                        field.classList.remove('border-red-500');
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    // Show error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded';
                    errorDiv.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <div>
                                <p class="font-medium text-red-800">Please fill all required fields</p>
                            </div>
                        </div>
                    `;
                    
                    const existingError = document.querySelector('.bg-red-50.border-red-500');
                    if (!existingError) {
                        this.insertBefore(errorDiv, this.firstChild);
                    }
                }
            });
        }
    </script>
</body>
</html>