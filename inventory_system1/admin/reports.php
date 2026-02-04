<?php
/**
 * Enhanced Reports & Analytics Page
 * Clean and professional design
 * Admin Access Required
 */
session_start();

// Include role checking system
require_once '../includes/role_check.php';
require_once '../config/db.php';

// Require admin access for reports
requireAdmin();

// Get user info for display
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT username, role, profile_photo FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();
}

// Suppress PHP warnings for clean display
error_reporting(0);
ini_set('display_errors', 0);

// Get comprehensive stats for reports
$stats = [];
try {
    // Products stats
    $result = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
    $stats['products'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as total FROM products WHERE quantity <= 10 AND status = 'active'");
    $stats['low_stock'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Sales stats
    $result = $conn->query("SELECT COUNT(*) as total FROM sales WHERE status != 'voided'");
    $stats['sales'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as total FROM sales WHERE DATE(sale_date) = CURDATE() AND status != 'voided'");
    $stats['today_sales'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Orders stats
    $result = $conn->query("SELECT COUNT(*) as total FROM orders");
    $stats['orders'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Revenue stats
    $result = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as revenue FROM sales WHERE status != 'voided'");
    $stats['revenue'] = $result ? $result->fetch_assoc()['revenue'] : 0;
    
    $result = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as revenue FROM sales WHERE DATE(sale_date) = CURDATE() AND status != 'voided'");
    $stats['today_revenue'] = $result ? $result->fetch_assoc()['revenue'] : 0;
    
    $result = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as revenue FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE()) AND status != 'voided'");
    $stats['monthly_revenue'] = $result ? $result->fetch_assoc()['revenue'] : 0;
    
} catch (Exception $e) {
    $stats = ['products' => 0, 'sales' => 0, 'orders' => 0, 'revenue' => 0, 'low_stock' => 0, 'today_sales' => 0, 'today_revenue' => 0, 'monthly_revenue' => 0];
}

$page_title = "Reports & Analytics";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - StockWise Pro</title>
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
            display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px; margin-bottom: 30px;
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
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }
        
        .stat-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: white; margin: 0 auto 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .stat-icon.bg-primary { background: linear-gradient(135deg, #1e40af 0%, #0f766e 100%); }
        .stat-icon.bg-success { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.bg-warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
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
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white; box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .reports-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px; padding: 30px;
        }
        
        .report-card {
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
            border-radius: 18px; padding: 30px; text-align: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative; overflow: hidden;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }
        
        .report-icon {
            font-size: 3.5rem; margin-bottom: 20px;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            display: block;
        }
        
        .report-title {
            font-size: 1.4rem; font-weight: 700; margin-bottom: 12px;
            color: #2c3e50;
        }
        
        .report-description {
            color: #6c757d; margin-bottom: 25px; line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .report-actions {
            display: flex; gap: 12px; justify-content: center;
            flex-wrap: wrap;
        }
        
        .alert {
            padding: 20px 25px; border-radius: 15px; margin-bottom: 25px;
            display: flex; align-items: center; gap: 15px; font-weight: 500;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(209, 236, 241, 0.9) 0%, rgba(191, 227, 234, 0.9) 100%);
            color: #0c5460; border-left: 5px solid #17a2b8;
        }
        
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; height: auto; }
            .main-content { padding: 20px; }
            .page-header { flex-direction: column; gap: 20px; text-align: center; }
            .stats-grid { grid-template-columns: 1fr; }
            .reports-grid { grid-template-columns: 1fr; padding: 20px; }
            .report-card { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-boxes"></i> StockWise Pro
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
                <li class="nav-item"><a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
                <li class="nav-item"><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li class="nav-item"><a href="sales.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li class="nav-item active"><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li class="nav-item"><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li class="nav-item"><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                <li class="nav-item"><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">

            <div class="page-header">
                <div>
                    <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
                    <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 1.1rem;">Comprehensive business insights and data analysis</p>
                </div>
                <div class="actions">
                    <button onclick="exportAllData()" class="btn btn-export">
                        <i class="fas fa-download"></i> Export All Data
                    </button>
                    <button onclick="printReport()" class="btn btn-print">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </div>

            <!-- Statistics Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['products']); ?></div>
                    <div class="stat-label">Active Products</div>
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
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['sales']); ?></div>
                    <div class="stat-label">Total Sales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['today_sales']); ?></div>
                    <div class="stat-label">Today's Sales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-number">$<?php echo number_format($stats['revenue'], 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-number">$<?php echo number_format($stats['today_revenue'], 2); ?></div>
                    <div class="stat-label">Today's Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-number">$<?php echo number_format($stats['monthly_revenue'], 2); ?></div>
                    <div class="stat-label">Monthly Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['orders']); ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>

            <!-- Available Reports -->
            <div class="content-section">
                <div class="section-header">
                    <h3><i class="fas fa-file-chart-line"></i> Available Reports</h3>
                </div>
    
    <div class="reports-grid">
        <div class="report-card">
            <div class="report-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="report-title">Sales Analytics</div>
            <div class="report-description">
                Comprehensive sales performance analysis with trends, top products, and revenue insights.
            </div>
            <div class="report-actions">
                <button onclick="generateSalesReport()" class="btn btn-view">
                    <i class="fas fa-eye"></i> View Report
                </button>
                <button onclick="exportData('sales')" class="btn btn-export">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
        </div>
        
        <div class="report-card">
            <div class="report-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="report-title">Inventory Analysis</div>
            <div class="report-description">
                Stock levels, low inventory alerts, product valuation, and inventory turnover analysis.
            </div>
            <div class="report-actions">
                <button onclick="generateInventoryReport()" class="btn btn-view">
                    <i class="fas fa-eye"></i> View Report
                </button>
                <button onclick="exportData('inventory')" class="btn btn-export">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
        </div>
        
        <div class="report-card">
            <div class="report-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="report-title">Orders Management</div>
            <div class="report-description">
                Order patterns, fulfillment metrics, customer behavior analysis, and delivery performance.
            </div>
            <div class="report-actions">
                <button onclick="generateOrdersReport()" class="btn btn-view">
                    <i class="fas fa-eye"></i> View Report
                </button>
                <button onclick="exportData('orders')" class="btn btn-export">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
        </div>
        
        <div class="report-card">
            <div class="report-icon">
                <i class="fas fa-truck"></i>
            </div>
            <div class="report-title">Supplier Performance</div>
            <div class="report-description">
                Supplier reliability, delivery times, cost analysis, and vendor performance metrics.
            </div>
            <div class="report-actions">
                <button onclick="generateSuppliersReport()" class="btn btn-view">
                    <i class="fas fa-eye"></i> View Report
                </button>
                <button onclick="exportData('suppliers')" class="btn btn-export">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
        </div>
        
        <div class="report-card">
            <div class="report-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="report-title">User Activity</div>
            <div class="report-description">
                User engagement, registration trends, access patterns, and system usage analytics.
            </div>
            <div class="report-actions">
                <button onclick="generateUsersReport()" class="btn btn-primary">
                    <i class="fas fa-eye"></i> View Report
                </button>
                <button onclick="exportData('users')" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
        </div>
        
        <div class="report-card">
            <div class="report-icon">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="report-title">Stock Movements</div>
            <div class="report-description">
                Complete audit trail of inventory transactions, adjustments, and stock movement history.
            </div>
            <div class="report-actions">
                <button onclick="generateStockMovementsReport()" class="btn btn-primary">
                    <i class="fas fa-eye"></i> View Report
                </button>
                <button onclick="exportData('stock_movements')" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
        </div>
        
        <div class="report-card">
            <div class="report-icon">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="report-title">Financial Summary</div>
            <div class="report-description">
                Revenue breakdown, profit margins, expense tracking, and comprehensive financial analysis.
            </div>
            <div class="report-actions">
                <button onclick="generateFinancialReport()" class="btn btn-primary">
                    <i class="fas fa-eye"></i> View Report
                </button>
                <button onclick="exportData('financial')" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
        </div>
        
        <div class="report-card">
            <div class="report-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="report-title">Custom Date Range</div>
            <div class="report-description">
                Generate custom reports for specific date ranges with flexible filtering and analysis options.
            </div>
            <div class="report-actions">
                <button onclick="showCustomReportModal()" class="btn btn-warning">
                    <i class="fas fa-calendar"></i> Custom Report
                </button>
                <button onclick="exportData('custom')" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
                </div>
            </div>

            <!-- Quick Actions Section -->
            <div class="content-section">
                <div class="section-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                
                <div style="padding: 30px;">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Pro Tip:</strong> Use the export functions to download data in CSV format for further analysis in Excel or other tools. All reports include real-time data from your inventory management system.
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                        <button onclick="exportAllData()" class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-download"></i> Export All Data
                        </button>
                        <button onclick="generateDashboardReport()" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-tachometer-alt"></i> Dashboard Summary
                        </button>
                        <button onclick="printReport()" class="btn btn-warning" style="width: 100%;">
                            <i class="fas fa-print"></i> Print Current View
                        </button>
                        <button onclick="scheduleReport()" class="btn btn-secondary" style="width: 100%;">
                            <i class="fas fa-clock"></i> Schedule Reports
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

