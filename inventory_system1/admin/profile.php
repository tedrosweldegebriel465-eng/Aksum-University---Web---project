<?php
/**
 * Clean Profile Page - StockWise Pro
 * Simple and professional profile management
 */
$page_title = 'My Profile';

// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Database connection
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Handle profile photo upload
    if (isset($_POST['upload_photo'])) {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_type = $_FILES['profile_photo']['type'];
            $file_size = $_FILES['profile_photo']['size'];
            
            if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                $upload_dir = '../uploads/profiles/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    // Update database with new photo path
                    $photo_path = 'uploads/profiles/' . $new_filename;
                    try {
                        $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                        $stmt->bind_param("si", $photo_path, $user_id);
                        
                        if ($stmt->execute()) {
                            $success_message = 'Profile photo updated successfully!';
                        } else {
                            $error_message = 'Failed to update profile photo in database.';
                        }
                    } catch (Exception $e) {
                        $error_message = 'Database error: Please run the database update script first. <a href="update_profile_database.php" style="color: #667eea;">Click here to update database</a>';
                    }
                } else {
                    $error_message = 'Failed to upload profile photo.';
                }
            } else {
                $error_message = 'Invalid file type or size. Please upload a JPEG, PNG, or GIF image under 5MB.';
            }
        } else {
            $error_message = 'Please select a valid image file.';
        }
    }
    
    // Handle extended profile update
    if (isset($_POST['update_extended_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $bio = trim($_POST['bio']);
        $department = trim($_POST['department']);
        $job_title = trim($_POST['job_title']);
        
        try {
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, bio = ?, department = ?, job_title = ? WHERE id = ?");
            $stmt->bind_param("sssssssi", $first_name, $last_name, $phone, $address, $bio, $department, $job_title, $user_id);
            
            if ($stmt->execute()) {
                $success_message = 'Extended profile updated successfully!';
            } else {
                $error_message = 'Failed to update extended profile.';
            }
        } catch (Exception $e) {
            $error_message = 'Database error: Please run the database update script first. <a href="update_profile_database.php" style="color: #667eea;">Click here to update database</a>';
        }
    }
    
    // Handle notification preferences
    if (isset($_POST['update_notifications'])) {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $low_stock_alerts = isset($_POST['low_stock_alerts']) ? 1 : 0;
        $order_notifications = isset($_POST['order_notifications']) ? 1 : 0;
        $system_updates = isset($_POST['system_updates']) ? 1 : 0;
        
        try {
            $stmt = $conn->prepare("UPDATE users SET email_notifications = ?, low_stock_alerts = ?, order_notifications = ?, system_updates = ? WHERE id = ?");
            $stmt->bind_param("iiiii", $email_notifications, $low_stock_alerts, $order_notifications, $system_updates, $user_id);
            
            if ($stmt->execute()) {
                $success_message = 'Notification preferences updated successfully!';
            } else {
                $error_message = 'Failed to update notification preferences.';
            }
        } catch (Exception $e) {
            $error_message = 'Database error: Please run the database update script first. <a href="update_profile_database.php" style="color: #667eea;">Click here to update database</a>';
        }
    }
    
    // Handle basic profile update
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        // Check if username/email already exists for other users
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $check_stmt->bind_param("ssi", $username, $email, $user_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error_message = 'Username or email already exists!';
        } else {
            // Update user information
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $email, $user_id);
            
            if ($stmt->execute()) {
                $success_message = 'Profile updated successfully!';
            } else {
                $error_message = 'Failed to update profile.';
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $error_message = 'New passwords do not match!';
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $success_message = 'Password changed successfully!';
                } else {
                    $error_message = 'Failed to change password.';
                }
            } else {
                $error_message = 'Current password is incorrect!';
            }
        }
    }
}

