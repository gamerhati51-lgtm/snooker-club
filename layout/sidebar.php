    <!-- Sidebar (Fixed) -->
    <?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
   
      <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'snooker-green': '#183a34', // Deeper Forest Green
                        'snooker-light': '#2a4d45',
                        'snooker-accent': '#ffb703', // Brighter Gold/Yellow accent
                        'snooker-bg': '#f3f4f6', // Light gray background
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script><aside id="sidebar" class="w-64 bg-snooker-green text-gray-200 flex flex-col fixed h-full snooker-shadow z-10 overflow-y-auto">
     
            <!-- Logo/Title -->
            <div class="p-6 text-center border-b border-snooker-light mt-0">
                <h2 class="text-3xl font-extrabold text-snooker-accent tracking-widest">Admin</h2>
                <p class="text-xs mt-1 text-gray-400 font-medium uppercase">Admin Control Panel</p>
            </div>

     <!-- Navigation Links -->
<nav class="flex-grow p-4 space-y-2">

    <!-- Dashboard -->
    <a href="./admin.php" class="sidebar-link active flex items-center p-3 text-sm font-semibold border-l-4 border-transparent">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
            </path>
        </svg>
        Dashboard
    </a>

    <!-- Tables -->
    <a href="./add_table.php" class="sidebar-link flex items-center p-3 text-sm font-semibold border-l-4 border-transparent">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0l-1-4m3 4l1-4m-9 4l-1-4m3 4l1-4">
            </path>
        </svg>
        Tables Management
    </a>

    <!-- POS WITH DROPDOWN -->
<!-- POS WITH DROPDOWN -->
<div class="w-full">
    <button onclick="togglePOS()" 
        class="w-full flex items-center p-3 text-sm font-semibold border-l-4 border-transparent hover:bg-none transition">
        
        <!-- POS Icon -->
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.5 7h11l-1.5-7M7 13h10">
            </path>
        </svg>

        Point Of Sale (POS)

        <!-- Arrow -->
<svg id="posArrow" class="w-4 h-4 ml-auto transform transition-transform" fill="none" 
stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- DROPDOWN ITEMS -->
    <div id="posMenu" class="hidden pl-10 mt-1 space-y-2">

        <!-- Add Product -->
        <a href="#" class="flex items-center text-sm py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4v16m8-8H4" />
            </svg>
            Add Product
        </a>

        <!-- Purchases -->
        <a href="#" class="flex items-center text-sm py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 3h18M9 7v14m6-14v14" />
            </svg>
            Purchases
        </a>

        <!-- Sell -->
        <a href="#" class="flex items-center text-sm py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8c-1.657 0-3 1.567-3 3.5S10.343 15 12 15s3-1.567 3-3.5S13.657 8 12 8zm-7 7a4 4 0 118 0m8 0a4 4 0 11-8 0" />
            </svg>
            Sell
        </a>

        <!-- Update Price -->
        <a href="#" class="flex items-center text-sm py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8c-1.656 0-3 1.568-3 3.5s1.344 3.5 3 3.5 3-1.568 3-3.5S13.656 8 12 8z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M6 20h12M7 4h10" />
            </svg>
            Update Price
        </a>

        <!-- Stock Transfers -->
        <a href="#" class="flex items-center text-sm py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 12h16m-4-4l4 4-4 4" />
            </svg>
            Stock Transfers
        </a>

        <!-- Stock Adjustment -->
        <a href="#" class="flex items-center text-sm py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 6v6l4 2" />
            </svg>
            Stock Adjustment
        </a>

    </div>
</div>


    <!-- Bookings -->
    <a href="./bookings.php" class="sidebar-link flex items-center p-3 text-sm font-semibold border-l-4 border-transparent">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
            </path>
        </svg>
        Bookings
    </a>

    <!-- Expenses -->
    <a href="./expance.php" class="sidebar-link flex items-center p-3 text-sm font-semibold border-l-4 border-transparent">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0h6">
            </path>
        </svg>
        Expanses
    </a>

    <!-- User -->
    <a href="./user.php" class="sidebar-link flex items-center p-3 text-sm font-semibold border-l-4 border-transparent">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20h-2m2 0h-2M13 20H11m4-10a4 4 0 11-8 0 4 4 0 018 0z">
            </path>
        </svg>
        User
    </a>

    <!-- Reports -->
    <a href="./report.php" class="sidebar-link flex items-center p-3 text-sm font-semibold border-l-4 border-transparent">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
        Reports
    </a>

    <!-- Settings -->
    <a href="ratsiting.php" class="sidebar-link flex items-center p-3 text-sm font-semibold border-l-4 border-transparent">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z">
            </path>
        </svg>
        Rates & Settings
    </a>

</nav>



<script>
function togglePOS() {
    document.getElementById("posMenu").classList.toggle("hidden");
    document.getElementById("posArrow").classList.toggle("rotate-180");
}
</script>
            
            <!-- Admin Info / Logout (Bottom) -->
            <div class="p-4 border-t border-snooker-light mt-auto sticky-bottom">
                <p class="text-xs font-semibold text-gray-400">ADMIN:</p>
 <p class="text-white font-extrabold mb-3 text-lg"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
                <a href="logout.php" class="block w-full text-center bg-red-600 text-white px-4 py-2 text-sm font-bold rounded-lg hover:bg-red-700 transition transform hover:scale-[1.02]">
                    <svg class="w-4 h-4 inline mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Logout
                </a>
            </div>

        </aside>