<style>
/* ============================================
   GLOBAL DARK MODE FIX - NUCLEAR OPTION
   ============================================ */
.dark-mode {
    background-color: #121212 !important;
    color: #ffffff !important;
}

/* FORCE ALL TEXT TO BE WHITE - NO EXCEPTIONS */
.dark-mode,
.dark-mode *:not(svg):not(path):not(g) {
    color: #ffffff !important;
}

/* ===============================
   SPECIFIC TABLE CELL FIX
   =============================== */
/* Target your exact table cell */
.dark-mode td.px-6.py-4,
.dark-mode td.px-6.py-4.text-gray-700,
.dark-mode td.text-gray-700,
.dark-mode .px-6.py-4.text-gray-700 {
    color: #ffffff !important;
    background-color: transparent !important;
}

/* Force all text inside table cells */
.dark-mode td *,
.dark-mode td span,
.dark-mode td div,
.dark-mode td p {
    color: #ffffff !important;
}

/* ===============================
   KILL ALL GRAY TEXT CLASSES
   =============================== */
.dark-mode .text-gray-100,
.dark-mode .text-gray-200,
.dark-mode .text-gray-300,
.dark-mode .text-gray-400,
.dark-mode .text-gray-500,
.dark-mode .text-gray-600,
.dark-mode .text-gray-700,
.dark-mode .text-gray-800,
.dark-mode .text-gray-900,
.dark-mode .text-slate-400,
.dark-mode .text-slate-500,
.dark-mode .text-slate-600,
.dark-mode .text-slate-700,
.dark-mode .text-zinc-400,
.dark-mode .text-zinc-500,
.dark-mode .text-zinc-600,
.dark-mode .text-zinc-700,
.dark-mode .text-neutral-400,
.dark-mode .text-neutral-500,
.dark-mode .text-neutral-600,
.dark-mode .text-neutral-700 {
    color: #ffffff !important;
}

/* ===============================
   INLINE STYLE OVERRIDE
   =============================== */
/* If text color is set inline, override it */
.dark-mode [style*="color: gray"],
.dark-mode [style*="color: #"],
.dark-mode [style*="color: rgb"],
.dark-mode [style*="color: rgba"] {
    color: #ffffff !important;
}

/* ===============================
   DEBUG MODE - TEMPORARY
   =============================== */
/* Uncomment this to see what's being targeted */
/*
.dark-mode td.px-6.py-4.text-gray-700 {
    background-color: #ff0000 !important;
    border: 2px solid yellow !important;
}
*/

/* ===============================
   STATUS BADGE SPECIFIC FIX
   =============================== */
/* If your badge has classes like text-green-800, fix them */
.dark-mode .text-green-800,
.dark-mode .text-red-800,
.dark-mode .text-yellow-800,
.dark-mode .text-blue-800,
.dark-mode .text-purple-800,
.dark-mode .text-pink-800 {
    color: #ffffff !important;
}

/* Badge backgrounds in dark mode */
.dark-mode .bg-green-100 {
    background-color: #1a3a1a !important;
}
.dark-mode .bg-red-100 {
    background-color: #3a1a1a !important;
}
.dark-mode .bg-yellow-100 {
    background-color: #3a3a1a !important;
}
.dark-mode .bg-blue-100 {
    background-color: #1a1a3a !important;
}

/* ===============================
   TABLE SPECIFIC
   =============================== */
.dark-mode table {
    background-color: #1a1a1a !important;
}

.dark-mode table tr {
    background-color: #1a1a1a !important;
}

.dark-mode table tr:nth-child(even) {
    background-color: #222222 !important;
}

.dark-mode td,
.dark-mode th {
    border-color: #333 !important;
    color: #ffffff !important;
}

/* ===============================
   ATOMIC FIX - ULTIMATE SOLUTION
   =============================== */
/* Add this at the END of your dark mode CSS */
.dark-mode * {
    color: #ffffff !important;
}

/* Allow only specific exceptions */
.dark-mode input:not([type="file"]),
.dark-mode select,
.dark-mode textarea {
    color: #ffffff !important;
}

.dark-mode ::placeholder {
    color: #888 !important;
}
.dark-mode .border-snooker-light a[href="logout.php"] {
    background-color: #041403ff !important;
    color: #ffffff !important;
}

