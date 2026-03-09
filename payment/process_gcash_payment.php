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
$gcashReference = trim($_POST['gcash_reference'] ?? '');

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
    
    // Handle file upload
    $proofOfPayment = '';
    if (isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/payment_proofs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($_FILES['proof_of_payment']['name'], PATHINFO_EXTENSION);
        $fileName = 'gcash_' . $reservationId . '_' . time() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $uploadPath)) {
            $proofOfPayment = $uploadPath;
        }
    }
    
    // Generate payment reference
    $paymentReference = 'PAY-GCA-' . date('Ymd') . '-' . str_pad($reservationId, 6, '0', STR_PAD_LEFT);
    
    // Update reservation (pending verification)
    $paymentMethod = 'gcash';
    $paymentStatus = 'pending'; // Will be verified by admin
    
    $stmt = $conn->prepare("UPDATE reservations SET payment_method = ?, payment_reference = ?, payment_status = ?, status = 'pending' WHERE id = ?");
    $stmt->bind_param("sssi", $paymentMethod, $paymentReference, $paymentStatus, $reservationId);
    
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
    error_log("GCash processing error: " . $e->getMessage());
    header('Location: payment_method.php?reservation_id=' . $reservationId . '&error=Payment submission failed, please try again');
    exit();
}
?>
