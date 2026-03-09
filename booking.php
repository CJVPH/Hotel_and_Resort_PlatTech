<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'includes/photo_functions.php';

// Get current user's information for auto-fill if logged in
$userInfo = null;
if (isLoggedIn()) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $userInfo = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        // If there's an error, we'll just not auto-fill
        $userInfo = null;
    }
}

// Get photos for room preview
$poolPhotos = getPhotosForSection('pool');
$spaPhotos = getPhotosForSection('spa');
$restaurantPhotos = getPhotosForSection('restaurant');
?>
<!DOCTYPE html>
<html lang="en" id="top">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Stay - Paradise Hotel & Resort</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/booking.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Flatpickr CSS for beautiful date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body class="booking-page">
    <!-- Header -->
    <header class="booking-header">
        <div class="header-container">
            <div class="header-left">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
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
                <?php if (isLoggedIn()): ?>
                    <div class="user-info">
                        <a href="profile.php" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: #2C3E50;">
                            <i class="fas fa-user-circle"></i>
                            <span>Hello, <?php echo htmlspecialchars(getFirstName() ?? getUsername()); ?></span>
                        </a>
                        <a href="logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="auth-links">
                        <a href="login.php" class="auth-link">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="register.php" class="auth-link">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="booking-container">
        <!-- Booking Form -->
        <div class="booking-form-section">
            <div class="booking-card">
                <div class="booking-header">
                    <h1><i class="fas fa-calendar-check"></i> Book Your Stay</h1>
                    <p>Complete your reservation details below</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <form id="bookingForm" action="process.php" method="POST" class="booking-form">
                    <!-- Guest Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Guest Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" required placeholder="Enter your full name" 
                                       value="<?php echo htmlspecialchars($userInfo['full_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required placeholder="Enter your email"
                                       value="<?php echo htmlspecialchars($userInfo['email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" required placeholder="Enter your phone number">
                            </div>
                            <div class="form-group">
                                <label for="guests">Number of Guests *</label>
                                <select id="guests" name="guests" required>
                                    <option value="">Select guests</option>
                                    <option value="2">2 Guests</option>
                                    <option value="8">4-8 Guests</option>
                                    <option value="20">10-20 Guests</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Details -->
                    <div class="form-section">
                        <h3><i class="fas fa-calendar"></i> Booking Details</h3>
                        <div class="form-row date-inputs-row">
                            <div class="form-group">
                                <label for="checkin">Check-in Date *</label>
                                <input type="date" id="checkin" name="checkin" required>
                            </div>
                            <div class="date-separator">
                                <i class="fas fa-arrow-right"></i>
                                <span class="nights-badge" id="nightsBadge" style="display: none;">
                                    <i class="fas fa-moon"></i>
                                    <span id="nightsBadgeCount">0</span> nights
                                </span>
                            </div>
                            <div class="form-group">
                                <label for="checkout">Check-out Date *</label>
                                <input type="date" id="checkout" name="checkout" required>
                            </div>
                        </div>
                        <!-- Hidden field for nights count -->
                        <input type="hidden" id="nights" name="nights">
                    </div>

                    <!-- Room Selection -->
                    <div class="form-section">
                        <h3><i class="fas fa-bed"></i> Select Your Room</h3>
                        <div id="roomSelection" class="room-selection">
                            <p class="room-instruction">Please select number of guests first to see available rooms.</p>
                        </div>
                        <input type="hidden" id="room" name="room" required>
                        <input type="hidden" id="roomData" name="options">
                    </div>

                    <!-- Special Requests -->
                    <div class="form-section">
                        <h3><i class="fas fa-comment"></i> Special Requests</h3>
                        <div class="form-group">
                            <label for="specialRequests">Additional Information</label>
                            <textarea id="specialRequests" name="special_requests" rows="4" placeholder="Any special requests or requirements..."></textarea>
                        </div>
                    </div>

                    <!-- Price Summary -->
                    <div class="form-section">
                        <h3><i class="fas fa-receipt"></i> Price Summary</h3>
                        <div class="price-summary">
                            <div class="price-row">
                                <span>Room Rate:</span>
                                <span id="roomRate">₱0</span>
                            </div>
                            <div class="price-row">
                                <span>Number of Nights:</span>
                                <span id="nightsCount">0</span>
                            </div>
                            <div class="price-row total">
                                <span>Total Amount:</span>
                                <span id="totalAmount">₱0</span>
                            </div>
                            <input type="hidden" id="price" name="price" value="0">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-actions">
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-credit-card"></i> Complete Booking
                        </button>
                        <p class="form-note">
                            <i class="fas fa-info-circle"></i>
                            You will be redirected to payment options after clicking Complete Booking
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Room Preview -->
        <div class="room-preview-section">
            <div class="preview-card">
                <h3><i class="fas fa-images"></i> Room Preview</h3>
                <div id="roomPreview" class="room-preview">
                    <div class="preview-placeholder">
                        <i class="fas fa-bed"></i>
                        <p>Select a room to see preview</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/booking.js?v=<?php echo time(); ?>"></script>
    <!-- Flatpickr JS for beautiful date picker -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</body>
</html>