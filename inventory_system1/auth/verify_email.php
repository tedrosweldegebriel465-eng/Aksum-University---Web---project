<?php
/**
 * Email Verification Page
 * StockWise Pro - Inventory Management System
 */
session_start();
require_once '../config/db.php';

$message = '';
$error = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Invalid verification token.';
} else {
    // Verify the token and activate the account
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE verification_token = ? AND email_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Update user to verified and active
        $update_stmt = $conn->prepare("UPDATE users SET email_verified = 1, status = 'active', verification_token = NULL WHERE id = ?");
        $update_stmt->bind_param("i", $user['id']);
        
        if ($update_stmt->execute()) {
            $message = 'Email verified successfully! Your account is now active. You can login with your credentials.';
        } else {
            $error = 'An error occurred while verifying your email. Please try again.';
        }
    } else {
        $error = 'Invalid or already used verification token.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - StockWise Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, input, select {
            font-family: 'Times New Roman', Times, serif;
        }

        .container {
            position: relative;
            width: 100%;
            min-height: 100vh;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 30%, #fbbf24 70%, #f59e0b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
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
            z-index: 1;
            box-shadow: 
                0 0 150px rgba(59, 130, 246, 0.3), 
                0 0 300px rgba(251, 191, 36, 0.4),
                inset 0 0 100px rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .form-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
            padding: 2rem 3rem;
            width: 100%;
            max-width: 450px;
            z-index: 2;
            position: relative;
            text-align: center;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            font-size: 2.53rem;
            color: transparent;
            margin: 0 auto 1rem auto;
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
            margin-bottom: 0.5rem;
            font-weight: 700;
            font-family: 'Times New Roman', Times, serif;
        }

        .subtitle {
            color: #1f2937;
            font-size: 1.15rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            font-family: 'Times New Roman', Times, serif;
        }

        .alert {
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            width: 100%;
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

        .verification-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .success-icon {
            color: #059669;
        }

        .error-icon {
            color: #dc2626;
        }

        .back-link {
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
            margin-top: 1rem;
        }

        .back-link:hover {
            background: #1f2937;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(31, 41, 55, 0.4);
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 1.5rem 2rem;
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="logo-section">
                <div class="logo">StockWise Pro</div>
                <h2 class="title">Email Verification</h2>
                <p class="subtitle">Verify your email address</p>
            </div>
            
            <?php if ($error): ?>
                <div class="verification-icon error-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="verification-icon success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center;">
                <a href="login.php" class="back-link">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>