.dark-mode .border-snooker-light a[href="logout.php"]:hover {
    background-color: #032203ff !important;
}
</style>
<style>
/* ============================================
   GLOBAL DARK MODE - FIX TEXT VISIBILITY
   ============================================ */
.dark-mode {
    background-color: #0f0f0f !important;
    color: #ffffff !important;
}

/* Ensure ALL text is visible - OVERRIDE light text colors */
.dark-mode * {
    color: #ffffff !important;
}

/* Force text colors to be visible */
.dark-mode span,
.dark-mode div,
.dark-mode p,
.dark-mode li,
.dark-mode td,
.dark-mode th,
.dark-mode label,
.dark-mode small,
.dark-mode strong,
.dark-mode em,
.dark-mode b,
.dark-mode i {
    color: #ffffff !important;
}

/* Headings - slightly brighter */
.dark-mode h1,
.dark-mode h2,
.dark-mode h3,
.dark-mode h4,
.dark-mode h5,
.dark-mode h6 {
    color: #f0f0f0 !important;
}

/* Fix for gray text classes */
.dark-mode .text-gray-100,
.dark-mode .text-gray-200,
.dark-mode .text-gray-300,
.dark-mode .text-gray-400,
.dark-mode .text-gray-500,
.dark-mode .text-gray-600,
.dark-mode .text-gray-700,
.dark-mode .text-gray-800,
.dark-mode .text-gray-900,
.dark-mode .text-slate-100,
.dark-mode .text-slate-200,
.dark-mode .text-slate-300,
.dark-mode .text-slate-400,
.dark-mode .text-slate-500,
.dark-mode .text-slate-600,
.dark-mode .text-slate-700,
.dark-mode .text-slate-800,
.dark-mode .text-slate-900,
.dark-mode .text-zinc-100,
.dark-mode .text-zinc-200,
.dark-mode .text-zinc-300,
.dark-mode .text-zinc-400,
.dark-mode .text-zinc-500,
.dark-mode .text-zinc-600,
.dark-mode .text-zinc-700,
.dark-mode .text-zinc-800,
.dark-mode .text-zinc-900,
.dark-mode .text-neutral-100,
.dark-mode .text-neutral-200,
.dark-mode .text-neutral-300,
.dark-mode .text-neutral-400,
.dark-mode .text-neutral-500,
.dark-mode .text-neutral-600,
.dark-mode .text-neutral-700,
.dark-mode .text-neutral-800,
.dark-mode .text-neutral-900 {
    color: #ffffff !important;
}

/* ===============================
   HEADER / NAVBAR
   =============================== */
.dark-mode header,
.dark-mode nav,
.dark-mode .navbar,
.dark-mode .top-header {
    background-color: #1a1a1a !important;
    border-bottom: 1px solid #333 !important;
}

/* Fix navbar text */
.dark-mode header *,
.dark-mode nav *,
.dark-mode .navbar * {
    color: #ffffff !important;
}

/* ===============================
   SIDEBAR BUTTONS FIX (For both Expanses and Bookings)
   =============================== */

/* Remove background from ALL sidebar buttons/toggle buttons */
.dark-mode .sidebar-link,
.dark-mode #toggleExpanse,
.dark-mode button[onclick*="toggleBookings"],
.dark-mode button[onclick*="toggle"] {
    background-color: transparent !important;
    color: #e0e0e0 !important;
    border-color: transparent !important;
}

/* Hover state for all sidebar buttons */
.dark-mode .sidebar-link:hover,
.dark-mode #toggleExpanse:hover,
.dark-mode button[onclick*="toggleBookings"]:hover,
.dark-mode button[onclick*="toggle"]:hover {
    background-color: #2a2a2a !important;
    color: #ffffff !important;
}

/* Fix text-black class in dark mode (applies to both buttons) */
.dark-mode .text-black {
    color: #ffffff !important;
}

/* Fix SVG icons in sidebar buttons */
.dark-mode .sidebar-link svg,
.dark-mode #toggleExpanse svg,
.dark-mode button[onclick*="toggleBookings"] svg {
    color: #e0e0e0 !important;
}

.dark-mode .sidebar-link:hover svg,
.dark-mode #toggleExpanse:hover svg,
.dark-mode button[onclick*="toggleBookings"]:hover svg {
    color: #ffffff !important;
}

/* More specific selector for your Bookings button if needed */
.dark-mode button[onclick="toggleBookings()"] {
    background-color: transparent !important;
    color: #e0e0e0 !important;
}

