<?php
session_start();
if (!isset($_SESSION['admin_name'])) {
    header("Location: index.php");
    exit;
}
// Note: No further PHP code has been added or modified to respect the user's request.
// The content logic (views) is handled via client-side JavaScript for this single file example.
?>
<!DOCTYPE html>
<html>
<head>
    <title>Snooker Club Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom scrollbar styling for the sidebar for a cleaner look */
        .sidebar-nav::-webkit-scrollbar {
            width: 6px;
        }
       
        
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans">

    <!-- Main Flex Container for Sidebar and Content -->
    <div class="flex min-h-screen">
<!-- SIDEBAR -->
<nav id="sidebar" 
     class="w-64 bg-blue-100 text-gray-700 flex flex-col fixed inset-y-0 left-0 
            transform -translate-x-full md:relative md:translate-x-0 
            transition-transform duration-300 z-30">

    <!-- Dashboard -->
    <div class="p-6 bg-blue-900">
        <a href="admin.php" 
           class="nav-link block px-4 py-3 rounded-lg text-lg font-medium 
                  bg-orange-700 text-white shadow-lg hover:bg-orange-600">
            <i class="fas fa-home mr-3"></i> Dashboard
        </a>
    </div>

    <!-- NAV LINKS -->
    <div class="sidebar-nav flex-grow p-4 overflow-y-auto">

        <ul class="space-y-2">

            <li class="pt-4 border-t border-gray-300">
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2 px-2">
                    Management
                </p>
            </li>

            <!-- Table Bookings -->
            <li>
                <a href="bookings.php" 
                   class="nav-link block px-4 py-3 rounded-lg 
                          text-gray-700 hover:text-white hover:bg-orange-500 transition">
                    <i class="fas fa-calendar-check mr-3"></i> Table Bookings
                </a>
            </li>

            <li class="pt-4 border-t border-gray-300">
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2 px-2">
                    Finance & Settings
                </p>
            </li>

            <!-- Pricing -->
            <li>
                <a href="#" 
                   class="nav-link block px-4 py-3 rounded-lg 
                          text-gray-700 hover:text-white hover:bg-orange-500 transition">
                    <i class="fas fa-pound-sign mr-3"></i> Pricing & Rates
                </a>
            </li>

            <!-- Reports -->
            <li>
                <a href="report.php" 
                   class="nav-link block px-4 py-3 rounded-lg 
                          text-gray-700 hover:text-white hover:bg-orange-500 transition">
                    <i class="fas fa-chart-line mr-3"></i> Reports & Analytics
                </a>
            </li>

            <!-- Change Password -->
            <li>
                <a href="password.php" 
                   class="nav-link block px-4 py-3 rounded-lg 
                          text-gray-700 hover:text-white hover:bg-orange-500 transition">
                    <i class="fas fa-key mr-3"></i> Change Password
                </a>
            </li>

            <!-- Club Settings -->
            <li>
                <a href="#" 
                   class="nav-link block px-4 py-3 rounded-lg 
                          text-gray-700 hover:text-white hover:bg-orange-500 transition">
                    <i class="fas fa-cog mr-3"></i> Club Settings
                </a>
            </li>

        </ul>
    </div>

    <!-- Logout -->
    <div class="p-4 border-t border-gray-300">
        <a href="logout.php" 
           class="block w-full text-center bg-orange-500 text-white px-4 py-2 rounded-lg font-semibold 
                  hover:bg-red-700 transition">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </a>
    </div>

</nav>

          <!-- Header -->
      <?php include "layout/header.php"; ?>
        <!-- Sidebar Toggle for Mobile -->
        <button id="sidebar-toggle" class="fixed top-4 left-4 p-2 mt-6 bg-gray-800 text-white rounded-lg shadow-xl md:hidden z-40 transition-all duration-300">
            <i class="fas fa-bars"></i>
        </button>
  <!-- 2. MAIN CONTENT AREA -->
        <div class="flex-grow p-4 md:p-8 transition-all duration-300 mt-7">
 
        <header class="bg-white p-4 shadow-md rounded-lg mt-9 mb-5 flex justify-between items-center sticky top-0 z-20">
                <h1 class="text-3xl font-bold text-gray-800">
                    Admin Panel
                </h1>
                <div class="flex items-center space-x-4">
                    <!-- Original Welcome Message -->
                    <span class="text-lg font-semibold text-green-700 hidden sm:inline">
                        Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!
                    </span>
                    <!-- Original Logout Button (for redundancy/visibility) -->
                    <a href="logout.php" class="bg-orange-500 text-white px-3 py-2 text-sm rounded-lg hover:bg-orange-600 transition shadow-md md:hidden">
                        Logout
                    </a>
                </div>
            </header>
            
            <!-- CONTENT VIEWS (Switched by JavaScript) -->
            <main class="space-y-8">
                
                <!-- DASHBOARD VIEW -->
                <div id="view-dashboard" class="content-view active bg-white p-6 rounded-lg shadow-xl">
                    <h3 class="text-2xl font-semibold border-b pb-3 mb-6 text-gray-700">Club Overview</h3>
                    <p class="mb-6 text-gray-600">This is the main dashboard. Quickly see key metrics for your snooker club.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Stat Card 1 -->
                        <div class="bg-blue-100 p-5 rounded-xl shadow-lg border-l-4 border-blue-500">
                            <p class="text-sm uppercase font-medium text-blue-600">Tables Booked Today</p>
                            <p class="text-4xl font-extrabold text-gray-900 mt-1">12 / 20</p>
                            <p class="text-xs text-blue-400 mt-2">75% Capacity</p>
                        </div>
                        <!-- Stat Card 2 -->
                        <div class="bg-yellow-100 p-5 rounded-xl shadow-lg border-l-4 border-yellow-500">
                            <p class="text-sm uppercase font-medium text-yellow-600">Active Members</p>
                            <p class="text-4xl font-extrabold text-gray-900 mt-1">45</p>
                            <p class="text-xs text-yellow-400 mt-2">3 New Signups</p>
                        </div>
                        <!-- Stat Card 3 -->
                        <div class="bg-green-100 p-5 rounded-xl shadow-lg border-l-4 border-green-500">
                            <p class="text-sm uppercase font-medium text-green-600">Upcoming Tournament</p>
                            <p class="text-4xl font-extrabold text-gray-900 mt-1">Club Open</p>
                            <p class="text-xs text-green-400 mt-2">Starts: Fri, 15th</p>
                        </div>
                    </div>
                </div>

            
                
                
             
            
           

            </main>

        </div>
        <!-- End Main Content Area -->

    </div>
    <!-- End Main Flex Container -->


    <!-- Font Awesome Icons for visual appeal -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script>
</body>
</html>