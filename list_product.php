<?php

function getDbConnection() {
    return true; 
}

// 2. Form Submission Handling (Placeholder - saves data to a simulated database)
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = getDbConnection();
    $action = $_POST['action'] ?? '';
    $name = $_POST['name'] ?? 'New Product';
    
    // Simulate database interaction
    if ($conn && $action === 'add_product') {
        $sku = $_POST['sku'] ?? 'N/A';
        $initial_stock = $_POST['initial_stock'] ?? 0;

        try {
            if (isset($_POST['save_and_open_stock'])) {
                 $message = "Product '{$name}' added and {$initial_stock} units of opening stock recorded successfully.";
            } else {
                 $message = "Product '{$name}' saved successfully. Ready for stock entry.";
            }
            $message_type = 'success';

        } catch (Exception $e) {
            $message_type = 'error';
            $message = "Error: Could not save product data.";
        }
    } else if (!$conn) {
        $message_type = 'error';
        $message = "CRITICAL: Database connection failed. Cannot save product.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snooker POS - New Product Registration</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        // Custom Tailwind Configuration for premium blue theme
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        // Premium blue color palette
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
<body class="bg-gray-50 font-sans">

  <!-- Dashboard Container -->
  <div class="flex min-h-screen">

    <!-- Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 ml-0 lg:ml-64 pt-7 p-8 main-content"> <!-- pt-20 = header height -->
      
      <!-- Header -->
      <?php include "layout/header.php"; ?>

      <!-- Page Content -->
      <div id="content-area" class="space-y-8 bg-gray-100 p-6 rounded-lg">


            <!-- Enhanced Message Box -->
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

            <!-- Form Layout -->
            <form method="POST" action="add_product.php" class="space-y-8 pb-24 mt-3" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-2">
                    
                    <!-- Column 1 & 2: Main Details (2/3 width) -->
                    <div class="lg:col-span-2 space-y-8">
                        
                        <!-- 1. Core Product Details & Media -->
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
                                           placeholder="e.g., Green Spot Chalk (Box)">
                                </div>
                                <div>
                                    <label for="sku" class="block text-sm font-semibold text-gray-700 mb-2">
                                        SKU (Stock Code)
                                    </label>
                                    <input type="text" id="sku" name="sku" 
                                           class="w-full p-4 border border-orange-300 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200"
                                           placeholder="Auto / Manual Code">
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
                                        <option value="Pc" selected>Pieces (Pc(s))</option>
                                        <option value="Hr">Hour (Table Time)</option>
                                        <option value="Bottle">Bottle / Can</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="brand" class="block text-sm font-semibold text-gray-700 mb-2">Brand</label>
                                    <select id="brand" name="brand" 
                                            class="w-full p-4 border border-orange-400 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,<svg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22><path%20fill%3D%22%236B7280%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%2F><%2Fsvg>')] bg-no-repeat bg-right-4 bg-center bg-[length:20px_20px] pr-12">
                                        <option value="">-- No Brand --</option>
                                        <option value="Triangle">Triangle</option>
                                        <option value="Peradon">Peradon</option>
                                    </select>
                                </div>
                                <div class="lg:col-span-2">
                                    <label for="barcode_type" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Barcode Number (or ID)
                                    </label>
                                    <input type="text" id="barcode_type" name="barcode_type" 
                                           class="w-full p-4 border border-orange-400 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200"
                                           placeholder="Scan barcode or enter manually">
                                </div>
                            </div>

                            <div class="mt-8">
                                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Product Description / Notes
                                </label>
                                <textarea id="description" name="description" 
                                          class="w-full p-4 border border-blue-400 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 h-32 resize-none"
                                          placeholder="Key features, variants, or internal notes."></textarea>
                            </div>
                        </div>

                        <!-- 2. Inventory and Pricing -->
                        <div class="bg-white p-8 rounded-card shadow-smooth border  border-orange-400 ">
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
                                        <input type="number" step="0.01" min="0" id="cost_price" name="cost_price" value="10.00" 
                                               class="w-full p-4 pl-10 border border-orange-400 rounded-input bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 font-mono text-lg text-right">
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="margin" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Desired Margin (%)
                                    </label>
                                    <div class="relative">
                                        <span class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 font-mono">%</span>
                                        <input type="number" step="0.01" id="margin" name="margin" value="33.33" 
                                               class="w-full p-4 pr-10 border border-orange-400 rounded-input bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 font-mono text-lg text-right">
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="selling_price_inc" class="block text-sm font-semibold text-primary mb-2">
                                        Selling Price (Inc. Tax)
                                        <span class="text-red-500 ml-1">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-primary font-mono font-bold">£</span>
                                        <input type="number" step="0.01" min="0.01" id="selling_price_inc" name="selling_price_inc" required value="15.00" 
                                               class="w-full p-4 pl-10 border-2 border-primary rounded-input bg-light focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 font-mono text-xl font-bold text-right text-primary">
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
                                        <option value="Inclusive" selected>Inclusive (Price Includes VAT)</option>
                                        <option value="Exclusive">Exclusive (VAT Added On Top)</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="applicable_tax" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Applicable Tax Rate
                                    </label>
                                    <select id="applicable_tax" name="applicable_tax" 
                                            class="w-full p-4 border border-orange-400 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,<svg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22><path%20fill%3D%22%236B7280%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%2F><%2Fsvg>')] bg-no-repeat bg-right-4 bg-center bg-[length:20px_20px] pr-12">
                                        <option value="" selected>Standard Rate (20%)</option>
                                        <option value="1">Zero Rated (0%)</option>
                                        <option value="2">Reduced Rate (5%)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Column 3: Stock, Categories, Image (1/3 width) -->
                    <div class="lg:col-span-1 space-y-8">

                        <!-- 3. Categories & Types -->
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
                                <div class="flex items-center justify-between p-4 rounded-input border border-blue-200 bg-blue-50">
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

                        <!-- 4. Stock Management -->
                        <div class="bg-white p-8 rounded-card shadow-smooth border border-blue-50">
                            <h2 class="text-xl font-bold text-gray-800 border-b border-blue-100 pb-4 mb-6 flex items-center">
                                <span class="bg-primary text-white w-8 h-8 rounded-lg flex items-center justify-center mr-3 text-sm">4</span>
                                Inventory Tracking
                            </h2>

                            <!-- Stock Management Toggle -->
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
                                    <input type="number" min="0" id="initial_stock" name="initial_stock" value="0" 
                                           class="w-full p-4 border border-orange-400 rounded-input bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-input transition-all duration-200 font-mono text-center text-lg">
                                    <span class="text-xs text-gray-500 mt-2 block">Quantity currently in storage</span>
                                </div>
                                <div>
                                    <label for="alert_quantity" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Low Stock Alert Level
                                    </label>
                                    <input type="number" min="0" id="alert_quantity" name="alert_quantity" value="5" 
                                           class="w-full p-4 border border-red-300 rounded-input bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 shadow-input transition-all duration-200 text-center">
                                    <span class="text-xs text-red-500 mt-2 block">Staff notified when stock drops below this</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 5. Product Image Upload -->
                        <div class="bg-white p-8 rounded-card shadow-smooth border border-blue-50">
                            <h2 class="text-xl font-bold text-gray-800 border-b border-blue-100 pb-4 mb-6 flex items-center">
                                <span class="bg-primary text-white w-8 h-8 rounded-lg flex items-center justify-center mr-3 text-sm">5</span>
                                Product Image
                            </h2>
                            <div class="border-2 border-dashed border-blue-300 rounded-input p-8 text-center bg-gradient-to-br from-blue-50 to-indigo-50 hover:border-primary transition-all duration-300 cursor-pointer group">
                                <svg class="mx-auto h-12 w-12 text-blue-400 group-hover:text-primary transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-12 5h8a2 2 0 002-2v-8a2 2 0 00-2-2H8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                <p class="text-sm text-blue-600 my-3 font-medium">Drag and drop or</p>
                                <input type="file" id="product_image" name="product_image" accept="image/*" 
                                       class="file-input w-full text-sm text-gray-500 file:mr-4 file:py-3 file:px-6 file:rounded-button file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-dark transition-all duration-200 cursor-pointer">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Enhanced Sticky Footer Action Bar -->
                <div class="fixed bottom-0 left-0 right-0 lg:ml-64 bg-white p-6 shadow-2xl border-t border-orange-400 flex justify-end space-x-4 z-50">
                    <!-- Option 1: Save & Add Opening Stock -->
                  <!-- Option 1: Save & Add Opening Stock -->
<button type="submit" name="save_and_open_stock" value="1"
    class="flex items-center space-x-2 bg-orange-600 text-white hover:bg-orange-800 font-semibold py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <span>Save & Add Opening Stock</span>
</button>

<!-- Option 2: Simple Save -->
<button type="submit" name="save_simple" value="1"
    class="flex items-center space-x-2 bg-blue-800 hover:bg-blue-900 text-white font-semibold py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M5 13l4 4L19 7"></path>
    </svg>
    <span>Save Product</span>
</button>

                </div>
            </form>
        </main>
    </div>

   <script>
    document.addEventListener('DOMContentLoaded', () => {

        // Initialize TinyMCE
        tinymce.init({
            selector: '#description',
            height: 150,
            menubar: false,
            plugins: 'autolink lists link code help wordcount',
            toolbar: 'undo redo | bold italic | bullist numlist | code',
            statusbar: false,
            content_style: 'body { font-family: Inter, sans-serif; font-size:16px }'
        });

        // Toggle Stock Fields
        const manageStock = document.getElementById('manage_stock');
        const stockFieldsContainer = document.getElementById('stock-fields');

        const toggleStockFields = () => {
            if (stockFieldsContainer) {
                stockFieldsContainer.classList.toggle('hidden', !manageStock.checked);

                const initialStockInput = document.getElementById('initial_stock');
                if (initialStockInput) {
                    initialStockInput.required = manageStock.checked;
                }
            }
        };

        if (manageStock) {
            manageStock.addEventListener('change', toggleStockFields);
            toggleStockFields(); 
        }

        // Margin / Selling Price Calculation
        const costInput = document.getElementById('cost_price');
        const marginInput = document.getElementById('margin');
        const sellingInput = document.getElementById('selling_price_inc');

        // Calculate Selling Price from Cost + Margin
        const calculateSellingPrice = () => {
            const cost = parseFloat(costInput.value) || 0;
            const margin = parseFloat(marginInput.value) || 0;

            let selling = cost / (1 - margin / 100);

            if (isFinite(selling) && selling >= cost && cost > 0) {
                sellingInput.value = selling.toFixed(2);
            } else if (cost > 0) {
                sellingInput.value = (cost * 1.5).toFixed(2);
            } else {
                sellingInput.value = "0.00";
            }
        };

        // Calculate Margin from Cost + Selling Price
        const calculateMargin = () => {
            const cost = parseFloat(costInput.value) || 0;
            const selling = parseFloat(sellingInput.value) || 0;

            if (selling > cost && selling > 0) {
                const newMargin = ((selling - cost) / selling) * 100;
                marginInput.value = newMargin.toFixed(2);
            } else {
                marginInput.value = "0.00";
            }
        };

        // Attach Events
        if (costInput && marginInput && sellingInput) {
            marginInput.addEventListener('input', calculateSellingPrice);
            costInput.addEventListener('input', () => {
                calculateSellingPrice();
                calculateMargin();
            });
            sellingInput.addEventListener('input', calculateMargin);
        }
    });
       setTimeout(() => {
        const msg = document.getElementById("autoMessage");
        if (msg) {
            msg.style.transition = "opacity 0.5s";
            msg.style.opacity = "0";

            // Remove from DOM after fade out
            setTimeout(() => msg.remove(), 500);
        }
    }, 5000); // 5 seconds

</script>
