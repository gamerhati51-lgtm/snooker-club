<style>
/* ============================================
   GLOBAL DARK MODE - OPTIMIZED & CONSOLIDATED
   ============================================ */
.dark-mode {
    background-color: #121212 !important;
    color: #e0e0e0 !important;
}

/* Force all elements to respect dark mode - with exceptions for SVG elements */
.dark-mode *:not(svg):not(path):not(g):not(rect):not(circle):not(line):not(polygon):not(polyline) {
    color: #e0e0e0 !important;
}

/* ===============================
   BACKGROUND OVERRIDES - ALL LIGHT BACKGROUNDS
   =============================== */
.dark-mode .bg-white,
.dark-mode .bg-gray-50,
.dark-mode .bg-gray-100,
.dark-mode .bg-gray-200,
.dark-mode .bg-gray-300,
.dark-mode .bg-gray-400,
.dark-mode .bg-gray-500,
.dark-mode .bg-gray-600,
.dark-mode .bg-gray-700,
.dark-mode .bg-gray-800,
.dark-mode .bg-gray-900,
.dark-mode .bg-slate-50,
.dark-mode .bg-slate-100,
.dark-mode .bg-slate-200,
.dark-mode .bg-slate-300,
.dark-mode .bg-slate-400,
.dark-mode .bg-slate-500,
.dark-mode .bg-slate-600,
.dark-mode .bg-slate-700,
.dark-mode .bg-slate-800,
.dark-mode .bg-slate-900,
.dark-mode .bg-zinc-50,
.dark-mode .bg-zinc-100,
.dark-mode .bg-zinc-200,
.dark-mode .bg-zinc-300,
.dark-mode .bg-zinc-400,
.dark-mode .bg-zinc-500,
.dark-mode .bg-zinc-600,
.dark-mode .bg-zinc-700,
.dark-mode .bg-zinc-800,
.dark-mode .bg-zinc-900,
.dark-mode .bg-neutral-50,
.dark-mode .bg-neutral-100,
.dark-mode .bg-neutral-200,
.dark-mode .bg-neutral-300,
.dark-mode .bg-neutral-400,
.dark-mode .bg-neutral-500,
.dark-mode .bg-neutral-600,
.dark-mode .bg-neutral-700,
.dark-mode .bg-neutral-800,
.dark-mode .bg-neutral-900 {
    background-color: #1a1a1a !important;
}

/* Colored light backgrounds */
.dark-mode .bg-blue-50,
.dark-mode .bg-red-50,
.dark-mode .bg-green-50,
.dark-mode .bg-yellow-50,
.dark-mode .bg-purple-50,
.dark-mode .bg-pink-50,
.dark-mode .bg-indigo-50,
.dark-mode .bg-teal-50,
.dark-mode .bg-orange-50,
.dark-mode .bg-cyan-50,
.dark-mode .bg-red-100,
.dark-mode .bg-green-100,
.dark-mode .bg-blue-100,
.dark-mode .bg-yellow-100,
.dark-mode .bg-purple-100,
.dark-mode .bg-pink-100,
.dark-mode .bg-indigo-100,
.dark-mode .bg-teal-100,
.dark-mode .bg-orange-100,
.dark-mode .bg-cyan-100 {
    background-color: #252525 !important;
}

/* ===============================
   TEXT COLOR OVERRIDES - COMPREHENSIVE
   =============================== */
