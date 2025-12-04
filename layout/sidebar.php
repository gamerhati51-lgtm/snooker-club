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
</script>

<button id="mobileMenuButton" class="lg:hidden fixed top-4 left-4 z-50 p-2 bg-blue-900 text-white rounded-md shadow-lg">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>

<aside id="sidebar" class=" sidebar-fixed-theme fixed top-0 left-0 w-64 h-screen bg-blue-100 text-black shadow-lg z-40 
                       flex flex-col transform -translate-x-full transition-transform duration-300 lg:translate-x-0">
    
   <div class="p-4 bg-blue-900">
     <h1  class="nav-link block px-4 py-3 rounded-lg text-lg font-medium 
                   text-white  bg-none ">
            <i class="fas fa-home mr-3"></i> Weclome Admin👋 </h1>
      
    </div>

    <nav class="flex-grow overflow-y-auto p-4 space-y-2">

        <a href="./admin.php" class="sidebar-link  active text-black flex items-center p-3 text-sm font-semibold border-l-4 border-transparent hover:bg-orange-500 ">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                </path>
            </svg>
            Dashboard
        </a>

     
        <div class="w-full">
        <button onclick="toggleBookings()" 
            class="w-full flex items-center text-black p-3 text-sm font-semibold border-l-4 border-transparent hover:bg-orange-500 transition">
            
             <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0l-1-4m3 4l1-4m-9 4l-1-4m3 4l1-4">
                </path>
            </svg>

            Game Tables 
            <svg id="bookingsArrow" class="w-4 h-4 ml-auto transform transition-transform" fill="none" 
            stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div id="bookingsMenu" class="hidden pl-10 mt-1 space-y-2 text-black"> 

            <a href="./add_table.php" class="flex items-center text-sm py-1 hover:text-blue-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Add Table
            </a>

            <a href="./view_tables.php" class="flex items-center text-sm py-1 hover:text-blue-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                View Tables
            </a>
        </div>
    </div>
    

        <div class="w-full">
            <button onclick="togglePOS()" 
                class="w-full flex items-center text-black p-3 text-sm font-semibold border-l-4 border-transparent hover:bg-orange-500  transition">
                
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.5 7h11l-1.5-7M7 13h10">
                    </path>
                </svg>

                Point Of Sale (POS)

                <svg id="posArrow" class="w-4 h-4 ml-auto transform transition-transform" fill="none" 
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div id="posMenu" class="hidden pl-10 mt-1 space-y-2 text-black"> 

                <a href="./add_product.php" class="flex items-center text-sm py-1 hover:text-blue-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v16m8-8H4" />
                    </svg>
                    Add Product
                </a>

                <a href="./list_product.php" class="flex items-center text-sm py-1 hover:text-blue-600">
                    <!-- List icon -->
     <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
             d="M4 6h16M4 12h16M4 18h16"/>
     </svg>
                    List Product
                </a>

            

                <a href="#" class="flex items-center text-sm py-1 hover:text-blue-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3h18M9 7v14m6-14v14" />
                    </svg>
                    Sell
                </a>

                <a href="#" class="flex items-center text-sm py-1 hover:text-blue-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 1.567-3 3.5S10.343 15 12 15s3-1.567 3-3.5S13.657 8 12 8zm-7 7a4 4 0 118 0m8 0a4 4 0 11-8 0" />
                    </svg>
                    Update Price
                </a>

        </div>
<div class="w-full">
        <button onclick="toggleBookings()" 
            class="w-full flex items-center text-black p-3 text-sm font-semibold border-l-4 border-transparent hover:bg-orange-500 transition">
            
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                </path>
            </svg>

            Bookings

            <svg id="bookingsArrow" class="w-4 h-4 ml-auto transform transition-transform" fill="none" 
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
    




<!-- Expanses Toggle Button -->
<button 
  id="toggleExpanse"
  class="w-full sidebar-link flex text-black items-center p-3 text-sm font-semibold 
  border-l-4 border-transparent hover:bg-orange-500 transition">
    
    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0h6" />
    </svg>

    Expanses

    <!-- Arrow Icon -->
    <svg id="arrowIcon" class="w-4 h-4 ml-auto transition-transform" fill="none" 
         stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M19 9l-7 7-7-7" />
    </svg>

