<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/google_oauth.php';

// Initialize Google OAuth
$google = new GoogleOAuth();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Handle redirect parameters
if (isset($_GET['redirect']) && isset($_GET['reservation_id'])) {
    $redirectUrl = $_GET['redirect'] . '?reservation_id=' . intval($_GET['reservation_id']);
    $_SESSION['redirect_after_login'] = $redirectUrl;
} elseif (isset($_GET['return'])) {
    // Handle simple return parameter for booking page
    $returnPage = $_GET['return'];
    if ($returnPage === 'booking') {
        $_SESSION['redirect_after_login'] = 'booking.php';
    } elseif (in_array($returnPage, ['profile', 'dashboard', 'reservations'])) {
        $_SESSION['redirect_after_login'] = $returnPage . '.php';
    }
}

$error = '';
$message = $_GET['message'] ?? '';

// Handle Google OAuth error
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password!';
    } else {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("SELECT id, username, password, full_name FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Use the improved login function
                loginUser($user);
                
                // Redirect to intended page or home
                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                
                header('Location: ' . $redirect);
                exit();
            } else {
                $error = 'Invalid username or password!';
            }
        } else {
            $error = 'Invalid username or password!';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Paradise Hotel & Resort</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="fas fa-hotel"></i> Login</h1>
                <p>Welcome back! Please login to continue</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Registration successful! You can now login.
                </div>
            <?php endif; ?>

            <!-- Google OAuth Section -->
            <div class="oauth-section">
                <a href="<?php echo $google->getAuthUrl(); ?>" class="btn-google">
                    <svg class="google-icon" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Continue with Google
                </a>
            </div>

            <div class="divider">
                <span>or continue with email</span>
            </div>

            <form method="POST" action="" class="auth-form" id="loginForm">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username or Email</label>
                    <input type="text" name="username" placeholder="Enter your username or email" required autofocus value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.querySelector('input[name="username"]').value.trim();
            const password = document.querySelector('input[name="password"]').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }
        });
    </script>
</body>
</html>