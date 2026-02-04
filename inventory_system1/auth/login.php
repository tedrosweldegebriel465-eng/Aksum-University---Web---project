<?php
/**
 * Animated Login & Registration Page
 * Inventory Management System - Enhanced Version
 */
session_start();
require_once '../config/db.php';

// Function to validate email domain
function isValidEmailDomain($email) {
    $domain = substr(strrchr($email, "@"), 1);
    
    // List of common valid email domains
    $validDomains = [
        'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com',
        'icloud.com', 'aol.com', 'protonmail.com', 'zoho.com', 'mail.com',
        'yandex.com', 'gmx.com', 'fastmail.com', 'tutanota.com'
    ];
    
    // Check if domain is in the valid list
    if (in_array(strtolower($domain), $validDomains)) {
        return true;
    }
    
    // Additional check: verify if domain has MX record (more thorough)
    if (function_exists('checkdnsrr')) {
        return checkdnsrr($domain, 'MX');
    }
    
    return false;
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../admin/dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';
$login_error = '';
$register_error = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $login_error = 'Please fill in all fields.';
    } else {
        // Check user credentials
        $stmt = $conn->prepare("SELECT id, username, email, password, role, status FROM users WHERE (username = ? OR email = ?)");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Check if user is active
            if ($user['status'] !== 'active') {
                if ($user['status'] === 'inactive') {
                    $login_error = 'Your account has been deactivated. Please contact an administrator.';
                } else {
                    $login_error = 'Your account status does not allow login. Please contact an administrator.';
                }
            } elseif (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                
                // Update last login
                $update_stmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                
                // Log activity
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, 'User Login', ?)");
                $log_stmt->bind_param("is", $user['id'], $ip_address);
                $log_stmt->execute();
                
                header('Location: ../admin/dashboard.php');
                exit();
            } else {
                $login_error = 'Invalid username or password.';
            }
        } else {
            $login_error = 'Invalid username or password.';
        }
    }
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'register') {
    $firstName = sanitize_input($_POST['firstName'] ?? '');
    $lastName = sanitize_input($_POST['lastName'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $username = sanitize_input($_POST['username'] ?? '');
    $role = sanitize_input($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($username) || empty($role) || empty($password)) {
        $register_error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = 'Please enter a valid email address format.';
    } elseif (!isValidEmailDomain($email)) {
        $register_error = 'Please enter a real email address with a valid domain.';
    } elseif (strlen($username) < 3) {
        $register_error = 'Username must be at least 3 characters long.';
    } elseif (strlen($password) < 6) {
        $register_error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $register_error = 'Passwords do not match.';
    } else {
        // Check if user already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $register_error = 'Username or email already exists.';
        } else {
            // Create new user (simplified - no email verification for now)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
            $insert_stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
            
            if ($insert_stmt->execute()) {
                $success_message = 'Registration successful! You can now login with your credentials.';
                // Clear form data
                $firstName = $lastName = $email = $username = $role = '';
            } else {
                $register_error = 'Registration failed. Please try again. Error: ' . $conn->error;
            }
        }
    }
}

// Check for logout message
if (isset($_GET['logout'])) {
    $success_message = 'You have been successfully logged out.';
}

// Check for timeout message
if (isset($_GET['timeout'])) {
    $login_error = 'Your session has expired. Please login again.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Import Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, input, select {
            font-family: 'Times New Roman', Times, serif;
        }

        /* Container & Background */
        .container {
            position: relative;
            width: 100%;
            min-height: 100vh;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 30%, #fbbf24 70%, #f59e0b 100%);
            overflow: hidden;
        }

        .container:before {
            content: '';
            position: absolute;
            width: 2000px;
            height: 2000px;
            border-radius: 50%;
            background: linear-gradient(-45deg, 
                rgba(255, 255, 255, 0.15), 
                rgba(30, 64, 175, 0.2), 
                rgba(251, 191, 36, 0.25), 
                rgba(245, 158, 11, 0.2)
            );
            top: -10%;
            right: 48%;
            transform: translateY(-50%);
            z-index: 6;
            transition: 1.8s ease-in-out;
            box-shadow: 
                0 0 150px rgba(59, 130, 246, 0.3), 
                0 0 300px rgba(251, 191, 36, 0.4),
                inset 0 0 100px rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Forms Container */
        .forms-container {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
        }

        .signin-signup {
            position: absolute;
            top: 50%;
            left: 75%;
            transform: translate(-50%, -50%);
            width: 42%;
            max-height: 85vh;
            display: grid;
            grid-template-columns: 1fr;
            z-index: 5;
            transition: 1s 0.7s ease-in-out;
        }

        /* Form Styles */
        form {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            flex-direction: column;
            padding: 1.5rem 3rem;
            overflow-y: auto;
            max-height: 85vh;
            grid-column: 1 / 2;
            grid-row: 1 / 2;
            transition: 0.2s 0.7s ease-in-out;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
        }

        form.sign-in-form {
            z-index: 2;
        }

        form.sign-up-form {
            z-index: 1;
            opacity: 0;
        }

        /* Logo Section */
        .logo-section {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo {
            width: auto;
            height: auto;
            background: none;
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.53rem;
            color: transparent;
            margin: 0 auto 0.8rem auto;
            font-weight: 800;
            background: linear-gradient(135deg, #1e40af 0%, #fbbf24 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 4px 20px rgba(251, 191, 36, 0.3);
            letter-spacing: -1px;
            font-family: 'Times New Roman', Times, serif;
        }

        .title {
            font-size: 2.53rem;
            color: #444;
            margin-bottom: 0.4rem;
            font-weight: 700;
            font-family: 'Times New Roman', Times, serif;
        }

        .subtitle {
            color: #1f2937;
            font-size: 1.15rem;
            margin-bottom: 0.8rem;
            font-weight: 500;
            font-family: 'Times New Roman', Times, serif;
        }

        .description-text {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .description-text p {
            color: #374151;
            font-size: 0.98rem;
            line-height: 1.5;
            margin: 0;
            text-align: center;
            font-weight: 500;
            font-family: 'Times New Roman', Times, serif;
        }

        /* Input Fields */
        .form-row {
            display: flex;
            gap: 0.8rem;
            width: 100%;
            max-width: 320px;
        }

        .input-field {
            max-width: 320px;
            width: 100%;
            height: 48px;
            background: rgba(255, 255, 255, 0.25);
            margin: 8px 0;
            border-radius: 48px;
            display: grid;
            grid-template-columns: 15% 85%;
            padding: 0 0.4rem;
            position: relative;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            box-shadow: 
                0 3px 15px rgba(30, 64, 175, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }

        .input-field.half {
            max-width: 155px;
        }

        .input-field:focus-within {
            border-color: rgba(251, 191, 36, 0.6);
            background: rgba(255, 255, 255, 0.4);
            box-shadow: 
                0 0 25px rgba(251, 191, 36, 0.5), 
                0 8px 30px rgba(59, 130, 246, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.7);
            transform: translateY(-2px);
        }

        .input-field i {
            text-align: center;
            line-height: 48px;
            color: #1e40af;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .input-field:focus-within i {
            color: #f59e0b;
            transform: scale(1.1);
        }

        .input-field input, .form-group select, .form-group textarea {
            background: none;
            outline: none;
            border: none;
            line-height: 1;
            font-weight: 600;
            font-size: 1.15rem;
            color: #333;
            width: 100%;
            padding-right: 40px;
        }

        .input-field input::placeholder {
            color: #6b7280;
            font-weight: 500;
            font-size: 1.09rem;
            font-family: 'Times New Roman', Times, serif;
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #374151;
            cursor: pointer;
            font-size: 1.1rem;
        }

        /* Form Options */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 320px;
            margin: 0.8rem 0;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #1f2937;
            font-size: 1.04rem;
            cursor: pointer;
            font-family: 'Times New Roman', Times, serif;
        }

        .forgot-link {
            color: #1f2937;
            text-decoration: none;
            font-size: 1.04rem;
            font-family: 'Times New Roman', Times, serif;
        }

        .forgot-link:hover {
            color: #374151;
            text-decoration: underline;
        }

        /* Buttons */
        .btn {
            width: 180px;
            height: 48px;
            border: none;
            outline: none;
            border-radius: 45px;
            cursor: pointer;
            color: #fff;
            text-transform: uppercase;
            font-weight: 600;
            margin: 8px 0;
            transition: all 0.3s ease;
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: 'Times New Roman', Times, serif;
        }

        .btn.solid {
            background: linear-gradient(135deg, 
                rgba(30, 64, 175, 0.9) 0%, 
                rgba(251, 191, 36, 0.9) 100%
            );
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 
                0 4px 15px rgba(251, 191, 36, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }

        .btn.solid:hover {
            background: linear-gradient(135deg, 
                rgba(251, 191, 36, 0.9) 0%, 
                rgba(245, 158, 11, 0.9) 100%
            );
            transform: translateY(-2px);
            box-shadow: 
                0 8px 25px rgba(251, 191, 36, 0.6),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
        }

        .btn.transparent {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            width: 240px;
            backdrop-filter: blur(20px);
            box-shadow: 
                0 4px 15px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            white-space: nowrap;
        }

        .btn.transparent:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #1a2332;
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 
                0 8px 25px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Panels */
        .panels-container {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
        }

        .panel {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: space-around;
            text-align: center;
            z-index: 7;
        }

        .left-panel {
            pointer-events: all;
            padding: 3rem 17% 2rem 12%;
        }

        .right-panel {
            pointer-events: none;
            padding: 3rem 12% 2rem 17%;
        }

        .panel .content {
            color: #fff;
            transition: 0.9s 0.6s ease-in-out;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .panel .image {
            width: 100%;
            transition: 1.1s 0.4s ease-in-out;
            font-size: 8rem;
            color: rgba(255, 255, 255, 0.1);
            margin-top: 2rem;
        }

        .right-panel .content,
        .right-panel .image {
            transform: translateX(800px);
        }

        .panel h3 {
            font-weight: 600;
            font-size: 2.53rem;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
            font-family: 'Times New Roman', Times, serif;
        }

        .panel p {
            font-size: 1.38rem;
            padding: 0 1rem;
            margin-bottom: 1.5rem;
            line-height: 1.6;
            color: #f8fafc;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
            font-family: 'Times New Roman', Times, serif;
        }

        /* Feature Lists in Panels */
        .features-list, .benefits-list {
            margin-bottom: 2rem;
            padding: 0 1rem;
        }

        .feature-point, .benefit-point {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .feature-point:hover, .benefit-point:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }

        .feature-point i, .benefit-point i {
            color: #fbbf24;
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .feature-point span, .benefit-point span {
            color: #f8fafc;
            font-size: 1.04rem;
            font-weight: 500;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            font-family: 'Times New Roman', Times, serif;
        }

        /* Animation Classes */
        .container.sign-up-mode:before {
            transform: translate(100%, -50%);
            right: 52%;
        }

        .container.sign-up-mode .left-panel .content,
        .container.sign-up-mode .left-panel .image {
            transform: translateX(-800px);
        }

        .container.sign-up-mode .right-panel .content,
        .container.sign-up-mode .right-panel .image {
            transform: translateX(0px);
        }

        .container.sign-up-mode .left-panel {
            pointer-events: none;
        }

        .container.sign-up-mode .right-panel {
            pointer-events: all;
        }

        .container.sign-up-mode .signin-signup {
            left: 25%;
        }

        .container.sign-up-mode form.sign-in-form {
            z-index: 1;
            opacity: 0;
        }

        .container.sign-up-mode form.sign-up-form {
            z-index: 2;
            opacity: 1;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 12px;
            width: 100%;
            max-width: 320px;
            text-align: center;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
            font-size: 1.15rem;
            font-weight: 500;
            font-family: 'Times New Roman', Times, serif;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            color: #dc2626;
            box-shadow: 0 4px 20px rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background: rgba(5, 150, 105, 0.15);
            border-color: rgba(5, 150, 105, 0.3);
            color: #059669;
            box-shadow: 0 4px 20px rgba(5, 150, 105, 0.2);
        }

        /* Demo Accounts */
        .demo-accounts {
            margin-top: 15px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            width: 100%;
            max-width: 320px;
            text-align: center;
        }

        .demo-accounts h4 {
            color: #1f2937;
            margin-bottom: 10px;
            font-size: 1.04rem;
            font-family: 'Times New Roman', Times, serif;
        }

        .demo-accounts p {
            color: #374151;
            font-size: 0.92rem;
            margin: 5px 0;
            font-family: 'Times New Roman', Times, serif;
        }

        /* Social Media Icons */
        .social-text {
            color: #1f2937;
            font-size: 1.15rem;
            margin: 1.2rem 0 0.8rem 0;
            font-weight: 500;
            font-family: 'Times New Roman', Times, serif;
        }

        .social-media {
            display: flex;
            justify-content: center;
            gap: 0.8rem;
        }

        .social-icon {
            height: 45px;
            width: 45px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            color: #fff;
            font-size: 1.1rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .social-icon.facebook {
            background: #1877f2;
        }

        .social-icon.facebook:hover {
            background: #166fe5;
            transform: translateY(-3px) scale(1.1);
        }

        .social-icon.google {
            background: #4285f4;
        }

        .social-icon.google:hover {
            background: #3367d6;
            transform: translateY(-3px) scale(1.1);
        }

        .social-icon.twitter {
            background: #1da1f2;
        }

        .social-icon.twitter:hover {
            background: #0d8bd9;
            transform: translateY(-3px) scale(1.1);
        }

        .social-icon.linkedin {
            background: #0077b5;
        }

        .social-icon.linkedin:hover {
            background: #005885;
            transform: translateY(-3px) scale(1.1);
        }

        .social-icon:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        /* Feature Highlights */
        .features-highlight, .benefits-highlight {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            margin-top: 1.5rem;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .feature-item, .benefit-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.3rem;
            flex: 1;
            text-align: center;
        }

        .feature-item i, .benefit-item i {
            color: #f59e0b;
            font-size: 1rem;
            margin-bottom: 0.2rem;
        }

        .feature-item span, .benefit-item span {
            color: #374151;
            font-size: 0.7rem;
            font-weight: 600;
            line-height: 1.2;
        }

        /* Back Home Button */
        .back-home-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #1f2937;
            text-decoration: none;
            font-size: 1.04rem;
            font-weight: 600;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            border: 2px solid #1f2937;
            transition: all 0.3s ease;
            font-family: 'Times New Roman', Times, serif;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .back-home-btn:hover {
            background: #1f2937;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(31, 41, 55, 0.4);
        }

        /* Responsive */
        @media (max-width: 870px) {
            .container:before {
                width: 1500px;
                height: 1500px;
                left: 30%;
                bottom: 68%;
                transform: translate(-50%);
                right: initial;
                top: initial;
            }
            
            .signin-signup {
                width: 100%;
                left: 50%;
                top: 95%;
                transform: translate(-50%, -100%);
            }
            
            .container.sign-up-mode .signin-signup {
                left: 50%;
                top: 5%;
                transform: translate(-50%, 0%);
            }
            
            .panels-container {
                grid-template-columns: 1fr;
                grid-template-rows: 1fr 2fr 1fr;
            }
            
            .panel {
                flex-direction: row;
                justify-content: space-around;
                align-items: center;
                padding: 2.5rem 8%;
            }
            
            form {
                padding: 1rem 2rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .input-field.half {
                max-width: 380px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="forms-container">
            <div class="signin-signup">
                <!-- Sign In Form -->
                <form class="sign-in-form" method="POST" action="">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="logo-section">
                        <div class="logo">
                            StockWise Pro
                        </div>
                        <h2 class="title">Welcome Back</h2>
                        <p class="subtitle">Sign in to your inventory account</p>
                    </div>
                    
                    <?php if ($login_error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo $login_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="input-field">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Email or Username" 
                               value="<?php echo isset($_POST['username']) && $_POST['action'] == 'login' ? htmlspecialchars($_POST['username']) : ''; ?>" required />
                    </div>
                    
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required />
                    </div>
                    
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="rememberMe">
                            <span>Remember me</span>
                        </label>
                        <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                    </div>
                    
                    <input type="submit" value="Sign In" class="btn solid" />
                    
                    <p class="social-text">Or sign in with</p>
                    <div class="social-media">
                        <a href="#" class="social-icon facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-icon google">
                            <i class="fab fa-google"></i>
                        </a>
                        <a href="#" class="social-icon twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-icon linkedin">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                    
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="../index.php" class="back-home-btn">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                    </div>
                </form>

                <!-- Sign Up Form -->
                <form class="sign-up-form" method="POST" action="">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="logo-section">
                        <div class="logo">
                            StockWise Pro
                        </div>
                        <h2 class="title">Create Account</h2>
                        <p class="subtitle">Join our inventory management system</p>
                    </div>
                    
                    <?php if ($register_error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo $register_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="input-field half">
                            <i class="fas fa-user"></i>
                            <input type="text" name="firstName" placeholder="First Name" 
                                   value="<?php echo isset($_POST['firstName']) && $_POST['action'] == 'register' ? htmlspecialchars($_POST['firstName']) : ''; ?>" required />
                        </div>
                        <div class="input-field half">
                            <i class="fas fa-user"></i>
                            <input type="text" name="lastName" placeholder="Last Name" 
                                   value="<?php echo isset($_POST['lastName']) && $_POST['action'] == 'register' ? htmlspecialchars($_POST['lastName']) : ''; ?>" required />
                        </div>
                    </div>
                    
                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email Address" 
                               value="<?php echo isset($_POST['email']) && $_POST['action'] == 'register' ? htmlspecialchars($_POST['email']) : ''; ?>" required />
                    </div>
                    
                    <div class="input-field">
                        <i class="fas fa-at"></i>
                        <input type="text" name="username" placeholder="Username" 
                               value="<?php echo isset($_POST['username']) && $_POST['action'] == 'register' ? htmlspecialchars($_POST['username']) : ''; ?>" required />
                    </div>
                    
                    <div class="input-field">
                        <i class="fas fa-user-tag"></i>
                        <select name="role" required>
                            <option value="">Select Role</option>
                            <option value="staff" <?php echo (isset($_POST['role']) && $_POST['action'] == 'register' && $_POST['role'] == 'staff') ? 'selected' : ''; ?>>Staff Member</option>
                            <option value="admin" <?php echo (isset($_POST['role']) && $_POST['action'] == 'register' && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>
                    
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required />
                    </div>
                    
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirmPassword" placeholder="Confirm Password" required />
                    </div>
                    
                    <input type="submit" value="Create Account" class="btn solid" />
                    
                    <p class="social-text">Or sign up with</p>
                    <div class="social-media">
                        <a href="#" class="social-icon facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-icon google">
                            <i class="fab fa-google"></i>
                        </a>
                        <a href="#" class="social-icon twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-icon linkedin">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="panels-container">
            <div class="panel left-panel">
                <div class="content">
                    <h3>New to Our Platform?</h3>
                    <p>Join thousands of businesses managing their inventory efficiently with our comprehensive system.</p>
                    <div class="features-list">
                        <div class="feature-point">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure & Reliable Platform</span>
                        </div>
                        <div class="feature-point">
                            <i class="fas fa-chart-line"></i>
                            <span>Real-time Analytics & Reports</span>
                        </div>
                        <div class="feature-point">
                            <i class="fas fa-mobile-alt"></i>
                            <span>Mobile-Friendly Interface</span>
                        </div>
                        <div class="feature-point">
                            <i class="fas fa-rocket"></i>
                            <span>Quick & Easy Setup</span>
                        </div>
                    </div>
                    <button class="btn transparent" id="sign-up-btn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </div>
                <div class="image">
                    <i class="fas fa-warehouse"></i>
                </div>
            </div>
            <div class="panel right-panel">
                <div class="content">
                    <h3>Welcome Back!</h3>
                    <p>Access your inventory dashboard and continue managing your business operations seamlessly.</p>
                    <div class="benefits-list">
                        <div class="benefit-point">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Advanced Dashboard</span>
                        </div>
                        <div class="benefit-point">
                            <i class="fas fa-users"></i>
                            <span>Team Collaboration</span>
                        </div>
                        <div class="benefit-point">
                            <i class="fas fa-cloud"></i>
                            <span>Cloud-Based Storage</span>
                        </div>
                        <div class="benefit-point">
                            <i class="fas fa-cog"></i>
                            <span>Automated Workflows</span>
                        </div>
                    </div>
                    <button class="btn transparent" id="sign-in-btn">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </div>
                <div class="image">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Animation Controls
        const sign_in_btn = document.querySelector("#sign-in-btn");
        const sign_up_btn = document.querySelector("#sign-up-btn");
        const container = document.querySelector(".container");

        sign_up_btn.addEventListener('click', () => {
            container.classList.add("sign-up-mode");
        });

        sign_in_btn.addEventListener('click', () => {
            container.classList.remove("sign-up-mode");
        });

        // Auto-switch to sign-in mode after successful registration
        <?php if ($success_message): ?>
        setTimeout(() => {
            container.classList.remove("sign-up-mode");
        }, 3000);
        <?php endif; ?>

        // Show registration form if there's a registration error
        <?php if ($register_error): ?>
        container.classList.add("sign-up-mode");
        <?php endif; ?>
    </script>
</body>
</html>