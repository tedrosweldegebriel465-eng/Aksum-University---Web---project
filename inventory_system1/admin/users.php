<?php
/**
 * Users Management Page
 * Admin Access Required
 */
session_start();

// Include role checking system
require_once '../includes/role_check.php';
require_once '../config/db.php';

// Require admin access for user management
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

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Add new user
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        if (!empty($username) && !empty($email) && !empty($password)) {
            // Check if username or email already exists
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check_stmt->bind_param("ss", $username, $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_message = "Username or email already exists!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
                $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
                
                if ($stmt->execute()) {
                    $success_message = "User added successfully!";
                    logActivity($conn, $_SESSION['user_id'], 'User Added', "Added user: $username");
                } else {
                    $error_message = "Failed to add user!";
                }
            }
        } else {
            $error_message = "Please fill in all required fields!";
        }
    }
    
    // Edit user
    if (isset($_POST['edit_user'])) {
        $user_id = $_POST['user_id'];
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $status = $_POST['status'];
        $password = $_POST['password'];
        
        if (!empty($username) && !empty($email)) {
            // Check if username or email already exists for other users
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $check_stmt->bind_param("ssi", $username, $email, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_message = "Username or email already exists for another user!";
            } else {
                if (!empty($password)) {
                    // Update with new password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ?, status = ?, password = ? WHERE id = ?");
                    $stmt->bind_param("sssssi", $username, $email, $role, $status, $hashed_password, $user_id);
                } else {
                    // Update without changing password
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ?, status = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $username, $email, $role, $status, $user_id);
                }
                
                if ($stmt->execute()) {
                    $success_message = "User updated successfully!";
                    logActivity($conn, $_SESSION['user_id'], 'User Updated', "Updated user: $username");
                } else {
                    $error_message = "Failed to update user!";
                }
            }
        } else {
            $error_message = "Please fill in all required fields!";
        }
    }
    
    // Delete user
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        
        // Prevent deleting own account
        if ($user_id == $_SESSION['user_id']) {
            $error_message = "You cannot delete your own account!";
        } else {
            // Get username for logging
            $user_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user_data = $user_result->fetch_assoc();
            
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $success_message = "User deleted successfully!";
                logActivity($conn, $_SESSION['user_id'], 'User Deleted', "Deleted user: " . $user_data['username']);
            } else {
                $error_message = "Failed to delete user!";
            }
        }
    }
    
    // Bulk actions
    if (isset($_POST['bulk_action']) && isset($_POST['selected_users'])) {
        $action = $_POST['bulk_action'];
        $selected_users = $_POST['selected_users'];
        $count = 0;
        
        foreach ($selected_users as $user_id) {
            // Skip own account for delete action
            if ($action === 'delete' && $user_id == $_SESSION['user_id']) {
                continue;
            }
            
            if ($action === 'activate') {
                $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) $count++;
            } elseif ($action === 'deactivate') {
                $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) $count++;
            } elseif ($action === 'delete') {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) $count++;
            }
        }
        
        if ($count > 0) {
            $success_message = "Bulk action completed on $count users!";
            logActivity($conn, $_SESSION['user_id'], 'Bulk Action', "Performed $action on $count users");
        } else {
            $error_message = "No users were affected by the bulk action!";
        }
    }
}

