<?php
/**
 * Enhanced Categories Management Page with Full CRUD Operations
 */
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

// Suppress PHP warnings and errors for clean display
error_reporting(0);
ini_set('display_errors', 0);

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add_category') {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $status = $_POST['status'];
            
            if (!empty($name)) {
                // Check if category exists
                $check_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
                $check_stmt->bind_param("s", $name);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error_message = 'Category name already exists.';
                } else {
                    $stmt = $conn->prepare("INSERT INTO categories (name, description, status, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->bind_param("sss", $name, $description, $status);
                    
                    if ($stmt->execute()) {
                        $success_message = 'Category created successfully!';
                    } else {
                        $error_message = 'Failed to create category.';
                    }
                }
            } else {
                $error_message = 'Category name is required.';
            }
        }
        
        if ($_POST['action'] == 'update_category') {
            $category_id = $_POST['category_id'];
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $status = $_POST['status'];
            
            if (!empty($name)) {
                // Check if name exists for other categories
                $check_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
                $check_stmt->bind_param("si", $name, $category_id);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error_message = 'Category name already exists.';
                } else {
                    $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, status = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $name, $description, $status, $category_id);
                    
                    if ($stmt->execute()) {
                        $success_message = 'Category updated successfully!';
                    } else {
                        $error_message = 'Failed to update category.';
                    }
                }
            } else {
                $error_message = 'Category name is required.';
            }
        }
        
        if ($_POST['action'] == 'delete_category') {
            $category_id = $_POST['category_id'];
            
            // Check if category is used by products
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
            $check_stmt->bind_param("i", $category_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            
            if ($count > 0) {
                $error_message = "Cannot delete category. It is used by $count product(s).";
            } else {
                $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->bind_param("i", $category_id);
                
                if ($stmt->execute()) {
                    $success_message = 'Category deleted successfully!';
                } else {
                    $error_message = 'Failed to delete category.';
                }
            }
        }
    }
}

