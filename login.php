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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #7e8ba3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Textured Background Layer */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                repeating-linear-gradient(45deg, transparent, transparent 2px, rgba(255,255,255,.03) 2px, rgba(255,255,255,.03) 4px),
                repeating-linear-gradient(-45deg, transparent, transparent 2px, rgba(0,0,0,.03) 2px, rgba(0,0,0,.03) 4px),
                linear-gradient(90deg, rgba(201, 169, 97, 0.05) 1px, transparent 1px),
                linear-gradient(rgba(201, 169, 97, 0.05) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 50px 50px, 50px 50px;
            z-index: 0;
        }

        /* Animated Pattern Overlay */
        body::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(201, 169, 97, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(201, 169, 97, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 60% 60%, rgba(139, 115, 85, 0.1) 0%, transparent 40%);
            animation: rotate 40s linear infinite;
            z-index: 0;
            opacity: 0.8;
        }

        @keyframes rotate {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        /* Noise Texture */
        .auth-container::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.05'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 0;
        }
        
        .auth-container {
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 1;
        }
        
        .auth-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 3rem;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.3) inset;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-header h1 {
            color: #2C3E50;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .auth-header h1 i {
            color: #C9A961;
        }
        
        .auth-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-info {
            background: #e7f3ff;
            color: #0066cc;
            border: 1px solid #b3d9ff;
        }
        
        .alert-success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            color: #2C3E50;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            text-align: left;
        }
        
        .form-group label i {
            color: #C9A961;
            margin-right: 0.5rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #C9A961;
            box-shadow: 0 0 0 3px rgba(201, 169, 97, 0.1);
        }
        
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(201, 169, 97, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(201, 169, 97, 0.4);
        }
        
        .btn-google {
            width: 100%;
            background: #fff;
            color: #3c4043;
            border: 1px solid #dadce0;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
            font-family: 'Roboto', 'Arial', sans-serif;
            box-shadow: 0 1px 2px 0 rgba(60, 64, 67, 0.30), 0 1px 3px 1px rgba(60, 64, 67, 0.15);
        }
        
        .btn-google:hover {
            background: #f8f9fa;
            border-color: #c6c6c6;
            box-shadow: 0 1px 3px 0 rgba(60, 64, 67, 0.30), 0 4px 8px 3px rgba(60, 64, 67, 0.15);
            color: #3c4043;
            text-decoration: none;
        }
        
        .btn-google:active {
            background: #f1f3f4;
            box-shadow: 0 1px 2px 0 rgba(60, 64, 67, 0.30), 0 2px 6px 2px rgba(60, 64, 67, 0.15);
        }
        
        .google-icon {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }
        
        .divider {
            text-align: center;
            margin: 24px 0;
            position: relative;
            color: #5f6368;
            font-size: 14px;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #dadce0;
        }
        
        .divider span {
            background: rgba(255, 255, 255, 0.98);
            padding: 0 16px;
            position: relative;
            font-size: 14px;
            color: #5f6368;
        }
        
        .oauth-section {
            margin-bottom: 0;
        }
        
        .auth-footer {
            text-align: center;
            color: #666;
        }
        
        .auth-footer p {
            margin: 0.5rem 0;
        }
        
        .auth-footer a {
            color: #C9A961;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .auth-footer a:hover {
            color: #8B7355;
        }
        
        @media (max-width: 480px) {
            .auth-card {
                padding: 2rem 1.5rem;
            }
            
            .auth-header h1 {
                font-size: 2rem;
            }
        }
    </style>
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