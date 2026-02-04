<?php
/**
 * Role-Based Access Control System
 * StockWise Pro - Inventory Management System
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Check if user has admin role
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user has staff role
 */
function isStaff() {
    return isLoggedIn() && $_SESSION['user_role'] === 'staff';
}

/**
 * Require login - redirect to login if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit();
    }
}

/**
 * Require admin role - show error if not admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        showAccessDenied('admin');
        exit();
    }
}

/**
 * Check if user can access specific feature
 */
function canAccess($feature) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Admin can access everything
    if (isAdmin()) {
        return true;
    }
    
    // Staff permissions
    if (isStaff()) {
        $staffPermissions = [
            'dashboard' => true,
            'products' => true,
            'add_product' => true,
            'edit_product' => true,
            'categories' => true,
            'suppliers' => true,
            'sales' => true,
            'orders' => true,
            'profile' => true,
            'reports' => false,  // Staff cannot access reports
            'users' => false,    // Staff cannot manage users
            'delete_product' => false,  // Staff cannot delete products
            'system_settings' => false  // Staff cannot access system settings
        ];
        
        return isset($staffPermissions[$feature]) ? $staffPermissions[$feature] : false;
    }
    
    return false;
}

/**
 * Show access denied page
 */
function showAccessDenied($requiredRole = 'admin') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied - StockWise Pro</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Times New Roman', Times, serif;
                background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .access-denied-container {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(20px);
                border-radius: 20px;
                padding: 3rem;
                text-align: center;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
                max-width: 500px;
                width: 90%;
            }
            .access-icon {
                font-size: 4rem;
                color: #dc2626;
                margin-bottom: 1.5rem;
            }
            .access-title {
                font-size: 2.5rem;
                color: #1f2937;
                margin-bottom: 1rem;
                font-weight: 700;
            }
            .access-message {
                font-size: 1.2rem;
                color: #6b7280;
                margin-bottom: 2rem;
                line-height: 1.6;
            }
            .role-info {
                background: rgba(239, 68, 68, 0.1);
                border: 1px solid rgba(239, 68, 68, 0.3);
                border-radius: 10px;
                padding: 1rem;
                margin-bottom: 2rem;
                color: #dc2626;
            }
            .back-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                background: linear-gradient(135deg, #1e40af 0%, #0f766e 100%);
                color: white;
                padding: 0.8rem 1.5rem;
                border-radius: 10px;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s ease;
                border: none;
                cursor: pointer;
            }
            .back-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(30, 64, 175, 0.3);
            }
        </style>
    </head>
    <body>
        <div class="access-denied-container">
            <div class="access-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1 class="access-title">Access Denied</h1>
            <p class="access-message">
                You don't have permission to access this page.
            </p>
            <div class="role-info">
                <strong>Required Role:</strong> <?php echo ucfirst($requiredRole); ?><br>
                <strong>Your Role:</strong> <?php echo isset($_SESSION['user_role']) ? ucfirst($_SESSION['user_role']) : 'Not logged in'; ?>
            </div>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Get user role display name
 */
function getRoleDisplayName($role) {
    $roles = [
        'admin' => 'Administrator',
        'staff' => 'Staff Member'
    ];
    return isset($roles[$role]) ? $roles[$role] : ucfirst($role);
}

/**
 * Get role permissions for display
 */
function getRolePermissions($role) {
    if ($role === 'admin') {
        return [
            'Full system access',
            'Manage all users',
            'View all reports',
            'System settings',
            'Delete products',
            'Manage categories & suppliers',
            'Process sales & orders'
        ];
    } elseif ($role === 'staff') {
        return [
            'View dashboard',
            'Manage products (add/edit only)',
            'Manage categories & suppliers',
            'Process sales & orders',
            'View own profile',
            'Limited access to reports'
        ];
    }
    return [];
}
?>