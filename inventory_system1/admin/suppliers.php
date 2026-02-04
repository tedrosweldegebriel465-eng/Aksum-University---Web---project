<?php
/**
 * Enhanced Suppliers Management Page with Full CRUD Operations
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
        if ($_POST['action'] == 'add_supplier') {
            $name = trim($_POST['name']);
            $contact_person = trim($_POST['contact_person']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);
            $status = $_POST['status'];
            
            if (!empty($name)) {
                // Check if supplier exists
                $check_stmt = $conn->prepare("SELECT id FROM suppliers WHERE name = ?");
                $check_stmt->bind_param("s", $name);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error_message = 'Supplier name already exists.';
                } else {
                    $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("ssssss", $name, $contact_person, $email, $phone, $address, $status);
                    
                    if ($stmt->execute()) {
                        $success_message = 'Supplier created successfully!';
                    } else {
                        $error_message = 'Failed to create supplier.';
                    }
                }
            } else {
                $error_message = 'Supplier name is required.';
            }
        }
        
        if ($_POST['action'] == 'update_supplier') {
            $supplier_id = $_POST['supplier_id'];
            $name = trim($_POST['name']);
            $contact_person = trim($_POST['contact_person']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);
            $status = $_POST['status'];
            
            if (!empty($name)) {
                // Check if name exists for other suppliers
                $check_stmt = $conn->prepare("SELECT id FROM suppliers WHERE name = ? AND id != ?");
                $check_stmt->bind_param("si", $name, $supplier_id);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error_message = 'Supplier name already exists.';
                } else {
                    $stmt = $conn->prepare("UPDATE suppliers SET name = ?, contact_person = ?, email = ?, phone = ?, address = ?, status = ? WHERE id = ?");
                    $stmt->bind_param("ssssssi", $name, $contact_person, $email, $phone, $address, $status, $supplier_id);
                    
                    if ($stmt->execute()) {
                        $success_message = 'Supplier updated successfully!';
                    } else {
                        $error_message = 'Failed to update supplier.';
                    }
                }
            } else {
                $error_message = 'Supplier name is required.';
            }
        }
        
        if ($_POST['action'] == 'delete_supplier') {
            $supplier_id = $_POST['supplier_id'];
            
            // Check if supplier is used by products
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE supplier_id = ?");
            $check_stmt->bind_param("i", $supplier_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            
            if ($count > 0) {
                $error_message = "Cannot delete supplier. It is used by $count product(s).";
            } else {
                $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
                $stmt->bind_param("i", $supplier_id);
                
                if ($stmt->execute()) {
                    $success_message = 'Supplier deleted successfully!';
                } else {
                    $error_message = 'Failed to delete supplier.';
                }
            }
        }
    }
}

// Get suppliers with product count
$suppliers = [];
try {
    $query = "SELECT s.*, COUNT(p.id) as product_count 
              FROM suppliers s 
              LEFT JOIN products p ON s.id = p.supplier_id 
              GROUP BY s.id 
              ORDER BY s.name ASC";
    $result = $conn->query($query);
    if ($result) {
        $suppliers = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $suppliers = [];
}

// Get statistics
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM suppliers");
    if ($result) $stats['total'] = $result->fetch_assoc()['total'];
    
    $result = $conn->query("SELECT COUNT(*) as active FROM suppliers WHERE status = 'active'");
    if ($result) $stats['active'] = $result->fetch_assoc()['active'];
    
    $result = $conn->query("SELECT COUNT(*) as inactive FROM suppliers WHERE status = 'inactive'");
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
    <title>Suppliers Management - StockWise Pro</title>
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
            border-radius: 15px; padding: 20px; text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative; overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
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
        
        .suppliers-table-container {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 20px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2); overflow: hidden;
        }
        
        .table-header {
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            color: white; padding: 25px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .table-header h3 {
            font-size: 1.5rem; font-weight: 600;
            display: flex; align-items: center; gap: 12px;
        }
        
        .suppliers-table { width: 100%; border-collapse: collapse; }
        
        .suppliers-table th, .suppliers-table td {
            padding: 20px; text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .suppliers-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600; color: #495057; text-transform: uppercase;
            letter-spacing: 0.5px; font-size: 0.9rem;
        }
        
        .suppliers-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.05) 0%, rgba(15, 118, 110, 0.05) 100%);
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
            background: linear-gradient(135deg, #1e40af 0%, #0f766e);
            color: white; box-shadow: 0 8px 25px rgba(30, 64, 175, 0.3);
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
            margin: 3% auto; padding: 30px; border-radius: 20px; width: 90%;
            max-width: 600px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid rgba(255, 255, 255, 0.2); max-height: 90vh; overflow-y: auto;
        }
        
        .modal-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .modal-header h3 {
            font-size: 1.5rem; font-weight: 600;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            display: flex; align-items: center; gap: 10px;
        }
        
        .close {
            color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .close:hover { color: #000; }
        
        .form-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; align-items: end;
        }
        
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
            outline: none; border-color: #1e40af;
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1); background: white;
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
            .form-row { grid-template-columns: 1fr; }
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
                <li class="nav-item"><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <li class="nav-item active"><a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
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
                    <h1><i class="fas fa-truck"></i> Suppliers Management</h1>
                    <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 1.1rem;">Manage your product suppliers and vendors</p>
                </div>
                <div class="actions">
                    <button onclick="showAddSupplierModal()" class="btn btn-add-supplier">
                        <i class="fas fa-plus"></i> Add New Supplier
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
                    <div class="stat-label">Total Suppliers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['active']); ?></div>
                    <div class="stat-label">Active Suppliers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['inactive']); ?></div>
                    <div class="stat-label">Inactive Suppliers</div>
                </div>
            </div>
            
            <div class="suppliers-table-container">
                <div class="table-header">
                    <h3><i class="fas fa-list"></i> Suppliers List</h3>
                </div>
                
                <?php if (empty($suppliers)): ?>
                    <div class="empty-state">
                        <i class="fas fa-truck"></i>
                        <h3>No Suppliers Found</h3>
                        <p>No suppliers added yet. Add your first supplier to get started.</p>
                        <button onclick="showAddSupplierModal()" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Supplier
                        </button>
                    </div>
                <?php else: ?>
                    <table class="suppliers-table">
                        <thead>
                            <tr>
                                <th>Supplier Name</th>
                                <th>Contact Person</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $supplier): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($supplier['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($supplier['contact_person'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge status-active"><?php echo $supplier['product_count']; ?> products</span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $supplier['status']; ?>">
                                            <?php echo ucfirst($supplier['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="editSupplier(<?php echo $supplier['id']; ?>, '<?php echo addslashes($supplier['name']); ?>', '<?php echo addslashes($supplier['contact_person']); ?>', '<?php echo addslashes($supplier['email']); ?>', '<?php echo addslashes($supplier['phone']); ?>', '<?php echo addslashes($supplier['address']); ?>', '<?php echo $supplier['status']; ?>')" class="btn btn-warning btn-sm" title="Edit Supplier">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteSupplier(<?php echo $supplier['id']; ?>, '<?php echo addslashes($supplier['name']); ?>', <?php echo $supplier['product_count']; ?>)" class="btn btn-danger btn-sm" title="Delete Supplier">
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
    
    <!-- Add Supplier Modal -->
    <div id="addSupplierModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Add New Supplier</h3>
                <span class="close" onclick="closeModal('addSupplierModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_supplier">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="add_name">Supplier Name *</label>
                        <input type="text" id="add_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_contact_person">Contact Person</label>
                        <input type="text" id="add_contact_person" name="contact_person">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="add_email">Email</label>
                        <input type="email" id="add_email" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="add_phone">Phone</label>
                        <input type="text" id="add_phone" name="phone">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="add_address">Address</label>
                    <textarea id="add_address" name="address" placeholder="Complete address"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="add_status">Status</label>
                    <select id="add_status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="closeModal('addSupplierModal')" class="btn" style="background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Supplier Modal -->
    <div id="editSupplierModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Supplier</h3>
                <span class="close" onclick="closeModal('editSupplierModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_supplier">
                <input type="hidden" id="edit_supplier_id" name="supplier_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_name">Supplier Name *</label>
                        <input type="text" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_contact_person">Contact Person</label>
                        <input type="text" id="edit_contact_person" name="contact_person">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_phone">Phone</label>
                        <input type="text" id="edit_phone" name="phone">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_address">Address</label>
                    <textarea id="edit_address" name="address" placeholder="Complete address"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="closeModal('editSupplierModal')" class="btn" style="background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showAddSupplierModal() {
            document.getElementById('addSupplierModal').style.display = 'block';
        }
        
        function editSupplier(id, name, contactPerson, email, phone, address, status) {
            document.getElementById('edit_supplier_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_contact_person').value = contactPerson || '';
            document.getElementById('edit_email').value = email || '';
            document.getElementById('edit_phone').value = phone || '';
            document.getElementById('edit_address').value = address || '';
            document.getElementById('edit_status').value = status;
            document.getElementById('editSupplierModal').style.display = 'block';
        }
        
        function deleteSupplier(id, name, productCount) {
            if (productCount > 0) {
                alert(`Cannot delete "${name}" because it has ${productCount} product(s) assigned to it.`);
                return;
            }
            
            if (confirm(`Are you sure you want to delete the supplier "${name}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_supplier">
                    <input type="hidden" name="supplier_id" value="${id}">
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
    </script>
</body>
</html>