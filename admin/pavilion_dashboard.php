<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/pavilion_pricing.php';
requireAdminLogin();
$pageTitle   = 'Pavilion Management';
$currentPage = 'pavilion_dashboard';

// Handle AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $conn   = getDBConnection();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'block_date') {
            $date   = $_POST['date']   ?? '';
            $reason = trim($_POST['reason'] ?? 'Blocked by admin');
            if (!$date) throw new Exception('Date is required.');
            $stmt = $conn->prepare("INSERT INTO pavilion_slots (event_date, max_pax, price, note, status) VALUES (?,0,0,?,'blocked') ON DUPLICATE KEY UPDATE status='blocked', note=VALUES(note)");
            $stmt->bind_param('ss', $date, $reason); $stmt->execute(); $stmt->close();
            echo json_encode(['success' => true, 'message' => "Date $date blocked."]);

        } elseif ($action === 'unblock_date') {
            $date = $_POST['date'] ?? '';
            $stmt = $conn->prepare("DELETE FROM pavilion_slots WHERE event_date=? AND status='blocked'");
            $stmt->bind_param('s', $date); $stmt->execute(); $stmt->close();
            echo json_encode(['success' => true, 'message' => "Date $date unblocked."]);

        } elseif ($action === 'set_price') {
            $price = floatval($_POST['price'] ?? 0);
            if ($price <= 0) throw new Exception('Price must be greater than 0.');
            $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('pavilion_price',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
            $stmt->bind_param('s', $price); $stmt->execute(); $stmt->close();
            echo json_encode(['success' => true, 'message' => "Pavilion price updated to ₱" . number_format($price, 2)]);

        } elseif ($action === 'set_event_prices') {
            $types = $_POST['event_types'] ?? [];
            $prices = $_POST['prices'] ?? [];
            if (empty($types)) throw new Exception('No event types provided.');
            $stmt = $conn->prepare("INSERT INTO pavilion_event_prices (event_type, base_price) VALUES (?,?) ON DUPLICATE KEY UPDATE base_price=VALUES(base_price)");
            foreach ($types as $i => $type) {
                $bp = floatval($prices[$i] ?? 0);
                if ($bp < 0) continue;
                $stmt->bind_param('sd', $type, $bp); $stmt->execute();
            }
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Event prices updated.']);

        } elseif ($action === 'cancel_booking') {
            $id = intval($_POST['id'] ?? 0);
            $stmt = $conn->prepare("UPDATE pavilion_bookings SET status='cancelled' WHERE id=?");
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            echo json_encode(['success' => true, 'message' => "Booking #$id cancelled."]);

        } elseif ($action === 'confirm_booking') {
            $id = intval($_POST['id'] ?? 0);
            $stmt = $conn->prepare("UPDATE pavilion_bookings SET status='confirmed' WHERE id=?");
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            echo json_encode(['success' => true, 'message' => "Booking #$id confirmed."]);

        } elseif ($action === 'manual_booking') {
            $date  = $_POST['date']  ?? '';
            $guest = trim($_POST['guest_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $pax   = intval($_POST['pax'] ?? 0);
            $event = trim($_POST['event_type'] ?? '');
            $time  = trim($_POST['event_time'] ?? '');
            if (!$date || !$guest || $pax <= 0) throw new Exception('Date, guest name, and pax are required.');
            if (strtotime($date) < strtotime('today')) throw new Exception('Date must be today or in the future.');
            // Check not blocked
            $chk = $conn->prepare("SELECT id FROM pavilion_slots WHERE event_date=? AND status='blocked' LIMIT 1");
            $chk->bind_param('s', $date); $chk->execute();
            if ($chk->get_result()->num_rows > 0) throw new Exception('That date is blocked.');
            $chk->close();
            // Check not already booked
            $chk2 = $conn->prepare("SELECT id FROM pavilion_bookings WHERE event_date=? AND status IN ('confirmed','pending') LIMIT 1");
            $chk2->bind_param('s', $date); $chk2->execute();
            if ($chk2->get_result()->num_rows > 0) throw new Exception('That date already has a booking.');
            $chk2->close();
            $pricing = calculatePavilionPrice($event, $pax, $conn);
            $price   = $pricing['total'];
            $stmt = $conn->prepare("INSERT INTO pavilion_bookings (event_date, guest_name, email, phone, pax, event_type, event_time, price, status, created_at) VALUES (?,?,?,?,?,?,?,?,'confirmed',NOW())");
            $stmt->bind_param('ssssissd', $date, $guest, $email, $phone, $pax, $event, $time, $price);
            $stmt->execute(); $stmt->close();
            echo json_encode(['success' => true, 'message' => "Booking created for $guest on $date."]);

        } elseif ($action === 'get_events') {
            // Returns blocked dates + bookings for calendar
            $blocked = [];
            $bQ = $conn->query("SELECT event_date FROM pavilion_slots WHERE status='blocked'");
            while ($r = $bQ->fetch_assoc()) $blocked[] = $r['event_date'];
            $booked = [];
            $bkQ = $conn->query("SELECT event_date, guest_name, pax, event_type, event_time, status, id FROM pavilion_bookings WHERE status IN ('confirmed','pending') ORDER BY event_date ASC");
            while ($r = $bkQ->fetch_assoc()) $booked[] = $r;
            echo json_encode(['success' => true, 'blocked' => $blocked, 'booked' => $booked]);

        } elseif ($action === 'get_bookings') {
            $f  = $_POST['filter'] ?? 'upcoming';
            $s  = trim($_POST['search'] ?? '');
            $w  = "WHERE 1=1";
            if ($f === 'upcoming')   $w .= " AND b.event_date >= CURDATE() AND b.status IN ('confirmed','pending')";
            elseif ($f === 'past')   $w .= " AND b.event_date < CURDATE() AND b.status != 'cancelled'";
            elseif ($f === 'cancelled') $w .= " AND b.status='cancelled'";
            if ($s) { $se = $conn->real_escape_string($s); $w .= " AND (b.guest_name LIKE '%$se%' OR b.email LIKE '%$se%' OR b.event_type LIKE '%$se%')"; }
            $res = $conn->query("SELECT b.* FROM pavilion_bookings b $w ORDER BY b.event_date DESC");
            $rows = [];
            while ($r = $res->fetch_assoc()) $rows[] = $r;
            echo json_encode(['success' => true, 'bookings' => $rows]);

        } else {
            throw new Exception('Unknown action.');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    $conn->close(); exit;
}

// Load events for initial render
$conn   = getDBConnection();

// JSON endpoint for AJAX refresh
if (isset($_GET['json_events'])) {
    header('Content-Type: application/json');
    $blocked = [];
    $bQ = $conn->query("SELECT event_date FROM pavilion_slots WHERE status='blocked'");
    if ($bQ) while ($r = $bQ->fetch_assoc()) $blocked[] = $r['event_date'];
    $booked = [];
    $bkQ = $conn->query("SELECT event_date, guest_name, pax, event_type, IFNULL(event_time,'') as event_time, status, id FROM pavilion_bookings WHERE status IN ('confirmed','pending') ORDER BY event_date ASC");
    if ($bkQ) {
        while ($r = $bkQ->fetch_assoc()) $booked[] = $r;
    } else {
        $bkQ2 = $conn->query("SELECT ps.event_date, pb.guest_name, pb.pax, pb.event_type, '' as event_time, pb.status, pb.id FROM pavilion_bookings pb JOIN pavilion_slots ps ON pb.slot_id=ps.id WHERE pb.status IN ('confirmed','pending') ORDER BY ps.event_date ASC");
        if ($bkQ2) while ($r = $bkQ2->fetch_assoc()) $booked[] = $r;
    }
    $conn->close();
    echo json_encode(['success' => true, 'blocked' => $blocked, 'booked' => $booked]);
    exit;
}

$blockedDates = [];
$bQ = $conn->query("SELECT event_date, note FROM pavilion_slots WHERE status='blocked' ORDER BY event_date ASC");
if ($bQ) while ($r = $bQ->fetch_assoc()) $blockedDates[] = $r;

$bookedDates = [];
// Try new schema (event_date column on pavilion_bookings)
$bkQ = $conn->query("SELECT event_date, guest_name, pax, event_type, IFNULL(event_time,'') as event_time, status, id FROM pavilion_bookings WHERE status IN ('confirmed','pending') ORDER BY event_date ASC");
if ($bkQ) {
    while ($r = $bkQ->fetch_assoc()) $bookedDates[] = $r;
} else {
    // Fallback: join with slots for old schema
    $bkQ2 = $conn->query("SELECT ps.event_date, pb.guest_name, pb.pax, pb.event_type, '' as event_time, pb.status, pb.id FROM pavilion_bookings pb JOIN pavilion_slots ps ON pb.slot_id=ps.id WHERE pb.status IN ('confirmed','pending') ORDER BY ps.event_date ASC");
    if ($bkQ2) while ($r = $bkQ2->fetch_assoc()) $bookedDates[] = $r;
}

// Price setting
$pavilionPrice = 5000;
$priceQ = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key='pavilion_price' LIMIT 1");
if ($priceQ && $priceQ->num_rows > 0) $pavilionPrice = floatval($priceQ->fetch_assoc()['setting_value']);

// Load per-event-type prices for admin pricing tab
$pvEventPrices = getPavilionEventPrices($conn);

// Stats
$bkStatsQ = $conn->query("SELECT COALESCE(SUM(CASE WHEN status='confirmed' THEN price END),0) as revenue FROM pavilion_bookings");
$bkStats = $bkStatsQ ? $bkStatsQ->fetch_assoc() : ['revenue' => 0];
$conn->close();
?>
<?php include 'template_header.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.pv-wrap { display:flex; flex-direction:column; gap:1.5rem; }
.pv-title { display:flex; align-items:center; gap:.75rem; color:#2C3E50; font-size:1.9rem; font-weight:700; }
.pv-title i { color:#C9A961; }

/* Stats row */
.pv-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:1rem; }
.pv-stat { background:#fff; border-radius:16px; padding:1.4rem 1.6rem; box-shadow:0 4px 20px rgba(0,0,0,.07); text-align:center; }
.pv-stat-n { font-size:2rem; font-weight:800; color:#C9A961; }
.pv-stat-l { font-size:.82rem; font-weight:700; color:#888; margin-top:.25rem; text-transform:uppercase; letter-spacing:.5px; }

/* Main grid */
.pv-main { display:grid; grid-template-columns:1fr 380px; gap:1.5rem; align-items:start; }
@media(max-width:1100px){ .pv-main{ grid-template-columns:1fr; } }

/* Calendar */
.pv-cal { background:#fff; border-radius:18px; box-shadow:0 4px 20px rgba(0,0,0,.07); overflow:hidden; }
.pv-topbar { display:flex; align-items:center; justify-content:space-between; padding:1.4rem 2rem; background:linear-gradient(135deg,#2C3E50,#3d5166); }
.pv-month-label { font-size:1.6rem; font-weight:700; color:#fff; }
.pv-room-label  { font-size:.9rem; color:rgba(255,255,255,.6); margin-top:.2rem; }
.pv-nav { background:rgba(255,255,255,.15); border:none; color:#fff; width:40px; height:40px; border-radius:50%; cursor:pointer; font-size:1rem; display:flex; align-items:center; justify-content:center; transition:background .2s; }
.pv-nav:hover { background:rgba(201,169,97,.7); }
.pv-today-btn { background:linear-gradient(135deg,#C9A961,#8B7355); border:none; color:#fff; padding:.5rem 1.2rem; border-radius:50px; font-size:.85rem; font-weight:700; cursor:pointer; }
.pv-wdays { display:grid; grid-template-columns:repeat(7,1fr); background:#f4f6f8; border-bottom:2px solid #e9ecef; }
.pv-wd { text-align:center; padding:.75rem .5rem; font-size:.8rem; font-weight:700; color:#6c757d; text-transform:uppercase; letter-spacing:.8px; }
.pv-body { display:grid; grid-template-columns:repeat(7,1fr); }
.pv-cell { min-height:110px; border-right:1px solid #edf0f2; border-bottom:1px solid #edf0f2; padding:.6rem .7rem; cursor:pointer; transition:background .15s; display:flex; flex-direction:column; }
.pv-cell:hover { background:#f8fafc; }
.pv-cell.other-m { background:#fafbfc; }
.pv-cell.other-m .pv-dn { color:#c8cdd2; }
.pv-cell.is-today { background:#fffcf0; }
.pv-cell.is-today .pv-dn { background:linear-gradient(135deg,#C9A961,#8B7355); color:#fff; border-radius:50%; width:30px; height:30px; display:flex; align-items:center; justify-content:center; font-weight:700; }
.pv-cell.is-sel { background:#eef6ff; outline:2px solid #3498db; outline-offset:-2px; }
.pv-dn { font-size:.95rem; font-weight:700; color:#2C3E50; width:30px; height:30px; display:flex; align-items:center; justify-content:center; margin-bottom:.3rem; flex-shrink:0; }
.pv-chip { font-size:.75rem; padding:.28rem .55rem; border-radius:6px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:2px; line-height:1.3; }
.chip-available { background:#e8f5e9; color:#2e7d32; border-left:4px solid #28a745; }
.chip-booked    { background:#d4edda; color:#155724; border-left:4px solid #1a6b2e; }
.chip-blocked   { background:#f8d7da; color:#721c24; border-left:4px solid #dc3545; }

/* Side panel */
.pv-side { display:flex; flex-direction:column; gap:1.2rem; }
.sc { background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,.07); padding:1.5rem; }
.sc-title { font-size:1rem; font-weight:700; color:#2C3E50; margin-bottom:1rem; display:flex; align-items:center; gap:.5rem; padding-bottom:.75rem; border-bottom:2px solid #f0f0f0; }
.sc-title i { color:#C9A961; }
.act-tabs { display:flex; gap:.5rem; margin-bottom:1.2rem; }
.act-tab { flex:1; padding:.55rem; border-radius:10px; border:2px solid #e0e0e0; background:#fff; font-size:.82rem; font-weight:700; cursor:pointer; text-align:center; color:#666; transition:all .2s; }
.act-tab.on { border-color:#C9A961; background:linear-gradient(135deg,#C9A961,#8B7355); color:#fff; }
.aform { display:none; }
.aform.show { display:block; }
.fg { display:flex; flex-direction:column; margin-bottom:.85rem; }
.fg label { font-size:.82rem; font-weight:700; color:#2C3E50; margin-bottom:.3rem; }
.fg input,.fg select,.fg textarea { padding:.65rem 1rem; border:2px solid #e0e0e0; border-radius:10px; font-size:.88rem; font-family:'Montserrat',sans-serif; transition:border-color .2s; }
.fg input:focus,.fg select:focus,.fg textarea:focus { outline:none; border-color:#C9A961; }
.abtn { width:100%; border:none; padding:.85rem; border-radius:50px; font-weight:700; font-size:.92rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:.5rem; margin-top:.4rem; }
.abtn-avail  { background:linear-gradient(135deg,#28a745,#1e7e34); color:#fff; }
.abtn-block  { background:linear-gradient(135deg,#dc3545,#c82333); color:#fff; }
.abtn-manual { background:linear-gradient(135deg,#007bff,#0056b3); color:#fff; }
.abtn:hover  { opacity:.9; transform:translateY(-1px); }

/* Day detail */
.dd-empty { color:#bbb; font-size:.88rem; text-align:center; padding:1.2rem .5rem; line-height:1.8; }
.dd-entry { border-radius:10px; padding:1rem; margin-bottom:.75rem; border-left:5px solid #C9A961; background:#f8f9fa; }
.dd-entry.dd-block { border-left-color:#dc3545; background:#fff5f5; }
.dd-entry.dd-booked { border-left-color:#28a745; background:#f0fff4; }
.dd-row { font-size:.87rem; color:#555; margin-bottom:.3rem; display:flex; align-items:center; gap:.4rem; }
.dd-row i { color:#C9A961; width:14px; }
.dd-row strong { color:#2C3E50; }
.dd-btns { display:flex; gap:.5rem; flex-wrap:wrap; margin-top:.7rem; }
.ddbtn { border:none; padding:.4rem .9rem; border-radius:50px; font-size:.78rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:.3rem; }
.ddbtn-cancel  { background:#dc3545; color:#fff; }
.ddbtn-unblock { background:#28a745; color:#fff; }
.ddbtn:hover   { opacity:.85; }

/* Legend */
.leg-list { display:flex; flex-direction:column; gap:.55rem; }
.leg-row  { display:flex; align-items:center; gap:.7rem; font-size:.88rem; color:#444; font-weight:600; }
.leg-sw   { width:14px; height:14px; border-radius:3px; flex-shrink:0; }

/* Toast */
#pvToast { position:fixed; top:50%; left:50%; transform:translate(-50%,-50%) scale(.85); z-index:9999; min-width:300px; max-width:460px; padding:1.3rem 1.7rem; border-radius:16px; font-size:.97rem; font-weight:600; display:flex; align-items:flex-start; gap:.8rem; box-shadow:0 20px 60px rgba(0,0,0,.25); opacity:0; pointer-events:none; transition:opacity .25s,transform .25s; line-height:1.5; }
#pvToast.show { opacity:1; pointer-events:auto; transform:translate(-50%,-50%) scale(1); }
#pvToast.tok  { background:#d4edda; color:#155724; border:1.5px solid #b7dfbb; }
#pvToast.terr { background:#f8d7da; color:#721c24; border:1.5px solid #f1b0b7; }
#pvToast i { font-size:1.2rem; flex-shrink:0; margin-top:.1rem; }
#pvOverlay { position:fixed; inset:0; background:rgba(0,0,0,.25); z-index:9998; display:none; }

/* Bookings table */
.bk-table { width:100%; border-collapse:collapse; font-size:.88rem; }
.bk-table th { background:#f4f6f8; padding:.7rem 1rem; text-align:left; font-weight:700; color:#2C3E50; border-bottom:2px solid #e9ecef; }
.bk-table td { padding:.7rem 1rem; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
.bk-table tr:hover td { background:#fafbfc; }
.badge { display:inline-block; padding:.25rem .65rem; border-radius:20px; font-size:.75rem; font-weight:700; }
.badge-confirmed { background:#d4edda; color:#155724; }
.badge-cancelled { background:#f8d7da; color:#721c24; }
.badge-available { background:#e8f5e9; color:#2e7d32; }
.badge-blocked   { background:#f8d7da; color:#721c24; }
/* Bookings filter tabs */
.pb-ftab { display:inline-block; padding:.45rem 1.1rem; border-radius:50px; border:2px solid #e0e0e0; background:#fff; font-size:.82rem; font-weight:700; cursor:pointer; color:#666; text-decoration:none; transition:all .2s; }
.pb-ftab.on { border-color:#C9A961; background:linear-gradient(135deg,#C9A961,#8B7355); color:#fff; }
.pb-search { flex:1; min-width:180px; padding:.55rem 1rem; border:2px solid #e0e0e0; border-radius:50px; font-size:.88rem; font-family:'Montserrat',sans-serif; }
.pb-search:focus { outline:none; border-color:#C9A961; }
</style>

<div id="pvOverlay"></div>
<div id="pvToast"><i></i><span id="pvToastText"></span></div>

<div class="pv-wrap">
<h1 class="pv-title"><i class="fas fa-archway"></i> Pavilion Management</h1>

<!-- Stats -->
<div class="pv-stats">
    <div class="pv-stat"><div class="pv-stat-n" id="statAvail">0</div><div class="pv-stat-l">Available Dates</div></div>
    <div class="pv-stat"><div class="pv-stat-n" id="statBooked">0</div><div class="pv-stat-l">Booked Events</div></div>
    <div class="pv-stat"><div class="pv-stat-n" id="statBlocked">0</div><div class="pv-stat-l">Blocked Dates</div></div>
    <div class="pv-stat"><div class="pv-stat-n" id="statMaxPax">—</div><div class="pv-stat-l">Max Pax (Next Avail)</div></div>
    <div class="pv-stat"><div class="pv-stat-n">₱<?php echo number_format($bkStats['revenue'] ?? 0); ?></div><div class="pv-stat-l">Total Revenue</div></div>
</div>

<!-- Calendar + Side -->
<div class="pv-main">
    <!-- Calendar -->
    <div class="pv-cal">
        <div class="pv-topbar">
            <div>
                <div class="pv-month-label" id="pvMonthLabel"></div>
                <div class="pv-room-label">Pavilion Event Calendar</div>
            </div>
            <div style="display:flex;align-items:center;gap:.75rem;">
                <button class="pv-nav" onclick="pvChangeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                <button class="pv-today-btn" onclick="pvGoToday()">Today</button>
                <button class="pv-nav" onclick="pvChangeMonth(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
        <div class="pv-wdays">
            <?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
            <div class="pv-wd"><?php echo $d; ?></div>
            <?php endforeach; ?>
        </div>
        <div class="pv-body" id="pvBody"></div>
    </div>

    <!-- Side Panel -->
    <div class="pv-side">
        <!-- Legend -->
        <div class="sc">
            <div class="sc-title"><i class="fas fa-info-circle"></i> Legend</div>
            <div class="leg-list">
                <div class="leg-row"><div class="leg-sw" style="background:#28a745;"></div> Available (default)</div>
                <div class="leg-row"><div class="leg-sw" style="background:#1a6b2e;"></div> Booked Event</div>
                <div class="leg-row"><div class="leg-sw" style="background:#dc3545;"></div> Blocked by Admin</div>
            </div>
        </div>

        <!-- Day Detail -->
        <div class="sc">
            <div class="sc-title"><i class="fas fa-calendar-day"></i> <span id="pvDdTitle">Click a date</span></div>
            <div id="pvDdBody"><div class="dd-empty"><i class="fas fa-hand-pointer" style="font-size:1.8rem;display:block;margin-bottom:.5rem;color:#ddd;"></i>Click a date to see details</div></div>
        </div>

        <!-- Actions -->
        <div class="sc">
            <div class="sc-title"><i class="fas fa-tools"></i> Actions</div>
            <div class="act-tabs">
                <button class="act-tab on" onclick="pvTab('block',this)"><i class="fas fa-ban"></i> Block Date</button>
                <button class="act-tab" onclick="pvTab('manual',this)"><i class="fas fa-user-plus"></i> Manual Book</button>
                <button class="act-tab" onclick="pvTab('price',this)"><i class="fas fa-peso-sign"></i> Pricing</button>
            </div>

            <!-- Block Date -->
            <form id="fBlock" class="aform show">
                <div class="fg"><label>Date *</label><input type="text" id="blockDate" name="date" placeholder="Pick date" required></div>
                <div class="fg"><label>Reason</label><input type="text" name="reason" placeholder="e.g. Private event, maintenance..."></div>
                <button type="submit" class="abtn abtn-block"><i class="fas fa-ban"></i> Block This Date</button>
            </form>

            <!-- Manual Booking -->
            <form id="fManual" class="aform">
                <div class="fg"><label>Date *</label><input type="text" id="manualDate" name="date" placeholder="Pick date" required></div>
                <div class="fg"><label>Guest / Organizer *</label><input type="text" name="guest_name" placeholder="Full name" required></div>
                <div class="fg"><label>Email</label><input type="email" name="email" placeholder="email@example.com"></div>
                <div class="fg"><label>Phone</label><input type="text" name="phone" placeholder="+63 9XX XXX XXXX"></div>
                <div class="fg"><label>Number of Guests *</label><input type="number" id="manualPax" name="pax" placeholder="e.g. 150" min="1" required oninput="updateManualPrice()"></div>
                <div class="fg"><label>Event Type</label>
                    <select id="manualEventType" name="event_type" onchange="updateManualPrice()">
                        <option value="">Select event type</option>
                        <option>Wedding</option><option>Corporate Event</option>
                        <option>Anniversary</option><option>Family Reunion</option>
                        <option>Birthday Party</option><option>Graduation</option><option>Other</option>
                    </select>
                </div>
                <div class="fg"><label>Start Time</label><input type="text" id="manualTime" name="event_time" placeholder="e.g. 10:00 AM"></div>
                <div id="manualPricePreview" style="display:none;background:#f8f9fa;border-radius:10px;padding:.75rem 1rem;margin-bottom:.85rem;font-size:.85rem;color:#2C3E50;">
                    <div style="display:flex;justify-content:space-between;"><span>Base Price:</span><span id="mpBase">—</span></div>
                    <div style="display:flex;justify-content:space-between;"><span>Guest Surcharge:</span><span id="mpSurcharge">—</span></div>
                    <div style="display:flex;justify-content:space-between;font-weight:800;color:#C9A961;margin-top:.4rem;border-top:1px solid #e0e0e0;padding-top:.4rem;"><span>Total:</span><span id="mpTotal">—</span></div>
                </div>
                <button type="submit" class="abtn abtn-manual"><i class="fas fa-calendar-check"></i> Create Booking</button>
            </form>

            <!-- Pricing Config -->
            <form id="fPrice" class="aform">
                <p style="font-size:.82rem;color:#888;margin-bottom:1rem;">Set base price per event type. Guest surcharge: ₱7,000 per 10 guests (rounded up).</p>
                <?php foreach ($pvEventPrices as $type => $basePrice): ?>
                <div class="fg">
                    <label><?php echo htmlspecialchars($type); ?> (₱)</label>
                    <input type="number" name="prices[]" value="<?php echo $basePrice; ?>" min="0" step="1000" required>
                    <input type="hidden" name="event_types[]" value="<?php echo htmlspecialchars($type); ?>">
                </div>
                <?php endforeach; ?>
                <button type="submit" class="abtn abtn-avail"><i class="fas fa-save"></i> Save Prices</button>
            </form>
        </div>
    </div>
</div>


</div><!-- .pv-wrap -->

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
let pvYear = new Date().getFullYear(), pvMonth = new Date().getMonth();
let pvSelDate = null;
let pvBlocked = <?php echo json_encode(array_column($blockedDates, 'event_date')); ?>;
let pvBlockedNotes = <?php echo json_encode(array_column($blockedDates, 'note', 'event_date')); ?>;
let pvBooked  = <?php echo json_encode($bookedDates); ?>;

const fpBlock  = flatpickr('#blockDate',  { dateFormat:'Y-m-d', onChange: d => { if(d[0]) { pvSelDate = d[0].toISOString().split('T')[0]; pvRender(); } } });
const fpManual = flatpickr('#manualDate', { dateFormat:'Y-m-d', onChange: d => { if(d[0]) { pvSelDate = d[0].toISOString().split('T')[0]; pvRender(); } } });
flatpickr('#manualTime', { enableTime:true, noCalendar:true, dateFormat:'h:i K', minuteIncrement:30, defaultHour:8 });

function pvTab(tab, btn) {
    document.querySelectorAll('.act-tab').forEach(t => t.classList.remove('on'));
    btn.classList.add('on');
    document.getElementById('fBlock').classList.toggle('show',  tab === 'block');
    document.getElementById('fManual').classList.toggle('show', tab === 'manual');
    document.getElementById('fPrice').classList.toggle('show',  tab === 'price');
}

function pvRender() {
    const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    document.getElementById('pvMonthLabel').textContent = MONTHS[pvMonth] + ' ' + pvYear;
    const firstDay = new Date(pvYear, pvMonth, 1).getDay();
    const daysInMonth = new Date(pvYear, pvMonth + 1, 0).getDate();
    const daysInPrev  = new Date(pvYear, pvMonth, 0).getDate();
    const today = new Date(); today.setHours(0,0,0,0);
    const body = document.getElementById('pvBody');
    body.innerHTML = '';
    const total = (firstDay + daysInMonth) <= 35 ? 35 : 42;

    let statBooked = 0, statBlocked = 0;

    for (let i = 0; i < total; i++) {
        let dayNum, dateObj, isCur = true;
        if (i < firstDay) { dayNum = daysInPrev - firstDay + i + 1; dateObj = new Date(pvYear, pvMonth - 1, dayNum); isCur = false; }
        else if (i >= firstDay + daysInMonth) { dayNum = i - firstDay - daysInMonth + 1; dateObj = new Date(pvYear, pvMonth + 1, dayNum); isCur = false; }
        else { dayNum = i - firstDay + 1; dateObj = new Date(pvYear, pvMonth, dayNum); }

        const ds = dateObj.toISOString().split('T')[0];
        const isBlocked = pvBlocked.includes(ds);
        const booking   = pvBooked.find(b => b.event_date === ds);
        const isToday   = dateObj.getTime() === today.getTime();
        const isSel     = pvSelDate === ds;

        if (isCur && isBlocked) statBlocked++;
        if (isCur && booking)   statBooked++;

        const cell = document.createElement('div');
        cell.className = 'pv-cell' + (!isCur ? ' other-m' : '') + (isToday ? ' is-today' : '') + (isSel ? ' is-sel' : '');

        const dn = document.createElement('div');
        dn.className = 'pv-dn'; dn.textContent = dayNum;
        cell.appendChild(dn);

        if (isCur) {
            if (isBlocked) {
                const chip = document.createElement('div');
                chip.className = 'pv-chip chip-blocked';
                chip.textContent = '🚫 Blocked';
                cell.appendChild(chip);
            } else if (booking) {
                const chip = document.createElement('div');
                chip.className = 'pv-chip chip-booked';
                chip.textContent = '🎉 ' + (booking.event_type || booking.guest_name || 'Booked');
                cell.appendChild(chip);
            } else if (dateObj >= today) {
                const chip = document.createElement('div');
                chip.className = 'pv-chip chip-available';
                chip.textContent = '✓ Available';
                cell.appendChild(chip);
            }
        }

        cell.addEventListener('click', () => pvClickDay(ds, isBlocked, booking, dateObj));
        body.appendChild(cell);
    }

    pvUpdateStats(statBooked, statBlocked);
}

function pvClickDay(ds, isBlocked, booking, dateObj) {
    pvSelDate = ds;
    pvRender();
    const d = new Date(ds + 'T00:00:00');
    document.getElementById('pvDdTitle').textContent = d.toLocaleDateString('en-US', { weekday:'long', month:'long', day:'numeric', year:'numeric' });
    const body = document.getElementById('pvDdBody');
    const today = new Date(); today.setHours(0,0,0,0);

    if (isBlocked) {
        body.innerHTML = `<div class="dd-entry dd-block">
            <div class="dd-row"><i class="fas fa-ban"></i><strong>BLOCKED</strong></div>
            <div class="dd-row"><i class="fas fa-sticky-note"></i>${pvBlockedNotes[ds] || 'No reason given'}</div>
            <div class="dd-btns"><button class="ddbtn ddbtn-unblock" onclick="pvAction('unblock_date','${ds}')"><i class="fas fa-unlock"></i> Unblock</button></div>
        </div>`;
    } else if (booking) {
        const bStatus = booking.status === 'confirmed'
            ? '<span class="badge badge-confirmed">Confirmed</span>'
            : '<span class="badge" style="background:#fff3cd;color:#856404;">Pending</span>';
        body.innerHTML = `<div class="dd-entry dd-booked">
            <div class="dd-row"><i class="fas fa-user"></i><strong>${esc(booking.guest_name)}</strong></div>
            <div class="dd-row"><i class="fas fa-users"></i>${booking.pax} guests</div>
            <div class="dd-row"><i class="fas fa-star"></i>${esc(booking.event_type || 'Event')}</div>
            ${booking.event_time ? `<div class="dd-row"><i class="fas fa-clock"></i>${esc(booking.event_time)}</div>` : ''}
            <div class="dd-row"><i class="fas fa-info-circle"></i>${bStatus}</div>
            <div class="dd-btns">
                ${booking.status === 'pending' ? `<button class="ddbtn" style="background:#28a745;color:#fff;" onclick="pvConfirmBk(${booking.id})"><i class="fas fa-check"></i> Confirm</button>` : ''}
                <button class="ddbtn ddbtn-cancel" onclick="pvCancelBk(${booking.id})"><i class="fas fa-times"></i> Cancel</button>
            </div>
        </div>`;
    } else if (dateObj >= today) {
        body.innerHTML = `<div class="dd-entry">
            <div class="dd-row"><i class="fas fa-check-circle" style="color:#28a745;"></i><strong style="color:#155724;">Available</strong></div>
            <div class="dd-row" style="color:#888;font-size:.85rem;">No booking for this date. Use "Block Date" to make it unavailable, or "Manual Book" to add a booking.</div>
        </div>`;
    } else {
        body.innerHTML = `<div class="dd-empty">Past date — no actions available.</div>`;
    }

    // Pre-fill date pickers
    fpBlock.setDate(ds); fpManual.setDate(ds);
}

function pvAction(action, date, id) {
    const msgs = { unblock_date: 'Unblock this date?', cancel_booking: 'Cancel this booking?' };
    if (!confirm(msgs[action] || 'Confirm?')) return;
    const fd = new FormData();
    fd.append('action', action); fd.append('date', date);
    if (id) fd.append('id', id);
    pvFetch(fd);
}

function pvFetch(fd) {
    fetch('pavilion_dashboard.php', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
        .then(r => r.json())
        .then(data => { pvToast(data.success, data.message); if (data.success) pvRefresh(); })
        .catch(() => pvToast(false, 'Request failed.'));
}

function pvRefresh() {
    fetch('pavilion_dashboard.php?json_events=1')
        .then(r => r.json())
        .then(data => {
            pvBlocked = data.blocked || [];
            pvBooked  = data.booked  || [];
            pvBlockedNotes = {};
            pvRender();
            if (pvSelDate) {
                const isBlocked = pvBlocked.includes(pvSelDate);
                const booking   = pvBooked.find(b => b.event_date === pvSelDate);
                pvClickDay(pvSelDate, isBlocked, booking, new Date(pvSelDate + 'T00:00:00'));
            }
            pvBkLoad();
        });
}

function pvToast(ok, text) {
    const t = document.getElementById('pvToast'), ov = document.getElementById('pvOverlay');
    t.querySelector('i').className = ok ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    document.getElementById('pvToastText').textContent = text;
    t.className = ok ? 'show tok' : 'show terr';
    ov.style.display = 'block';
    ov.onclick = () => { t.classList.remove('show'); ov.style.display = 'none'; };
    clearTimeout(t._t);
    t._t = setTimeout(() => { t.classList.remove('show'); ov.style.display = 'none'; }, ok ? 3500 : 6000);
}

function pvUpdateStats(booked, blocked) {
    const today = new Date().toISOString().split('T')[0];
    const upcomingBooked  = booked  !== undefined ? booked  : pvBooked.filter(b => b.event_date >= today).length;
    const upcomingBlocked = blocked !== undefined ? blocked : pvBlocked.filter(d => d >= today).length;
    document.getElementById('statBooked').textContent  = upcomingBooked;
    document.getElementById('statBlocked').textContent = upcomingBlocked;
    document.getElementById('statAvail').textContent   = '∞';
    document.getElementById('statMaxPax').textContent  = 'Open';
}

let pvBkCurrentFilter = 'upcoming';
let pvBkDebTimer = null;

function pvBkFilter(f, btn) {
    pvBkCurrentFilter = f;
    document.querySelectorAll('#pvBkTabs .pb-ftab').forEach(b => b.classList.remove('on'));
    btn.classList.add('on');
    pvBkLoad();
}

function pvBkDebounce() {
    clearTimeout(pvBkDebTimer);
    pvBkDebTimer = setTimeout(pvBkLoad, 350);
}

function pvBkLoad() {
    const wrap = document.getElementById('pvBkTableWrap');
    wrap.innerHTML = '<div style="text-align:center;padding:2rem;color:#bbb;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    const fd = new FormData();
    fd.append('action', 'get_bookings');
    fd.append('filter', pvBkCurrentFilter);
    fd.append('search', document.getElementById('pvBkSearch').value.trim());
    fetch('pavilion_dashboard.php', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { wrap.innerHTML = '<div style="text-align:center;padding:2rem;color:#c00;">Failed to load bookings.</div>'; return; }
            const rows = data.bookings;
            if (!rows.length) {
                wrap.innerHTML = '<div style="text-align:center;padding:3rem;color:#bbb;"><i class="fas fa-calendar-times" style="font-size:3rem;display:block;margin-bottom:1rem;color:#e0e0e0;"></i><p>No bookings found.</p></div>';
                return;
            }
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const days   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
            wrap.innerHTML = `<table class="bk-table">
                <thead><tr><th>#</th><th>Event Date</th><th>Guest</th><th>Event</th><th>Time</th><th>Guests</th><th>Price</th><th>Status</th><th>Booked On</th><th>Action</th></tr></thead>
                <tbody>${rows.map(b => {
                    const d   = new Date(b.event_date + 'T00:00:00');
                    const df  = months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
                    const dow = days[d.getDay()];
                    const cd  = new Date(b.created_at);
                    const cdf = months[cd.getMonth()] + ' ' + cd.getDate() + ', ' + cd.getFullYear();
                    const badge = b.status === 'confirmed'
                        ? '<span class="badge badge-confirmed">Confirmed</span>'
                        : b.status === 'pending'
                        ? '<span class="badge" style="background:#fff3cd;color:#856404;">Pending</span>'
                        : '<span class="badge badge-cancelled">Cancelled</span>';
                    const action = b.status !== 'cancelled'
                        ? `${b.status === 'pending' ? `<button class="ddbtn" style="background:#28a745;color:#fff;margin-right:4px;" onclick="pvConfirmBk(${b.id})"><i class="fas fa-check"></i></button>` : ''}
                           <button class="ddbtn ddbtn-cancel" onclick="pvCancelBk(${b.id})"><i class="fas fa-times"></i> Cancel</button>`
                        : '<span style="color:#bbb;font-size:.82rem;">—</span>';
                    const phone = b.phone ? `<br><small style="color:#aaa;">${esc(b.phone)}</small>` : '';
                    return `<tr id="pvbkrow-${b.id}">
                        <td>#${b.id}</td>
                        <td><strong>${dow}, ${df}</strong></td>
                        <td><strong>${esc(b.guest_name)}</strong><br><small style="color:#888;">${esc(b.email)}</small>${phone}</td>
                        <td>${esc(b.event_type || '—')}</td>
                        <td>${esc(b.event_time || '—')}</td>
                        <td>${Number(b.pax).toLocaleString()}</td>
                        <td><strong style="color:#C9A961;">₱${Number(b.price).toLocaleString()}</strong></td>
                        <td>${badge}</td>
                        <td>${cdf}</td>
                        <td>${action}</td>
                    </tr>`;
                }).join('')}</tbody>
            </table>`;
        })
        .catch(() => { wrap.innerHTML = '<div style="text-align:center;padding:2rem;color:#c00;">Request failed.</div>'; });
}

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function pvConfirmBk(id) {
    if (!confirm('Confirm this booking?')) return;
    const fd = new FormData(); fd.append('action','confirm_booking'); fd.append('id', id);
    pvFetch(fd);
}

function pvCancelBk(id) {
    if (!confirm('Cancel this pavilion booking?')) return;
    const fd = new FormData(); fd.append('action','cancel_booking'); fd.append('id', id);
    fetch('pavilion_dashboard.php', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
        .then(r => r.json())
        .then(data => { pvToast(data.success, data.message); if (data.success) { pvBkLoad(); pvRefresh(); } })
        .catch(() => pvToast(false, 'Request failed.'));
}

function pvChangeMonth(d) { pvMonth += d; if(pvMonth>11){pvMonth=0;pvYear++;} if(pvMonth<0){pvMonth=11;pvYear--;} pvRender(); }
function pvGoToday() { pvYear = new Date().getFullYear(); pvMonth = new Date().getMonth(); pvRender(); }

// Form submits
document.getElementById('fBlock').addEventListener('submit', function(e) {
    e.preventDefault(); const fd = new FormData(this); fd.set('action','block_date'); pvFetch(fd);
});
document.getElementById('fManual').addEventListener('submit', function(e) {
    e.preventDefault(); const fd = new FormData(this); fd.set('action','manual_booking'); pvFetch(fd);
});
document.getElementById('fPrice').addEventListener('submit', function(e) {
    e.preventDefault(); const fd = new FormData(this); fd.set('action','set_event_prices'); pvFetch(fd);
});

pvRender();
pvBkLoad();

// ── Manual booking price preview ──
const adminEventPrices = <?php echo json_encode($pvEventPrices); ?>;
const adminSurchargePerTen = <?php echo PAVILION_GUEST_SURCHARGE_PER_10; ?>;

function updateManualPrice() {
    const etype  = document.getElementById('manualEventType').value;
    const guests = parseInt(document.getElementById('manualPax').value) || 0;
    const preview = document.getElementById('manualPricePreview');
    if (!etype || guests < 1) { preview.style.display = 'none'; return; }
    const base      = adminEventPrices[etype] ?? adminEventPrices['Other'] ?? 15000;
    const surcharge = Math.ceil(guests / 10) * adminSurchargePerTen;
    const total     = base + surcharge;
    document.getElementById('mpBase').textContent      = '₱' + base.toLocaleString();
    document.getElementById('mpSurcharge').textContent = '₱' + surcharge.toLocaleString();
    document.getElementById('mpTotal').textContent     = '₱' + total.toLocaleString();
    preview.style.display = 'block';
}
</script>

<?php include 'template_footer.php'; ?>
