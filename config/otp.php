<?php
/**
 * OTP (One-Time Password) System
 * Handles OTP generation, sending, and verification
 */

require_once __DIR__ . '/database.php';

/**
 * Generate a 6-digit OTP
 */
function generateOTP() {
    return sprintf("%06d", mt_rand(0, 999999));
}

/**
 * Store OTP in database
 */
function storeOTP($email, $otp, $purpose = 'verification') {
    try {
        $conn = getDBConnection();
        
        // Delete any existing OTPs for this email and purpose
        $stmt = $conn->prepare("DELETE FROM otp_codes WHERE email = ? AND purpose = ?");
        $stmt->bind_param("ss", $email, $purpose);
        $stmt->execute();
        $stmt->close();
        
        // Store new OTP (expires in 10 minutes)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $stmt = $conn->prepare("INSERT INTO otp_codes (email, otp_code, purpose, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $email, $otp, $purpose, $expiresAt);
        $result = $stmt->execute();
        
        $stmt->close();
        $conn->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Store OTP error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify OTP
 */
function verifyOTP($email, $otp, $purpose = 'verification') {
    try {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("SELECT * FROM otp_codes WHERE email = ? AND otp_code = ? AND purpose = ? AND expires_at > NOW() AND is_used = 0");
        $stmt->bind_param("sss", $email, $otp, $purpose);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            // Mark OTP as used
            $stmt->close();
            $stmt = $conn->prepare("UPDATE otp_codes SET is_used = 1 WHERE email = ? AND otp_code = ? AND purpose = ?");
            $stmt->bind_param("sss", $email, $otp, $purpose);
            $stmt->execute();
            $stmt->close();
            $conn->close();
            
            return true;
        }
        
        $stmt->close();
        $conn->close();
        return false;
    } catch (Exception $e) {
        error_log("Verify OTP error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send OTP via email
 */
function sendOTPEmail($email, $otp, $fullName = '') {
    $subject = "Your OTP Code - Paradise Hotel & Resort";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .otp-box { background: white; border: 2px solid #C9A961; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
            .otp-code { font-size: 32px; font-weight: bold; color: #C9A961; letter-spacing: 5px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏨 Paradise Hotel & Resort</h1>
            </div>
            <div class='content'>
                <h2>Hello" . ($fullName ? " " . htmlspecialchars($fullName) : "") . "!</h2>
                <p>Your One-Time Password (OTP) for verification is:</p>
                <div class='otp-box'>
                    <div class='otp-code'>" . $otp . "</div>
                </div>
                <p><strong>This code will expire in 10 minutes.</strong></p>
                <p>If you didn't request this code, please ignore this email.</p>
                <div class='footer'>
                    <p>Paradise Hotel & Resort<br>
                    Calayo, Nasugbu, Batangas, Philippines</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Paradise Hotel & Resort <noreply@paradisehotel.com>" . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}

/**
 * Clean up expired OTPs (should be run periodically)
 */
function cleanupExpiredOTPs() {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("DELETE FROM otp_codes WHERE expires_at < NOW()");
        $stmt->execute();
        $stmt->close();
        $conn->close();
        return true;
    } catch (Exception $e) {
        error_log("Cleanup OTP error: " . $e->getMessage());
        return false;
    }
}
?>
