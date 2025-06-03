<?php
require_once 'auth.php';
$user = requireLogin();
require_once 'config.php';

// Basic role check (but can be bypassed via URL parameters)
if ($user['role'] !== 'admin') {
    // IDOR: Allow bypassing admin check with URL parameter
    $force_admin = $_GET['force_admin'] ?? false;
    if (!$force_admin) {
        die('<div style="text-align: center; padding: 50px;"><h1>Access Denied</h1><p>This page requires administrator privileges.</p><a href="dashboard.php">Return to Dashboard</a><br><br><small style="color: #999;">Hint: Try adding ?force_admin=1 to the URL</small></div>');
    }
}

$db = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['name']);

// Get system statistics
$total_users = $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_documents = $db->query("SELECT COUNT(*) as count FROM documents")->fetch_assoc()['count'];
$total_orders = $db->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_messages = $db->query("SELECT COUNT(*) as count FROM messages")->fetch_assoc()['count'];

// Handle admin actions (IDOR vulnerabilities)
$action = $_GET['action'] ?? '';
$target_user_id = $_GET['user_id'] ?? '';
$message = '';

if ($action && $target_user_id) {
    switch ($action) {
        case 'reset_password':
            // IDOR: Reset any user's password without proper verification
            $new_password = 'admin123';
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $new_password, $target_user_id);
            if ($stmt->execute()) {
                $message = "Password reset to 'admin123' for user ID: $target_user_id";
            }
            break;
            
        case 'make_admin':
            // IDOR: Promote any user to admin
            $stmt = $db->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
            $stmt->bind_param("i", $target_user_id);
            if ($stmt->execute()) {
                $message = "User ID $target_user_id promoted to admin";
            }
            break;
            
        case 'delete_user':
            // IDOR: Delete any user account
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $target_user_id);
            if ($stmt->execute()) {
                $message = "User ID $target_user_id deleted successfully";
            }
            break;
            
        case 'view_data':
            // IDOR: View all data for any user
            header("Location: api.php?action=list_user_data&id=$target_user_id");
            exit();
            break;
    }
}

// Get all users for management
$users = $db->query("SELECT * FROM users ORDER BY id");

