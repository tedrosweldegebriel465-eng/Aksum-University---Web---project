<?php
/**
 * Mark Notifications as Read API
 * Inventory Management System
 */
header('Content-Type: application/json');
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $user_id = $_SESSION['user_id'];
        
        // Mark all notifications as read for the current user
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Notifications marked as read';
        } else {
            $response['error'] = 'Failed to update notifications';
        }
        
    } catch (Exception $e) {
        $response['error'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Invalid request method';
}

echo json_encode($response);
?>