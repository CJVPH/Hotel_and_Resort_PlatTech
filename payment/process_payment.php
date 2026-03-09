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
$paymentPercentage = intval($_POST['payment_percentage'] ?? 0);
$paymentAmount = floatval($_POST['payment_amount'] ?? 0);

// Validation
if ($reservationId <= 0) {
    header('Location: ../booking.php?error=Invalid reservation');
    exit();
}

if (!in_array($paymentPercentage, [50, 100])) {
    header('Location: payment.php?reservation_id=' . $reservationId . '&error=Invalid payment percentage');
    exit();
}

if ($paymentAmount <= 0) {
    header('Location: payment.php?reservation_id=' . $reservationId . '&error=Invalid payment amount');
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
    
    // Update reservation with payment information
    $stmt = $conn->prepare("UPDATE reservations SET payment_percentage = ?, payment_amount = ?, payment_status = 'pending' WHERE id = ?");
    $stmt->bind_param("idi", $paymentPercentage, $paymentAmount, $reservationId);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        
        // Redirect to payment method selection
        header('Location: payment_method.php?reservation_id=' . $reservationId);
        exit();
    } else {
        throw new Exception("Failed to update reservation: " . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Process payment error: " . $e->getMessage());
    header('Location: payment.php?reservation_id=' . $reservationId . '&error=Database error, please try again');
    exit();
}
?>
