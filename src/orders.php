<?php
require_once 'auth.php';
$user = requireLogin();
require_once 'config.php';

$db = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['name']);

// IDOR: Allow viewing orders by user_id without proper authorization
$filter_user_id = $_GET['user_id'] ?? '';
$filter_status = $_GET['status'] ?? '';
$show_all = $_GET['show_all'] ?? false;

// Build query with IDOR vulnerabilities
if ($show_all) {
    // IDOR: Admin or manager can see all orders, but regular users shouldn't
    $query = "SELECT o.*, u.first_name, u.last_name, u.username, u.email, u.department 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE 1=1";
} else if ($filter_user_id) {
    // IDOR: No authorization check - any user can view any user's orders
    $query = "SELECT o.*, u.first_name, u.last_name, u.username, u.email, u.department 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.user_id = ?";
} else {
    // Default: Show user's own orders (but can be bypassed with parameters)
    $query = "SELECT o.*, u.first_name, u.last_name, u.username, u.email, u.department 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.user_id = ?";
    $filter_user_id = $user['id'];
}

$params = [];
$types = "";

if ($filter_user_id && !$show_all) {
    $params[] = $filter_user_id;
    $types .= "i";
}

if ($filter_status) {
    $query .= " AND o.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$query .= " ORDER BY o.order_date DESC";

$stmt = $db->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result();

// Get all users for filter dropdown (IDOR: exposes user list)
$users = $db->query("SELECT id, first_name, last_name, username, department FROM users ORDER BY first_name, last_name");

// Calculate totals
$total_orders = $orders->num_rows;
$total_amount = 0;
$orders_array = [];
while ($order = $orders->fetch_assoc()) {
    $orders_array[] = $order;
    $total_amount += $order['total_amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - DocManager Pro</title>
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
                <h1>Order Management</h1>
                <p>View and manage order history</p>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number"><?php echo $total_orders; ?></div>
                    <div class="label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="number">$<?php echo number_format($total_amount, 2); ?></div>
                    <div class="label">Total Value</div>
                </div>
                <div class="stat-card">
                    <div class="number">$<?php echo $total_orders > 0 ? number_format($total_amount / $total_orders, 2) : '0.00'; ?></div>
                    <div class="label">Average Order</div>
                </div>
            </div>
            
            <!-- Filter Card -->
            <div class="card">
                <h2>Filter Orders</h2>
                <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr auto auto; gap: 15px; align-items: end;">
                    <div class="form-group">
                        <label for="user_id">Filter by Customer:</label>
                        <select id="user_id" name="user_id">
                            <option value="">My Orders Only</option>
                            <?php while ($user_option = $users->fetch_assoc()): ?>
                                <option value="<?php echo $user_option['id']; ?>" 
                                        <?php echo $filter_user_id == $user_option['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user_option['first_name'] . ' ' . $user_option['last_name']); ?> 
                                    (<?php echo htmlspecialchars($user_option['department']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Filter by Status:</label>
                        <select id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $filter_status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div>
                        <button type="submit" class="btn">Filter</button>
                    </div>
                    
                    <div>
                        <?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?>
                            <a href="orders.php?show_all=1<?php echo $filter_status ? '&status=' . $filter_status : ''; ?>" 
                               class="btn <?php echo $show_all ? 'btn-warning' : 'btn-success'; ?>">
                                <?php echo $show_all ? 'Hide All' : 'Show All Orders'; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Orders List -->
            <div class="card">
                <h2>Orders 
                    <?php if ($show_all): ?>
                        <span style="font-size: 14px; color: #e74c3c;">(All Users)</span>
                    <?php elseif ($filter_user_id && $filter_user_id != $user['id']): ?>
                        <span style="font-size: 14px; color: #f39c12;">(User ID: <?php echo $filter_user_id; ?>)</span>
                    <?php endif; ?>
                </h2>
                
                <?php if (!empty($orders_array)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Order Number</th>
                            <th>Customer</th>
                            <th>Department</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Order Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders_array as $order): ?>
                        <tr>
                            <td style="font-family: monospace;">#<?php echo $order['id']; ?></td>
                            <td style="font-family: monospace;"><?php echo htmlspecialchars($order['order_number']); ?></td>
                            <td>
                                <a href="profiles/<?php echo $order['user_id']; ?>/view" style="text-decoration: none;">
                                    <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                </a>
                                <div style="font-size: 11px; color: #7f8c8d;"><?php echo htmlspecialchars($order['email']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($order['department']); ?></td>
                            <td style="font-weight: bold; color: #27ae60;">$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $order['status'] === 'completed' ? 'success' : 
                                         ($order['status'] === 'processing' ? 'warning' : 
                                         ($order['status'] === 'cancelled' ? 'danger' : 'info')); 
                                ?>">
                                    <?php echo strtoupper($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                            <td>
                                <a href="orders/<?php echo $order['id']; ?>/view" class="btn btn-sm">View</a>
                                <a href="orders/<?php echo $order['id']; ?>/invoice" class="btn btn-sm btn-success">Invoice</a>
                                <?php if ($order['user_id'] == $user['id'] || $user['role'] === 'admin'): ?>
                                    <a href="orders/<?php echo $order['id']; ?>/edit" class="btn btn-sm btn-warning">Edit</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <h3>No orders found</h3>
                    <p>No orders match your current filter criteria.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- IDOR Testing Helper -->
            <div class="card">
                <h2>Quick Access (Path-based IDOR Testing)</h2>
                <p>Direct access to specific orders using RESTful URLs:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <a href="orders/<?php echo $i; ?>/view" class="btn btn-sm">Order #<?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                
                <h3>View Orders by User ID (Path-based)</h3>
                <p>Test IDOR by viewing orders belonging to different users:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <a href="orders/user/<?php echo $i; ?>" 
                           class="btn btn-sm <?php echo $filter_user_id == $i ? 'btn-warning' : ''; ?>">
                            User #<?php echo $i; ?> Orders
                        </a>
                    <?php endfor; ?>
                </div>
                
                <h3>RESTful API Access</h3>
                <p>Access user order data via API endpoints:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <a href="api/users/<?php echo $i; ?>/orders" target="_blank" class="btn btn-sm">User #<?php echo $i; ?> API</a>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="card">
                <h2>Actions</h2>
                <div style="display: flex; gap: 15px;">
                    <a href="create_order.php" class="btn btn-success">Create New Order</a>
                    <a href="export_orders.php" class="btn">Export Orders</a>
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="admin_orders.php" class="btn btn-warning">Admin Management</a>
                        <a href="order_analytics.php" class="btn">Analytics</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (($filter_user_id && $filter_user_id != $user['id']) || $show_all): ?>
            <div class="alert alert-warning">
                <strong>Security Notice:</strong> You are viewing orders that don't belong to you. 
                In a secure application, users should only see their own orders unless they have administrative privileges.
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 