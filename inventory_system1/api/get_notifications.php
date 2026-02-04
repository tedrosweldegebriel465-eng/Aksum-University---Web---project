<?php
/**
 * Get Notifications API
 * Inventory Management System
 */
header('Content-Type: application/json');
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$response = ['success' => false, 'notifications' => [], 'count' => 0];

try {
    $user_id = $_SESSION['user_id'];
    $count_only = isset($_GET['count_only']) && $_GET['count_only'] == '1';
    
    if ($count_only) {
        // Get unread notification count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        $response['success'] = true;
        $response['count'] = (int)$count;
    } else {
        // Get all notifications for user
        $stmt = $conn->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? OR user_id IS NULL 
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
        
        // Get unread count
        $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count = $count_result->fetch_assoc()['count'];
        
        $response['success'] = true;
        $response['notifications'] = $notifications;
        $response['count'] = (int)$count;
    }
    
} catch (Exception $e) {
    $response['error'] = 'Failed to fetch notifications';
}

echo json_encode($response);
?>