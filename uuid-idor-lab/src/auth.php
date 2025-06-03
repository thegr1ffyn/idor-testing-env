<?php
// Session configuration - 1 hour timeout
ini_set('session.gc_maxlifetime', 3600); // 1 hour in seconds
ini_set('session.cookie_lifetime', 3600); // 1 hour in seconds
session_start();

// Include global config
require_once 'config.php';

function requireLogin() {
    global $config;
    
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    $_SESSION['last_activity'] = time();
    
    $db = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['name']);
    
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    return $user;
}

function login($username, $password) {
    global $config;
    
    $db = new mysqli($config['database']['host'], $config['database']['user'], $config['database']['pass'], $config['database']['name']);
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    return false;
} 