<!-- Custom Report Modal -->
<div id="customReportModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px);">
    <div class="modal-content" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); margin: 5% auto; padding: 30px; border-radius: 20px; width: 90%; max-width: 600px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); border: 1px solid rgba(255, 255, 255, 0.2);">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid rgba(0,0,0,0.1);">
            <h3 style="font-size: 1.8rem; font-weight: 600; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; display: flex; align-items: center; gap: 10px; margin: 0;">
                <i class="fas fa-calendar-alt"></i> Custom Report Generator
            </h3>
            <span class="close" onclick="closeModal('customReportModal')" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; transition: color 0.3s ease;">&times;</span>
        </div>
        
        <form id="customReportForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">From Date</label>
                    <input type="date" id="report_date_from" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 12px; font-size: 1rem;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">To Date</label>
                    <input type="date" id="report_date_to" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 12px; font-size: 1rem;">
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">Report Type</label>
                <select id="report_type" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 12px; font-size: 1rem;">
                    <option value="sales">Sales Report</option>
                    <option value="inventory">Inventory Report</option>
                    <option value="orders">Orders Report</option>
                    <option value="financial">Financial Summary</option>
                    <option value="comprehensive">Comprehensive Report</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                <button type="button" onclick="closeModal('customReportModal')" class="btn btn-secondary">Cancel</button>
                <button type="button" onclick="generateCustomReport()" class="btn btn-primary">
                    <i class="fas fa-chart-line"></i> Generate Report
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .page-header {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px); border-radius: 20px; padding: 30px;
        margin-bottom: 30px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        display: flex; justify-content: space-between; align-items: center;
    }
    
    .page-header h1 {
        font-size: 2.5rem; font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        display: flex; align-items: center; gap: 15px;
    }
    
    .header-actions { display: flex; gap: 15px; }
    
    .stats-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 10px; margin-bottom: 40px;
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
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
    }
    
    .stat-number {
        font-size: 1.4rem; font-weight: 800; margin-bottom: 6px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    
    .stat-label {
        font-size: 0.7rem; font-weight: 600; color: #6c757d;
        text-transform: uppercase; letter-spacing: 1px;
    }
    
    .reports-section {
        background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
        border-radius: 20px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2); overflow: hidden;
        margin-bottom: 25px;
    }
    
    .section-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white; padding: 25px 30px;
        display: flex; justify-content: space-between; align-items: center;
    }
    
    .section-header h3 {
        font-size: 1.5rem; font-weight: 600;
        display: flex; align-items: center; gap: 12px;
    }
    
    .reports-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 25px; padding: 30px;
    }
    
    .report-card {
        background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
        border-radius: 18px; padding: 30px; text-align: center;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative; overflow: hidden;
    }
    
    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }
    
    .report-icon {
        font-size: 3.5rem; margin-bottom: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        display: block;
    }
    
    .report-title {
        font-size: 1.4rem; font-weight: 700; margin-bottom: 12px;
        color: #2c3e50;
    }
    
    .report-description {
        color: #6c757d; margin-bottom: 25px; line-height: 1.6;
        font-size: 0.95rem;
    }
    
    .report-actions {
        display: flex; gap: 12px; justify-content: center;
        flex-wrap: wrap;
    }
    
    .alert {
        padding: 20px 25px; border-radius: 15px; margin-bottom: 25px;
        display: flex; align-items: center; gap: 15px; font-weight: 500;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1); backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .alert-info {
        background: linear-gradient(135deg, rgba(209, 236, 241, 0.9) 0%, rgba(191, 227, 234, 0.9) 100%);
        color: #0c5460; border-left: 5px solid #17a2b8;
    }
    
    @media (max-width: 768px) {
        .page-header { flex-direction: column; gap: 20px; text-align: center; }
        .stats-grid { grid-template-columns: 1fr; }
        .reports-grid { grid-template-columns: 1fr; padding: 20px; }
        .report-card { padding: 20px; }
    }