.dark-mode .text-gray-50,
.dark-mode .text-gray-100,
.dark-mode .text-gray-200,
.dark-mode .text-gray-300,
.dark-mode .text-gray-400,
.dark-mode .text-gray-500,
.dark-mode .text-gray-600,
.dark-mode .text-gray-700,
.dark-mode .text-gray-800,
.dark-mode .text-gray-900,
.dark-mode .text-slate-50,
.dark-mode .text-slate-100,
.dark-mode .text-slate-200,
.dark-mode .text-slate-300,
.dark-mode .text-slate-400,
.dark-mode .text-slate-500,
.dark-mode .text-slate-600,
.dark-mode .text-slate-700,
.dark-mode .text-slate-800,
.dark-mode .text-slate-900,
.dark-mode .text-zinc-50,
.dark-mode .text-zinc-100,
.dark-mode .text-zinc-200,
.dark-mode .text-zinc-300,
.dark-mode .text-zinc-400,
.dark-mode .text-zinc-500,
.dark-mode .text-zinc-600,
.dark-mode .text-zinc-700,
.dark-mode .text-zinc-800,
.dark-mode .text-zinc-900,
.dark-mode .text-neutral-50,
.dark-mode .text-neutral-100,
.dark-mode .text-neutral-200,
.dark-mode .text-neutral-300,
.dark-mode .text-neutral-400,
.dark-mode .text-neutral-500,
.dark-mode .text-neutral-600,
.dark-mode .text-neutral-700,
.dark-mode .text-neutral-800,
.dark-mode .text-neutral-900,
.dark-mode .text-black,
.dark-mode .text-muted,
.dark-mode .text-secondary,
.dark-mode .text-light,
.dark-mode .text-dark,
.dark-mode .text-body,
.dark-mode .text-default,
.dark-mode .text-normal {
    color: #e0e0e0 !important;
}

/* Status badge text colors */
.dark-mode .text-red-800,
.dark-mode .text-green-800,
.dark-mode .text-blue-800,
.dark-mode .text-yellow-800,
.dark-mode .text-purple-800,
.dark-mode .text-pink-800,
.dark-mode .text-indigo-800,
.dark-mode .text-teal-800,
.dark-mode .text-orange-800,
.dark-mode .text-cyan-800 {
    color: #e0e0e0 !important;
}

/* ===============================
   HEADINGS AND TITLES
   =============================== */
.dark-mode h1,
.dark-mode h2,
.dark-mode h3,
.dark-mode h4,
.dark-mode h5,
.dark-mode h6 {
    color: #ffffff !important;
}

.dark-mode .text-xl,
.dark-mode .text-2xl,
.dark-mode .text-3xl,
.dark-mode .text-4xl,
.dark-mode .text-5xl,
.dark-mode .text-6xl {
    color: #ffffff !important;
}

/* ===============================
   LAYOUT COMPONENTS
   =============================== */
.dark-mode header,
.dark-mode nav,
.dark-mode .navbar,
.dark-mode .top-header {
    background-color: #1a1a1a !important;
    border-bottom-color: #333 !important;
}

.dark-mode header *,
.dark-mode nav *,
.dark-mode .navbar * {
    color: #ffffff !important;
}

.dark-mode .sidebar,
.dark-mode .main-content {
    background-color: #121212 !important;
}

/* ===============================
   CARDS, BOXES AND CONTAINERS
   =============================== */
.dark-mode .card,
.dark-mode .stat-card,
.dark-mode .bg-white,
.dark-mode .bg-gray-50,
.dark-mode .bg-gray-100,
.dark-mode .bg-gray-200,
.dark-mode .rounded-lg,
.dark-mode .shadow-md,
.dark-mode .shadow-lg,
.dark-mode .shadow-xl,
.dark-mode .p-4,
.dark-mode .p-6,
.dark-mode .box,
.dark-mode .panel {
    background-color: #1a1a1a !important;
    border-color: #333 !important;
    color: #ffffff !important;
}

.dark-mode .chart-container {
    background-color: #1e1e1e !important;
}

.dark-mode .border-income-blue,
.dark-mode .border-pos-teal,
.dark-mode .border-expense-red,
.dark-mode .border-profit-green,
.dark-mode .border-booking-purple,
.dark-mode .border-inventory-orange {
    border-color: #444 !important;
}

/* ===============================
   TABLES
   =============================== */
.dark-mode table {
    background-color: #1a1a1a !important;
}

.dark-mode thead tr,
.dark-mode tbody tr {
    background-color: #1a1a1a !important;
}

.dark-mode th {
    background-color: #252525 !important;
    border-color: #333 !important;
}

.dark-mode td {
    border-color: #333 !important;
    background-color: transparent !important;
}

.dark-mode tr:nth-child(even) {
    background-color: #1f1f1f !important;
}

.dark-mode .table-row:hover {
    background-color: #2a2a2a !important;
}

/* ===============================
   BUTTONS AND INTERACTIVE ELEMENTS
   =============================== */
