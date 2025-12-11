<?php
session_start();
include 'db.php';

// Function to map tax options to a numeric rate
function getTaxRate($tax_option) {
    switch ($tax_option) {
        case 'Standard Rate (20%)':
            return 0.20;
        case 'Reduced Rate (5%)':
            return 0.05;
        case 'Zero Rated (0%)':
        case '1':
            return 0.00;
        default:
            return 0.00;
    }
}

// --- Configuration & Initialization ---
$message = '';
$message_type = '';
$upload_dir = 'product_images/'; 

// Create the upload directory if it doesn't exist
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        die('Failed to create upload directory: ' . $upload_dir);
    }
}

// --- Form Submission Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Collect and Sanitize ALL Input Fields ---
    $sku = trim($_POST['sku'] ?? '');
    $barcode_type = trim($_POST['barcode_type'] ?? '');
    $unit = trim($_POST['unit'] ?? 'Pc');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    $cost_price = (float)($_POST['cost_price'] ?? 0);
    $selling_price_inc = (float)($_POST['selling_price_inc'] ?? 0);
    $applicable_tax_text = trim($_POST['applicable_tax'] ?? 'Standard Rate (20%)');
    $tax_rate = getTaxRate($applicable_tax_text);

    $stock_quantity = (int)($_POST['initial_stock'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $sub_category = trim($_POST['sub_category'] ?? '');
    $alert_quantity = (int)($_POST['alert_quantity'] ?? 0);
    
    $product_type = trim($_POST['product_type'] ?? 'Single');
    $selling_price_tax_type = trim($_POST['selling_price_tax_type'] ?? 'Inclusive');
    
    $is_active = 1; 
    $is_service_product = ($unit === 'Hr') ? 1 : 0;
    
    // --- Calculate Selling Price (Tax Exclusive) ---
    if ($selling_price_tax_type === 'Inclusive' && $tax_rate > 0) {
        $selling_price = $selling_price_inc / (1 + $tax_rate);
    } else {
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
    
    // --- Validation and SQL INSERT ---
    if (empty($name) || $selling_price_inc <= 0) {
        $message = 'Product Name and Selling Price are required.';
        $message_type = 'error';
    } else if (empty($message_type)) {
        $tax_id_placeholder = $applicable_tax_text;
        
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
                stock_quantity = stock_quantity + VALUES(stock_quantity),
                category = VALUES(category),
                updated_at = NOW(),
                brand = VALUES(brand),
                sub_category = VALUES(sub_category),
                alert_quantity = VALUES(alert_quantity),
                is_service_product = VALUES(is_service_product),
                tax_id = VALUES(tax_id),
                selling_price_tax_type = VALUES(selling_price_tax_type),
                product_type = VALUES(product_type),
                image_path = IF(VALUES(image_path) = '', image_path, VALUES(image_path))
        ";
        
        $stmt = $conn->prepare($sql);
        $service_time_minutes = 0;
        
        $stmt->bind_param("sssssddisisiiisisss", 
            $sku, $barcode_type, $unit, $name, $description, $cost_price, $selling_price, 
            $stock_quantity, $category, $is_active, $brand, $sub_category, $alert_quantity, 
            $is_service_product, $service_time_minutes, $tax_id_placeholder, 
            $selling_price_tax_type, $product_type, $image_path
        );

        if ($stmt->execute()) {
            $rows_affected = $conn->affected_rows;
            
            if ($rows_affected > 1) {
                $message = 'Product **' . htmlspecialchars($name) . '** updated successfully (Stock Adjusted)!';
            } else {
                $message = 'Product **' . htmlspecialchars($name) . '** added successfully!';
            }
            
            $message_type = 'success';
            $_POST = array();
        } else {
            $message = 'Database Error: ' . $stmt->error;
            $message_type = 'error';
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product | Snooker POS</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
        }
        
        /* Modern Card Design */
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 8px 32px rgba(31, 38, 135, 0.07),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card:hover {
            box-shadow: 
                0 12px 48px rgba(31, 38, 135, 0.12),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            transform: translateY(-2px);
        }
        
        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Step Indicators */
        .step-indicator {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
            position: relative;
            z-index: 1;
        }
        
        .step-indicator::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 20px;
            height: 2px;
            background: linear-gradient(90deg, #e0e7ff 0%, #c7d2fe 100%);
            z-index: 0;
        }
        
        .step-indicator:last-child::after {
            display: none;
        }
        
        /* Form Inputs */
        .form-input {
            background: linear-gradient(to bottom, #ffffff, #f8fafc);
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 16px;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        
        .form-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: white;
            transform: translateY(-1px);
        }
        
        .form-input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }
        
        /* Select Styling */
        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 1.25em 1.25em;
            padding-right: 2.5rem;
            cursor: pointer;
        }
        
        /* Radio & Checkbox */
        .custom-radio {
            width: 20px;
            height: 20px;
            border: 2px solid #cbd5e1;
            transition: all 0.2s ease;
        }
        
        .custom-radio:checked {
            border-color: #6366f1;
            background-color: #6366f1;
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e");
            background-size: 60% 60%;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .custom-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #cbd5e1;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .custom-checkbox:checked {
            border-color: #6366f1;
            background-color: #6366f1;
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e");
            background-size: 60% 60%;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        /* File Upload Area */
        .file-upload-area {
            border: 2px dashed #c7d2fe;
            border-radius: 12px;
            background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%);
            transition: all 0.3s ease;
        }
        
        .file-upload-area:hover {
            border-color: #6366f1;
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            transform: translateY(-1px);
        }
        
        .file-upload-area.dragover {
            border-color: #6366f1;
            background: linear-gradient(135deg, #c7d2fe 0%, #a5b4fc 100%);
        }
        
        /* Price Inputs */
        .price-input {
            background: linear-gradient(to bottom, #f8fafc, #f1f5f9);
            border: 2px solid #e2e8f0;
            border-radius: 10px;
        }
        
        .price-input:focus {
            border-color: #6366f1;
            background: white;
        }
        
        .selling-price-input {
            background: linear-gradient(to bottom, #f0f9ff, #e0f2fe);
            border: 2px solid #38bdf8;
        }
        
        .selling-price-input:focus {
            border-color: #0ea5e9;
            background: white;
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
            border: none;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(99, 102, 241, 0.4);
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        /* Section Headers */
        .section-header {
            position: relative;
            padding-bottom: 16px;
            margin-bottom: 24px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .section-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 2px;
        }
        
        /* Success/Error Messages */
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 1px solid #10b981;
            border-left: 4px solid #10b981;
            border-radius: 10px;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 1px solid #ef4444;
            border-left: 4px solid #ef4444;
            border-radius: 10px;
        }
        
        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c7d2fe;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a5b4fc;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Fixed Action Bar */
        .action-bar {
            background: linear-gradient(to bottom, rgba(255,255,255,0.98), rgba(255,255,255,0.95));
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .step-indicator::after {
                display: none;
            }
            
            .glass-card {
                padding: 20px !important;
            }
            
            .form-input, .form-select {
                padding: 12px 14px;
                font-size: 14px;
            }
        }
        
        /* Hover Effects for Interactive Elements */
        label:hover .custom-radio:not(:checked),
        label:hover .custom-checkbox:not(:checked) {
            border-color: #94a3b8;
        }
        
        /* Focus States */
        *:focus {
            outline: 2px solid transparent;
            outline-offset: 2px;
        }
        
        /* Loading State */
        .loading {
            position: relative;
            overflow: hidden;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* Badge Styling */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        /* Icon Wrapper */
        .icon-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            margin-right: 12px;
        }
        
        .icon-wrapper-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }
        
        .icon-wrapper-secondary {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: #0ea5e9;
        }
        
        .icon-wrapper-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50">

    <!-- Dashboard Container -->
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include 'layout/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 ml-0 lg:ml-64">
            <!-- Header -->
            <?php include "layout/header.php"; ?>

            <!-- Page Content -->
            <div class="p-4 md:p-6 lg:p-8">
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                                <span class="gradient-text">Add New Product</span>
                            </h1>
                            <p class="text-gray-600">Add a new product to your inventory</p>
                        </div>
                    </div>

                    <!-- Progress Steps -->
                    <div class="glass-card p-6 mb-8">
                        <div class="flex items-center justify-between md:justify-start md:space-x-8 overflow-x-auto pb-2">
                            <div class="flex items-center space-x-3 flex-shrink-0">
                                <div class="step-indicator">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <span class="font-semibold text-gray-900">Product Info</span>
                            </div>
                            <div class="flex items-center space-x-3 flex-shrink-0">
                                <div class="step-indicator bg-gradient-to-r from-blue-500 to-cyan-500">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <span class="font-medium text-gray-600">Pricing</span>
                            </div>
                            <div class="flex items-center space-x-3 flex-shrink-0">
                                <div class="step-indicator bg-gradient-to-r from-gray-400 to-gray-500">
                                    <i class="fas fa-folder"></i>
                                </div>
                                <span class="font-medium text-gray-600">Categories</span>
                            </div>
                            <div class="flex items-center space-x-3 flex-shrink-0">
                                <div class="step-indicator bg-gradient-to-r from-gray-400 to-gray-500">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <span class="font-medium text-gray-600">Inventory</span>
                            </div>
                            <div class="flex items-center space-x-3 flex-shrink-0">
                                <div class="step-indicator bg-gradient-to-r from-gray-400 to-gray-500">
                                    <i class="fas fa-image"></i>
                                </div>
                                <span class="font-medium text-gray-600">Image</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Message -->
                <?php if ($message): ?>
                    <div class="mb-8 animate-fade-in">
                        <div class="<?= $message_type === 'success' ? 'alert-success' : 'alert-error' ?> p-6">
                            <div class="flex items-start">
                                <div class="icon-wrapper <?= $message_type === 'success' ? 'icon-wrapper-success' : 'icon-wrapper-danger' ?>">
                                    <i class="fas <?= $message_type === 'success' ? 'fa-check' : 'fa-exclamation-triangle' ?>"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-bold text-gray-900 mb-1">
                                        <?= $message_type === 'success' ? 'Success!' : 'Error!' ?>
                                    </h3>
                                    <p class="text-gray-700"><?php echo htmlspecialchars($message); ?></p>
                                </div>
                                <button onclick="this.parentElement.parentElement.remove()" 
                                        class="text-gray-400 hover:text-gray-600 ml-4">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Main Form -->
                <form method="POST" action="add_product.php" enctype="multipart/form-data" class="space-y-8 pb-32">
                    <input type="hidden" name="action" value="add_product">
                    <input type="hidden" id="selling_price" name="selling_price" value="0.00">

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                        <!-- Left Column (2/3 width) -->
                        <div class="lg:col-span-2 space-y-6 lg:space-y-8">
                            <!-- Section 1: Product Identification -->
                            <div class="glass-card p-6 lg:p-8">
                                <div class="section-header">
                                    <div class="flex items-center">
                                        <div class="icon-wrapper icon-wrapper-primary">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <div>
                                            <h2 class="text-xl font-bold text-gray-900">Product Identification</h2>
                                            <p class="text-sm text-gray-600 mt-1">Basic information about your product</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <!-- Product Name & SKU -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="name" class="block text-sm font-semibold text-gray-900 mb-2">
                                                Product Name <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" id="name" name="name" required 
                                                   class="form-input w-full"
                                                   placeholder="Enter product name"
                                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                        </div>
                                        <div>
                                            <label for="sku" class="block text-sm font-semibold text-gray-900 mb-2">
                                                SKU Code
                                            </label>
                                            <input type="text" id="sku" name="sku" 
                                                   class="form-input w-full"
                                                   placeholder="Auto-generated or manual"
                                                   value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <!-- Unit, Brand & Barcode -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div>
                                            <label for="unit" class="block text-sm font-semibold text-gray-900 mb-2">
                                                Unit of Measure <span class="text-red-500">*</span>
                                            </label>
                                            <select id="unit" name="unit" required 
                                                    class="form-input form-select w-full">
                                                <option value="Pc" <?php echo ($_POST['unit'] ?? 'Pc') == 'Pc' ? 'selected' : ''; ?>>Pieces (Pc(s))</option>
                                                <option value="Hr" <?php echo ($_POST['unit'] ?? '') == 'Hr' ? 'selected' : ''; ?>>Hour (Table Time)</option>
                                                <option value="Bottle" <?php echo ($_POST['unit'] ?? '') == 'Bottle' ? 'selected' : ''; ?>>Bottle / Can</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="brand" class="block text-sm font-semibold text-gray-900 mb-2">
                                                Brand
                                            </label>
                                            <select id="brand" name="brand" 
                                                    class="form-input form-select w-full">
                                                <option value="">-- Select Brand --</option>
                                                <option value="Triangle" <?php echo ($_POST['brand'] ?? '') == 'Triangle' ? 'selected' : ''; ?>>Triangle</option>
                                                <option value="Peradon" <?php echo ($_POST['brand'] ?? '') == 'Peradon' ? 'selected' : ''; ?>>Peradon</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="barcode_type" class="block text-sm font-semibold text-gray-900 mb-2">
                                                Barcode
                                            </label>
                                            <input type="text" id="barcode_type" name="barcode_type" 
                                                   class="form-input w-full"
                                                   placeholder="Scan or enter barcode"
                                                   value="<?php echo htmlspecialchars($_POST['barcode_type'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <div>
                                        <label for="description" class="block text-sm font-semibold text-gray-900 mb-2">
                                            Product Description
                                        </label>
                                        <textarea id="description" name="description" rows="4"
                                                  class="form-input w-full resize-none"
                                                  placeholder="Enter product description..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Pricing & Tax -->
                            <div class="glass-card p-6 lg:p-8">
                                <div class="section-header">
                                    <div class="flex items-center">
                                        <div class="icon-wrapper icon-wrapper-primary">
                                            <i class="fas fa-tags"></i>
                                        </div>
                                        <div>
                                            <h2 class="text-xl font-bold text-gray-900">Pricing & Financials</h2>
                                            <p class="text-sm text-gray-600 mt-1">Set pricing, margin, and tax information</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <!-- Price Inputs -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div>
                                            <label for="cost_price" class="block text-sm font-semibold text-gray-900 mb-2">
                                                Cost Price (Exc. Tax)
                                            </label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">£</span>
                                                <input type="number" step="0.01" min="0" id="cost_price" name="cost_price" 
                                                       class="price-input form-input w-full pl-8 text-right"
                                                       value="<?php echo htmlspecialchars($_POST['cost_price'] ?? '10.00'); ?>">
                                            </div>
                                        </div>

                                        <div>
                                            <label for="margin" class="block text-sm font-semibold text-gray-900 mb-2">
                                                Desired Margin (%)
                                            </label>
                                            <div class="relative">
                                                <input type="number" step="0.01" id="margin" name="margin" 
                                                       class="price-input form-input w-full pr-8 text-right"
                                                       value="<?php echo htmlspecialchars($_POST['margin'] ?? '33.33'); ?>">
                                                <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">%</span>
                                            </div>
                                        </div>

                                        <div>
                                            <label for="selling_price_inc" class="block text-sm font-semibold text-blue-600 mb-2">
                                                Selling Price (Inc. Tax) <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-blue-600 font-bold">£</span>
                                                <input type="number" step="0.01" min="0.01" id="selling_price_inc" name="selling_price_inc" required 
                                                       class="selling-price-input form-input w-full pl-8 text-right font-semibold text-blue-700"
                                                       value="<?php echo htmlspecialchars($_POST['selling_price_inc'] ?? '15.00'); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tax Settings -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="selling_price_tax_type" class="block text-sm font-semibold text-gray-900 mb-2">
                                                Price Tax Type <span class="text-red-500">*</span>
                                            </label>
                                            <select id="selling_price_tax_type" name="selling_price_tax_type" required 
                                                    class="form-input form-select w-full">
                                                <option value="Inclusive" <?php echo ($_POST['selling_price_tax_type'] ?? 'Inclusive') == 'Inclusive' ? 'selected' : ''; ?>>Inclusive (Price Includes VAT)</option>
                                                <option value="Exclusive" <?php echo ($_POST['selling_price_tax_type'] ?? '') == 'Exclusive' ? 'selected' : ''; ?>>Exclusive (VAT Added On Top)</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="applicable_tax" class="block text-sm font-semibold text-gray-900 mb-2">
                                                Tax Rate
                                            </label>
                                            <select id="applicable_tax" name="applicable_tax" 
                                                    class="calc-field form-input form-select w-full">
                                                <option value="Standard Rate (20%)" selected>Standard Rate (20%)</option>
                                                <option value="Zero Rated (0%)">Zero Rated (0%)</option>
                                                <option value="Reduced Rate (5%)">Reduced Rate (5%)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column (1/3 width) -->
                        <div class="space-y-6 lg:space-y-8">
                            <!-- Section 3: Categories -->
                            <div class="glass-card p-6 lg:p-8">
                                <div class="section-header">
                                    <div class="flex items-center">
                                        <div class="icon-wrapper icon-wrapper-primary">
                                            <i class="fas fa-folder"></i>
                                        </div>
                                        <div>
                                            <h2 class="text-xl font-bold text-gray-900">Categories & Types</h2>
                                            <p class="text-sm text-gray-600 mt-1">Organize your product</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <div>
                                        <label for="category" class="block text-sm font-semibold text-gray-900 mb-2">
                                            Primary Category <span class="text-red-500">*</span>
                                        </label>
                                        <select id="category" name="category" required 
                                                class="form-input form-select w-full">
                                            <option value="Accessories" selected>Accessories (Chalk, Tips, etc.)</option>
                                            <option value="Cues">Cues & Cases</option>
                                            <option value="TableHire">Table Hire / Service</option>
                                            <option value="Consumables">Food & Drink (Bar)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="sub_category" class="block text-sm font-semibold text-gray-900 mb-2">
                                            Sub Category
                                        </label>
                                        <select id="sub_category" name="sub_category" 
                                                class="form-input form-select w-full">
                                            <option value="">-- None --</option>
                                            <option value="Snooker">Snooker</option>
                                            <option value="Pool">Pool</option>
                                        </select>
                                    </div>

                                    <div class="pt-6 border-t border-gray-100">
                                        <label class="block text-sm font-semibold text-gray-900 mb-4">Product Type</label>
                                        <div class="space-y-3">
                                            <label class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-300 cursor-pointer transition-all">
                                                <input type="radio" name="product_type" value="Single" checked 
                                                       class="custom-radio">
                                                <div class="ml-3">
                                                    <span class="font-medium text-gray-900">Single Item</span>
                                                    <p class="text-sm text-gray-600 mt-1">Standard product with fixed attributes</p>
                                                </div>
                                            </label>
                                            <label class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-300 cursor-pointer transition-all">
                                                <input type="radio" name="product_type" value="Variable" 
                                                       class="custom-radio">
                                                <div class="ml-3">
                                                    <span class="font-medium text-gray-900">Variable</span>
                                                    <p class="text-sm text-gray-600 mt-1">Multiple variations (size, color, etc.)</p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 4: Inventory -->
                            <div class="glass-card p-6 lg:p-8">
                                <div class="section-header">
                                    <div class="flex items-center">
                                        <div class="icon-wrapper icon-wrapper-primary">
                                            <i class="fas fa-boxes"></i>
                                        </div>
                                        <div>
                                            <h2 class="text-xl font-bold text-gray-900">Inventory Tracking</h2>
                                            <p class="text-sm text-gray-600 mt-1">Manage stock levels</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <!-- Stock Toggle -->
                                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-100">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="checkbox" id="manage_stock" name="manage_stock" checked 
                                                   class="custom-checkbox">
                                            <span class="ml-3 font-semibold text-gray-900">Enable Stock Tracking</span>
                                        </label>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check mr-1"></i> Enabled
                                        </span>
                                    </div>

                                    <!-- Stock Fields -->
                                    <div id="stock-fields" class="space-y-6">
                                        <div>
                                            <label for="initial_stock" class="block text-sm font-semibold text-gray-900 mb-2">
                                                Initial Stock Quantity
                                            </label>
                                            <input type="number" min="0" id="initial_stock" name="initial_stock" 
                                                   class="form-input w-full text-center font-semibold"
                                                   value="<?php echo htmlspecialchars($_POST['initial_stock'] ?? '0'); ?>">
                                            <p class="text-xs text-gray-500 mt-2 text-center">Quantity currently in storage</p>
                                        </div>

                                        <div>
                                            <label for="alert_quantity" class="block text-sm font-semibold text-gray-900 mb-2">
                                                Low Stock Alert Level
                                            </label>
                                            <input type="number" min="0" id="alert_quantity" name="alert_quantity" 
                                                   class="form-input w-full text-center border-red-200 bg-red-50 font-semibold"
                                                   value="<?php echo htmlspecialchars($_POST['alert_quantity'] ?? '5'); ?>">
                                            <p class="text-xs text-red-500 mt-2 text-center">Alert when stock falls below this</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 5: Product Image -->
                            <div class="glass-card p-6 lg:p-8">
                                <div class="section-header">
                                    <div class="flex items-center">
                                        <div class="icon-wrapper icon-wrapper-primary">
                                            <i class="fas fa-image"></i>
                                        </div>
                                        <div>
                                            <h2 class="text-xl font-bold text-gray-900">Product Image</h2>
                                            <p class="text-sm text-gray-600 mt-1">Upload product photo</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="file-upload-area p-8 text-center" id="dropZone">
                                    <div class="mb-4">
                                        <div class="w-20 h-20 mx-auto bg-gradient-to-br from-blue-100 to-indigo-100 rounded-2xl flex items-center justify-center">
                                            <i class="fas fa-camera text-blue-400 text-2xl"></i>
                                        </div>
                                    </div>

                                    <input type="file" id="product_image" name="product_image" accept="image/*" 
                                           class="hidden">
                                    
                                    <label for="product_image" class="cursor-pointer">
                                        <div class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 font-semibold">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span>Choose Image</span>
                                        </div>
                                    </label>
                                    
                                    <p class="text-sm text-gray-500 mt-3">JPG, PNG or GIF • Max 5MB</p>
                                    
                                    <!-- File Info -->
                                    <div id="fileInfo" class="mt-4 p-3 bg-gray-50 rounded-lg hidden">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-file-image text-blue-500"></i>
                                                <span id="fileName" class="text-sm font-medium text-gray-900"></span>
                                            </div>
                                            <button type="button" id="removeImage" class="text-red-500 hover:text-red-700">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Bar -->
                    <div class="action-bar fixed bottom-0 left-0 right-0 p-4 lg:ml-64 z-50">
                        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                Fields marked with <span class="text-red-500">*</span> are required
                            </div>
                            <div class="flex gap-3">
                                <button type="reset" 
                                        class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-all">
                                    <i class="fas fa-redo mr-2"></i>Reset
                                </button>
                                <button type="submit" 
                                        class="btn-primary flex items-center gap-2">
                                    <i class="fas fa-save"></i>
                                    Save Product
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const costPriceInput = document.getElementById('cost_price');
            const marginInput = document.getElementById('margin');
            const sellingPriceIncInput = document.getElementById('selling_price_inc');
            const sellingPriceHiddenInput = document.getElementById('selling_price');
            const taxRateSelect = document.getElementById('applicable_tax');
            const taxTypeSelect = document.getElementById('selling_price_tax_type');
            const manageStockCheckbox = document.getElementById('manage_stock');
            const stockFieldsDiv = document.getElementById('stock-fields');
            const fileInput = document.getElementById('product_image');
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const removeImageBtn = document.getElementById('removeImage');
            const dropZone = document.getElementById('dropZone');

            // Tax rates
            const taxRates = {
                'Standard Rate (20%)': 0.20,
                'Reduced Rate (5%)': 0.05,
                'Zero Rated (0%)': 0.00
            };

            // Calculate prices
            function calculatePrices(changedField) {
                let costPrice = parseFloat(costPriceInput.value) || 0;
                let margin = parseFloat(marginInput.value) || 0;
                let taxRate = taxRates[taxRateSelect.value] || 0.00;
                let isInclusive = taxTypeSelect.value === 'Inclusive';
                let sellingPriceInc = parseFloat(sellingPriceIncInput.value) || 0;

                let calculatedSellingPriceExc;
                let calculatedSellingPriceInc;
                
                // Calculate from Cost + Margin
                if (changedField === 'cost_price' || changedField === 'margin' || changedField === 'applicable_tax' || changedField === 'selling_price_tax_type') {
                    calculatedSellingPriceExc = costPrice / (1 - (margin / 100));
                    
                    if (isInclusive) {
                        calculatedSellingPriceInc = calculatedSellingPriceExc * (1 + taxRate);
                    } else {
                        calculatedSellingPriceInc = calculatedSellingPriceExc + (calculatedSellingPriceExc * taxRate);
                    }
                    
                    sellingPriceIncInput.value = calculatedSellingPriceInc.toFixed(2);
                    sellingPriceHiddenInput.value = calculatedSellingPriceExc.toFixed(2);

                } 
                // Calculate from Selling Price Inclusive
                else if (changedField === 'selling_price_inc') {
                    if (isInclusive) {
                        calculatedSellingPriceExc = sellingPriceInc / (1 + taxRate);
                    } else {
                        calculatedSellingPriceExc = sellingPriceInc;
                    }

                    let calculatedMargin = (calculatedSellingPriceExc > 0) ? (1 - (costPrice / calculatedSellingPriceExc)) * 100 : 0;

                    sellingPriceHiddenInput.value = calculatedSellingPriceExc.toFixed(2);
                    marginInput.value = calculatedMargin.toFixed(2);
                }
                
                // Add visual feedback
                if (changedField) {
                    const input = document.getElementById(changedField);
                    if (input) {
                        input.classList.add('loading');
                        setTimeout(() => {
                            input.classList.remove('loading');
                        }, 500);
                    }
                }
            }

            // Toggle stock fields
            function toggleStockFields() {
                if (manageStockCheckbox.checked) {
                    stockFieldsDiv.style.display = 'block';
                    stockFieldsDiv.style.opacity = '1';
                    stockFieldsDiv.style.pointerEvents = 'all';
                } else {
                    stockFieldsDiv.style.display = 'none';
                    stockFieldsDiv.style.opacity = '0.5';
                    stockFieldsDiv.style.pointerEvents = 'none';
                    document.getElementById('initial_stock').value = 0;
                    document.getElementById('alert_quantity').value = 0;
                }
            }

            // File upload handling
            function handleFileUpload(event) {
                const file = event.target.files[0];
                if (file) {
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File size must be less than 5MB');
                        return;
                    }
                    
                    fileName.textContent = file.name;
                    fileInfo.classList.remove('hidden');
                    dropZone.classList.remove('file-upload-area');
                    dropZone.classList.add('bg-gradient-to-r', 'from-green-50', 'to-emerald-50', 'border-green-400');
                }
            }

            // Remove image
            function removeImage() {
                fileInput.value = '';
                fileInfo.classList.add('hidden');
                dropZone.classList.remove('bg-gradient-to-r', 'from-green-50', 'to-emerald-50', 'border-green-400');
                dropZone.classList.add('file-upload-area');
            }

            // Drag and drop functionality
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });

            function highlight() {
                dropZone.classList.add('dragover');
            }

            function unhighlight() {
                dropZone.classList.remove('dragover');
            }

            dropZone.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                fileInput.files = files;
                handleFileUpload({target: fileInput});
            }

            // Event Listeners
            costPriceInput.addEventListener('input', () => calculatePrices('cost_price'));
            marginInput.addEventListener('input', () => calculatePrices('margin'));
            sellingPriceIncInput.addEventListener('input', () => calculatePrices('selling_price_inc'));
            taxRateSelect.addEventListener('change', () => calculatePrices('applicable_tax'));
            taxTypeSelect.addEventListener('change', () => calculatePrices('selling_price_tax_type'));
            manageStockCheckbox.addEventListener('change', toggleStockFields);
            fileInput.addEventListener('change', handleFileUpload);
            
            if (removeImageBtn) {
                removeImageBtn.addEventListener('click', removeImage);
            }

            // Initialize
            toggleStockFields();
            calculatePrices('margin');

            // Auto-generate SKU if empty
            if (!document.getElementById('sku').value) {
                const prefix = 'PROD';
                const timestamp = Date.now().toString().slice(-6);
                const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                document.getElementById('sku').value = `${prefix}-${timestamp}-${random}`;
            }

            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + S to save
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    document.querySelector('button[type="submit"]').click();
                }
            });

            // Add form validation styling
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const requiredInputs = form.querySelectorAll('[required]');
                
                requiredInputs.forEach(input => {
                    if (!input.value.trim()) {
                        input.classList.add('border-red-500', 'bg-red-50');
                        isValid = false;
                        
                        // Remove error styling after 3 seconds
                        setTimeout(() => {
                            input.classList.remove('border-red-500', 'bg-red-50');
                        }, 3000);
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    // Scroll to first error
                    const firstError = form.querySelector('.border-red-500');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }
                }
            });

            // Remove error styling on input
            form.querySelectorAll('[required]').forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('border-red-500', 'bg-red-50');
                });
            });
        });
    </script>
</body>
</html>