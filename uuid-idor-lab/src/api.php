<?php
header('Content-Type: application/json');

require_once 'auth.php';
$user = requireLogin();
global $config;

$report_id = $_GET['report_id'] ?? '';

if (!$report_id) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Bad Request',
        'message' => 'Report ID is required',
        'code' => 400
    ]);
    exit();
}

$db = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['name']);

if ($db->connect_error) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => 'Database connection failed',
        'code' => 500
    ]);
    exit();
}

// IDOR VULNERABILITY: No ownership verification!
$stmt = $db->prepare("SELECT r.*, u.first_name, u.last_name, u.username, u.email FROM reports r JOIN users u ON r.owner_id = u.id WHERE r.uuid = ?");
$stmt->bind_param("s", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();

if (!$report) {
    http_response_code(404);
    echo json_encode([
        'error' => 'Not Found',
        'message' => 'Report not found',
        'code' => 404
    ]);
    exit();
}

// Return report data as JSON
http_response_code(200);
echo json_encode([
    'success' => true,
    'data' => [
        'report' => [
            'id' => $report['uuid'],
            'title' => $report['title'],
            'content' => $report['content'],
            'created_at' => $report['created_at']
        ],
        'author' => [
            'name' => $report['first_name'] . ' ' . $report['last_name'],
            'username' => $report['username'],
            'email' => $report['email']
        ],
        'viewer' => [
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'username' => $user['username']
        ]
    ],
    'metadata' => [
        'accessed_at' => date('c'),
        'api_version' => 'v1'
    ]
]);
?> 