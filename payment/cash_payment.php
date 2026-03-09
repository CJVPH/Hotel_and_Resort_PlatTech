<?php
// Only fetch reservation if not already set (when accessed directly)
if (!isset($reservation) || !isset($reservationId)) {
    require_once '../config/database.php';
    require_once '../config/auth.php';

    // Require login
    requireLogin();

    // Get reservation ID
    $reservationId = intval($_GET['reservation_id'] ?? 0);

    if ($reservationId <= 0) {
        header('Location: ../booking.php?error=Invalid reservation');
        exit();
    }

    // Get reservation details
    try {
        $conn = getDBConnection();
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
        $conn->close();
        
    } catch (Exception $e) {
        error_log("Cash payment page error: " . $e->getMessage());
        header('Location: ../booking.php?error=Database error');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Payment - Paradise Hotel & Resort</title>
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
                <a href="payment_method.php?reservation_id=<?php echo $reservationId; ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
            <div class="header-center">
                <div class="hotel-logo">
                    <i class="fas fa-hotel"></i>
                    <span>Paradise Hotel & Resort</span>
                </div>
            </div>
            <div class="header-right">
                <a href="profile.php" class="user-info" style="text-decoration: none; color: #2C3E50;">
                    <i class="fas fa-user-circle"></i>
                    <span>Hello, <?php echo htmlspecialchars(getFirstName() ?? getUsername()); ?></span>
                </a>
            </div>
        </div>
    </header>

    <div class="payment-container">
        <div class="payment-form-section">
            <div class="booking-card">
                <div class="booking-header">
                    <h1><i class="fas fa-money-bill-wave"></i> Cash Payment</h1>
                    <p>Pay in cash at our front desk</p>
                </div>

                <!-- Payment Amount -->
                <div class="payment-amount-compact">
                    <div class="amount-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="amount-info">
                        <span class="amount-label">Amount to Pay</span>
                        <span class="amount-value">₱<?php echo number_format($reservation['payment_amount'], 2); ?></span>
                    </div>
                </div>

                <!-- Cash Payment Information -->
                <div class="form-section">
                    <h3><i class="fas fa-info-circle"></i> Payment Instructions</h3>
                    
                    <div class="info-box">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <h4>Paradise Hotel & Resort</h4>
                            <p>Calayo, Nasugbu, Batangas, Philippines</p>
                            <p><strong>Front Desk Hours:</strong> 24/7</p>
                            <p><strong>Contact:</strong> +63 123 456 7890</p>
                        </div>
                    </div>

                    <div class="reference-box">
                        <p><strong>Reference Number:</strong> PAY-CAS-<?php echo date('Ymd') . '-' . str_pad($reservationId, 6, '0', STR_PAD_LEFT); ?></p>
                        <small>Please provide this reference number when paying at the front desk</small>
                    </div>

                    <div class="payment-instructions">
                        <h4><i class="fas fa-clipboard-list"></i> How to Pay:</h4>
                        <ol>
                            <li>Visit our front desk at Paradise Hotel & Resort</li>
                            <li>Provide your reference number: <strong>PAY-CAS-<?php echo date('Ymd') . '-' . str_pad($reservationId, 6, '0', STR_PAD_LEFT); ?></strong></li>
                            <li>Pay the exact amount: <strong>₱<?php echo number_format($reservation['payment_amount'], 2); ?></strong></li>
                            <li>Receive your official receipt</li>
                            <li>Your reservation will be confirmed immediately</li>
                        </ol>
                    </div>

                    <div class="important-note">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <h4>Important Notes:</h4>
                            <ul>
                                <li>Payment must be made at least 24 hours before check-in</li>
                                <li>Bring a valid ID for verification</li>
                                <li>Keep your official receipt for check-in</li>
                                <li>Late payments may result in reservation cancellation</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Confirm Button -->
                <form action="process_cash_payment.php" method="POST">
                    <input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>">
                    <input type="hidden" name="payment_method" value="cash">
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-check"></i> I Understand - Confirm Reservation
                        </button>
                    </div>
                </form>

                <!-- Security Notice -->
                <div class="security-notice">
                    <i class="fas fa-shield-alt"></i>
                    <p>Your reservation is confirmed. Please complete the cash payment at our front desk before check-in.</p>
                </div>
            </div>
        </div>
    </div>

    
    <script src="js/payment.js"></script>`n</body>
</html>







