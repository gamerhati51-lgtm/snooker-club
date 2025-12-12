<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<script>
    // Include the necessary Tailwind CSS CDN link if this is a standalone file for development
    // <script src="https://cdn.tailwindcss.com
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
</script>
<aside id="sidebar"
 class="fixed top-0 left-0 w-64 h-screen bg-blue-100 text-black shadow-lg z-40 
      flex flex-col transform -translate-x-full transition-transform duration-300 lg:translate-x-0">
    <a href="./admin.php"><div class="p-1 bg-blue-600">
        <h1 class="nav-link block px-4 py-4 rounded-lg text-lg font-medium 
                        text-white  bg-none ">
            <i class="fas fa-home mr-3"></i> SNOOKER CLUB </h1>
    </div></a>

    <nav class="flex-grow overflow-y-auto p-4 space-y-2">

        <a href="./admin.php" class="sidebar-link  active text-black flex items-center
          p-3 text-sm font-semibold border-l-4 border-transparent hover:bg-blue-200">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                </path>
            </svg>
         Home
        </a>

        <div class="w-full">
            <button onclick="toggleDropdown('tablesMenu', 'tablesArrow')" 
                class="w-full flex items-center text-black p-3 text-sm font-semibold border-l-4 border-transparent hover:bg-blue-200 transition">
                
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0l-1-4m3 4l1-4m-9 4l-1-4m3 4l1-4">
                    </path>
                </svg>

                Game Tables 
                <svg id="tablesArrow" class="w-4 h-4 ml-auto transform transition-transform" fill="none" 
                stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            

            <div id="tablesMenu" class="hidden pl-10 mt-1 space-y-2 text-black"> 
          
                   
                <a href="./view_tables.php" class="flex items-center text-sm py-1 hover:text-blue-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    View Tables
                </a>
            </div>
        </div>
<div class="w-full">
    <button onclick="toggleDropdown('tournamentMenu', 'tournamentArrow')" 
        class="w-full flex items-center text-black p-3 text-sm font-semibold border-l-4 border-transparent hover:bg-blue-200 transition">
        
        <!-- Tournament Icon -->
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
        </svg>

        Tournament 
        <svg id="tournamentArrow" class="w-4 h-4 ml-auto transform transition-transform" fill="none" 
        stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Collapsible Menu Items -->
    <div id="tournamentMenu" class="hidden pl-10 mt-1 space-y-2 text-black"> 
        <a href="tournament_dashboard.php" class="flex items-center text-sm py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Dashboard
        </a>
        <a href="create_tournament.php" class="flex items-center text-sm py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create Tournament
        </a>
        <a href="tournament_list.php" class="flex items-center text-sm py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Tournament List
        </a>
        <a href="add_players.php" class="flex items-center text-sm py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            Add Players
        </a>
        <a href="generate_brackets.php" class="flex items-center text-sm py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Generate Brackets
        </a>
        <a href="enter_scores.php" class="flex items-center text-sm py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Enter Scores
        </a>
        <a href="tournament_results.php" class="flex items-center text-sm py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
            Tournament Results
        </a>
    </div>
</div>
        <div class="w-full">
            <button onclick="toggleDropdown('posMenu', 'posArrow')" 
                class="w-full flex items-center text-black p-3 text-sm font-semibold border-l-4 border-transparent hover:bg-blue-200 transition">
                
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.5 7h11l-1.5-7M7 13h10">
                    </path>
                </svg>

                POS

                <svg id="posArrow" class="w-4 h-4 ml-auto transform transition-transform" fill="none" 
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div id="posMenu" class="hidden pl-10 mt-1 space-y-2 text-black"> 
               

                <a href="./sales.php" class="flex items-center text-sm py-1 hover:text-blue-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3h18M9 7v14m6-14v14" />
                    </svg>
                    Sell
                </a>

                <a href="./update_price.php" class="flex items-center text-sm py-1 hover:text-blue-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 1.567-3 3.5S10.343 15 12 15s3-1.567 3-3.5S13.657 8 12 8zm-7 7a4 4 0 118 0m8 0a4 4 0 11-8 0" />
                    </svg>
                    Update Price
                </a>
            </div>
        </div>
     
        <!-- Second Dropdown - Inventory (FIXED WITH UNIQUE IDs) -->
        <div class="w-full">
            <button onclick="toggleDropdown('inventoryMenu', 'inventoryArrow')" 
                class="w-full flex items-center text-black p-3 text-sm font-semibold border-l-4 border-transparent hover:bg-blue-200 transition">
                
           <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4">
    </path>
