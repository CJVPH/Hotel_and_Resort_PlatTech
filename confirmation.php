<?php
require_once 'config/database.php';
require_once 'config/auth.php';

requireLogin();

$reservationId = intval($_GET['reservation_id'] ?? 0);
$bookingId     = intval($_GET['booking_id'] ?? 0);
$isPavilion    = $bookingId > 0;

if (!$isPavilion && $reservationId <= 0) {
    header('Location: booking.php?error=Invalid reservation'); exit();
}

$userId = getUserId();

try {
    $conn = getDBConnection();
    if ($isPavilion) {
        $stmt = $conn->prepare("SELECT * FROM pavilion_bookings WHERE id=?");
        $stmt->bind_param('i', $bookingId);
    } else {
        $stmt = $conn->prepare("SELECT * FROM reservations WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $reservationId, $userId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) { header('Location: booking.php?error=Booking not found'); exit(); }
    $data = $result->fetch_assoc();
    $stmt->close(); $conn->close();
} catch (Exception $e) {
    error_log("Confirmation page error: " . $e->getMessage());
    header('Location: booking.php?error=Database error'); exit();
}

$paymentMethods = [
    'credit_card'   => 'Credit/Debit Card',
    'paypal'        => 'PayPal',
    'gcash'         => 'GCash',
    'bank_transfer' => 'Bank Transfer',
    'cash'          => 'Cash Payment',
    'otc'           => 'Over the Counter',
];
$methodLabel = $paymentMethods[$data['payment_method'] ?? ''] ?? ucwords(str_replace('_', ' ', $data['payment_method'] ?? 'Pending'));

if ($isPavilion) {
    $refNum    = 'PAV-' . str_pad($bookingId, 6, '0', STR_PAD_LEFT);
    $pageTitle = 'Pavilion Booking Confirmed';
} else {
    $refNum    = $data['payment_reference'] ?? ('RES-' . str_pad($reservationId, 6, '0', STR_PAD_LEFT));
    $pageTitle = 'Booking Confirmation';
    $options   = json_decode($data['options'] ?? '{}', true) ?? [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Paradise Hotel & Resort</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Montserrat', sans-serif;
        background: #2e3f50;
        min-height: 100vh;
    }

    /* ── Navbar ── */
    .topbar {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        padding: .85rem 2.5rem;
        background: #f5f5f5;
        border-bottom: 1px solid #ddd;
    }
    .topbar a.nav-link {
        color: #2C3E50; font-size: 1rem; font-weight: 600;
        text-decoration: none; display: flex; align-items: center; gap: .4rem;
    }
    .topbar a.nav-link:hover { color: #C9A961; }
    .topbar .logo {
        font-size: 1.1rem; font-weight: 800; color: #2C3E50;
        display: flex; align-items: center; gap: .5rem;
        justify-content: center;
    }
    .topbar .logo img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
    .topbar .user {
        color: #2C3E50; font-size: 1rem; font-weight: 600;
        display: flex; align-items: center; gap: .4rem;
        justify-content: flex-end;
    }
    .topbar .user i { color: #C9A961; }

    /* ── Page wrapper ── */
    .page {
        max-width: 960px;
        margin: .8rem auto;
        padding: 0 1.5rem 3rem;
    }

    /* ── Single white card ── */
    .card {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 40px rgba(0,0,0,.25);
    }

    /* ── Hero (green gradient) ── */
    .hero {
        background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
        padding: 2.5rem 2rem;
        text-align: center;
        color: #fff;
    }
    .hero .chk {
        width: 60px; height: 60px;
        background: rgba(255,255,255,.25);
        border: 3px solid rgba(255,255,255,.7);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1.2rem;
        font-size: 1.6rem;
    }
    .hero h1 { font-size: 2rem; font-weight: 800; margin-bottom: .4rem; }
    .hero p  { font-size: .95rem; opacity: .9; margin-bottom: 1.4rem; }
    .hero .ref-box {
        background: rgba(255,255,255,.2);
        border: 1px solid rgba(255,255,255,.5);
        border-radius: 8px;
        padding: .7rem 1.5rem;
        font-size: 1rem; font-weight: 700;
        display: inline-block;
    }

    /* ── Card body ── */
    .card-body { padding: 2rem; }

    /* ── Section ── */
    .section { margin-bottom: 2rem; }
    .section-title {
        font-size: 1.05rem; font-weight: 800; color: #2C3E50;
        display: flex; align-items: center; gap: .5rem;
        margin-bottom: 1rem;
        padding-bottom: .5rem;
        border-bottom: 2px solid #f0f0f0;
    }
    .section-title i { color: #C9A961; }

    /* ── Detail table (label left, value right) ── */
    .detail-box {
        border: 1px solid #e8e8e8;
        border-radius: 10px;
        overflow: hidden;
    }
    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .75rem 1.2rem;
        border-bottom: 1px solid #f2f2f2;
        font-size: .92rem;
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-row .lbl { font-weight: 600; color: #2C3E50; }
    .detail-row .val { color: #555; text-align: right; }
    .val-pending   { color: #C9A961 !important; font-weight: 700 !important; }
    .val-completed { color: #27ae60 !important; font-weight: 700 !important; }

    /* ── Info list ── */
    .info-list { display: flex; flex-direction: column; gap: .9rem; }
    .info-item {
        display: flex; align-items: flex-start; gap: .9rem;
        font-size: .9rem; color: #555; line-height: 1.6;
    }
    .info-item i { color: #C9A961; font-size: 1rem; margin-top: .2rem; flex-shrink: 0; width: 18px; text-align: center; }
    .info-item strong { color: #2C3E50; }

    /* ── Buttons ── */
    .btn-row {
        display: flex; gap: 1rem; justify-content: center;
        padding: 1.5rem 2rem 2rem;
        flex-wrap: wrap;
    }
    .btn {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .8rem 2rem; border-radius: 50px; border: none;
        font-size: .95rem; font-weight: 700; font-family: 'Montserrat', sans-serif;
        cursor: pointer; text-decoration: none; transition: all .2s;
    }
    .btn-home  { background: #2C3E50; color: #fff; box-shadow: 0 4px 14px rgba(44,62,80,.3); }
    .btn-home:hover  { background: #1a2332; transform: translateY(-1px); }
    .btn-print { background: #C9A961; color: #fff; box-shadow: 0 4px 14px rgba(201,169,97,.3); }
    .btn-print:hover { background: #b8944a; transform: translateY(-1px); }

    @media (max-width: 600px) {
        .topbar { padding: .8rem 1rem; }
        .topbar .logo span { display: none; }
        .hero h1 { font-size: 1.5rem; }
        .page { padding: 0 .75rem 3rem; }
        .card-body { padding: 1.2rem; }
        .btn-row { padding: 1rem 1.2rem 1.5rem; }
    }

    /* ── Print styles ── */
    @media print {
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { background: #fff !important; }
        .topbar  { display: none !important; }
        .btn-row { display: none !important; }
        .hero    { display: none !important; }
        .section:last-of-type { display: none !important; } /* hide Important Information */
        .page { margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
        .card { box-shadow: none !important; border-radius: 0 !important; }
        .detail-row { page-break-inside: avoid; }
        .section { page-break-inside: avoid; }

        /* Print header — logo + name */
        .print-header { display: flex !important; }
    }
    .print-header {
        display: none;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem 2rem 1rem;
        border-bottom: 2px solid #C9A961;
        margin-bottom: 1rem;
    }
    .print-header img {
        width: 52px; height: 52px;
        border-radius: 50%; object-fit: cover;
    }
    .print-header-text h2 {
        font-size: 1.2rem; font-weight: 800; color: #2C3E50; margin: 0;
    }
    .print-header-text p {
        font-size: 0.82rem; color: #888; margin: 0.1rem 0 0;
    }
    </style>
</head>
<body>

<!-- Navbar -->
<div class="topbar">
    <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Back to Home</a>
    <span class="logo">
        <img src="uploads/logo/logo.png" alt="">
        <span>Paradise Hotel &amp; Resort</span>
    </span>
    <span class="user"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars(getFirstName() ?? getUsername()); ?></span>
</div>

<div class="page">
<div class="card">

    <!-- Print header (only visible when printing) -->
    <div class="print-header">
        <img src="uploads/logo/logo.png" alt="Paradise Hotel & Resort" onerror="this.style.display='none'">
        <div class="print-header-text">
            <h2>Paradise Hotel &amp; Resort</h2>
            <p>Reservation Details &mdash; <?php echo $refNum; ?></p>
        </div>
    </div>

    <!-- Hero -->
    <div class="hero">
        <div class="chk"><i class="fas fa-check"></i></div>
        <h1><?php echo $isPavilion ? 'Pavilion Booked!' : 'Booking Confirmed!'; ?></h1>
        <p>Thank you for choosing Paradise Hotel &amp; Resort<?php if (!empty($data['email'])): ?>.
            A confirmation has been sent to <?php echo htmlspecialchars($data['email']); ?><?php endif; ?>.</p>
        <div class="ref-box">Confirmation #: <?php echo $refNum; ?></div>
    </div>

    <div class="card-body">

        <!-- Booking Details -->
        <div class="section">
            <div class="section-title">
                <i class="fas fa-<?php echo $isPavilion ? 'archway' : 'calendar-check'; ?>"></i>
                <?php echo $isPavilion ? 'Event Details' : 'Reservation Details'; ?>
            </div>
            <div class="detail-box">
                <div class="detail-row"><span class="lbl">Guest Name:</span><span class="val"><?php echo htmlspecialchars($data['guest_name']); ?></span></div>
                <div class="detail-row"><span class="lbl">Email:</span><span class="val"><?php echo htmlspecialchars($data['email']); ?></span></div>
                <?php if (!empty($data['phone'])): ?>
                <div class="detail-row"><span class="lbl">Phone:</span><span class="val"><?php echo htmlspecialchars($data['phone']); ?></span></div>
                <?php endif; ?>

                <?php if ($isPavilion): ?>
                    <div class="detail-row"><span class="lbl">Event Date:</span><span class="val"><?php echo date('l, F j, Y', strtotime($data['event_date'])); ?></span></div>
                    <?php if (!empty($data['event_type'])): ?>
                    <div class="detail-row"><span class="lbl">Event Type:</span><span class="val"><?php echo htmlspecialchars($data['event_type']); ?></span></div>
                    <?php endif; ?>
                    <?php if (!empty($data['event_time'])): ?>
                    <div class="detail-row"><span class="lbl">Time:</span><span class="val"><?php echo htmlspecialchars($data['event_time']); ?><?php echo !empty($data['event_end_time']) ? ' – ' . htmlspecialchars($data['event_end_time']) : ''; ?></span></div>
                    <?php endif; ?>
                    <div class="detail-row"><span class="lbl">Number of Guests:</span><span class="val"><?php echo number_format($data['pax']); ?> guests</span></div>
                <?php else: ?>
                    <div class="detail-row"><span class="lbl">Room:</span><span class="val"><?php echo htmlspecialchars($data['room_type']); ?></span></div>
                    <div class="detail-row"><span class="lbl">Check-in Date:</span><span class="val"><?php echo date('F j, Y', strtotime($data['checkin_date'])); ?></span></div>
                    <div class="detail-row"><span class="lbl">Check-out Date:</span><span class="val"><?php echo date('F j, Y', strtotime($data['checkout_date'])); ?></span></div>
                    <div class="detail-row"><span class="lbl">Number of Guests:</span><span class="val"><?php echo $data['guests']; ?> guests</span></div>
                    <?php if (!empty($options['individual_room']['room_number'])): ?>
                    <div class="detail-row"><span class="lbl">Room Number:</span><span class="val"><?php echo htmlspecialchars($options['individual_room']['room_number']); ?></span></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="section">
            <div class="section-title"><i class="fas fa-credit-card"></i> Payment Information</div>
            <div class="detail-box">
                <div class="detail-row"><span class="lbl">Payment Method:</span><span class="val"><?php echo $methodLabel; ?></span></div>
                <div class="detail-row"><span class="lbl">Payment Type:</span><span class="val"><?php echo $data['payment_percentage']; ?>% Payment</span></div>
                <div class="detail-row"><span class="lbl">Amount Paid:</span><span class="val">₱<?php echo number_format($data['payment_amount'], 2); ?></span></div>
                <div class="detail-row"><span class="lbl">Total Amount:</span><span class="val">₱<?php echo number_format($data['price'], 2); ?></span></div>
                <?php if ($data['payment_percentage'] == 50): ?>
                <div class="detail-row"><span class="lbl">Remaining Balance:</span><span class="val">₱<?php echo number_format($data['price'] - $data['payment_amount'], 2); ?></span></div>
                <?php endif; ?>
                <div class="detail-row">
                    <span class="lbl">Payment Status:</span>
                    <span class="val val-<?php echo $data['payment_status']; ?>"><?php echo ucfirst($data['payment_status']); ?></span>
                </div>
            </div>
        </div>

        <!-- Important Information -->
        <div class="section">
            <div class="section-title"><i class="fas fa-info-circle"></i> Important Information</div>
            <div class="info-list">
                <?php if ($isPavilion): ?>
                    <div class="info-item"><i class="fas fa-clock"></i><div><strong>Arrival:</strong> Please arrive 30 minutes before your scheduled event time.<br><strong>Venue:</strong> Paradise Pavilion &amp; Event Space</div></div>
                    <div class="info-item"><i class="fas fa-users"></i><div><strong>Guest Count:</strong> Final guest count must be confirmed 48 hours before the event.</div></div>
                <?php else: ?>
                    <div class="info-item"><i class="fas fa-clock"></i><div><strong>Check-in Time:</strong> 3:00 PM<br><strong>Check-out Time:</strong> 12:00 PM</div></div>
                    <div class="info-item"><i class="fas fa-utensils"></i><div><strong>Complimentary Breakfast:</strong> Included for all guests.<br><strong>Serving Time:</strong> 6:00 AM - 10:00 AM</div></div>
                    <?php if ($data['payment_percentage'] == 50): ?>
                    <div class="info-item"><i class="fas fa-money-bill-wave"></i><div><strong>Remaining Balance:</strong> ₱<?php echo number_format($data['price'] - $data['payment_amount'], 2); ?><br><strong>Payment Due:</strong> Upon check-in</div></div>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="info-item"><i class="fas fa-phone"></i><div><strong>Contact Us:</strong> +1 (555) 123-4567<br><strong>Email:</strong> reservations@paradisehotel.com</div></div>
            </div>
        </div>

    </div><!-- /card-body -->

    <!-- Buttons -->
    <div class="btn-row">
        <a href="index.php" class="btn btn-home"><i class="fas fa-home"></i> Back to Home</a>
        <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Print Confirmation</button>
    </div>

</div><!-- /card -->
</div><!-- /page -->

</body>
</html>
