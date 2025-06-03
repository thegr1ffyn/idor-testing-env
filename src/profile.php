<?php
require_once 'auth.php';
$user = requireLogin();
 
// Redirect to the current user's profile
header('Location: view_profile.php?id=' . $user['id']);
exit(); 