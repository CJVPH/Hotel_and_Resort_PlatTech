<?php
require_once '../config/database.php';
require_once '../config/auth.php';

$bookingId = intval($_GET['booking_id'] ?? 0);
if ($bookingId <= 0) { header('Location: ../booking.php?tab=pavilion&error=Invalid booking'); exit(); }

if (!isLoggedIn()) {
    header('Location: ../login.php?redirect=payment/pavilion_payment.php&booking_id=' . $bookingId . '&message=' . urlencode('Please log in to complete your payment'));
    exit();
}

try {
    $conn = getDBConnection();
    $userId = getUserId();
    $stmt = $conn->prepare("SELECT * FROM pavilion_bookings WHERE id=? AND (user_id=? OR user_id IS NULL)");
    $stmt->bind_param('ii', $bookingId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) { header('Location: ../booking.php?tab=pavilion&error=Booking not found'); exit(); }
    $booking = $res->fetch_assoc();
    $booking['slot_note'] = $booking['special_requests'] ?? '';
    $stmt->close();
    // Associate with logged-in user if guest booking
    if ($booking['user_id'] === null) {
        $conn->query("UPDATE pavilion_bookings SET user_id=$userId WHERE id=$bookingId");
        $booking['user_id'] = $userId;
    }
    $conn->close();
} catch (Exception $e) {
    header('Location: ../booking.php?tab=pavilion&error=Database error'); exit();
}

$totalAmount = $booking['price'];
$halfAmount  = $totalAmount / 2;
$eventDate   = new DateTime($booking['event_date']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pavilion Payment - Paradise Hotel & Resort</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/booking.css">
    <link rel="stylesheet" href="css/payment.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="booking-page">
<header class="booking-header">
    <div class="header-container">
        <div class="header-left">
            <a href="../booking.php?tab=pavilion" class="back-link"><i class="fas fa-arrow-left"></i><span>Back</span></a>
        </div>
        <div class="header-center">
            <div class="hotel-logo">
                <img src="../uploads/logo/logo.png" alt="Paradise Hotel & Resort" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                <span>Paradise Hotel & Resort</span>
            </div>
        </div>
        <div class="header-right">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars(getFullName() ?? getUsername()); ?></span>
            </div>
        </div>
    </div>
</header>

<div class="payment-container">
    <div class="payment-form-section">
        <div class="booking-card">
            <div class="booking-header">
                <h1><i class="fas fa-archway"></i> Pavilion Payment</h1>
                <p>Choose your preferred payment amount</p>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-receipt"></i> Booking Summary</h3>
                <div class="reservation-summary">
                    <div class="summary-row"><span>Guest Name:</span><span><?php echo htmlspecialchars($booking['guest_name']); ?></span></div>
                    <div class="summary-row"><span>Event Date:</span><span><?php echo $eventDate->format('l, F j, Y'); ?></span></div>
                    <div class="summary-row"><span>Event Type:</span><span><?php echo htmlspecialchars($booking['event_type'] ?: 'Not specified'); ?></span></div>
                    <div class="summary-row"><span>Guests:</span><span><?php echo number_format($booking['pax']); ?> guests</span></div>
                    <?php if ($booking['slot_note']): ?>
                    <div class="summary-row"><span>Note:</span><span><?php echo htmlspecialchars($booking['slot_note']); ?></span></div>
                    <?php endif; ?>
                    <div class="summary-row total"><span>Total Amount:</span><span>₱<?php echo number_format($totalAmount, 2); ?></span></div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-money-bill-wave"></i> Select Payment Amount</h3>
                <div class="payment-options">
                    <div class="payment-option">
                        <div class="payment-card">
                            <div class="payment-header">
                                <h4><i class="fas fa-percentage"></i> 50% Deposit</h4>
                                <div class="payment-amount">₱<?php echo number_format($halfAmount, 2); ?></div>
                            </div>
                            <div class="payment-details">
                                <p>Pay 50% now and the remaining 50% on the event day</p>
                                <ul><li>Secure your date</li><li>Flexible payment</li><li>Pay balance on event day</li></ul>
                            </div>
                            <form action="pavilion_process_payment.php" method="POST">
                                <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">
                                <input type="hidden" name="payment_percentage" value="50">
                                <input type="hidden" name="payment_amount" value="<?php echo $halfAmount; ?>">
                                <button type="submit" class="btn-payment"><i class="fas fa-credit-card"></i> Pay 50% Now</button>
                            </form>
                        </div>
                    </div>
                    <div class="payment-option">
                        <div class="payment-card">
                            <div class="payment-header">
                                <h4><i class="fas fa-check-circle"></i> Full Payment</h4>
                                <div class="payment-amount">₱<?php echo number_format($totalAmount, 2); ?></div>
                            </div>
                            <div class="payment-details">
                                <p>Pay the full amount now and enjoy your event worry-free</p>
                                <ul><li>Complete payment</li><li>No additional charges</li><li>Priority confirmation</li></ul>
                            </div>
                            <form action="pavilion_process_payment.php" method="POST">
                                <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">
                                <input type="hidden" name="payment_percentage" value="100">
                                <input type="hidden" name="payment_amount" value="<?php echo $totalAmount; ?>">
                                <button type="submit" class="btn-payment btn-full"><i class="fas fa-credit-card"></i> Pay Full Amount</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="js/payment.js"></script>
</body>
</html>
