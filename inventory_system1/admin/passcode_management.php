<?php
/**
 * Registration Passcode Management
 * Admin Only - Generate and manage registration passcodes
 */
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Enhanced passcode generation function
function generateUniquePasscode($length = 8) {
    $patterns = [
        'ALPHANUMERIC' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
        'NO_CONFUSING' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789', // Excludes I,O,0,1
        'MIXED_PATTERN' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
    ];
    
    $characters = $patterns['NO_CONFUSING']; // Use non-confusing characters
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

// Bulk passcode generation function
function generateBulkPasscodes($role, $count, $expires_in_days) {
    global $conn;
    $generated_passcodes = [];
    $expires_at = date('Y-m-d H:i:s', strtotime("+$expires_in_days days"));
    
    for ($i = 0; $i < $count; $i++) {
        $passcode = generateUniquePasscode();
        
        // Ensure uniqueness
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
            $stmt = $conn->prepare("INSERT INTO registration_passcodes (passcode, role, generated_by, expires_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $passcode, $role, $_SESSION['user_id'], $expires_at);
            
            if ($stmt->execute()) {
                $generated_passcodes[] = $passcode;
            }
        }
    }
    
    return $generated_passcodes;
}

// Check if user is admin
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle passcode generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'generate') {
    $role = $_POST['role'];
    $expires_in_days = (int)$_POST['expires_in_days'];
    
    if (empty($role) || $expires_in_days < 1 || $expires_in_days > 365) {
        $error_message = 'Please select a valid role and expiration period (1-365 days).';
    } else {
        // Generate unique passcode
        $passcode = generateUniquePasscode();
        
        // Ensure passcode is truly unique in database
        $attempts = 0;
        while ($attempts < 10) {
            $check_stmt = $conn->prepare("SELECT id FROM registration_passcodes WHERE passcode = ?");
            $check_stmt->bind_param("s", $passcode);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                break; // Passcode is unique
            }
            
            $passcode = generateUniquePasscode();
            $attempts++;
        }
        
        if ($attempts >= 10) {
            $error_message = 'Failed to generate unique passcode. Please try again.';
        } else {
            $expires_at = date('Y-m-d H:i:s', strtotime("+$expires_in_days days"));
            
            $stmt = $conn->prepare("INSERT INTO registration_passcodes (passcode, role, generated_by, expires_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $passcode, $role, $_SESSION['user_id'], $expires_at);
            
            if ($stmt->execute()) {
                $success_message = "Passcode generated successfully: <strong>$passcode</strong><br>Valid for $role registration until " . date('M j, Y g:i A', strtotime($expires_at));
            } else {
                $error_message = 'Failed to generate passcode. Please try again.';
            }
        }
    }
}

// Handle bulk passcode generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'generate_bulk') {
    $role = $_POST['role'];
    $count = (int)$_POST['count'];
    $expires_in_days = (int)$_POST['expires_in_days'];
    
    if (empty($role) || $count < 1 || $count > 20 || $expires_in_days < 1 || $expires_in_days > 365) {
        $error_message = 'Please select valid options (1-20 passcodes, 1-365 days expiration).';
    } else {
        $generated_passcodes = generateBulkPasscodes($role, $count, $expires_in_days);
        
        if (count($generated_passcodes) > 0) {
            $expires_at = date('Y-m-d H:i:s', strtotime("+$expires_in_days days"));
            $success_message = "Successfully generated " . count($generated_passcodes) . " $role passcodes:<br><strong>" . 
                             implode(', ', $generated_passcodes) . "</strong><br>Valid until " . 
                             date('M j, Y g:i A', strtotime($expires_at));
        } else {
            $error_message = 'Failed to generate passcodes. Please try again.';
        }
    }
}

// Handle passcode deactivation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'deactivate') {
    $passcode_id = (int)$_POST['passcode_id'];
    
    $stmt = $conn->prepare("UPDATE registration_passcodes SET is_used = TRUE WHERE id = ? AND generated_by = ?");
    $stmt->bind_param("ii", $passcode_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $success_message = 'Passcode deactivated successfully.';
    } else {
        $error_message = 'Failed to deactivate passcode.';
    }
}

// Get active passcodes
$active_passcodes_query = "
    SELECT rp.*, u.username as used_by_username 
    FROM registration_passcodes rp 
    LEFT JOIN users u ON rp.used_by = u.id 
    WHERE rp.generated_by = ? 
    ORDER BY rp.created_at DESC
