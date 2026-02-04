<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
require_once '../config/db.php';

// Get user info for display
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT username, role, profile_photo FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();
}

// Get products with proper error handling
$products = [];
try {
    $query = "SELECT p.*, c.name as category_name, s.name as supplier_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              LEFT JOIN suppliers s ON p.supplier_id = s.id 
              WHERE p.status = 'active' 
              ORDER BY p.name ASC";
    $result = $conn->query($query);
    if ($result) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $products = [];
}

// Get statistics
$stats = ['total' => 0, 'low_stock' => 0, 'out_of_stock' => 0];
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
    if ($result) $stats['total'] = $result->fetch_assoc()['total'];
    
    $result = $conn->query("SELECT COUNT(*) as low_stock FROM products WHERE quantity <= min_stock_level AND quantity > 0 AND status = 'active'");
    if ($result) $stats['low_stock'] = $result->fetch_assoc()['low_stock'];
    
    $result = $conn->query("SELECT COUNT(*) as out_of_stock FROM products WHERE quantity = 0 AND status = 'active'");
    if ($result) $stats['out_of_stock'] = $result->fetch_assoc()['out_of_stock'];
} catch (Exception $e) {
    // Keep default values
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - StockWise Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            min-height: 100vh;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(30, 64, 175, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(5, 150, 105, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(15, 118, 110, 0.1) 0%, transparent 50%);
            z-index: -1;
            animation: backgroundShift 20s ease-in-out infinite;
        }
        
        @keyframes backgroundShift {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(5deg); }
        }
        
        .container { display: flex; min-height: 100vh; }
        
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.1);
        }
        
        .logo {
            text-align: center; padding: 30px 20px; font-size: 1.8rem; font-weight: 700;
            background: rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            display: flex; align-items: center; justify-content: center; gap: 12px;
        }
        
        .logo i {
            background: linear-gradient(135deg, #60a5fa 0%, #34d399 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            font-size: 2rem;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }
        
        .nav-menu { list-style: none; padding: 20px 0; }
        
        .nav-item {
            margin: 5px 15px; border-radius: 12px; transition: all 0.3s ease;
        }
        
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(8px);
        }
        
        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid #60a5fa;
        }
        
        .nav-item a {
            color: rgba(255, 255, 255, 0.9); text-decoration: none;
            display: flex; align-items: center; gap: 15px;
            padding: 16px 20px; font-weight: 500; transition: all 0.3s ease;
        }
        
        .nav-item:hover a { color: white; }
        .nav-item i { width: 20px; text-align: center; font-size: 1.1rem; }
        
        .main-content {
            flex: 1; padding: 40px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }
        
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px); border-radius: 20px; padding: 30px;
            margin-bottom: 30px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .page-header h1 {
            font-size: 2.5rem; font-weight: 700;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            display: flex; align-items: center; gap: 15px;
        }
        
        .actions { display: flex; gap: 15px; }
        
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px; margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 20px; padding: 30px; text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative; overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }
        
        .stat-number {
            font-size: 3rem; font-weight: 800; margin-bottom: 10px;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            font-size: 1.1rem; font-weight: 600; color: #6c757d;
            text-transform: uppercase; letter-spacing: 1px;
        }
        
        .search-section {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 20px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2); overflow: hidden;
            margin-bottom: 25px;
        }
        
        .search-header {
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            color: white; padding: 25px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .search-header h3 {
            font-size: 1.3rem; font-weight: 600;
            display: flex; align-items: center; gap: 12px;
        }
        
        .search-content {
            padding: 25px;
        }
        
        .search-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px; align-items: end;
        }
        
        .form-group {
            display: flex; flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 8px; font-weight: 600; color: #495057; font-size: 0.95rem;
        }
        
        .form-control {
            padding: 12px 16px; border: 2px solid #e9ecef; border-radius: 12px;
            font-size: 1rem; transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
        }
        
        .form-control:focus {
            outline: none; border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); background: white;
        }
        
        .products-table-container {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 20px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2); overflow: hidden;
        }
        
        .table-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 25px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .table-header h3 {
            font-size: 1.5rem; font-weight: 600;
            display: flex; align-items: center; gap: 12px;
        }
        
        .products-table { width: 100%; border-collapse: collapse; }
        
        .products-table th, .products-table td {
            padding: 20px; text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .products-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600; color: #495057; text-transform: uppercase;
            letter-spacing: 0.5px; font-size: 0.9rem;
        }
        
        .products-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: scale(1.01);
        }
        
        .product-image {
            width: 60px; height: 60px; border-radius: 12px;
            object-fit: cover; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .product-info h4 {
            font-weight: 600; color: #2c3e50; margin-bottom: 5px;
        }
        
        .product-info p {
            color: #6c757d; font-size: 0.9rem;
        }
        
        .btn {
            padding: 12px 24px; border: none; border-radius: 12px;
            font-weight: 600; font-size: 0.95rem; cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-decoration: none; display: inline-flex;
            align-items: center; gap: 8px; position: relative; overflow: hidden;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white; box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white; box-shadow: 0 8px 25px rgba(240, 147, 251, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white; box-shadow: 0 8px 25px rgba(250, 112, 154, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white; box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .btn-sm { padding: 8px 16px; font-size: 0.85rem; }
        
        .status-badge {
            padding: 8px 16px; border-radius: 20px; font-size: 0.8rem;
            font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
        }
        
        .status-in-stock {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .status-low-stock {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
        }
        
        .status-out-of-stock {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        
        .price-display {
            font-size: 1.1rem; font-weight: 700; color: #28a745;
        }
        
        .stock-display {
            font-weight: 600;
        }
        
        .fab {
            position: fixed; bottom: 30px; right: 30px;
            width: 60px; height: 60px; border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; font-size: 1.5rem; cursor: pointer;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); z-index: 1000;
        }
        
        .fab:hover {
            transform: scale(1.1) translateY(-5px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.6);
        }
        
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; height: auto; }
            .main-content { padding: 20px; }
            .page-header { flex-direction: column; gap: 20px; text-align: center; }
            .stats-grid { grid-template-columns: 1fr; }
            .search-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-boxes"></i>
                StockWise Pro
            </div>
            
            <!-- User info under StockWise Pro -->
            <?php if ($user_info): ?>
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: rgba(255, 255, 255, 0.1); border-radius: 12px; margin: 15px 20px; border: 1px solid rgba(255, 255, 255, 0.1);">
                <?php 
                // Check for profile photo using the same logic as dashboard
                $show_photo = false;
                $photo_src = '';
                
                if (!empty($user_info['profile_photo'])) {
                    // Try the path as stored in database
                    $photo_path = "../" . $user_info['profile_photo'];
                    if (file_exists($photo_path)) {
                        $show_photo = true;
                        $photo_src = $photo_path;
                    }
                }
                
                if ($show_photo): ?>
                    <img src="<?php echo htmlspecialchars($photo_src); ?>" 
                         alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255, 255, 255, 0.2);">
                <?php else: ?>
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #1e40af 0%, #0f766e 100%); display: flex; align-items: center; justify-content: center; color: white; border: 2px solid rgba(255, 255, 255, 0.2); font-weight: 600; font-size: 1.2rem;">
                        <?php echo strtoupper(substr($user_info['username'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: left;">
                    <span style="display: block; font-weight: 600; font-size: 0.9rem; color: white; margin-bottom: 2px;"><?php echo htmlspecialchars($user_info['username']); ?></span>
                    <span style="color: rgba(255,255,255,0.8); text-transform: capitalize; font-size: 0.75rem; background: rgba(79, 195, 247, 0.2); padding: 2px 8px; border-radius: 8px;"><?php echo ucfirst($user_info['role']); ?></span>
                </div>
            </div>
            <?php endif; ?>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="nav-item active"><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                <li class="nav-item"><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <li class="nav-item"><a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
                <li class="nav-item"><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li class="nav-item"><a href="sales.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li class="nav-item"><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li class="nav-item"><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li class="nav-item"><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                <li class="nav-item"><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-boxes"></i> Products Management</h1>
                    <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 1.1rem;">Manage your inventory products and stock levels</p>
                </div>
                <div class="actions">
                    <a href="add_product.php" class="btn btn-success"><i class="fas fa-plus"></i> Add New Product</a>
                    <button class="btn btn-primary" onclick="exportProducts()"><i class="fas fa-download"></i> Export</button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['low_stock']); ?></div>
                    <div class="stat-label">Low Stock</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['out_of_stock']); ?></div>
                    <div class="stat-label">Out of Stock</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total'] - $stats['low_stock'] - $stats['out_of_stock']); ?></div>
                    <div class="stat-label">In Stock</div>
                </div>
            </div>

            <div class="search-section">
                <div class="search-header">
                    <h3><i class="fas fa-search"></i> Search & Filter</h3>
                </div>
                <div class="search-content">
                    <div class="search-grid">
                        <div class="form-group">
                            <label for="search">Search Products</label>
                            <input type="text" id="search" class="form-control" placeholder="Search by name, SKU, or description..." onkeyup="filterProducts()">
                        </div>
                        <div class="form-group">
                            <label for="categoryFilter">Category</label>
                            <select id="categoryFilter" class="form-control" onchange="filterProducts()">
                                <option value="">All Categories</option>
                                <?php
                                try {
                                    $categories = $conn->query("SELECT * FROM categories ORDER BY name");
                                    while ($category = $categories->fetch_assoc()) {
                                        echo "<option value='{$category['name']}'>" . htmlspecialchars($category['name']) . "</option>";
                                    }
                                } catch (Exception $e) {
                                    // Handle error silently
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="stockFilter">Stock Status</label>
                            <select id="stockFilter" class="form-control" onchange="filterProducts()">
                                <option value="">All Stock Levels</option>
                                <option value="in_stock">In Stock</option>
                                <option value="low_stock">Low Stock</option>
                                <option value="out_of_stock">Out of Stock</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button class="btn btn-secondary" onclick="clearFilters()" style="width: 100%;">
                                <i class="fas fa-times"></i> Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="products-table-container">
                <div class="table-header">
                    <h3><i class="fas fa-list"></i> Products Inventory</h3>
                    <div class="actions">
                        <button class="btn btn-success btn-sm" onclick="bulkActions()">
                            <i class="fas fa-tasks"></i> Bulk Actions
                        </button>
                    </div>
                </div>
                
                <table class="products-table" id="productsTable">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Info</th>
                            <th>Category</th>
                            <th>Supplier</th>
                            <th>Stock</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr data-min-stock="<?php echo $product['min_stock_level']; ?>">
                            <td>
                                <?php if ($product['image'] && file_exists("../assets/images/products/" . $product['image'])): ?>
                                    <img src="../assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                         class="product-image" alt="Product Image">
                                <?php else: ?>
                                    <div class="product-image" style="background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%); display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-image" style="color: #6c757d;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="product-info">
                                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <p>SKU: <?php echo htmlspecialchars($product['sku']); ?></p>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                            <td><?php echo htmlspecialchars($product['supplier_name'] ?? 'No Supplier'); ?></td>
                            <td>
                                <div class="stock-display">
                                    <?php echo number_format($product['quantity']); ?>
                                    <?php
                                    if ($product['quantity'] == 0) {
                                        echo ' <span class="status-badge status-out-of-stock">OUT</span>';
                                    } elseif ($product['quantity'] <= $product['min_stock_level']) {
                                        echo ' <span class="status-badge status-low-stock">LOW</span>';
                                    } else {
                                        echo ' <span class="status-badge status-in-stock">OK</span>';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td>
                                <div class="price-display">$<?php echo number_format($product['price'], 2); ?></div>
                            </td>
                            <td>
                                <?php
                                if ($product['quantity'] == 0) {
                                    echo '<span class="status-badge status-out-of-stock">Out of Stock</span>';
                                } elseif ($product['quantity'] <= $product['min_stock_level']) {
                                    echo '<span class="status-badge status-low-stock">Low Stock</span>';
                                } else {
                                    echo '<span class="status-badge status-in-stock">In Stock</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-warning btn-sm" onclick="updateStock(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-boxes"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <button class="fab" onclick="window.location.href='add_product.php'" title="Add New Product">
        <i class="fas fa-plus"></i>
    </button>

    <script>
        function filterProducts() {
            const search = document.getElementById('search').value.toLowerCase();
            const categoryFilter = document.getElementById('categoryFilter').value.toLowerCase();
            const stockFilter = document.getElementById('stockFilter').value;
            const rows = document.querySelectorAll('#productsTable tbody tr');
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const sku = row.cells[1].textContent.toLowerCase();
                const category = row.cells[2].textContent.toLowerCase();
                const quantity = parseInt(row.cells[4].textContent);
                const minStock = parseInt(row.getAttribute('data-min-stock') || 0);
                
                let showRow = true;
                
                if (search && !name.includes(search) && !sku.includes(search)) {
                    showRow = false;
                }
                
                if (categoryFilter && !category.includes(categoryFilter)) {
                    showRow = false;
                }
                
                if (stockFilter) {
                    if (stockFilter === 'out_of_stock' && quantity > 0) showRow = false;
                    if (stockFilter === 'low_stock' && (quantity > minStock || quantity === 0)) showRow = false;
                    if (stockFilter === 'in_stock' && quantity <= minStock) showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
                if (showRow) visibleCount++;
            });
        }
        
        function clearFilters() {
            document.getElementById('search').value = '';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('stockFilter').value = '';
            filterProducts();
        }
        
        function exportProducts() {
            alert('Export functionality coming soon!');
        }
        
        function bulkActions() {
            alert('Bulk actions functionality coming soon!');
        }
        
        function updateStock(productId) {
            alert('Update stock for product ' + productId + ' - Feature coming soon!');
        }
        
        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                alert('Delete product ' + productId + ' - Feature coming soon!');
            }
        }
    </script>
</body>
</html>