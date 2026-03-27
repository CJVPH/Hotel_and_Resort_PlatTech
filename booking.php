<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'includes/photo_functions.php';
require_once 'includes/pavilion_pricing.php';

// ── PAVILION BOOKING AJAX HANDLER ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $conn = getDBConnection();
    $action = $_POST['action'] ?? '';
    $inTransaction = false;
    try {
        if ($action === 'book') {
            $eventDate    = trim($_POST['event_date'] ?? '');
            $checkoutDate = $eventDate;
            $guestName    = trim($_POST['guest_name'] ?? '');
            $email        = trim($_POST['email'] ?? '');
            $phone        = trim($_POST['phone'] ?? '');
            $pax          = intval($_POST['pax'] ?? 0);
            $eventType    = trim($_POST['event_type'] ?? '');
            $eventTime    = trim($_POST['event_time'] ?? '');
            $eventEndTime = trim($_POST['event_end_time'] ?? '');
            $buffetItems  = trim($_POST['buffet_items'] ?? '');
            $specialReq   = trim($_POST['special_requests'] ?? '');

            if (!$eventDate || !$guestName || !$email || $pax <= 0)
                throw new Exception('Please fill in all required fields.');
            if (strtotime($eventDate) < strtotime('today'))
                throw new Exception('Please select a future date.');

            $conn->begin_transaction(); $inTransaction = true;

            // Check not blocked by admin
            $chk = $conn->prepare("SELECT id FROM pavilion_slots WHERE event_date=? AND status='blocked' LIMIT 1");
            $chk->bind_param('s', $eventDate); $chk->execute();
            if ($chk->get_result()->num_rows > 0) throw new Exception('This date is not available. Please choose another.');
            $chk->close();

            // Check not already booked
            $chk2 = $conn->prepare("SELECT id FROM pavilion_bookings WHERE event_date=? AND status IN ('confirmed','pending') LIMIT 1");
            $chk2->bind_param('s', $eventDate); $chk2->execute();
            if ($chk2->get_result()->num_rows > 0) throw new Exception('This date is already booked. Please choose another.');
            $chk2->close();

            // Calculate dynamic price
            $pricing = calculatePavilionPrice($eventType, $pax, $conn);

            // Calculate buffet add-on total (per pax)
            $buffetTotal = 0.00;
            if ($buffetItems) {
                foreach (explode(',', $buffetItems) as $bid) {
                    $bid = intval(trim($bid));
                    if ($bid > 0) {
                        $bpQ = $conn->prepare("SELECT price FROM pavilion_menu WHERE id=? AND available=1 LIMIT 1");
                        $bpQ->bind_param('i', $bid); $bpQ->execute();
                        $bpR = $bpQ->get_result();
                        if ($bpR && $bpR->num_rows > 0) $buffetTotal += floatval($bpR->fetch_assoc()['price']) * $pax;
                        $bpQ->close();
                    }
                }
            }

            $userId = isLoggedIn() ? getUserId() : null;
            $totalPrice = $pricing['total'] + $buffetTotal;
            $slotId = null;
            $stmt = $conn->prepare("INSERT INTO pavilion_bookings (slot_id, event_date, checkout_date, user_id, guest_name, email, phone, pax, event_type, event_time, event_end_time, buffet_items, special_requests, price, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending',NOW())");
            $stmt->bind_param('ississssissssd', $slotId, $eventDate, $checkoutDate, $userId, $guestName, $email, $phone, $pax, $eventType, $eventTime, $eventEndTime, $buffetItems, $specialReq, $totalPrice);
            $stmt->execute(); $stmt->close();
            $bookingId = $conn->insert_id;
            $conn->commit();

            echo json_encode(['success' => true, 'redirect' => 'payment/pavilion_payment.php?booking_id=' . $bookingId]);
        } else {
            throw new Exception('Invalid action.');
        }
    } catch (Exception $e) {
        if ($inTransaction) $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    $conn->close(); exit;
}
// ───────────────────────────────────────────────────────────────────────────

// Load pavilion unavailable dates (blocked by admin + already booked)
$pavilionUnavailable = [];
$pavilionPrice = 5000; // default price, admin can configure
try {
    $pvConn = getDBConnection();
    // Blocked dates set by admin
    $pvQ = $pvConn->query("SELECT event_date FROM pavilion_slots WHERE status='blocked' AND event_date >= CURDATE()");
    if ($pvQ) while ($r = $pvQ->fetch_assoc()) $pavilionUnavailable[] = $r['event_date'];
    // Already booked dates — try event_date column first, fall back to joining slots
    $pvQ2 = $pvConn->query("SELECT event_date FROM pavilion_bookings WHERE status IN ('confirmed','pending') AND event_date >= CURDATE()");
    if ($pvQ2) {
        while ($r = $pvQ2->fetch_assoc()) {
            if ($r['event_date'] && !in_array($r['event_date'], $pavilionUnavailable))
                $pavilionUnavailable[] = $r['event_date'];
        }
    } else {
        // Fallback: join with pavilion_slots for older schema
        $pvQ2b = $pvConn->query("SELECT ps.event_date FROM pavilion_bookings pb JOIN pavilion_slots ps ON pb.slot_id=ps.id WHERE pb.status IN ('confirmed','pending') AND ps.event_date >= CURDATE()");
        if ($pvQ2b) while ($r = $pvQ2b->fetch_assoc()) {
            if (!in_array($r['event_date'], $pavilionUnavailable)) $pavilionUnavailable[] = $r['event_date'];
        }
    }
    // Get admin-configured price if set
    $pvPriceQ = $pvConn->query("SELECT setting_value FROM site_settings WHERE setting_key='pavilion_price' LIMIT 1");
    if ($pvPriceQ && $pvPriceQ->num_rows > 0) $pavilionPrice = floatval($pvPriceQ->fetch_assoc()['setting_value']);
    // Load per-event-type base prices
    $pvEventPrices = getPavilionEventPrices($pvConn);
    // Load buffet menu items
    $pvMenuQ = $pvConn->query("SELECT id, name, description, price FROM pavilion_menu WHERE available=1 ORDER BY name ASC");
    $pvMenuItems = [];
    if ($pvMenuQ) while ($r = $pvMenuQ->fetch_assoc()) $pvMenuItems[] = $r;
    $pvConn->close();
} catch (Exception $e) { $pavilionUnavailable = []; $pvEventPrices = PAVILION_DEFAULT_PRICES; $pvMenuItems = []; }

// Get current user's information for auto-fill if logged in
$userInfo = null;
$preselectedRoomType = strtolower(trim($_GET['type'] ?? ''));
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

