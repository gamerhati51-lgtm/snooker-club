<?php
// PHP Configuration and Setup

// 1. Database Connection (Replace with your actual credentials)
function getDbConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "your_db_password"; // CHANGE THIS
    $dbname = "snooker_club_pos";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        // In a full application, you would log this error and show a user-friendly message.
        return null;
    }
}

// 2. Form Submission Handling
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = getDbConnection();
    if ($conn && isset($_POST['action']) && $_POST['action'] == 'add_product') {
        
        // Sanitize and collect form data
        $name = $_POST['name'] ?? '';
        $sku = $_POST['sku'] ?? null;
        $barcode_type = $_POST['barcode_type'] ?? 'Code 128';
        $unit = $_POST['unit'] ?? 'Pieces (Pc(s))';
        $brand = $_POST['brand'] ?? null;
        $category = $_POST['category'] ?? 'Default';
        $sub_category = $_POST['sub_category'] ?? null;
        $stock_management = isset($_POST['manage_stock']) ? 1 : 0;
        $alert_quantity = $_POST['alert_quantity'] ?? 10;
        $description = $_POST['description'] ?? null;
        $not_for_selling = isset($_POST['not_for_selling']) ? 1 : 0;
        $weight = $_POST['weight'] ?? null;
        $service_time = $_POST['service_time'] ?? 0;
        $tax_id = $_POST['applicable_tax'] ?? null; // Assuming 'None' is NULL
        $selling_price_tax_type = $_POST['selling_price_tax_type'] ?? 'Inclusive';
        $product_type = $_POST['product_type'] ?? 'Single';
        $selling_price_exc = $_POST['selling_price_exc'] ?? 0;
        $selling_price_inc = $_POST['selling_price_inc'] ?? 0;
        $cost_price = $_POST['cost_price'] ?? 0; // Assuming default cost price is zero
        
        // Set stock quantity based on stock management toggle (simplified: starts at 0 if managed, or NULL if not)
        $initial_stock = $stock_management ? ($_POST['initial_stock'] ?? 0) : null;


        try {
            $stmt = $conn->prepare("INSERT INTO products 
                (name, sku, barcode_type, unit, brand, category, sub_category, stock_quantity, alert_quantity, cost_price, selling_price, selling_price_inc, selling_price_tax_type, product_type, description, is_active, is_service_product)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $name, $sku, $barcode_type, $unit, $brand, $category, $sub_category, 
                $initial_stock, $alert_quantity, $cost_price, 
                // We'll use the inclusive price as the main selling_price for simplicity in the basic POS setup
                $selling_price_inc, $selling_price_inc, $selling_price_tax_type, $product_type, 
                $description, $not_for_selling ? 0 : 1, $not_for_selling
            ]);

            $message = "Product '{$name}' added successfully! SKU: {$sku}.";

        } catch (PDOException $e) {
            // Check for specific errors, e.g., duplicate SKU
            if ($e->getCode() == '23000') {
                 $message = "Error: SKU '{$sku}' already exists. Please use a unique SKU.";
            } else {
                 $message = "A database error occurred: " . $e->getMessage();
            }
        }
    } else if (!$conn) {
        $message = "CRITICAL: Database connection failed. Cannot save product.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urban POS - Add New Product</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'urban-blue': '#1D4ED8',
                        'urban-dark': '#1F2937',
                        'urban-green': '#059669',
                        'urban-light': '#F9FAFB',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        .form-label {
            @apply block text-sm font-medium text-gray-700 mb-1 flex items-center;
        }
        .form-input, .form-select, .form-textarea {
            @apply w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-urban-blue focus:border-urban-blue transition duration-150;
        }
        .sidebar-link {
             @apply text-gray-300 hover:bg-white/10 hover:text-white px-3 py-2 rounded-lg text-sm font-medium transition duration-150 flex items-center whitespace-nowrap;
        }
        .sidebar-link.active {
            @apply bg-urban-blue/30 text-white font-bold shadow-lg;
        }
        .card-section {
            @apply bg-white p-6 rounded-xl shadow-lg border border-gray-100;
        }
        .section-header {
             @apply text-xl font-semibold text-urban-dark border-b pb-3 mb-4;
        }
    </style>
</head>
<body class="bg-urban-light min-h-screen font-sans">

    <div class="flex h-screen overflow-hidden">
        
        <!-- START: Sidebar (Mimics sidebar.php) -->
        <nav class="w-64 bg-urban-dark shadow-2xl p-4 flex flex-col justify-between flex-shrink-0">
            <div>
                <div class="text-white text-3xl font-extrabold tracking-wider p-4 border-b-2 border-urban-blue mb-6">
                    Urban POS
                </div>
                <div class="text-xs text-gray-400 mb-2 px-3">Signed in as: Urban Codez</div>

                <div class="space-y-2">
                    <a href="#" class="sidebar-link">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Dashboard
                    </a>
                    <a href="#" class="sidebar-link active">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Add Product
                    </a>
                    <a href="#" class="sidebar-link">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        Sell (POS)
                    </a>
                    <a href="#" class="sidebar-link">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                        All Products
                    </a>
                </div>
            </div>
            
            <div class="text-sm text-gray-500 p-4 border-t border-gray-700">
                Urban POS - V6.6 | Copyright © 2025
            </div>
        </nav>
        <!-- END: Sidebar -->

        <!-- Main Content Area -->
        <main class="flex-grow p-4 sm:p-8 overflow-y-auto">
            
            <!-- Header -->
            <header class="flex justify-between items-center mb-6 border-b pb-4">
                <h1 class="text-3xl font-extrabold text-urban-dark">Add New Product</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500">11/28/2025</span>
                    <button class="text-urban-blue hover:text-urban-blue/70">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1"></path></svg>
                    </button>
                </div>
            </header>

            <!-- Dynamic Message Box -->
            <?php if ($message): ?>
                <div class="<?= strpos($message, 'Error') !== false ? 'bg-red-100 text-red-700' : 'bg-urban-green/20 text-urban-green' ?> p-4 rounded-lg shadow-md mb-6 transition duration-300 ease-in-out font-medium border border-current">
                    <p><?php echo htmlspecialchars($message); ?></p>
                </div>
            <?php endif; ?>

            <!-- Form Layout -->
            <form method="POST" action="add_product_page.php" class="space-y-6">
                <input type="hidden" name="action" value="add_product">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Column 1: Core Details (2/3 width) -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- 1. General Product Information -->
                        <div class="card-section">
                            <h2 class="section-header">General Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="name" class="form-label">Product Name:<span class="text-red-500">*</span></label>
                                    <input type="text" id="name" name="name" required class="form-input">
                                </div>
                                <div>
                                    <label for="sku" class="form-label">SKU:</label>
                                    <input type="text" id="sku" name="sku" placeholder="Unique Product Code" class="form-input">
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="barcode_type" class="form-label">Barcode Type:<span class="text-red-500">*</span></label>
                                    <select id="barcode_type" name="barcode_type" class="form-select">
                                        <option value="Code 128" selected>Code 128 (C128)</option>
                                        <option value="Code 39">Code 39 (C39)</option>
                                        <option value="EAN-13">EAN-13</option>
                                        <option value="UPC-A">UPC-A</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="unit" class="form-label">Unit:<span class="text-red-500">*</span></label>
                                    <select id="unit" name="unit" required class="form-select">
                                        <option value="">Please Select</option>
                                        <option value="Pieces (Pc(s))" selected>Pieces (Pc(s))</option>
                                        <option value="Monthly">Monthly (Service)</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="brand" class="form-label">Brand:</label>
                                    <select id="brand" name="brand" class="form-select">
                                        <option value="">Please Select</option>
                                        <option value="John Guest">John Guest (Example)</option>
                                    </select>
                                </div>
                            </div>
                             <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="category" class="form-label">Category:</label>
                                    <select id="category" name="category" class="form-select">
                                        <option value="Default-0230" selected>Default-0230</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="sub_category" class="form-label">Sub category:</label>
                                    <select id="sub_category" name="sub_category" class="form-select">
                                        <option value="">Please Select</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Inventory and Description -->
                        <div class="card-section">
                            <h2 class="section-header">Inventory & Stock</h2>

                            <!-- Stock Management Toggle -->
                            <div class="flex items-center space-x-2 mb-4 p-3 bg-gray-50 rounded-lg">
                                <input type="checkbox" id="manage_stock" name="manage_stock" checked class="h-4 w-4 text-urban-blue border-gray-300 rounded focus:ring-urban-blue">
                                <label for="manage_stock" class="text-sm font-medium text-urban-dark">Enable stock management at product level</label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="stock-fields">
                                <div>
                                    <label for="initial_stock" class="form-label">Initial Stock Quantity:</label>
                                    <input type="number" min="0" id="initial_stock" name="initial_stock" value="0" class="form-input">
                                </div>
                                <div>
                                    <label for="alert_quantity" class="form-label">Alert quantity:</label>
                                    <input type="number" min="0" id="alert_quantity" name="alert_quantity" value="10" class="form-input">
                                </div>
                            </div>

                            <div class="mt-6">
                                <label for="description" class="form-label">Product Description:</label>
                                <textarea id="description" name="description" class="form-textarea h-32"></textarea>
                                <p class="text-xs text-gray-400 mt-1">Powered by TinyMCE (Placeholder)</p>
                            </div>
                            
                            <div class="flex items-center space-x-6 mt-6 pt-4 border-t">
                                <div class="flex items-center space-x-2">
                                    <input type="checkbox" id="serial_number" name="serial_number" class="h-4 w-4 text-urban-blue border-gray-300 rounded focus:ring-urban-blue">
                                    <label for="serial_number" class="text-sm font-medium text-gray-600">Enable Product description, IMEI or Serial Number</label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <input type="checkbox" id="not_for_selling" name="not_for_selling" class="h-4 w-4 text-urban-blue border-gray-300 rounded focus:ring-urban-blue">
                                    <label for="not_for_selling" class="text-sm font-medium text-gray-600">Not for selling (Internal Use)</label>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="weight" class="form-label">Weight:</label>
                                    <input type="number" step="0.01" id="weight" name="weight" placeholder="e.g., 0.5 (kg)" class="form-input">
                                </div>
                                <div>
                                    <label for="service_time" class="form-label">Service/Prep Time (In minutes):</label>
                                    <input type="number" min="0" id="service_time" name="service_time" value="0" class="form-input">
                                </div>
                            </div>
                        </div>

                        <!-- 3. Price and Tax -->
                        <div class="card-section">
                            <h2 class="section-header">Pricing & Tax Configuration</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="applicable_tax" class="form-label">Applicable Tax:</label>
                                    <select id="applicable_tax" name="applicable_tax" class="form-select">
                                        <option value="" selected>None</option>
                                        <option value="1">GST (Example)</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="selling_price_tax_type" class="form-label">Selling Price Tax Type:<span class="text-red-500">*</span></label>
                                    <select id="selling_price_tax_type" name="selling_price_tax_type" required class="form-select">
                                        <option value="Inclusive" selected>Inclusive</option>
                                        <option value="Exclusive">Exclusive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6">
                                <label class="form-label">Product Type:<span class="text-red-500">*</span></label>
                                <div class="flex space-x-4 mt-2">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="product_type" value="Single" checked class="form-radio h-4 w-4 text-urban-blue border-gray-300 focus:ring-urban-blue">
                                        <span class="ml-2 text-gray-700">Single</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="product_type" value="Variable" class="form-radio h-4 w-4 text-urban-blue border-gray-300 focus:ring-urban-blue">
                                        <span class="ml-2 text-gray-700">Variable</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="product_type" value="Combo" class="form-radio h-4 w-4 text-urban-blue border-gray-300 focus:ring-urban-blue">
                                        <span class="ml-2 text-gray-700">Combo</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Single Product Pricing (Default View) -->
                            <div id="single-pricing-fields" class="mt-4 space-y-4 p-4 border rounded-lg bg-urban-light">
                                <div class="grid grid-cols-3 gap-4 items-end">
                                    <!-- Cost Price Field added for basic inventory management -->
                                    <div>
                                        <label for="cost_price" class="form-label">Default Purchase Price (Cost):</label>
                                        <input type="number" step="0.01" min="0" id="cost_price" name="cost_price" value="0.00" class="form-input">
                                    </div>
                                    <div class="text-center">
                                        <label for="margin" class="form-label justify-center">Margin (%):</label>
                                        <input type="number" step="0.01" id="margin" name="margin" value="20.00" class="form-input text-center">
                                    </div>
                                    <div class="text-right">
                                        <label for="selling_price_inc" class="form-label justify-end">Default Selling Price Inc. tax:<span class="text-red-500">*</span></label>
                                        <input type="number" step="0.01" min="0.01" id="selling_price_inc" name="selling_price_inc" required value="0.00" class="form-input text-right bg-yellow-50">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Column 2: Media and Location (1/3 width) -->
                    <div class="lg:col-span-1 space-y-6">

                        <!-- 4. Business Location -->
                        <div class="card-section bg-urban-light">
                            <h2 class="section-header">Business Locations</h2>
                             <div class="p-2 border border-gray-300 rounded-lg bg-white">
                                 <label class="inline-flex items-center">
                                    <input type="checkbox" checked disabled class="h-4 w-4 text-urban-blue border-gray-300 rounded focus:ring-urban-blue opacity-70">
                                    <span class="ml-2 text-gray-700 font-semibold">Urban Codez (BL0001)</span>
                                </label>
                                <p class="text-xs text-gray-500 mt-1">Product will be available at this location.</p>
                            </div>
                        </div>
                        
                        <!-- 5. Product Image -->
                        <div class="card-section">
                            <h2 class="section-header">Product Image</h2>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                                <p class="text-sm text-gray-500 mb-2">Drag & Drop or Click to Upload</p>
                                <input type="file" id="product_image" name="product_image" accept="image/*" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-urban-blue/10 file:text-urban-blue hover:file:bg-urban-blue/20">
                                <p class="text-xs text-gray-400 mt-2">Max File size: 5MB. Aspect ratio should be 1:1</p>
                                <!-- Placeholder Image Preview -->
                                <div class="mt-4 w-24 h-24 mx-auto bg-gray-100 border rounded-lg flex items-center justify-center text-gray-400">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-9-6h.01M6 18h12a2 2 0 002-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                </div>
                            </div>
                        </div>

                        <!-- 6. Product Brochure -->
                        <div class="card-section">
                            <h2 class="section-header">Product Brochure</h2>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                                <p class="text-sm text-gray-500 mb-2">Upload Technical Document</p>
                                <input type="file" id="product_brochure" name="product_brochure" accept=".pdf,.csv,.zip,.doc,.docx,.jpeg,.jpg,.png" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-200 hover:file:bg-gray-300">
                                <p class="text-xs text-gray-400 mt-2">Max File size: 5MB. Allowed Files: .pdf, .csv, .zip, .doc, .docx, .jpeg, .jpg, .png</p>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer Action Bar -->
                <div class="sticky bottom-0 bg-white p-4 shadow-2xl border-t border-gray-200 flex justify-end">
                    <button type="submit" class="bg-urban-green hover:bg-urban-green/80 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition duration-150">
                        Save Product
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
        // Placeholder for TinyMCE initialization for the Product Description
        document.addEventListener('DOMContentLoaded', () => {
             tinymce.init({
                selector: '#description',
                height: 200,
                menubar: false,
                plugins: 'autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help',
                toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help'
            });

            // JavaScript to toggle initial stock fields based on 'Manage Stock' checkbox
            const manageStock = document.getElementById('manage_stock');
            const stockFields = document.getElementById('stock-fields');

            const toggleStockFields = () => {
                if (stockFields) {
                    stockFields.classList.toggle('hidden', !manageStock.checked);
                    stockFields.querySelectorAll('input').forEach(input => {
                        input.required = manageStock.checked;
                    });
                }
            };

            if (manageStock) {
                manageStock.addEventListener('change', toggleStockFields);
                toggleStockFields(); // Initial run
            }
        });
    </script>
</body>
</html>