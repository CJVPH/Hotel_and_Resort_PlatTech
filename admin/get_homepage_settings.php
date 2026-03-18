<?php
require_once '../config/database.php';
require_once '../config/auth.php';

requireAdminLogin();
header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    // If requesting photos for a section
    if (!empty($_GET['photos_section'])) {
        $section = $conn->real_escape_string($_GET['photos_section']);
        $result = $conn->query("SELECT id, file_path, original_name FROM website_photos WHERE section='$section' AND is_active=1 ORDER BY sort_order ASC, upload_date DESC");
        $photos = [];
        if ($result) while ($r = $result->fetch_assoc()) $photos[] = $r;
        echo json_encode(['success' => true, 'photos' => $photos]);
        $conn->close(); exit;
    }

    $result = $conn->query("SELECT setting_key, setting_value FROM homepage_settings");
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    echo json_encode(['success' => true, 'settings' => $settings]);
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
