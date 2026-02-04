<?php
/**
 * Enhanced Activity Logs Page (Admin Only)
 * Modern glass morphism design with comprehensive activity tracking
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db.php';
require_once '../includes/role_permissions.php';

// Check admin access
$user_role = getUserRole();
if (!isAdmin($user_role)) {
    $_SESSION['error_message'] = 'Access denied. Admin privileges required.';
    header('Location: dashboard.php');
    exit();
}

// Get user info for display
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT username, role, profile_photo FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();
}

$page_title = 'Activity Logs';

// Get filters
$user_filter = $_GET['user_id'] ?? '';
$action_filter = $_GET['action'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days')); // Last 7 days
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Build query
$where_conditions = ["1=1"];
$params = [];
$param_types = '';

if ($user_filter) {
    $where_conditions[] = "al.user_id = ?";
    $params[] = $user_filter;
    $param_types .= 'i';
}

if ($action_filter) {
    $where_conditions[] = "al.action LIKE ?";
    $params[] = "%$action_filter%";
    $param_types .= 's';
}

if ($start_date) {
    $where_conditions[] = "DATE(al.created_at) >= ?";
    $params[] = $start_date;
    $param_types .= 's';
}

if ($end_date) {
    $where_conditions[] = "DATE(al.created_at) <= ?";
    $params[] = $end_date;
    $param_types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

// Get activity logs
$query = "
    SELECT al.*, u.username, u.role
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    WHERE $where_clause
    ORDER BY al.created_at DESC
    LIMIT 200
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get users for filter
$users = $conn->query("SELECT id, username FROM users ORDER BY username")->fetch_all(MYSQLI_ASSOC);

// Get unique actions for filter
$actions = $conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action")->fetch_all(MYSQLI_ASSOC);

// Get activity statistics
$stats = [
    'total_activities' => 0,
    'today_activities' => 0,
    'unique_users' => 0,
    'login_activities' => 0,
    'product_activities' => 0,
    'user_activities' => 0
];

try {
    // Total activities
    $result = $conn->query("SELECT COUNT(*) as total FROM activity_logs");
    if ($result) $stats['total_activities'] = $result->fetch_assoc()['total'];
    
    // Today's activities
    $result = $conn->query("SELECT COUNT(*) as today FROM activity_logs WHERE DATE(created_at) = CURDATE()");
    if ($result) $stats['today_activities'] = $result->fetch_assoc()['today'];
    
    // Unique users
    $result = $conn->query("SELECT COUNT(DISTINCT user_id) as users FROM activity_logs");
    if ($result) $stats['unique_users'] = $result->fetch_assoc()['users'];
    
    // Login activities
    $result = $conn->query("SELECT COUNT(*) as logins FROM activity_logs WHERE action LIKE '%Login%'");
    if ($result) $stats['login_activities'] = $result->fetch_assoc()['logins'];
    
    // Product activities
    $result = $conn->query("SELECT COUNT(*) as products FROM activity_logs WHERE action LIKE '%Product%'");
    if ($result) $stats['product_activities'] = $result->fetch_assoc()['products'];
    
    // User management activities
    $result = $conn->query("SELECT COUNT(*) as users FROM activity_logs WHERE action LIKE '%User%' AND action NOT LIKE '%Login%' AND action NOT LIKE '%Logout%'");
    if ($result) $stats['user_activities'] = $result->fetch_assoc()['users'];
    
} catch (Exception $e) {
    // Keep default values
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - StockWise Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #4fc3f7 0%, #29b6f6 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .stat-icon {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; color: white; margin: 0 auto 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        
        .stat-icon.bg-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.bg-success { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.bg-warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.bg-danger { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .stat-icon.bg-info { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-icon.bg-secondary { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); }
        
        .stat-number {
            font-size: 1.8rem; font-weight: 800; margin-bottom: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            font-size: 0.85rem; font-weight: 600; color: #6c757d;
        }
        
        .content-section {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 20px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2); overflow: hidden;
            margin-bottom: 30px;
        }
        
        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            align-items: center; gap: 8px; position: relative; overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }

        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white; box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
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
        
        .filter-section {
            padding: 25px 30px;
            background: rgba(248, 249, 250, 0.8);
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .filter-grid {
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
        
        .form-group input, .form-group select {
            width: 100%; padding: 12px 16px; border: 2px solid #e9ecef;
            border-radius: 12px; font-size: 1rem; transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none; border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); background: white;
        }
        
        .activity-table { width: 100%; border-collapse: collapse; }
        
        .activity-table th, .activity-table td {
            padding: 18px 20px; text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .activity-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600; color: #495057; text-transform: uppercase;
            letter-spacing: 0.5px; font-size: 0.9rem;
        }
        
        .activity-table tbody tr {
            transition: all 0.3s ease;
        }
        
        .activity-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: scale(1.01);
        }
        
        .status-badge {
            padding: 6px 12px; border-radius: 20px; font-size: 0.8rem;
            font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
        }
        
        .status-active {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .status-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
        }
        
        .action-badge {
            padding: 6px 12px; border-radius: 15px; font-size: 0.8rem;
            font-weight: 600; display: inline-block;
        }
        
        .action-login { background: #d4edda; color: #155724; }
        .action-logout { background: #f8d7da; color: #721c24; }
        .action-product { background: #d1ecf1; color: #0c5460; }
        .action-update { background: #fff3cd; color: #856404; }
        .action-delete { background: #f8d7da; color: #721c24; }
        .action-stock { background: #d4edda; color: #155724; }
        .action-default { background: #e2e3e5; color: #383d41; }
        
        .empty-state {
            text-align: center; padding: 60px 30px; color: #6c757d;
            background: rgba(255, 255, 255, 0.5); border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        
        .empty-state i {
            font-size: 4rem; color: #dee2e6; margin-bottom: 20px;
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        
        .empty-state h3 {
            margin-bottom: 15px; color: #495057; font-size: 1.5rem; font-weight: 600;
        }
        
        .summary-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 30px;
            padding: 25px;
        }
        
        .summary-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 0; border-bottom: 1px solid #eee;
        }
        
        .summary-item:last-child { border-bottom: none; }
        
        .summary-title {
            font-size: 1.1rem; font-weight: 600; color: #333; margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; height: auto; }
            .main-content { padding: 20px; }
            .page-header { flex-direction: column; gap: 20px; text-align: center; }
            .stats-grid { grid-template-columns: 1fr; }
            .filter-grid { grid-template-columns: 1fr; }
            .summary-grid { grid-template-columns: 1fr; }
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
                <?php if ($user_info['profile_photo']): ?>
                    <img src="../assets/images/profiles/<?php echo htmlspecialchars($user_info['profile_photo']); ?>" 
                         alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255, 255, 255, 0.2);">
                <?php else: ?>
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #4fc3f7 0%, #29b6f6 100%); display: flex; align-items: center; justify-content: center; color: white; border: 2px solid rgba(255, 255, 255, 0.2);">
                        <i class="fas fa-user"></i>
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
                <li class="nav-item"><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li class="nav-item"><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li class="nav-item active"><a href="activity_logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li class="nav-item"><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                <li class="nav-item"><a href="../auth/logout.php" onclick="return confirm('Are you sure you want to logout?')"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-history"></i> Activity Logs</h1>
                <div class="actions">
                    <button onclick="exportData('activity_logs')" class="btn btn-success">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['total_activities']); ?></div>
                    <div class="stat-label">Total Activities</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['today_activities']); ?></div>
                    <div class="stat-label">Today's Activities</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['unique_users']); ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['login_activities']); ?></div>
                    <div class="stat-label">Login Activities</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-danger">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['product_activities']); ?></div>
                    <div class="stat-label">Product Activities</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-secondary">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['user_activities']); ?></div>
                    <div class="stat-label">User Management</div>
                </div>
            </div>

            <!-- Activity Logs Table -->
            <div class="content-section">
                <div class="section-header">
                    <h3><i class="fas fa-history"></i> Activity Logs</h3>
                </div>
                
                <!-- Filters -->
                <div class="filter-section">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label>User:</label>
                            <select onchange="updateFilter('user_id', this.value)">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Action:</label>
                            <select onchange="updateFilter('action', this.value)">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $action): ?>
                                    <option value="<?php echo $action['action']; ?>" <?php echo $action_filter == $action['action'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($action['action']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>From Date:</label>
                            <input type="date" value="<?php echo $start_date; ?>" onchange="updateFilter('start_date', this.value)">
                        </div>
                        
                        <div class="form-group">
                            <label>To Date:</label>
                            <input type="date" value="<?php echo $end_date; ?>" onchange="updateFilter('end_date', this.value)">
                        </div>
                        
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button onclick="clearFilters()" class="btn btn-secondary" style="width: 100%;">
                                <i class="fas fa-times"></i> Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($activities)): ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h3>No Activity Logs Found</h3>
                        <p>No activities match your current filters.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo date('M j, Y', strtotime($activity['created_at'])); ?></strong>
                                            <br><small style="color: #666;"><?php echo date('g:i:s A', strtotime($activity['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($activity['username']); ?></strong>
                                            <br><span class="status-badge <?php echo $activity['role'] == 'admin' ? 'status-active' : 'status-info'; ?>">
                                                <?php echo ucfirst($activity['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            // Determine action badge class
                                            $action_class = 'action-default';
                                            if (strpos($activity['action'], 'Login') !== false) $action_class = 'action-login';
                                            elseif (strpos($activity['action'], 'Logout') !== false) $action_class = 'action-logout';
                                            elseif (strpos($activity['action'], 'Product') !== false) $action_class = 'action-product';
                                            elseif (strpos($activity['action'], 'Updated') !== false) $action_class = 'action-update';
                                            elseif (strpos($activity['action'], 'Deleted') !== false) $action_class = 'action-delete';
                                            elseif (strpos($activity['action'], 'Stock') !== false) $action_class = 'action-stock';
                                            ?>
                                            <span class="action-badge <?php echo $action_class; ?>">
                                                <?php echo htmlspecialchars($activity['action']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($activity['details']): ?>
                                                <?php echo htmlspecialchars($activity['details']); ?>
                                            <?php else: ?>
                                                <small style="color: #999;">No details</small>
                                            <?php endif; ?>
                                            
                                            <?php if ($activity['table_name'] && $activity['record_id']): ?>
                                                <br><small style="color: #666;">
                                                    Table: <?php echo htmlspecialchars($activity['table_name']); ?> | 
                                                    ID: <?php echo $activity['record_id']; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small style="font-family: monospace; color: #666;">
                                                <?php echo htmlspecialchars($activity['ip_address']); ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (count($activities) >= 200): ?>
                        <div style="padding: 20px; text-align: center; background: #fff3cd; color: #856404; border-top: 1px solid rgba(0,0,0,0.1);">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Showing the most recent 200 activities. Use filters to narrow down results.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Activity Summary -->
            <?php if (!empty($activities)): ?>
            <div class="content-section">
                <div class="section-header">
                    <h3><i class="fas fa-chart-pie"></i> Activity Summary</h3>
                </div>
                
                <div class="summary-grid">
                    <?php
                    // Calculate activity statistics
                    $action_counts = [];
                    $user_counts = [];
                    
                    foreach ($activities as $activity) {
                        // Count by action
                        $action_counts[$activity['action']] = ($action_counts[$activity['action']] ?? 0) + 1;
                        
                        // Count by user
                        $user_counts[$activity['username']] = ($user_counts[$activity['username']] ?? 0) + 1;
                    }
                    
                    arsort($action_counts);
                    arsort($user_counts);
                    ?>
                    
                    <!-- Top Actions -->
                    <div>
                        <div class="summary-title">Top Actions</div>
                        <?php foreach (array_slice($action_counts, 0, 5, true) as $action => $count): ?>
                            <div class="summary-item">
                                <span><?php echo htmlspecialchars($action); ?></span>
                                <span class="status-badge status-info"><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Most Active Users -->
                    <div>
                        <div class="summary-title">Most Active Users</div>
                        <?php foreach (array_slice($user_counts, 0, 5, true) as $username => $count): ?>
                            <div class="summary-item">
                                <span><?php echo htmlspecialchars($username); ?></span>
                                <span class="status-badge status-active"><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function updateFilter(param, value) {
        const url = new URL(window.location);
        if (value) {
            url.searchParams.set(param, value);
        } else {
            url.searchParams.delete(param);
        }
        window.location = url;
    }

    function clearFilters() {
        window.location = 'activity_logs.php';
    }

    function exportData(type) {
        // Add export functionality here
        alert('Export functionality will be implemented');
    }
    </script>
</body>
</html>