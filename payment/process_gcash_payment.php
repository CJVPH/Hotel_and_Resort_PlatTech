<?php
require_once '../config/database.php';
require_once '../config/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../booking.php?error=Invalid request'); exit();
}

$reservationId  = intval($_POST['reservation_id'] ?? 0);
$bookingId      = intval($_POST['booking_id'] ?? 0);
$gcashReference = trim($_POST['gcash_reference'] ?? '');
$isPavilion     = $bookingId > 0;
$userId         = getUserId();

try {
    $conn = getDBConnection();

    if ($isPavilion) {
        $stmt = $conn->prepare("SELECT * FROM pavilion_bookings WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $bookingId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) { header('Location: ../booking.php?error=Booking not found'); exit(); }
        $stmt->close();
        $ref = 'PAY-GCA-' . date('Ymd') . '-' . str_pad($bookingId, 6, '0', STR_PAD_LEFT);
        $upd = $conn->prepare("UPDATE pavilion_bookings SET payment_method='gcash', payment_reference=?, payment_status='pending', status='pending' WHERE id=?");
        $upd->bind_param('si', $ref, $bookingId);
        $upd->execute(); $upd->close();
        $conn->close();
        header('Location: ../confirmation.php?booking_id=' . $bookingId); exit();
    } else {
        if ($reservationId <= 0) { header('Location: ../booking.php?error=Invalid reservation'); exit(); }
        $stmt = $conn->prepare("SELECT * FROM reservations WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $reservationId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) { header('Location: ../booking.php?error=Reservation not found'); exit(); }
        $stmt->close();

        // Handle file upload
        if (isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/payment_proofs/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext      = pathinfo($_FILES['proof_of_payment']['name'], PATHINFO_EXTENSION);
            $fileName = 'gcash_' . $reservationId . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $uploadDir . $fileName);
        }

        $ref = 'PAY-GCA-' . date('Ymd') . '-' . str_pad($reservationId, 6, '0', STR_PAD_LEFT);
        $upd = $conn->prepare("UPDATE reservations SET payment_method='gcash', payment_reference=?, payment_status='pending', status='pending' WHERE id=?");
        $upd->bind_param('si', $ref, $reservationId);
        $upd->execute(); $upd->close();
        $conn->close();
        header('Location: ../confirmation.php?reservation_id=' . $reservationId . '&pending=1'); exit();
    }
} catch (Exception $e) {
    error_log("GCash processing error: " . $e->getMessage());
    $back = $isPavilion ? "payment_method.php?booking_id=$bookingId" : "payment_method.php?reservation_id=$reservationId";
    header('Location: ' . $back . '&error=Payment submission failed'); exit();
}
?>
