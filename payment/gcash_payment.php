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
        error_log("GCash payment page error: " . $e->getMessage());
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
    <title>GCash Payment - Paradise Hotel & Resort</title>
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
                    <h1><i class="fas fa-mobile-alt"></i> GCash Payment</h1>
                    <p>Scan QR code to pay with GCash</p>
                    <div style="margin-top:0.75rem;">
                        <img src="../uploads/payment-icons/Gcash.png" alt="GCash" style="height:36px;object-fit:contain;">
                    </div>
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

                <!-- QR Code Section -->
                <div class="form-section qr-section">
                    <h3><i class="fas fa-qrcode"></i> Scan QR Code</h3>
                    
                    <div class="qr-container">
                        <div class="qr-code">
                            <!-- Generate QR Code using API -->
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?php 
                                $qrData = urlencode('GCash Payment - Paradise Hotel & Resort|Amount: ₱' . number_format($reservation['payment_amount'], 2) . '|Ref: PAY-GCA-' . date('Ymd') . '-' . str_pad($reservationId, 6, '0', STR_PAD_LEFT) . '|Mobile: 09171234567');
                                echo $qrData;
                            ?>" alt="GCash QR Code">
                        </div>
                        <div class="qr-info">
                            <p><strong>GCash Number:</strong> 0917 123 4567</p>
                            <p><strong>Account Name:</strong> Paradise Hotel & Resort</p>
                            <p><strong>Reference Number:</strong> PAY-GCA-<?php echo date('Ymd') . '-' . str_pad($reservationId, 6, '0', STR_PAD_LEFT); ?></p>
                        </div>
                    </div>

                    <div class="payment-instructions">
                        <h4><i class="fas fa-info-circle"></i> How to Pay:</h4>
                        <ol>
                            <li>Open your GCash app</li>
                            <li>Tap "Scan QR" or "Send Money"</li>
                            <li>Scan the QR code above or enter the GCash number</li>
                            <li>Enter the exact amount: <strong>₱<?php echo number_format($reservation['payment_amount'], 2); ?></strong></li>
                            <li>Add the reference number in the message</li>
                            <li>Complete the payment</li>
                            <li>Take a screenshot of the confirmation</li>
                            <li>Click "I've Paid" button below</li>
                        </ol>
                    </div>
                </div>

                <!-- Upload Proof of Payment -->
                <form action="process_gcash_payment.php" method="POST" enctype="multipart/form-data">
                    <?php if ($isPavilion ?? false): ?><input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>"><?php else: ?><input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>"><?php endif; ?>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-upload"></i> Upload Proof of Payment</h3>
                        
                        <div class="form-group">
                            <label for="proof_of_payment">Screenshot of GCash Receipt *</label>
                            <input type="file" id="proof_of_payment" name="proof_of_payment" 
                                   accept="image/*" required>
                            <small style="color: #666;">Upload a clear screenshot of your GCash payment confirmation</small>
                        </div>

                        <div class="form-group">
                            <label for="gcash_reference">GCash Reference Number *</label>
                            <input type="text" id="gcash_reference" name="gcash_reference" 
                                   placeholder="Enter GCash reference number" required>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-check"></i> I've Paid - Submit Proof
                        </button>
                    </div>
                </form>

                <!-- Security Notice -->
                <div class="security-notice">
                    <i class="fas fa-shield-alt"></i>
                    <p>Your payment will be verified within 24 hours. You will receive a confirmation email once verified.</p>
                </div>
            </div>
        </div>
    </div>

    
    <script src="js/payment.js"></script>`n</body>
</html>





