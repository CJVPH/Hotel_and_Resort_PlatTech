<?php
require_once 'config/database.php';
require_once 'config/auth.php';

// Require login
requireLogin();

// Get reservation ID
$reservationId = intval($_GET['reservation_id'] ?? 0);

if ($reservationId <= 0) {
    header('Location: booking.php?error=Invalid reservation');
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
        header('Location: booking.php?error=Reservation not found');
        exit();
    }
    
    $reservation = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Confirmation page error: " . $e->getMessage());
    header('Location: booking.php?error=Database error');
    exit();
}

// Parse options JSON
$options = [];
if (!empty($reservation['options'])) {
    $options = json_decode($reservation['options'], true) ?? [];
}

// Get payment method display name
$paymentMethods = [
    'credit_card' => 'Credit/Debit Card',
    'paypal' => 'PayPal',
    'gcash' => 'GCash',
    'bank_transfer' => 'Bank Transfer',
    'cash' => 'Cash Payment',
    'otc' => 'Over the Counter'
];

$paymentMethodName = $paymentMethods[$reservation['payment_method']] ?? $reservation['payment_method'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - Paradise Hotel & Resort</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/booking.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="booking-page">
    <!-- Header -->
    <header class="booking-header">
        <div class="header-container">
            <div class="header-left">
                <a href="index.php" class="back-link">
                    <i class="fas fa-home"></i>
                    <span>Back to Home</span>
                </a>
            </div>
            <div class="header-center">
                <div class="hotel-logo">
                    <i class="fas fa-hotel"></i>
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

    <div class="confirmation-container">
        <div class="confirmation-form-section">
            <div class="booking-card">
                <!-- Success Header -->
                <div class="confirmation-header">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1>Booking Confirmed!</h1>
                    <p>Thank you for choosing Paradise Hotel & Resort</p>
                    <div class="confirmation-number">
                        <strong>Confirmation #: <?php echo htmlspecialchars($reservation['payment_reference']); ?></strong>
                    </div>
                </div>

                <!-- Reservation Details -->
                <div class="form-section">
                    <h3><i class="fas fa-calendar-check"></i> Reservation Details</h3>
                    <div class="confirmation-details">
                        <div class="detail-row">
                            <span class="detail-label">Guest Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($reservation['guest_name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($reservation['email']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($reservation['phone']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Room:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($reservation['room_type']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Check-in Date:</span>
                            <span class="detail-value"><?php echo date('F j, Y', strtotime($reservation['checkin_date'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Check-out Date:</span>
                            <span class="detail-value"><?php echo date('F j, Y', strtotime($reservation['checkout_date'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Number of Guests:</span>
                            <span class="detail-value"><?php echo $reservation['guests']; ?> guests</span>
                        </div>
                        <?php if (isset($options['individual_room'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Room Number:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($options['individual_room']['room_number']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($options['special_requests']) && !empty($options['special_requests'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Special Requests:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($options['special_requests']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="form-section">
                    <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                    <div class="confirmation-details">
                        <div class="detail-row">
                            <span class="detail-label">Payment Method:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($paymentMethodName); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment Type:</span>
                            <span class="detail-value"><?php echo $reservation['payment_percentage']; ?>% Payment</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Amount Paid:</span>
                            <span class="detail-value">₱<?php echo number_format($reservation['payment_amount'], 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Total Amount:</span>
                            <span class="detail-value">₱<?php echo number_format($reservation['price'], 2); ?></span>
                        </div>
                        <?php if ($reservation['payment_percentage'] == 50): ?>
                        <div class="detail-row">
                            <span class="detail-label">Remaining Balance:</span>
                            <span class="detail-value">₱<?php echo number_format($reservation['price'] - $reservation['payment_amount'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <span class="detail-label">Payment Status:</span>
                            <span class="detail-value status-<?php echo $reservation['payment_status']; ?>">
                                <?php echo ucfirst($reservation['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Important Information -->
                <div class="form-section">
                    <h3><i class="fas fa-info-circle"></i> Important Information</h3>
                    <div class="important-info">
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Check-in Time:</strong> 3:00 PM<br>
                                <strong>Check-out Time:</strong> 12:00 PM
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-utensils"></i>
                            <div>
                                <strong>Complimentary Breakfast:</strong> Included for all guests<br>
                                <strong>Serving Time:</strong> 6:00 AM - 10:00 AM
                            </div>
                        </div>
                        <?php if ($reservation['payment_percentage'] == 50): ?>
                        <div class="info-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <div>
                                <strong>Remaining Balance:</strong> ₱<?php echo number_format($reservation['price'] - $reservation['payment_amount'], 2); ?><br>
                                <strong>Payment Due:</strong> Upon check-in
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <strong>Contact Us:</strong> +1 (555) 123-4567<br>
                                <strong>Email:</strong> reservations@paradisehotel.com
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="form-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print"></i> Print Confirmation
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* Confirmation Container Centering */
    .confirmation-container {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: calc(100vh - 120px);
        padding: 2rem;
    }

    .confirmation-form-section {
        width: 100%;
        max-width: 800px;
        margin: 0 auto;
    }

    .confirmation-header {
        text-align: center;
        padding: 2rem;
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border-radius: 15px;
        margin-bottom: 2rem;
    }

    .success-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: white;
    }

    .confirmation-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .confirmation-header p {
        font-size: 1.2rem;
        opacity: 0.9;
        margin-bottom: 1rem;
    }

    .confirmation-number {
        background: rgba(255, 255, 255, 0.2);
        padding: 1rem;
        border-radius: 10px;
        font-size: 1.1rem;
    }

    .confirmation-details {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        border: 1px solid #e0e0e0;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: 600;
        color: #2C3E50;
        min-width: 150px;
    }

    .detail-value {
        color: #555;
        text-align: right;
        flex: 1;
    }

    .status-completed {
        color: #28a745;
        font-weight: 700;
    }

    .status-pending {
        color: #ffc107;
        font-weight: 700;
    }

    .important-info {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 10px;
        border-left: 4px solid #C9A961;
    }

    .info-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .info-item:last-child {
        margin-bottom: 0;
    }

    .info-item i {
        color: #C9A961;
        font-size: 1.2rem;
        margin-top: 0.2rem;
        min-width: 20px;
    }

    .info-item div {
        color: #555;
        line-height: 1.6;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
    }

    @media (max-width: 768px) {
        .confirmation-container {
            padding: 1rem;
            min-height: calc(100vh - 100px);
        }

        .confirmation-form-section {
            max-width: 100%;
        }

        .confirmation-header h1 {
            font-size: 2rem;
        }
        
        .detail-row {
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .detail-label {
            min-width: auto;
        }
        
        .detail-value {
            text-align: left;
        }
        
        .form-actions {
            flex-direction: column;
        }
    }

    @media print {
        .booking-header,
        .form-actions {
            display: none;
        }
        
        .booking-page {
            background: white !important;
        }
        
        .booking-card {
            box-shadow: none !important;
            border: none !important;
        }

        .confirmation-header {
            background: white !important;
            color: #2C3E50 !important;
            border: 2px solid #28a745 !important;
        }

        .success-icon {
            color: #28a745 !important;
        }

        .confirmation-number {
            background: #f8f9fa !important;
            color: #2C3E50 !important;
            border: 1px solid #e0e0e0 !important;
        }

        .confirmation-details {
            border: 1px solid #e0e0e0 !important;
            box-shadow: none !important;
        }

        .important-info {
            border-left: none !important;
            border: 1px solid #e0e0e0 !important;
            box-shadow: none !important;
            background: white !important;
        }

        .form-section {
            box-shadow: none !important;
            border: none !important;
        }

        .confirmation-container {
            padding: 0 !important;
        }
    }
    </style>
</body>
</html>