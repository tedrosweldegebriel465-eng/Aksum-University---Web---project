<?php
/**
 * Header Include File
 * Inventory Management System
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in for protected pages
$current_page = basename($_SERVER['PHP_SELF']);
$public_pages = ['login.php', 'register.php', 'index.php'];

if (!in_array($current_page, $public_pages) && !isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Get user info if logged in
$user_info = null;
if (isset($_SESSION['user_id'])) {
    require_once '../config/db.php';
    $stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>StockWise Pro - Colorful Inventory System</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Immediate Colorful Enhancements -->
    <style>
        /* Force colorful changes to show immediately */
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%) !important;
            animation: backgroundShift 20s ease infinite !important;
        }
        
        @keyframes backgroundShift {
            0%, 100% { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); }
            50% { background: linear-gradient(135deg, #e8f5e8 0%, #d4e7f7 100%); }
        }
        
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%) !important;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15) !important;
        }
        
        .sidebar-header h3 {
            background: linear-gradient(45deg, #fff, #f0f8ff) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            font-weight: 700 !important;
            font-size: 1.4rem !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%) !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            position: relative !important;
            overflow: hidden !important;
            transition: all 0.4s ease !important;
            margin-bottom: 20px !important;
        }
        
        .stat-card::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 5px !important;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #f5576c, #43e97b) !important;
            background-size: 200% 100% !important;
            animation: rainbowShift 3s ease infinite !important;
        }
        
        @keyframes rainbowShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .stat-card:hover {
            transform: translateY(-10px) scale(1.03) !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25) !important;
        }
        
        .stat-icon {
            width: 80px !important;
            height: 80px !important;
            border-radius: 20px !important;
            font-size: 2rem !important;
            margin-right: 25px !important;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2) !important;
            animation: iconPulse 2s ease-in-out infinite !important;
        }
        
        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .stat-details h3 {
            font-size: 2.5rem !important;
            font-weight: 800 !important;
            background: linear-gradient(135deg, #667eea, #764ba2) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            animation: textShimmer 2s ease-in-out infinite !important;
        }
        
        @keyframes textShimmer {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        .table-container {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%) !important;
            border-radius: 25px !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
            border: 2px solid rgba(255, 255, 255, 0.3) !important;
            overflow: hidden !important;
        }
        
        .table-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%) !important;
            padding: 30px !important;
            border-radius: 25px 25px 0 0 !important;
        }
        
        .table-header h3 {
            color: white !important;
            font-size: 1.5rem !important;
            font-weight: 700 !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3) !important;
        }
        
        .btn {
            border-radius: 15px !important;
            padding: 15px 25px !important;
            font-weight: 700 !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
            transition: all 0.3s ease !important;
            position: relative !important;
            overflow: hidden !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }
        
        .btn::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: -100% !important;
            width: 100% !important;
            height: 100% !important;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent) !important;
            transition: left 0.5s !important;
        }
        
        .btn:hover::before {
            left: 100% !important;
        }
        
        .btn:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.25) !important;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;
        }
        
        th {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f2ff 100%) !important;
            color: #333 !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            padding: 20px 15px !important;
        }
        
        tr:hover {
            background: linear-gradient(135deg, #f0f8ff 0%, #e8f2ff 100%) !important;
            transform: scale(1.01) !important;
            transition: all 0.2s ease !important;
        }
        
        .top-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1) !important;
            border-bottom: 3px solid transparent !important;
            border-image: linear-gradient(90deg, #667eea, #764ba2, #f093fb) 1 !important;
        }
        
        /* Colorful status badges */
        .status-badge {
            padding: 8px 15px !important;
            border-radius: 25px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            font-size: 0.75rem !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
        }
        
        /* Dashboard welcome message */
        .content h1, .content h2 {
            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            font-weight: 800 !important;
            text-align: center !important;
            margin-bottom: 30px !important;
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-boxes"></i> StockWise Pro</h3>
            
            <!-- User info under StockWise Pro -->
            <?php if ($user_info): ?>
            <div class="header-user-info" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: rgba(255, 255, 255, 0.1); border-radius: 12px; margin-top: 15px; border: 1px solid rgba(255, 255, 255, 0.1);">
                <?php 
                $profile_stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
                $profile_stmt->bind_param("i", $_SESSION['user_id']);
                $profile_stmt->execute();
                $profile_result = $profile_stmt->get_result();
                $profile_data = $profile_result->fetch_assoc();
                ?>
                
                <?php if ($profile_data['profile_photo']): ?>
                    <img src="../assets/images/profiles/<?php echo htmlspecialchars($profile_data['profile_photo']); ?>" 
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
            
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <ul>
                <li><a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a></li>
                
                <li><a href="products.php" class="<?php echo in_array($current_page, ['products.php', 'add_product.php', 'edit_product.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> Products
                </a></li>
                
                <li><a href="categories.php" class="<?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i> Categories
                </a></li>
                
                <li><a href="suppliers.php" class="<?php echo $current_page == 'suppliers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i> Suppliers
                </a></li>
                
                <li><a href="stock_transactions.php" class="<?php echo $current_page == 'stock_transactions.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i> Stock History
                </a></li>
                
                <li><a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Reports
                </a></li>
                
                <?php if ($user_info && $user_info['role'] == 'admin'): ?>
                <li><a href="users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Users
                </a></li>
                
                <li><a href="user_management.php" class="<?php echo $current_page == 'user_management.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i> User Management
                </a></li>
                
                <li><a href="activity_logs.php" class="<?php echo $current_page == 'activity_logs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> Activity Logs
                </a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <?php 
                $profile_stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
                $profile_stmt->bind_param("i", $_SESSION['user_id']);
                $profile_stmt->execute();
                $profile_result = $profile_stmt->get_result();
                $profile_data = $profile_result->fetch_assoc();
                ?>
                
                <?php if ($profile_data['profile_photo']): ?>
                    <img src="../assets/images/profiles/<?php echo htmlspecialchars($profile_data['profile_photo']); ?>" alt="Profile" class="user-avatar">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
                
                <div class="user-details">
                    <span><?php echo htmlspecialchars($user_info['username'] ?? ''); ?></span>
                    <small><?php echo ucfirst($user_info['role'] ?? ''); ?></small>
                </div>
            </div>
            
            <div class="user-actions">
                <a href="profile.php" class="profile-btn" title="My Profile">
                    <i class="fas fa-cog"></i>
                </a>
                <a href="../auth/logout.php" class="logout-btn" title="Logout" onclick="return confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="main-content" id="mainContent">
        <?php if (isset($_SESSION['user_id'])): ?>
        <header class="top-header">
            <div class="header-left">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1><?php echo $page_title ?? 'Dashboard'; ?></h1>
            </div>
            
            <div class="header-right">
                <div class="notifications" id="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count" id="notificationCount">0</span>
                </div>
                
                <div class="user-menu">
                    <span>Welcome, <?php echo htmlspecialchars($user_info['username'] ?? ''); ?></span>
                </div>
            </div>
        </header>
        <?php endif; ?>
        
        <main class="content">
            <?php
            // Display flash messages
            if (isset($_SESSION['success_message'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                unset($_SESSION['success_message']);
            }
            
            if (isset($_SESSION['error_message'])) {
                echo '<div class="alert alert-error">' . $_SESSION['error_message'] . '</div>';
                unset($_SESSION['error_message']);
            }
            ?>