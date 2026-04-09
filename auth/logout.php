<?php
require_once '../config.php';

// Clear session
session_unset();
session_destroy();

// Clear remember me cookie if exists
if(isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page
header('Location: signin.php');
exit();
?>