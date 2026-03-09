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

$section = $_POST['section'] ?? '';
$validSections = ['carousel', 'pool', 'spa', 'restaurant', 'pavilion'];

if (!in_array($section, $validSections)) {
    echo json_encode(['success' => false, 'message' => 'Invalid section']);
    exit();
}

if (!isset($_FILES['photos']) || empty($_FILES['photos']['name'][0])) {
    echo json_encode(['success' => false, 'message' => 'No files uploaded']);
    exit();
}

try {
    // Create upload directory if it doesn't exist
    $uploadDir = "../uploads/{$section}/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $conn = getPDOConnection();
    $uploadedFiles = [];
    $errors = [];
    
    $files = $_FILES['photos'];
    $fileCount = count($files['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload error for file " . ($i + 1);
            continue;
        }
        
        $originalName = $files['name'][$i];
        $tmpName = $files['tmp_name'][$i];
        $fileSize = $files['size'][$i];
        $mimeType = $files['type'][$i];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = "Invalid file type for {$originalName}";
            continue;
        }
        
        // Validate file size (max 5MB)
        if ($fileSize > 5 * 1024 * 1024) {
            $errors[] = "File too large: {$originalName}";
            continue;
        }
        
        // Generate unique filename
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($tmpName, $filePath)) {
            // Save to database
            $stmt = $conn->prepare("INSERT INTO website_photos (section, filename, original_name, file_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?)");
            $relativePath = "uploads/{$section}/{$filename}";
            
            if ($stmt->execute([$section, $filename, $originalName, $relativePath, $fileSize, $mimeType])) {
                $uploadedFiles[] = $originalName;
            } else {
                $errors[] = "Database error for {$originalName}";
                unlink($filePath); // Remove file if database insert failed
            }
        } else {
            $errors[] = "Failed to move {$originalName}";
        }
    }
    
    if (!empty($uploadedFiles)) {
        $message = count($uploadedFiles) . " photo(s) uploaded successfully";
        if (!empty($errors)) {
            $message .= ". " . count($errors) . " file(s) failed.";
        }
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No files were uploaded. ' . implode(', ', $errors)]);
    }
    
} catch (Exception $e) {
    error_log("Photo upload error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Upload failed. Please try again.']);
}
?>