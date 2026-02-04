<?php
/**
 * Contact Messages Management
 * Admin panel to view and manage contact form submissions
 */
session_start();

// Include role checking system
require_once '../includes/role_check.php';
require_once '../config/db.php';

// Require admin access
requireAdmin();

// Get user info for display
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT username, role, profile_photo FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_status') {
        $message_id = $_POST['message_id'];
        $new_status = $_POST['new_status'];
        
        $stmt = $conn->prepare("UPDATE contact_messages SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("si", $new_status, $message_id);
        $stmt->execute();
        
        $success_message = "Message status updated successfully.";
    }
}

// Get contact messages
$messages = [];
try {
    $result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 50");
    if ($result) {
        $messages = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $messages = [];
}

// Get statistics
$stats = [
    'total_messages' => 0,
    'new_messages' => 0,
    'read_messages' => 0,
    'replied_messages' => 0
];

try {
    $result = $conn->query("SELECT COUNT(*) as total FROM contact_messages");
    if ($result) $stats['total_messages'] = $result->fetch_assoc()['total'];
    
    $result = $conn->query("SELECT COUNT(*) as new FROM contact_messages WHERE status = 'new'");
    if ($result) $stats['new_messages'] = $result->fetch_assoc()['new'];
    
    $result = $conn->query("SELECT COUNT(*) as read FROM contact_messages WHERE status = 'read'");
    if ($result) $stats['read_messages'] = $result->fetch_assoc()['read'];
    
    $result = $conn->query("SELECT COUNT(*) as replied FROM contact_messages WHERE status = 'replied'");
    if ($result) $stats['replied_messages'] = $result->fetch_assoc()['replied'];
} catch (Exception $e) {
    // Keep default values
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - StockWise Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-weight: bold; }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-weight: bold;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            min-height: 100vh;
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
        
        .nav-menu { list-style: none; padding: 20px 0; }
        
        .nav-item {
            margin: 5px 15px; border-radius: 12px; transition: all 0.3s ease;
        }
        
        .nav-item a {
            color: rgba(255, 255, 255, 0.9); text-decoration: none;
            display: flex; align-items: center; gap: 15px;
            padding: 16px 20px; font-weight: 500; transition: all 0.3s ease;
        }
        
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
        }
        
        .page-header h1 {
            font-size: 2.5rem; font-weight: 700;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            display: flex; align-items: center; gap: 15px;
        }
        
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 15px; padding: 25px; text-align: center;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-number {
            font-size: 2rem; font-weight: 800; margin-bottom: 10px;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        
        .messages-table {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 20px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2); overflow: hidden;
        }
        
        .table-header {
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            color: white; padding: 25px 30px;
        }
        
        table { width: 100%; border-collapse: collapse; }
        
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid rgba(0,0,0,0.05); }
        
        th { background: #f8f9fa; font-weight: 600; }
        
        .status-badge {
            padding: 5px 12px; border-radius: 15px; font-size: 0.8rem; font-weight: 600;
        }
        
        .status-new { background: #fef3c7; color: #92400e; }
        .status-read { background: #dbeafe; color: #1e40af; }
        .status-replied { background: #d1fae5; color: #065f46; }
        
        .btn {
            padding: 8px 16px; border: none; border-radius: 8px;
            font-weight: 600; cursor: pointer; text-decoration: none;
            display: inline-flex; align-items: center; gap: 5px;
        }
        
        .btn-primary { background: #1e40af; color: white; }
        .btn-success { background: #059669; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
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
                <li class="nav-item"><a href="contact_messages.php" style="background: rgba(255,255,255,0.2);"><i class="fas fa-envelope"></i> Contact Messages</a></li>
                <li class="nav-item"><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                <li class="nav-item"><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-envelope"></i> Contact Messages</h1>
                <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 1.1rem;">Manage customer inquiries and support requests</p>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_messages']; ?></div>
                    <div class="stat-label">Total Messages</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['new_messages']; ?></div>
                    <div class="stat-label">New Messages</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['read_messages']; ?></div>
                    <div class="stat-label">Read Messages</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['replied_messages']; ?></div>
                    <div class="stat-label">Replied Messages</div>
                </div>
            </div>

            <!-- Messages Table -->
            <div class="messages-table">
                <div class="table-header">
                    <h3><i class="fas fa-list"></i> Recent Messages</h3>
                </div>
                
                <?php if (empty($messages)): ?>
                    <div style="padding: 40px; text-align: center; color: #6c757d;">
                        <i class="fas fa-envelope" style="font-size: 3rem; margin-bottom: 20px;"></i>
                        <h3>No Messages Yet</h3>
                        <p>Contact form messages will appear here when customers submit inquiries.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $message): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($message['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($message['email']); ?></td>
                                    <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $message['status']; ?>">
                                            <?php echo ucfirst($message['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></td>
                                    <td>
                                        <button onclick="viewMessage(<?php echo $message['id']; ?>)" class="btn btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function viewMessage(messageId) {
            // This would open a modal or redirect to view the full message
            alert('View message functionality - Message ID: ' + messageId);
        }
    </script>
</body>
</html>