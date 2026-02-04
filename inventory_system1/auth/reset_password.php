<?php
/**
 * Reset Password Page
 * StockWise Pro - Inventory Management System
 */
session_start();
require_once '../config/db.php';

$message = '';
$error = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Invalid reset token.';
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($token)) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Verify token and check if it's not expired
            $stmt = $conn->prepare("SELECT user_id, email FROM password_resets WHERE token = ? AND expires_at > NOW()");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $reset_data = $result->fetch_assoc();
                
                // Update user password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                
                if (!$update_stmt) {
                    throw new Exception("Prepare update failed: " . $conn->error);
                }
                
                $update_stmt->bind_param("si", $hashed_password, $reset_data['user_id']);
                
                if ($update_stmt->execute()) {
                    // Delete the reset token
                    $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                    $delete_stmt->bind_param("s", $token);
                    $delete_stmt->execute();
                    
                    $message = 'Your password has been successfully reset. You can now login with your new password.';
                } else {
                    throw new Exception("Password update failed: " . $update_stmt->error);
                }
            } else {
                $error = 'Invalid or expired reset token.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred while resetting your password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - StockWise Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same styles as forgot_password.php */
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
                <h2 class="title">Reset Password</h2>
                <p class="subtitle">Enter your new password</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$message && !empty($token)): ?>
            <form method="POST" action="">
                <div class="input-field">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="New Password" required />
                </div>
                
                <div class="input-field">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required />
                </div>
                
                <input type="submit" value="Reset Password" class="btn solid" />
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