<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require login
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../booking.php?error=Invalid request method');
    exit();
}

// Get form data
$reservationId = intval($_POST['reservation_id'] ?? 0);
$paymentMethod = $_POST['payment_method'] ?? '';

// Validation
if ($reservationId <= 0) {
    header('Location: ../booking.php?error=Invalid reservation');
    exit();
}

$validMethods = ['credit_card', 'paypal', 'gcash', 'bank_transfer', 'cash', 'otc'];
if (!in_array($paymentMethod, $validMethods)) {
    header('Location: payment_method.php?reservation_id=' . $reservationId . '&error=Invalid payment method');
    exit();
}

try {
    $conn = getDBConnection();
    
    // Verify reservation belongs to current user
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ? AND user_id = ?");
    $userId = getUserId();
    $stmt->bind_param("ii", $reservationId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: ../booking.php?error=Reservation not found');
        exit();
    }
    
    $reservation = $result->fetch_assoc();
    $stmt->close();
    
    // If credit card, show billing form
    if ($paymentMethod === 'credit_card') {
        include 'credit_card_form.php';
        exit();
    }
    
    // If GCash, show QR code
    if ($paymentMethod === 'gcash') {
        include 'gcash_payment.php';
        exit();
    }
    
    // If PayPal, show PayPal payment page
    if ($paymentMethod === 'paypal') {
        include 'paypal_payment.php';
        exit();
    }
    
    // If Bank Transfer, show bank details
    if ($paymentMethod === 'bank_transfer') {
        include 'bank_transfer_payment.php';
        exit();
    }
    
    // If Cash, show cash payment instructions
    if ($paymentMethod === 'cash') {
        include 'cash_payment.php';
        exit();
    }
    
    // If OTC, show over the counter payment
    if ($paymentMethod === 'otc') {
        include 'otc_payment.php';
        exit();
    }
    
} catch (Exception $e) {
    error_log("Complete payment error: " . $e->getMessage());
    header('Location: payment_method.php?reservation_id=' . $reservationId . '&error=Payment processing failed, please try again');
    exit();
}
?>
