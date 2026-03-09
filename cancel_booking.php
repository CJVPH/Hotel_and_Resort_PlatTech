<?php
require_once 'config/database.php';
require_once 'config/auth.php';

header('Content-Type: application/json');

// Require login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$userId = getUserId();
$reservationId = intval($_POST['reservation_id'] ?? 0);

if ($reservationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid reservation ID']);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Verify the reservation belongs to the user and is upcoming
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $reservationId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Reservation not found']);
        $stmt->close();
        $conn->close();
        exit();
    }
    
    $reservation = $result->fetch_assoc();
    $stmt->close();
    
    // Check if booking is in the future
    $today = date('Y-m-d');
    if ($reservation['checkin_date'] < $today) {
        echo json_encode(['success' => false, 'message' => 'Cannot cancel past bookings']);
        $conn->close();
        exit();
    }
    
    // Check if booking is within 24 hours
    $checkinTime = strtotime($reservation['checkin_date']);
    $currentTime = time();
    $hoursUntilCheckin = ($checkinTime - $currentTime) / 3600;
    
    if ($hoursUntilCheckin < 24) {
        echo json_encode(['success' => false, 'message' => 'Cannot cancel bookings within 24 hours of check-in. Please contact us directly.']);
        $conn->close();
        exit();
    }
    
    // Update reservation status to cancelled
    $stmt = $conn->prepare("UPDATE reservations SET payment_status = 'cancelled', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $reservationId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error cancelling booking']);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Cancel booking error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
