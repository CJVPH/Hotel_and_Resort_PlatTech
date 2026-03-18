<?php
// Simple admin authentication guard
// Include this at top of admin pages to require an admin session

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If admin session is not set, redirect to admin login
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Optionally you can further verify user's is_admin flag from DB here
?>
