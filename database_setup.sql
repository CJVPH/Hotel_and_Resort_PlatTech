-- ============================================
-- PARADISE HOTEL & RESORT DATABASE SETUP
-- Paste entire file into phpMyAdmin SQL tab
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS hotel_reservation
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_reservation;


-- ============================================
-- USERS
-- ============================================
CREATE TABLE users (
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


-- ============================================
-- OTP CODES
-- ============================================
CREATE TABLE otp_codes (
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

-- ============================================
-- RESERVATIONS
-- ============================================
CREATE TABLE reservations (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NULL,
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
    INDEX idx_user_id (user_id),
    INDEX idx_checkin_date (checkin_date),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ROOM PRICES
-- ============================================
CREATE TABLE room_prices (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    room_type VARCHAR(50) NOT NULL,
    pax_group INT(11) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_room_pax (room_type, pax_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WEBSITE PHOTOS
-- ============================================
CREATE TABLE website_photos (
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

-- ============================================
-- ROOM IMAGES
-- ============================================
CREATE TABLE room_images (
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
    INDEX idx_room_lookup (room_number, room_type, pax_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================
-- SPA SERVICES
-- ============================================
CREATE TABLE spa_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL,
    image VARCHAR(255) NULL,
    enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- RESTAURANT MENU
-- ============================================
CREATE TABLE restaurant_menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    prep_time INT NOT NULL,
    image VARCHAR(255) NULL,
    available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_available (available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- PAVILION MENU
-- ============================================
CREATE TABLE pavilion_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    prep_time INT NOT NULL,
    image VARCHAR(255) NULL,
    available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_available (available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- WATER ACTIVITIES
-- ============================================
CREATE TABLE water_activities_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL,
    image VARCHAR(255) NULL,
    available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_available (available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- BAR MENU
-- ============================================
CREATE TABLE bar_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    bar_type ENUM('mini','main') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) NULL,
    available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bar_type (bar_type),
    INDEX idx_available (available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- HOMEPAGE SETTINGS
-- ============================================
CREATE TABLE homepage_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type VARCHAR(50) DEFAULT 'text',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SITE SETTINGS (general key-value store)
-- ============================================
CREATE TABLE site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================
-- PAVILION SLOTS
-- ============================================
CREATE TABLE pavilion_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_date DATE NOT NULL UNIQUE,
    max_pax INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    note VARCHAR(255) DEFAULT '',
    status ENUM('available','booked','blocked') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_event_date (event_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- PAVILION BOOKINGS
-- ============================================
CREATE TABLE pavilion_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_id INT NULL DEFAULT NULL,
    event_date DATE NULL,
    checkout_date DATE NULL,
    user_id INT(11) NULL,
    guest_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(30),
    pax INT NOT NULL DEFAULT 1,
    event_type VARCHAR(100) DEFAULT '',
    event_time VARCHAR(20) DEFAULT '',
    event_end_time VARCHAR(20) DEFAULT '',
    special_requests TEXT DEFAULT NULL,
    buffet_items TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    payment_status VARCHAR(20) DEFAULT 'pending',
    payment_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_percentage INT DEFAULT 0,
    payment_method VARCHAR(50),
    payment_reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (slot_id) REFERENCES pavilion_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_slot_id (slot_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- PAVILION EVENT PRICES
-- ============================================
CREATE TABLE pavilion_event_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL UNIQUE,
    base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================
-- DEFAULT DATA
-- ============================================

-- Admin accounts
-- admin  / password: password
-- admin2 / password: password
INSERT INTO users (username, email, password, full_name, is_admin) VALUES
('admin',  'admin@paradisehotel.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator',      1),
('admin2', 'admin2@paradisehotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 1);

-- Room prices
INSERT INTO room_prices (room_type, pax_group, price) VALUES
('Regular', 2,  1500.00),
('Regular', 8,  3000.00),
('Regular', 20, 6000.00),
('Deluxe',  2,  2500.00),
('Deluxe',  8,  4500.00),
('Deluxe',  20, 8500.00),
('VIP',     2,  4000.00),
('VIP',     8,  7000.00),
('VIP',     20, 12000.00);

-- Homepage settings
INSERT INTO homepage_settings (setting_key, setting_value, setting_type) VALUES
('site_title',        'Paradise Hotel & Resort',                                                                    'text'),
('site_tagline',      'Experience luxury, comfort, and unforgettable memories',                                     'text'),
('hero_title',        'Welcome to Paradise Hotel & Resort',                                                         'text'),
('hero_subtitle',     'Experience luxury, comfort, and unforgettable memories',                                     'text'),
('about_title',       'About Paradise Hotel & Resort',                                                              'text'),
('about_description', 'Welcome to Paradise Hotel & Resort, where luxury meets comfort.',                            'textarea'),
('contact_phone',     '+1 (555) 123-4567',                                                                         'text'),
('contact_email',     'info@paradisehotel.com',                                                                    'text'),
('contact_address',   '123 Paradise Lane, Resort City',                                                            'text'),
('google_maps_embed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3861.4447!2d121.0244!3d14.5995',     'text'),
('feature_1_icon',    'fas fa-star',                                                                               'text'),
('feature_1_text',    '5 Star Luxury',                                                                             'text'),
('feature_2_icon',    'fas fa-wifi',                                                                               'text'),
('feature_2_text',    'Free WIFI',                                                                                 'text'),
('feature_3_icon',    'fas fa-parking',                                                                            'text'),
('feature_3_text',    'Free Parking',                                                                              'text');

-- Site settings
INSERT INTO site_settings (setting_key, setting_value) VALUES
('pavilion_price', '5000');

-- Pavilion event prices
INSERT INTO pavilion_event_prices (event_type, base_price) VALUES
('Wedding',         50000.00),
('Corporate Event', 40000.00),
('Anniversary',     25000.00),
('Family Reunion',  25000.00),
('Birthday Party',  15000.00),
('Graduation',      15000.00),
('Other',           15000.00);

-- ============================================
-- GUEST REVIEWS
-- ============================================
CREATE TABLE guest_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_name VARCHAR(100) NOT NULL,
    guest_type VARCHAR(100) NOT NULL,
    review_text TEXT NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO guest_reviews (guest_name, guest_type, review_text, rating, sort_order) VALUES
('John & Sarah Davis', 'Honeymoon Suite Guests', 'An absolutely incredible experience! The staff was amazing, the rooms were luxurious, and the amenities exceeded all expectations. We\'ll definitely be back!', 5, 1),
('Maria Johnson',      'VIP Suite Guest',        'Paradise Hotel truly lives up to its name. From the moment we arrived, we were treated like royalty. The spa treatments were divine and the food was exceptional.', 5, 2),
('Robert Wilson',      'Family Suite Guest',     'Perfect for our family vacation! The kids loved the pool, we enjoyed the spa, and everyone raved about the restaurant. Outstanding service throughout our stay.', 5, 3);
