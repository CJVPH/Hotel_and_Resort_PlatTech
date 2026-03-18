<?php
require_once '../config/database.php';
require_once '../config/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../booking.php?error=Invalid request'); exit();
}

$reservationId = intval($_POST['reservation_id'] ?? 0);
$bookingId     = intval($_POST['booking_id'] ?? 0);
$cardName      = trim($_POST['card_name'] ?? '');
$cardNumber    = preg_replace('/\s/', '', $_POST['card_number'] ?? '');
$isPavilion    = $bookingId > 0;
$userId        = getUserId();

try {
    $conn = getDBConnection();
    $lastFour = substr($cardNumber, -4);
    $ref      = 'PAY-CRD-' . date('Ymd') . '-' . str_pad($isPavilion ? $bookingId : $reservationId, 6, '0', STR_PAD_LEFT);

    if ($isPavilion) {
        $stmt = $conn->prepare("SELECT id FROM pavilion_bookings WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $bookingId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) { header('Location: ../booking.php?error=Booking not found'); exit(); }
        $stmt->close();
        $upd = $conn->prepare("UPDATE pavilion_bookings SET payment_method='credit_card', payment_reference=?, payment_status='completed', status='confirmed' WHERE id=?");
        $upd->bind_param('si', $ref, $bookingId);
        $upd->execute(); $upd->close();
        $conn->close();
        header('Location: ../confirmation.php?booking_id=' . $bookingId); exit();
    } else {
        if ($reservationId <= 0) { header('Location: ../booking.php?error=Invalid reservation'); exit(); }
        $stmt = $conn->prepare("SELECT id FROM reservations WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $reservationId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) { header('Location: ../booking.php?error=Reservation not found'); exit(); }
        $stmt->close();
        $upd = $conn->prepare("UPDATE reservations SET payment_method='credit_card', payment_reference=?, payment_status='completed', status='confirmed' WHERE id=?");
        $upd->bind_param('si', $ref, $reservationId);
        $upd->execute(); $upd->close();
        $conn->close();
        header('Location: ../confirmation.php?reservation_id=' . $reservationId); exit();
    }
} catch (Exception $e) {
    error_log("Credit card processing error: " . $e->getMessage());
    $back = $isPavilion ? "payment_method.php?booking_id=$bookingId" : "payment_method.php?reservation_id=$reservationId";
    header('Location: ' . $back . '&error=Payment failed'); exit();
}
?>