.dark-mode button[onclick="toggleBookings()"]:hover {
    background-color: #2a2a2a !important;
    color: #ffffff !important;
}
/* ===============================
   CARDS / BOXES - FIX TEXT IN CARDS
   =============================== */
.dark-mode .card,
.dark-mode .bg-white,
.dark-mode .bg-gray-50,
.dark-mode .bg-gray-100,
.dark-mode .bg-gray-200,
.dark-mode .bg-gray-300,
.dark-mode .bg-gray-400,
.dark-mode .rounded-lg,
.dark-mode .shadow-md,
.dark-mode .shadow-lg,
.dark-mode .p-4,
.dark-mode .p-6,
.dark-mode .box,
.dark-mode .panel {
    background-color: #1a1a1a !important;
    border-color: #333 !important;
    color: #ffffff !important;
}

/* Force text inside cards to be white */
.dark-mode .card *,
.dark-mode .bg-white *,
.dark-mode .bg-gray-50 *,
.dark-mode .bg-gray-100 *,
.dark-mode .bg-gray-200 * {
    color: #ffffff !important;
}

/* ===============================
   BUTTONS - ALL DARK
   =============================== */
.dark-mode button,
.dark-mode .btn,
.dark-mode input[type="submit"],
.dark-mode input[type="button"],
.dark-mode .button {
    background-color: #2d2d2d !important;
    color: #ffffff !important;
    border: 1px solid #444 !important;
}

.dark-mode button:hover,
.dark-mode .btn:hover {
    background-color: #3a3a3a !important;
}

/* Button text must be visible */
.dark-mode button *,
.dark-mode .btn * {
    color: #ffffff !important;
}

/* Colored buttons - text stays white */
.dark-mode .bg-blue-600,
.dark-mode .bg-blue-500,
.dark-mode .bg-blue-400,
.dark-mode .btn-primary,
.dark-mode .bg-red-600,
.dark-mode .bg-red-500,
.dark-mode .bg-red-400,
.dark-mode .bg-green-600,
.dark-mode .bg-green-500,
.dark-mode .bg-green-400,
.dark-mode .bg-yellow-600,
.dark-mode .bg-yellow-500,
.dark-mode .bg-yellow-400,
.dark-mode .bg-purple-600,
.dark-mode .bg-purple-500,
.dark-mode .bg-purple-400 {
    background-color: #2d2d2d !important;
    color: #ffffff !important;
    border-color: #444 !important;
}

/* ===============================
   TABLES - FIX TABLE TEXT
   =============================== */
.dark-mode table {
    background-color: #1a1a1a !important;
}

.dark-mode table tr {
    background-color: #1a1a1a !important;
}

.dark-mode table tr:nth-child(even) {
    background-color: #1f1f1f !important;
}

/* Table text must be visible */
.dark-mode table *,
.dark-mode tr *,
.dark-mode td *,
.dark-mode th * {
    color: #ffffff !important;
}

.dark-mode table td,
.dark-mode table th {
    color: #ffffff !important;
    border-color: #333 !important;
}

/* ===============================
   FORMS - FIX FORM TEXT
   =============================== */
.dark-mode input:not([type="file"]),
.dark-mode select,
.dark-mode textarea {
    background-color: #1a1a1a !important;
    color: #ffffff !important;
    border: 1px solid #444 !important;
}

/* Fix placeholder text */
.dark-mode ::placeholder {
    color: #999 !important;
}

/* Form labels */
.dark-mode label {
    color: #e0e0e0 !important;
}

/* ===============================
   FILE INPUTS
   =============================== */
.dark-mode input[type="file"] {
    background-color: #1a1a1a !important;
    color: #ffffff !important;
    border: 1px solid #444 !important;
}

.dark-mode input[type="file"]::file-selector-button {
    background-color: #2d2d2d !important;
    color: #ffffff !important;
    border: 1px solid #444 !important;
}

/* ===============================
   FIX SPECIFIC TEXT COLOR ISSUES
   =============================== */
/* Any element with light text color that disappears */
.dark-mode [class*="text-"] {
    color: #ffffff !important;
}

/* Override any inline text colors */
.dark-mode [style*="color:"] {
    color: #ffffff !important;
}

