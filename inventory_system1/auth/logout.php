<?php
/**
 * Logout Script
 * Inventory Management System
 */
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // User not logged in, redirect to login
    header('Location: login.php');
    exit();
}

// Include database connection
require_once '../config/db.php';

// Log activity before destroying session
try {
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, 'User Logout', ?)");
    $stmt->bind_param("is", $user_id, $ip_address);
    $stmt->execute();
} catch (Exception $e) {
    // Log error but continue with logout
    error_log("Logout activity log error: " . $e->getMessage());
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login page with logout message
header('Location: login.php?logout=1');
exit();
?>