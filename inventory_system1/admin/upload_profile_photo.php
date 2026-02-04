<?php
/**
 * Profile Photo Upload Handler
 * Handles AJAX photo uploads for the user display area
 */
session_start();

// Set JSON response header
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'File upload incomplete',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'No temporary directory',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write file',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        
        $response['message'] = $error_messages[$file['error']] ?? 'Upload error';
        echo json_encode($response);
        exit();
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = $file['type'];
    
    // Also check by file extension as backup
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($file_type, $allowed_types) && !in_array($file_extension, $allowed_extensions)) {
        $response['message'] = 'Invalid file type. Please use JPG, PNG, or GIF images.';
        echo json_encode($response);
        exit();
    }
    
    // Validate file size (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        $response['message'] = 'File too large. Maximum size is 5MB.';
        echo json_encode($response);
        exit();
    }
    
    // Validate that it's actually an image
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        $response['message'] = 'Invalid image file.';
        echo json_encode($response);
        exit();
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = '../assets/images/profiles/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $response['message'] = 'Cannot create upload directory.';
            echo json_encode($response);
            exit();
        }
    }
    
    // Get old profile photo to delete later
    $old_photo = null;
    try {
        $old_photo_query = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
        $old_photo_query->bind_param("i", $user_id);
        $old_photo_query->execute();
        $old_photo_result = $old_photo_query->get_result();
        $old_photo_data = $old_photo_result->fetch_assoc();
        $old_photo = $old_photo_data['profile_photo'] ?? null;
    } catch (Exception $e) {
        // Continue even if we can't get old photo
    }
    
    // Generate unique filename
    $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Update database
        try {
            $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $stmt->bind_param("si", $new_filename, $user_id);
            
            if ($stmt->execute()) {
                // Delete old profile photo if it exists
                if ($old_photo && $old_photo !== $new_filename && file_exists($upload_dir . $old_photo)) {
                    unlink($upload_dir . $old_photo);
                }
                
                $response['success'] = true;
                $response['message'] = 'Profile photo updated successfully!';
                $response['photo_url'] = '../assets/images/profiles/' . $new_filename;
                $response['filename'] = $new_filename;
            } else {
                // Database update failed, delete uploaded file
                unlink($upload_path);
                $response['message'] = 'Database update failed. Please try again.';
            }
        } catch (Exception $e) {
            // Database error, delete uploaded file
            unlink($upload_path);
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Failed to save uploaded file. Check directory permissions.';
    }
} else {
    $response['message'] = 'No file uploaded or invalid request method.';
}

echo json_encode($response);
?>