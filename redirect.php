<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/google_oauth.php';

try {
    $google = new GoogleOAuth();
    
    if (!isset($_GET['code'])) {
        throw new Exception('Authorization code not received');
    }
    
    // Get access token
    $tokenData = $google->getAccessToken($_GET['code']);
    
    if (!isset($tokenData['access_token'])) {
        throw new Exception('Failed to get access token');
    }
    
    // Get user info from Google
    $userInfo = $google->getUserInfo($tokenData['access_token']);
    
    if (!isset($userInfo['email'])) {
        throw new Exception('Failed to get user email from Google');
    }
    
    // Check if user exists in database
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT id, username, full_name, email FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception('Database query error: ' . $conn->error);
    }
    $stmt->bind_param("s", $userInfo['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User exists, log them in
        $user = $result->fetch_assoc();
        
        // Update user info
        $updateStmt = $conn->prepare("UPDATE users SET full_name = ?, google_id = ? WHERE id = ?");
        if (!$updateStmt) {
            throw new Exception('Database query error: ' . $conn->error);
        }
        $updateStmt->bind_param("ssi", $userInfo['name'], $userInfo['id'], $user['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        loginUser($user);
        
        // Debug: Log successful login
        error_log('Google OAuth: User logged in successfully. User ID: ' . $user['id'] . ', Session ID: ' . session_id());
        
    } else {
        // Create new user
        $username = strtolower(str_replace(' ', '', $userInfo['name'])) . '_' . substr($userInfo['id'], -4);
        $fullName = $userInfo['name'];
        $email = $userInfo['email'];
        $googleId = $userInfo['id'];
        
        // Generate a random password
        $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        
        $insertStmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, google_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        if (!$insertStmt) {
            throw new Exception('Database query error: ' . $conn->error);
        }
        $insertStmt->bind_param("sssss", $username, $email, $randomPassword, $fullName, $googleId);
        
        if ($insertStmt->execute()) {
            $userId = $conn->insert_id;
            
            // Get the new user data
            $newUserStmt = $conn->prepare("SELECT id, username, full_name, email FROM users WHERE id = ?");
            if (!$newUserStmt) {
                throw new Exception('Database query error: ' . $conn->error);
            }
            $newUserStmt->bind_param("i", $userId);
            $newUserStmt->execute();
            $newUserResult = $newUserStmt->get_result();
            $newUser = $newUserResult->fetch_assoc();
            $newUserStmt->close();
            
            loginUser($newUser);
        } else {
            throw new Exception('Failed to create user account');
        }
        
        $insertStmt->close();
    }
    
    $stmt->close();
    $conn->close();
    
    // Determine redirect URL
    $redirect = 'index.php'; // Default to main page
    
    // Check if there's a stored redirect URL
    if (isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
        $storedRedirect = $_SESSION['redirect_after_login'];
        // Only use stored redirect if it's not an admin page
        if (strpos($storedRedirect, 'admin/') === false) {
            $redirect = $storedRedirect;
        }
    }
    
    // Clear the redirect session variable
    unset($_SESSION['redirect_after_login']);
    
    // Ensure session is written before redirect
    session_write_close();
    
    header('Location: ' . $redirect);
    exit();
    
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    error_log('Google OAuth Error: ' . $errorMsg);
    
    // Redirect to login with error message (limit length for URL)
    $displayError = strlen($errorMsg) > 100 ? substr($errorMsg, 0, 100) . '...' : $errorMsg;
    $errorMessage = urlencode('Login failed: ' . $displayError);
    header('Location: login.php?error=' . $errorMessage);
    exit();
}
?>