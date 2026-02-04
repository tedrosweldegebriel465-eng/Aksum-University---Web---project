<?php
/**
 * Dashboard Page - StockWise Pro
 * Role-Based Access Control Enabled
 */
$page_title = 'Dashboard';
session_start();

// Include role checking system
require_once '../includes/role_check.php';
require_once '../config/db.php';

// Require login
requireLogin();

// Get user info for display
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT username, role, profile_photo FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();
}

// Get dashboard statistics with error handling
$stats = [];

// Total products
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
    $stats['total_products'] = $result ? $result->fetch_assoc()['count'] : 0;
} catch (Exception $e) {
    $stats['total_products'] = 0;
}

// Low stock products
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity <= min_stock_level AND status = 'active'");
    $stats['low_stock'] = $result ? $result->fetch_assoc()['count'] : 0;
} catch (Exception $e) {
    $stats['low_stock'] = 0;
}

// Total suppliers
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM suppliers WHERE status = 'active'");
    $stats['total_suppliers'] = $result ? $result->fetch_assoc()['count'] : 0;
} catch (Exception $e) {
    $stats['total_suppliers'] = 0;
}

// Total categories
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM categories");
    $stats['total_categories'] = $result ? $result->fetch_assoc()['count'] : 0;
} catch (Exception $e) {
    $stats['total_categories'] = 0;
}

// Today's sales
try {
    $result = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM sales WHERE DATE(sale_date) = CURDATE()");
    $stats['today_sales'] = $result ? $result->fetch_assoc()['total'] : 0;
} catch (Exception $e) {
    $stats['today_sales'] = 0;
}

// Monthly revenue
try {
    $result = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())");
    $stats['monthly_revenue'] = $result ? $result->fetch_assoc()['total'] : 0;
} catch (Exception $e) {
    $stats['monthly_revenue'] = 0;
}