// Get categories with product count
$categories = [];
try {
    $query = "SELECT c.*, COUNT(p.id) as product_count 
              FROM categories c 
              LEFT JOIN products p ON c.id = p.category_id 
              GROUP BY c.id 
              ORDER BY c.name ASC";
    $result = $conn->query($query);
    if ($result) {
        $categories = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $categories = [];
}

// Get statistics
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM categories");
    if ($result) $stats['total'] = $result->fetch_assoc()['total'];
    
    $result = $conn->query("SELECT COUNT(*) as active FROM categories WHERE status = 'active'");
    if ($result) $stats['active'] = $result->fetch_assoc()['active'];
    
    $result = $conn->query("SELECT COUNT(*) as inactive FROM categories WHERE status = 'inactive'");
    if ($result) $stats['inactive'] = $result->fetch_assoc()['inactive'];
} catch (Exception $e) {
    // Keep default values
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - StockWise Pro</title>
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
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%);
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
            border-left: 4px solid #4fc3f7;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            font-size: 1.1rem; font-weight: 600; color: #6c757d;
            text-transform: uppercase; letter-spacing: 1px;
        }
        
        .categories-table-container {
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
        
        .categories-table { width: 100%; border-collapse: collapse; }
        
        .categories-table th, .categories-table td {
            padding: 20px; text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .categories-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600; color: #495057; text-transform: uppercase;
            letter-spacing: 0.5px; font-size: 0.9rem;
        }
        
        .categories-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: scale(1.01);
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
        
        .status-active {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .status-inactive {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        
        .alert {
            padding: 20px 25px; border-radius: 15px; margin-bottom: 25px;
            display: flex; align-items: center; gap: 15px; font-weight: 500;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(212, 237, 218, 0.9) 0%, rgba(195, 230, 203, 0.9) 100%);
            color: #155724; border-left: 5px solid #28a745;
        }
        
        .alert-error {
            background: linear-gradient(135deg, rgba(248, 215, 218, 0.9) 0%, rgba(245, 198, 203, 0.9) 100%);
            color: #721c24; border-left: 5px solid #dc3545;
        }
        
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            margin: 5% auto; padding: 30px; border-radius: 20px; width: 90%;
            max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .modal-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .modal-header h3 {
            font-size: 1.5rem; font-weight: 600;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            display: flex; align-items: center; gap: 10px;
        }
        
        .close {
            color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .close:hover { color: #000; }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block; margin-bottom: 8px; font-weight: 600;
            color: #495057; font-size: 0.95rem;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 12px 16px; border: 2px solid #e9ecef;
            border-radius: 12px; font-size: 1rem; transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); background: white;
        }
        
        .form-group textarea {
            resize: vertical; min-height: 80px;
        }
        
        .action-buttons { display: flex; gap: 8px; }
        
        .empty-state {
            text-align: center; padding: 80px 30px; color: #6c757d;
            background: rgba(255, 255, 255, 0.5); border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        
        .empty-state i {
            font-size: 5rem; color: #dee2e6; margin-bottom: 25px;
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        
        .empty-state h3 {
            margin-bottom: 15px; color: #495057; font-size: 1.5rem; font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; height: auto; }
            .main-content { padding: 20px; }
            .page-header { flex-direction: column; gap: 20px; text-align: center; }
            .stats-grid { grid-template-columns: 1fr; }
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
                <li class="nav-item"><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                <li class="nav-item active"><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
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
                    <h1><i class="fas fa-tags"></i> Categories Management</h1>
                    <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 1.1rem;">Organize your products with categories</p>
                </div>
                <div class="actions">
                    <button onclick="showAddCategoryModal()" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add New Category
                    </button>
                </div>
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
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                    <div class="stat-label">Total Categories</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['active']); ?></div>
                    <div class="stat-label">Active Categories</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['inactive']); ?></div>
                    <div class="stat-label">Inactive Categories</div>
                </div>
            </div>
            
            <div class="categories-table-container">
                <div class="table-header">
                    <h3><i class="fas fa-list"></i> Product Categories</h3>
                </div>
                
                <?php if (empty($categories)): ?>
                    <div class="empty-state">
                        <i class="fas fa-tags"></i>
                        <h3>No Categories Found</h3>
                        <p>No categories created yet. Create your first category to get started.</p>
                        <button onclick="showAddCategoryModal()" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Category
                        </button>
                    </div>
                <?php else: ?>
                    <table class="categories-table">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($category['description'] ?? 'No description'); ?></td>
                                    <td>
                                        <span class="status-badge status-active"><?php echo $category['product_count']; ?> products</span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $category['status']; ?>">
                                            <?php echo ucfirst($category['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>', '<?php echo addslashes($category['description']); ?>', '<?php echo $category['status']; ?>')" class="btn btn-warning btn-sm" title="Edit Category">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>', <?php echo $category['product_count']; ?>)" class="btn btn-danger btn-sm" title="Delete Category">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Add New Category</h3>
                <span class="close" onclick="closeModal('addCategoryModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_category">
                
                <div class="form-group">
                    <label for="add_name">Category Name *</label>
                    <input type="text" id="add_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="add_description">Description</label>
                    <textarea id="add_description" name="description" placeholder="Optional description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="add_status">Status</label>
                    <select id="add_status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="closeModal('addCategoryModal')" class="btn" style="background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Category
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div id="editCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Category</h3>
                <span class="close" onclick="closeModal('editCategoryModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_category">
                <input type="hidden" id="edit_category_id" name="category_id">
                
                <div class="form-group">
                    <label for="edit_name">Category Name *</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" placeholder="Optional description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="closeModal('editCategoryModal')" class="btn" style="background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showAddCategoryModal() {
            document.getElementById('addCategoryModal').style.display = 'block';
        }
        
        function editCategory(id, name, description, status) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_status').value = status;
            document.getElementById('editCategoryModal').style.display = 'block';
        }
        
        function deleteCategory(id, name, productCount) {
            if (productCount > 0) {
                alert(`Cannot delete "${name}" because it has ${productCount} product(s) assigned to it.`);
                return;
            }
            
            if (confirm(`Are you sure you want to delete the category "${name}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="category_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Profile Photo Upload Functions
        function openPhotoUpload() {
            console.log('Opening photo upload...');
            document.getElementById('profilePhotoInput').click();
        }

        function uploadProfilePhoto() {
            console.log('Upload function called');
            const fileInput = document.getElementById('profilePhotoInput');
            const file = fileInput.files[0];
            
            if (!file) {
                console.log('No file selected');
                return;
            }
            
            console.log('File selected:', file.name, file.type, file.size);
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showUploadStatus('Please select a valid image file (JPG, PNG, GIF)', 'error');
                return;
            }
            
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                showUploadStatus('File size must be less than 5MB', 'error');
                return;
            }
            
            // Show loading
            showUploadStatus('Uploading photo...', 'loading');
            
            // Create FormData
            const formData = new FormData();
            formData.append('profile_photo', file);
            
            console.log('Starting upload...');
            
            // Upload via AJAX
            fetch('upload_profile_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response received:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Upload response:', data);
                if (data.success) {
                    // Update the profile photo display
                    const photoElement = document.getElementById('userProfilePhoto');
                    if (photoElement.tagName === 'IMG') {
                        photoElement.src = data.photo_url + '?t=' + new Date().getTime();
                    } else {
                        // Replace the div with an img
                        const newImg = document.createElement('img');
                        newImg.id = 'userProfilePhoto';
                        newImg.src = data.photo_url + '?t=' + new Date().getTime();
                        newImg.alt = 'Profile';
                        newImg.style.cssText = 'width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255, 255, 255, 0.2); transition: all 0.3s ease;';
                        photoElement.parentNode.replaceChild(newImg, photoElement);
                    }
                    
                    showUploadStatus('Photo updated successfully!', 'success');
                } else {
                    showUploadStatus(data.message || 'Upload failed', 'error');
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                showUploadStatus('Upload failed. Please try again.', 'error');
            });
            
            // Clear the input
            fileInput.value = '';
        }

        function showUploadStatus(message, type) {
            const statusDiv = document.getElementById('uploadStatus');
            if (!statusDiv) return;
            
            statusDiv.textContent = message;
            statusDiv.style.display = 'block';
            
            // Set colors based on type
            if (type === 'success') {
                statusDiv.style.background = 'rgba(40, 167, 69, 0.2)';
                statusDiv.style.color = '#28a745';
                statusDiv.style.border = '1px solid rgba(40, 167, 69, 0.3)';
            } else if (type === 'error') {
                statusDiv.style.background = 'rgba(220, 53, 69, 0.2)';
                statusDiv.style.color = '#dc3545';
                statusDiv.style.border = '1px solid rgba(220, 53, 69, 0.3)';
            } else if (type === 'loading') {
                statusDiv.style.background = 'rgba(255, 193, 7, 0.2)';
                statusDiv.style.color = '#ffc107';
                statusDiv.style.border = '1px solid rgba(255, 193, 7, 0.3)';
            }
            
            // Auto hide after 3 seconds (except loading)
            if (type !== 'loading') {
                setTimeout(() => {
                    statusDiv.style.display = 'none';
                }, 3000);
            }
        }
    </script>

    <!-- Profile Photo Upload Styles -->
    <style>
    .profile-photo-container:hover .upload-overlay {
        opacity: 1 !important;
    }

    .profile-photo-container:hover #userProfilePhoto {
        transform: scale(1.05);
        filter: brightness(0.8);
    }

    .header-user-info button:hover {
        background: rgba(255,255,255,0.2) !important;
        transform: translateY(-1px);
    }
    </style>
</body>
</html>