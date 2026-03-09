<?php
require_once 'config/database.php';
require_once 'config/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please enter both username and password']);
    exit();
}

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, username, password, full_name, email FROM users WHERE (username = ? OR email = ?) AND is_admin = 0");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Login successful
            loginUser($user);
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => 'booking.php'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("AJAX login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Login failed. Please try again.']);
}
?>