// Get all users
$users = [];
try {
    $result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - StockWise Pro</title>
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
                radial-gradient(circle at 20% 80%, rgba(30, 64, 175, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(5, 150, 105, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(15, 118, 110, 0.1) 0%, transparent 50%);
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
            background: linear-gradient(135deg, #4fc3f7 0%, #29b6f6 100%);
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
            border-left: 4px solid #4fc3f7;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            display: flex; align-items: center; gap: 15px;
        }
        
        .actions { display: flex; gap: 15px; }
        
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px; margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 15px; padding: 20px; text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative; overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }
        
        .stat-number {
            font-size: 1.8rem; font-weight: 800; margin-bottom: 5px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            font-size: 0.85rem; font-weight: 600; color: #6c757d;
            text-transform: uppercase; letter-spacing: 1px;
        }
        
        .users-table-container {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 20px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2); overflow: hidden;
        }
        
        .table-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 25px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .table-header h3 {
            font-size: 1.5rem; font-weight: 600;
            display: flex; align-items: center; gap: 12px;
        }
        
        .users-table { width: 100%; border-collapse: collapse; }
        
        .users-table th, .users-table td {
            padding: 20px; text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .users-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600; color: #495057; text-transform: uppercase;
            letter-spacing: 0.5px; font-size: 0.9rem;
        }
        
        .users-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: scale(1.01);
        }
        
        .user-avatar {
            width: 50px; height: 50px; border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 600; font-size: 1.2rem;
            margin-right: 15px; box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .user-info { display: flex; align-items: center; }
        
        .user-details h4 {
            font-weight: 600; color: #2c3e50; margin-bottom: 5px;
        }
        
        .user-details p { color: #6c757d; font-size: 0.9rem; }
        
        .btn {
            padding: 12px 24px; border: none; border-radius: 12px;
            font-weight: 600; font-size: 0.95rem; cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-decoration: none; display: inline-flex;
            align-items: center; gap: 8px; position: relative; overflow: hidden;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white; box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white; box-shadow: 0 8px 25px rgba(250, 112, 154, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .btn-sm { padding: 8px 16px; font-size: 0.85rem; }
        
        .status-badge {
            padding: 8px 16px; border-radius: 20px; font-size: 0.8rem;
            font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
        }
        
        .status-active {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .status-inactive {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        
        .role-badge {
            padding: 6px 12px; border-radius: 15px; font-size: 0.8rem;
            font-weight: 600; text-transform: capitalize;
        }
        
        .role-admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .role-staff {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .fab {
            position: fixed; bottom: 30px; right: 30px;
            width: 60px; height: 60px; border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; font-size: 1.5rem; cursor: pointer;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); z-index: 1000;
        }
        
        .fab:hover {
            transform: scale(1.1) translateY(-5px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.6);
        }
        
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; height: auto; }
            .main-content { padding: 20px; }
            .page-header { flex-direction: column; gap: 20px; text-align: center; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-boxes"></i>
                StockWise Pro
            </div>
            
            <!-- User info under StockWise Pro -->
            <?php if ($user_info): ?>
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: rgba(255, 255, 255, 0.1); border-radius: 12px; margin: 15px 20px; border: 1px solid rgba(255, 255, 255, 0.1);">
                <?php 
                // Check for profile photo using the same logic as dashboard
                $show_photo = false;
                $photo_src = '';
                
                if (!empty($user_info['profile_photo'])) {
                    // Try the path as stored in database
                    $photo_path = "../" . $user_info['profile_photo'];
                    if (file_exists($photo_path)) {
                        $show_photo = true;
                        $photo_src = $photo_path;
                    }
                }
                
                if ($show_photo): ?>
                    <img src="<?php echo htmlspecialchars($photo_src); ?>" 
                         alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255, 255, 255, 0.2);">
                <?php else: ?>
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #1e40af 0%, #0f766e 100%); display: flex; align-items: center; justify-content: center; color: white; border: 2px solid rgba(255, 255, 255, 0.2); font-weight: 600; font-size: 1.2rem;">
                        <?php echo strtoupper(substr($user_info['username'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: left;">
                    <span style="display: block; font-weight: 600; font-size: 0.9rem; color: white; margin-bottom: 2px;"><?php echo htmlspecialchars($user_info['username']); ?></span>
                    <span style="color: rgba(255,255,255,0.8); text-transform: capitalize; font-size: 0.75rem; background: rgba(79, 195, 247, 0.2); padding: 2px 8px; border-radius: 8px;"><?php echo ucfirst($user_info['role']); ?></span>
                </div>
            </div>
            <?php endif; ?>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="nav-item"><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                <li class="nav-item"><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <li class="nav-item"><a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
                <li class="nav-item"><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li class="nav-item"><a href="sales.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li class="nav-item"><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li class="nav-item active"><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li class="nav-item"><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                <li class="nav-item"><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-users"></i> Users Management</h1>
                    <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 1.1rem;">Manage system users and their permissions</p>
                </div>
                <div class="actions">
                    <button class="btn btn-success" onclick="showAddUserModal()"><i class="fas fa-user-plus"></i> Add New User</button>
                    <button class="btn btn-primary" onclick="exportUsers()"><i class="fas fa-download"></i> Export</button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($users); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($users, function($u) { return $u['status'] == 'active'; })); ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">0</div>
                    <div class="stat-label">Pending Approval</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($users, function($u) { return $u['status'] == 'inactive'; })); ?></div>
                    <div class="stat-label">Inactive Users</div>
                </div>
            </div>

            <div class="users-table-container">
                <div class="table-header">
                    <h3><i class="fas fa-users"></i> System Users</h3>
                    <div class="actions">
                        <button class="btn btn-secondary btn-sm" onclick="showBulkActionsModal()"><i class="fas fa-tasks"></i> Bulk Actions</button>
                    </div>
                </div>
                
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"> User Info
                            </th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                    </div>
                                    <div class="user-details">
                                        <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                                        <p><?php echo htmlspecialchars($user['email'] ?? 'admin@inventory.com'); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo date('M j, Y', strtotime($user['created_at'])); ?></strong>
                                <br><small style="color: #6c757d;"><?php echo date('g:i A', strtotime($user['created_at'])); ?></small>
                            </td>
                            <td>
                                <?php if (isset($user['last_login']) && $user['last_login']): ?>
                                    <strong><?php echo date('M j, Y', strtotime($user['last_login'])); ?></strong>
                                    <br><small style="color: #6c757d;"><?php echo date('g:i A', strtotime($user['last_login'])); ?></small>
                                <?php else: ?>
                                    <em style="color: #6c757d;">Never</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <input type="checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>" style="margin-right: 10px;">
                                    <button class="btn btn-warning btn-sm" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo $user['role']; ?>', '<?php echo $user['status']; ?>')" title="Edit User">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" title="Delete User">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Add New User</h3>
                <span class="close" onclick="closeModal('addUserModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role">Role *</label>
                    <select id="role" name="role" required>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> Edit User</h3>
                <span class="close" onclick="closeModal('editUserModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="form-group">
                    <label for="edit_username">Username *</label>
                    <input type="text" id="edit_username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email *</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_password">New Password (leave blank to keep current)</label>
                    <input type="password" id="edit_password" name="password">
                </div>
                <div class="form-group">
                    <label for="edit_role">Role *</label>
                    <select id="edit_role" name="role" required>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_status">Status *</label>
                    <select id="edit_status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                    <button type="submit" name="edit_user" class="btn btn-warning">Update User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Delete User</h3>
                <span class="close" onclick="closeModal('deleteUserModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" id="delete_user_id" name="user_id">
                <div class="form-group">
                    <p>Are you sure you want to delete the user <strong id="delete_username"></strong>?</p>
                    <p style="color: #dc3545; font-weight: 600;">This action cannot be undone!</p>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteUserModal')">Cancel</button>
                    <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions Modal -->
    <div id="bulkActionsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-tasks"></i> Bulk Actions</h3>
                <span class="close" onclick="closeModal('bulkActionsModal')">&times;</span>
            </div>
            <form method="POST" id="bulkActionsForm">
                <div class="form-group">
                    <label for="bulk_action">Select Action *</label>
                    <select id="bulk_action" name="bulk_action" required>
                        <option value="">Choose an action...</option>
                        <option value="activate">Activate Users</option>
                        <option value="deactivate">Deactivate Users</option>
                        <option value="delete">Delete Users</option>
                    </select>
                </div>
                <div class="form-group">
                    <p id="selectedUsersCount">No users selected</p>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('bulkActionsModal')">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="bulkActionBtn" disabled>Execute Action</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Styles -->
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close:hover {
            transform: scale(1.1);
        }

        .form-group {
            margin: 20px 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-actions {
            padding: 20px 25px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .form-actions .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
    </style>

    <!-- JavaScript Functions -->
    <script>
        // Show Add User Modal
        function showAddUserModal() {
            document.getElementById('addUserModal').style.display = 'block';
        }

        // Edit User Function
        function editUser(id, username, email, role, status) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_status').value = status;
            document.getElementById('editUserModal').style.display = 'block';
        }

        // Delete User Function
        function deleteUser(id, username) {
            document.getElementById('delete_user_id').value = id;
            document.getElementById('delete_username').textContent = username;
            document.getElementById('deleteUserModal').style.display = 'block';
        }

        // Show Bulk Actions Modal
        function showBulkActionsModal() {
            const selectedUsers = document.querySelectorAll('input[name="selected_users[]"]:checked');
            if (selectedUsers.length === 0) {
                alert('Please select at least one user first.');
                return;
            }
            
            document.getElementById('selectedUsersCount').textContent = 
                selectedUsers.length + ' user(s) selected';
            document.getElementById('bulkActionsModal').style.display = 'block';
        }

        // Close Modal Function
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Toggle Select All
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="selected_users[]"]');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        // Export Users Function
        function exportUsers() {
            window.location.href = 'export_users.php';
        }

        // Handle bulk actions form submission
        document.getElementById('bulkActionsForm').addEventListener('submit', function(e) {
            const selectedUsers = document.querySelectorAll('input[name="selected_users[]"]:checked');
            const action = document.getElementById('bulk_action').value;
            
            if (selectedUsers.length === 0) {
                e.preventDefault();
                alert('Please select at least one user.');
                return;
            }
            
            if (!action) {
                e.preventDefault();
                alert('Please select an action.');
                return;
            }
            
            if (action === 'delete') {
                if (!confirm('Are you sure you want to delete the selected users? This action cannot be undone!')) {
                    e.preventDefault();
                    return;
                }
            }
            
            // Add selected users to form
            selectedUsers.forEach(checkbox => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'selected_users[]';
                hiddenInput.value = checkbox.value;
                this.appendChild(hiddenInput);
            });
        });

        // Enable/disable bulk action button based on selection
        document.getElementById('bulk_action').addEventListener('change', function() {
            const btn = document.getElementById('bulkActionBtn');
            btn.disabled = !this.value;
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Show success/error messages
        <?php if ($success_message): ?>
        alert('<?php echo addslashes($success_message); ?>');
        <?php endif; ?>

        <?php if ($error_message): ?>
        alert('<?php echo addslashes($error_message); ?>');
        <?php endif; ?>
    </script>

    <button class="fab" onclick="showAddUserModal()" title="Add New User"><i class="fas fa-plus"></i></button>
</body>
</html>