<?php
require_once 'auth.php';
$user = requireLogin();
require_once 'config.php';

$db = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['name']);

// IDOR: Allow filtering by department, author_id, or viewing all reports
$filter_department = $_GET['department'] ?? '';
$filter_author_id = $_GET['author_id'] ?? '';
$show_confidential = $_GET['show_confidential'] ?? false;
$show_all = $_GET['show_all'] ?? false;

// Build query with IDOR vulnerabilities
$query = "SELECT r.*, u.first_name, u.last_name, u.username, u.department as author_department 
          FROM reports r 
          JOIN users u ON r.author_id = u.id 
          WHERE 1=1";

$params = [];
$types = "";

if (!$show_all && !$show_confidential) {
    // Default: show public reports or user's own reports (but can be bypassed)
    $query .= " AND (r.is_public = 1 OR r.author_id = ?)";
    $params[] = $user['id'];
    $types .= "i";
}

if ($show_confidential) {
    // IDOR: Allow viewing confidential reports without proper authorization
    $query .= " AND r.is_public = 0";
}

if ($filter_department) {
    $query .= " AND r.department = ?";
    $params[] = $filter_department;
    $types .= "s";
}

if ($filter_author_id) {
    // IDOR: No authorization check for viewing specific author's reports
    $query .= " AND r.author_id = ?";
    $params[] = $filter_author_id;
    $types .= "i";
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $db->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reports = $stmt->get_result();

// Get departments for filter
$departments = $db->query("SELECT DISTINCT department FROM reports WHERE department IS NOT NULL ORDER BY department");

// Get all users for author filter (IDOR: exposes user list)
$authors = $db->query("SELECT id, first_name, last_name, username, department FROM users ORDER BY first_name, last_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - DocManager Pro</title>
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
                    <li><a href="reports.php" class="active">Reports</a></li>
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
                <h1>Corporate Reports</h1>
                <p>Access and manage business reports and analytics</p>
            </div>
            
            <!-- Filter and Search -->
            <div class="card">
                <h2>Filter Reports</h2>
                <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div class="form-group">
                        <label for="department">Department:</label>
                        <select id="department" name="department">
                            <option value="">All Departments</option>
                            <?php while ($dept = $departments->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                        <?php echo $filter_department === $dept['department'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="author_id">Author:</label>
                        <select id="author_id" name="author_id">
                            <option value="">All Authors</option>
                            <?php while ($author = $authors->fetch_assoc()): ?>
                                <option value="<?php echo $author['id']; ?>" 
                                        <?php echo $filter_author_id == $author['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($author['first_name'] . ' ' . $author['last_name']); ?> 
                                    (<?php echo htmlspecialchars($author['department']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Access Level:</label>
                        <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                            <label style="display: flex; align-items: center; margin: 0;">
                                <input type="checkbox" name="show_all" value="1" <?php echo $show_all ? 'checked' : ''; ?> style="margin-right: 5px;">
                                All Reports
                            </label>
                            <label style="display: flex; align-items: center; margin: 0;">
                                <input type="checkbox" name="show_confidential" value="1" <?php echo $show_confidential ? 'checked' : ''; ?> style="margin-right: 5px;">
                                Confidential Only
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" class="btn">Filter</button>
                        <a href="reports.php" class="btn" style="margin-left: 10px;">Clear</a>
                    </div>
                </form>
            </div>
            
            <!-- Reports List -->
            <div class="card">
                <h2>Reports 
                    <?php if ($show_all): ?>
                        <span style="font-size: 14px; color: #e74c3c;">(All Reports)</span>
                    <?php elseif ($show_confidential): ?>
                        <span style="font-size: 14px; color: #d63031;">(Confidential Only)</span>
                    <?php endif; ?>
                    <span style="font-size: 14px; color: #7f8c8d;">(<?php echo $reports->num_rows; ?> found)</span>
                </h2>
                
                <?php if ($reports->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Department</th>
                            <th>Visibility</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($report = $reports->fetch_assoc()): ?>
                        <tr>
                            <td style="font-family: monospace;">#<?php echo $report['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($report['title']); ?></strong>
                                <div style="font-size: 12px; color: #7f8c8d;">
                                    <?php echo htmlspecialchars(substr($report['content'], 0, 100)); ?>...
                                </div>
                            </td>
                            <td>
                                <a href="profiles/<?php echo $report['author_id']; ?>/view" style="text-decoration: none;">
                                    <?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                                </a>
                                <div style="font-size: 11px; color: #7f8c8d;">@<?php echo htmlspecialchars($report['username']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($report['department']); ?></td>
                            <td>
                                <?php if ($report['is_public']): ?>
                                    <span class="badge badge-success">PUBLIC</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">CONFIDENTIAL</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($report['created_at'])); ?></td>
                            <td>
                                <a href="reports/<?php echo $report['id']; ?>/view" class="btn btn-sm">View</a>
                                <a href="reports/<?php echo $report['id']; ?>/download" class="btn btn-sm btn-success">Download</a>
                                <?php if ($report['author_id'] == $user['id'] || $user['role'] === 'admin'): ?>
                                    <a href="reports/<?php echo $report['id']; ?>/edit" class="btn btn-sm btn-warning">Edit</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <h3>No reports found</h3>
                    <p>No reports match your current filter criteria.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- IDOR Testing Helper -->
            <div class="card">
                <h2>Quick Access (Path-based IDOR Testing)</h2>
                <p>Direct access to specific reports using RESTful URLs:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <a href="reports/<?php echo $i; ?>/view" class="btn btn-sm">Report #<?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                
                <h3>Filter by Author ID (Path-based IDOR Testing)</h3>
                <p>View reports created by specific users via clean URLs:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <a href="reports/author/<?php echo $i; ?>" 
                           class="btn btn-sm <?php echo $filter_author_id == $i ? 'btn-warning' : ''; ?>">
                            Author #<?php echo $i; ?> Reports
                        </a>
                    <?php endfor; ?>
                </div>
                
                <h3>Access Confidential Reports</h3>
                <p>Attempt to access confidential reports (IDOR vulnerability):</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="reports.php?show_confidential=1" 
                       class="btn btn-sm <?php echo $show_confidential ? 'btn-danger' : 'btn-warning'; ?>">
                        Show Confidential Reports
                    </a>
                    <a href="reports.php?show_all=1" 
                       class="btn btn-sm <?php echo $show_all ? 'btn-danger' : ''; ?>">
                        Show All Reports
                    </a>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="card">
                <h2>Actions</h2>
                <div style="display: flex; gap: 15px;">
                    <a href="create_report.php" class="btn btn-success">Create New Report</a>
                    <a href="report_templates.php" class="btn">Report Templates</a>
                    <a href="export_reports.php" class="btn">Export All</a>
                    <?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?>
                        <a href="admin_reports.php" class="btn btn-warning">Manage Reports</a>
                        <a href="report_analytics.php" class="btn">Analytics</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($show_confidential || $show_all || ($filter_author_id && $filter_author_id != $user['id'])): ?>
            <div class="alert alert-warning">
                <strong>Security Notice:</strong> You may be viewing reports that should be restricted. 
                In a secure application, access to confidential reports should be properly controlled based on user roles and departments.
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 