";
$stmt = $conn->prepare($active_passcodes_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$passcodes = $stmt->get_result();

// Get passcode usage statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_generated,
        SUM(CASE WHEN is_used = TRUE THEN 1 ELSE 0 END) as total_used,
        SUM(CASE WHEN is_used = FALSE AND expires_at > NOW() THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN is_used = FALSE AND expires_at <= NOW() THEN 1 ELSE 0 END) as expired_count
    FROM registration_passcodes 
    WHERE generated_by = ?
";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $_SESSION['user_id']);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Passcode Management - IMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/stylesheets/style.css">
    <style>
        .passcode-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .passcode-display {
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            font-weight: bold;
            color: #3b82f6;
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 15px 0;
            border: 2px dashed #3b82f6;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .passcode-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .passcode-table th,
        .passcode-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .passcode-table th {
            background: #f9fafb;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-used {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-expired {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .copy-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .copy-btn:hover {
            background: #2563eb;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group select,
        .form-group input {
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
        }
    </style>
</head>
<body>
    <?php require_once '../includes/unified_header.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-key"></i> Registration Passcode Management</h1>
                <p>Generate and manage registration passcodes for new users</p>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_generated']; ?></div>
                    <div>Total Generated</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_count']; ?></div>
                    <div>Active Passcodes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_used']; ?></div>
                    <div>Successfully Used</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['expired_count']; ?></div>
                    <div>Expired</div>
                </div>
            </div>
            
            <!-- Generate New Passcode -->
            <div class="passcode-card">
                <h3><i class="fas fa-plus-circle"></i> Generate New Passcode</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="generate">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select name="role" id="role" required>
                                <option value="">Select Role</option>
                                <option value="staff">Staff Member</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="expires_in_days">Expires In (Days)</label>
                            <select name="expires_in_days" id="expires_in_days" required>
                                <option value="7">7 Days</option>
                                <option value="14">14 Days</option>
                                <option value="30" selected>30 Days</option>
                                <option value="60">60 Days</option>
                                <option value="90">90 Days</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> Generate Passcode
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Bulk Generate Passcodes -->
            <div class="passcode-card">
                <h3><i class="fas fa-layer-group"></i> Bulk Generate Passcodes</h3>
                <p style="color: #6b7280; margin-bottom: 20px;">Generate multiple passcodes at once for team registration</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="generate_bulk">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bulk_role">Role</label>
                            <select name="role" id="bulk_role" required>
                                <option value="">Select Role</option>
                                <option value="staff">Staff Members</option>
                                <option value="admin">Administrators</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="count">Number of Passcodes</label>
                            <select name="count" id="count" required>
                                <option value="">Select Count</option>
                                <option value="2">2 Passcodes</option>
                                <option value="3">3 Passcodes</option>
                                <option value="5" selected>5 Passcodes</option>
                                <option value="10">10 Passcodes</option>
                                <option value="15">15 Passcodes</option>
                                <option value="20">20 Passcodes</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="bulk_expires_in_days">Expires In (Days)</label>
                            <select name="expires_in_days" id="bulk_expires_in_days" required>
                                <option value="7">7 Days</option>
                                <option value="14">14 Days</option>
                                <option value="30" selected>30 Days</option>
                                <option value="60">60 Days</option>
                                <option value="90">90 Days</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div style="display: flex; gap: 10px;">
                                <button type="button" class="btn btn-secondary" onclick="previewPasscodes()">
                                    <i class="fas fa-eye"></i> Preview Passcodes
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-layer-group"></i> Generate & Save
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <!-- Preview Area -->
                <div id="passcodePreview" style="display: none; margin-top: 20px;">
                    <h4><i class="fas fa-eye"></i> Passcode Preview</h4>
                    <div id="previewContent" style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 2px dashed #3b82f6;">
                        <!-- Preview passcodes will appear here -->
                    </div>
                    <p style="color: #6b7280; font-size: 0.9rem; margin-top: 10px;">
                        <i class="fas fa-info-circle"></i> These are preview passcodes. Click "Generate & Save" to save them to the database.
                    </p>
                </div>
            </div>
            
            <!-- Passcode List -->
            <div class="passcode-card">
                <h3><i class="fas fa-list"></i> Generated Passcodes</h3>
                
                <?php if ($passcodes->num_rows > 0): ?>
                    <table class="passcode-table">
                        <thead>
                            <tr>
                                <th>Passcode</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Expires</th>
                                <th>Used By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($passcode = $passcodes->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <code style="font-weight: bold; color: #3b82f6;">
                                            <?php echo $passcode['passcode']; ?>
                                        </code>
                                        <button class="copy-btn" onclick="copyToClipboard('<?php echo $passcode['passcode']; ?>')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <i class="fas fa-<?php echo $passcode['role'] == 'admin' ? 'user-shield' : 'user'; ?>"></i>
                                        <?php echo ucfirst($passcode['role']); ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($passcode['is_used']) {
                                            echo '<span class="status-badge status-used">Used</span>';
                                        } elseif (strtotime($passcode['expires_at']) < time()) {
                                            echo '<span class="status-badge status-expired">Expired</span>';
                                        } else {
                                            echo '<span class="status-badge status-active">Active</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($passcode['created_at'])); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($passcode['expires_at'])); ?></td>
                                    <td>
                                        <?php echo $passcode['used_by_username'] ? $passcode['used_by_username'] : '-'; ?>
                                    </td>
                                    <td>
                                        <?php if (!$passcode['is_used'] && strtotime($passcode['expires_at']) > time()): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to deactivate this passcode?')">
                                                <input type="hidden" name="action" value="deactivate">
                                                <input type="hidden" name="passcode_id" value="<?php echo $passcode['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-ban"></i> Deactivate
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center" style="padding: 40px;">
                        <i class="fas fa-key" style="font-size: 3rem; color: #d1d5db; margin-bottom: 20px;"></i>
                        <p>No passcodes generated yet. Create your first passcode above.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const btn = event.target.closest('.copy-btn');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.style.background = '#10b981';
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '#3b82f6';
                }, 2000);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
                alert('Failed to copy passcode. Please copy manually: ' + text);
            });
        }
        
        // Auto-refresh page every 5 minutes to update expired status
        setTimeout(() => {
            location.reload();
        }, 300000);
        
        // Preview passcodes function
        function previewPasscodes() {
            const count = document.getElementById('count').value;
            const role = document.getElementById('bulk_role').value;
            
            if (!count || !role) {
                alert('Please select both role and number of passcodes first.');
                return;
            }
            
            // Show loading
            const previewDiv = document.getElementById('passcodePreview');
            const previewContent = document.getElementById('previewContent');
            
            previewDiv.style.display = 'block';
            previewContent.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating preview passcodes...';
            
            // Call API to generate preview passcodes
            fetch('../application_interface/generate_passcode.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ count: parseInt(count) })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px;">';
                    data.passcodes.forEach(passcode => {
                        html += `<div style="background: white; padding: 10px; border-radius: 5px; text-align: center; font-family: 'Courier New', monospace; font-weight: bold; color: #3b82f6; border: 1px solid #e5e7eb;">
                            ${passcode}
                            <button onclick="copyToClipboard('${passcode}')" style="margin-left: 5px; background: none; border: none; color: #6b7280; cursor: pointer;">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>`;
                    });
                    html += '</div>';
                    html += `<p style="margin-top: 10px; color: #6b7280; font-size: 0.9rem;">Generated ${data.count} unique ${role} passcodes</p>`;
                    
                    previewContent.innerHTML = html;
                } else {
                    previewContent.innerHTML = '<span style="color: #dc2626;"><i class="fas fa-exclamation-triangle"></i> ' + (data.error || 'Failed to generate passcodes') + '</span>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                previewContent.innerHTML = '<span style="color: #dc2626;"><i class="fas fa-exclamation-triangle"></i> Error generating preview passcodes</span>';
            });
        }
        
        // Add CSS for secondary button
        const style = document.createElement('style');
        style.textContent = `
            .btn-secondary {
                background: linear-gradient(135deg, #6b7280, #4b5563);
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 8px;
                transition: all 0.3s ease;
            }
            
            .btn-secondary:hover {
                background: linear-gradient(135deg, #4b5563, #374151);
                transform: translateY(-2px);
            }
        `;
        document.head.appendChild(style);
    </script>

<?php require_once '../includes/unified_footer.php'; ?>