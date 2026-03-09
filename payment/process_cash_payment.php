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

if ($paymentMethod !== 'cash') {
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
        $stmt->close();
        $conn->close();
        exit();
    }
    
    $reservation = $result->fetch_assoc();
    $stmt->close();
    
    // Generate payment reference
    $paymentReference = 'PAY-CAS-' . date('Ymd') . '-' . str_pad($reservationId, 6, '0', STR_PAD_LEFT);
    
    // Update reservation with cash payment method
    $stmt = $conn->prepare("UPDATE reservations SET payment_method = ?, payment_reference = ?, payment_status = 'pending', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $paymentMethod, $paymentReference, $reservationId);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        
        // Redirect to confirmation page
        header('Location: ../confirmation.php?reservation_id=' . $reservationId);
        exit();
    } else {
        throw new Exception("Failed to update reservation: " . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Process cash payment error: " . $e->getMessage());
    header('Location: cash_payment.php?reservation_id=' . $reservationId . '&error=Database error, please try again');
    exit();
}
?>
