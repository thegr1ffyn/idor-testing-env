<?php
require_once 'auth.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit(); 