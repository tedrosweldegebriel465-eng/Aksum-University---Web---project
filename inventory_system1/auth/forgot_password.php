<?php
/**
 * Forgot Password Page
 * StockWise Pro - Inventory Management System
 */
session_start();
require_once '../config/db.php';

$message = '';
$error = '';
$debug_info = '';

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check if email exists in database
            $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // First, try to create the table if it doesn't exist
                $create_table_sql = "CREATE TABLE IF NOT EXISTS password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    token VARCHAR(255) NOT NULL UNIQUE,
                    expires_at DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_token (token),
                    INDEX idx_expires (expires_at)
                )";
                $conn->query($create_table_sql);
                
                // Store reset token in database
                $insert_stmt = $conn->prepare("INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)");
                
                if (!$insert_stmt) {
                    throw new Exception("Prepare insert failed: " . $conn->error);
                }
                
                $insert_stmt->bind_param("isss", $user['id'], $email, $reset_token, $expires_at);
                
                if ($insert_stmt->execute()) {
                    // Success message
                    $message = 'Password reset instructions have been sent to your email address. Please check your inbox.';
                    
                    // Demo: Show the reset link (in production, this would be sent via email)
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset_password.php?token=" . $reset_token;
                    $message .= "<br><br><strong>Demo Link (click to reset password):</strong><br><a href='$reset_link' style='color: #1e40af; text-decoration: underline;'>$reset_link</a>";
                } else {
                    throw new Exception("Execute failed: " . $insert_stmt->error);
                }
            } else {
                // Don't reveal if email exists or not for security
                $message = 'If an account with that email exists, password reset instructions have been sent.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred while processing your request. Please try again.';
            $debug_info = 'Debug: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - StockWise Pro</title>
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

        /* Form Container */
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
        }

        /* Logo Section */
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

        /* Input Fields */
        .input-field {
            max-width: 100%;
            width: 100%;
            height: 48px;
            background: rgba(255, 255, 255, 0.25);
            margin: 15px 0;
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

        .input-field input {
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

        /* Buttons */
        .btn {
            width: 100%;
            height: 48px;
            border: none;
            outline: none;
            border-radius: 45px;
            cursor: pointer;
            color: #fff;
            text-transform: uppercase;
            font-weight: 600;
            margin: 15px 0;
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

        /* Alert Messages */
        .alert {
            padding: 15px;
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

        /* Back Link */
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

        /* Responsive */
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
                <h2 class="title">Forgot Password</h2>
                <p class="subtitle">Enter your email to reset your password</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                    <?php if ($debug_info): ?>
                        <br><small style="font-size: 0.9rem; opacity: 0.8;"><?php echo $debug_info; ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$message): ?>
            <form method="POST" action="">
                <div class="input-field">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Enter your email address" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required />
                </div>
                
                <input type="submit" value="Send Reset Link" class="btn solid" />
            </form>
            <?php endif; ?>
            
            <div style="text-align: center;">
                <a href="login.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>