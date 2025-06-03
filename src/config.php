<?php
$config = [
    'database' => [
        'host' => 'database',
        'user' => 'webuser',
        'pass' => 'WebPass123',
        'name' => 'docmanager',
    ],
    'app' => [
        'name' => 'DocManager Pro',
        'version' => '2.1.0',
        'upload_path' => '/var/www/html/uploads/',
        'max_file_size' => 10485760, // 10MB
    ],
    'security' => [
        'session_lifetime' => 3600, // 1 hour
        'password_hash' => false, // Intentionally weak for testing
    ]
]; 