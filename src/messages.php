<?php
require_once 'auth.php';
$user = requireLogin();
require_once 'config.php';

$db = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['name']);

// IDOR vulnerabilities: Allow viewing messages by user_id parameters
$view_user_id = $_GET['user_id'] ?? '';
$view_sent = $_GET['sent'] ?? false;
$view_all = $_GET['view_all'] ?? false;

// Build query with IDOR vulnerabilities
if ($view_all) {
    // IDOR: View all messages in the system
    $query = "SELECT m.*, 
                     sender.first_name as sender_first, sender.last_name as sender_last, sender.username as sender_username,
                     recipient.first_name as recipient_first, recipient.last_name as recipient_last, recipient.username as recipient_username
              FROM messages m 
              JOIN users sender ON m.sender_id = sender.id 
              JOIN users recipient ON m.recipient_id = recipient.id 
              ORDER BY m.sent_at DESC";
    $stmt = $db->prepare($query);
} elseif ($view_user_id) {
    // IDOR: View all messages for a specific user (sent and received)
    $query = "SELECT m.*, 
                     sender.first_name as sender_first, sender.last_name as sender_last, sender.username as sender_username,
                     recipient.first_name as recipient_first, recipient.last_name as recipient_last, recipient.username as recipient_username
              FROM messages m 
              JOIN users sender ON m.sender_id = sender.id 
              JOIN users recipient ON m.recipient_id = recipient.id 
              WHERE m.sender_id = ? OR m.recipient_id = ?
              ORDER BY m.sent_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $view_user_id, $view_user_id);
} elseif ($view_sent) {
    // View sent messages (legitimate)
    $query = "SELECT m.*, 
                     sender.first_name as sender_first, sender.last_name as sender_last, sender.username as sender_username,
                     recipient.first_name as recipient_first, recipient.last_name as recipient_last, recipient.username as recipient_username
              FROM messages m 
              JOIN users sender ON m.sender_id = sender.id 
              JOIN users recipient ON m.recipient_id = recipient.id 
              WHERE m.sender_id = ?
              ORDER BY m.sent_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $user['id']);
} else {
    // Default: View received messages (legitimate)
    $query = "SELECT m.*, 
                     sender.first_name as sender_first, sender.last_name as sender_last, sender.username as sender_username,
                     recipient.first_name as recipient_first, recipient.last_name as recipient_last, recipient.username as recipient_username
              FROM messages m 
              JOIN users sender ON m.sender_id = sender.id 
              JOIN users recipient ON m.recipient_id = recipient.id 
              WHERE m.recipient_id = ?
              ORDER BY m.sent_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $user['id']);
}

$stmt->execute();
$messages = $stmt->get_result();

// Get all users for filter dropdown (IDOR: exposes user list)
$users = $db->query("SELECT id, first_name, last_name, username, department FROM users ORDER BY first_name, last_name");

