<?php
session_start();

// --- 1. Database Connection & Tax Lookup (Simulation) ---

// NOTE: You must create a 'db.php' file containing your actual database connection:
// e.g., define('DB_SERVER', 'localhost'); // etc.
//        $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
include 'db.php'; 

// Check connection
if ($conn->connect_error) {
    // Critical error, stop execution
    die("Connection failed: " . $conn->connect_error);
}

// Function to map tax options to a numeric rate
function getTaxRate($tax_option) {
    switch ($tax_option) {
        case 'Standard Rate (20%)':
            return 0.20;
        case 'Reduced Rate (5%)':
            return 0.05;
        case 'Zero Rated (0%)':
        case '1': // Value 1 from form (for Zero Rated)
            return 0.00;
        default:
            return 0.00; // Default to 0 if unknown
    }
}

// --- 2. Configuration & Initialization ---
$message = '';
$message_type = '';
$upload_dir = 'product_images/'; 

// Create the upload directory if it doesn't exist (CRITICAL for image saving)
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        die('Failed to create upload directory: ' . $upload_dir);
    }
}

// --- 3. Form Submission Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Collect and Sanitize ALL Input Fields ---
    $sku = trim($_POST['sku'] ?? '');
    $barcode_type = trim($_POST['barcode_type'] ?? '');
    $unit = trim($_POST['unit'] ?? 'Pc');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    $cost_price = (float)($_POST['cost_price'] ?? 0);
    $selling_price_inc = (float)($_POST['selling_price_inc'] ?? 0); // Price including tax (from form)
    $applicable_tax_text = trim($_POST['applicable_tax'] ?? 'Standard Rate (20%)');
    $tax_rate = getTaxRate($applicable_tax_text); // Get the numeric tax rate

    $stock_quantity = (int)($_POST['initial_stock'] ?? 0); // Corrected variable name
    $category = trim($_POST['category'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $sub_category = trim($_POST['sub_category'] ?? '');
    $alert_quantity = (int)($_POST['alert_quantity'] ?? 0);
    
    // Defaulting to Standard/Single based on the form structure
    $product_type = trim($_POST['product_type'] ?? 'Single');
    $selling_price_tax_type = trim($_POST['selling_price_tax_type'] ?? 'Inclusive');
    
    // Checkbox values
    // Note: The form doesn't use these specific checkboxes, but this is good practice:
    $is_active = 1; 
    $is_service_product = ($unit === 'Hr') ? 1 : 0; // Assume 'Hr' unit implies a service
    
    // --- Calculate Selling Price (Tax Exclusive) ---
    // If selling price is tax inclusive (recommended for POS systems)
    if ($selling_price_tax_type === 'Inclusive' && $tax_rate > 0) {
        // selling_price = selling_price_inc / (1 + tax_rate)
        $selling_price = $selling_price_inc / (1 + $tax_rate);
    } else {
        // If Exclusive or Zero Rated: selling_price = selling_price_inc
        $selling_price = $selling_price_inc;
    }
    
    // --- Handle Image Upload ---
    $image_path = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['product_image']['tmp_name'];
        $file_name = $_FILES['product_image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $safe_file_name = uniqid('prod_', true) . '.' . $file_ext;
        $destination = $upload_dir . $safe_file_name;
        
        if (move_uploaded_file($file_tmp_name, $destination)) {
            $image_path = $destination; 
        } else {
            $message = 'Error uploading image: File movement failed.';
            $message_type = 'error';
        }
    }
    
   // --- 4. Validation and SQL INSERT ---
if (empty($name) || $selling_price_inc <= 0) {
    $message = 'Product Name and Selling Price are required.';
    $message_type = 'error';
} else if (empty($message_type)) { // Only proceed if no image error occurred

    // IMPORTANT: The `tax_id` from the database schema is likely the ID of the tax rate.
    $tax_id_placeholder = $applicable_tax_text; // Storing the name as a placeholder
    
    // ----------------------------------------------------------------------------------
    // FIX IMPLEMENTED HERE: Added ON DUPLICATE KEY UPDATE
    // If the INSERT fails due to a duplicate 'sku', the statement will switch to UPDATE
    // and update the listed fields instead of throwing a fatal error.
    // ----------------------------------------------------------------------------------
    $sql = "
        INSERT INTO products (
            sku, barcode_type, unit, name, description, cost_price, selling_price, 
            stock_quantity, category, is_active, created_at, updated_at, brand, 
            sub_category, alert_quantity, is_service_product, 
            service_time_minutes, tax_id, selling_price_tax_type, product_type, image_path
        ) 
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
        ON DUPLICATE KEY UPDATE
            barcode_type = VALUES(barcode_type),
            unit = VALUES(unit),
            name = VALUES(name),
            description = VALUES(description),
            cost_price = VALUES(cost_price),
            selling_price = VALUES(selling_price),
            stock_quantity = stock_quantity + VALUES(stock_quantity), -- IMPORTANT: Add new stock to existing
            category = VALUES(category),
            updated_at = NOW(),
            brand = VALUES(brand),
            sub_category = VALUES(sub_category),
            alert_quantity = VALUES(alert_quantity),
            is_service_product = VALUES(is_service_product),
            tax_id = VALUES(tax_id),
            selling_price_tax_type = VALUES(selling_price_tax_type),
            product_type = VALUES(product_type),
            image_path = IF(VALUES(image_path) = '', image_path, VALUES(image_path)) -- Keep old image path if no new one provided
    ";
    
    // Removed `weight` field from the SQL query as it was missing a form field and variable
    $stmt = $conn->prepare($sql);
    
    // Bind parameters: ssss sdd i s i s ii i i s s s (20 parameters)
    // NOTE: The binding remains the same, which is the beauty of this fix!
    // Line 133: (Binding)
    $stmt->bind_param("sssssddisisiiisisss", 
        $sku, $barcode_type, $unit, $name, $description, $cost_price, $selling_price, 
        $stock_quantity, $category, $is_active, $brand, $sub_category, $alert_quantity, 
        $is_service_product, $service_time_minutes, $tax_id_placeholder, 
        $selling_price_tax_type, $product_type, $image_path
    );

    // Line 135: The execute call will now UPDATE instead of causing a fatal error.
    if ($stmt->execute()) {
        $rows_affected = $conn->affected_rows;
        
        if ($rows_affected > 1) { // 2 rows affected means an update occurred (MySQL's behavior)
            $message = 'Product **' . htmlspecialchars($name) . '** updated successfully (Stock Adjusted)!';
        } else { // 1 row affected means a new product was inserted
            $message = 'Product **' . htmlspecialchars($name) . '** added successfully!';
        }
        
        $message_type = 'success';
        $_POST = array(); // Clear form data after success
    } else {
        $message = 'Database Error: ' . $stmt->error;
        $message_type = 'error';
    }

    $stmt->close();
}
// End of form submission block
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snooker POS - New Product Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        // Custom Tailwind Configuration for premium blue theme
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#1E40AF',      // Rich blue for primary actions
                        'primary-dark': '#1E3A8A', // Darker blue for hover states
                        'secondary': '#3B82F6',    // Medium blue for secondary elements
                        'accent': '#60A5FA',       // Light blue for accents
                        'light': '#DBEAFE',        // Very light blue for backgrounds
                        'success': '#10B981',      // Success green
                        'warning': '#FBBF24',      // Warning amber
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        'smooth': '0 4px 20px -2px rgba(0, 0, 0, 0.08)',
                        'input': '0 2px 8px -1px rgba(0, 0, 0, 0.04)',
                        'button': '0 4px 14px -2px rgba(30, 64, 175, 0.3)',
                    },
                    borderRadius: {
                        'card': '12px',
                        'input': '8px',
                        'button': '10px',
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom styles for enhanced appearance */
        .form-input, .form-select, .form-textarea {
            transition: all 0.2s ease-in-out;
        }
        
        /* Custom file input styling */
        .file-input {
            background: linear-gradient(to bottom right, #FFFFFF, #F8FAFC);
        }
        
        /* Smooth focus transitions */
        input:focus, select:focus, textarea:focus {
            transform: translateY(-1px);
        }
        
        /* Button hover effects */
        .btn-primary:hover, .btn-secondary:hover {
            transform: translateY(-2px);
            transition: all 0.2s ease-in-out;
        }
   </style>
</head>
<body class="bg-blue-100 font-sans">

  <!-- Dashboard Container -->
  <div class="flex min-h-screen">

    <!-- Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 ml-0 lg:ml-64 pt-20 p-8 main-content"> <!-- pt-20 = header height -->
      
      <!-- Header -->
      <?php include "layout/header.php"; ?>

      <!-- Page Content -->
      <div id="content-area" class="space-y-8 bg-blue-100 p-6 rounded-lg">

            <div id="content-area" class="space-y-8 bg-gray-100 p-6 rounded-card">


                <?php if ($message): ?>
                    <div class="<?= $message_type === 'success' ? 'bg-green-50 border-l-4 border-success text-green-800' : 'bg-red-50 border-l-4 border-red-500 text-red-800' ?> p-6 rounded-card shadow-smooth mb-8 font-medium">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 mr-3 <?= $message_type === 'success' ? 'text-success' : 'text-red-500' ?>" fill="currentColor" viewBox="0 0 20 20">
                                <?php if ($message_type === 'success'): ?>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                <?php else: ?>
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                <?php endif; ?>
                            </svg>
                            <p class="text-lg"><?php echo htmlspecialchars($message); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="add_product.php" class="space-y-8 pb-24 mt-3" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_product">
                    <input type="hidden" id="selling_price" name="selling_price" value="0.00"> <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
                        <div class="lg:col-span-2 space-y-8">
                            
                            <div class="bg-white p-8 rounded-card shadow-smooth border border-blue-50">
                                <h2 class="text-xl font-bold text-gray-800 border-b border-blue-100 pb-4 mb-6 flex items-center">
                                    <span class="bg-primary text-white w-8 h-8 rounded-lg flex items-center justify-center mr-3 text-sm">1</span>
                                    Product Identification
                                </h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="md:col-span-2">
                                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Product Name
                                            <span class="text-red-500 ml-1">*</span>
                                        </label>
                                        <input type="text" id="name" name="name" required 
                                                class="w-full p-4 border border-orange-400 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200"
                                                placeholder="e.g., Green Spot Chalk (Box)"
                                                value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label for="sku" class="block text-sm font-semibold text-gray-700 mb-2">
                                            SKU (Stock Code)
                                        </label>
                                        <input type="text" id="sku" name="sku" 
                                                class="w-full p-4 border border-orange-300 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200"
                                                placeholder="Auto / Manual Code"
                                                value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                                    <div>
                                        <label for="unit" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Unit of Measure
                                            <span class="text-red-500 ml-1">*</span>
                                        </label>
                                        <select id="unit" name="unit" required 
                                                class="w-full p-4 border border-orange-400 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,<svg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22><path%20fill%3D%22%236B7280%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%2F><%2Fsvg>')] bg-no-repeat bg-right-4 bg-center bg-[length:20px_20px] pr-12">
                                            <option value="Pc" <?php echo ($_POST['unit'] ?? 'Pc') == 'Pc' ? 'selected' : ''; ?>>Pieces (Pc(s))</option>
                                            <option value="Hr" <?php echo ($_POST['unit'] ?? '') == 'Hr' ? 'selected' : ''; ?>>Hour (Table Time)</option>
                                            <option value="Bottle" <?php echo ($_POST['unit'] ?? '') == 'Bottle' ? 'selected' : ''; ?>>Bottle / Can</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="brand" class="block text-sm font-semibold text-gray-700 mb-2">Brand</label>
                                        <select id="brand" name="brand" 
                                                class="w-full p-4 border border-orange-400 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,<svg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22><path%20fill%3D%22%236B7280%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%2F><%2Fsvg>')] bg-no-repeat bg-right-4 bg-center bg-[length:20px_20px] pr-12">
                                            <option value="">-- No Brand --</option>
                                            <option value="Triangle" <?php echo ($_POST['brand'] ?? '') == 'Triangle' ? 'selected' : ''; ?>>Triangle</option>
                                            <option value="Peradon" <?php echo ($_POST['brand'] ?? '') == 'Peradon' ? 'selected' : ''; ?>>Peradon</option>
                                        </select>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <label for="barcode_type" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Barcode Number (or ID)
                                        </label>
                                        <input type="text" id="barcode_type" name="barcode_type" 
                                                class="w-full p-4 border border-orange-400 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200"
                                                placeholder="Scan barcode or enter manually"
                                                value="<?php echo htmlspecialchars($_POST['barcode_type'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="mt-8">
                                    <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Product Description / Notes
                                    </label>
                                    <textarea id="description" name="description" 
                                                class="w-full p-4 border border-blue-400 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 h-32 resize-none"
                                                placeholder="Key features, variants, or internal notes."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="bg-white p-8 rounded-card shadow-smooth border border-orange-400">
                                <h2 class="text-xl font-bold text-gray-800 border-b border-blue-100 pb-4 mb-6 flex items-center">
                                    <span class="bg-primary text-white w-8 h-8 rounded-lg flex items-center justify-center mr-3 text-sm">2</span>
                                    Pricing & Financials
                                </h2>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                                    <div>
                                        <label for="cost_price" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Cost Price (Exc. Tax)
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 font-mono">£</span>
                                            <input type="number" step="0.01" min="0" id="cost_price" name="cost_price" value="<?php echo htmlspecialchars($_POST['cost_price'] ?? '10.00'); ?>" 
                                                    class="calc-field w-full p-4 pl-10 border border-orange-400 rounded-input bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 font-mono text-lg text-right">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="margin" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Desired Margin (%)
                                        </label>
                                        <div class="relative">
                                            <span class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 font-mono">%</span>
                                            <input type="number" step="0.01" id="margin" name="margin" value="<?php echo htmlspecialchars($_POST['margin'] ?? '33.33'); ?>" 
                                                    class="calc-field w-full p-4 pr-10 border border-orange-400 rounded-input bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 font-mono text-lg text-right">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="selling_price_inc" class="block text-sm font-semibold text-primary mb-2">
                                            Selling Price (Inc. Tax)
                                            <span class="text-red-500 ml-1">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-primary font-mono font-bold">£</span>
                                            <input type="number" step="0.01" min="0.01" id="selling_price_inc" name="selling_price_inc" required value="<?php echo htmlspecialchars($_POST['selling_price_inc'] ?? '15.00'); ?>" 
                                                    class="calc-field w-full p-4 pl-10 border-2 border-primary rounded-input bg-light focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 font-mono text-xl font-bold text-right text-primary">
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="selling_price_tax_type" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Selling Price Tax Rule
                                            <span class="text-red-500 ml-1">*</span>
                                        </label>
                                        <select id="selling_price_tax_type" name="selling_price_tax_type" required 
                                                class="w-full p-4 border border-orange-400 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,<svg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22><path%20fill%3D%22%236B7280%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%2F><%2Fsvg>')] bg-no-repeat bg-right-4 bg-center bg-[length:20px_20px] pr-12">
                                            <option value="Inclusive" <?php echo ($_POST['selling_price_tax_type'] ?? 'Inclusive') == 'Inclusive' ? 'selected' : ''; ?>>Inclusive (Price Includes VAT)</option>
                                            <option value="Exclusive" <?php echo ($_POST['selling_price_tax_type'] ?? '') == 'Exclusive' ? 'selected' : ''; ?>>Exclusive (VAT Added On Top)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="applicable_tax" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Applicable Tax Rate
                                        </label>
                                        <select id="applicable_tax" name="applicable_tax" 
                                                class="calc-field w-full p-4 border border-orange-400 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,<svg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22><path%20fill%3D%22%236B7280%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%2F><%2Fsvg>')] bg-no-repeat bg-right-4 bg-center bg-[length:20px_20px] pr-12">
                                            <option value="Standard Rate (20%)" selected>Standard Rate (20%)</option>
                                            <option value="Zero Rated (0%)">Zero Rated (0%)</option>
                                            <option value="Reduced Rate (5%)">Reduced Rate (5%)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-1 space-y-8">

                            <div class="bg-white p-8 rounded-card shadow-smooth border border-blue-50">
                                <h2 class="text-xl font-bold text-gray-800 border-b border-blue-100 pb-4 mb-6 flex items-center">
                                    <span class="bg-primary text-white w-8 h-8 rounded-lg flex items-center justify-center mr-3 text-sm">3</span>
                                    Categories & Types
                                </h2>
                                
                                <div class="space-y-6">
                                    <div>
                                        <label for="category" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Primary Category
                                            <span class="text-red-500 ml-1">*</span>
                                        </label>
                                        <select id="category" name="category" required 
                                                class="w-full p-4 border border-orange-400 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,<svg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22><path%20fill%3D%22%236B7280%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%2F><%2Fsvg>')] bg-no-repeat bg-right-4 bg-center bg-[length:20px_20px] pr-12">
                                            <option value="Accessories" selected>Accessories (Chalk, Tips, etc.)</option>
                                            <option value="Cues">Cues & Cases</option>
                                            <option value="TableHire">Table Hire / Service</option>
                                            <option value="Consumables">Food & Drink (Bar)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="sub_category" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Sub category
                                        </label>
                                        <select id="sub_category" name="sub_category" 
                                                class="w-full p-4 border border-orange-400 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,<svg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22><path%20fill%3D%22%236B7280%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%2F><%2Fsvg>')] bg-no-repeat bg-right-4 bg-center bg-[length:20px_20px] pr-12">
                                            <option value="">-- None --</option>
                                            <option value="Snooker">Snooker</option>
                                            <option value="Pool">Pool</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-6 border-t border-blue-100 pt-6">
                                    <label class="block text-sm font-semibold text-gray-700 mb-4">Product Type</label>
                                    <div class="flex items-center justify-between p-4 rounded-input border border-blue-100 bg-blue-50">
                                        <label class="inline-flex items-center text-gray-800 cursor-pointer">
                                            <input type="radio" name="product_type" value="Single" checked 
                                                    class="h-5 w-5 text-primary border-gray-400 focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-all duration-200">
                                            <span class="ml-3 font-medium">Single Item</span>
                                        </label>
                                        <label class="inline-flex items-center text-gray-800 cursor-pointer">
                                            <input type="radio" name="product_type" value="Variable" 
                                                    class="h-5 w-5 text-primary border-gray-400 focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-all duration-200">
                                            <span class="ml-3 font-medium">Variable</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white p-8 rounded-card shadow-smooth border border-blue-50">
                                <h2 class="text-xl font-bold text-gray-800 border-b border-blue-100 pb-4 mb-6 flex items-center">
                                    <span class="bg-primary text-white w-8 h-8 rounded-lg flex items-center justify-center mr-3 text-sm">4</span>
                                    Inventory Tracking
                                </h2>

                                <div class="flex items-center space-x-4 p-4 bg-light rounded-input border border-accent mb-6">
                                    <input type="checkbox" id="manage_stock" name="manage_stock" checked 
                                            class="h-5 w-5 text-primary border-gray-400 rounded focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-all duration-200">
                                    <label for="manage_stock" class="text-base font-semibold text-gray-800 cursor-pointer">
                                        Enable Stock Tracking
                                    </label>
                                </div>

                                <div id="stock-fields" class="space-y-6">
                                    <div>
                                        <label for="initial_stock" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Initial Stock Quantity
                                        </label>
                                        <input type="number" min="0" id="initial_stock" name="initial_stock" value="<?php echo htmlspecialchars($_POST['initial_stock'] ?? '0'); ?>" 
                                                class="w-full p-4 border border-orange-400 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 font-mono text-center text-lg">
                                        <span class="text-xs text-gray-500 mt-2 block">Quantity currently in storage</span>
                                    </div>
                                    <div>
                                        <label for="alert_quantity" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Low Stock Alert Level
                                        </label>
                                        <input type="number" min="0" id="alert_quantity" name="alert_quantity" value="<?php echo htmlspecialchars($_POST['alert_quantity'] ?? '5'); ?>" 
                                                class="w-full p-4 border border-red-300 rounded-input bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 shadow-input transition-all duration-200 text-center">
                                        <span class="text-xs text-red-500 mt-2 block">Staff notified when stock drops below this</span>
                                    </div>
                                </div>
                            </div>
                          <div class="bg-white dark:bg-gray-800 p-8 rounded-card shadow-smooth border border-blue-50 dark:border-gray-700">
    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 border-b border-blue-100 dark:border-gray-600 pb-4 mb-6 flex items-center">
        <span class="bg-primary text-white w-8 h-8 rounded-lg flex items-center justify-center mr-3 text-sm"></span>
        Product Image
    </h2>
    <div class="border-2 border-dashed border-blue-300 dark:border-gray-600 rounded-input p-8 text-center bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-800 hover:border-primary transition-all duration-300 cursor-pointer group">
        <svg class="mx-auto h-12 w-12 text-blue-400 dark:text-gray-300 group-hover:text-primary transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-12 5h8a2 2 0 002-2v-8a2 2 0 00-2-2H8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
        </svg>
        <p class="text-sm text-blue-600 dark:text-gray-300 my-3 font-medium">Drag and drop or</p>
        <input type="file" id="product_image" name="product_image" accept="image/*"
            class="file-input w-full text-sm text-gray-500 dark:text-gray-300 file:mr-4 file:py-3 file:px-6 file:rounded-button file:border-0 
                   file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-dark dark:file:bg-blue-600 
                   dark:file:text-white dark:hover:file:bg-blue-700 transition-all duration-200 cursor-pointer">
    </div>
</div>


                    <div class="pt-6 border-t border-gray-200 flex justify-end fixed bottom-0 left-0 right-0 p-4 bg-white shadow-2xl z-10 lg:ml-64">
                        <button type="submit"
                                class="btn-primary px-8 py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-button hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                            ✅ Save Product
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const costPriceInput = document.getElementById('cost_price');
            const marginInput = document.getElementById('margin');
            const sellingPriceIncInput = document.getElementById('selling_price_inc');
            const sellingPriceHiddenInput = document.getElementById('selling_price');
            const taxRateSelect = document.getElementById('applicable_tax');
            const taxTypeSelect = document.getElementById('selling_price_tax_type');
            
            const manageStockCheckbox = document.getElementById('manage_stock');
            const stockFieldsDiv = document.getElementById('stock-fields');

            // --- Price Calculation Logic ---

            const taxRates = {
                'Standard Rate (20%)': 0.20,
                'Reduced Rate (5%)': 0.05,
                'Zero Rated (0%)': 0.00
            };

            function getTaxRateValue() {
                return taxRates[taxRateSelect.value] || 0.00;
            }

            function calculatePrices(changedField) {
                let costPrice = parseFloat(costPriceInput.value) || 0;
                let margin = parseFloat(marginInput.value) || 0;
                let taxRate = getTaxRateValue();
                let isInclusive = taxTypeSelect.value === 'Inclusive';
                let sellingPriceInc = parseFloat(sellingPriceIncInput.value) || 0;

                let calculatedSellingPriceExc;
                let calculatedSellingPriceInc;
                
                // 1. Calculate from Cost + Margin
                if (changedField === 'cost_price' || changedField === 'margin' || changedField === 'applicable_tax' || changedField === 'selling_price_tax_type') {
                    // Margin calculation: Selling Price Exc. Tax = Cost Price / (1 - Margin %)
                    calculatedSellingPriceExc = costPrice / (1 - (margin / 100));
                    
                    if (isInclusive) {
                        calculatedSellingPriceInc = calculatedSellingPriceExc * (1 + taxRate);
                    } else {
                        calculatedSellingPriceInc = calculatedSellingPriceExc + (calculatedSellingPriceExc * taxRate);
                    }
                    
                    sellingPriceIncInput.value = calculatedSellingPriceInc.toFixed(2);
                    sellingPriceHiddenInput.value = calculatedSellingPriceExc.toFixed(2);

                } 
                // 2. Calculate from Selling Price Inclusive (User manually enters final price)
                else if (changedField === 'selling_price_inc') {
                    
                    if (isInclusive) {
                        calculatedSellingPriceExc = sellingPriceInc / (1 + taxRate);
                    } else {
                        calculatedSellingPriceExc = sellingPriceInc;
                    }

                    // Recalculate Margin for display purposes: Margin = (1 - (Cost / Selling Exc)) * 100
                    let calculatedMargin = (calculatedSellingPriceExc > 0) ? (1 - (costPrice / calculatedSellingPriceExc)) * 100 : 0;

                    sellingPriceHiddenInput.value = calculatedSellingPriceExc.toFixed(2);
                    marginInput.value = calculatedMargin.toFixed(2);
                }
            }

            // --- Stock Toggle Logic ---

            function toggleStockFields() {
                if (manageStockCheckbox.checked) {
                    stockFieldsDiv.style.display = 'block';
                } else {
                    stockFieldsDiv.style.display = 'none';
                    // Optional: Clear or set initial_stock to 0 when disabled
                    document.getElementById('initial_stock').value = 0;
                    document.getElementById('alert_quantity').value = 0;
                }
            }

            // Event Listeners
            costPriceInput.addEventListener('input', () => calculatePrices('cost_price'));
            marginInput.addEventListener('input', () => calculatePrices('margin'));
            sellingPriceIncInput.addEventListener('input', () => calculatePrices('selling_price_inc'));
            taxRateSelect.addEventListener('change', () => calculatePrices('applicable_tax'));
            taxTypeSelect.addEventListener('change', () => calculatePrices('selling_price_tax_type'));
            
            manageStockCheckbox.addEventListener('change', toggleStockFields);

            // Initial run on load
            toggleStockFields();
            calculatePrices('margin'); // Start by calculating based on default margin
        });
    </script>
</body>
</html>