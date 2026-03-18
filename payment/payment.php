<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Get reservation ID first
$reservationId = intval($_GET['reservation_id'] ?? 0);

if ($reservationId <= 0) {
    header('Location: ../booking.php?error=Invalid reservation');
    exit();
}

// If not logged in, redirect to login with reservation ID
if (!isLoggedIn()) {
    header('Location: ../login.php?redirect=payment/payment.php&reservation_id=' . $reservationId . '&message=' . urlencode('Please log in to complete your payment'));
    exit();
}

// Get reservation details
try {
    $conn = getDBConnection();
    
    // For logged-in users, check if reservation belongs to them OR if it's a guest reservation (user_id is NULL)
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
    $userId = getUserId();
    $stmt->bind_param("ii", $reservationId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: ../booking.php?error=Reservation not found');
        exit();
    }
    
    $reservation = $result->fetch_assoc();
    
    // If this was a guest reservation, update it to associate with the logged-in user
    if ($reservation['user_id'] === null) {
        $updateStmt = $conn->prepare("UPDATE reservations SET user_id = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $userId, $reservationId);
        $updateStmt->execute();
        $updateStmt->close();
        $reservation['user_id'] = $userId;
    }
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Payment page error: " . $e->getMessage());
    header('Location: ../booking.php?error=Database error');
    exit();
}

$totalAmount = $reservation['price'];
$halfAmount = $totalAmount / 2;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Options - Paradise Hotel & Resort</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/booking.css">
    <link rel="stylesheet" href="css/payment.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="booking-page">
    <!-- Header -->
    <header class="booking-header">
        <div class="header-container">
            <div class="header-left">
                <a href="../booking.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Booking</span>
                </a>
            </div>
            <div class="header-center">
                <div class="hotel-logo">
                    <img src="../uploads/logo/logo.png" alt="Paradise Hotel & Resort" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;">
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
                    <h1><i class="fas fa-credit-card"></i> Payment Options</h1>
                    <p>Choose your preferred payment amount</p>
                </div>

                <!-- Reservation Summary -->
                <div class="form-section">
                    <h3><i class="fas fa-receipt"></i> Reservation Summary</h3>
                    <div class="reservation-summary">
                        <div class="summary-row">
                            <span>Guest Name:</span>
                            <span><?php echo htmlspecialchars($reservation['guest_name']); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Room:</span>
                            <?php
                                $opts = json_decode($reservation['options'] ?? '{}', true);
                                $roomNum  = $opts['individual_room']['room_number'] ?? '';
                                $roomTyp  = $opts['individual_room']['room_type']   ?? $reservation['room_type'] ?? '';
                                $roomName = $opts['individual_room']['room_name']   ?? '';
                                // Clean up room_type if it's the old full string format
                                if (strpos($roomTyp, ' - ') !== false) {
                                    $roomTyp = explode(' - ', $roomTyp)[0];
                                }
                                if ($roomNum && $roomTyp) {
                                    echo '<span>' . htmlspecialchars($roomTyp) . ' Room ' . htmlspecialchars($roomNum) . '</span>';
                                } elseif ($roomNum) {
                                    echo '<span>Room ' . htmlspecialchars($roomNum) . '</span>';
                                } elseif ($roomName) {
                                    echo '<span>' . htmlspecialchars($roomName) . '</span>';
                                } else {
                                    echo '<span>' . htmlspecialchars($roomTyp ?: 'N/A') . '</span>';
                                }
                            ?>
                        </div>
                        <div class="summary-row">
                            <span>Check-in:</span>
                            <span><?php echo date('M d, Y', strtotime($reservation['checkin_date'])); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Check-out:</span>
                            <span><?php echo date('M d, Y', strtotime($reservation['checkout_date'])); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Guests:</span>
                            <span><?php echo $reservation['guests']; ?> guests</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total Amount:</span>
                            <span>₱<?php echo number_format($totalAmount, 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Payment Options -->
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
                                    <p>Pay 50% now and the remaining 50% upon check-in</p>
                                    <ul>
                                        <li>Secure your reservation</li>
                                        <li>Flexible payment schedule</li>
                                        <li>Pay balance at hotel</li>
                                    </ul>
                                </div>
                                <form action="process_payment.php" method="POST">
                                    <input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>">
                                    <input type="hidden" name="payment_percentage" value="50">
                                    <input type="hidden" name="payment_amount" value="<?php echo $halfAmount; ?>">
                                    <button type="submit" class="btn-payment">
                                        <i class="fas fa-credit-card"></i> Pay 50% Now
                                    </button>
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
                                    <p>Pay the full amount now and enjoy your stay worry-free</p>
                                    <ul>
                                        <li>Complete payment</li>
                                        <li>No additional charges</li>
                                        <li>Express check-in</li>
                                    </ul>
                                </div>
                                <form action="process_payment.php" method="POST">
                                    <input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>">
                                    <input type="hidden" name="payment_percentage" value="100">
                                    <input type="hidden" name="payment_amount" value="<?php echo $totalAmount; ?>">
                                    <button type="submit" class="btn-payment btn-full">
                                        <i class="fas fa-credit-card"></i> Pay Full Amount
                                    </button>
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