// Recent activities
$recent_activities = [];
try {
    $stmt = $conn->prepare("SELECT al.*, u.username FROM activity_logs al JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $recent_activities = [];
}

// Low stock products
$low_stock_products = [];
try {
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.quantity <= p.min_stock_level AND p.status = 'active' ORDER BY (p.quantity / p.min_stock_level) ASC LIMIT 5");
    $stmt->execute();
    $low_stock_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $low_stock_products = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - StockWise Pro</title>
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
            display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px; margin-bottom: 20px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 12px; padding: 15px; text-align: center;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative; overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: white; margin: 0 auto 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .stat-icon.bg-primary { background: linear-gradient(135deg, #1e40af 0%, #0f766e 100%); }
        .stat-icon.bg-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-icon.bg-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-icon.bg-danger { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        
        .stat-number {
            font-size: 1.4rem; font-weight: 800; margin-bottom: 6px;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            font-size: 0.7rem; font-weight: 600; color: #6c757d;
        }
        
        .content-section {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 20px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2); overflow: hidden;
            margin-bottom: 30px;
        }
        
        .section-header {
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            color: white; padding: 25px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .section-header h3 {
            font-size: 1.3rem; font-weight: 600;
            display: flex; align-items: center; gap: 12px;
        }
        
        .btn {
            padding: 12px 24px; border: none; border-radius: 12px;
            font-weight: 600; font-size: 0.95rem; cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-decoration: none; display: inline-flex;
            align-items: center; gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 100%);
            color: white; box-shadow: 0 8px 25px rgba(30, 64, 175, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white; box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .activity-item, .product-item {
            padding: 20px 30px; border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .activity-item:last-child, .product-item:last-child {
            border-bottom: none;
        }
        
        .activity-info h4, .product-info h4 {
            font-weight: 600; color: #2c3e50; margin-bottom: 5px;
        }
        
        .activity-info p, .product-info p {
            color: #6c757d; font-size: 0.9rem;
        }
        
        .activity-time, .product-stock {
            color: #6c757d; font-size: 0.85rem;
        }
        
        .stock-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404; padding: 4px 12px; border-radius: 15px;
            font-size: 0.8rem; font-weight: 600;
        }
        
        .empty-state {
            padding: 40px; text-align: center; color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem; color: #dee2e6; margin-bottom: 20px;
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
                // Check for profile photo using the same logic as profile page
                $show_photo = false;
                $photo_src = '';
                
                if (!empty($user_info['profile_photo'])) {
                    // Try the path as stored in database (same as profile page)
                    $photo_path = "../" . $user_info['profile_photo'];
                    if (file_exists($photo_path)) {
                        $show_photo = true;
                        $photo_src = $photo_path;
                    }
                }
                
                if ($show_photo): ?>
                    <img src="<?php echo htmlspecialchars($photo_src); ?>" 
                         alt="Profile" 
                         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255, 255, 255, 0.2);">
                <?php else: ?>
                    <!-- Show user initial with professional styling -->
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #1e40af 0%, #0f766e 100%); display: flex; align-items: center; justify-content: center; color: white; border: 2px solid rgba(255, 255, 255, 0.2); font-weight: 600; font-size: 1.2rem;">
                        <?php echo strtoupper(substr($user_info['username'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: left;">
                    <span style="display: block; font-weight: 600; font-size: 0.9rem; color: white; margin-bottom: 2px;"><?php echo htmlspecialchars($user_info['username']); ?></span>
                    <span style="color: white; text-transform: capitalize; font-size: 0.75rem; 
                        background: <?php echo isAdmin() ? 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)' : 'linear-gradient(135deg, #059669 0%, #047857 100%)'; ?>; 
                        padding: 3px 10px; border-radius: 12px; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                        <?php echo isAdmin() ? '👑 Administrator' : '👤 Staff Member'; ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
            <ul class="nav-menu">
                <li class="nav-item active"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                
                <?php if (canAccess('products')): ?>
                <li class="nav-item"><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                <?php endif; ?>
                
                <?php if (canAccess('categories')): ?>
                <li class="nav-item"><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <?php endif; ?>
                
                <?php if (canAccess('suppliers')): ?>
                <li class="nav-item"><a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
                <?php endif; ?>
                
                <?php if (canAccess('orders')): ?>
                <li class="nav-item"><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <?php endif; ?>
                
                <?php if (canAccess('sales')): ?>
                <li class="nav-item"><a href="sales.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <?php endif; ?>
                
                <?php if (canAccess('reports')): ?>
                <li class="nav-item"><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <?php else: ?>
                <li class="nav-item" style="opacity: 0.5;" title="Admin access required">
                    <a href="#" onclick="alert('Access denied: Admin role required'); return false;">
                        <i class="fas fa-chart-bar"></i> Reports <i class="fas fa-lock" style="font-size: 0.8rem; margin-left: auto;"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (canAccess('users')): ?>
                <li class="nav-item"><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <?php else: ?>
                <li class="nav-item" style="opacity: 0.5;" title="Admin access required">
                    <a href="#" onclick="alert('Access denied: Admin role required'); return false;">
                        <i class="fas fa-users"></i> Users <i class="fas fa-lock" style="font-size: 0.8rem; margin-left: auto;"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item"><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                <li class="nav-item"><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                    <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 1.1rem;">Welcome back! Here's what's happening with your inventory.</p>
                </div>
                <div class="actions">
                    <a href="add_product.php" class="btn btn-success"><i class="fas fa-plus"></i> Add Product</a>
                    <button class="btn btn-primary" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['total_products']); ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['low_stock']); ?></div>
                    <div class="stat-label">Low Stock Items</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['total_suppliers']); ?></div>
                    <div class="stat-label">Active Suppliers</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-danger">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['total_categories']); ?></div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>

            <!-- Revenue Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-number">$<?php echo number_format($stats['today_sales'], 2); ?></div>
                    <div class="stat-label">Today's Sales</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #1e40af 0%, #0f766e 100%);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-number">$<?php echo number_format($stats['monthly_revenue'], 2); ?></div>
                    <div class="stat-label">Monthly Revenue</div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="content-section">
                <div class="section-header">
                    <h3><i class="fas fa-clock"></i> Recent Activities</h3>
                    <a href="activity_logs.php" class="btn btn-primary">View All</a>
                </div>
                
                <?php if (empty($recent_activities)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clock"></i>
                        <h3>No Recent Activities</h3>
                        <p>Activities will appear here as users interact with the system.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <h4><?php echo htmlspecialchars($activity['action']); ?></h4>
                            <p><?php echo htmlspecialchars($activity['details'] ?? 'No details available'); ?></p>
                        </div>
                        <div class="activity-time">
                            <?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Low Stock Alert -->
            <?php if (!empty($low_stock_products)): ?>
            <div class="content-section">
                <div class="section-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h3>
                    <a href="products.php?filter=low_stock" class="btn btn-primary">View All</a>
                </div>
                
                <?php foreach ($low_stock_products as $product): ?>
                <div class="product-item">
                    <div class="product-info">
                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p><?php echo htmlspecialchars($product['category_name'] ?? 'No category'); ?> | SKU: <?php echo htmlspecialchars($product['sku']); ?></p>
                    </div>
                    <div class="product-stock">
                        <span class="stock-warning">
                            <?php echo $product['quantity']; ?> left (Min: <?php echo $product['min_stock_level']; ?>)
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>