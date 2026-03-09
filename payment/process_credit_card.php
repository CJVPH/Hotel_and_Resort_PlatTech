<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require login
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../booking.php?error=Invalid request');
    exit();
}

// Get form data
$reservationId = intval($_POST['reservation_id'] ?? 0);
$cardNumber = preg_replace('/\s/', '', $_POST['card_number'] ?? '');
$cardName = trim($_POST['card_name'] ?? '');
$expiryDate = $_POST['expiry_date'] ?? '';
$cvv = $_POST['cvv'] ?? '';
$billingAddress = trim($_POST['billing_address'] ?? '');
$billingCity = trim($_POST['billing_city'] ?? '');
$billingZip = trim($_POST['billing_zip'] ?? '');
$billingCountry = trim($_POST['billing_country'] ?? '');

// Validation
if ($reservationId <= 0) {
    header('Location: ../booking.php?error=Invalid reservation');
    exit();
}

try {
    $conn = getDBConnection();
    
    // Verify reservation
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
    
    // In a real application, you would:
    // 1. Validate card using Luhn algorithm
    // 2. Process payment through payment gateway (Stripe, PayPal, etc.)
    // 3. Handle 3D Secure authentication
    // 4. Store encrypted card details (last 4 digits only)
    
    // For demo purposes, we'll simulate successful payment
    $lastFourDigits = substr($cardNumber, -4);
    $maskedCardNumber = str_repeat('*', strlen($cardNumber) - 4) . $lastFourDigits;
    
    // Generate payment reference
    $paymentReference = 'PAY-CRD-' . date('Ymd') . '-' . str_pad($reservationId, 6, '0', STR_PAD_LEFT);
    
    // Update reservation
    $paymentMethod = 'credit_card';
    $paymentStatus = 'completed';
    $paymentDetails = json_encode([
        'card_last_four' => $lastFourDigits,
        'card_name' => $cardName,
        'billing_city' => $billingCity,
        'billing_country' => $billingCountry
    ]);
    
    $stmt = $conn->prepare("UPDATE reservations SET payment_method = ?, payment_reference = ?, payment_status = ?, status = 'confirmed' WHERE id = ?");
    $stmt->bind_param("sssi", $paymentMethod, $paymentReference, $paymentStatus, $reservationId);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        
        // Redirect to confirmation
        header('Location: ../confirmation.php?reservation_id=' . $reservationId);
        exit();
    } else {
        throw new Exception("Failed to process payment");
    }
    
} catch (Exception $e) {
    error_log("Credit card processing error: " . $e->getMessage());
    header('Location: payment_method.php?reservation_id=' . $reservationId . '&error=Payment failed, please try again');
    exit();
}
?>
