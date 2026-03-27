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

// Room reservations
$stmt = $conn->prepare("SELECT *, 'room' AS booking_type FROM reservations WHERE user_id = ? AND status != 'cancelled' ORDER BY checkin_date DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($booking = $result->fetch_assoc()) {
    // Parse room name from options JSON
    $opts = json_decode($booking['options'] ?? '', true);
    $roomName = $opts['individual_room']['room_name'] ?? $opts['individual_room']['room_type'] ?? $booking['room_type'] ?? 'Room';
    $roomNumber = $opts['individual_room']['room_number'] ?? '';
    $booking['display_name'] = $roomName . ($roomNumber ? ' (Room ' . $roomNumber . ')' : '');
    $booking['display_icon'] = 'fa-bed';
    $booking['display_date'] = date('M j, Y', strtotime($booking['checkin_date'])) . ' - ' . date('M j, Y', strtotime($booking['checkout_date']));
    $booking['display_guests'] = $booking['guests'] . ' Guests';
    $booking['view_url'] = 'confirmation.php?reservation_id=' . $booking['id'];
    $booking['cancel_id'] = $booking['id'];

    if ($booking['checkin_date'] >= $today) {
        $upcomingBookings[] = $booking;
    } else {
        $pastBookings[] = $booking;
    }
}
$stmt->close();

// Pavilion bookings
$stmt = $conn->prepare("SELECT *, 'pavilion' AS booking_type FROM pavilion_bookings WHERE user_id = ? AND status != 'cancelled' ORDER BY event_date DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($booking = $result->fetch_assoc()) {
    $booking['display_name'] = 'Pavilion - ' . ($booking['event_type'] ?? 'Event');
    $booking['display_icon'] = 'fa-archway';
    $booking['display_date'] = date('M j, Y', strtotime($booking['event_date'])) . ($booking['event_time'] ? ' at ' . $booking['event_time'] : '');
    $booking['display_guests'] = ($booking['pax'] ?? 0) . ' Guests';
    $booking['view_url'] = 'confirmation.php?booking_id=' . $booking['id'];
    $booking['cancel_id'] = null;
    // Use event_date as checkin_date equivalent
    $booking['checkin_date'] = $booking['event_date'];
    $booking['price'] = $booking['price'] ?? 0;
    $booking['payment_reference'] = $booking['payment_reference'] ?? '';

    if ($booking['event_date'] >= $today) {
        $upcomingBookings[] = $booking;
    } else {
        $pastBookings[] = $booking;
    }
}
$stmt->close();

// Sort both arrays by date descending
usort($upcomingBookings, fn($a,$b) => strcmp($b['checkin_date'], $a['checkin_date']));
usort($pastBookings,    fn($a,$b) => strcmp($b['checkin_date'], $a['checkin_date']));

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
    <link rel="stylesheet" href="assets/css/profile.css">
</head>
<body class="profile-page">

<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">
            <img src="uploads/logo/logo.png" alt="Logo" class="nav-logo-img" onerror="this.style.display='none'">
            <span>Paradise Hotel & Resort</span>
        </a>
        <div class="nav-menu">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
            <a href="booking.php" class="nav-link book-now"><i class="fas fa-calendar-check"></i> Book Now</a>
        </div>
    </div>
</nav>