.dark-mode button,
.dark-mode .btn,
.dark-mode input[type="submit"],
.dark-mode input[type="button"],
.dark-mode .button {
    background-color: #2d3748 !important;
    color: #e0e0e0 !important;
    border-color: #4a5568 !important;
}

.dark-mode button:hover,
.dark-mode .btn:hover {
    background-color: #374151 !important;
}

/* Specific button colors */
.dark-mode .bg-snooker-green {
    background-color: #0d2c26 !important;
}

.dark-mode .bg-snooker-light {
    background-color: #1a3d35 !important;
}

.dark-mode .bg-snooker-accent {
    background-color: #ffb703 !important;
    color: #000000 !important;
}

/* Sidebar buttons */
.dark-mode .sidebar-link,
.dark-mode #toggleExpanse,
.dark-mode button[onclick*="toggleBookings"],
.dark-mode button[onclick*="toggle"] {
    background-color: transparent !important;
    color: #e0e0e0 !important;
    border-color: transparent !important;
}

.dark-mode .sidebar-link:hover,
.dark-mode #toggleExpanse:hover,
.dark-mode button[onclick*="toggleBookings"]:hover,
.dark-mode button[onclick*="toggle"]:hover {
    background-color: #2a2a2a !important;
    color: #ffffff !important;
}

/* Logout button specific */
.dark-mode .border-snooker-light a[href="logout.php"] {
    background-color: #0d2c26 !important;
    color: #ffffff !important;
}

.dark-mode .border-snooker-light a[href="logout.php"]:hover {
    background-color: #1a3d35 !important;
}

/* ===============================
   FORMS AND INPUTS
   =============================== */
.dark-mode input:not([type="file"]),
.dark-mode select,
.dark-mode textarea {
    background-color: #1a1a1a !important;
    color: #e0e0e0 !important;
    border: 1px solid #444 !important;
}

.dark-mode input:focus,
.dark-mode select:focus,
.dark-mode textarea:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
}

.dark-mode ::placeholder {
    color: #888 !important;
}

/* File inputs */
.dark-mode input[type="file"] {
    background-color: #1a1a1a !important;
    color: #e0e0e0 !important;
    border: 1px solid #444 !important;
}

.dark-mode input[type="file"]::file-selector-button {
    background-color: #2d3748 !important;
    color: #e0e0e0 !important;
    border: 1px solid #4a5568 !important;
}

/* ===============================
   PROGRESS BARS
   =============================== */
.dark-mode .progress-bar {
    background-color: #2d2d2d !important;
}

.dark-mode .progress-fill {
    background-color: #3b82f6 !important;
}

/* ===============================
   CANVAS AND CHARTS
   =============================== */
.dark-mode canvas {
    background-color: #1e1e1e !important;
}

/* ===============================
   FINANCIAL METRICS SPECIFIC COLORS
   =============================== */
.dark-mode .text-income-blue {
    color: #60a5fa !important;
}

.dark-mode .text-pos-teal {
    color: #5eead4 !important;
}

.dark-mode .text-expense-red {
    color: #f87171 !important;
}

.dark-mode .text-profit-green {
    color: #34d399 !important;
}

.dark-mode .text-inventory-orange {
    color: #fb923c !important;
}

.dark-mode .text-booking-purple {
    color: #c4b5fd !important;
}

/* ===============================
   ICONS AND SVGs
   =============================== */
.dark-mode svg:not([fill="none"]):not([fill^="url"]) {
    fill: #cccccc !important;
}

.dark-mode .fas,
.dark-mode .far,
.dark-mode .fab,
.dark-mode .icon {
    color: #cccccc !important;
}

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

/* ===============================
   SCROLLBAR STYLING
   =============================== */
.dark-mode ::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.dark-mode ::-webkit-scrollbar-track {
    background: #1a1a1a;
}

.dark-mode ::-webkit-scrollbar-thumb {
    background: #333;
    border-radius: 5px;
}

.dark-mode ::-webkit-scrollbar-thumb:hover {
    background: #444;
}

/* ===============================
   SHADOWS AND BORDERS
   =============================== */
.dark-mode .shadow-md,
.dark-mode .shadow-lg,
.dark-mode .shadow-xl {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5), 0 2px 4px -1px rgba(0, 0, 0, 0.4) !important;
}

