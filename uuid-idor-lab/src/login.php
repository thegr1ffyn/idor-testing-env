<?php
require_once 'auth.php';
global $config;

$error = '';

if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($username, $password)) {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Corporate Reports</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Corporate Reports</h1>
            <p>Secure Business Intelligence Platform</p>
        </div>
        
        <div class="card login-form">
            <h2>Sign In to Your Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Sign In</button>
            </form>
            
            <div style="margin-top: 20px; text-align: center;">
                <small style="color: #666;">For demo purposes:</small><br>
                <small style="color: #888;">user_a / password123 or user_b / password123</small>
            </div>
        </div>
    </div>
</body>
</html>