<?php
require_once 'auth.php';
$user = requireLogin();
require_once 'config.php';

// Database connection
$db = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['name']);

// Get statistics
$totalDocs = $db->query("SELECT COUNT(*) as count FROM documents")->fetch_assoc()['count'];
$myDocs = $db->query("SELECT COUNT(*) as count FROM documents WHERE owner_id = " . $user['id'])->fetch_assoc()['count'];
$totalOrders = $db->query("SELECT COUNT(*) as count FROM orders WHERE user_id = " . $user['id'])->fetch_assoc()['count'];
$unreadMessages = $db->query("SELECT COUNT(*) as count FROM messages WHERE recipient_id = " . $user['id'] . " AND is_read = 0")->fetch_assoc()['count'];

// Get recent documents
$recentDocs = $db->query("SELECT * FROM documents ORDER BY created_at DESC LIMIT 5");

// Get recent orders
$recentOrders = $db->query("SELECT * FROM orders WHERE user_id = " . $user['id'] . " ORDER BY order_date DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DocManager Pro</title>
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
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="documents.php">Documents</a></li>
                    <li><a href="orders.php">Orders</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="messages.php">Messages</a></li>
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
                <h1>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                <p>Here's an overview of your DocManager Pro account</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number"><?php echo $totalDocs; ?></div>
                    <div class="label">Total Documents</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $myDocs; ?></div>
                    <div class="label">My Documents</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $totalOrders; ?></div>
                    <div class="label">My Orders</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $unreadMessages; ?></div>
                    <div class="label">Unread Messages</div>
                </div>
            </div>
            
            <div class="grid">
                <div class="card">
                    <h2>Recent Documents</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Department</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($doc = $recentDocs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doc['title']); ?></td>
                                <td><?php echo htmlspecialchars($doc['department']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($doc['created_at'])); ?></td>
                                <td><a href="view_document.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm">View</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="card">
                    <h2>My Recent Orders</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $recentOrders->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $order['status'] === 'completed' ? 'success' : 
                                             ($order['status'] === 'processing' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm">View</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <h2>Quick Actions</h2>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="documents.php" class="btn">Browse Documents</a>
                    <a href="orders.php" class="btn btn-success">View All Orders</a>
                    <a href="reports.php" class="btn btn-warning">Generate Report</a>
                    <a href="messages.php" class="btn">Check Messages</a>
                    <a href="profile.php" class="btn">Update Profile</a>
                </div>
            </div>
            
            <!-- IDOR Testing Helper -->
            <div class="card">
                <h2>Quick Access (Path-based IDOR Testing)</h2>
                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                    <strong>Security Testing:</strong> This environment contains intentional IDOR (Insecure Direct Object Reference) vulnerabilities using RESTful URL patterns.
                </div>
                
                <h3>Direct Resource Access</h3>
                <p>Access resources directly via path-based URLs:</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <div style="border: 1px solid #ecf0f1; padding: 10px; border-radius: 5px;">
                        <strong>Documents</strong><br>
                        <div style="display: flex; gap: 5px; margin-top: 5px; flex-wrap: wrap;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <a href="documents/<?php echo $i; ?>/view" class="btn btn-sm">Doc #<?php echo $i; ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div style="border: 1px solid #ecf0f1; padding: 10px; border-radius: 5px;">
                        <strong>Orders</strong><br>
                        <div style="display: flex; gap: 5px; margin-top: 5px; flex-wrap: wrap;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <a href="orders/<?php echo $i; ?>/view" class="btn btn-sm">Order #<?php echo $i; ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div style="border: 1px solid #ecf0f1; padding: 10px; border-radius: 5px;">
                        <strong>Profiles</strong><br>
                        <div style="display: flex; gap: 5px; margin-top: 5px; flex-wrap: wrap;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <a href="profiles/<?php echo $i; ?>/view" class="btn btn-sm">User #<?php echo $i; ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div style="border: 1px solid #ecf0f1; padding: 10px; border-radius: 5px;">
                        <strong>Messages</strong><br>
                        <div style="display: flex; gap: 5px; margin-top: 5px; flex-wrap: wrap;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <a href="messages/<?php echo $i; ?>/view" class="btn btn-sm">Msg #<?php echo $i; ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div style="border: 1px solid #ecf0f1; padding: 10px; border-radius: 5px;">
                        <strong>Reports</strong><br>
                        <div style="display: flex; gap: 5px; margin-top: 5px; flex-wrap: wrap;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <a href="reports/<?php echo $i; ?>/view" class="btn btn-sm">Report #<?php echo $i; ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <h3>Collection Filters (User-based IDOR)</h3>
                <p>Filter collections by user ID via path parameters:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <div style="border: 1px solid #ecf0f1; padding: 8px; border-radius: 5px;">
                            <strong>User #<?php echo $i; ?></strong><br>
                            <div style="display: flex; gap: 3px; margin-top: 3px;">
                                <a href="documents/user/<?php echo $i; ?>" class="btn btn-sm">Docs</a>
                                <a href="orders/user/<?php echo $i; ?>" class="btn btn-sm">Orders</a>
                                <a href="messages/user/<?php echo $i; ?>" class="btn btn-sm">Messages</a>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <h3>RESTful API Endpoints</h3>
                <p>Access user data via RESTful API patterns:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <div style="border: 1px solid #ecf0f1; padding: 8px; border-radius: 5px;">
                            <strong>User #<?php echo $i; ?> API</strong><br>
                            <div style="display: flex; gap: 3px; margin-top: 3px;">
                                <a href="api/users/<?php echo $i; ?>" target="_blank" class="btn btn-sm">Profile</a>
                                <a href="api/users/<?php echo $i; ?>/data" target="_blank" class="btn btn-sm">All Data</a>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <h3>Admin Functions (Path-based Bypass)</h3>
                <p>Admin functions accessible via clean URLs:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <a href="admin/users/<?php echo $i; ?>/view-data" class="btn btn-sm btn-warning">Admin User #<?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 