.dark-mode .border-gray-200,
.dark-mode .border-gray-300,
.dark-mode .border-gray-400 {
    border-color: #333 !important;
}

/* ===============================
   INLINE STYLE OVERRIDES
   =============================== */
.dark-mode [style*="color:"]:not([style*="color: transparent"]):not([style*="color: inherit"]) {
    color: #e0e0e0 !important;
}

.dark-mode [style*="background-color:"]:not([style*="background-color: transparent"]):not([style*="background-color: inherit"]) {
    background-color: #1a1a1a !important;
}

/* ===============================
   OTHER UI COMPONENTS
   =============================== */
.dark-mode .dropdown-content,
.dark-mode .modal-content,
.dark-mode .dialog-content {
    background-color: #1a1a1a !important;
    color: #ffffff !important;
}

.dark-mode .badge,
.dark-mode .tag {
    background-color: #2d2d2d !important;
    color: #ffffff !important;
}

.dark-mode footer,
.dark-mode .footer {
    background-color: #1a1a1a !important;
    color: #ffffff !important;
}

.dark-mode ul,
.dark-mode ol,
.dark-mode li {
    color: #ffffff !important;
}

/* ===============================
   RESPONSIVE OVERRIDES
   =============================== */
@media (max-width: 768px) {
    .dark-mode .stat-card {
        background-color: #1e1e1e !important;
    }
    
    .dark-mode table {
        background-color: #1a1a1a !important;
    }
}

/* ===============================
   PRINT STYLES
   =============================== */
@media print {
    .dark-mode {
        background-color: white !important;
        color: black !important;
    }
    
    .dark-mode * {
        color: black !important;
        background-color: white !important;
    }
}
</style>
<style>
/* ============================================
   GLOBAL DARK MODE - OPTIMIZED & CONSOLIDATED
   ============================================ */
.dark-mode {
    background-color: #121212 !important;
    color: #e0e0e0 !important;
}

/* Force all elements to respect dark mode - with exceptions for SVG elements */
.dark-mode *:not(svg):not(path):not(g):not(rect):not(circle):not(line):not(polygon):not(polyline) {
    color: #e0e0e0 !important;
}

/* ===============================
   BACKGROUND OVERRIDES - ALL LIGHT BACKGROUNDS
   =============================== */
.dark-mode .bg-white,
.dark-mode .bg-gray-50,
.dark-mode .bg-gray-100,
.dark-mode .bg-gray-200,
.dark-mode .bg-gray-300,
.dark-mode .bg-gray-400,
.dark-mode .bg-gray-500,
.dark-mode .bg-gray-600,
.dark-mode .bg-gray-700,
.dark-mode .bg-gray-800,
.dark-mode .bg-gray-900,
.dark-mode .bg-slate-50,
.dark-mode .bg-slate-100,
.dark-mode .bg-slate-200,
.dark-mode .bg-slate-300,
.dark-mode .bg-slate-400,
.dark-mode .bg-slate-500,
.dark-mode .bg-slate-600,
.dark-mode .bg-slate-700,
.dark-mode .bg-slate-800,
.dark-mode .bg-slate-900,
.dark-mode .bg-zinc-50,
.dark-mode .bg-zinc-100,
.dark-mode .bg-zinc-200,
.dark-mode .bg-zinc-300,
.dark-mode .bg-zinc-400,
.dark-mode .bg-zinc-500,
.dark-mode .bg-zinc-600,
.dark-mode .bg-zinc-700,
.dark-mode .bg-zinc-800,
.dark-mode .bg-zinc-900,
.dark-mode .bg-neutral-50,
.dark-mode .bg-neutral-100,
.dark-mode .bg-neutral-200,
.dark-mode .bg-neutral-300,
.dark-mode .bg-neutral-400,
.dark-mode .bg-neutral-500,
.dark-mode .bg-neutral-600,
.dark-mode .bg-neutral-700,
.dark-mode .bg-neutral-800,
.dark-mode .bg-neutral-900 {
    background-color: #1a1a1a !important;
}