/* Fix for elements with opacity or light colors */
.dark-mode .text-light,
.dark-mode .text-muted,
.dark-mode .text-secondary {
    color: #cccccc !important;
}

/* Fix for badge text */
.dark-mode .badge,
.dark-mode .tag {
    background-color: #2d2d2d !important;
    color: #ffffff !important;
}

/* ===============================
   FIX DROPDOWNS & MODALS
   =============================== */
.dark-mode .dropdown-content,
.dark-mode .modal-content,
.dark-mode .dialog-content {
    background-color: #1a1a1a !important;
    color: #ffffff !important;
}

.dark-mode .dropdown-content *,
.dark-mode .modal-content * {
    color: #ffffff !important;
}

/* ===============================
   FIX FOOTER TEXT
   =============================== */
.dark-mode footer,
.dark-mode .footer {
    background-color: #1a1a1a !important;
    color: #ffffff !important;
}

.dark-mode footer *,
.dark-mode .footer * {
    color: #cccccc !important;
}

/* ===============================
   FIX LIST ITEMS
   =============================== */
.dark-mode ul,
.dark-mode ol,
.dark-mode li {
    color: #ffffff !important;
}

.dark-mode ul *,
.dark-mode ol *,
.dark-mode li * {
    color: #ffffff !important;
}

/* ===============================
   SPECIAL CASES - ICONS, SVGs
   =============================== */
/* Keep icons visible but not pure white */
.dark-mode svg:not([fill="none"]) {
    fill: #cccccc !important;
}

.dark-mode .icon {
    color: #cccccc !important;
}

/* ===============================
   SCROLLBAR
   =============================== */
.dark-mode ::-webkit-scrollbar {
    width: 10px;
}

.dark-mode ::-webkit-scrollbar-track {
    background: #1a1a1a;
}

.dark-mode ::-webkit-scrollbar-thumb {
    background: #333;
}

/* ===============================
   UTILITY OVERRIDES - LAST RESORT
   =============================== */
/* Nuclear option for any remaining invisible text */
.dark-mode *:not(svg):not(path):not(g):not(rect):not(circle):not(line):not(polygon):not(polyline) {
    color: #ffffff !important;
}

/* Fix for specific frameworks */
.dark-mode .text-body,
.dark-mode .text-default,
.dark-mode .text-normal {
    color: #ffffff !important;
}
</style>
   
   
   <!-- Header Navigation -->
      <header class="fixed top-0 left-0 lg:left-64 right-0 bg-blue-900 text-white h-16 flex items-center justify-between px-4 shadow-lg z-50 mb-6">
  <div class="flex items-center space-x-3">

    
  </div>
<!-- Right Info -->
<div class="flex items-center space-x-2">


  <!-- Add / Plus -->
  <a href="./add_product.php"><button class="flex items-center justify-center w-10 h-10 bg-blue-600 hover:bg-blue-700 rounded transition">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
  </button></a>

  <!-- Calculator -->
  <button id="calculatorBtn" class="flex items-center justify-center w-10 h-10 bg-blue-600 hover:bg-blue-700 rounded transition" title="Calculator">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h8M8 12h8M8 18h8M6 6v12h12V6H6z"/>
</svg>
  </button>

  <!-- POS -->
 <a href="./list_product.php"> <button class="flex items-center justify-center w-10 h-10 bg-blue-600 hover:bg-blue-700 rounded transition font-semibold text-white">
    POS
  </button></a>

  <!-- Notification -->
  <button class="flex items-center justify-center w-10 h-10 bg-blue-600 hover:bg-blue-700 rounded transition">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14V11a6 6 0 00-12 0v3c0 .538-.214 1.055-.595 1.595L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
    </svg>
  </button>

  <!-- User -->
  <a href="./report.php"><button class="flex items-center justify-center w-auto px-3 h-10 bg-blue-600 hover:bg-blue-700 rounded transition text-white space-x-2">
    <span>Club Reports</span>
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9 9 0 1118.878 6.196 9 9 0 015.12 17.804z"/>
    </svg>
  </button></a>
  <!-- Date & Time -->
<div id="current-time" 
     class="flex items-center justify-center w-48 h-10 bg-blue-600 text-white rounded text-sm font-medium">
  <!-- JS will insert date/time here -->
</div>


