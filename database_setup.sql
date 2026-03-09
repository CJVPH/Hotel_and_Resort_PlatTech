-- ============================================
-- PARADISE HOTEL & RESORT DATABASE SETUP
-- Complete Database Schema with All Tables and Data
-- ============================================

CREATE DATABASE IF NOT EXISTS hotel_reservation;
USE hotel_reservation;

-- ============================================
-- MAIN TABLES
-- ============================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    is_admin TINYINT(1) DEFAULT 0,
    email_verified TINYINT(1) DEFAULT 0,
    google_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add columns if they don't exist (for existing databases)
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) AFTER full_name;
ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT AFTER phone;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0 AFTER is_admin;

-- OTP codes table for email verification
CREATE TABLE IF NOT EXISTS otp_codes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    purpose VARCHAR(50) NOT NULL DEFAULT 'verification',
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reservations table with payment support and guest booking capability
CREATE TABLE IF NOT EXISTS reservations (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NULL,  -- Allow NULL for guest bookings
    guest_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    checkin_date DATE NOT NULL,
    checkout_date DATE NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    guests INT(11) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    options TEXT,
    payment_status VARCHAR(20) DEFAULT 'pending',
    payment_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_percentage INT DEFAULT 0,
    payment_method VARCHAR(50),
    payment_reference VARCHAR(100),
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Room prices table for admin-controlled pricing
CREATE TABLE IF NOT EXISTS room_prices (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    room_type VARCHAR(50) NOT NULL,
    pax_group INT(11) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_room_pax (room_type, pax_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Website photos table for admin photo management
CREATE TABLE IF NOT EXISTS website_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(50) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    INDEX idx_section (section),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Individual room images table (SUPPORTS MULTIPLE IMAGES PER ROOM)
CREATE TABLE IF NOT EXISTS room_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    pax_group INT(11) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    INDEX idx_room_number (room_number),
    INDEX idx_room_type (room_type),
    INDEX idx_pax_group (pax_group),
    INDEX idx_active (is_active),
    INDEX idx_sort_order (sort_order),
    INDEX idx_room_lookup (room_number, room_type, pax_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Spa Services table for spa management
CREATE TABLE IF NOT EXISTS spa_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL, -- in minutes
    image VARCHAR(255) NULL,
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_enabled (enabled),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Restaurant Menu Items table
CREATE TABLE IF NOT EXISTS restaurant_menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    prep_time INT NOT NULL, -- in minutes
    image VARCHAR(255) NULL,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_available (available),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pavilion Menu table
CREATE TABLE IF NOT EXISTS pavilion_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    prep_time INT NOT NULL, -- in minutes
    image VARCHAR(255) NULL,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_available (available),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Water Activities Menu table
CREATE TABLE IF NOT EXISTS water_activities_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL, -- in minutes
    image VARCHAR(255) NULL,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_available (available),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bar Menu table (for both mini bar and main bar)
CREATE TABLE IF NOT EXISTS bar_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    bar_type ENUM('mini', 'main') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) NULL,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bar_type (bar_type),
    INDEX idx_available (available),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Homepage Settings table for editable content
CREATE TABLE IF NOT EXISTS homepage_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type VARCHAR(50) DEFAULT 'text',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default homepage settings
INSERT INTO homepage_settings (setting_key, setting_value, setting_type) VALUES
('site_title', 'Paradise Hotel & Resort', 'text'),
('site_tagline', 'Experience luxury, comfort, and unforgettable memories', 'text'),
('hero_title', 'Welcome to Paradise Hotel & Resort', 'text'),
('hero_subtitle', 'Experience luxury, comfort, and unforgettable memories', 'text'),
('about_title', 'About Paradise Hotel & Resort', 'text'),
('about_description', 'Welcome to Paradise Hotel & Resort, where luxury meets comfort. Our world-class facilities and exceptional service ensure an unforgettable stay.', 'textarea'),
('contact_phone', '+1 (555) 123-4567', 'text'),
('contact_email', 'info@paradisehotel.com', 'text'),
('contact_address', '123 Paradise Lane, Resort City', 'text'),
('google_maps_embed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3861.4447!2d121.0244!3d14.5995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTTCsDM1JzU4LjIiTiAxMjHCsDAxJzI3LjgiRQ!5e0!3m2!1sen!2sph!4v1234567890', 'text'),
('feature_1_icon', 'fas fa-star', 'text'),
('feature_1_text', '5 Star Luxury', 'text'),
('feature_2_icon', 'fas fa-wifi', 'text'),
('feature_2_text', 'Free WIFI', 'text'),
('feature_3_icon', 'fas fa-parking', 'text'),
('feature_3_text', 'Free Parking', 'text')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

-- ============================================
-- DEFAULT DATA
-- ============================================

-- Insert default room prices
INSERT INTO room_prices (room_type, pax_group, price) VALUES
('Regular', 2, 1500.00),
('Regular', 8, 3000.00),
('Regular', 20, 6000.00),
('Deluxe', 2, 2500.00),
('Deluxe', 8, 4500.00),
('Deluxe', 20, 8500.00),
('VIP', 2, 4000.00),
('VIP', 8, 7000.00),
('VIP', 20, 12000.00)
ON DUPLICATE KEY UPDATE price=VALUES(price);

-- Insert default admin account
-- Username: admin, Password: admin123, Email: admin@paradisehotel.com
INSERT INTO users (username, email, password, full_name, is_admin) 
VALUES ('admin', 'admin@paradisehotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 1)
ON DUPLICATE KEY UPDATE 
password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
is_admin = 1;

-- admin back up
-- Username: admin2
-- Password: password

INSERT INTO users (username, email, password, full_name, is_admin)
VALUES ('admin2','admin2@paradisehotel.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','System Administrator',1);

-- ============================================
-- PERFORMANCE INDEXES
-- ============================================

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_user_id ON reservations(user_id);
CREATE INDEX IF NOT EXISTS idx_checkin_date ON reservations(checkin_date);
CREATE INDEX IF NOT EXISTS idx_status ON reservations(status);

-- ============================================
-- TROUBLESHOOTING SECTION
-- ============================================

-- Guest Booking Support: Allow NULL user_id for guest reservations
-- This allows users to book without logging in, login is only required for payment
ALTER TABLE reservations MODIFY COLUMN user_id INT(11) NULL;

-- Update foreign key constraint to handle NULL properly (if it exists)
-- Note: This may fail if constraint doesn't exist, which is fine
SET @constraint_name = (
    SELECT CONSTRAINT_NAME 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'hotel_reservation' 
    AND TABLE_NAME = 'reservations' 
    AND REFERENCED_TABLE_NAME = 'users'
    LIMIT 1
);

SET @sql = IF(@constraint_name IS NOT NULL, 
    CONCAT('ALTER TABLE reservations DROP FOREIGN KEY ', @constraint_name), 
    'SELECT "No foreign key to drop" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint with proper NULL handling
ALTER TABLE reservations ADD CONSTRAINT fk_reservations_user_id 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

