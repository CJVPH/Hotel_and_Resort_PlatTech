<?php
require_once '../config/database.php';
require_once '../config/auth.php';

requireLogin();

// Support both room reservations and pavilion bookings
$reservationId = intval($_GET['reservation_id'] ?? 0);
$bookingId     = intval($_GET['booking_id'] ?? 0);
$isPavilion    = $bookingId > 0;

$userId = getUserId();

try {
    $conn = getDBConnection();

    if ($isPavilion) {
        $stmt = $conn->prepare("SELECT * FROM pavilion_bookings WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $bookingId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) { header('Location: ../booking.php?error=Booking not found'); exit(); }
        $row = $result->fetch_assoc();
        $paymentAmount     = $row['payment_amount'];
        $paymentPercentage = $row['payment_percentage'];
        $backUrl = 'pavilion_payment.php?booking_id=' . $bookingId;
    } else {
        if ($reservationId <= 0) { header('Location: ../booking.php?error=Invalid reservation'); exit(); }
        $stmt = $conn->prepare("SELECT * FROM reservations WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $reservationId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) { header('Location: ../booking.php?error=Reservation not found'); exit(); }
        $row = $result->fetch_assoc();
        $paymentAmount     = $row['payment_amount'];
        $paymentPercentage = $row['payment_percentage'];
        $backUrl = 'payment.php?reservation_id=' . $reservationId;
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Payment method page error: " . $e->getMessage());
    header('Location: ../booking.php?error=Database error'); exit();
}

// Hidden field values passed to complete_payment.php
$hiddenReservation = $isPavilion ? '' : $reservationId;
$hiddenBooking     = $isPavilion ? $bookingId : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Method - Paradise Hotel & Resort</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/booking.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="booking-page">
    <header class="booking-header">
        <div class="header-container">
            <div class="header-left">
                <a href="<?php echo $backUrl; ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i><span>Back</span>
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
                    <h1><i class="fas fa-credit-card"></i> Payment Method</h1>
                    <p>Choose how you'd like to pay</p>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-receipt"></i> Payment Summary</h3>
                    <div class="payment-summary">
                        <div class="summary-row">
                            <span>Payment Type:</span>
                            <span><?php echo $paymentPercentage; ?>% Payment</span>
                        </div>
                        <div class="summary-row total">
                            <span>Amount to Pay:</span>
                            <span>₱<?php echo number_format($paymentAmount, 2); ?></span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-money-bill-wave"></i> Select Payment Method</h3>
                    <div class="payment-methods">
                        <?php
                        $methods = [
                            ['value'=>'credit_card',  'icon'=>'fas fa-credit-card', 'label'=>'Credit/Debit Card',  'desc'=>'Visa, Mastercard, American Express'],
                            ['value'=>'paypal',        'icon'=>'fab fa-paypal',      'label'=>'PayPal',             'desc'=>'Pay securely with your PayPal account'],
                            ['value'=>'gcash',         'icon'=>'fas fa-mobile-alt',  'label'=>'GCash',              'desc'=>'Pay using your GCash mobile wallet'],
                            ['value'=>'bank_transfer', 'icon'=>'fas fa-university',  'label'=>'Bank Transfer',      'desc'=>'Direct bank transfer or online banking'],
                            ['value'=>'cash',          'icon'=>'fas fa-money-bill-wave','label'=>'Cash Payment',    'desc'=>'Pay in cash at our front desk'],
                            ['value'=>'otc',           'icon'=>'fas fa-store',       'label'=>'Over the Counter',   'desc'=>'Pay at 7-Eleven, SM, or other partner stores'],
                        ];
                        foreach ($methods as $m): ?>
                        <div class="payment-method-card">
                            <div class="method-icon"><i class="<?php echo $m['icon']; ?>"></i></div>
                            <div class="method-info">
                                <h4><?php echo $m['label']; ?></h4>
                                <p><?php echo $m['desc']; ?></p>
                            </div>
                            <form action="complete_payment.php" method="POST">
                                <?php if ($isPavilion): ?>
                                    <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">
                                <?php else: ?>
                                    <input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>">
                                <?php endif; ?>
                                <input type="hidden" name="payment_method" value="<?php echo $m['value']; ?>">
                                <button type="submit" class="btn-method">
                                    <i class="fas fa-arrow-right"></i> Pay
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .payment-container { display:flex; justify-content:center; align-items:flex-start; min-height:calc(100vh - 120px); padding:2rem; }
    .payment-form-section { width:100%; max-width:1000px; margin:0 auto; }
    .payment-summary { background:white; padding:1.5rem; border-radius:10px; border:1px solid #e0e0e0; }
    .summary-row { display:flex; justify-content:space-between; align-items:center; padding:.75rem 0; border-bottom:1px solid #f0f0f0; }
    .summary-row:last-child { border-bottom:none; }
    .summary-row.total { font-size:1.2rem; font-weight:700; color:#2C3E50; border-top:2px solid #C9A961; margin-top:.5rem; padding-top:1rem; }
    .payment-methods { display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:1rem; }
    .payment-method-card { background:white; border:2px solid #e0e0e0; border-radius:15px; padding:1.5rem; display:flex; align-items:center; gap:1rem; transition:all .3s ease; }
    .payment-method-card:hover { border-color:#C9A961; transform:translateY(-2px); box-shadow:0 5px 15px rgba(0,0,0,.1); }
    .method-icon { font-size:2rem; color:#C9A961; min-width:60px; text-align:center; }
    .method-info { flex:1; }
    .method-info h4 { color:#2C3E50; font-weight:700; margin-bottom:.25rem; }
    .method-info p { color:#666; font-size:.9rem; }
    .btn-method { background:linear-gradient(135deg,#C9A961 0%,#8B7355 100%); color:white; border:none; padding:.75rem 1.5rem; border-radius:25px; font-weight:600; cursor:pointer; transition:all .3s ease; display:flex; align-items:center; gap:.5rem; white-space:nowrap; }
    .btn-method:hover { transform:translateY(-2px); box-shadow:0 5px 15px rgba(201,169,97,.3); }
    @media (max-width:768px) {
        .payment-container { padding:1rem; }
        .payment-methods { grid-template-columns:1fr; }
        .payment-method-card { flex-direction:column; text-align:center; }
        .method-info { text-align:center; }
    }
    </style>
</body>
</html>