// Get carousel photos for right panel resort preview
$carouselPhotos = getPhotosWithFallback('carousel', 5);
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
                    <img src="uploads/logo/logo.png" alt="Paradise Hotel & Resort" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;">
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
        <?php if (!empty($_GET['error'])): ?>
        <div id="bookingErrorBanner" style="background:#f8d7da;color:#721c24;border:1.5px solid #f5c6cb;border-radius:10px;padding:1rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:.75rem;font-weight:600;">
            <i class="fas fa-exclamation-circle" style="font-size:1.2rem;flex-shrink:0;"></i>
            <span><?php echo htmlspecialchars($_GET['error']); ?></span>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('bookingErrorBanner')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            <?php if (!empty($_GET['tab'])): ?>
            switchTab('<?php echo htmlspecialchars($_GET['tab']); ?>');
            <?php endif; ?>
        });
        </script>
        <?php endif; ?>
        <!-- Tab Switcher -->
        <div class="booking-tabs">
            <button class="bk-tab active" id="tabRoom" onclick="switchTab('room')">
                <i class="fas fa-bed"></i> Room Booking
            </button>
            <button class="bk-tab" id="tabPavilion" onclick="switchTab('pavilion')">
                <i class="fas fa-archway"></i> Pavilion / Event Space
            </button>
        </div>

        <!-- ── ROOM BOOKING PANEL ── -->
        <div id="panelRoom">
        <div class="pv-two-col">

        <!-- Left: Room Wizard -->
        <div class="booking-form-section">
        <div class="booking-card" style="padding:0;overflow:hidden;">

            <!-- Wizard Header -->
            <div class="pv-wizard-header">
                <div class="pv-wizard-step active" id="rws1" onclick="rmGoStep(1)">
                    <div class="pv-ws-num">1</div>
                    <div class="pv-ws-label">Your Info</div>
                </div>
                <div class="pv-ws-line"></div>
                <div class="pv-wizard-step" id="rws2" onclick="rmGoStep(2)">
                    <div class="pv-ws-num">2</div>
                    <div class="pv-ws-label">Room</div>
                </div>
                <div class="pv-ws-line"></div>
                <div class="pv-wizard-step" id="rws3" onclick="rmGoStep(3)">
                    <div class="pv-ws-num">3</div>
                    <div class="pv-ws-label">Dates</div>
                </div>
                <div class="pv-ws-line"></div>
                <div class="pv-wizard-step" id="rws4" onclick="rmGoStep(4)">
                    <div class="pv-ws-num">4</div>
                    <div class="pv-ws-label">Review</div>
                </div>
            </div>

            <div style="padding:1.5rem 1.5rem 1.25rem;">
            <form id="bookingForm" action="process.php" method="POST">

            <!-- Step 1: Guest Info -->
            <div class="pv-step" id="rmStep1">
                <div class="pv-step-title"><i class="fas fa-user"></i> Tell us about yourself</div>
                <div class="pv-step-sub">We'll use this to confirm your booking.</div>
                <!-- Hidden data to pass login status to JS -->
                <input type="hidden" id="isUserLoggedIn" value="<?php echo isLoggedIn() ? '1' : '0'; ?>">
                <input type="hidden" id="preselectedRoomType" value="<?php echo htmlspecialchars($preselectedRoomType); ?>">
                <div class="form-row" style="margin-top:1.25rem;">
                    <div class="form-group">
                        <label for="name">Full Name <span class="req">*</span></label>
                        <input type="text" id="name" name="name" placeholder="e.g. Juan dela Cruz"
                               value="<?php echo htmlspecialchars($userInfo['full_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address <span class="req">*</span></label>
                        <input type="email" id="email" name="email" placeholder="e.g. juan@email.com"
                               value="<?php echo htmlspecialchars($userInfo['email'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="req">*</span></label>
                        <input type="tel" id="phone" name="phone" placeholder="e.g. 09XX XXX XXXX">
                    </div>
                    <div class="form-group">
                        <label for="guests">Number of Guests <span class="req">*</span></label>
                        <select id="guests" name="guests">
                            <option value="">How many guests?</option>
                            <option value="2">2 Guests</option>
                            <option value="8">4–8 Guests</option>
                            <option value="20">10–20 Guests</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="specialRequests">Special Requests <span style="font-weight:400;color:#aaa;font-size:.8rem;">(optional)</span></label>
                    <textarea id="specialRequests" name="special_requests" rows="2" placeholder="Any special requests or requirements..."></textarea>
                </div>
                <div class="pv-step-nav">
                    <div></div>
                    <button type="button" class="pv-btn-next" onclick="rmNext(1)">
                        Next: Select Room <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
                <!-- Hidden fields for all steps — must be inside the form -->
                <input type="hidden" id="room"     name="room">
                <input type="hidden" id="roomData" name="options">
                <input type="hidden" id="price"    name="price" value="0">
                <input type="hidden" id="nights"   name="nights">
                <input type="hidden" id="checkin"  name="checkin">
                <input type="hidden" id="checkout" name="checkout">
            </div>

            <!-- Step 2: Room Selection -->
            <div class="pv-step" id="rmStep2" style="display:none;">
                <div class="pv-step-title"><i class="fas fa-bed"></i> Choose your room</div>
                <div class="pv-step-sub" id="rmStep2Sub">Rooms available for your guest count.</div>
                <div id="roomSelection" class="room-selection" style="margin-top:1.25rem;">
                    <p class="room-instruction"><i class="fas fa-info-circle"></i> Loading rooms...</p>
                </div>
                <div class="pv-step-nav" style="margin-top:1.25rem;">
                    <button type="button" class="pv-btn-back" onclick="rmGoStep(1)">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="button" class="pv-btn-next" onclick="rmNext(2)">
                        Next: Pick Dates <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 3: Dates -->
            <div class="pv-step" id="rmStep3" style="display:none;">
                <div class="pv-step-title"><i class="fas fa-calendar-alt"></i> When are you staying?</div>
                <div class="pv-step-sub" id="rmDateRoomLabel">Select your check-in and check-out dates.</div>

                <div class="pv-step2-layout" style="margin-top:1.25rem;">
                    <!-- Left: Calendar -->
                    <div class="pv-step2-cal">
                        <div class="pv-big-cal-wrap">
                            <div id="rmCalendar"></div>
                        </div>
                        <div class="pv-cal-legend" style="margin-top:.75rem;">
                            <span class="pv-leg-item"><span class="pv-dot pv-dot-avail"></span> Available</span>
                            <span class="pv-leg-item"><span class="pv-dot pv-dot-booked"></span> Booked</span>
                            <span class="pv-leg-item"><span class="pv-dot pv-dot-past"></span> Past</span>
                        </div>
                    </div>
                    <!-- Right: Stay summary -->
                    <div class="pv-step2-side">
                        <div class="pv-sel-info" style="margin-bottom:1.25rem;">
                            <div style="font-size:.72rem;font-weight:800;color:#888;text-transform:uppercase;letter-spacing:.6px;margin-bottom:.4rem;">Your Stay</div>
                            <div id="rmSelCheckin"  style="font-size:.88rem;font-weight:700;color:#2C3E50;">Check-in: —</div>
                            <div id="rmSelCheckout" style="font-size:.88rem;font-weight:700;color:#2C3E50;margin-top:.2rem;">Check-out: —</div>
                            <div id="rmNightsBadge" style="display:none;margin-top:.5rem;background:#2C3E50;color:#fff;border-radius:20px;padding:.3rem .8rem;font-size:.82rem;font-weight:700;display:inline-flex;align-items:center;gap:.35rem;">
                                <i class="fas fa-moon"></i> <span id="rmNightsCount">0</span> nights
                            </div>
                        </div>
                        <!-- Price preview -->
                        <div class="price-summary" style="margin-top:0;">
                            <div class="price-row"><span>Room Rate:</span><span id="roomRate">₱0</span></div>
                            <div class="price-row"><span>Nights:</span><span id="nightsCount">0</span></div>
                            <div class="price-row total"><span>Total:</span><span id="totalAmount">₱0</span></div>
                        </div>
                    </div>
                </div>

                <div class="pv-step-nav" style="margin-top:1.25rem;">
                    <button type="button" class="pv-btn-back" onclick="rmGoStep(2)">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="button" class="pv-btn-next" onclick="rmNext(3)">
                        Review Booking <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 4: Review & Submit -->
            <div class="pv-step" id="rmStep4" style="display:none;">
                <div class="pv-step-title"><i class="fas fa-receipt"></i> Review Your Booking</div>
                <div class="pv-step-sub">Everything look good? Confirm to proceed to payment.</div>

                <div class="pv-review-card" style="margin-top:1.25rem;">
                    <div class="pv-review-section">
                        <div class="pv-review-label"><i class="fas fa-user"></i> Guest</div>
                        <div class="pv-review-val" id="rvRmName">—</div>
                        <div class="pv-review-sub" id="rvRmContact">—</div>
                    </div>
                    <div class="pv-review-section">
                        <div class="pv-review-label"><i class="fas fa-bed"></i> Room</div>
                        <div class="pv-review-val" id="rvRmRoom">—</div>
                        <div class="pv-review-sub" id="rvRmGuests">—</div>
                    </div>
                    <div class="pv-review-section">
                        <div class="pv-review-label"><i class="fas fa-calendar-alt"></i> Dates</div>
                        <div class="pv-review-val" id="rvRmDates">—</div>
                        <div class="pv-review-sub" id="rvRmNights">—</div>
                    </div>
                </div>

                <div class="price-summary" style="margin-top:1rem;">
                    <div class="price-row"><span>Room Rate:</span><span id="rvRmRate">₱0</span></div>
                    <div class="price-row"><span>Number of Nights:</span><span id="rvRmNightCount">0</span></div>
                    <div class="price-row total"><span>Total Amount:</span><span id="rvRmTotal">₱0</span></div>
                </div>

                <div class="pv-step-nav" style="margin-top:1.5rem;">
                    <button type="button" class="pv-btn-back" onclick="rmGoStep(3)">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="submit" class="btn-submit" id="submitBtn" style="flex:1;max-width:260px;">
                        <i class="fas fa-credit-card"></i> Confirm & Pay
                    </button>
                </div>
                <p class="form-note" style="margin-top:.75rem;">
                    <i class="fas fa-lock"></i> You'll be redirected to secure payment after confirming.
                </p>
            </div>

            </div><!-- /padding wrapper -->
            </form>
        </div><!-- /booking-card -->
        </div><!-- /booking-form-section -->

        <!-- Right: Room Preview -->
        <div class="room-preview-section">
            <div class="preview-card">
                <h3><i class="fas fa-images"></i> Room Preview</h3>
                <div id="roomPreview" class="room-preview">
                    <!-- Room detail preview (shown after room selected) -->
                    <div id="roomDetail" style="display:none;"></div>
                </div>
            </div>
        </div>

        </div><!-- .pv-two-col -->
        </div><!-- end #panelRoom -->

        <!-- ── PAVILION BOOKING PANEL ── -->
        <div id="panelPavilion" style="display:none;">
            <div class="pv-two-col">

                <!-- Left: Wizard Form -->
                <div class="booking-form-section">
                    <div class="booking-card" style="padding:0;overflow:hidden;">

                        <!-- Wizard Header -->
                        <div class="pv-wizard-header">
                            <div class="pv-wizard-step active" id="ws1" onclick="pvGoStep(1)">
                                <div class="pv-ws-num">1</div>
                                <div class="pv-ws-label">Your Info</div>
                            </div>
                            <div class="pv-ws-line"></div>
                            <div class="pv-wizard-step" id="ws2" onclick="pvGoStep(2)">
                                <div class="pv-ws-num">2</div>
                                <div class="pv-ws-label">Dates</div>
                            </div>
                            <div class="pv-ws-line"></div>
                            <div class="pv-wizard-step" id="ws3" onclick="pvGoStep(3)">
                                <div class="pv-ws-num">3</div>
                                <div class="pv-ws-label">Event</div>
                            </div>
                            <div class="pv-ws-line"></div>
                            <div class="pv-wizard-step" id="ws4" onclick="pvGoStep(4)">
                                <div class="pv-ws-num">4</div>
                                <div class="pv-ws-label">Review</div>
                            </div>
                        </div>

                        <div style="padding:1.5rem 1.5rem 1.25rem;">

                        <!-- Step 1: Guest Info -->
                        <div class="pv-step" id="pvStep1">
                            <div class="pv-step-title"><i class="fas fa-user"></i> Tell us about yourself</div>
                            <div class="pv-step-sub">We'll use this to confirm your booking.</div>
                            <!-- Hidden data to pass login status to JS -->
                            <input type="hidden" id="isPvUserLoggedIn" value="<?php echo isLoggedIn() ? '1' : '0'; ?>">
                            <div class="form-row" style="margin-top:1.25rem;">
                                <div class="form-group">
                                    <label>Full Name <span class="req">*</span></label>
                                    <input type="text" id="pvName" placeholder="e.g. Juan dela Cruz"
                                           value="<?php echo htmlspecialchars($userInfo['full_name'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Email Address <span class="req">*</span></label>
                                    <input type="email" id="pvEmail" placeholder="e.g. juan@email.com"
                                           value="<?php echo htmlspecialchars($userInfo['email'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Phone Number <span class="req">*</span></label>
                                    <input type="tel" id="pvPhone" placeholder="e.g. 09XX XXX XXXX">
                                </div>
                                <div class="form-group">
                                    <label>Number of Guests <span class="req">*</span></label>
                                    <select id="pvPax">
                                        <option value="">How many guests?</option>
                                        <option value="50">Up to 50 guests</option>
                                        <option value="100">Up to 100 guests</option>
                                        <option value="150">Up to 150 guests</option>
                                        <option value="200">Up to 200 guests</option>
                                        <option value="250">Up to 250 guests</option>
                                        <option value="300">Up to 300 guests</option>
                                        <option value="400">Up to 400 guests</option>
                                        <option value="500">Up to 500 guests</option>
                                    </select>
                                    <small id="pvPaxHint" style="color:#C9A961;font-size:.78rem;margin-top:.3rem;display:none;font-weight:600;"></small>
                                </div>
                            </div>
                            <div class="pv-step-nav">
                                <div></div>
                                <button type="button" class="pv-btn-next" onclick="pvNext(1)">
                                    Next: Select Dates <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Dates + Time -->
                        <div class="pv-step" id="pvStep2" style="display:none;">
                            <div class="pv-step-title"><i class="fas fa-calendar-alt"></i> When is your event?</div>
                            <div class="pv-step-sub">Select your event dates, then pick start and end times.</div>

                            <div class="pv-step2-layout" style="margin-top:1.25rem;">
                                <!-- Left: Calendar -->
                                <div class="pv-step2-cal">
                                    <div class="pv-big-cal-wrap">
                                        <div id="pvCalendar"></div>
                                    </div>
                                    <div class="pv-cal-legend" style="margin-top:.75rem;">
                                        <span class="pv-leg-item"><span class="pv-dot pv-dot-avail"></span> Available</span>
                                        <span class="pv-leg-item"><span class="pv-dot pv-dot-booked"></span> Unavailable</span>
                                        <span class="pv-leg-item"><span class="pv-dot pv-dot-past"></span> Past</span>
                                    </div>
                                </div>

                                <!-- Right: Selected info + Time pickers -->
                                <div class="pv-step2-side">
                                    <!-- Selected range info -->
                                    <div id="pvSelInfo" class="pv-sel-info" style="margin-bottom:1.25rem;">
                                        <div style="font-size:.72rem;font-weight:800;color:#888;text-transform:uppercase;letter-spacing:.6px;margin-bottom:.4rem;">Selected Dates</div>
                                        <div class="pv-sel-date" id="pvSelDate" style="font-size:.95rem;">—</div>
                                        <div class="pv-sel-meta" id="pvSelMeta" style="margin-top:.2rem;"></div>
                                        <div class="pv-sel-price" id="pvSelPrice"></div>
                                    </div>

                                    <!-- Time summary (read-only, filled from step 3) -->
                                    <div id="pvTimeRow">
                                        <div style="font-size:.82rem;font-weight:700;color:#2C3E50;margin-bottom:.5rem;display:flex;align-items:center;gap:.4rem;">
                                            <i class="fas fa-clock" style="color:#C9A961;"></i> Event Time
                                        </div>
                                        <div id="pvTimeSummary" style="font-size:.88rem;color:#888;font-style:italic;">Set in next step</div>
                                        <!-- hidden inputs still hold the values for submission -->
                                        <input type="hidden" id="pvTime">
                                        <input type="hidden" id="pvEndTime">
                                    </div>
                                </div>
                            </div>

                            <!-- Time slot picker — full width below calendar -->
                            <div style="margin-top:1.25rem;">
                                <div style="font-size:.82rem;font-weight:700;color:#2C3E50;margin-bottom:.75rem;display:flex;align-items:center;gap:.4rem;">
                                    <i class="fas fa-clock" style="color:#C9A961;"></i> Event Time <span class="req">*</span>
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                                    <div>
                                        <div style="font-size:.78rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem;">Start Time</div>
                                        <div class="pv-time-grid" id="pvStartGrid"></div>
                                        <div id="pvStartErr" class="pv-field-error" style="display:none;"></div>
                                    </div>
                                    <div>
                                        <div style="font-size:.78rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem;">End Time</div>
                                        <div class="pv-time-grid" id="pvEndGrid"></div>
                                        <div id="pvEndErr" class="pv-field-error" style="display:none;"></div>
                                    </div>
                                </div>
                                <div id="pvDurationBadge" style="display:none;background:#f0fff4;border:1.5px solid #28a745;border-radius:10px;padding:.55rem 1rem;font-size:.84rem;font-weight:700;color:#155724;margin-top:.75rem;">
                                    <i class="fas fa-hourglass-half"></i> <span id="pvDurationText"></span>
                                </div>
                            </div>

                            <div class="pv-step-nav" style="margin-top:1.25rem;">
                                <button type="button" class="pv-btn-back" onclick="pvGoStep(1)">
                                    <i class="fas fa-arrow-left"></i> Back
                                </button>
                                <button type="button" class="pv-btn-next" onclick="pvNext(2)">
                                    Next: Event Details <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div><!-- /pvStep2 -->

                        <!-- Step 3: Event Details + Catering -->
                        <div class="pv-step" id="pvStep3" style="display:none;">
                            <div class="pv-step-title"><i class="fas fa-star"></i> Event Details</div>
                            <div class="pv-step-sub">Tell us about your event and any catering needs.</div>

                            <div class="form-row" style="margin-top:1.25rem;">
                                <div class="form-group">
                                    <label>Event Type <span class="req">*</span></label>
                                    <select id="pvType" onchange="pvOnEventTypeChange()">
                                        <option value="">What kind of event?</option>
                                        <option>Wedding</option>
                                        <option>Birthday Party</option>
                                        <option>Corporate Event</option>
                                        <option>Graduation</option>
                                        <option>Anniversary</option>
                                        <option>Family Reunion</option>
                                        <option>Other</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Catering question — shown after event type is picked -->
                            <div id="pvCateringQuestion" style="display:none;margin-top:1rem;">
                                <div style="font-size:.82rem;font-weight:700;color:#2C3E50;margin-bottom:.6rem;display:flex;align-items:center;gap:.4rem;">
                                    <i class="fas fa-utensils" style="color:#C9A961;"></i> Would you like catering?
                                </div>
                                <div style="display:flex;gap:.75rem;">
                                    <button type="button" class="pv-catering-toggle" id="pvCateringYes" onclick="pvToggleCatering(true)">
                                        <i class="fas fa-check"></i> Yes, add catering
                                    </button>
                                    <button type="button" class="pv-catering-toggle pv-catering-no" id="pvCateringNo" onclick="pvToggleCatering(false)">
                                        <i class="fas fa-times"></i> No thanks
                                    </button>
                                </div>
                            </div>

                            <!-- Catering packages — shown when user says Yes -->
                            <div id="pvCateringPackages" style="display:none;margin-top:1rem;">
                                <?php if (!empty($pvMenuItems)): ?>
                                <div class="form-group">
                                    <label><i class="fas fa-utensils" style="color:#C9A961;"></i> Choose a Package <span class="req">*</span></label>
                                    <select id="pvBuffetSelect" onchange="pvBuffetChange()">
                                        <option value="">— Select a package —</option>
                                        <?php foreach ($pvMenuItems as $item): ?>
                                        <option value="<?php echo $item['id']; ?>"
                                                data-price="<?php echo $item['price']; ?>"
                                                data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                data-desc="<?php echo htmlspecialchars($item['description']); ?>">
                                            <?php echo htmlspecialchars($item['name']); ?> — ₱<?php echo number_format($item['price'], 2); ?>/head
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="pvCateringInfo" style="display:none;background:linear-gradient(135deg,#fffdf5,#fff8e8);border:1.5px solid #C9A961;border-radius:10px;padding:.7rem 1rem;font-size:.84rem;color:#555;margin-top:.5rem;">
                                        <div id="pvCateringDesc" style="margin-bottom:.25rem;"></div>
                                        <div id="pvCateringCost" style="font-weight:700;color:#C9A961;"></div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div style="background:#fff8e8;border:1.5px solid #C9A961;border-radius:10px;padding:.85rem 1rem;font-size:.85rem;color:#7a6020;">
                                    <i class="fas fa-info-circle"></i> Catering packages are being updated. Please contact us directly to arrange catering for your event.
                                </div>
                                <?php endif; ?>

                                <!-- Food tasting date — 5 days before event -->
                                <div id="pvFoodTastingWrap" style="display:none;margin-top:.75rem;">
                                    <div style="background:linear-gradient(135deg,#f0f8ff,#e8f4fd);border:1.5px solid #4a90d9;border-radius:12px;padding:.85rem 1rem;">
                                        <div style="font-size:.82rem;font-weight:700;color:#1a5276;margin-bottom:.4rem;display:flex;align-items:center;gap:.4rem;">
                                            <i class="fas fa-calendar-check" style="color:#4a90d9;"></i> Food Tasting Session
                                        </div>
                                        <div style="font-size:.8rem;color:#555;margin-bottom:.6rem;">
                                            We schedule a complimentary food tasting 5 days before your event so you can finalize the menu.
                                        </div>
                                        <div style="font-size:.88rem;font-weight:700;color:#1a5276;" id="pvFoodTastingDate">
                                            <i class="fas fa-clock"></i> Select your event date first to see the tasting date.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Special Requests -->
                            <div class="form-group" style="margin-top:1rem;">
                                <label><i class="fas fa-comment" style="color:#C9A961;"></i> Special Requests <span style="font-weight:400;color:#aaa;font-size:.8rem;">(optional)</span></label>
                                <textarea id="pvReq" rows="3" placeholder="Any special arrangements, decorations, or requirements..."></textarea>
                            </div>

                            <div class="pv-step-nav">
                                <button type="button" class="pv-btn-back" onclick="pvGoStep(2)">
                                    <i class="fas fa-arrow-left"></i> Back
                                </button>
                                <button type="button" class="pv-btn-next" onclick="pvNext(3)">
                                    Review Booking <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 4: Review & Submit -->
                        <div class="pv-step" id="pvStep4" style="display:none;">
                            <div class="pv-step-title"><i class="fas fa-receipt"></i> Review Your Booking</div>
                            <div class="pv-step-sub">Everything look good? Confirm to proceed to payment.</div>

                            <div class="pv-review-card" style="margin-top:1.25rem;">
                                <div class="pv-review-section">
                                    <div class="pv-review-label"><i class="fas fa-user"></i> Guest</div>
                                    <div class="pv-review-val" id="rvName">—</div>
                                    <div class="pv-review-sub" id="rvContact">—</div>
                                </div>
                                <div class="pv-review-section">
                                    <div class="pv-review-label"><i class="fas fa-calendar-alt"></i> Dates</div>
                                    <div class="pv-review-val" id="rvDates">—</div>
                                    <div class="pv-review-sub" id="rvNights">—</div>
                                </div>
                                <div class="pv-review-section">
                                    <div class="pv-review-label"><i class="fas fa-star"></i> Event</div>
                                    <div class="pv-review-val" id="rvEvent">—</div>
                                    <div class="pv-review-sub" id="rvTime">—</div>
                                </div>
                                <div class="pv-review-section" id="rvCateringSection" style="display:none;">
                                    <div class="pv-review-label"><i class="fas fa-utensils"></i> Catering</div>
                                    <div class="pv-review-val" id="rvCatering">—</div>
                                </div>
                            </div>

                            <!-- Price breakdown -->
                            <div class="price-summary" style="margin-top:1rem;">
                                <div class="price-row"><span>Base Price:</span><span id="pvSummaryBase">—</span></div>
                                <div class="price-row"><span>Guest Surcharge:</span><span id="pvSummarySurcharge">—</span></div>
                                <div class="price-row" id="pvSummaryBuffetRow" style="display:none;"><span>Catering:</span><span id="pvSummaryBuffet">₱0</span></div>
                                <div class="price-row total"><span>Total Amount:</span><span id="pvSummaryPrice">₱0</span></div>
                            </div>

                            <!-- Hidden summary fields still needed for JS compat -->
                            <span id="pvSummaryDate" style="display:none;"></span>
                            <span id="pvSummaryCheckout" style="display:none;"></span>
                            <span id="pvSummaryNights" style="display:none;"></span>
                            <span id="pvSummaryTime" style="display:none;"></span>
                            <span id="pvSummaryEndTime" style="display:none;"></span>
                            <span id="pvSummaryPax" style="display:none;"></span>

                            <div class="pv-step-nav" style="margin-top:1.5rem;">
                                <button type="button" class="pv-btn-back" onclick="pvGoStep(3)">
                                    <i class="fas fa-arrow-left"></i> Back
                                </button>
                                <button type="button" class="btn-submit" id="pvSubmitBtn" onclick="doPvBook()" style="flex:1;max-width:260px;">
                                    <i class="fas fa-credit-card"></i> Confirm & Pay
                                </button>
                            </div>
                            <p class="form-note" style="margin-top:.75rem;">
                                <i class="fas fa-lock"></i> You'll be redirected to secure payment after confirming.
                            </p>
                        </div>

                        </div><!-- /padding wrapper -->
                    </div>
                </div>

                <!-- Right: Pavilion Preview -->
                <div class="room-preview-section">
                    <div class="preview-card">
                        <h3><i class="fas fa-archway"></i> Pavilion Overview</h3>
                        <div class="room-preview">
                            <div class="room-preview-card">
                                <div class="room-preview-title">
                                    <h3>Paradise Pavilion & Event Space</h3>
                                    <p class="room-preview-desc">A stunning open-air venue perfect for weddings, corporate events, and celebrations. Surrounded by lush gardens and resort facilities.</p>
                                </div>
                                <div class="room-preview-amenities">
                                    <h4><i class="fas fa-star"></i> Venue Features</h4>
                                    <div class="amenities-preview-grid">
                                        <div class="amenity-preview-item"><i class="fas fa-users"></i><span>Up to 500 guests</span></div>
                                        <div class="amenity-preview-item"><i class="fas fa-music"></i><span>Sound system</span></div>
                                        <div class="amenity-preview-item"><i class="fas fa-lightbulb"></i><span>Event lighting</span></div>
                                        <div class="amenity-preview-item"><i class="fas fa-parking"></i><span>Ample parking</span></div>
                                        <div class="amenity-preview-item"><i class="fas fa-utensils"></i><span>Catering area</span></div>
                                        <div class="amenity-preview-item"><i class="fas fa-wifi"></i><span>Free WiFi</span></div>
                                        <div class="amenity-preview-item"><i class="fas fa-snowflake"></i><span>Climate control</span></div>
                                        <div class="amenity-preview-item"><i class="fas fa-camera"></i><span>Photo-ready setup</span></div>
                                    </div>
                                </div>
                                <div class="room-preview-inclusions">
                                    <h4><i class="fas fa-gift"></i> Included Services</h4>
                                    <ul class="inclusions-preview-list">
                                        <li><i class="fas fa-check-circle"></i> Tables & chairs setup</li>
                                        <li><i class="fas fa-check-circle"></i> Basic decorations</li>
                                        <li><i class="fas fa-check-circle"></i> Event coordinator</li>
                                        <li><i class="fas fa-check-circle"></i> Security personnel</li>
                                        <li><i class="fas fa-check-circle"></i> Cleanup service</li>
                                        <li><i class="fas fa-check-circle"></i> Restroom facilities</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- .pv-two-col -->
        </div><!-- end #panelPavilion -->

    </div><!-- end .booking-container -->

    <!-- Toast for pavilion -->
    <div id="pvToastOv" style="position:fixed;inset:0;background:rgba(0,0,0,.2);z-index:9999;display:none;"></div>
    <div id="pvToast" style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) scale(.85);z-index:10000;min-width:300px;max-width:440px;padding:1.1rem 1.5rem;border-radius:14px;font-size:.93rem;font-weight:600;display:flex;align-items:flex-start;gap:.7rem;box-shadow:0 20px 60px rgba(0,0,0,.25);opacity:0;pointer-events:none;transition:opacity .25s,transform .25s;line-height:1.5;">
        <i id="pvToastIcon"></i><span id="pvToastTxt"></span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="assets/js/booking.js?v=<?php echo time(); ?>"></script>
    <script>
    // ── TAB SWITCHER ──
    function switchTab(tab) {
        document.getElementById('panelRoom').style.display      = tab === 'room'     ? '' : 'none';
        document.getElementById('panelPavilion').style.display  = tab === 'pavilion' ? '' : 'none';
        document.getElementById('tabRoom').classList.toggle('active',      tab === 'room');
        document.getElementById('tabPavilion').classList.toggle('active',  tab === 'pavilion');
        sessionStorage.setItem('bookingTab', tab);
    }
    // Restore last tab or use URL param
    (function(){
        const urlTab = new URLSearchParams(window.location.search).get('tab');
        const t = urlTab || sessionStorage.getItem('bookingTab');
        if (t) switchTab(t);
    })();

    // ── PAVILION CALENDAR ──
    const pvUnavailable = <?php echo json_encode(array_values($pavilionUnavailable)); ?>;
    const pvEventPrices = <?php echo json_encode($pvEventPrices); ?>;
    const pvSurchargePerTen = <?php echo PAVILION_GUEST_SURCHARGE_PER_10; ?>;

    function calcPvPrice(eventType, guests) {
        const base = pvEventPrices[eventType] ?? pvEventPrices['Other'] ?? 15000;
        const surcharge = Math.ceil((guests || 0) / 10) * pvSurchargePerTen;
        return { base, surcharge, total: base + surcharge };
    }

    let pvSel = null;
    let pvSelOut = null;
    let pvCalInstance = null;
    let pvCalOutInstance = null;
    let pvTimePicker  = null;
    let pvEndTimePicker = null;
    const pvTimeSlots = ['6:00 AM','6:30 AM','7:00 AM','7:30 AM','8:00 AM','8:30 AM','9:00 AM','9:30 AM',
        '10:00 AM','10:30 AM','11:00 AM','11:30 AM','12:00 PM','12:30 PM','1:00 PM','1:30 PM',
        '2:00 PM','2:30 PM','3:00 PM','3:30 PM','4:00 PM','4:30 PM','5:00 PM','5:30 PM',
        '6:00 PM','6:30 PM','7:00 PM','7:30 PM','8:00 PM','8:30 PM','9:00 PM','9:30 PM','10:00 PM'];
    const pvMenuPrices = <?php echo json_encode(array_column($pvMenuItems, 'price', 'id')); ?>;
    let pvSelectedCatering = { id: null, price: 0 };

    function pvOnEventTypeChange() {
        const etype = document.getElementById('pvType').value;
        const qWrap = document.getElementById('pvCateringQuestion');
        if (etype) {
            qWrap.style.display = 'block';
        } else {
            qWrap.style.display = 'none';
            pvToggleCatering(false);
        }
        updatePvPriceSummary();
    }

    let pvWantsCatering = false;

    function pvToggleCatering(yes) {
        pvWantsCatering = yes;
        document.getElementById('pvCateringYes').classList.toggle('active', yes);
        document.getElementById('pvCateringNo').classList.toggle('active', !yes);
        const pkgs = document.getElementById('pvCateringPackages');
        if (pkgs) pkgs.style.display = yes ? 'block' : 'none';
        if (!yes) {
            const sel = document.getElementById('pvBuffetSelect');
            if (sel) sel.value = '';
            pvSelectedCatering = { id: null, price: 0 };
            const info = document.getElementById('pvCateringInfo');
            if (info) info.style.display = 'none';
            const ftWrap = document.getElementById('pvFoodTastingWrap');
            if (ftWrap) ftWrap.style.display = 'none';
            updatePvPriceSummary();
        } else {
            // Show food tasting wrap right away
            const ftWrap = document.getElementById('pvFoodTastingWrap');
            if (ftWrap) ftWrap.style.display = 'block';
            const dateEl = document.getElementById('pvFoodTastingDate');
            if (dateEl && pvSel) {
                const evDate = new Date(pvSel.dateStr + 'T00:00:00');
                evDate.setDate(evDate.getDate() - 5);
                const label = evDate.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
                dateEl.innerHTML = `<i class="fas fa-calendar-check"></i> Your tasting is scheduled for: <strong>${label}</strong>`;
            }
        }
    }

    function pvBuffetChange() {
        const sel = document.getElementById('pvBuffetSelect');
        const opt = sel.options[sel.selectedIndex];
        const info = document.getElementById('pvCateringInfo');
        const ftWrap = document.getElementById('pvFoodTastingWrap');
        if (!sel.value) {
            pvSelectedCatering = { id: null, price: 0 };
            info.style.display = 'none';
            if (ftWrap) ftWrap.style.display = 'none';
        } else {
            const price = parseFloat(opt.dataset.price) || 0;
            pvSelectedCatering = { id: parseInt(sel.value), price };
            const guests = parseInt(document.getElementById('pvPax').value) || 0;
            document.getElementById('pvCateringDesc').textContent = opt.dataset.desc || '';
            document.getElementById('pvCateringCost').textContent = guests > 0
                ? `₱${price.toLocaleString()}/head × ${guests} guests = ₱${(price * guests).toLocaleString()} total`
                : `₱${price.toLocaleString()} per head`;
            info.style.display = 'block';

            // Show food tasting date (5 days before event)
            if (ftWrap) {
                ftWrap.style.display = 'block';
                const dateEl = document.getElementById('pvFoodTastingDate');
                if (pvSel && dateEl) {
                    const evDate = new Date(pvSel.dateStr + 'T00:00:00');
                    evDate.setDate(evDate.getDate() - 5);
                    const label = evDate.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
                    dateEl.innerHTML = `<i class="fas fa-calendar-check"></i> Your tasting is scheduled for: <strong>${label}</strong>`;
                } else if (dateEl) {
                    dateEl.innerHTML = `<i class="fas fa-clock"></i> Select your event date first to see the tasting date.`;
                }
            }
        }
        updatePvPriceSummary();
    }

    function pvBuffetTotal() {
        const guests = parseInt(document.getElementById('pvPax').value) || 0;
        return pvSelectedCatering.price * guests;
    }

    function updatePvEndTimeMin() { if (typeof pvUpdateEndGrid === 'function') pvUpdateEndGrid(); }

    let pvUpdateEndGrid = null;

    function updatePvDuration() {
        const badge = document.getElementById('pvDurationBadge');
        const txt   = document.getElementById('pvDurationText');
        const startVal = document.getElementById('pvTime')?.value;
        const endVal   = document.getElementById('pvEndTime')?.value;
        if (!startVal || !endVal) { if(badge) badge.style.display = 'none'; return; }
        const si = pvTimeSlots.indexOf(startVal), ei = pvTimeSlots.indexOf(endVal);
        if (si < 0 || ei < 0 || ei <= si) { if(badge) badge.style.display = 'none'; return; }
        const mins = (ei - si) * 30;
        const h = Math.floor(mins / 60), m = mins % 60;
        if(txt) txt.textContent = h + 'h' + (m ? ' ' + m + 'm' : '') + ' duration';
        if(badge) badge.style.display = 'block';
    }

    function pvNightsBetween(inStr, outStr) {
        if (!inStr || !outStr) return 0;
        const a = new Date(inStr + 'T00:00:00'), b = new Date(outStr + 'T00:00:00');
        return Math.max(0, Math.round((b - a) / 86400000));
    }

    function initPvCalendar() {
        if (pvCalInstance) return;
        const today = new Date(); today.setHours(0,0,0,0);

        function applyDayClasses(fp) {
            fp.calendarContainer?.querySelectorAll('.flatpickr-day').forEach(dayElem => {
                if (!dayElem.dateObj) return;
                const d = dayElem.dateObj, ymd = d.toISOString().slice(0,10);
                dayElem.classList.remove('pv-past','pv-avail','pv-booked');
                if (d < today) dayElem.classList.add('pv-past');
                else if (pvUnavailable.includes(ymd)) dayElem.classList.add('pv-booked');
                else dayElem.classList.add('pv-avail');
            });
        }

        // Single date picker (no checkout — pavilion is single-day event)
        pvCalInstance = flatpickr('#pvCalendar', {
            inline: true,
            minDate: 'today',
            dateFormat: 'Y-m-d',
            disable: pvUnavailable,
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const d = dayElem.dateObj; if (!d) return;
                const ymd = d.toISOString().slice(0,10);
                if (d < today || dayElem.classList.contains('flatpickr-disabled')) {
                    dayElem.classList.add(pvUnavailable.includes(ymd) && d >= today ? 'pv-booked' : 'pv-past');
                } else {
                    dayElem.classList.add('pv-avail');
                }
            },
            onMonthChange: function(sel, str, fp) { applyDayClasses(fp); },
            onChange: function(selectedDates, dateStr) {
                if (!dateStr) return;
                const d = new Date(dateStr + 'T00:00:00');
                pvSel = { dateStr, label: d.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' }) };
                pvSelOut = pvSel; // same day event
                updatePvSelInfo();
                updatePvPriceSummary();
            }
        });

        // Visual time slot grids
        const pvTimeSlots = ['6:00 AM','6:30 AM','7:00 AM','7:30 AM','8:00 AM','8:30 AM','9:00 AM','9:30 AM',
            '10:00 AM','10:30 AM','11:00 AM','11:30 AM','12:00 PM','12:30 PM','1:00 PM','1:30 PM',
            '2:00 PM','2:30 PM','3:00 PM','3:30 PM','4:00 PM','4:30 PM','5:00 PM','5:30 PM',
            '6:00 PM','6:30 PM','7:00 PM','7:30 PM','8:00 PM','8:30 PM','9:00 PM','9:30 PM','10:00 PM'];

        function pvBuildTimeGrid(containerId, hiddenId, onSelect) {
            const wrap = document.getElementById(containerId);
            if (!wrap) return;
            wrap.innerHTML = '';
            pvTimeSlots.forEach(slot => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'pv-time-slot';
                btn.textContent = slot;
                btn.dataset.val = slot;
                btn.onclick = function() {
                    wrap.querySelectorAll('.pv-time-slot').forEach(b => b.classList.remove('selected'));
                    btn.classList.add('selected');
                    document.getElementById(hiddenId).value = slot;
                    onSelect(slot);
                };
                wrap.appendChild(btn);
            });
        }

        pvUpdateEndGrid = function() {
            const startVal = document.getElementById('pvTime').value;
            const startIdx = pvTimeSlots.indexOf(startVal);
            const endWrap  = document.getElementById('pvEndGrid');
            if (!endWrap) return;
            endWrap.querySelectorAll('.pv-time-slot').forEach((btn, i) => {
                const disabled = startIdx >= 0 && i <= startIdx;
                btn.disabled = disabled;
                btn.classList.toggle('disabled', disabled);
            });
            updatePvDuration();
            updatePvPriceSummary();
        }

        pvBuildTimeGrid('pvStartGrid', 'pvTime', function(val) {
            // Clear end time if it's now invalid
            const endVal = document.getElementById('pvEndTime').value;
            const si = pvTimeSlots.indexOf(val), ei = pvTimeSlots.indexOf(endVal);
            if (ei >= 0 && ei <= si) {
                document.getElementById('pvEndTime').value = '';
                document.getElementById('pvEndGrid').querySelectorAll('.pv-time-slot').forEach(b => b.classList.remove('selected'));
            }
            pvUpdateEndGrid();
            pvUpdateTimeSummary();
        });
        pvBuildTimeGrid('pvEndGrid', 'pvEndTime', function() {
            updatePvDuration();
            updatePvPriceSummary();
            pvUpdateTimeSummary();
        });
        pvUpdateEndGrid();

        function pvUpdateTimeSummary() {
            const s = document.getElementById('pvTime').value;
            const e = document.getElementById('pvEndTime').value;
            const el = document.getElementById('pvTimeSummary');
            if (el) el.textContent = (s && e) ? s + ' – ' + e : s ? s + ' (end not set)' : 'Set in next step';
        }

        document.getElementById('pvType').addEventListener('change', updatePvPriceSummary);
        document.getElementById('pvPax').addEventListener('change', function() { pvBuffetChange(); updatePvPriceSummary(); });
    }

    function updatePvSelInfo() {
        if (!pvSel) {
            document.getElementById('pvSelDate').textContent = '—';
            document.getElementById('pvSelMeta').textContent = 'No date selected yet';
            return;
        }
        document.getElementById('pvSelDate').textContent = pvSel.label;
        document.getElementById('pvSelMeta').textContent = 'Single-day event';
    }

    function updatePvPriceSummary() {
        const etype   = document.getElementById('pvType').value;
        const guests  = parseInt(document.getElementById('pvPax').value) || 0;
        const pricing = calcPvPrice(etype, guests);
        const buffet  = pvBuffetTotal();
        const grandTotal = pricing.total + buffet;

        // Sel info price hint (step 2)
        if (pvSel) {
            const hint = etype && guests > 0
                ? `₱${pricing.base.toLocaleString()} base + ₱${pricing.surcharge.toLocaleString()} surcharge`
                : 'Complete event details to see price';
            document.getElementById('pvSelPrice').textContent = hint;
        }

        // Pax hint (step 1)
        const paxHint = document.getElementById('pvPaxHint');
        if (guests > 0 && etype) {
            const groups = Math.ceil(guests / 10);
            paxHint.textContent = `${groups} × 10 guests → +₱${pricing.surcharge.toLocaleString()} surcharge`;
            paxHint.style.display = 'block';
        } else {
            paxHint.style.display = 'none';
        }

        // Price breakdown (step 4)
        const baseEl = document.getElementById('pvSummaryBase');
        if (baseEl) {
            baseEl.textContent = '₱' + pricing.base.toLocaleString();
            document.getElementById('pvSummarySurcharge').textContent = '₱' + pricing.surcharge.toLocaleString();
            const buffetRow = document.getElementById('pvSummaryBuffetRow');
            buffetRow.style.display = buffet > 0 ? '' : 'none';
            document.getElementById('pvSummaryBuffet').textContent = '₱' + buffet.toLocaleString();
            document.getElementById('pvSummaryPrice').textContent  = '₱' + grandTotal.toLocaleString();
        }
    }

    // ── PAVILION WIZARD ──
    let pvCurrentStep = 1;

    function pvGoStep(n) {
        for (let i = 1; i <= 4; i++) {
            document.getElementById('pvStep' + i).style.display = i === n ? '' : 'none';
            const ws = document.getElementById('ws' + i);
            ws.classList.toggle('active', i === n);
            ws.classList.toggle('done',   i < n);
        }
        pvCurrentStep = n;
        if (n === 2) initPvCalendar();
        if (n === 4) pvPopulateReview();
    }

    function pvFieldErr(id, msg) {
        const el = document.getElementById(id);
        if (!el) return;
        const wrap = el.closest('.form-group') || el.parentElement;
        wrap.classList.add('pv-input-err');
        let errEl = wrap.querySelector('.pv-field-error');
        if (!errEl) { errEl = document.createElement('div'); errEl.className = 'pv-field-error'; wrap.appendChild(errEl); }
        errEl.textContent = msg;
        el.focus();
        setTimeout(() => {
            wrap.classList.remove('pv-input-err');
            if (errEl) errEl.remove();
        }, 4000);
    }

    function pvClearErrs(ids) {
        ids.forEach(id => {
            const el = document.getElementById(id); if (!el) return;
            const wrap = el.closest('.form-group') || el.parentElement;
            wrap.classList.remove('pv-input-err');
            wrap.querySelector('.pv-field-error')?.remove();
        });
    }

    function pvNext(step) {
        if (step === 1) {
            const name  = document.getElementById('pvName').value.trim();
            const email = document.getElementById('pvEmail').value.trim();
            const phone = document.getElementById('pvPhone').value.trim();
            const pax   = document.getElementById('pvPax').value;
            pvClearErrs(['pvName','pvEmail','pvPhone','pvPax']);
            if (!name)  { pvFieldErr('pvName',  'Please enter your full name.'); return; }
            if (!email) { pvFieldErr('pvEmail', 'Please enter your email address.'); return; }
            if (!phone) { pvFieldErr('pvPhone', 'Please enter your phone number.'); return; }
            if (!pax)   { pvFieldErr('pvPax',   'Please select the number of guests.'); return; }
        }
        if (step === 2) {
            pvClearErrs([]);
            if (!pvSel) { pvToast(false, 'Please select an event date on the calendar.'); return; }
            const time = document.getElementById('pvTime').value.trim();
            const end  = document.getElementById('pvEndTime').value.trim();
            if (!time) {
                document.getElementById('pvStartErr').textContent = 'Please select a start time.';
                document.getElementById('pvStartErr').style.display = 'block';
                setTimeout(() => { document.getElementById('pvStartErr').style.display = 'none'; }, 4000);
                return;
            }
            if (!end) {
                document.getElementById('pvEndErr').textContent = 'Please select an end time.';
                document.getElementById('pvEndErr').style.display = 'block';
                setTimeout(() => { document.getElementById('pvEndErr').style.display = 'none'; }, 4000);
                return;
            }
        }
        if (step === 3) {
            const etype = document.getElementById('pvType').value;
            pvClearErrs(['pvType']);
            if (!etype) { pvFieldErr('pvType', 'Please select an event type.'); return; }
            const bufSel = document.getElementById('pvBuffetSelect');
            if (pvWantsCatering && bufSel && !bufSel.value) {
                pvFieldErr('pvBuffetSelect', 'Please select a catering package or choose "No thanks".');
                return;
            }
        }
        pvGoStep(step + 1);
    }

    function pvPopulateReview() {
        const name  = document.getElementById('pvName').value.trim();
        const email = document.getElementById('pvEmail').value.trim();
        const phone = document.getElementById('pvPhone').value.trim();
        const pax   = document.getElementById('pvPax').value;
        const etype = document.getElementById('pvType').value;
        const time  = document.getElementById('pvTime').value;
        const end   = document.getElementById('pvEndTime').value;
        const nights = pvNightsBetween(pvSel?.dateStr, pvSelOut?.dateStr);

        document.getElementById('rvName').textContent    = name;
        document.getElementById('rvContact').textContent = email + (phone ? ' · ' + phone : '');
        document.getElementById('rvDates').textContent   = pvSel?.label || '—';
        document.getElementById('rvNights').textContent  = pax + ' guests · Single-day event';
        document.getElementById('rvEvent').textContent   = etype || '—';
        document.getElementById('rvTime').textContent    = time + (end ? ' – ' + end : '');

        const catSec = document.getElementById('rvCateringSection');
        if (pvSelectedCatering.id) {
            const sel = document.getElementById('pvBuffetSelect');
            const opt = sel.options[sel.selectedIndex];
            const guests = parseInt(pax) || 0;
            let cateringText = opt.dataset.name + ' · ₱' + (pvSelectedCatering.price * guests).toLocaleString();
            if (pvSel) {
                const evDate = new Date(pvSel.dateStr + 'T00:00:00');
                evDate.setDate(evDate.getDate() - 5);
                const tastingLabel = evDate.toLocaleDateString('en-US', { month:'long', day:'numeric', year:'numeric' });
                cateringText += ` · Tasting: ${tastingLabel}`;
            }
            document.getElementById('rvCatering').textContent = cateringText;
            catSec.style.display = '';
        } else {
            catSec.style.display = 'none';
        }

        updatePvPriceSummary();
    }
    const _origSwitch = window.switchTab;
    window.switchTab = function(tab) {
        _origSwitch(tab);
        if (tab === 'pavilion') initPvCalendar();
    };
    if (sessionStorage.getItem('bookingTab') === 'pavilion') {
        initPvCalendar();
        restorePendingPvBooking();
    } else if (sessionStorage.getItem('pendingPvBooking')) {
        // If pavilion booking was pending, switch to pavilion tab and restore
        setTimeout(() => {
            switchTab('pavilion');
            restorePendingPvBooking();
        }, 100);
    }

    const pvAutoName  = <?php echo json_encode($userInfo['full_name'] ?? ''); ?>;
    const pvAutoEmail = <?php echo json_encode($userInfo['email']     ?? ''); ?>;

    function restorePendingPvBooking() {
        const pendingData = sessionStorage.getItem('pendingPvBooking');
        if (pendingData) {
            try {
                const data = JSON.parse(pendingData);
                // Restore form fields
                if (data.name) document.getElementById('pvName').value = data.name;
                if (data.email) document.getElementById('pvEmail').value = data.email;
                if (data.phone) document.getElementById('pvPhone').value = data.phone;
                if (data.pax) document.getElementById('pvPax').value = data.pax;
                if (data.eventType) document.getElementById('pvType').value = data.eventType;
                if (data.eventTime) document.getElementById('pvTime').value = data.eventTime;
                if (data.eventEndTime) document.getElementById('pvEndTime').value = data.eventEndTime;
                if (data.specialRequests) document.getElementById('pvReq').value = data.specialRequests;
                
                // Show toast and move to appropriate step
                document.getElementById('pvToastTxt').textContent = 'Welcome back! Your pavilion booking details have been restored.';
                pvGoStep(2);
                
                // Clear the saved data
                sessionStorage.removeItem('pendingPvBooking');
                sessionStorage.removeItem('pvBookingStep');
            } catch (e) {
                console.log('Could not restore pending pavilion booking:', e);
            }
        }
    }

    function doPvBook() {
        // ────── LOGIN CHECK ──────────────────────────────────────────────────────────
        const pvIsLoggedIn = document.getElementById('isPvUserLoggedIn')?.value === '1';
        if (!pvIsLoggedIn) {
            // Save all pavilion form data to sessionStorage
            const pvBookingData = {
                name: document.getElementById('pvName').value,
                email: document.getElementById('pvEmail').value,
                phone: document.getElementById('pvPhone').value,
                pax: document.getElementById('pvPax').value,
                eventType: document.getElementById('pvType').value,
                eventDate: pvSel?.dateStr || '',
                eventDateLabel: pvSel?.label || '',
                eventTime: document.getElementById('pvTime').value,
                eventEndTime: document.getElementById('pvEndTime').value,
                specialRequests: document.getElementById('pvReq')?.value || '',
                buffetSelected: pvSelectedCatering?.id || ''
            };
            sessionStorage.setItem('pendingPvBooking', JSON.stringify(pvBookingData));
            sessionStorage.setItem('pvBookingStep', '1'); // Resume from step 1 after login
            
            // Redirect to login
            window.location.href = 'login.php?return=booking';
            return;
        }
        // ──────────────────────────────────────────────────────────────────────────────
        
        if (!pvSel) { pvToast(false, 'Please select an event date from the calendar.'); return; }
        const name  = document.getElementById('pvName').value.trim();
        const email = document.getElementById('pvEmail').value.trim();
        const pax   = parseInt(document.getElementById('pvPax').value);
        const phone = document.getElementById('pvPhone').value.trim();
        const etype = document.getElementById('pvType').value;
        const time  = document.getElementById('pvTime').value.trim();
        const req   = document.getElementById('pvReq').value.trim();

        if (!name)           { pvToast(false, 'Please enter your full name.'); return; }
        if (!email)          { pvToast(false, 'Please enter your email address.'); return; }
        if (!phone)          { pvToast(false, 'Please enter your phone number.'); return; }
        if (!pax || pax < 1) { pvToast(false, 'Please enter the number of guests.'); return; }
        if (!etype)          { pvToast(false, 'Please select an event type.'); return; }
        if (!time)           { pvToast(false, 'Please select an event start time.'); return; }

        const endTime = document.getElementById('pvEndTime').value.trim();
        if (!endTime)        { pvToast(false, 'Please select an event end time.'); return; }

        const pricing  = calcPvPrice(etype, pax);
        const buffet   = pvBuffetTotal();
        const grandTotal = pricing.total + buffet;

        // Build buffet selections string
        const buffetItems = pvSelectedCatering.id ? [pvSelectedCatering.id] : [];

        const btn = document.getElementById('pvSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        const fd = new FormData();
        fd.append('action',       'book');
        fd.append('event_date',   pvSel.dateStr);
        fd.append('checkout_date', pvSel.dateStr);
        fd.append('guest_name',   name);
        fd.append('email',        email);
        fd.append('phone',        phone);
        fd.append('pax',          pax);
        fd.append('event_type',   etype);
        fd.append('event_time',   time);
        fd.append('event_end_time', endTime);
        fd.append('buffet_items', buffetItems.join(','));
        fd.append('special_requests', req);

        fetch('booking.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    pvToast(false, data.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-credit-card"></i> Complete Booking';
                    return;
                }
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redirecting to payment...';
                window.location.href = data.redirect;
            })
            .catch(() => {
                pvToast(false, 'Request failed. Please try again.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-credit-card"></i> Complete Booking';
            });
    }

    function pvToast(ok, msg) {
        const t = document.getElementById('pvToast'), ov = document.getElementById('pvToastOv');
        document.getElementById('pvToastIcon').className = ok ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
        document.getElementById('pvToastTxt').textContent = msg;
        t.style.background   = ok ? '#d4edda' : '#f8d7da';
        t.style.color        = ok ? '#155724' : '#721c24';
        t.style.border       = ok ? '1.5px solid #b7dfbb' : '1.5px solid #f1b0b7';
        t.style.opacity      = '1';
        t.style.pointerEvents= 'auto';
        t.style.transform    = 'translate(-50%,-50%) scale(1)';
        ov.style.display     = 'block';
        ov.onclick = () => { t.style.opacity='0'; t.style.transform='translate(-50%,-50%) scale(.85)'; t.style.pointerEvents='none'; ov.style.display='none'; };
        clearTimeout(t._t);
        t._t = setTimeout(() => { t.style.opacity='0'; t.style.transform='translate(-50%,-50%) scale(.85)'; t.style.pointerEvents='none'; ov.style.display='none'; }, ok ? 3500 : 6000);
    }
    </script>
</body>
</html>