// Mark messages as read if viewing received messages
if (!$view_user_id && !$view_sent && !$view_all) {
    $db->query("UPDATE messages SET is_read = 1 WHERE recipient_id = " . $user['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - DocManager Pro</title>
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
                    <li><a href="messages.php" class="active">Messages</a></li>
                    <li><a href="profile.php">Profile</a></li>
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
                <h1>Message Center</h1>
                <p>Internal communication and messaging system</p>
            </div>
            
            <!-- Message Filter -->
            <div class="card">
                <h2>Message Filters</h2>
                <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                    <a href="messages.php" class="btn <?php echo !$view_sent && !$view_user_id && !$view_all ? 'btn-warning' : ''; ?>">
                        Inbox
                    </a>
                    <a href="messages.php?sent=1" class="btn <?php echo $view_sent ? 'btn-warning' : ''; ?>">
                        Sent Messages
                    </a>
                    
                    <?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?>
                        <a href="messages.php?view_all=1" class="btn <?php echo $view_all ? 'btn-danger' : 'btn-success'; ?>">
                            View All Messages
                        </a>
                    <?php endif; ?>
                    
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <label>View User Messages:</label>
                        <select onchange="if(this.value) window.location.href='messages/user/'+this.value">
                            <option value="">Select User...</option>
                            <?php while ($user_option = $users->fetch_assoc()): ?>
                                <option value="<?php echo $user_option['id']; ?>" 
                                        <?php echo $view_user_id == $user_option['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user_option['first_name'] . ' ' . $user_option['last_name']); ?> 
                                    (<?php echo htmlspecialchars($user_option['department']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Messages List -->
            <div class="card">
                <h2>Messages 
                    <?php if ($view_all): ?>
                        <span style="font-size: 14px; color: #e74c3c;">(All System Messages)</span>
                    <?php elseif ($view_user_id): ?>
                        <span style="font-size: 14px; color: #f39c12;">(User ID: <?php echo $view_user_id; ?>)</span>
                    <?php elseif ($view_sent): ?>
                        <span style="font-size: 14px; color: #3498db;">(Sent)</span>
                    <?php else: ?>
                        <span style="font-size: 14px; color: #27ae60;">(Inbox)</span>
                    <?php endif; ?>
                    <span style="font-size: 14px; color: #7f8c8d;">(<?php echo $messages->num_rows; ?> messages)</span>
                </h2>
                
                <?php if ($messages->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Subject</th>
                            <th>Preview</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($message = $messages->fetch_assoc()): ?>
                        <tr <?php echo !$message['is_read'] && $message['recipient_id'] == $user['id'] ? 'style="background-color: #f8f9fa; font-weight: bold;"' : ''; ?>>
                            <td style="font-family: monospace;">#<?php echo $message['id']; ?></td>
                            <td>
                                <a href="profiles/<?php echo $message['sender_id']; ?>/view" style="text-decoration: none;">
                                    <?php echo htmlspecialchars($message['sender_first'] . ' ' . $message['sender_last']); ?>
                                </a>
                                <div style="font-size: 11px; color: #7f8c8d;">@<?php echo htmlspecialchars($message['sender_username']); ?></div>
                            </td>
                            <td>
                                <a href="profiles/<?php echo $message['recipient_id']; ?>/view" style="text-decoration: none;">
                                    <?php echo htmlspecialchars($message['recipient_first'] . ' ' . $message['recipient_last']); ?>
                                </a>
                                <div style="font-size: 11px; color: #7f8c8d;">@<?php echo htmlspecialchars($message['recipient_username']); ?></div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($message['subject']); ?></strong>
                            </td>
                            <td>
                                <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; font-size: 12px; color: #7f8c8d;">
                                    <?php echo htmlspecialchars(substr($message['content'], 0, 80)); ?>...
                                </div>
                            </td>
                            <td>
                                <?php if ($message['is_read']): ?>
                                    <span class="badge badge-success">Read</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Unread</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($message['sent_at'])); ?></td>
                            <td>
                                <a href="messages/<?php echo $message['id']; ?>/view" class="btn btn-sm">View</a>
                                <a href="messages/<?php echo $message['id']; ?>/reply" class="btn btn-sm btn-success">Reply</a>
                                <?php if ($message['recipient_id'] == $user['id'] || $user['role'] === 'admin'): ?>
                                    <a href="messages/<?php echo $message['id']; ?>/delete" class="btn btn-sm btn-danger">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <h3>No messages found</h3>
                    <p>No messages in the current view.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- IDOR Testing Helper -->
            <div class="card">
                <h2>Quick Access (Path-based IDOR Testing)</h2>
                <p>Direct access to specific messages using RESTful URLs:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <a href="messages/<?php echo $i; ?>/view" class="btn btn-sm">Message #<?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                
                <h3>View Messages by User ID (Path-based)</h3>
                <p>Test IDOR by viewing messages for different users:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <a href="messages/user/<?php echo $i; ?>" 
                           class="btn btn-sm <?php echo $view_user_id == $i ? 'btn-warning' : ''; ?>">
                            User #<?php echo $i; ?> Messages
                        </a>
                    <?php endfor; ?>
                </div>
                
                <h3>RESTful API Access</h3>
                <p>Access user message data via API endpoints:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <a href="api/users/<?php echo $i; ?>/messages" target="_blank" class="btn btn-sm">User #<?php echo $i; ?> API</a>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="card">
                <h2>Actions</h2>
                <div style="display: flex; gap: 15px;">
                    <a href="compose_message.php" class="btn btn-success">Compose New Message</a>
                    <a href="message_search.php" class="btn">Search Messages</a>
                    <a href="export_messages.php" class="btn">Export Messages</a>
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="admin_messages.php" class="btn btn-warning">Admin Management</a>
                        <a href="message_analytics.php" class="btn">Analytics</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($view_all || ($view_user_id && $view_user_id != $user['id'])): ?>
            <div class="alert alert-warning">
                <strong>Security Notice:</strong> You are viewing messages that don't belong to you. 
                In a secure application, users should only see their own messages unless they have administrative privileges.
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 