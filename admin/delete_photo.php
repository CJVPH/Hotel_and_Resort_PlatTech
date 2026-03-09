<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin login
requireAdminLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$photoId = intval($input['photo_id'] ?? 0);
$section = $input['section'] ?? '';

if ($photoId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid photo ID']);
    exit();
}

$validSections = ['carousel', 'pool', 'spa', 'restaurant'];
if (!in_array($section, $validSections)) {
    echo json_encode(['success' => false, 'message' => 'Invalid section']);
    exit();
}

try {
    $conn = getPDOConnection();
    
    // Get photo details
    $stmt = $conn->prepare("SELECT * FROM website_photos WHERE id = ? AND section = ?");
    $stmt->execute([$photoId, $section]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$photo) {
        echo json_encode(['success' => false, 'message' => 'Photo not found']);
        exit();
    }
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM website_photos WHERE id = ?");
    if ($stmt->execute([$photoId])) {
        // Delete physical file
        $filePath = '../' . $photo['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        echo json_encode(['success' => true, 'message' => 'Photo deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete photo from database']);
    }
    
} catch (Exception $e) {
    error_log("Photo delete error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Delete failed. Please try again.']);
}
?>