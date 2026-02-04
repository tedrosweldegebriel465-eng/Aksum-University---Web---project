<?php
/**
 * Authentication Check Include
 * Inventory Management System
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Check session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: ../auth/login.php?timeout=1');
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Function to check if user has admin role
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Function to require admin access
function require_admin() {
    if (!is_admin()) {
        $_SESSION['error_message'] = 'Access denied. Admin privileges required.';
        header('Location: dashboard.php');
        exit();
    }
}

// Function to log user activity
function log_activity($action, $table_name = null, $record_id = null, $details = null) {
    global $conn;
    
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $user_id, $action, $table_name, $record_id, $details, $ip_address);
    $stmt->execute();
}

// Function to create notification
function create_notification($user_id, $type, $title, $message) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $type, $title, $message);
    $stmt->execute();
}

// Function to check for low stock and create notifications
function check_low_stock() {
    global $conn;
    
    // Get products with low stock
    $stmt = $conn->prepare("SELECT id, name, quantity, min_stock_level FROM products WHERE quantity <= min_stock_level AND status = 'active'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($product = $result->fetch_assoc()) {
        // Check if notification already exists for this product
        $check_stmt = $conn->prepare("SELECT id FROM notifications WHERE type = 'low_stock' AND title LIKE ? AND is_read = 0");
        $title_pattern = "Low Stock Alert: " . $product['name'] . "%";
        $check_stmt->bind_param("s", $title_pattern);
        $check_stmt->execute();
        $existing = $check_stmt->get_result();
        
        if ($existing->num_rows == 0) {
            $title = "Low Stock Alert: " . $product['name'];
            $message = "Product '{$product['name']}' is running low. Current stock: {$product['quantity']}, Minimum level: {$product['min_stock_level']}";
            
            // Create notification for all admin users
            $admin_stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
            $admin_stmt->execute();
            $admin_result = $admin_stmt->get_result();
            
            while ($admin = $admin_result->fetch_assoc()) {
                create_notification($admin['id'], 'low_stock', $title, $message);
            }
        }
    }
}

// Run low stock check
check_low_stock();
?>