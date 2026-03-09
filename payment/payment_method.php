<?php
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
    error_log("Payment method page error: " . $e->getMessage());
    header('Location: ../booking.php?error=Database error');
    exit();
}

$paymentAmount = $reservation['payment_amount'];
$paymentPercentage = $reservation['payment_percentage'];
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
    <!-- Header -->
    <header class="booking-header">
        <div class="header-container">
            <div class="header-left">
                <a href="payment.php?reservation_id=<?php echo $reservationId; ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Payment</span>
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

    <div class="payment-container">
        <div class="payment-form-section">
            <div class="booking-card">
                <div class="booking-header">
                    <h1><i class="fas fa-credit-card"></i> Payment Method</h1>
                    <p>Choose how you'd like to pay</p>
                </div>

                <!-- Payment Summary -->
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

                <!-- Payment Methods -->
                <div class="form-section">
                    <h3><i class="fas fa-money-bill-wave"></i> Select Payment Method</h3>
                    <div class="payment-methods">
                        <!-- Credit/Debit Card -->
                        <div class="payment-method-card">
                            <div class="method-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="method-info">
                                <h4>Credit/Debit Card</h4>
                                <p>Visa, Mastercard, American Express</p>
                            </div>
                            <form action="complete_payment.php" method="POST">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>">
                                <input type="hidden" name="payment_method" value="credit_card">
                                <button type="submit" class="btn-method">
                                    <i class="fas fa-arrow-right"></i> Pay with Card
                                </button>
                            </form>
                        </div>

                        <!-- PayPal -->
                        <div class="payment-method-card">
                            <div class="method-icon">
                                <i class="fab fa-paypal"></i>
                            </div>
                            <div class="method-info">
                                <h4>PayPal</h4>
                                <p>Pay securely with your PayPal account</p>
                            </div>
                            <form action="complete_payment.php" method="POST">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>">
                                <input type="hidden" name="payment_method" value="paypal">
                                <button type="submit" class="btn-method">
                                    <i class="fas fa-arrow-right"></i> Pay with PayPal
                                </button>
                            </form>
                        </div>

                        <!-- GCash -->
                        <div class="payment-method-card">
                            <div class="method-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="method-info">
                                <h4>GCash</h4>
                                <p>Pay using your GCash mobile wallet</p>
                            </div>
                            <form action="complete_payment.php" method="POST">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>">
                                <input type="hidden" name="payment_method" value="gcash">
                                <button type="submit" class="btn-method">
                                    <i class="fas fa-arrow-right"></i> Pay with GCash
                                </button>
                            </form>
                        </div>

                        <!-- Bank Transfer -->
                        <div class="payment-method-card">
                            <div class="method-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="method-info">
                                <h4>Bank Transfer</h4>
                                <p>Direct bank transfer or online banking</p>
                            </div>
                            <form action="complete_payment.php" method="POST">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>">
                                <input type="hidden" name="payment_method" value="bank_transfer">
                                <button type="submit" class="btn-method">
                                    <i class="fas fa-arrow-right"></i> Bank Transfer
                                </button>
                            </form>
                        </div>

                        <!-- Cash Payment -->
                        <div class="payment-method-card">
                            <div class="method-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="method-info">
                                <h4>Cash Payment</h4>
                                <p>Pay in cash at our front desk</p>
                            </div>
                            <form action="complete_payment.php" method="POST">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>">
                                <input type="hidden" name="payment_method" value="cash">
                                <button type="submit" class="btn-method">
                                    <i class="fas fa-arrow-right"></i> Pay in Cash
                                </button>
                            </form>
                        </div>

                        <!-- Over the Counter -->
                        <div class="payment-method-card">
                            <div class="method-icon">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="method-info">
                                <h4>Over the Counter</h4>
                                <p>Pay at 7-Eleven, SM, or other partner stores</p>
                            </div>
                            <form action="complete_payment.php" method="POST">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>">
                                <input type="hidden" name="payment_method" value="otc">
                                <button type="submit" class="btn-method">
                                    <i class="fas fa-arrow-right"></i> Pay Over Counter
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* Payment Container Centering */
    .payment-container {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: calc(100vh - 120px);
        padding: 2rem;
    }

    .payment-form-section {
        width: 100%;
        max-width: 1000px;
        margin: 0 auto;
    }

    .payment-summary {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        border: 1px solid #e0e0e0;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .summary-row:last-child {
        border-bottom: none;
    }

    .summary-row.total {
        font-size: 1.2rem;
        font-weight: 700;
        color: #2C3E50;
        border-top: 2px solid #C9A961;
        margin-top: 0.5rem;
        padding-top: 1rem;
    }

    .payment-methods {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1rem;
    }

    .payment-method-card {
        background: white;
        border: 2px solid #e0e0e0;
        border-radius: 15px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.3s ease;
    }

    .payment-method-card:hover {
        border-color: #C9A961;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .method-icon {
        font-size: 2rem;
        color: #C9A961;
        min-width: 60px;
        text-align: center;
    }

    .method-info {
        flex: 1;
    }

    .method-info h4 {
        color: #2C3E50;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .method-info p {
        color: #666;
        font-size: 0.9rem;
    }

    .btn-method {
        background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 25px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
    }

    .btn-method:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(201, 169, 97, 0.3);
    }

    @media (max-width: 768px) {
        .payment-container {
            padding: 1rem;
            min-height: calc(100vh - 100px);
        }

        .payment-form-section {
            max-width: 100%;
        }

        .payment-methods {
            grid-template-columns: 1fr;
        }
        
        .payment-method-card {
            flex-direction: column;
            text-align: center;
        }
        
        .method-info {
            text-align: center;
        }
    }
    </style>
</body>
</html>