/* Colored light backgrounds */
.dark-mode .bg-blue-50,
.dark-mode .bg-red-50,
.dark-mode .bg-green-50,
.dark-mode .bg-yellow-50,
.dark-mode .bg-purple-50,
.dark-mode .bg-pink-50,
.dark-mode .bg-indigo-50,
.dark-mode .bg-teal-50,
.dark-mode .bg-orange-50,
.dark-mode .bg-cyan-50,
.dark-mode .bg-red-100,
.dark-mode .bg-green-100,
.dark-mode .bg-blue-100,
.dark-mode .bg-yellow-100,
.dark-mode .bg-purple-100,
.dark-mode .bg-pink-100,
.dark-mode .bg-indigo-100,
.dark-mode .bg-teal-100,
.dark-mode .bg-orange-100,
.dark-mode .bg-cyan-100 {
    background-color: #252525 !important;
}

/* ===============================
   TEXT COLOR OVERRIDES - COMPREHENSIVE
   =============================== */
.dark-mode .text-gray-50,
.dark-mode .text-gray-100,
.dark-mode .text-gray-200,
.dark-mode .text-gray-300,
.dark-mode .text-gray-400,
.dark-mode .text-gray-500,
.dark-mode .text-gray-600,
.dark-mode .text-gray-700,
.dark-mode .text-gray-800,
.dark-mode .text-gray-900,
.dark-mode .text-slate-50,
.dark-mode .text-slate-100,
.dark-mode .text-slate-200,
.dark-mode .text-slate-300,
.dark-mode .text-slate-400,
.dark-mode .text-slate-500,
.dark-mode .text-slate-600,
.dark-mode .text-slate-700,
.dark-mode .text-slate-800,
.dark-mode .text-slate-900,
.dark-mode .text-zinc-50,
.dark-mode .text-zinc-100,
.dark-mode .text-zinc-200,
.dark-mode .text-zinc-300,
.dark-mode .text-zinc-400,
.dark-mode .text-zinc-500,
.dark-mode .text-zinc-600,
.dark-mode .text-zinc-700,
.dark-mode .text-zinc-800,
.dark-mode .text-zinc-900,
.dark-mode .text-neutral-50,
.dark-mode .text-neutral-100,
.dark-mode .text-neutral-200,
.dark-mode .text-neutral-300,
.dark-mode .text-neutral-400,
.dark-mode .text-neutral-500,
.dark-mode .text-neutral-600,
.dark-mode .text-neutral-700,
.dark-mode .text-neutral-800,
.dark-mode .text-neutral-900,
.dark-mode .text-black,
.dark-mode .text-muted,
.dark-mode .text-secondary,
.dark-mode .text-light,
.dark-mode .text-dark,
.dark-mode .text-body,
.dark-mode .text-default,
.dark-mode .text-normal {
    color: #e0e0e0 !important;
}

/* Status badge text colors */
.dark-mode .text-red-800,
.dark-mode .text-green-800,
.dark-mode .text-blue-800,
.dark-mode .text-yellow-800,
.dark-mode .text-purple-800,
.dark-mode .text-pink-800,
.dark-mode .text-indigo-800,
.dark-mode .text-teal-800,
.dark-mode .text-orange-800,
.dark-mode .text-cyan-800 {
    color: #e0e0e0 !important;
}

/* ===============================
   HEADINGS AND TITLES
   =============================== */
.dark-mode h1,
.dark-mode h2,
.dark-mode h3,
.dark-mode h4,
.dark-mode h5,
.dark-mode h6 {
    color: #ffffff !important;
}

.dark-mode .text-xl,
.dark-mode .text-2xl,
.dark-mode .text-3xl,
.dark-mode .text-4xl,
.dark-mode .text-5xl,
.dark-mode .text-6xl {
    color: #ffffff !important;
}

/* ===============================
   LAYOUT COMPONENTS
   =============================== */
.dark-mode header,
.dark-mode nav,
.dark-mode .navbar,
.dark-mode .top-header {
    background-color: #1a1a1a !important;
    border-bottom-color: #333 !important;
}

.dark-mode header *,
.dark-mode nav *,
.dark-mode .navbar * {
    color: #ffffff !important;
}

.dark-mode .sidebar,
.dark-mode .main-content {
    background-color: #121212 !important;
}

/* ===============================
   CARDS, BOXES AND CONTAINERS
   =============================== */
