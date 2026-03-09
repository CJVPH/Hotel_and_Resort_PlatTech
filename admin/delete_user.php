<?php
require_once 'auth.php';
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit();
}

$adminId = $_SESSION['admin_id'] ?? null;
if (!$adminId) {
    header('Location: login.php');
    exit();
}

$userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
if ($userId <= 0) {
    header('Location: users.php?message=' . urlencode('Invalid user id'));
    exit();
}

// Prevent deleting yourself
if ($userId == $adminId) {
    header('Location: users.php?message=' . urlencode('You cannot delete your own admin account'));
    exit();
}

$conn = getDBConnection();
// Check if the target user is an admin; prevent deleting admin accounts
$check = $conn->prepare("SELECT is_admin FROM users WHERE id = ? LIMIT 1");
$check->bind_param('i', $userId);
$check->execute();
$res = $check->get_result();
if (!$res || $res->num_rows !== 1) {
    $check->close();
    $conn->close();
    header('Location: users.php?message=' . urlencode('User not found'));
    exit();
}
$row = $res->fetch_assoc();
$check->close();

if (isset($row['is_admin']) && $row['is_admin'] == 1) {
    $conn->close();
    header('Location: users.php?message=' . urlencode('Cannot delete an admin account'));
    exit();
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header('Location: users.php?message=' . urlencode('User deleted successfully'));
    exit();
} else {
    $err = $conn->error;
    $stmt->close();
    $conn->close();
    header('Location: users.php?message=' . urlencode('Error deleting user: ' . $err));
    exit();
}
?>