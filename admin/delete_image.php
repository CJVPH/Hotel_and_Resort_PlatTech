<?php
require_once '../config/database.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['image_id'])) {
    $imageId = intval($_POST['image_id']);

    $conn = getDBConnection();

    // Fetch image path from DB to ensure we delete the correct file
    $sel = $conn->prepare("SELECT image_path FROM room_images WHERE id = ? LIMIT 1");
    $sel->bind_param('i', $imageId);
    $sel->execute();
    $res = $sel->get_result();

    if ($res && $res->num_rows === 1) {
        $row = $res->fetch_assoc();
        $imagePath = $row['image_path'];

        // Perform delete in DB
        $del = $conn->prepare("DELETE FROM room_images WHERE id = ?");
        $del->bind_param('i', $imageId);

        if ($del->execute()) {
            // Resolve full path and ensure it's inside uploads directory
            $baseDir = realpath(__DIR__ . '/../uploads/rooms');
            $fullPathCandidate = realpath(__DIR__ . '/../' . $imagePath);

            if ($fullPathCandidate && strpos($fullPathCandidate, $baseDir) === 0 && file_exists($fullPathCandidate)) {
                @unlink($fullPathCandidate);
            }

            header('Location: upload_images.php?message=' . urlencode('Image deleted successfully'));
        } else {
            header('Location: upload_images.php?message=' . urlencode('Error deleting image'));
        }

        $del->close();
    } else {
        header('Location: upload_images.php?message=' . urlencode('Image not found'));
    }

    $sel->close();
    $conn->close();
} else {
    header('Location: upload_images.php');
}
exit();
exit();
?>

