<?php
/**
 * Add Product Page with Image Upload
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $sku = trim($_POST['sku']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'] ?: null;
    $supplier_id = $_POST['supplier_id'] ?: null;
    $unit_price = floatval($_POST['unit_price']);
    $selling_price = floatval($_POST['selling_price']);
    $quantity = intval($_POST['quantity']);
    $min_stock_level = intval($_POST['min_stock_level']);
    $status = $_POST['status'];
    
    $image_path = null;
    
    // Handle image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $upload_dir = '../assets/images/products/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file = $_FILES['product_image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        
        if (in_array($file['type'], $allowed_types)) {
            if ($file['size'] <= 5000000) { // 5MB limit
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $image_path = $new_filename;
                } else {
                    $error_message = 'Failed to upload image.';
                }
            } else {
                $error_message = 'Image size must be less than 5MB.';
            }
        } else {
            $error_message = 'Only JPG, JPEG, PNG, and GIF files are allowed.';
        }
    }
    
    if (empty($error_message)) {
        if (!empty($name) && $unit_price >= 0 && $selling_price >= 0 && $quantity >= 0) {
            // Check if SKU exists
            if (!empty($sku)) {
                $check_stmt = $conn->prepare("SELECT id FROM products WHERE sku = ?");
                $check_stmt->bind_param("s", $sku);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error_message = 'SKU already exists. Please use a different SKU.';
                }
            }
            
            if (empty($error_message)) {
                $stmt = $conn->prepare("INSERT INTO products (name, sku, description, category_id, supplier_id, unit_price, selling_price, quantity, min_stock_level, image_path, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("sssiiddiiss", $name, $sku, $description, $category_id, $supplier_id, $unit_price, $selling_price, $quantity, $min_stock_level, $image_path, $status);
                
                if ($stmt->execute()) {
                    $success_message = 'Product created successfully!';
                    
                    // Log stock transaction
                    if ($quantity > 0) {
                        $product_id = $conn->insert_id;
                        $log_stmt = $conn->prepare("INSERT INTO stock_transactions (product_id, transaction_type, quantity, notes, user_id, created_at) VALUES (?, 'in', ?, 'Initial stock', ?, NOW())");
                        $log_stmt->bind_param("iii", $product_id, $quantity, $_SESSION['user_id']);
                        $log_stmt->execute();
                    }
                    
                    // Clear form data
                    $_POST = [];
                } else {
                    $error_message = 'Failed to create product.';
                }
            }
        } else {
            $error_message = 'Please fill in all required fields with valid values.';
        }
    }
}

// Get categories for dropdown
$categories = [];
try {
    $result = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
    if ($result) {
        $categories = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $categories = [];
}

// Get suppliers for dropdown
$suppliers = [];
try {
    $result = $conn->query("SELECT id, name FROM suppliers WHERE status = 'active' ORDER BY name ASC");
    if ($result) {
        $suppliers = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $suppliers = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - StockWise Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 0;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            color: white;
            padding: 20px 0;
        }
        
        .logo {
            text-align: center;
            padding: 20px;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-item {
            padding: 15px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .nav-item a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1e40af, #0f766e);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1e40af;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .image-upload {
            border: 2px dashed #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: border-color 0.3s ease;
        }
        
        .image-upload:hover {
            border-color: #1e40af;
        }
        
        .image-upload input[type="file"] {
            display: none;
        }
        
        .image-upload label {
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            color: #666;
        }
        
        .image-upload i {
            font-size: 2rem;
            color: #1e40af;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .required {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-boxes"></i> StockWise Pro
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="products.php">
                        <i class="fas fa-box"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a href="categories.php">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a href="suppliers.php">
                        <i class="fas fa-truck"></i> Suppliers
                    </a>
                </li>
                <li class="nav-item">
                    <a href="orders.php">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="sales.php">
                        <i class="fas fa-cash-register"></i> Sales
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a href="users.php">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-plus"></i> Add New Product</h1>
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- Basic Information -->
                    <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Product Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="sku">SKU (Stock Keeping Unit)</label>
                            <input type="text" id="sku" name="sku" value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>" placeholder="Auto-generated if empty">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Product description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Category and Supplier -->
                    <h3><i class="fas fa-tags"></i> Classification</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo (($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="supplier_id">Supplier</label>
                            <select id="supplier_id" name="supplier_id">
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>" <?php echo (($_POST['supplier_id'] ?? '') == $supplier['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($supplier['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Pricing -->
                    <h3><i class="fas fa-dollar-sign"></i> Pricing</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="unit_price">Unit Price (Cost) <span class="required">*</span></label>
                            <input type="number" id="unit_price" name="unit_price" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['unit_price'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="selling_price">Selling Price <span class="required">*</span></label>
                            <input type="number" id="selling_price" name="selling_price" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['selling_price'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <!-- Inventory -->
                    <h3><i class="fas fa-warehouse"></i> Inventory</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity">Initial Quantity <span class="required">*</span></label>
                            <input type="number" id="quantity" name="quantity" min="0" value="<?php echo htmlspecialchars($_POST['quantity'] ?? '0'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="min_stock_level">Minimum Stock Level <span class="required">*</span></label>
                            <input type="number" id="min_stock_level" name="min_stock_level" min="0" value="<?php echo htmlspecialchars($_POST['min_stock_level'] ?? '5'); ?>" required>
                        </div>
                    </div>
                    
                    <!-- Product Image -->
                    <h3><i class="fas fa-image"></i> Product Image</h3>
                    
                    <div class="form-group">
                        <div class="image-upload">
                            <input type="file" id="product_image" name="product_image" accept="image/*">
                            <label for="product_image">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Click to upload product image</span>
                                <small>JPG, PNG, GIF up to 5MB</small>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Status -->
                    <h3><i class="fas fa-toggle-on"></i> Status</h3>
                    
                    <div class="form-group">
                        <label for="status">Product Status</label>
                        <select id="status" name="status" required>
                            <option value="active" <?php echo (($_POST['status'] ?? 'active') == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (($_POST['status'] ?? '') == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-generate SKU based on product name
        document.getElementById('name').addEventListener('input', function() {
            const skuField = document.getElementById('sku');
            if (!skuField.value) {
                const name = this.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
                const timestamp = Date.now().toString().slice(-4);
                skuField.value = name.substring(0, 6) + '-' + timestamp;
            }
        });
        
        // Image upload preview
        document.getElementById('product_image').addEventListener('change', function() {
            const file = this.files[0];
            const label = this.nextElementSibling;
            
            if (file) {
                label.innerHTML = `
                    <i class="fas fa-check-circle" style="color: #28a745;"></i>
                    <span>Image selected: ${file.name}</span>
                    <small>Click to change image</small>
                `;
            }
        });
    </script>
</body>
</html>