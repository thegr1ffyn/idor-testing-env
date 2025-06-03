<?php
require_once 'auth.php';
$user = requireLogin();
require_once 'config.php';

$db = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['name']);

// IDOR: Allow filtering by department or user_id without proper authorization checks
$filter_department = $_GET['department'] ?? '';
$filter_user_id = $_GET['user_id'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with potential IDOR vulnerabilities
$query = "SELECT d.*, u.first_name, u.last_name, u.username 
          FROM documents d 
          JOIN users u ON d.owner_id = u.id 
          WHERE 1=1";

$params = [];
$types = "";

if ($filter_department) {
    $query .= " AND d.department = ?";
    $params[] = $filter_department;
    $types .= "s";
}

if ($filter_user_id) {
    // IDOR: No check if current user should see this user's documents
    $query .= " AND d.owner_id = ?";
    $params[] = $filter_user_id;
    $types .= "i";
}

if ($search) {
    $query .= " AND (d.title LIKE ? OR d.filename LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

$query .= " ORDER BY d.created_at DESC";

$stmt = $db->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$documents = $stmt->get_result();

// Get all departments for filter
$departments = $db->query("SELECT DISTINCT department FROM documents WHERE department IS NOT NULL ORDER BY department");

// Get all users for filter (IDOR: Should not expose all users)
$users = $db->query("SELECT id, first_name, last_name, username, department FROM users ORDER BY first_name, last_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - DocManager Pro</title>
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
                    <li><a href="documents.php" class="active">Documents</a></li>
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
                <h1>Document Library</h1>
                <p>Browse and manage corporate documents</p>
            </div>
            
            <!-- Search and Filter Card -->
            <div class="card">
                <h2>Search & Filter</h2>
                <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div class="form-group">
                        <label for="search">Search Documents:</label>
                        <input type="text" id="search" name="search" placeholder="Title or filename..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="department">Filter by Department:</label>
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
                        <label for="user_id">Filter by Owner:</label>
                        <select id="user_id" name="user_id">
                            <option value="">All Users</option>
                            <?php while ($user_option = $users->fetch_assoc()): ?>
                                <option value="<?php echo $user_option['id']; ?>" 
                                        <?php echo $filter_user_id == $user_option['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user_option['first_name'] . ' ' . $user_option['last_name']); ?> 
                                    (<?php echo htmlspecialchars($user_option['department']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <button type="submit" class="btn">Search</button>
                        <a href="documents.php" class="btn" style="margin-left: 10px;">Clear</a>
                    </div>
                </form>
            </div>
            
            <!-- Documents List -->
            <div class="card">
                <h2>Documents 
                    <?php if ($filter_department || $filter_user_id || $search): ?>
                        <span style="font-size: 14px; color: #7f8c8d;">
                            (Filtered: <?php echo $documents->num_rows; ?> results)
                        </span>
                    <?php endif; ?>
                </h2>
                
                <?php if ($documents->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Filename</th>
                            <th>Owner</th>
                            <th>Department</th>
                            <th>Security</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($doc = $documents->fetch_assoc()): ?>
                        <tr>
                            <td style="font-family: monospace;">#<?php echo $doc['id']; ?></td>
                            <td><?php echo htmlspecialchars($doc['title']); ?></td>
                            <td style="font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($doc['filename']); ?></td>
                            <td>
                                <a href="profiles/<?php echo $doc['owner_id']; ?>/view" style="text-decoration: none;">
                                    <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?>
                                </a>
                                <div style="font-size: 11px; color: #7f8c8d;">@<?php echo htmlspecialchars($doc['username']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($doc['department']); ?></td>
                            <td>
                                <?php if ($doc['is_confidential']): ?>
                                    <span class="badge badge-danger">CONFIDENTIAL</span>
                                <?php else: ?>
                                    <span class="badge badge-success">PUBLIC</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($doc['created_at'])); ?></td>
                            <td>
                                <a href="documents/<?php echo $doc['id']; ?>/view" class="btn btn-sm">View</a>
                                <a href="documents/<?php echo $doc['id']; ?>/download" class="btn btn-sm btn-success">Download</a>
                                <?php if ($doc['owner_id'] == $user['id'] || $user['role'] === 'admin'): ?>
                                    <a href="documents/<?php echo $doc['id']; ?>/edit" class="btn btn-sm btn-warning">Edit</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <h3>No documents found</h3>
                    <p>Try adjusting your search criteria or clear the filters.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- IDOR Testing Helper -->
            <div class="card">
                <h2>Quick Access (Path-based IDOR Testing)</h2>
                <p>Direct access to specific documents using RESTful URLs:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <a href="documents/<?php echo $i; ?>/view" class="btn btn-sm">Doc #<?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                
                <h3>Filter by User ID (Path-based IDOR Testing)</h3>
                <p>View documents owned by specific users via clean URLs:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <a href="documents/user/<?php echo $i; ?>" 
                           class="btn btn-sm <?php echo $filter_user_id == $i ? 'btn-warning' : ''; ?>">
                            User #<?php echo $i; ?> Docs
                        </a>
                    <?php endfor; ?>
                </div>
                
                <h3>RESTful API Access</h3>
                <p>Access user data via API endpoints:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <a href="api/users/<?php echo $i; ?>/documents" target="_blank" class="btn btn-sm">User #<?php echo $i; ?> API</a>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- Upload New Document (if authorized) -->
            <div class="card">
                <h2>Actions</h2>
                <div style="display: flex; gap: 15px;">
                    <a href="upload_document.php" class="btn btn-success">Upload New Document</a>
                    <a href="bulk_download.php" class="btn">Bulk Download</a>
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="admin_documents.php" class="btn btn-warning">Admin Management</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 