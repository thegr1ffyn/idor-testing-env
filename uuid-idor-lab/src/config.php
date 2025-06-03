<?php
// Database configuration
$config = [
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'name' => $_ENV['DB_NAME'] ?? 'reports_db',
        'user' => $_ENV['DB_USER'] ?? 'reports_user',
        'pass' => $_ENV['DB_PASS'] ?? 'reports_pass123'
    ]
]; 