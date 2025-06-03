<?php
require_once 'auth.php';
$user = requireLogin();
global $config;

$db = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['name']);

// Get current user's reports
$stmt = $db->prepare("SELECT * FROM reports WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$my_reports = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Corporate Reports</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Corporate Reports Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
            <div style="text-align: right;">
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <div class="card">
            <h2>📂 My Reports</h2>
            <p>Access your business reports and analytics below.</p>
            <p><small>API Endpoint: <code>/api/v1/report/{UUID}/view</code></small></p>
            
            <?php if ($my_reports->num_rows > 0): ?>
                <div class="reports-grid">
                    <?php while ($report = $my_reports->fetch_assoc()): ?>
                        <div class="report-card">
                            <h3><?php echo htmlspecialchars($report['title']); ?></h3>
                            <div class="uuid">Report ID: <?php echo htmlspecialchars(substr($report['uuid'], 0, 8)); ?>...</div>
                            <div class="content"><?php echo htmlspecialchars(substr($report['content'], 0, 100)); ?>...</div>
                            <div style="margin-top: 15px;">
                                <a href="view_report.php?id=<?php echo $report['uuid']; ?>" class="btn btn-primary">View Report</a>
                                <br><small style="color: #666; margin-top: 5px; display: block;">Direct URL: view_report.php?id=<?php echo $report['uuid']; ?></small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No reports available for your account.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>📈 Quick Stats</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; text-align: center;">
                    <h3><?php echo $my_reports->num_rows; ?></h3>
                    <p>Total Reports</p>
                </div>
                <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; text-align: center;">
                    <h3><?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></h3>
                    <p>Department</p>
                </div>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                    <h3>Active</h3>
                    <p>Account Status</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>