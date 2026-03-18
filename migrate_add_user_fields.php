<?php
/**
 * Migration Script: Add phone and address fields to users table
 * Run this file once to update existing databases
 */

require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    echo "Starting migration...\n";
    
    // Check if phone column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
    if ($result->num_rows == 0) {
        echo "Adding 'phone' column...\n";
        $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER full_name");
        echo "✓ 'phone' column added successfully\n";
    } else {
        echo "✓ 'phone' column already exists\n";
    }
    
    // Check if address column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'address'");
    if ($result->num_rows == 0) {
        echo "Adding 'address' column...\n";
        $conn->query("ALTER TABLE users ADD COLUMN address TEXT AFTER phone");
        echo "✓ 'address' column added successfully\n";
    } else {
        echo "✓ 'address' column already exists\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