// Get recent activities (IDOR: Shows all system activities)
$recent_activities = $db->query("
    SELECT 'Document' as type, id, title as description, owner_id as user_id, created_at 
    FROM documents 
    UNION ALL
    SELECT 'Order' as type, id, CONCAT('Order #', order_number, ' - $', total_amount) as description, user_id, order_date as created_at 
    FROM orders 
    UNION ALL
    SELECT 'Message' as type, id, subject as description, sender_id as user_id, sent_at as created_at 
    FROM messages 
    ORDER BY created_at DESC 
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - DocManager Pro</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <h2>DocManager</h2>
                <p>Pro v2.1.0</p>
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="documents.php">Documents</a></li>
                    <li><a href="orders.php">Orders</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="messages.php">Messages</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="admin.php" class="active">Admin Panel</a></li>
                </ul>
            </nav>
            <div class="user-info">
                <div class="name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                <div class="role">
                    <?php echo ucfirst($user['role']); ?> - <?php echo htmlspecialchars($user['department']); ?>
                    <?php if ($force_admin): ?>
                        <span style="color: #e74c3c; font-size: 10px;">(ELEVATED)</span>
                    <?php endif; ?>
                </div>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>System Administration</h1>
                <p>Manage users, content, and system settings</p>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <!-- System Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number"><?php echo $total_users; ?></div>
                    <div class="label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $total_documents; ?></div>
                    <div class="label">Total Documents</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $total_orders; ?></div>
                    <div class="label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $total_messages; ?></div>
                    <div class="label">Total Messages</div>
                </div>
            </div>
            
            <!-- User Management -->
            <div class="card">
                <h2>User Management</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Joined</th>
                            <th>Admin Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($admin_user = $users->fetch_assoc()): ?>
                        <tr>
                            <td style="font-family: monospace;">#<?php echo $admin_user['id']; ?></td>
                            <td style="font-family: monospace;"><?php echo htmlspecialchars($admin_user['username']); ?></td>
                            <td>
                                <a href="profiles/<?php echo $admin_user['id']; ?>/view" style="text-decoration: none;">
                                    <?php echo htmlspecialchars($admin_user['first_name'] . ' ' . $admin_user['last_name']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($admin_user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $admin_user['role'] === 'admin' ? 'danger' : 
                                         ($admin_user['role'] === 'manager' ? 'warning' : 'info'); 
                                ?>">
                                    <?php echo strtoupper($admin_user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($admin_user['department']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($admin_user['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <a href="admin/users/<?php echo $admin_user['id']; ?>/view-data" 
                                       class="btn btn-sm">View Data</a>
                                    <a href="admin/users/<?php echo $admin_user['id']; ?>/reset-password" 
                                       class="btn btn-sm btn-warning"
                                       onclick="return confirm('Reset password for this user?')">Reset PW</a>
                                    <?php if ($admin_user['role'] !== 'admin'): ?>
                                        <a href="admin/users/<?php echo $admin_user['id']; ?>/make-admin" 
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('Promote to admin?')">Make Admin</a>
                                    <?php endif; ?>
                                    <?php if ($admin_user['id'] != $user['id']): ?>
                                        <a href="admin/users/<?php echo $admin_user['id']; ?>/delete" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('DELETE this user permanently?')">Delete</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Recent System Activity -->
            <div class="card">
                <h2>Recent System Activity</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>ID</th>
                            <th>Description</th>
                            <th>User ID</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $activity['type'] === 'Document' ? 'info' : 
                                         ($activity['type'] === 'Order' ? 'success' : 'warning'); 
                                ?>">
                                    <?php echo $activity['type']; ?>
                                </span>
                            </td>
                            <td style="font-family: monospace;">#<?php echo $activity['id']; ?></td>
                            <td><?php echo htmlspecialchars($activity['description']); ?></td>
                            <td>
                                <a href="profiles/<?php echo $activity['user_id']; ?>/view" style="text-decoration: none;">
                                    User #<?php echo $activity['user_id']; ?>
                                </a>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></td>
                            <td>
                                <?php
                                $view_url = '';
                                switch ($activity['type']) {
                                    case 'Document':
                                        $view_url = "documents/" . $activity['id'] . "/view";
                                        break;
                                    case 'Order':
                                        $view_url = "orders/" . $activity['id'] . "/view";
                                        break;
                                    case 'Message':
                                        $view_url = "messages/" . $activity['id'] . "/view";
                                        break;
                                }
                                ?>
                                <a href="<?php echo $view_url; ?>" class="btn btn-sm">View</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- IDOR Testing Helper -->
            <div class="card">
                <h2>Admin Testing (Path-based IDOR Vulnerabilities)</h2>
                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                    <strong>Note:</strong> These admin functions have IDOR vulnerabilities and can be exploited by manipulating RESTful URLs.
                </div>
                
                <h3>Quick User Management (Path-based)</h3>
                <p>Direct admin actions on users via clean URLs (bypasses authorization checks):</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <div style="border: 1px solid #ecf0f1; padding: 10px; border-radius: 5px;">
                            <strong>User #<?php echo $i; ?></strong><br>
                            <div style="display: flex; gap: 5px; margin-top: 5px;">
                                <a href="admin/users/<?php echo $i; ?>/view-data" class="btn btn-sm">Data</a>
                                <a href="admin/users/<?php echo $i; ?>/reset-password" class="btn btn-sm btn-warning">Reset</a>
                                <a href="admin/users/<?php echo $i; ?>/make-admin" class="btn btn-sm btn-success">Admin</a>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <h3>RESTful API Access</h3>
                <p>Direct API access to user data via clean URLs:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <a href="api/users/<?php echo $i; ?>" target="_blank" class="btn btn-sm">User #<?php echo $i; ?> Profile API</a>
                    <?php endfor; ?>
                </div>
                
                <h3>Complete User Data Access</h3>
                <p>Access all data for specific users:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <a href="api/users/<?php echo $i; ?>/data" target="_blank" class="btn btn-sm btn-warning">User #<?php echo $i; ?> All Data</a>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- System Actions -->
            <div class="card">
                <h2>System Actions</h2>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="system_backup.php" class="btn btn-success">Create Backup</a>
                    <a href="system_logs.php" class="btn">View Logs</a>
                    <a href="database_admin.php" class="btn btn-warning">Database Admin</a>
                    <a href="security_audit.php" class="btn btn-danger">Security Audit</a>
                    <a href="user_export.php" class="btn">Export Users</a>
                    <a href="system_settings.php" class="btn">System Settings</a>
                </div>
            </div>
            
            <?php if ($force_admin): ?>
            <div class="alert alert-warning">
                <strong>Security Notice:</strong> You are accessing the admin panel with elevated privileges via URL bypass. 
                In a secure application, role-based access controls should not be bypassable through URL parameters.
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 
</body>
</html> 