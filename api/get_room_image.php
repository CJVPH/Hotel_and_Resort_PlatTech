<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $conn = getDBConnection();
    
    // Get parameters
    $roomNumber = $_GET['room_number'] ?? '';
    $roomType = $_GET['room_type'] ?? '';
    $paxGroup = intval($_GET['pax_group'] ?? 0);
    
    if (empty($roomNumber) || empty($roomType) || $paxGroup <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing required parameters'
        ]);
        exit;
    }
    
    // Get all room images for this room
    $stmt = $conn->prepare("SELECT * FROM room_images WHERE room_number = ? AND room_type = ? AND pax_group = ? AND is_active = 1 ORDER BY sort_order ASC");
    $stmt->bind_param("ssi", $roomNumber, $roomType, $paxGroup);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = [
            'id' => $row['id'],
            'filename' => $row['filename'],
            'file_path' => $row['file_path'],
            'original_name' => $row['original_name'],
            'upload_date' => $row['upload_date'],
            'sort_order' => $row['sort_order']
        ];
    }
    
    if (!empty($images)) {
        echo json_encode([
            'success' => true,
            'images' => $images,
            'count' => count($images)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No images found for this room',
            'images' => [],
            'count' => 0
        ]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Get room images error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?>