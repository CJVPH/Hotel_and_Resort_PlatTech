<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/otp.php';

$error = '';
$success = '';
$email = $_GET['email'] ?? $_SESSION['otp_email'] ?? '';
$purpose = $_GET['purpose'] ?? 'verification';

if (empty($email)) {
    header('Location: login.php?error=Invalid verification request');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    
    if (empty($otp)) {
        $error = 'Please enter the OTP code';
    } elseif (strlen($otp) !== 6 || !ctype_digit($otp)) {
        $error = 'OTP must be 6 digits';
    } else {
        if (verifyOTP($email, $otp, $purpose)) {
            // OTP verified successfully
            if ($purpose === 'registration') {
                // Mark user as verified and log them in
                $conn = getDBConnection();
                $stmt = $conn->prepare("UPDATE users SET email_verified = 1 WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                
                // Get user data and log in
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                loginUser($user);
                
                $stmt->close();
                $conn->close();
                
                unset($_SESSION['otp_email']);
                header('Location: index.php?message=Registration successful!');
                exit();
            } elseif ($purpose === 'login') {
                // Log in the user
                $conn = getDBConnection();
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                loginUser($user);
                
                $stmt->close();
                $conn->close();
                
                unset($_SESSION['otp_email']);
                
                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                
                header('Location: ' . $redirect);
                exit();
            }
        } else {
            $error = 'Invalid or expired OTP code';
        }
    }
}

// Handle resend OTP
if (isset($_GET['resend'])) {
    $otp = generateOTP();
    if (storeOTP($email, $otp, $purpose)) {
        if (sendOTPEmail($email, $otp)) {
            $success = 'New OTP code sent to your email';
        } else {
            $error = 'Failed to send OTP. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Paradise Hotel & Resort</title>
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
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                repeating-linear-gradient(45deg, transparent, transparent 2px, rgba(255,255,255,.03) 2px, rgba(255,255,255,.03) 4px),
                repeating-linear-gradient(-45deg, transparent, transparent 2px, rgba(0,0,0,.03) 2px, rgba(0,0,0,.03) 4px);
            z-index: 0;
        }

        .otp-container {
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 1;
        }

        .otp-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 3rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
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

        .otp-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .otp-icon {
            font-size: 4rem;
            color: #C9A961;
            margin-bottom: 1rem;
        }

        .otp-header h1 {
            color: #2C3E50;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .otp-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .email-display {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 2rem;
            color: #C9A961;
            font-weight: 600;
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

        .alert-success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }

        .otp-input-group {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .otp-input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .otp-input:focus {
            outline: none;
            border-color: #C9A961;
            box-shadow: 0 0 0 3px rgba(201, 169, 97, 0.1);
        }

        .btn-verify {
            width: 100%;
            background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(201, 169, 97, 0.3);
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(201, 169, 97, 0.4);
        }

        .resend-section {
            text-align: center;
            margin-top: 2rem;
            color: #666;
        }

        .resend-link {
            color: #C9A961;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .resend-link:hover {
            color: #8B7355;
        }

        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: #666;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #C9A961;
        }
    </style>
</head>
<body>
    <div class="otp-container">
        <div class="otp-card">
            <div class="otp-header">
                <div class="otp-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1>Verify Your Email</h1>
                <p>Enter the 6-digit code sent to:</p>
            </div>

            <div class="email-display">
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($email); ?>
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

            <form method="POST" action="" id="otpForm">
                <div class="otp-input-group">
                    <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                </div>
                <input type="hidden" name="otp" id="otpValue">
                <button type="submit" class="btn-verify">
                    <i class="fas fa-check"></i> Verify Code
                </button>
            </form>

            <div class="resend-section">
                <p>Didn't receive the code?</p>
                <a href="?email=<?php echo urlencode($email); ?>&purpose=<?php echo urlencode($purpose); ?>&resend=1" class="resend-link">
                    <i class="fas fa-redo"></i> Resend Code
                </a>
                <br>
                <a href="login.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        const inputs = document.querySelectorAll('.otp-input');
        const form = document.getElementById('otpForm');
        const otpValue = document.getElementById('otpValue');

        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const value = e.target.value;
                
                if (value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                
                updateOTPValue();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').slice(0, 6);
                
                pastedData.split('').forEach((char, i) => {
                    if (inputs[i] && /[0-9]/.test(char)) {
                        inputs[i].value = char;
                    }
                });
                
                updateOTPValue();
                inputs[Math.min(pastedData.length, 5)].focus();
            });
        });

        function updateOTPValue() {
            const otp = Array.from(inputs).map(input => input.value).join('');
            otpValue.value = otp;
        }

        form.addEventListener('submit', (e) => {
            updateOTPValue();
            if (otpValue.value.length !== 6) {
                e.preventDefault();
                alert('Please enter all 6 digits');
            }
        });

        // Auto-focus first input
        inputs[0].focus();
    </script>
</body>
</html>
