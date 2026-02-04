<?php
/**
 * Passcode Generation API
 * Generate unique passcodes via AJAX
 */
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Enhanced passcode generation function
function generateUniquePasscode($length = 8) {
    // Use non-confusing characters (excludes I, O, 0, 1)
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $passcode = '';
    
    // Ensure first character is always a letter
    $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $passcode .= $letters[rand(0, strlen($letters) - 1)];
    
    // Generate remaining characters
    for ($i = 1; $i < $length; $i++) {
        $passcode .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $passcode;
}

$input = json_decode(file_get_contents('php://input'), true);
$count = isset($input['count']) ? (int)$input['count'] : 1;

if ($count < 1 || $count > 20) {
    echo json_encode(['error' => 'Invalid count. Must be between 1 and 20.']);
    exit();
}

$passcodes = [];
for ($i = 0; $i < $count; $i++) {
    $passcode = generateUniquePasscode();
    
    // Ensure uniqueness against database
    $attempts = 0;
    while ($attempts < 10) {
        $check_stmt = $conn->prepare("SELECT id FROM registration_passcodes WHERE passcode = ?");
        $check_stmt->bind_param("s", $passcode);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            break;
        }
        
        $passcode = generateUniquePasscode();
        $attempts++;
    }
    
    if ($attempts < 10) {
        $passcodes[] = $passcode;
    }
}

echo json_encode([
    'success' => true,
    'passcodes' => $passcodes,
    'count' => count($passcodes)
]);
?>