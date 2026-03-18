<?php
// Only fetch reservation if not already set (when accessed directly)
if (!isset($reservation)) {
    require_once '../config/database.php';
    require_once '../config/auth.php';
    requireLogin();
    $reservationId = intval($_GET['reservation_id'] ?? 0);
    $bookingId     = intval($_GET['booking_id'] ?? 0);
    $isPavilion    = $bookingId > 0;
    if ($reservationId <= 0 && !$isPavilion) { header('Location: ../booking.php?error=Invalid reservation'); exit(); }
    try {
        $conn = getDBConnection();
        $userId = getUserId();
        if ($isPavilion) {
            $stmt = $conn->prepare("SELECT * FROM pavilion_bookings WHERE id=? AND user_id=?");
            $stmt->bind_param('ii', $bookingId, $userId);
        } else {
            $stmt = $conn->prepare("SELECT * FROM reservations WHERE id=? AND user_id=?");
            $stmt->bind_param('ii', $reservationId, $userId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) { header('Location: ../booking.php?error=Reservation not found'); exit(); }
        $reservation = $result->fetch_assoc();
        $stmt->close(); $conn->close();
    } catch (Exception $e) {
        error_log("Credit card payment page error: " . $e->getMessage());
        header('Location: ../booking.php?error=Database error'); exit();
    }
}
$reservationId = $reservationId ?? 0;
$bookingId     = $bookingId ?? 0;
$isPavilion    = $isPavilion ?? ($bookingId > 0);
$backUrl = $isPavilion ? "payment_method.php?booking_id=$bookingId" : "payment_method.php?reservation_id=$reservationId";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Card Payment - Paradise Hotel & Resort</title>
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
                <a href="<?php echo $backUrl; ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
            <div class="header-center">
                <div class="hotel-logo">
                    <img src="../uploads/logo/logo.png" alt="Paradise Hotel & Resort" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;">
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
                    <h1><i class="fas fa-credit-card"></i> Credit Card Payment</h1>
                    <p>Enter your card details securely</p>
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

                <!-- Credit Card Form -->
                <form id="creditCardForm" action="process_credit_card.php" method="POST">
                    <?php if ($isPavilion ?? false): ?><input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>"><?php else: ?><input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>"><?php endif; ?>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-credit-card"></i> Card Information</h3>
                        
                        <div class="form-group">
                            <label for="card_number">Card Number *</label>
                            <input type="text" id="card_number" name="card_number" 
                                   placeholder="1234 5678 9012 3456" 
                                   maxlength="19" required
                                   pattern="[0-9\s]{13,19}">
                            <div class="card-icons">
                                <i class="fab fa-cc-visa"></i>
                                <i class="fab fa-cc-mastercard"></i>
                                <i class="fab fa-cc-amex"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="card_name">Cardholder Name *</label>
                            <input type="text" id="card_name" name="card_name" 
                                   placeholder="JOHN DOE" 
                                   required
                                   style="text-transform: uppercase;">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="expiry_date">Expiry Date *</label>
                                <input type="text" id="expiry_date" name="expiry_date" 
                                       placeholder="MM/YY" 
                                       maxlength="5" required
                                       pattern="(0[1-9]|1[0-2])\/[0-9]{2}">
                            </div>
                            <div class="form-group">
                                <label for="cvv">CVV *</label>
                                <input type="text" id="cvv" name="cvv" 
                                       placeholder="123" 
                                       maxlength="4" required
                                       pattern="[0-9]{3,4}">
                                <small style="color: #666;">3-4 digits on back of card</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Billing Address</h3>
                        
                        <div class="form-group">
                            <label for="billing_address">Street Address *</label>
                            <input type="text" id="billing_address" name="billing_address" 
                                   placeholder="123 Main Street" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="billing_city">City *</label>
                                <input type="text" id="billing_city" name="billing_city" 
                                       placeholder="Manila" required>
                            </div>
                            <div class="form-group">
                                <label for="billing_zip">ZIP Code *</label>
                                <input type="text" id="billing_zip" name="billing_zip" 
                                       placeholder="1000" required
                                       pattern="[0-9]{4}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="billing_country">Country *</label>
                            <select id="billing_country" name="billing_country" required>
                                <option value="Philippines" selected>Philippines</option>
                                <option value="United States">United States</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="Canada">Canada</option>
                                <option value="Australia">Australia</option>
                            </select>
                        </div>
                    </div>

                    <!-- Security Notice -->
                    <div class="security-notice">
                        <i class="fas fa-lock"></i>
                        <p>Your payment information is encrypted and secure. We never store your full card details.</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-lock"></i> Complete Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    

    
    <script src="js/payment.js"></script>`n</body>
</html>