</svg>
                </svg>

                Inventory

                <svg id="inventoryArrow" class="w-4 h-4 ml-auto transform transition-transform" fill="none" 
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div id="inventoryMenu" class="hidden pl-10 mt-1 space-y-2 text-black"> 
                <a href="./add_product.php" class="flex items-center text-sm py-1 hover:text-blue-600">
             <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
        d="M12 4v16m8-8H4m7 4h3m-3-3v3M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8">
    </path>
</svg>

                    Add Product
                </a>

                <a href="./list_product.php" class="flex items-center text-sm py-1 hover:text-blue-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    List Product
                </a>
            </div>
        </div>

        <div class="w-full">
            <button onclick="toggleDropdown('bookingsMenu', 'bookingsArrow2')" 
                class="w-full flex items-center text-black p-3 text-sm font-semibold 
              border-l-4 border-transparent hover:bg-blue-200">
                
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>

                Bookings

                <svg id="bookingsArrow2" class="w-4 h-4 ml-auto transform transition-transform" fill="none" 
                stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div id="bookingsMenu" class="hidden pl-10 mt-1 space-y-2 text-black"> 
                <a href="./add_booking.php" class="flex items-center text-sm py-1 hover:text-blue-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    New Booking
                </a>
                <a href="./bookings.php" class="flex items-center text-sm py-1 hover:text-blue-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    View Bookings
                </a>
            </div>
        </div>
        
 <!-- Expenses Dropdown -->
<div class="w-full">
  <button onclick="toggleDropdown('expanseMenu1', 'expanseArrow1')" 
          class="w-full sidebar-link flex text-black items-center p-3 text-sm font-semibold 
                 border-l-4 border-transparent hover:bg-blue-200"> 
    <!-- Expenses Icon -->
    <svg class="w-5 h-5 mr-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0h6" />
    </svg>
    Expenses
    <!-- Arrow Icon -->
    <svg id="expanseArrow1" class="w-4 h-4 ml-auto transform transition-transform text-gray-700" fill="none" 
         stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M19 9l-7 7-7-7" />
    </svg>
  </button>

  <div id="expanseMenu1" class="hidden pl-10 mt-1 space-y-2 text-black">
    <a href="./list_expanse.php" class="py-1 flex items-center text-sm hover:text-blue-700 transition">
      <svg class="w-4 h-4 mr-2 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
      List Expenses
    </a>
    <a href="./add_expance.php" class="py-1 flex items-center text-sm hover:text-blue-700 transition">
      <svg class="w-4 h-4 mr-2 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M12 4v16m8-8H4"/>
      </svg>
      Add Expenses
    </a>
  </div>
</div>

<!-- User Dropdown -->
<div class="w-full">
  <button onclick="toggleDropdown('expanseMenu2', 'expanseArrow2')" 
          class="w-full sidebar-link flex text-black items-center p-3 text-sm font-semibold 
                 border-l-4 border-transparent hover:bg-blue-200"> 
    <!-- User Icon -->
 <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
    </path>
</svg>
    User
    <!-- Arrow Icon -->
    <svg id="expanseArrow2" class="w-4 h-4 ml-auto 
    transform transition-transform text-gray-700" fill="none" 
         stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M19 9l-7 7-7-7" />
    </svg>
  </button>

  <div id="expanseMenu2" class="hidden pl-10 mt-1 space-y-2 text-black">
 <!-- List of Users -->
<a href="./user.php" class="py-1 flex items-center text-sm hover:text-blue-700 transition">
 <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-1.205a4 4 0 11-8 0 4 4 0 018 0z">
    </path>
</svg>
  Role Of Users
</a>



    <a href="./add_user.php" class="py-1 flex items-center text-sm hover:text-blue-700 transition">
    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
    </path>
</svg>
      Add New User
    </a>
  </div>
</div>

<script>
  function toggleDropdown(menuId, arrowId) {
    const menu = document.getElementById(menuId);
    const arrow = document.getElementById(arrowId);

    // Toggle current dropdown
    menu.classList.toggle('hidden');
    arrow.classList.toggle('rotate-180');

    // Close other dropdowns
    document.querySelectorAll('[id^="expanseMenu"]').forEach(el => {
      if(el.id !== menuId) el.classList.add('hidden');
    });
    document.querySelectorAll('[id^="expanseArrow"]').forEach(el => {
      if(el.id !== arrowId) el.classList.remove('rotate-180');
    });
  }
