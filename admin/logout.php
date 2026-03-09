<?php
require_once '../config/auth.php';

// Clear admin session
if (isset($_SESSION['admin_logged_in'])) {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_full_name']);
    unset($_SESSION['admin_email']);
}

// Destroy session if no other user is logged in
if (!isLoggedIn()) {
    session_destroy();
}

// Redirect to admin login
header('Location: login.php');
exit();
?>