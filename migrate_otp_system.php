<?php
/**
 * Migration script to add OTP system tables and columns
 * Run this once to update your existing database
 */

require_once 'config/database.php';

echo "<h2>OTP System Migration</h2>";
echo "<p>Adding OTP system to database...</p>";

try {
    $conn = getDBConnection();
    
    // Add email_verified column to users table
    echo "<p>Adding email_verified column to users table...</p>";
    $sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0 AFTER is_admin";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ email_verified column added successfully</p>";
    } else {
        echo "<p style='color: orange;'>⚠ email_verified column may already exist</p>";
    }
    
    // Create OTP codes table
    echo "<p>Creating otp_codes table...</p>";
    $sql = "CREATE TABLE IF NOT EXISTS otp_codes (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL,
        otp_code VARCHAR(6) NOT NULL,
        purpose VARCHAR(50) NOT NULL DEFAULT 'verification',
        expires_at DATETIME NOT NULL,
        is_used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ otp_codes table created successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating otp_codes table: " . $conn->error . "</p>";
    }
    
    // Set existing users as verified (optional - comment out if you want to require verification for existing users)
    echo "<p>Setting existing users as verified...</p>";
    $sql = "UPDATE users SET email_verified = 1 WHERE email_verified = 0";
    if ($conn->query($sql)) {
        $affected = $conn->affected_rows;
        echo "<p style='color: green;'>✓ Updated $affected existing users as verified</p>";
    }
    
    $conn->close();
    
    echo "<h3 style='color: green;'>Migration completed successfully!</h3>";
    echo "<p><a href='index.php'>Go to Home</a> | <a href='register.php'>Test Registration</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Migration error: " . $e->getMessage() . "</p>";
}
?>
