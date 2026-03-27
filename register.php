<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/google_oauth.php';

// Initialize Google OAuth
$google = new GoogleOAuth();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'All required fields must be filled!';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long!';
    } elseif (strlen($full_name) < 2) {
        $error = 'Full name must be at least 2 characters long!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long!';
    } else {
        $conn = getDBConnection();
        
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username or email already exists!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $email, $hashed_password, $full_name, $phone, $address);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now login.';
                // Redirect to login page with success message
                header('Location: login.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
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
    <title>Register - Paradise Hotel & Resort</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="fas fa-user-plus"></i> Register</h1>
                <p>Create your account to book rooms</p>
            </div>


                        <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
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
                    Sign up with Google
                </a>
            </div>

            <div class="divider">
                <span>or sign up with email</span>
            </div>

            <form method="POST" action="" class="auth-form" id="registerForm">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="full_name" placeholder="Enter your full name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                    <div class="error-message">Please enter your full name</div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-circle"></i> Username</label>
                    <input type="text" name="username" placeholder="Choose a username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    <div class="error-message">Username must be at least 3 characters</div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <div class="error-message">Please enter a valid email address</div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" name="phone" placeholder="Enter your phone number (optional)" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Address</label>
                    <input type="text" name="address" placeholder="Enter your address (optional)" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" placeholder="Create a password (min. 6 characters)" required>
                    <div class="password-strength" id="passwordStrength"></div>
                    <div class="error-message">Password must be at least 6 characters</div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm your password" required>
                    <div class="error-message">Passwords do not match</div>
                </div>

                <button type="submit" class="btn-primary" id="submitBtn">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <p><a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Form validation
        const form = document.getElementById('registerForm');
        const submitBtn = document.getElementById('submitBtn');
        
        // Password strength checker
        const passwordInput = document.querySelector('input[name="password"]');
        const strengthIndicator = document.getElementById('passwordStrength');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let message = '';
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            if (password.length === 0) {
                message = '';
            } else if (strength < 2) {
                message = '<span class="strength-weak">Weak password</span>';
            } else if (strength < 4) {
                message = '<span class="strength-medium">Medium password</span>';
            } else {
                message = '<span class="strength-strong">Strong password</span>';
            }
            
            strengthIndicator.innerHTML = message;
        });
        
        // Real-time validation
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(msg => {
                msg.style.display = 'none';
            });
            document.querySelectorAll('input').forEach(input => {
                input.classList.remove('error');
            });
            
            // Validate full name
            const fullName = document.querySelector('input[name="full_name"]');
            if (fullName.value.trim().length < 2) {
                showError(fullName, 'Please enter your full name');
                isValid = false;
            }
            
            // Validate username
            const username = document.querySelector('input[name="username"]');
            if (username.value.trim().length < 3) {
                showError(username, 'Username must be at least 3 characters');
                isValid = false;
            }
            
            // Validate email
            const email = document.querySelector('input[name="email"]');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                showError(email, 'Please enter a valid email address');
                isValid = false;
            }
            
            // Validate password
            const password = document.querySelector('input[name="password"]');
            if (password.value.length < 6) {
                showError(password, 'Password must be at least 6 characters');
                isValid = false;
            }
            
            // Validate password confirmation
            const confirmPassword = document.querySelector('input[name="confirm_password"]');
            if (password.value !== confirmPassword.value) {
                showError(confirmPassword, 'Passwords do not match');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        function showError(input, message) {
            input.classList.add('error');
            const errorMsg = input.parentNode.querySelector('.error-message');
            errorMsg.textContent = message;
            errorMsg.style.display = 'block';
        }
    </script>
</body>
</html>