<?php
header('Content-Type: application/json');
require_once '../includes/photo_functions.php';

$section = $_GET['section'] ?? '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;

if (empty($section)) {
    echo json_encode([
        'success' => false,
        'error' => 'Section parameter is required'
    ]);
    exit();
}

try {
    $photos = getPhotosWithFallback($section, $limit);
    
    echo json_encode([
        'success' => true,
        'photos' => $photos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch images'
    ]);
}
?>