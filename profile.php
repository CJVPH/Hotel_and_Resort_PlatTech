<?php
require_once 'config/database.php';
require_once 'config/auth.php';

// Require user to be logged in
requireLogin();

$userId = getUserId();
$message = '';
$messageType = '';

// Fetch user data
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Fetch user's bookings
$today = date('Y-m-d');
$upcomingBookings = [];
$pastBookings = [];

$stmt = $conn->prepare("SELECT * FROM reservations WHERE user_id = ? ORDER BY checkin_date DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($booking = $result->fetch_assoc()) {
    // Skip cancelled bookings
    if ($booking['payment_status'] === 'cancelled') {
        continue;
    }
    
    if ($booking['checkin_date'] >= $today) {
        $upcomingBookings[] = $booking;
    } else {
        $pastBookings[] = $booking;
    }
}
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($fullName) || empty($email)) {
        $message = 'Full name and email are required.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format.';
        $messageType = 'error';
    } else {
        // Check if email is already taken by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = 'Email is already taken by another user.';
            $messageType = 'error';
            $stmt->close();
        } else {
            $stmt->close();
            
            // Update profile
            if (!empty($newPassword)) {
                // Verify current password
                if (empty($currentPassword)) {
                    $message = 'Current password is required to set a new password.';
                    $messageType = 'error';
                } elseif (!password_verify($currentPassword, $user['password'])) {
                    $message = 'Current password is incorrect.';
                    $messageType = 'error';
                } elseif ($newPassword !== $confirmPassword) {
                    $message = 'New passwords do not match.';
                    $messageType = 'error';
                } elseif (strlen($newPassword) < 6) {
                    $message = 'New password must be at least 6 characters.';
                    $messageType = 'error';
                } else {
                    // Update with new password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, password = ? WHERE id = ?");
                    $stmt->bind_param("sssssi", $fullName, $email, $phone, $address, $hashedPassword, $userId);
                    
                    if ($stmt->execute()) {
                        $_SESSION['full_name'] = $fullName;
                        $user['full_name'] = $fullName;
                        $user['email'] = $email;
                        $user['phone'] = $phone;
                        $user['address'] = $address;
                        $message = 'Profile and password updated successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Error updating profile.';
                        $messageType = 'error';
                    }
                    $stmt->close();
                }
            } else {
                // Update without password change
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $fullName, $email, $phone, $address, $userId);
                
                if ($stmt->execute()) {
                    $_SESSION['full_name'] = $fullName;
                    $user['full_name'] = $fullName;
                    $user['email'] = $email;
                    $user['phone'] = $phone;
                    $user['address'] = $address;
                    $message = 'Profile updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error updating profile.';
                    $messageType = 'error';
                }
                $stmt->close();
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Paradise Hotel & Resort</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/booking.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-page {
            background: linear-gradient(135deg, #2C3E50 0%, #34495E 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .profile-header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
        }
        
        .profile-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .profile-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .profile-avatar {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .avatar-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: 700;
            box-shadow: 0 10px 30px rgba(201, 169, 97, 0.3);
        }
        
        .section-divider {
            border: none;
            border-top: 2px solid #f0f0f0;
            margin: 2rem 0;
        }
        
        .section-title {
            color: #2C3E50;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title i {
            color: #C9A961;
        }
        
        .back-link-profile {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }
        
        .back-link-profile:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
        }
    </style>
</head>
<body class="profile-page">
    <div class="profile-container">
        <a href="index.php" class="back-link-profile">
            <i class="fas fa-arrow-left"></i>
            Back to Home
        </a>
        
        <div class="profile-header">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            <p>Manage your account information</p>
            <div style="margin-top: 1rem;">
                <a href="logout.php" class="btn-submit" style="display: inline-block; background: #e74c3c; text-decoration: none; color: white; padding: 0.6rem 1.5rem; border-radius: 5px;" onclick="return confirm('Are you sure you are going to log out?');">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <div class="profile-card">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="profile-avatar">
                <div class="avatar-circle">
                    <?php echo strtoupper(substr(getFirstName() ?? 'U', 0, 1)); ?>
                </div>
                <h2 style="margin-top: 1rem; color: #2C3E50;"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                <p style="color: #666;">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
            </div>
            
            <form method="POST" action="">
                <div class="section-title">
                    <i class="fas fa-user"></i>
                    Personal Information
                </div>
                
                <div class="form-section">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" required 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small style="color: #666; font-size: 0.85rem;">Username cannot be changed</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" 
                                   value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <hr class="section-divider">
                
                <div class="section-title">
                    <i class="fas fa-lock"></i>
                    Change Password
                </div>
                
                <div class="form-section">
                    <p style="color: #666; margin-bottom: 1rem;">Leave password fields empty if you don't want to change your password.</p>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" minlength="6">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Upcoming Bookings -->
        <?php if (count($upcomingBookings) > 0): ?>
        <div class="profile-card" style="margin-top: 2rem;">
            <div class="section-title">
                <i class="fas fa-calendar-check"></i>
                Upcoming Bookings
            </div>
            
            <div class="bookings-list">
                <?php foreach ($upcomingBookings as $booking): ?>
                <div class="booking-item">
                    <div class="booking-header-item">
                        <div class="booking-room">
                            <i class="fas fa-bed"></i>
                            <strong><?php echo htmlspecialchars($booking['room_type']); ?></strong>
                        </div>
                        <div class="booking-status status-<?php echo $booking['payment_status']; ?>">
                            <?php echo ucfirst($booking['payment_status']); ?>
                        </div>
                    </div>
                    <div class="booking-details-grid">
                        <div class="booking-detail">
                            <i class="fas fa-calendar"></i>
                            <span><?php echo date('M j, Y', strtotime($booking['checkin_date'])); ?> - <?php echo date('M j, Y', strtotime($booking['checkout_date'])); ?></span>
                        </div>
                        <div class="booking-detail">
                            <i class="fas fa-users"></i>
                            <span><?php echo $booking['guests']; ?> Guests</span>
                        </div>
                        <div class="booking-detail">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>₱<?php echo number_format($booking['price'], 2); ?></span>
                        </div>
                        <div class="booking-detail">
                            <i class="fas fa-hashtag"></i>
                            <span>Ref: <?php echo htmlspecialchars($booking['payment_reference']); ?></span>
                        </div>
                    </div>
                    <div class="booking-actions">
                        <a href="confirmation.php?reservation_id=<?php echo $booking['id']; ?>" class="btn-view">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <button onclick="cancelBooking(<?php echo $booking['id']; ?>)" class="btn-cancel">
                            <i class="fas fa-times-circle"></i> Cancel Booking
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Past Bookings -->
        <?php if (count($pastBookings) > 0): ?>
        <div class="profile-card" style="margin-top: 2rem;">
            <div class="section-title">
                <i class="fas fa-history"></i>
                Booking History
            </div>
            
            <div class="bookings-list">
                <?php foreach (array_slice($pastBookings, 0, 5) as $booking): ?>
                <div class="booking-item past-booking">
                    <div class="booking-header-item">
                        <div class="booking-room">
                            <i class="fas fa-bed"></i>
                            <strong><?php echo htmlspecialchars($booking['room_type']); ?></strong>
                        </div>
                        <div class="booking-status status-completed">
                            Completed
                        </div>
                    </div>
                    <div class="booking-details-grid">
                        <div class="booking-detail">
                            <i class="fas fa-calendar"></i>
                            <span><?php echo date('M j, Y', strtotime($booking['checkin_date'])); ?> - <?php echo date('M j, Y', strtotime($booking['checkout_date'])); ?></span>
                        </div>
                        <div class="booking-detail">
                            <i class="fas fa-users"></i>
                            <span><?php echo $booking['guests']; ?> Guests</span>
                        </div>
                        <div class="booking-detail">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>₱<?php echo number_format($booking['price'], 2); ?></span>
                        </div>
                        <div class="booking-detail">
                            <i class="fas fa-hashtag"></i>
                            <span>Ref: <?php echo htmlspecialchars($booking['payment_reference']); ?></span>
                        </div>
                    </div>
                    <div class="booking-actions">
                        <a href="confirmation.php?reservation_id=<?php echo $booking['id']; ?>" class="btn-view">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (count($pastBookings) > 5): ?>
                <p style="text-align: center; color: #666; margin-top: 1rem;">
                    Showing 5 most recent bookings
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (count($upcomingBookings) === 0 && count($pastBookings) === 0): ?>
        <div class="profile-card" style="margin-top: 2rem;">
            <div class="no-bookings">
                <i class="fas fa-calendar-times"></i>
                <h3>No Bookings Yet</h3>
                <p>You haven't made any reservations yet.</p>
                <a href="booking.php" class="btn-submit" style="margin-top: 1rem;">
                    <i class="fas fa-plus"></i> Make a Reservation
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <style>
        .bookings-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .booking-item {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .booking-item:hover {
            border-color: #C9A961;
            box-shadow: 0 5px 15px rgba(201, 169, 97, 0.2);
            transform: translateY(-2px);
        }

        .past-booking {
            opacity: 0.85;
        }

        .booking-header-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .booking-room {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #2C3E50;
            font-size: 1.1rem;
        }

        .booking-room i {
            color: #C9A961;
        }

        .booking-status {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .booking-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .booking-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #555;
        }

        .booking-detail i {
            color: #C9A961;
            min-width: 20px;
        }

        .booking-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-view {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.5rem;
            background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(201, 169, 97, 0.3);
        }

        .btn-cancel {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.5rem;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .no-bookings {
            text-align: center;
            padding: 3rem 2rem;
            color: #666;
        }

        .no-bookings i {
            font-size: 4rem;
            color: #C9A961;
            margin-bottom: 1rem;
        }

        .no-bookings h3 {
            color: #2C3E50;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .booking-header-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .booking-details-grid {
                grid-template-columns: 1fr;
            }

            .booking-actions {
                justify-content: center;
            }
        }
    </style>

    <script>
    function cancelBooking(reservationId) {
        if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
            // Show loading state
            const btn = event.target.closest('.btn-cancel');
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
            
            // Send cancellation request
            fetch('cancel_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'reservation_id=' + reservationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Booking cancelled successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            })
            .catch(error => {
                alert('Error cancelling booking. Please try again.');
                btn.disabled = false;
                btn.innerHTML = originalContent;
            });
        }
    }
    </script>
</body>
</html>
