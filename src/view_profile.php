<?php
require_once 'auth.php';
$user = requireLogin();
require_once 'config.php';

$db = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['name']);

$profile_id = $_GET['id'] ?? $user['id'];

// IDOR Vulnerability: No authorization check - any logged in user can view any profile
$stmt = $db->prepare("
    SELECT u.*, up.phone, up.address, up.bio, up.profile_picture, up.salary, up.emergency_contact 
    FROM users u 
    LEFT JOIN user_profiles up ON u.id = up.user_id 
    WHERE u.id = ?
");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

if (!$profile) {
    die("Profile not found");
}

// Get user's documents count
$doc_count = $db->query("SELECT COUNT(*) as count FROM documents WHERE owner_id = " . $profile_id)->fetch_assoc()['count'];

// Get user's orders count
$order_count = $db->query("SELECT COUNT(*) as count FROM orders WHERE user_id = " . $profile_id)->fetch_assoc()['count'];

// Get user's messages count
$msg_count = $db->query("SELECT COUNT(*) as count FROM messages WHERE recipient_id = " . $profile_id)->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile: <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?> - DocManager Pro</title>
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
                    <li><a href="profile.php" class="active">Profile</a></li>
                    <?php if ($user['role'] === 'admin'): ?>
                    <li><a href="admin.php">Admin Panel</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="user-info">
                <div class="name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                <div class="role"><?php echo ucfirst($user['role']); ?> - <?php echo htmlspecialchars($user['department']); ?></div>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>User Profile</h1>
                <p>Viewing user information and account details</p>
            </div>
            
            <div class="card">
                <h2><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h2>
                
                <div class="grid" style="grid-template-columns: 2fr 1fr;">
                    <div>
                        <div class="form-group">
                            <label>User ID:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px; font-family: monospace;">
                                #<?php echo htmlspecialchars($profile['id']); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Username:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px; font-family: monospace;">
                                <?php echo htmlspecialchars($profile['username']); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <?php echo htmlspecialchars($profile['email']); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Phone Number:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <?php echo htmlspecialchars($profile['phone'] ?? 'Not provided'); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Department:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <?php echo htmlspecialchars($profile['department']); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Role:</label>
                            <div style="padding: 10px;">
                                <span class="badge badge-<?php 
                                    echo $profile['role'] === 'admin' ? 'danger' : 
                                         ($profile['role'] === 'manager' ? 'warning' : 'info'); 
                                ?>" style="font-size: 14px; padding: 8px 12px;">
                                    <?php echo strtoupper($profile['role']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Address:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <?php echo htmlspecialchars($profile['address'] ?? 'Not provided'); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Bio:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <?php echo htmlspecialchars($profile['bio'] ?? 'No bio available'); ?>
                            </div>
                        </div>
                        
                        <!-- Sensitive Information - Should not be visible to other users -->
                        <?php if ($profile['salary']): ?>
                        <div class="form-group">
                            <label>Annual Salary:</label>
                            <div style="padding: 10px; background: #ffe6e6; border-radius: 5px; color: #d63031; font-weight: bold;">
                                $<?php echo number_format($profile['salary'], 2); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($profile['emergency_contact']): ?>
                        <div class="form-group">
                            <label>Emergency Contact:</label>
                            <div style="padding: 10px; background: #ffe6e6; border-radius: 5px; color: #d63031;">
                                <?php echo htmlspecialchars($profile['emergency_contact']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Account Created:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <?php echo date('F j, Y g:i A', strtotime($profile['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="card" style="margin: 0;">
                            <h3>Account Statistics</h3>
                            <div class="form-group">
                                <label>Documents Owned:</label>
                                <div style="padding: 8px; background: #f8f9fa; border-radius: 3px; text-align: center;">
                                    <span style="font-size: 24px; font-weight: bold; color: #3498db;"><?php echo $doc_count; ?></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Total Orders:</label>
                                <div style="padding: 8px; background: #f8f9fa; border-radius: 3px; text-align: center;">
                                    <span style="font-size: 24px; font-weight: bold; color: #27ae60;"><?php echo $order_count; ?></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Messages Received:</label>
                                <div style="padding: 8px; background: #f8f9fa; border-radius: 3px; text-align: center;">
                                    <span style="font-size: 24px; font-weight: bold; color: #f39c12;"><?php echo $msg_count; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card" style="margin: 20px 0 0 0;">
                            <h3>Quick Actions</h3>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php if ($profile_id == $user['id']): ?>
                                    <a href="edit_profile.php" class="btn btn-warning btn-sm">Edit Profile</a>
                                    <a href="change_password.php" class="btn btn-sm">Change Password</a>
                                <?php else: ?>
                                    <a href="send_message.php?to=<?php echo $profile['id']; ?>" class="btn btn-sm">Send Message</a>
                                    <a href="view_user_documents.php?user_id=<?php echo $profile['id']; ?>" class="btn btn-sm">View Documents</a>
                                <?php endif; ?>
                                
                                <?php if ($user['role'] === 'admin'): ?>
                                    <a href="admin_edit_user.php?id=<?php echo $profile['id']; ?>" class="btn btn-danger btn-sm">Admin Edit</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ecf0f1;">
                    <a href="dashboard.php" class="btn">Back to Dashboard</a>
                </div>
            </div>
            
            <!-- IDOR Testing Helper - Try different user IDs -->
            <div class="card">
                <h2>User Navigation</h2>
                <p>Quick access to other user profiles:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <a href="view_profile.php?id=<?php echo $i; ?>" 
                           class="btn btn-sm <?php echo $i == $profile_id ? 'btn-warning' : ''; ?>">
                            User #<?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
            
            <?php if ($profile_id != $user['id']): ?>
            <div class="alert alert-warning">
                <strong>Security Notice:</strong> You are viewing someone else's profile. 
                In a secure application, sensitive information like salary and emergency contacts should not be visible to other users.
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 