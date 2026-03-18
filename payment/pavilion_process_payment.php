<?php
require_once '../config/database.php';
require_once '../config/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../booking.php?tab=pavilion'); exit();
}

$bookingId         = intval($_POST['booking_id'] ?? 0);
$paymentPercentage = intval($_POST['payment_percentage'] ?? 0);
$paymentAmount     = floatval($_POST['payment_amount'] ?? 0);

if ($bookingId <= 0 || !in_array($paymentPercentage, [50, 100]) || $paymentAmount <= 0) {
    header('Location: pavilion_payment.php?booking_id=' . $bookingId . '&error=Invalid payment data'); exit();
}

try {
    $conn   = getDBConnection();
    $userId = getUserId();
    $stmt   = $conn->prepare("SELECT * FROM pavilion_bookings WHERE id=? AND user_id=?");
    $stmt->bind_param('ii', $bookingId, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        header('Location: ../booking.php?tab=pavilion&error=Booking not found'); exit();
    }
    $stmt->close();

    $upd = $conn->prepare("UPDATE pavilion_bookings SET payment_percentage=?, payment_amount=?, payment_status='pending' WHERE id=?");
    $upd->bind_param('idi', $paymentPercentage, $paymentAmount, $bookingId);
    $upd->execute(); $upd->close();
    $conn->close();

    header('Location: payment_method.php?booking_id=' . $bookingId); exit();
} catch (Exception $e) {
    header('Location: pavilion_payment.php?booking_id=' . $bookingId . '&error=Database error'); exit();
}