.dark-mode .card,
.dark-mode .stat-card,
.dark-mode .bg-white,
.dark-mode .bg-gray-50,
.dark-mode .bg-gray-100,
.dark-mode .bg-gray-200,
.dark-mode .rounded-lg,
.dark-mode .shadow-md,
.dark-mode .shadow-lg,
.dark-mode .shadow-xl,
.dark-mode .p-4,
.dark-mode .p-6,
.dark-mode .box,
.dark-mode .panel {
    background-color: #1a1a1a !important;
    border-color: #333 !important;
    color: #ffffff !important;
}

.dark-mode .chart-container {
    background-color: #1e1e1e !important;
}

.dark-mode .border-income-blue,
.dark-mode .border-pos-teal,
.dark-mode .border-expense-red,
.dark-mode .border-profit-green,
.dark-mode .border-booking-purple,
.dark-mode .border-inventory-orange {
    border-color: #444 !important;
}

/* ===============================
   TABLES
   =============================== */
.dark-mode table {
    background-color: #1a1a1a !important;
}

.dark-mode thead tr,
.dark-mode tbody tr {
    background-color: #1a1a1a !important;
}

.dark-mode th {
    background-color: #252525 !important;
    border-color: #333 !important;
}

.dark-mode td {
    border-color: #333 !important;
    background-color: transparent !important;
}

.dark-mode tr:nth-child(even) {
    background-color: #1f1f1f !important;
}

.dark-mode .table-row:hover {
    background-color: #2a2a2a !important;
}

/* ===============================
   BUTTONS AND INTERACTIVE ELEMENTS
   =============================== */
.dark-mode button,
.dark-mode .btn,
.dark-mode input[type="submit"],
.dark-mode input[type="button"],
.dark-mode .button {
    background-color: #2d3748 !important;
    color: #e0e0e0 !important;
    border-color: #4a5568 !important;
}

.dark-mode button:hover,
.dark-mode .btn:hover {
    background-color: #374151 !important;
}

/* Specific button colors */
.dark-mode .bg-snooker-green {
    background-color: #0d2c26 !important;
}

.dark-mode .bg-snooker-light {
    background-color: #1a3d35 !important;
}

.dark-mode .bg-snooker-accent {
    background-color: #ffb703 !important;
    color: #000000 !important;
}

/* Sidebar buttons */
.dark-mode .sidebar-link,
.dark-mode #toggleExpanse,
.dark-mode button[onclick*="toggleBookings"],
.dark-mode button[onclick*="toggle"] {
    background-color: transparent !important;
    color: #e0e0e0 !important;
    border-color: transparent !important;
}

.dark-mode .sidebar-link:hover,
.dark-mode #toggleExpanse:hover,
.dark-mode button[onclick*="toggleBookings"]:hover,
.dark-mode button[onclick*="toggle"]:hover {
    background-color: #2a2a2a !important;
    color: #ffffff !important;
}

/* Logout button specific */
.dark-mode .border-snooker-light a[href="logout.php"] {
    background-color: #0d2c26 !important;
    color: #ffffff !important;
}

.dark-mode .border-snooker-light a[href="logout.php"]:hover {
    background-color: #1a3d35 !important;
}

/* ===============================
   FORMS AND INPUTS
   =============================== */
.dark-mode input:not([type="file"]),
.dark-mode select,
.dark-mode textarea {
    background-color: #1a1a1a !important;
    color: #e0e0e0 !important;
    border: 1px solid #444 !important;
}

.dark-mode input:focus,
.dark-mode select:focus,
.dark-mode textarea:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
}

.dark-mode ::placeholder {
    color: #888 !important;
}

/* File inputs */
.dark-mode input[type="file"] {
    background-color: #1a1a1a !important;
    color: #e0e0e0 !important;
    border: 1px solid #444 !important;
}

.dark-mode input[type="file"]::file-selector-button {
    background-color: #2d3748 !important;
    color: #e0e0e0 !important;
    border: 1px solid #4a5568 !important;
}

/* ===============================
   PROGRESS BARS
   =============================== */
.dark-mode .progress-bar {
    background-color: #2d2d2d !important;
}

.dark-mode .progress-fill {
    background-color: #3b82f6 !important;
}

/* ===============================
   CANVAS AND CHARTS
   =============================== */