</button>


<!-- Dropdown Content -->
<div id="expanseMenu" class="hidden flex-col ml-10 text-sm">

  <a href="./list_expance.php" 
     class="py-2 flex items-center text-black hover:text-blue-700 transition hover:">
     
     <!-- List icon -->
     <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
             d="M4 6h16M4 12h16M4 18h16"/>
     </svg>

     List Expanses
  </a>

  <a href="./add_expance.php" 
     class="py-2 flex items-center text-black hover:text-blue-700 transition">
     
     <!-- Plus icon -->
     <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
             d="M12 4v16m8-8H4"/>
     </svg>

     Add Expanses
  </a>

</div>




        <a href="./user.php" class="sidebar-link flex text-black items-center p-3 text-sm font-semibold border-l-4 border-transparent hover:bg-orange-500 ">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20h-2m2 0h-2M13 20H11m4-10a4 4 0 11-8 0 4 4 0 018 0z">
                </path>
            </svg>
            Users
        </a>

        <a href="./report.php" class="sidebar-link flex items-center text-black p-3 text-sm font-semibold border-l-4 border-transparent hover:bg-orange-500 ">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            Reports
        </a>

        <a href="ratsiting.php" class="sidebar-link flex text-black items-center p-3 text-sm font-semibold border-l-4 border-transparent hover:bg-orange-500">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z">
                </path>
            </svg>
            Settings
        </a>
    </nav>
        
    <div class="p-4 border-t border-snooker-light flex-shrink-0">
        <p class="text-xs font-semibold text-black">ADMIN:</p>
        <a href="logout.php" title="are you want to logout" class="block w-full text-center bg-orange-600 text-white px-4 py-2 text-sm font-bold mt-4 rounded-lg  hover:bg-red-700 transition transform hover:scale-[1.02]">
            <svg class="w-4 h-4 inline mr-1 -mt-0.5" fill="none"
             stroke="currentColor" viewBox="0 0 24 24" 
             xmlns="http://www.w3.org/2000/svg">
             <path stroke-linecap="round" stroke-linejoin="round" 

            stroke-width="2" 
            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
        </path></svg>
            Logout
        </a>
    </div>

</aside>

<script>
    // --- Dropdown Toggle Functions ---

    // Function for Point Of Sale (POS) Dropdown
    function togglePOS() {
        document.getElementById("posMenu").classList.toggle("hidden");
        document.getElementById("posArrow").classList.toggle("rotate-180");
    }

    // Function for Bookings Dropdown (The new function you needed)
    function toggleBookings() {
        document.getElementById("bookingsMenu").classList.toggle("hidden");
        document.getElementById("bookingsArrow").classList.toggle("rotate-180");
    }

    // --- Mobile Sidebar Toggle Logic ---
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const menuButton = document.getElementById('mobileMenuButton');
        
        // Ensure both elements exist before running logic
        if (!sidebar || !menuButton) return; 

        // Function to toggle the sidebar's visibility
        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            // A visual fix for mobile accessibility: disable scroll on the body when open
            document.body.classList.toggle('overflow-hidden'); 
        }

        // Attach event listeners
        menuButton.addEventListener('click', toggleSidebar);

        // Close sidebar if a link is clicked on mobile (good UX)
        sidebar.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                // Only close if the sidebar is currently open (i.e., on a small screen)
                if (window.innerWidth < 1024 && !sidebar.classList.contains('-translate-x-full')) {
                    toggleSidebar();
                }
            });
        });

        // Close sidebar on outside click on mobile (best practice)
        document.addEventListener('click', (event) => {
            // Check if screen is small AND sidebar is visible AND the click is outside the sidebar/menu button
            if (window.innerWidth < 1024 && 
                !sidebar.contains(event.target) && 
                !menuButton.contains(event.target) && 
                !sidebar.classList.contains('-translate-x-full')) {
                toggleSidebar();
            }
        });
    });

document.getElementById("toggleExpanse").addEventListener("click", function () {

    const menu = document.getElementById("expanseMenu");
    const arrow = document.getElementById("arrowIcon");

    menu.classList.toggle("hidden");

    // Rotate arrow
    arrow.classList.toggle("rotate-180");
});

</script>