<button id="darkModeToggle" 
    class="flex items-center justify-center w-auto px-3 h-10 bg-gray-800 hover:bg-gray-900 rounded transition text-white space-x-2">
    
    <span id="dmText">Dark Mode</span>

    <svg id="dmIcon" xmlns="http://www.w3.org/2000/svg" 
         class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path id="dmPath" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 3v1m0 16v1m8.66-9h-1M4.34 12h-1m15.02 6.36l-.7-.7M6.34 6.34l-.7-.7m12.02 0l-.7.7M6.34 17.66l-.7.7M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
    </svg>
</button>


<script>
  function updateTime() {
      const now = new Date();

      // Format: MM/DD/YYYY HH:MM:SS
      const formatted = now.toLocaleDateString('en-US') + ' ' + now.toLocaleTimeString('en-US');

      document.getElementById('current-time').textContent = formatted;
  }

  // Initial call
  updateTime();

  // Update every second
  setInterval(updateTime, 1000);
</script>

</div>
      </header>
<script>
    // Calculator functionality
document.addEventListener('DOMContentLoaded', function() {
    // Create calculator HTML
    const calculatorHTML = `
        <div id="calculatorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-lg w-80 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-4">
                    <div class="flex justify-between items-center text-white">
                        <h3 class="font-bold">Calculator</h3>
                        <button id="closeCalculator" class="text-white hover:text-gray-200 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="bg-blue-800 p-3 rounded-lg mt-2">
                        <input type="text" id="calcDisplay" readonly 
                               class="w-full bg-transparent text-white text-right text-2xl font-mono outline-none" 
                               value="0">
                    </div>
                </div>
                <div class="p-4 bg-gray-50">
                    <div class="grid grid-cols-4 gap-3">
                        <button class="calc-btn bg-red-500 hover:bg-red-600 text-white p-3 rounded-lg font-bold" data-action="clear">C</button>
                        <button class="calc-btn bg-red-400 hover:bg-red-500 text-white p-3 rounded-lg font-bold" data-action="clearEntry">CE</button>
                        <button class="calc-btn bg-gray-300 hover:bg-gray-400 p-3 rounded-lg font-bold" data-action="backspace">⌫</button>
                        <button class="calc-btn bg-orange-500 hover:bg-orange-600 text-white p-3 rounded-lg font-bold" data-action="/">÷</button>
                        
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="7">7</button>
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="8">8</button>
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="9">9</button>
                        <button class="calc-btn bg-orange-500 hover:bg-orange-600 text-white p-3 rounded-lg font-bold" data-action="*">×</button>
                        
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="4">4</button>
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="5">5</button>
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="6">6</button>
                        <button class="calc-btn bg-orange-500 hover:bg-orange-600 text-white p-3 rounded-lg font-bold" data-action="-">−</button>
                        
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="1">1</button>
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="2">2</button>
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-number="3">3</button>
                        <button class="calc-btn bg-orange-500 hover:bg-orange-600 text-white p-3 rounded-lg font-bold" data-action="+">+</button>
                        
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium col-span-2" data-number="0">0</button>
                        <button class="calc-btn bg-gray-200 hover:bg-gray-300 p-3 rounded-lg font-medium" data-action=".">.</button>
                        <button class="calc-btn bg-green-500 hover:bg-green-600 text-white p-3 rounded-lg font-bold" data-action="=">=</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Add calculator to page
    document.body.insertAdjacentHTML('beforeend', calculatorHTML);

    // Calculator state
    let currentValue = '0';
    let previousValue = '';
    let operator = null;
    let shouldResetScreen = false;

    // Get elements
    const calculatorBtn = document.getElementById('calculatorBtn');
    const calculatorModal = document.getElementById('calculatorModal');
    const closeCalculator = document.getElementById('closeCalculator');
    const calcDisplay = document.getElementById('calcDisplay');
    const calcButtons = document.querySelectorAll('.calc-btn');

    // Calculator functions
    function updateDisplay() {
        calcDisplay.value = currentValue;
    }

    function resetCalculator() {
        currentValue = '0';
        previousValue = '';
        operator = null;
        shouldResetScreen = false;
    }

    function clearEntry() {
        currentValue = '0';
    }

    function deleteLast() {
        if (currentValue.length === 1) {
            currentValue = '0';
        } else {
            currentValue = currentValue.slice(0, -1);
        }
    }

    function appendNumber(number) {
        if (currentValue === '0' || shouldResetScreen) {
            currentValue = number;
            shouldResetScreen = false;
        } else {
            currentValue += number;
        }
    }

    function addDecimal() {
        if (shouldResetScreen) {
            currentValue = '0.';
            shouldResetScreen = false;
            return;
        }
        
        if (!currentValue.includes('.')) {
            currentValue += '.';
        }
    }

    function setOperation(newOperator) {
        if (operator !== null) {
            calculate();
        }
        previousValue = currentValue;
        operator = newOperator;
        shouldResetScreen = true;
    }

    function calculate() {
        if (operator === null || shouldResetScreen) return;
        
        const prev = parseFloat(previousValue);
        const current = parseFloat(currentValue);
        
        if (isNaN(prev) || isNaN(current)) return;
        
        let result;
        switch (operator) {
            case '+':
                result = prev + current;
                break;
            case '-':
                result = prev - current;
                break;
            case '*':
                result = prev * current;
                break;
            case '/':
                result = prev / current;
                break;
            default:
                return;
        }
        
        currentValue = result.toString();
        operator = null;
        previousValue = '';
    }

    // Event listeners for calculator buttons
    calcButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (button.dataset.number) {
                appendNumber(button.dataset.number);
                updateDisplay();
            } else if (button.dataset.action) {
                const action = button.dataset.action;
                
                switch (action) {
                    case 'clear':
                        resetCalculator();
                        break;
                    case 'clearEntry':
                        clearEntry();
                        break;
                    case 'backspace':
                        deleteLast();
                        break;
                    case '=':
                        calculate();
                        break;
                    case '.':
                        addDecimal();
                        break;
                    case '+':
                    case '-':
                    case '*':
                    case '/':
                        setOperation(action);
                        break;
                }
                updateDisplay();
            }
        });
    });

    // Modal controls
    calculatorBtn.addEventListener('click', () => {
        calculatorModal.classList.remove('hidden');
    });

    closeCalculator.addEventListener('click', () => {
        calculatorModal.classList.add('hidden');
        resetCalculator();
        updateDisplay();
    });

    calculatorModal.addEventListener('click', (e) => {
        if (e.target === calculatorModal) {
            calculatorModal.classList.add('hidden');
            resetCalculator();
            updateDisplay();
        }
    });

    // Keyboard support
    document.addEventListener('keydown', (e) => {
        if (calculatorModal.classList.contains('hidden')) return;

        if (e.key >= '0' && e.key <= '9') {
            appendNumber(e.key);
            updateDisplay();
        } else if (e.key === '.') {
            addDecimal();
            updateDisplay();
        } else if (e.key === '+' || e.key === '-' || e.key === '*' || e.key === '/') {
            setOperation(e.key);
        } else if (e.key === 'Enter' || e.key === '=') {
            calculate();
            updateDisplay();
        } else if (e.key === 'Escape') {
            resetCalculator();
            updateDisplay();
        } else if (e.key === 'Backspace') {
            deleteLast();
            updateDisplay();
        }
    });

    // Initialize
    updateDisplay();
});
const toggleBtn = document.getElementById("darkModeToggle");
const dmText = document.getElementById("dmText");
const dmIcon = document.getElementById("dmIcon");
const dmPath = document.getElementById("dmPath");

// Load saved mode
if (localStorage.getItem("darkMode") === "enabled") {
    document.body.classList.add("dark-mode");
    dmText.textContent = "Light Mode";

    dmPath.setAttribute("d",
        "M21 12.79A9 9 0 1111.21 3a7 7 0 1010.58 9.79z"
    );
}

toggleBtn.addEventListener("click", function () {
    document.body.classList.toggle("dark-mode");

    if (document.body.classList.contains("dark-mode")) {
        localStorage.setItem("darkMode", "enabled");
        dmText.textContent = "Light Mode";
        dmPath.setAttribute("d",
            "M21 12.79A9 9 0 1111.21 3a7 7 0 1010.58 9.79z"
        );
    } else {
        localStorage.setItem("darkMode", "disabled");
        dmText.textContent = "Dark Mode";
        dmPath.setAttribute("d",
            "M12 3v1m0 16v1m8.66-9h-1M4.34 12h-1m15.02 6.36l-.7-.7M6.34 6.34l-.7-.7m12.02 0l-.7.7M6.34 17.66l-.7.7M16 12a4 4 0 11-8 0 4 4 0 018 0z"
        );
    }
});

</script>