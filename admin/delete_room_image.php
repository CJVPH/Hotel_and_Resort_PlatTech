<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin login
requireAdminLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Get image ID
    $imageId = intval($_POST['image_id'] ?? 0);
    
    if ($imageId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid image ID']);
        exit;
    }
    
    // Get image info before deleting
    $stmt = $conn->prepare("SELECT file_path FROM room_images WHERE id = ?");
    $stmt->bind_param("i", $imageId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $filePath = '../' . $row['file_path'];
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM room_images WHERE id = ?");
        $stmt->bind_param("i", $imageId);
        
        if ($stmt->execute()) {
            // Delete physical file
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete image from database']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Image not found']);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Delete room image error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?>