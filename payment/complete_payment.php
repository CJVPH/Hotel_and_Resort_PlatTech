<?php
require_once '../config/database.php';
require_once '../config/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../booking.php?error=Invalid request method'); exit();
}

$reservationId = intval($_POST['reservation_id'] ?? 0);
$bookingId     = intval($_POST['booking_id'] ?? 0);
$paymentMethod = $_POST['payment_method'] ?? '';
$isPavilion    = $bookingId > 0;

$validMethods = ['credit_card', 'paypal', 'gcash', 'bank_transfer', 'cash', 'otc'];
if (!in_array($paymentMethod, $validMethods)) {
    $back = $isPavilion ? "payment_method.php?booking_id=$bookingId" : "payment_method.php?reservation_id=$reservationId";
    header('Location: ' . $back . '&error=Invalid payment method'); exit();
}

$userId = getUserId();

try {
    $conn = getDBConnection();

    if ($isPavilion) {
        $stmt = $conn->prepare("SELECT * FROM pavilion_bookings WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $bookingId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) { header('Location: ../booking.php?error=Booking not found'); exit(); }
        $reservation = $result->fetch_assoc();
        $stmt->close();
    } else {
        if ($reservationId <= 0) { header('Location: ../booking.php?error=Invalid reservation'); exit(); }
        $stmt = $conn->prepare("SELECT * FROM reservations WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $reservationId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) { header('Location: ../booking.php?error=Reservation not found'); exit(); }
        $reservation = $result->fetch_assoc();
        $stmt->close();
    }

    // Route to the appropriate payment sub-page
    // Each included file uses $reservation, $paymentMethod, $isPavilion, $bookingId, $reservationId
    switch ($paymentMethod) {
        case 'credit_card':   include 'credit_card_form.php';    exit();
        case 'gcash':         include 'gcash_payment.php';        exit();
        case 'paypal':        include 'paypal_payment.php';       exit();
        case 'bank_transfer': include 'bank_transfer_payment.php'; exit();
        case 'cash':          include 'cash_payment.php';         exit();
        case 'otc':           include 'otc_payment.php';          exit();
    }

} catch (Exception $e) {
    error_log("Complete payment error: " . $e->getMessage());
    $back = $isPavilion ? "payment_method.php?booking_id=$bookingId" : "payment_method.php?reservation_id=$reservationId";
    header('Location: ' . $back . '&error=Payment processing failed'); exit();
}
?>
