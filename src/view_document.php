<?php
require_once 'auth.php';
$user = requireLogin();
require_once 'config.php';

$db = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['name']);

$document_id = $_GET['id'] ?? 0;

// IDOR Vulnerability: No authorization check - any logged in user can view any document
$stmt = $db->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->bind_param("i", $document_id);
$stmt->execute();
$result = $stmt->get_result();
$document = $result->fetch_assoc();

if (!$document) {
    die("Document not found");
}

// Get owner information
$owner_stmt = $db->prepare("SELECT first_name, last_name, department FROM users WHERE id = ?");
$owner_stmt->bind_param("i", $document['owner_id']);
$owner_stmt->execute();
$owner_result = $owner_stmt->get_result();
$owner = $owner_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document: <?php echo htmlspecialchars($document['title']); ?> - DocManager Pro</title>
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
                <h1>Document Details</h1>
                <p>Viewing document information and metadata</p>
            </div>
            
            <div class="card">
                <h2><?php echo htmlspecialchars($document['title']); ?></h2>
                
                <div class="grid" style="grid-template-columns: 2fr 1fr;">
                    <div>
                        <div class="form-group">
                            <label>Document ID:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px; font-family: monospace;">
                                <?php echo htmlspecialchars($document['id']); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Title:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <?php echo htmlspecialchars($document['title']); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Filename:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px; font-family: monospace;">
                                <?php echo htmlspecialchars($document['filename']); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>File Path:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px; font-family: monospace;">
                                <?php echo htmlspecialchars($document['file_path']); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Department:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <?php echo htmlspecialchars($document['department']); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Created:</label>
                            <div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <?php echo date('F j, Y g:i A', strtotime($document['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="card" style="margin: 0;">
                            <h3>Owner Information</h3>
                            <div class="form-group">
                                <label>Owner ID:</label>
                                <div style="padding: 8px; background: #f8f9fa; border-radius: 3px; font-family: monospace;">
                                    <a href="view_profile.php?id=<?php echo $document['owner_id']; ?>" style="text-decoration: none;">
                                        #<?php echo $document['owner_id']; ?>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Name:</label>
                                <div style="padding: 8px; background: #f8f9fa; border-radius: 3px;">
                                    <?php echo htmlspecialchars($owner['first_name'] . ' ' . $owner['last_name']); ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Department:</label>
                                <div style="padding: 8px; background: #f8f9fa; border-radius: 3px;">
                                    <?php echo htmlspecialchars($owner['department']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card" style="margin: 20px 0 0 0;">
                            <h3>Security Level</h3>
                            <div style="text-align: center; padding: 20px;">
                                <?php if ($document['is_confidential']): ?>
                                    <span class="badge badge-danger" style="font-size: 14px; padding: 8px 12px;">CONFIDENTIAL</span>
                                <?php else: ?>
                                    <span class="badge badge-success" style="font-size: 14px; padding: 8px 12px;">PUBLIC</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ecf0f1;">
                    <a href="download_document.php?id=<?php echo $document['id']; ?>" class="btn btn-success">Download File</a>
                    <a href="documents.php" class="btn">Back to Documents</a>
                    
                    <?php if ($document['owner_id'] == $user['id'] || $user['role'] === 'admin'): ?>
                        <a href="edit_document.php?id=<?php echo $document['id']; ?>" class="btn btn-warning">Edit Document</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- IDOR Testing Helper - Try different document IDs -->
            <div class="card">
                <h2>Document Navigation</h2>
                <p>Quick access to other documents:</p>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <a href="view_document.php?id=<?php echo $i; ?>" 
                           class="btn btn-sm <?php echo $i == $document_id ? 'btn-warning' : ''; ?>">
                            Doc #<?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 