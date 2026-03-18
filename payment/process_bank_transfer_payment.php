<?php
require_once '../config/database.php';
require_once '../config/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../booking.php?error=Invalid request'); exit();
}

$reservationId    = intval($_POST['reservation_id'] ?? 0);
$bookingId        = intval($_POST['booking_id'] ?? 0);
$paymentReference = trim($_POST['payment_reference'] ?? '');
$bankName         = trim($_POST['bank_name'] ?? '');
$isPavilion       = $bookingId > 0;
$userId           = getUserId();

if (empty($paymentReference) || empty($bankName)) {
    $back = $isPavilion ? "bank_transfer_payment.php?booking_id=$bookingId" : "bank_transfer_payment.php?reservation_id=$reservationId";
    header('Location: ' . $back . '&error=All fields are required'); exit();
}

try {
    $conn = getDBConnection();

    // Handle file upload
    if (!isset($_FILES['proof_of_payment']) || $_FILES['proof_of_payment']['error'] !== UPLOAD_ERR_OK) {
        $back = $isPavilion ? "bank_transfer_payment.php?booking_id=$bookingId" : "bank_transfer_payment.php?reservation_id=$reservationId";
        header('Location: ' . $back . '&error=Deposit slip is required'); exit();
    }
    $uploadDir = 'uploads/payment_proofs/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext      = pathinfo($_FILES['proof_of_payment']['name'], PATHINFO_EXTENSION);
    $id       = $isPavilion ? $bookingId : $reservationId;
    $fileName = 'bank_' . $id . '_' . time() . '.' . $ext;
    move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $uploadDir . $fileName);

    if ($isPavilion) {
        $stmt = $conn->prepare("SELECT id FROM pavilion_bookings WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $bookingId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) { header('Location: ../booking.php?error=Booking not found'); exit(); }
        $stmt->close();
        $upd = $conn->prepare("UPDATE pavilion_bookings SET payment_method='bank_transfer', payment_reference=?, payment_status='pending', status='pending' WHERE id=?");
        $upd->bind_param('si', $paymentReference, $bookingId);
        $upd->execute(); $upd->close();
        $conn->close();
        header('Location: ../confirmation.php?booking_id=' . $bookingId . '&pending=1'); exit();
    } else {
        if ($reservationId <= 0) { header('Location: ../booking.php?error=Invalid reservation'); exit(); }
        $stmt = $conn->prepare("SELECT id FROM reservations WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $reservationId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) { header('Location: ../booking.php?error=Reservation not found'); exit(); }
        $stmt->close();
        $upd = $conn->prepare("UPDATE reservations SET payment_method='bank_transfer', payment_reference=?, payment_status='pending', status='pending' WHERE id=?");
        $upd->bind_param('si', $paymentReference, $reservationId);
        $upd->execute(); $upd->close();
        $conn->close();
        header('Location: ../confirmation.php?reservation_id=' . $reservationId . '&pending=1'); exit();
    }
} catch (Exception $e) {
    error_log("Bank transfer error: " . $e->getMessage());
    $back = $isPavilion ? "payment_method.php?booking_id=$bookingId" : "payment_method.php?reservation_id=$reservationId";
    header('Location: ' . $back . '&error=Payment submission failed'); exit();
}
?>
