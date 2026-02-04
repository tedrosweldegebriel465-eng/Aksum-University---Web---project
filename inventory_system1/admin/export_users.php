<?php
/**
 * Export Users to CSV
 * Admin only functionality
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Get current user info
$user_info = getCurrentUser($conn);
if (!$user_info) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if user has permission to export users
checkPageAccess('view_users', $user_info['role'], 'dashboard.php');

// Get all users
try {
    $result = $conn->query("SELECT id, username, email, role, status, created_at, last_login FROM users ORDER BY created_at DESC");
    $users = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Failed to export users: ' . $e->getMessage();
    header('Location: users.php');
    exit();
}

// Set headers for CSV download
$filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create file pointer
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'ID',
    'Username',
    'Email',
    'Role',
    'Status',
    'Created Date',
    'Last Login'
]);

// Add user data
foreach ($users as $user) {
    fputcsv($output, [
        $user['id'],
        $user['username'],
        $user['email'],
        ucfirst($user['role']),
        ucfirst($user['status']),
        $user['created_at'] ? date('Y-m-d H:i:s', strtotime($user['created_at'])) : 'Never',
        $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : 'Never'
    ]);
}

// Close file pointer
fclose($output);

// Log the export activity
logActivity($conn, $_SESSION['user_id'], 'Users Export', 'Exported ' . count($users) . ' users to CSV');

exit();
?>