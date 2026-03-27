<?php
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['photo'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
    exit;
}

$file    = $_FILES['photo'];
$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$maxSize = 3 * 1024 * 1024; // 3MB

if (!in_array($file['type'], $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, WEBP or GIF allowed.']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File must be under 3MB.']);
    exit;
}

$userId  = getUserId();
$ext     = pathinfo($file['name'], PATHINFO_EXTENSION);
$dir     = 'uploads/avatars/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
$dest     = $dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'Upload failed.']);
    exit;
}

try {
    $conn = getDBConnection();
    // Add column if it doesn't exist yet
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT NULL");

    // Delete old photo file if exists
    $old = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
    $old->bind_param('i', $userId);
    $old->execute();
    $oldPath = $old->get_result()->fetch_assoc()['profile_photo'] ?? '';
    $old->close();
    if ($oldPath && file_exists($oldPath)) @unlink($oldPath);

    $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
    $stmt->bind_param('si', $dest, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'path' => $dest]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
