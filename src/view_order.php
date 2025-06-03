<?php
require_once 'auth.php';
$user = requireLogin();
require_once 'config.php';

$db = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['name']);

$order_id = $_GET['id'] ?? 0;

// IDOR Vulnerability: No ownership check - any logged in user can view any order
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("Order not found");
}

// Get customer information
$customer_stmt = $db->prepare("SELECT first_name, last_name, email, department FROM users WHERE id = ?");
$customer_stmt->bind_param("i", $order['user_id']);
$customer_stmt->execute();
$customer_result = $customer_stmt->get_result();
$customer = $customer_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order: <?php echo htmlspecialchars($order['order_number']); ?> - DocManager Pro</title>
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
                    <li><a href="orders.php" class="active">Orders</a></li>
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
                <h1>Order Details</h1>
                <p>Viewing order information and transaction details</p>
            </div>
            
            <div class="card">
                <h2>Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                
                <div class="grid" style="grid-template-columns: 2fr 1fr;">
                    <div>
                        <div class="form-group">
                            <label>Order ID:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px; font-family: monospace;">
                                <?php echo htmlspecialchars($order['id']); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Order Number:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px; font-family: monospace;">
                                <?php echo htmlspecialchars($order['order_number']); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Total Amount:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px; font-size: 18px; font-weight: bold; color: #27ae60;">
                                $<?php echo number_format($order['total_amount'], 2); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Status:</label>
                            <div style="padding: 10px;">
                                <span class="badge badge-<?php 
                                    echo $order['status'] === 'completed' ? 'success' : 
                                         ($order['status'] === 'processing' ? 'warning' : 
                                         ($order['status'] === 'cancelled' ? 'danger' : 'info')); 
                                ?>" style="font-size: 14px; padding: 8px 12px;">
                                    <?php echo strtoupper($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Order Date:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="card" style="margin: 0;">
                            <h3>Customer Information</h3>
                            <div class="form-group">
                                <label>Customer ID:</label>
                                <div style="padding: 8px; background: #f8f9fa; border-radius: 3px; font-family: monospace;">
                                    <a href="view_profile.php?id=<?php echo $order['user_id']; ?>" style="text-decoration: none;">
                                        #<?php echo $order['user_id']; ?>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Name:</label>
                                <div style="padding: 8px; background: #f8f9fa; border-radius: 3px;">
                                    <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Email:</label>
                                <div style="padding: 8px; background: #f8f9fa; border-radius: 3px; font-family: monospace; font-size: 12px;">
                                    <?php echo htmlspecialchars($customer['email']); ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Department:</label>
                                <div style="padding: 8px; background: #f8f9fa; border-radius: 3px;">
                                    <?php echo htmlspecialchars($customer['department']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card" style="margin: 20px 0 0 0;">
                            <h3>Order Actions</h3>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php if ($order['user_id'] == $user['id'] || $user['role'] === 'admin'): ?>
                                    <a href="edit_order.php?id=<?php echo $order['id']; ?>" class="btn btn-warning btn-sm">Edit Order</a>
                                <?php endif; ?>
                                <a href="download_invoice.php?order_id=<?php echo $order['id']; ?>" class="btn btn-success btn-sm">Download Invoice</a>
                                <a href="duplicate_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm">Duplicate Order</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ecf0f1;">
                    <a href="orders.php" class="btn">Back to Orders</a>
                </div>
            </div>
            
            <!-- IDOR Testing Helper - Try different order IDs -->
            <div class="card">
                <h2>Order Navigation</h2>
                <p>Quick access to other orders:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <a href="view_order.php?id=<?php echo $i; ?>" 
                           class="btn btn-sm <?php echo $i == $order_id ? 'btn-warning' : ''; ?>">
                            Order #<?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
            
            <?php if ($order['user_id'] != $user['id'] && $user['role'] !== 'admin'): ?>
            <div class="alert alert-warning">
                <strong>Note:</strong> You are viewing an order that doesn't belong to you. 
                This might be a security concern in a real application.
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 