</style>

<script>
    // Report generation functions
    function generateSalesReport() {
        showReportPreview('Sales Analytics', 'Generating comprehensive sales performance report...');
    }
    
    function generateInventoryReport() {
        showReportPreview('Inventory Analysis', 'Analyzing current stock levels and inventory metrics...');
    }
    
    function generateOrdersReport() {
        showReportPreview('Orders Management', 'Compiling order patterns and fulfillment data...');
    }
    
    function generateSuppliersReport() {
        showReportPreview('Supplier Performance', 'Evaluating supplier reliability and performance metrics...');
    }
    
    function generateUsersReport() {
        showReportPreview('User Activity', 'Analyzing user engagement and system usage patterns...');
    }
    
    function generateStockMovementsReport() {
        showReportPreview('Stock Movements', 'Tracking inventory transactions and movement history...');
    }
    
    function generateFinancialReport() {
        showReportPreview('Financial Summary', 'Calculating revenue, profits, and financial metrics...');
    }
    
    function generateDashboardReport() {
        showReportPreview('Dashboard Summary', 'Creating executive summary of key business metrics...');
    }
    
    function showReportPreview(title, description) {
        alert(`${title}\n\n${description}\n\nThis feature would generate detailed reports with charts, tables, and analytics in a full implementation.`);
    }
    
    function showCustomReportModal() {
        document.getElementById('customReportModal').style.display = 'block';
        
        // Set default dates (last 30 days)
        const today = new Date();
        const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
        
        document.getElementById('report_date_to').value = today.toISOString().split('T')[0];
        document.getElementById('report_date_from').value = thirtyDaysAgo.toISOString().split('T')[0];
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    function generateCustomReport() {
        const dateFrom = document.getElementById('report_date_from').value;
        const dateTo = document.getElementById('report_date_to').value;
        const reportType = document.getElementById('report_type').value;
        
        if (!dateFrom || !dateTo) {
            alert('Please select both start and end dates.');
            return;
        }
        
        if (new Date(dateFrom) > new Date(dateTo)) {
            alert('Start date cannot be after end date.');
            return;
        }
        
        closeModal('customReportModal');
        
        const reportTypeName = document.getElementById('report_type').options[document.getElementById('report_type').selectedIndex].text;
        showReportPreview(`Custom ${reportTypeName}`, `Generating custom report from ${dateFrom} to ${dateTo}...`);
    }
    
    // Export functions
    function exportData(type) {
        const exportTypes = {
            'sales': 'Sales Data',
            'inventory': 'Inventory Data',
            'orders': 'Orders Data',
            'suppliers': 'Suppliers Data',
            'users': 'Users Data',
            'stock_movements': 'Stock Movements Data',
            'financial': 'Financial Data',
            'custom': 'Custom Report Data'
        };
        
        alert(`Exporting ${exportTypes[type] || 'Data'}...\n\nThis would generate and download a CSV file with the selected data in a full implementation.`);
    }
    
    function exportAllData() {
        if (confirm('This will export all system data including sales, inventory, orders, and user information. Continue?')) {
            alert('Exporting all data...\n\nThis would generate a comprehensive data export in multiple formats (CSV, Excel) in a full implementation.');
        }
    }
    
    function printReport() {
        window.print();
    }
    
    function scheduleReport() {
        alert('Schedule Reports\n\nThis feature would allow you to set up automated report generation and email delivery on daily, weekly, or monthly schedules.');
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('customReportModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
    
    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Reports page loaded successfully');
        
        // Add hover effects to stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    });
</script>

</body>
</html>