// Get user data - handle missing columns gracefully
try {
    $stmt = $conn->prepare("SELECT username, email, role, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    
    // Try to get extended profile data if columns exist
    $extended_columns = ['profile_photo', 'first_name', 'last_name', 'phone', 'address', 'bio', 'department', 'job_title', 'email_notifications', 'low_stock_alerts', 'order_notifications', 'system_updates'];
    
    foreach ($extended_columns as $column) {
        try {
            $stmt = $conn->prepare("SELECT $column FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $row = $result->fetch_assoc()) {
                $user_data[$column] = $row[$column] ?? '';
            } else {
                $user_data[$column] = '';
            }
        } catch (Exception $e) {
            // Column doesn't exist yet, set default value
            $user_data[$column] = ($column == 'email_notifications' || $column == 'low_stock_alerts' || $column == 'order_notifications' || $column == 'system_updates') ? 1 : '';
        }
    }
} catch (Exception $e) {
    $error_message = 'Error loading user data: ' . $e->getMessage();
    $user_data = [
        'username' => '',
        'email' => '',
        'role' => '',
        'created_at' => date('Y-m-d H:i:s'),
        'profile_photo' => '',
        'first_name' => '',
        'last_name' => '',
        'phone' => '',
        'address' => '',
        'bio' => '',
        'department' => '',
        'job_title' => '',
        'email_notifications' => 1,
        'low_stock_alerts' => 1,
        'order_notifications' => 1,
        'system_updates' => 1
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - StockWise Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            min-height: 100vh;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%);
            z-index: -1;
            animation: backgroundShift 20s ease-in-out infinite;
        }
        
        @keyframes backgroundShift {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(5deg); }
        }
        
        .container { display: flex; min-height: 100vh; }
        
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.1);
        }
        
        .logo {
            text-align: center; padding: 30px 20px; font-size: 1.8rem; font-weight: 700;
            background: rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            display: flex; align-items: center; justify-content: center; gap: 12px;
        }
        
        .logo i {
            background: linear-gradient(135deg, #60a5fa 0%, #34d399 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            font-size: 2rem;
        }
        
        .nav-menu { list-style: none; padding: 20px 0; }
        
        .nav-item {
            margin: 5px 15px; border-radius: 12px; transition: all 0.3s ease;
        }
        
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(8px);
        }
        
        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid #60a5fa;
        }
        
        .nav-item a {
            color: rgba(255, 255, 255, 0.9); text-decoration: none;
            display: flex; align-items: center; gap: 15px;
            padding: 16px 20px; font-weight: 500; transition: all 0.3s ease;
        }
        
        .nav-item:hover a { color: white; }
        .nav-item i { width: 20px; text-align: center; font-size: 1.1rem; }
        
        .main-content {
            flex: 1; padding: 40px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }
        
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px); border-radius: 20px; padding: 30px;
            margin-bottom: 30px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .page-header h1 {
            font-size: 2.5rem; font-weight: 700;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            display: flex; align-items: center; gap: 15px;
        }
        
        .content-section {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 20px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2); overflow: hidden;
            margin-bottom: 30px;
        }
        
        .section-header {
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            color: white; padding: 25px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .section-header h3 {
            font-size: 1.3rem; font-weight: 600;
            display: flex; align-items: center; gap: 12px;
        }
        
        .form-group {
            margin-bottom: 25px;
            padding: 0 30px;
        }

        .form-group:first-of-type {
            margin-top: 30px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 0.95rem;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #1e40af;
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
            background: white;
        }

        .form-actions {
            padding: 0 30px 30px 30px;
        }
        
        .btn {
            padding: 12px 24px; border: none; border-radius: 12px;
            font-weight: 600; font-size: 0.95rem; cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-decoration: none; display: inline-flex;
            align-items: center; gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1e40af 0%, #0f766e);
            color: white; box-shadow: 0 8px 25px rgba(30, 64, 175, 0.3);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white; box-shadow: 0 8px 25px rgba(240, 147, 251, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .profile-content {
            display: grid;
            gap: 30px;
            grid-template-columns: 1fr 1fr;
            margin-bottom: 30px;
        }
        
        .profile-photo-section {
            text-align: center;
            padding: 30px;
        }
        
        .profile-photo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        
        .photo-upload-overlay {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .photo-upload-overlay:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        }
        
        .file-input-hidden {
            display: none;
        }
        
        .notification-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .notification-toggle:last-child {
            border-bottom: none;
        }
        
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
            background: #ccc;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .toggle-switch.active {
            background: linear-gradient(135deg, #1e40af 0%, #0f766e);
        }
        
        .toggle-slider {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .toggle-switch.active .toggle-slider {
            transform: translateX(30px);
        }
        
        .extended-profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            padding: 30px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.bg-primary { 
            background: linear-gradient(135deg, #1e40af 0%, #0f766e);
        }
        .stat-icon.bg-success { 
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-icon.bg-warning { 
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-icon.bg-info { 
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .stat-info h4 {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .stat-info p {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .alert {
            padding: 20px 25px; border-radius: 15px; margin-bottom: 25px;
            display: flex; align-items: center; gap: 15px; font-weight: 500;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(212, 237, 218, 0.9) 0%, rgba(195, 230, 203, 0.9) 100%);
            color: #155724; border-left: 5px solid #28a745;
        }

        .alert-error {
            background: linear-gradient(135deg, rgba(248, 215, 218, 0.9) 0%, rgba(245, 198, 203, 0.9) 100%);
            color: #721c24; border-left: 5px solid #dc3545;
        }
        
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; height: auto; }
            .main-content { padding: 20px; }
            .page-header { flex-direction: column; gap: 20px; text-align: center; }
            .profile-content { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-boxes"></i> StockWise Pro
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="nav-item"><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                <li class="nav-item"><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <li class="nav-item"><a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
                <li class="nav-item"><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li class="nav-item"><a href="sales.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li class="nav-item"><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li class="nav-item"><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li class="nav-item active"><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                <li class="nav-item"><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">

            <div class="page-header">
                <div>
                    <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                    <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 1.1rem;">Manage your account settings and personal information</p>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Database Update Notice -->
            <?php
            // Check if extended profile columns exist
            $columns_exist = true;
            try {
                $conn->query("SELECT profile_photo FROM users LIMIT 1");
            } catch (Exception $e) {
                $columns_exist = false;
            }
            
            if (!$columns_exist): ?>
                <div class="alert" style="background: linear-gradient(135deg, rgba(255, 243, 205, 0.9) 0%, rgba(255, 238, 186, 0.9) 100%); color: #856404; border-left: 5px solid #ffc107;">
                    <i class="fas fa-database"></i>
                    <div>
                        <strong>Database Update Required:</strong> To use the new profile features (photo upload, extended info, notifications), please run the database update script first.
                        <br><br>
                        <a href="update_profile_database.php" class="btn btn-warning" style="margin-top: 10px;">
                            <i class="fas fa-database"></i> Update Database Now
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Profile Content -->
            <div class="profile-content">
                <!-- Profile Photo Section -->
                <div class="content-section">
                    <div class="section-header">
                        <h3><i class="fas fa-camera"></i> Profile Photo</h3>
                    </div>
                    
                    <div class="profile-photo-section">
                        <div class="profile-photo-container">
                            <?php if (!empty($user_data['profile_photo']) && file_exists('../' . $user_data['profile_photo'])): ?>
                                <img src="../<?php echo htmlspecialchars($user_data['profile_photo']); ?>" alt="Profile Photo" class="profile-photo">
                            <?php else: ?>
                                <div class="profile-photo">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            
                            <button type="button" class="photo-upload-overlay" onclick="document.getElementById('profile_photo_input').click()">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" id="photoUploadForm">
                            <input type="file" id="profile_photo_input" name="profile_photo" accept="image/*" class="file-input-hidden" onchange="uploadPhoto()">
                            <input type="hidden" name="upload_photo" value="1">
                        </form>
                        
                        <p style="color: #6c757d; font-size: 0.9rem; margin-top: 10px;">
                            Click the camera icon to upload a new photo<br>
                            <small>Supported formats: JPEG, PNG, GIF (Max 5MB)</small>
                        </p>
                    </div>
                </div>

                <!-- Profile Information Section -->
                <div class="content-section">
                    <div class="section-header">
                        <h3><i class="fas fa-user"></i> Basic Information</h3>
                    </div>
        
        <form method="POST" action="">
            <input type="hidden" name="update_profile" value="1">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="role">Role</label>
                <input type="text" id="role" value="<?php echo ucfirst($user_data['role']); ?>" readonly style="background: #f8f9fa;">
            </div>
            
            <div class="form-group">
                <label for="member_since">Member Since</label>
                <input type="text" id="member_since" value="<?php echo date('F j, Y', strtotime($user_data['created_at'])); ?>" readonly style="background: #f8f9fa;">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </div>
                    </form>
                </div>
                
                <!-- Password Change Section -->
                <div class="content-section">
                    <div class="section-header">
                        <h3><i class="fas fa-lock"></i> Change Password</h3>
                    </div>
        
        <form method="POST" action="">
            <input type="hidden" name="change_password" value="1">
            
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </div>
                    </form>
                </div>
            </div>

            <!-- Extended Profile Information -->
            <div class="content-section">
                <div class="section-header">
                    <h3><i class="fas fa-id-card"></i> Extended Profile</h3>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="update_extended_profile" value="1">
                    
                    <div class="extended-profile-grid">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="department">Department</label>
                            <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($user_data['department']); ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="job_title">Job Title</label>
                            <input type="text" id="job_title" name="job_title" value="<?php echo htmlspecialchars($user_data['job_title']); ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user_data['address']); ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="bio">Bio / About Me</label>
                            <textarea id="bio" name="bio" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user_data['bio']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Extended Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Notification Preferences -->
            <div class="content-section">
                <div class="section-header">
                    <h3><i class="fas fa-bell"></i> Notification Preferences</h3>
                </div>
                
                <form method="POST" action="" style="padding: 30px;">
                    <input type="hidden" name="update_notifications" value="1">
                    
                    <div class="notification-toggle">
                        <div>
                            <strong>Email Notifications</strong>
                            <p style="color: #6c757d; font-size: 0.9rem; margin: 5px 0 0 0;">Receive general email notifications</p>
                        </div>
                        <div class="toggle-switch <?php echo $user_data['email_notifications'] ? 'active' : ''; ?>" onclick="toggleNotification(this, 'email_notifications')">
                            <div class="toggle-slider"></div>
                            <input type="checkbox" name="email_notifications" style="display: none;" <?php echo $user_data['email_notifications'] ? 'checked' : ''; ?>>
                        </div>
                    </div>
                    
                    <div class="notification-toggle">
                        <div>
                            <strong>Low Stock Alerts</strong>
                            <p style="color: #6c757d; font-size: 0.9rem; margin: 5px 0 0 0;">Get notified when products are running low</p>
                        </div>
                        <div class="toggle-switch <?php echo $user_data['low_stock_alerts'] ? 'active' : ''; ?>" onclick="toggleNotification(this, 'low_stock_alerts')">
                            <div class="toggle-slider"></div>
                            <input type="checkbox" name="low_stock_alerts" style="display: none;" <?php echo $user_data['low_stock_alerts'] ? 'checked' : ''; ?>>
                        </div>
                    </div>
                    
                    <div class="notification-toggle">
                        <div>
                            <strong>Order Notifications</strong>
                            <p style="color: #6c757d; font-size: 0.9rem; margin: 5px 0 0 0;">Receive updates about new orders and status changes</p>
                        </div>
                        <div class="toggle-switch <?php echo $user_data['order_notifications'] ? 'active' : ''; ?>" onclick="toggleNotification(this, 'order_notifications')">
                            <div class="toggle-slider"></div>
                            <input type="checkbox" name="order_notifications" style="display: none;" <?php echo $user_data['order_notifications'] ? 'checked' : ''; ?>>
                        </div>
                    </div>
                    
                    <div class="notification-toggle">
                        <div>
                            <strong>System Updates</strong>
                            <p style="color: #6c757d; font-size: 0.9rem; margin: 5px 0 0 0;">Get notified about system maintenance and updates</p>
                        </div>
                        <div class="toggle-switch <?php echo $user_data['system_updates'] ? 'active' : ''; ?>" onclick="toggleNotification(this, 'system_updates')">
                            <div class="toggle-slider"></div>
                            <input type="checkbox" name="system_updates" style="display: none;" <?php echo $user_data['system_updates'] ? 'checked' : ''; ?>>
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(0, 0, 0, 0.1);">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Notification Preferences
                        </button>
                    </div>
                </form>
            </div>

            <!-- Account Statistics -->
            <div class="content-section">
                <div class="section-header">
                    <h3><i class="fas fa-chart-bar"></i> Account Statistics</h3>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h4>Account Age</h4>
                            <p><?php echo floor((time() - strtotime($user_data['created_at'])) / (60 * 60 * 24)); ?> days</p>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h4>Account Status</h4>
                            <p>Active</p>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-user-tag"></i>
                        </div>
                        <div class="stat-info">
                            <h4>Role</h4>
                            <p><?php echo ucfirst($user_data['role']); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-icon bg-info">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h4>Last Login</h4>
                            <p>Today</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

<script>
    // Password confirmation validation
    document.addEventListener('DOMContentLoaded', function() {
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        if (newPassword && confirmPassword) {
            confirmPassword.addEventListener('input', function() {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            });
        }
    });
    
    // Profile photo upload function
    function uploadPhoto() {
        const form = document.getElementById('photoUploadForm');
        const fileInput = document.getElementById('profile_photo_input');
        
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            
            if (!allowedTypes.includes(file.type)) {
                alert('Please select a valid image file (JPEG, PNG, or GIF).');
                return;
            }
            
            if (file.size > maxSize) {
                alert('File size must be less than 5MB.');
                return;
            }
            
            // Show loading state
            const overlay = document.querySelector('.photo-upload-overlay');
            overlay.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            overlay.style.pointerEvents = 'none';
            
            // Submit the form
            form.submit();
        }
    }
    
    // Toggle notification function
    function toggleNotification(toggleElement, inputName) {
        const checkbox = toggleElement.querySelector('input[type="checkbox"]');
        const isActive = toggleElement.classList.contains('active');
        
        if (isActive) {
            toggleElement.classList.remove('active');
            checkbox.checked = false;
        } else {
            toggleElement.classList.add('active');
            checkbox.checked = true;
        }
    }
    
    // Auto-save notification preferences (optional)
    function autoSaveNotifications() {
        const form = document.querySelector('form[action=""] input[name="update_notifications"]').closest('form');
        const formData = new FormData(form);
        
        fetch('', {
            method: 'POST',
            body: formData
        }).then(response => {
            if (response.ok) {
                // Show brief success indicator
                const indicator = document.createElement('div');
                indicator.innerHTML = '<i class="fas fa-check"></i> Saved';
                indicator.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 10px 15px; border-radius: 5px; z-index: 1000;';
                document.body.appendChild(indicator);
                
                setTimeout(() => {
                    document.body.removeChild(indicator);
                }, 2000);
            }
        });
    }
    
    // Enhanced form validation
    function validateExtendedProfile() {
        const phone = document.getElementById('phone').value;
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        
        if (phone && !phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''))) {
            alert('Please enter a valid phone number.');
            return false;
        }
        
        return true;
    }
    
    // Add form validation to extended profile form
    document.addEventListener('DOMContentLoaded', function() {
        const extendedForm = document.querySelector('input[name="update_extended_profile"]');
        if (extendedForm) {
            extendedForm.closest('form').addEventListener('submit', function(e) {
                if (!validateExtendedProfile()) {
                    e.preventDefault();
                }
            });
        }
    });
</script>

</body>
</html>