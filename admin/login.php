<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// If already logged in as admin, redirect to dashboard
if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT id, username, password, full_name, email FROM users WHERE username = ? AND is_admin = 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                
                if (password_verify($password, $admin['password'])) {
                    // Login successful
                    loginAdmin($admin);
                    
                    // Redirect to intended page or dashboard
                    $redirectTo = $_SESSION['redirect_after_login'] ?? 'index.php';
                    unset($_SESSION['redirect_after_login']);
                    
                    header('Location: ' . $redirectTo);
                    exit();
                } else {
                    $error = 'Invalid username or password';
                }
            } else {
                $error = 'Invalid username or password';
            }
            
            $stmt->close();
            $conn->close();
            
        } catch (Exception $e) {
            error_log("Admin login error: " . $e->getMessage());
            $error = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Paradise Hotel & Resort</title>
    <link rel="stylesheet" href="../assets/css/main.css">
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
        background-attachment: fixed;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Montserrat', sans-serif;
        padding: 2rem;
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

    .admin-login-container {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        border-radius: 25px;
        padding: 3.5rem;
        box-shadow: 
            0 25px 50px rgba(0, 0, 0, 0.3),
            0 0 0 1px rgba(255, 255, 255, 0.3) inset;
        border: 1px solid rgba(255, 255, 255, 0.2);
        width: 100%;
        max-width: 480px;
        position: relative;
        overflow: hidden;
        z-index: 1;
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

    .admin-login-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%);
    }

    .login-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }

    .login-header h1 {
        color: #2C3E50;
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
    }

    .login-header h1 i {
        background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .login-header p {
        color: #666;
        font-size: 1.1rem;
        font-weight: 500;
    }

    .form-group {
        margin-bottom: 2rem;
    }

    .form-group label {
        display: block;
        color: #2C3E50;
        font-weight: 600;
        margin-bottom: 0.75rem;
        font-size: 1rem;
    }

    .form-group input {
        width: 100%;
        padding: 1.25rem;
        border: 2px solid rgba(201, 169, 97, 0.2);
        border-radius: 15px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(5px);
        font-family: 'Montserrat', sans-serif;
    }

    .form-group input:focus {
        outline: none;
        border-color: #C9A961;
        box-shadow: 0 0 0 4px rgba(201, 169, 97, 0.1);
        background: white;
    }

    .btn-login {
        width: 100%;
        background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%);
        color: white;
        border: none;
        padding: 1.25rem;
        border-radius: 15px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 8px 20px rgba(201, 169, 97, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-login:hover {
        background: linear-gradient(135deg, #8B7355 0%, #C9A961 100%);
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(201, 169, 97, 0.4);
    }

    .error-message {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        text-align: center;
        font-weight: 600;
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        box-shadow: 0 5px 15px rgba(201, 169, 97, 0.3);
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(201, 169, 97, 0.4);
    }

    .error-message {
        background: #fee;
        color: #c33;
        border: 1px solid #fcc;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .back-link {
        text-align: center;
        margin-top: 2rem;
    }

    .back-link a {
        color: #666;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .back-link a:hover {
        color: #C9A961;
    }

    @media (max-width: 480px) {
        .admin-login-container {
            margin: 1rem;
            padding: 2rem;
        }
    }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="login-header">
            <h1><i class="fas fa-shield-alt"></i> Admin Login</h1>
            <p>Paradise Hotel & Resort Administration</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       placeholder="Enter your admin username">
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your password">
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login to Admin Panel
            </button>
        </form>
    </div>
</body>
</html>