.dark-mode canvas {
    background-color: #1e1e1e !important;
}

/* ===============================
   FINANCIAL METRICS SPECIFIC COLORS
   =============================== */
.dark-mode .text-income-blue {
    color: #60a5fa !important;
}

.dark-mode .text-pos-teal {
    color: #5eead4 !important;
}

.dark-mode .text-expense-red {
    color: #f87171 !important;
}

.dark-mode .text-profit-green {
    color: #34d399 !important;
}

.dark-mode .text-inventory-orange {
    color: #fb923c !important;
}

.dark-mode .text-booking-purple {
    color: #c4b5fd !important;
}

/* ===============================
   ICONS AND SVGs
   =============================== */
.dark-mode svg:not([fill="none"]):not([fill^="url"]) {
    fill: #cccccc !important;
}

.dark-mode .fas,
.dark-mode .far,
.dark-mode .fab,
.dark-mode .icon {
    color: #cccccc !important;
}

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

/* ===============================
   SCROLLBAR STYLING
   =============================== */
.dark-mode ::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.dark-mode ::-webkit-scrollbar-track {
    background: #1a1a1a;
}

.dark-mode ::-webkit-scrollbar-thumb {
    background: #333;
    border-radius: 5px;
}

.dark-mode ::-webkit-scrollbar-thumb:hover {
    background: #444;
}

/* ===============================
   SHADOWS AND BORDERS
   =============================== */
.dark-mode .shadow-md,
.dark-mode .shadow-lg,
.dark-mode .shadow-xl {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5), 0 2px 4px -1px rgba(0, 0, 0, 0.4) !important;
}

.dark-mode .border-gray-200,
.dark-mode .border-gray-300,
.dark-mode .border-gray-400 {
    border-color: #333 !important;
}

/* ===============================
   INLINE STYLE OVERRIDES
   =============================== */
.dark-mode [style*="color:"]:not([style*="color: transparent"]):not([style*="color: inherit"]) {
    color: #e0e0e0 !important;
}

.dark-mode [style*="background-color:"]:not([style*="background-color: transparent"]):not([style*="background-color: inherit"]) {
    background-color: #1a1a1a !important;
}

/* ===============================
   OTHER UI COMPONENTS
   =============================== */
.dark-mode .dropdown-content,
.dark-mode .modal-content,
.dark-mode .dialog-content {
    background-color: #1a1a1a !important;
    color: #ffffff !important;
}

.dark-mode .badge,
.dark-mode .tag {
    background-color: #2d2d2d !important;
    color: #ffffff !important;
}

.dark-mode footer,
.dark-mode .footer {
    background-color: #1a1a1a !important;
    color: #ffffff !important;
}

.dark-mode ul,
.dark-mode ol,
.dark-mode li {
    color: #ffffff !important;
}

/* ===============================
   RESPONSIVE OVERRIDES
   =============================== */
@media (max-width: 768px) {
    .dark-mode .stat-card {
        background-color: #1e1e1e !important;
    }
    
    .dark-mode table {
        background-color: #1a1a1a !important;
    }
}

/* ===============================
   PRINT STYLES
   =============================== */
@media print {
    .dark-mode {
        background-color: white !important;
        color: black !important;
    }
    
    .dark-mode * {
        color: black !important;
        background-color: white !important;
    }
}
</style>
   <!-- Header Navigation -->
      <header class="fixed top-0 left-0 lg:left-64 right-0 bg-blue-900 text-white h-16 flex items-center justify-between px-4 shadow-lg z-50 mb-6">
  <div class="flex items-center space-x-3">
    <!-- Mobile Menu Button -->
    <button id="mobileMenuButton" class="lg:hidden p-2 bg-blue-700 hover:bg-blue-800 text-white rounded-md shadow-lg">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
      </svg>
    </button>
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

// Sidebar toggle function - needs to be global
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('-translate-x-full');
        // Toggle body overflow when sidebar is open on mobile
        if (window.innerWidth < 1024) {
            document.body.classList.toggle('overflow-hidden');
        }
    }
}

// Initialize mobile menu button
document.addEventListener('DOMContentLoaded', () => {
    const mobileMenuBtn = document.getElementById('mobileMenuButton');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleSidebar);
    }
});

</script>