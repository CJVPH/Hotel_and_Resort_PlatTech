<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin login
requireAdminLogin();

header('Content-Type: application/json');

$response = ['success' => false, 'uploaded_files' => []];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['error'] = 'Invalid request method';
        echo json_encode($response);
        exit;
    }
    
    // Check database connection
    $conn = getDBConnection();
    
    // Get form data
    $roomNumber = $_POST['room_number'] ?? '';
    $roomType = $_POST['room_type'] ?? '';
    $paxGroup = intval($_POST['pax_group'] ?? 0);
    
    if (empty($roomNumber) || empty($roomType) || $paxGroup <= 0) {
        $response['error'] = 'Missing required parameters';
        echo json_encode($response);
        exit;
    }
    
    // Check if files were uploaded
    if (!isset($_FILES['room_images'])) {
        $response['error'] = 'No files uploaded';
        echo json_encode($response);
        exit;
    }
    
    $files = $_FILES['room_images'];
    
    // Handle both single and multiple file uploads
    if (!is_array($files['name'])) {
        $files = [
            'name' => [$files['name']],
            'type' => [$files['type']],
            'tmp_name' => [$files['tmp_name']],
            'error' => [$files['error']],
            'size' => [$files['size']]
        ];
    }
    
    // Filter out empty files
    $validFiles = [];
    for ($i = 0; $i < count($files['name']); $i++) {
        if (!empty($files['name'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK) {
            $validFiles[] = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
        }
    }
    
    if (empty($validFiles)) {
        $response['error'] = 'No valid files uploaded';
        echo json_encode($response);
        exit;
    }
    
    // Check current image count for this room
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM room_images WHERE room_number = ? AND room_type = ? AND pax_group = ? AND is_active = 1");
    $stmt->bind_param("ssi", $roomNumber, $roomType, $paxGroup);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentCount = $result->fetch_assoc()['count'];
    $stmt->close();
    
    // Check if adding these files would exceed the limit
    $maxImages = 10;
    if ($currentCount + count($validFiles) > $maxImages) {
        $response['error'] = "Cannot upload " . count($validFiles) . " files. Room already has $currentCount images. Maximum is $maxImages images per room.";
        echo json_encode($response);
        exit;
    }
    
    // Check upload directory
    $uploadDir = '../uploads/rooms/individual/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $response['error'] = 'Could not create upload directory';
            echo json_encode($response);
            exit;
        }
    }
    
    // Check table exists and has correct structure
    $result = $conn->query("SHOW TABLES LIKE 'room_images'");
    if ($result->num_rows == 0) {
        $response['error'] = 'room_images table does not exist';
        echo json_encode($response);
        exit;
    }
    
    // Check if sort_order column exists
    $result = $conn->query("SHOW COLUMNS FROM room_images LIKE 'sort_order'");
    if ($result->num_rows == 0) {
        $response['error'] = 'sort_order column missing. Run force_fix.php first.';
        echo json_encode($response);
        exit;
    }
    
    // Get next sort order
    $stmt = $conn->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 as next_sort FROM room_images WHERE room_number = ? AND room_type = ? AND pax_group = ?");
    $stmt->bind_param("ssi", $roomNumber, $roomType, $paxGroup);
    $stmt->execute();
    $result = $stmt->get_result();
    $nextSort = $result->fetch_assoc()['next_sort'];
    $stmt->close();
    
    // Process all files
    $uploadedFiles = [];
    $errors = [];
    
    foreach ($validFiles as $index => $file) {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = "Invalid file type for {$file['name']}: {$file['type']}";
            continue;
        }
        
        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = "File too large: {$file['name']} (" . round($file['size'] / 1024 / 1024, 2) . "MB). Maximum is 5MB.";
            continue;
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '_' . $index . '.' . $extension;
        $filePath = $uploadDir . $filename;
        
        // Move file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $errors[] = "Failed to move uploaded file: {$file['name']}";
            continue;
        }
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO room_images (room_number, room_type, pax_group, filename, original_name, file_path, file_size, mime_type, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            $errors[] = "Database prepare error for {$file['name']}: " . $conn->error;
            unlink($filePath);
            continue;
        }
        
        $dbFilePath = 'uploads/rooms/individual/' . $filename;
        $stmt->bind_param("ssisssisi", $roomNumber, $roomType, $paxGroup, $filename, $file['name'], $dbFilePath, $file['size'], $file['type'], $nextSort);
        
        if ($stmt->execute()) {
            $uploadedFiles[] = [
                'filename' => $filename,
                'original_name' => $file['name'],
                'file_path' => $dbFilePath,
                'sort_order' => $nextSort
            ];
            $nextSort++;
        } else {
            $errors[] = "Database insert error for {$file['name']}: " . $stmt->error;
            unlink($filePath);
        }
        
        $stmt->close();
    }
    
    // Prepare response
    if (!empty($uploadedFiles)) {
        $response['success'] = true;
        $response['uploaded_files'] = $uploadedFiles;
        $response['message'] = count($uploadedFiles) . ' file(s) uploaded successfully';
        
        if (!empty($errors)) {
            $response['message'] .= '. Some files failed: ' . implode(', ', $errors);
            $response['partial_success'] = true;
        }
    } else {
        $response['error'] = 'No files were uploaded successfully';
        if (!empty($errors)) {
            $response['error'] .= ': ' . implode(', ', $errors);
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $response['error'] = 'Exception: ' . $e->getMessage();
}

echo json_encode($response);
?>