</script>


        <a href="./report.php" class="sidebar-link flex items-center text-black p-3 text-sm
          font-semibold border-l-4 border-transparent hover:bg-blue-200">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            Reports
        </a>
<!-- Settings Dropdown -->
<div>
    <button onclick="toggleDropdown('settingsMenu','settingsArrow')" class="w-full flex items-center p-3 text-sm font-semibold border-l-4 border-transparent hover:bg-blue-200">
        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z">
            </path>
        </svg>
        Settings
        <svg id="settingsArrow" class="w-4 h-4 ml-auto transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div id="settingsMenu" class="hidden pl-10 mt-1 space-y-2 text-sm">

        <a href="./report.php" class="flex items-center py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6h6v6m-6-6V5h6v6h6v12H3V11h6z"/>
            </svg>
            Report
        </a>

        <a href="./password.php" class="flex items-center py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.657 1.567-3 3.5-3S19 9.343 19 11v2h-2v-2c0-.828-.672-1.5-1.5-1.5S14 10.172 14 11v2h-2v-2zM5 11v2H3v-2c0-2.21 1.79-4 4-4h1v2H7c-1.105 0-2 .895-2 2z"/>
            </svg>
            Change Password
        </a>

        <a href="./user.php" class="flex items-center py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A4 4 0 0112 15a4 4 0 016.879 2.804M12 12a4 4 0 100-8 4 4 0 000 8z"/>
            </svg>
            Users
        </a>

        <a href="./pricing.php" class="flex items-center py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.567-3 3.5S10.343 15 12 15s3-1.567 3-3.5S13.657 8 12 8zm0 0V5m0 10v3m-9-6h3m10 0h3M4.222 19.778l2.121-2.121m12.728 0l2.121 2.121M4.222 4.222l2.121 2.121m12.728 0l2.121-2.121"/>
            </svg>
            Pricing
        </a>

        <a href="./update_price.php" class="flex items-center py-1 hover:text-blue-600">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            Rates
        </a>

    </div>
</div>

    </nav>
        
    <div class="p-4 border-t border-snooker-light flex-shrink-0">
        <p class="text-xs font-semibold text-black">ADMIN:</p>
        <a href="logout.php" title="are you want to logout" class="block w-full text-center bg-blue-800 
        text-white px-4 py-2 text-sm font-bold mt-4 rounded-lg shadow-md hover:bg-blue-700 transition transform hover:scale-[1.02]">
            <svg class="w-4 h-4 inline mr-1 -mt-0.5" fill="none"
             stroke="currentColor" viewBox="0 0 24 24" 
             xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" 
            stroke-width="2" 
            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3
             3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
            </path></svg>
            Logout
        </a>
    </div>

</aside>


<script>
    // --- Generic Dropdown Toggle Function ---
    // This consolidated function makes the code cleaner and easier to maintain.
    function toggleDropdown(menuId, arrowId) {
        document.getElementById(menuId).classList.toggle("hidden");
        // Check if the arrow element exists before trying to rotate it
        const arrow = document.getElementById(arrowId);
        if (arrow) {
             arrow.classList.toggle("rotate-180");
        }
    }

    // --- Mobile Sidebar Toggle Logic ---
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        
        // Close sidebar if a link is clicked on mobile (good UX)
        if (sidebar) {
            sidebar.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 1024 && !sidebar.classList.contains('-translate-x-full')) {
                        toggleSidebar();
                    }
                });
            });
        }

        // Close sidebar on outside click on mobile (best practice)
        document.addEventListener('click', (event) => {
            const sidebar = document.getElementById('sidebar');
            const mobileMenuBtn = document.getElementById('mobileMenuButton');
            
            if (window.innerWidth < 1024 && 
                sidebar && 
                !sidebar.classList.contains('-translate-x-full') &&
                !sidebar.contains(event.target) && 
                (!mobileMenuBtn || !mobileMenuBtn.contains(event.target))) {
                toggleSidebar();
            }
        });
    });
    function toggleDropdown(menuId, arrowId) {
    const menu = document.getElementById(menuId);
    const arrow = document.getElementById(arrowId);
    
    menu.classList.toggle('hidden');
    arrow.classList.toggle('rotate-180');
}
</script>