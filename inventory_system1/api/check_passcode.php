<?php
/**
 * Passcode Validation API
 * Check if a passcode is valid for registration
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$passcode = $input['passcode'] ?? '';
$role = $input['role'] ?? '';

if (empty($passcode) || empty($role)) {
    echo json_encode(['valid' => false, 'message' => 'Passcode and role are required']);
    exit();
}

// Check passcode validity
$stmt = $conn->prepare("SELECT id, role, expires_at FROM registration_passcodes WHERE passcode = ? AND is_used = FALSE AND expires_at > NOW()");
$stmt->bind_param("s", $passcode);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['valid' => false, 'message' => 'Invalid or expired passcode']);
} else {
    $passcode_data = $result->fetch_assoc();
    
    if ($passcode_data['role'] !== $role) {
        echo json_encode(['valid' => false, 'message' => 'Passcode is not valid for the selected role']);
    } else {
        $expires_at = date('M j, Y g:i A', strtotime($passcode_data['expires_at']));
        echo json_encode(['valid' => true, 'message' => "Valid passcode for $role registration (expires $expires_at)"]);
    }
}
?>