<div class="prof-wrap">

    <!-- Sidebar -->
    <aside class="prof-sidebar">
        <div class="prof-avatar" id="profAvatarWrap" onclick="document.getElementById('photoInput').click()" title="Click to change photo" style="cursor:pointer;position:relative;overflow:visible;">
            <?php if (!empty($user['profile_photo']) && file_exists($user['profile_photo'])): ?>
                <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" id="profAvatarImg"
                     style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">
            <?php else: ?>
                <span id="profAvatarInitial"><?php echo strtoupper(substr(getFirstName() ?? 'U', 0, 1)); ?></span>
            <?php endif; ?>
            <div style="position:absolute;bottom:2px;right:2px;width:24px;height:24px;background:#C9A961;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid #1a2a4a;pointer-events:none;">
                <i class="fas fa-camera" style="font-size:0.6rem;color:#fff;"></i>
            </div>
        </div>
        <input type="file" id="photoInput" accept="image/*" style="display:none;" onchange="uploadPhoto(this)">
        <div class="prof-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
        <div class="prof-username">@<?php echo htmlspecialchars($user['username']); ?></div>
        <div class="prof-since">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></div>

        <div class="prof-meta">
            <div class="prof-meta-item"><i class="fas fa-envelope"></i><?php echo htmlspecialchars($user['email']); ?></div>
            <?php if (!empty($user['phone'])): ?>
            <div class="prof-meta-item"><i class="fas fa-phone"></i><?php echo htmlspecialchars($user['phone']); ?></div>
            <?php endif; ?>
        </div>

        <div class="prof-sidebar-nav">
            <button class="prof-nav-btn active" onclick="showTab('edit')"><i class="fas fa-user-edit"></i> Edit Profile</button>
            <button class="prof-nav-btn" onclick="showTab('bookings')">
                <i class="fas fa-calendar-check"></i> My Bookings
                <?php $total = count($upcomingBookings) + count($pastBookings); if ($total > 0): ?>
                <span class="prof-badge"><?php echo $total; ?></span>
                <?php endif; ?>
            </button>
        </div>

        <a href="logout.php" class="prof-logout" onclick="return confirm('Are you sure you want to log out?');">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>

    <!-- Main -->
    <main class="prof-main">

        <?php if ($message): ?>
        <div class="prof-alert prof-alert-<?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Edit Profile Tab -->
        <div id="tab-edit" class="prof-tab active">
            <div class="prof-card">
                <div class="prof-card-title"><i class="fas fa-user"></i> Personal Information</div>
                <form method="POST" action="">
                    <div class="prof-form-grid">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" required value="<?php echo htmlspecialchars($user['full_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small>Username cannot be changed</small>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group prof-full">
                            <label>Address</label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="prof-card-title" style="margin-top:2rem;"><i class="fas fa-lock"></i> Change Password</div>
                    <p style="color:#888;font-size:0.88rem;margin-bottom:1rem;">Leave blank to keep your current password.</p>
                    <div class="prof-form-grid">
                        <div class="form-group prof-full">
                            <label>Current Password</label>
                            <input type="password" name="current_password">
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" minlength="6">
                        </div>
                    </div>

                    <div style="margin-top:1.5rem;">
                        <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bookings Tab -->
        <div id="tab-bookings" class="prof-tab">

            <?php if (count($upcomingBookings) > 0): ?>
            <div class="prof-card">
                <div class="prof-card-title"><i class="fas fa-calendar-check"></i> Upcoming Bookings</div>
                <div class="bk-list">
                    <?php foreach ($upcomingBookings as $b):
                        $status = $b['status'] ?? $b['payment_status'] ?? 'pending';
                    ?>
                    <div class="bk-item">
                        <div class="bk-top">
                            <div class="bk-name">
                                <i class="fas <?php echo $b['display_icon']; ?>"></i>
                                <?php echo htmlspecialchars($b['display_name']); ?>
                            </div>
                            <span class="bk-status status-<?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                        </div>
                        <div class="bk-meta">
                            <span><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($b['display_date']); ?></span>
                            <span><i class="fas fa-users"></i> <?php echo htmlspecialchars($b['display_guests']); ?></span>
                            <span><i class="fas fa-peso-sign"></i> ₱<?php echo number_format($b['price'], 2); ?></span>
                            <?php if (!empty($b['payment_reference'])): ?>
                            <span><i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($b['payment_reference']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="bk-actions">
                            <a href="<?php echo $b['view_url']; ?>" class="bk-btn bk-btn-view"><i class="fas fa-eye"></i> View</a>
                            <?php if ($b['cancel_id']): ?>
                            <button onclick="cancelBooking(<?php echo $b['cancel_id']; ?>)" class="bk-btn bk-btn-cancel"><i class="fas fa-times"></i> Cancel</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (count($pastBookings) > 0): ?>
            <div class="prof-card" style="margin-top:1.5rem;">
                <div class="prof-card-title"><i class="fas fa-history"></i> Booking History</div>
                <div class="bk-list">
                    <?php foreach (array_slice($pastBookings, 0, 10) as $b):
                        $status = $b['status'] ?? $b['payment_status'] ?? 'pending';
                    ?>
                    <div class="bk-item bk-past">
                        <div class="bk-top">
                            <div class="bk-name">
                                <i class="fas <?php echo $b['display_icon']; ?>"></i>
                                <?php echo htmlspecialchars($b['display_name']); ?>
                            </div>
                            <span class="bk-status status-<?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                        </div>
                        <div class="bk-meta">
                            <span><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($b['display_date']); ?></span>
                            <span><i class="fas fa-users"></i> <?php echo htmlspecialchars($b['display_guests']); ?></span>
                            <span><i class="fas fa-peso-sign"></i> ₱<?php echo number_format($b['price'], 2); ?></span>
                        </div>
                        <div class="bk-actions">
                            <a href="<?php echo $b['view_url']; ?>" class="bk-btn bk-btn-view"><i class="fas fa-eye"></i> View</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (count($upcomingBookings) === 0 && count($pastBookings) === 0): ?>
            <div class="prof-card">
                <div style="text-align:center;padding:3rem 1rem;color:#888;">
                    <i class="fas fa-calendar-times" style="font-size:3rem;color:#C9A961;opacity:0.5;display:block;margin-bottom:1rem;"></i>
                    <h3 style="color:#2C3E50;margin-bottom:0.5rem;">No Bookings Yet</h3>
                    <p>You have not made any reservations yet.</p>
                    <a href="booking.php" class="btn-submit" style="margin-top:1.25rem;display:inline-flex;"><i class="fas fa-plus"></i> Make a Reservation</a>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /tab-bookings -->

    </main>
</div><!-- /prof-wrap -->

<script>
function showTab(tab) {
    document.querySelectorAll('.prof-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.prof-nav-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
}

function uploadPhoto(input) {
    if (!input.files || !input.files[0]) return;
    const fd = new FormData();
    fd.append('photo', input.files[0]);
    const wrap = document.getElementById('profAvatarWrap');
    wrap.style.opacity = '0.5';
    fetch('upload_profile_photo.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            wrap.style.opacity = '1';
            if (data.success) {
                // Replace initial with image
                const initial = document.getElementById('profAvatarInitial');
                let img = document.getElementById('profAvatarImg');
                if (!img) {
                    img = document.createElement('img');
                    img.id = 'profAvatarImg';
                    img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;';
                    if (initial) initial.replaceWith(img);
                    else wrap.insertBefore(img, wrap.firstChild);
                }
                img.src = data.path + '?t=' + Date.now();
            } else {
                alert(data.message || 'Upload failed.');
            }
        })
        .catch(() => { wrap.style.opacity = '1'; alert('Upload failed. Please try again.'); });
}

function cancelBooking(reservationId) {
    if (!confirm('Are you sure you want to cancel this booking?')) return;
    const btn = event.target.closest('.bk-btn-cancel');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    fetch('cancel_booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'reservation_id=' + reservationId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { location.reload(); }
        else { alert('Error: ' + data.message); btn.disabled = false; btn.innerHTML = '<i class="fas fa-times"></i> Cancel'; }
    })
    .catch(() => { alert('Error. Please try again.'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-times"></i> Cancel'; });
}
</script>
</body>
</html>
