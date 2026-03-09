<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user ID
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current username
function getUsername() {
    return $_SESSION['username'] ?? null;
}

// Get current user's full name
function getFullName() {
    return $_SESSION['full_name'] ?? null;
}

// Get current user's first name
function getFirstName() {
    $fullName = $_SESSION['full_name'] ?? null;
    if ($fullName) {
        $nameParts = explode(' ', trim($fullName));
        return $nameParts[0];
    }
    return null;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        // Store the current page to redirect back after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}

// Logout function
function logout() {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    header('Location: index.php');
    exit();
}

// Admin authentication functions
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}

function getAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

function getAdminUsername() {
    return $_SESSION['admin_username'] ?? null;
}

function getAdminFullName() {
    return $_SESSION['admin_full_name'] ?? null;
}

// Login user function
function loginUser($user) {
    // Regenerate session ID for security but keep session data
    session_regenerate_id(false);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    
    return true;
}

// Login admin function
function loginAdmin($admin) {
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_full_name'] = $admin['full_name'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_logged_in'] = true;
    
    return true;
}
?>