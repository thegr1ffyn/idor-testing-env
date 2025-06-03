<?php
require_once 'auth.php';
$user = requireLogin();
global $config;

$report_id = $_GET['id'] ?? '';

if (!$report_id) {
    header('Location: dashboard.php');
    exit();
}

$db = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['name']);

if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

// IDOR VULNERABILITY: No ownership verification!
$stmt = $db->prepare("SELECT r.*, u.first_name, u.last_name, u.username, u.email FROM reports r JOIN users u ON r.owner_id = u.id WHERE r.uuid = ?");
$stmt->bind_param("s", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();

if (!$report) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($report['title']); ?> - Corporate Reports</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 <?php echo htmlspecialchars($report['title']); ?></h1>
            <p>Corporate Reports System</p>
            <div style="text-align: right;">
                <a href="dashboard.php" class="btn">← Back to Dashboard</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <div class="card">
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <strong>📄 Report ID:</strong><br>
                        <code><?php echo htmlspecialchars($report['uuid']); ?></code>
                    </div>
                    <div>
                        <strong>📅 Created:</strong><br>
                        <?php echo date('M j, Y g:i A', strtotime($report['created_at'])); ?>
                    </div>
                    <div>
                        <strong>👤 Author:</strong><br>
                        <?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?><br>
                        <small><?php echo htmlspecialchars($report['email']); ?></small>
                    </div>
                    <div>
                        <strong>👤 Viewing as:</strong><br>
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?><br>
                        <small><?php echo htmlspecialchars($user['username']); ?></small>
                    </div>
                </div>
            </div>
            
            <div style="background: #fff; padding: 25px; border: 1px solid #ddd; border-radius: 8px; line-height: 1.6;">
                <div style="font-size: 16px;">
                    <?php echo nl2br(htmlspecialchars($report['content'])); ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>📊 Report Actions</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <a href="dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
                <button class="btn" onclick="window.print()">🖨️ Print Report</button>
                <button class="btn" onclick="navigator.share({title: 'Report', url: window.location.href})" style="background: #17a2b8;">📤 Share</button>
            </div>
        </div>
    </div>
</body>
</html> 