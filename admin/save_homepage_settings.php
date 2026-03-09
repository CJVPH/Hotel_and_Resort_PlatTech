<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin login
requireAdminLogin();

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    
    // List of all settings fields
    $settings = [
        'site_title',
        'site_tagline',
        'hero_title',
        'hero_subtitle',
        'about_title',
        'about_description',
        'contact_phone',
        'contact_email',
        'contact_address',
        'feature_1_icon',
        'feature_1_text',
        'feature_2_icon',
        'feature_2_text',
        'feature_3_icon',
        'feature_3_text'
    ];
    
    // Prepare statement for updating settings
    $stmt = $conn->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    
    foreach ($settings as $key) {
        if (isset($_POST[$key])) {
            $value = trim($_POST[$key]);
            $stmt->bind_param("ss", $key, $value);
            $stmt->execute();
        }
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Homepage settings saved successfully!'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error saving settings: ' . $e->getMessage()
    ]);
}
?>
