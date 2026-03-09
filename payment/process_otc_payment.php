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
$receiptNumber = trim($_POST['payment_reference'] ?? '');

// Validation
if ($reservationId <= 0) {
    header('Location: ../booking.php?error=Invalid reservation');
    exit();
}

if (empty($receiptNumber)) {
    header('Location: otc_payment.php?reservation_id=' . $reservationId . '&error=Receipt number is required');
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
    
    // Handle file upload
    $proofOfPayment = '';
    if (isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/payment_proofs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($_FILES['proof_of_payment']['name'], PATHINFO_EXTENSION);
        $fileName = 'otc_' . $reservationId . '_' . time() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $uploadPath)) {
            $proofOfPayment = $uploadPath;
        }
    } else {
        header('Location: otc_payment.php?reservation_id=' . $reservationId . '&error=Official Receipt is required');
        exit();
    }
    
    // Update reservation
    $paymentMethod = 'otc';
    $paymentStatus = 'pending'; // Will be verified by admin
    
    $stmt = $conn->prepare("UPDATE reservations SET payment_method = ?, payment_reference = ?, payment_status = ?, status = 'pending' WHERE id = ?");
    $stmt->bind_param("sssi", $paymentMethod, $receiptNumber, $paymentStatus, $reservationId);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        
        // Redirect to confirmation
        header('Location: ../confirmation.php?reservation_id=' . $reservationId . '&pending=1');
        exit();
    } else {
        throw new Exception("Failed to process payment");
    }
    
} catch (Exception $e) {
    error_log("OTC processing error: " . $e->getMessage());
    header('Location: otc_payment.php?reservation_id=' . $reservationId . '&error=Payment submission failed, please try again');
    exit();
}
?>
