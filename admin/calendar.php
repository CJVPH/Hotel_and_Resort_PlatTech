<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireAdminLogin();
$pageTitle   = 'Calendar Management';
$currentPage = 'calendar';

$message = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
    $action = $_POST['action'] ?? '';
    $conn   = getDBConnection();

    if ($action === 'cancel_reservation') {
        $rid  = intval($_POST['reservation_id']);
        $stmt = $conn->prepare("UPDATE reservations SET status='cancelled' WHERE id=?");
        $stmt->bind_param('i', $rid); $stmt->execute(); $stmt->close();
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success'=>true,'message'=>"Reservation #$rid cancelled."]); $conn->close(); exit; }
        $message = "Reservation #$rid cancelled."; $msgType = 'success';
    }

    if ($action === 'block_dates') {
        $rn = $_POST['room_number'] ?? ''; $rt = $_POST['room_type'] ?? '';
        $from = $_POST['from_date'] ?? ''; $to = $_POST['to_date'] ?? '';
        $rsn  = trim($_POST['reason'] ?? 'Admin block');
        if ($rn && $from && $to) {
            $roomNumPat  = '%"room_number":"' . $conn->real_escape_string($rn) . '"%';
            $adminBlkPat = '%"admin_block":true%';
            $chk = $conn->prepare(
                "SELECT id, guest_name FROM reservations
                 WHERE status IN ('confirmed','pending')
                   AND checkin_date < ?
                   AND checkout_date > ?
                   AND options LIKE ?
                   AND options NOT LIKE ?
                 LIMIT 1"
            );
            $chk->bind_param('ssss', $to, $from, $roomNumPat, $adminBlkPat);
            $chk->execute();
            $chkRes = $chk->get_result();
            if ($chkRes->num_rows > 0) {
                $conflict = $chkRes->fetch_assoc();
                $msg = "Cannot block Room $rn — it has an existing booking by \"{$conflict['guest_name']}\" that overlaps those dates. Cancel the booking first.";
                if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>$msg]); $chk->close(); $conn->close(); exit; }
                $message = $msg; $msgType = 'error';
            } else {
                $opts = json_encode(['individual_room'=>['room_number'=>$rn,'room_type'=>$rt,'room_id'=>strtolower($rt).'-'.$rn,'room_name'=>$rt.' Room '.$rn],'admin_block'=>true,'reason'=>$rsn]);
                $stmt = $conn->prepare("INSERT INTO reservations (user_id,guest_name,email,phone,checkin_date,checkout_date,room_type,guests,price,options,status,created_at) VALUES (NULL,'ADMIN BLOCK','admin@system','N/A',?,?,?,0,0,?,'confirmed',NOW())");
                $stmt->bind_param('ssss',$from,$to,$rt,$opts); $stmt->execute(); $stmt->close();
                $msg = "Room $rn blocked from $from to $to.";
                if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success'=>true,'message'=>$msg]); $chk->close(); $conn->close(); exit; }
                $message = $msg; $msgType = 'success';
            }
            $chk->close();
        } else {
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Please fill all fields.']); $conn->close(); exit; }
            $message = 'Please fill all fields.'; $msgType = 'error';
        }
    }

    if ($action === 'manual_booking') {
        $rn = $_POST['room_number'] ?? ''; $rt = $_POST['room_type'] ?? '';
        $from = $_POST['from_date'] ?? ''; $to = $_POST['to_date'] ?? '';
        $guest = trim($_POST['guest_name'] ?? ''); $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? ''); $price = floatval($_POST['price'] ?? 0);
        if ($rn && $from && $to && $guest) {
            $roomNumPat = '%"room_number":"' . $conn->real_escape_string($rn) . '"%';
            $chk = $conn->prepare(
                "SELECT id, guest_name, options FROM reservations
                 WHERE status IN ('confirmed','pending')
                   AND checkin_date < ? AND checkout_date > ?
                   AND options LIKE ?
                 LIMIT 1"
            );
            $chk->bind_param('sss', $to, $from, $roomNumPat);
            $chk->execute();
            $chkRes = $chk->get_result();
            if ($chkRes->num_rows > 0) {
                $conflict = $chkRes->fetch_assoc();
                $cOpts    = json_decode($conflict['options'] ?? '{}', true);
                $isBlk    = !empty($cOpts['admin_block']);
                $who      = $isBlk ? 'an admin block' : "a booking by \"{$conflict['guest_name']}\"";
                $msg      = "Cannot book Room $rn — it already has $who overlapping those dates.";
                if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>$msg]); $chk->close(); $conn->close(); exit; }
                $message = $msg; $msgType = 'error';
            } else {
                $opts = json_encode(['individual_room'=>['room_number'=>$rn,'room_type'=>$rt,'room_id'=>strtolower($rt).'-'.$rn,'room_name'=>$rt.' Room '.$rn],'manual_booking'=>true]);
                $stmt = $conn->prepare("INSERT INTO reservations (user_id,guest_name,email,phone,checkin_date,checkout_date,room_type,guests,price,options,status,created_at) VALUES (NULL,?,?,?,?,?,?,2,?,?,'confirmed',NOW())");
                $stmt->bind_param('ssssssdss',$guest,$email,$phone,$from,$to,$rt,$price,$opts); $stmt->execute(); $stmt->close();
                $msg = "Booking created for $guest in Room $rn.";
                if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success'=>true,'message'=>$msg]); $chk->close(); $conn->close(); exit; }
                $message = $msg; $msgType = 'success';
            }
            $chk->close();
        } else {
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Guest name and dates are required.']); $conn->close(); exit; }
            $message = 'Guest name and dates are required.'; $msgType = 'error';
        }
    }
    $conn->close();
}

try {
    $conn = getDBConnection();
    $res  = $conn->query("SELECT id,guest_name,checkin_date,checkout_date,room_type,price,status,options FROM reservations WHERE status IN ('confirmed','pending') ORDER BY checkin_date ASC");
    $reservations = [];
    while ($r = $res->fetch_assoc()) {
        $o = json_decode($r['options']??'{}',true);
        $r['room_number'] = $o['individual_room']['room_number'] ?? '';
        $r['is_block']    = !empty($o['admin_block']);
        $r['reason']      = $o['reason'] ?? '';
        $reservations[]   = $r;
    }
    $conn->close();
} catch(Exception $e){ $reservations=[]; }

// JSON endpoint for AJAX event refresh
if (isset($_GET['json_events'])) {
    header('Content-Type: application/json');
    echo json_encode(array_values(array_filter($reservations, fn($r) => $r['room_number'])));
    exit;
}

$bookedRooms = []; $blockedRooms = [];
foreach($reservations as $r){
    if($r['is_block']) $blockedRooms[$r['room_number']] = true;
    else $bookedRooms[$r['room_number']] = true;
}
$allRooms = [
    ['101','Regular'],['102','Regular'],['103','Regular'],['104','Regular'],['105','Regular'],['106','Regular'],
    ['201','Deluxe'], ['202','Deluxe'], ['203','Deluxe'], ['204','Deluxe'], ['205','Deluxe'], ['206','Deluxe'],
    ['301','VIP'],    ['302','VIP'],    ['303','VIP'],    ['304','VIP'],    ['305','VIP'],    ['306','VIP'],
];
?>
<?php include 'template_header.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
/* ── Layout ── */
.cm-wrap { display:flex; flex-direction:column; gap:1.5rem; }
.cm-title { display:flex; align-items:center; gap:.75rem; color:#2C3E50; font-size:1.9rem; font-weight:700; margin-bottom:.25rem; }
.cm-title i { color:#C9A961; }

/* ── Room Picker ── */
.room-picker { background:#fff; border-radius:18px; box-shadow:0 4px 20px rgba(0,0,0,.07); padding:1.5rem 2rem; }
.room-picker-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.2rem; flex-wrap:wrap; gap:1rem; }
.room-picker-top h3 { font-size:1.1rem; font-weight:700; color:#2C3E50; display:flex; align-items:center; gap:.5rem; margin:0; }
.room-picker-top h3 i { color:#C9A961; }
.rtype-tabs { display:flex; gap:.5rem; }
.rtype-tab { padding:.45rem 1.2rem; border-radius:50px; border:2px solid #e0e0e0; background:#fff; font-weight:700; font-size:.85rem; cursor:pointer; color:#666; transition:all .2s; }
.rtype-tab.on { border-color:#C9A961; background:linear-gradient(135deg,#C9A961,#8B7355); color:#fff; }

.room-grid { display:flex; flex-wrap:wrap; gap:.75rem; }
.rpill { position:relative; padding:.65rem 1.4rem; border-radius:50px; border:2px solid #e0e0e0; background:#fff; font-weight:700; font-size:1rem; cursor:pointer; color:#2C3E50; transition:all .2s; user-select:none; }
.rpill:hover { border-color:#C9A961; transform:translateY(-1px); }
.rpill.on { border-color:#C9A961; background:linear-gradient(135deg,#C9A961,#8B7355); color:#fff; box-shadow:0 4px 12px rgba(201,169,97,.4); }
.rpill.booked { border-color:#ffc107; }
.rpill.blocked { border-color:#dc3545; }
.rpill .rdot { position:absolute; top:3px; right:5px; width:8px; height:8px; border-radius:50%; }
.rdot-booked  { background:#ffc107; }
.rdot-blocked { background:#dc3545; }
.rpill-label { font-size:.65rem; font-weight:600; display:block; text-align:center; margin-top:.1rem; opacity:.7; }

/* ── Main area: big calendar + side panel ── */
.cm-main { display:grid; grid-template-columns:1fr 380px; gap:1.5rem; align-items:start; }
@media(max-width:1100px){ .cm-main{ grid-template-columns:1fr; } }

/* ── Big Calendar ── */
.big-cal { background:#fff; border-radius:18px; box-shadow:0 4px 20px rgba(0,0,0,.07); overflow:hidden; }

.cal-topbar { display:flex; align-items:center; justify-content:space-between; padding:1.4rem 2rem; background:linear-gradient(135deg,#2C3E50,#3d5166); }
.cal-topbar-left { display:flex; flex-direction:column; }
.cal-month-label { font-size:1.6rem; font-weight:700; color:#fff; line-height:1.1; }
.cal-room-label  { font-size:.9rem; color:rgba(255,255,255,.65); margin-top:.2rem; }
.cal-topbar-right { display:flex; align-items:center; gap:.75rem; }
.cal-nav { background:rgba(255,255,255,.15); border:none; color:#fff; width:40px; height:40px; border-radius:50%; cursor:pointer; font-size:1rem; display:flex; align-items:center; justify-content:center; transition:background .2s; }
.cal-nav:hover { background:rgba(201,169,97,.7); }
.cal-today { background:linear-gradient(135deg,#C9A961,#8B7355); border:none; color:#fff; padding:.5rem 1.2rem; border-radius:50px; font-size:.85rem; font-weight:700; cursor:pointer; }

.cal-wdays { display:grid; grid-template-columns:repeat(7,1fr); background:#f4f6f8; border-bottom:2px solid #e9ecef; }
.cal-wd { text-align:center; padding:.75rem .5rem; font-size:.8rem; font-weight:700; color:#6c757d; text-transform:uppercase; letter-spacing:.8px; }

.cal-body { display:grid; grid-template-columns:repeat(7,1fr); }
.cal-cell {
    min-height:130px;
    border-right:1px solid #edf0f2;
    border-bottom:1px solid #edf0f2;
    padding:.6rem .7rem;
    cursor:pointer;
    transition:background .15s;
    display:flex;
    flex-direction:column;
}
.cal-cell:hover { background:#f8fafc; }
.cal-cell.other-m { background:#fafbfc; }
.cal-cell.other-m .day-n { color:#c8cdd2; }
.cal-cell.is-today { background:#fffcf0; }
.cal-cell.is-today .day-n {
    background:linear-gradient(135deg,#C9A961,#8B7355);
    color:#fff; border-radius:50%;
    width:32px; height:32px;
    display:flex; align-items:center; justify-content:center;
    font-weight:700;
}
.cal-cell.is-selected { background:#eef6ff; outline:2px solid #3498db; outline-offset:-2px; }
.cal-cell.is-available { background:#f0fff4; }

.day-n { font-size:1rem; font-weight:700; color:#2C3E50; width:32px; height:32px; display:flex; align-items:center; justify-content:center; margin-bottom:.4rem; flex-shrink:0; }

.day-evts { display:flex; flex-direction:column; gap:3px; flex:1; }
.devt {
    font-size:.78rem;
    padding:.3rem .6rem;
    border-radius:6px;
    font-weight:600;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    line-height:1.3;
}
.devt-confirmed { background:#d4edda; color:#155724; border-left:4px solid #28a745; }
.devt-pending   { background:#fff3cd; color:#856404; border-left:4px solid #ffc107; }
.devt-blocked   { background:#f8d7da; color:#721c24; border-left:4px solid #dc3545; }
.devt-avail     { background:#e8f5e9; color:#2e7d32; font-style:italic; font-size:.72rem; }

/* ── Side Panel ── */
.side-col { display:flex; flex-direction:column; gap:1.2rem; }

.sc { background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,.07); padding:1.5rem; }
.sc-title { font-size:1rem; font-weight:700; color:#2C3E50; margin-bottom:1rem; display:flex; align-items:center; gap:.5rem; padding-bottom:.75rem; border-bottom:2px solid #f0f0f0; }
.sc-title i { color:#C9A961; }

/* Legend */
.leg-list { display:flex; flex-direction:column; gap:.6rem; }
.leg-row  { display:flex; align-items:center; gap:.75rem; font-size:.9rem; color:#444; font-weight:600; }
.leg-swatch { width:16px; height:16px; border-radius:4px; flex-shrink:0; }

/* Day detail */
.dd-empty { color:#bbb; font-size:.9rem; text-align:center; padding:1.5rem .5rem; line-height:1.8; }
.dd-entry { border-radius:10px; padding:1rem; margin-bottom:.75rem; border-left:5px solid #C9A961; background:#f8f9fa; }
.dd-entry.dd-block { border-left-color:#dc3545; background:#fff5f5; }
.dd-room  { font-size:1rem; font-weight:700; color:#2C3E50; margin-bottom:.3rem; }
.dd-guest { font-size:.88rem; color:#555; margin-bottom:.25rem; }
.dd-dates { font-size:.82rem; color:#888; margin-bottom:.6rem; }
.dd-price { font-size:.9rem; font-weight:700; color:#C9A961; margin-bottom:.6rem; }
.dd-btns  { display:flex; gap:.5rem; flex-wrap:wrap; }
.ddbtn { border:none; padding:.45rem 1rem; border-radius:50px; font-size:.8rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:.3rem; }
.ddbtn-cancel  { background:#dc3545; color:#fff; }
.ddbtn-unblock { background:#28a745; color:#fff; }
.ddbtn-view    { background:#6c757d; color:#fff; text-decoration:none; }
.ddbtn:hover   { opacity:.85; }

/* Action tabs */
.act-tabs { display:flex; gap:.5rem; margin-bottom:1.2rem; }
.act-tab { flex:1; padding:.6rem; border-radius:10px; border:2px solid #e0e0e0; background:#fff; font-size:.85rem; font-weight:700; cursor:pointer; text-align:center; color:#666; transition:all .2s; }
.act-tab.on { border-color:#C9A961; background:linear-gradient(135deg,#C9A961,#8B7355); color:#fff; }

.aform { display:none; }
.aform.show { display:block; }
.fg { display:flex; flex-direction:column; margin-bottom:.9rem; }
.fg label { font-size:.82rem; font-weight:700; color:#2C3E50; margin-bottom:.35rem; }
.fg input, .fg select {
    padding:.7rem 1rem; border:2px solid #e0e0e0; border-radius:10px;
    font-size:.9rem; font-family:'Montserrat',sans-serif; transition:border-color .2s;
}
.fg input:focus, .fg select:focus { outline:none; border-color:#C9A961; }
.abtn { width:100%; border:none; padding:.9rem; border-radius:50px; font-weight:700; font-size:.95rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:.5rem; margin-top:.5rem; }
.abtn-block  { background:linear-gradient(135deg,#dc3545,#c82333); color:#fff; }
.abtn-manual { background:linear-gradient(135deg,#28a745,#1e7e34); color:#fff; }
.abtn:hover  { opacity:.9; transform:translateY(-1px); }

/* Toast notification */
#calToast {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.85);
    z-index: 9999;
    min-width: 320px;
    max-width: 480px;
    padding: 1.4rem 1.8rem;
    border-radius: 16px;
    font-size: 1rem;
    font-weight: 600;
    display: flex;
    align-items: flex-start;
    gap: .85rem;
    box-shadow: 0 20px 60px rgba(0,0,0,.25);
    opacity: 0;
    pointer-events: none;
    transition: opacity .25s ease, transform .25s ease;
    line-height: 1.5;
}
#calToast.show {
    opacity: 1;
    pointer-events: auto;
    transform: translate(-50%, -50%) scale(1);
}
#calToast.toast-ok  { background: #d4edda; color: #155724; border: 1.5px solid #b7dfbb; }
#calToast.toast-err { background: #f8d7da; color: #721c24; border: 1.5px solid #f1b0b7; }
#calToast i { font-size: 1.3rem; flex-shrink: 0; margin-top: .1rem; }
#calToastOverlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.25);
    z-index: 9998;
    display: none;
}

/* Alert */
.cm-alert { padding:1rem 1.4rem; border-radius:12px; margin-bottom:1.2rem; display:flex; align-items:center; gap:.75rem; font-weight:600; font-size:.95rem; }
.cm-alert-ok  { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.cm-alert-err { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

/* No room placeholder */
.no-room { text-align:center; padding:4rem 2rem; color:#bbb; background:#fff; border-radius:18px; box-shadow:0 4px 20px rgba(0,0,0,.07); }
.no-room i { font-size:4rem; display:block; margin-bottom:1rem; color:#e0e0e0; }
.no-room p { font-size:1.1rem; font-weight:600; }
</style>

<?php if($message): ?>
<div class="cm-alert <?php echo $msgType==='success'?'cm-alert-ok':'cm-alert-err'; ?>">
    <i class="fas fa-<?php echo $msgType==='success'?'check-circle':'exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<div class="cm-wrap">
<h1 class="cm-title"><i class="fas fa-calendar-alt"></i> Calendar Management</h1>

<!-- Room Picker -->
<div class="room-picker">
    <div class="room-picker-top">
        <h3><i class="fas fa-bed"></i> Select a Room to Manage</h3>
        <div class="rtype-tabs">
            <button class="rtype-tab on" onclick="filterType('all',this)">All</button>
            <button class="rtype-tab" onclick="filterType('Regular',this)">Regular</button>
            <button class="rtype-tab" onclick="filterType('Deluxe',this)">Deluxe</button>
            <button class="rtype-tab" onclick="filterType('VIP',this)">VIP</button>
        </div>
    </div>
    <div class="room-grid" id="roomGrid">
    <?php foreach($allRooms as [$num,$type]):
        $isBlocked = isset($blockedRooms[$num]);
        $isBooked  = isset($bookedRooms[$num]);
        $cls = $isBlocked ? 'blocked' : ($isBooked ? 'booked' : '');
        $dot = $isBlocked ? '<span class="rdot rdot-blocked"></span>' : ($isBooked ? '<span class="rdot rdot-booked"></span>' : '');
        $lbl = $isBlocked ? 'Blocked' : ($isBooked ? 'Booked' : 'Free');
    ?>
    <div class="rpill <?php echo $cls; ?>" data-room="<?php echo $num; ?>" data-type="<?php echo $type; ?>" onclick="selectRoom('<?php echo $num; ?>','<?php echo $type; ?>')">
        <?php echo $dot; ?>
        <?php echo $num; ?>
        <span class="rpill-label"><?php echo $lbl; ?></span>
    </div>
    <?php endforeach; ?>
    </div>
</div>

<!-- Calendar + Side (hidden until room picked) -->
<div id="calMain" style="display:none;" class="cm-main">

    <!-- BIG CALENDAR -->
    <div class="big-cal">
        <div class="cal-topbar">
            <div class="cal-topbar-left">
                <div class="cal-month-label" id="calMonthLabel"></div>
                <div class="cal-room-label"  id="calRoomLabel">Select a room</div>
            </div>
            <div class="cal-topbar-right">
                <button class="cal-nav" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                <button class="cal-today" onclick="goToday()">Today</button>
                <button class="cal-nav" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
        <div class="cal-wdays">
            <?php foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d): ?>
            <div class="cal-wd"><?php echo $d; ?></div>
            <?php endforeach; ?>
        </div>
        <div class="cal-body" id="calBody"></div>
    </div>

    <!-- SIDE PANEL -->
    <div class="side-col">

        <!-- Legend -->
        <div class="sc">
            <div class="sc-title"><i class="fas fa-info-circle"></i> Legend</div>
            <div class="leg-list">
                <div class="leg-row"><div class="leg-swatch" style="background:#28a745;border-left:4px solid #1a6b2e;"></div> Confirmed Booking</div>
                <div class="leg-row"><div class="leg-swatch" style="background:#ffc107;border-left:4px solid #b38600;"></div> Pending Booking</div>
                <div class="leg-row"><div class="leg-swatch" style="background:#dc3545;border-left:4px solid #9b1c2a;"></div> Blocked by Admin</div>
                <div class="leg-row"><div class="leg-swatch" style="background:#e8f5e9;border:1px solid #a5d6a7;"></div> Available</div>
            </div>
        </div>

        <!-- Day Detail -->
        <div class="sc" id="dayDetailCard">
            <div class="sc-title"><i class="fas fa-calendar-day"></i> <span id="ddTitle">Click a date</span></div>
            <div id="ddBody">
                <div class="dd-empty"><i class="fas fa-hand-pointer" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#ddd;"></i>Click any date on the calendar to see details and take action</div>
            </div>
        </div>

        <!-- Actions -->
        <div class="sc">
            <div class="sc-title"><i class="fas fa-tools"></i> Actions — <span id="actRoomLabel" style="color:#C9A961;">select a room</span></div>
            <div class="act-tabs">
                <button class="act-tab on" onclick="switchTab('block',this)"><i class="fas fa-ban"></i> Block Dates</button>
                <button class="act-tab" onclick="switchTab('manual',this)"><i class="fas fa-calendar-plus"></i> Manual Booking</button>
            </div>

            <form id="fBlock" class="aform show">
                <input type="hidden" name="action" value="block_dates">
                <input type="hidden" name="room_number" id="bRoomNum">
                <input type="hidden" name="room_type"   id="bRoomType">

                <div class="fg"><label>From Date</label><input type="text" name="from_date" id="bFrom" placeholder="Pick start date" required></div>
                <div class="fg"><label>To Date</label><input type="text" name="to_date" id="bTo" placeholder="Pick end date" required></div>
                <div class="fg"><label>Reason (optional)</label><input type="text" name="reason" placeholder="e.g. Maintenance, private event..."></div>
                <button type="submit" class="abtn abtn-block"><i class="fas fa-ban"></i> Block These Dates</button>
            </form>

            <form id="fManual" class="aform">
                <input type="hidden" name="action" value="manual_booking">
                <input type="hidden" name="room_number" id="mRoomNum">
                <input type="hidden" name="room_type"   id="mRoomType">

                <div class="fg"><label>Guest Name *</label><input type="text" name="guest_name" placeholder="Full name" required></div>
                <div class="fg"><label>Email</label><input type="email" name="email" placeholder="guest@email.com"></div>
                <div class="fg"><label>Phone</label><input type="text" name="phone" placeholder="+63 9XX XXX XXXX"></div>
                <div class="fg"><label>Check-in *</label><input type="text" name="from_date" id="mFrom" placeholder="Pick check-in" required></div>
                <div class="fg"><label>Check-out *</label><input type="text" name="to_date" id="mTo" placeholder="Pick check-out" required></div>
                <div class="fg"><label>Price (₱)</label><input type="number" name="price" id="mPrice" placeholder="Auto-calculated" step="0.01"></div>
                <button type="submit" class="abtn abtn-manual"><i class="fas fa-calendar-plus"></i> Create Booking</button>
            </form>
        </div>

    </div>
</div>

<!-- Placeholder before room is selected -->
<div id="noRoomPlaceholder" class="no-room">
    <i class="fas fa-bed"></i>
    <p>Select a room above to open its calendar</p>
</div>

</div><!-- .cm-wrap -->

<div id="calToastOverlay"></div>
<div id="calToast"><i></i><span id="calToastText"></span></div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
const ALL_EVENTS = <?php echo json_encode(array_values(array_filter($reservations, fn($r) => $r['room_number']))); ?>;
let curYear = new Date().getFullYear(), curMonth = new Date().getMonth();
let selDate = null, selRoom = null, selType = null;

const fpBFrom = flatpickr('#bFrom', { dateFormat:'Y-m-d', onChange: d => { if(d[0]){ const n=new Date(d[0]); n.setDate(n.getDate()+1); fpBTo.set('minDate',n); } } });
const fpBTo   = flatpickr('#bTo',   { dateFormat:'Y-m-d' });
const fpMFrom = flatpickr('#mFrom', { dateFormat:'Y-m-d', onChange: d => { if(d[0]){ const n=new Date(d[0]); n.setDate(n.getDate()+1); fpMTo.set('minDate',n); autoPrice(); } } });
const fpMTo   = flatpickr('#mTo',   { dateFormat:'Y-m-d', onChange: autoPrice });

function autoPrice() {
    const f=document.getElementById('mFrom').value, t=document.getElementById('mTo').value;
    if(!f||!t||!selType) return;
    const nights=Math.ceil((new Date(t)-new Date(f))/86400000);
    const r={Regular:1500,Deluxe:2500,VIP:4000};
    document.getElementById('mPrice').value = (r[selType]||0)*nights;
}

function filterType(type, btn) {
    document.querySelectorAll('.rtype-tab').forEach(t=>t.classList.remove('on'));
    btn.classList.add('on');
    document.querySelectorAll('.rpill').forEach(p => p.style.display=(type==='all'||p.dataset.type===type)?'':'none');
}

function selectRoom(num, type) {
    selRoom=num; selType=type;
    document.querySelectorAll('.rpill').forEach(p=>p.classList.remove('on'));
    document.querySelector(`.rpill[data-room="${num}"]`).classList.add('on');
    document.getElementById('calMain').style.display='';
    document.getElementById('noRoomPlaceholder').style.display='none';
    document.getElementById('calRoomLabel').textContent = type+' · Room '+num;
    document.getElementById('actRoomLabel').textContent = type+' Room '+num;
    ['bRoomNum','mRoomNum'].forEach(id=>document.getElementById(id).value=num);
    ['bRoomType','mRoomType'].forEach(id=>document.getElementById(id).value=type);
    selDate=null; renderCal();
}

function renderCal() {
    if(!selRoom) return;
    const MONTHS=['January','February','March','April','May','June','July','August','September','October','November','December'];
    document.getElementById('calMonthLabel').textContent = MONTHS[curMonth]+' '+curYear;

    const firstDay=new Date(curYear,curMonth,1).getDay();
    const daysInMonth=new Date(curYear,curMonth+1,0).getDate();
    const daysInPrev=new Date(curYear,curMonth,0).getDate();
    const today=new Date(); today.setHours(0,0,0,0);
    const body=document.getElementById('calBody');
    body.innerHTML='';

    const roomEvts=ALL_EVENTS.filter(e=>e.room_number===selRoom);
    const evtsOn=ds=>roomEvts.filter(e=>e.checkin_date<=ds&&e.checkout_date>ds);
    const total=(firstDay+daysInMonth)<=35?35:42;

    for(let i=0;i<total;i++){
        let dayNum,dateObj,isCur=true;
        if(i<firstDay){ dayNum=daysInPrev-firstDay+i+1; dateObj=new Date(curYear,curMonth-1,dayNum); isCur=false; }
        else if(i>=firstDay+daysInMonth){ dayNum=i-firstDay-daysInMonth+1; dateObj=new Date(curYear,curMonth+1,dayNum); isCur=false; }
        else{ dayNum=i-firstDay+1; dateObj=new Date(curYear,curMonth,dayNum); }

        const ds=dateObj.toISOString().split('T')[0];
        const evts=evtsOn(ds);
        const isToday=dateObj.getTime()===today.getTime();
        const isSel=selDate===ds;
        const isAvail=isCur&&evts.length===0&&dateObj>=today;

        const cell=document.createElement('div');
        cell.className='cal-cell'
            +(!isCur?' other-m':'')
            +(isToday?' is-today':'')
            +(isSel?' is-selected':'')
            +(isAvail?' is-available':'');

        // Day number
        const dn=document.createElement('div');
        dn.className='day-n'; dn.textContent=dayNum;
        cell.appendChild(dn);

        // Events
        const ew=document.createElement('div');
        ew.className='day-evts';
        if(evts.length===0&&isCur&&dateObj>=today){
            const av=document.createElement('div');
            av.className='devt devt-avail'; av.textContent='✓ Available';
            ew.appendChild(av);
        }
        evts.forEach(e=>{
            const ev=document.createElement('div');
            ev.className='devt devt-'+(e.is_block?'blocked':e.status);
            ev.textContent=e.is_block?'🚫 Blocked — '+(e.reason||'Admin'):'🛏 '+e.guest_name;
            ev.title=e.is_block?(e.reason||'Blocked'):e.guest_name+' | '+fmt(e.checkin_date)+' → '+fmt(e.checkout_date);
            ew.appendChild(ev);
        });
        cell.appendChild(ew);
        cell.addEventListener('click',()=>clickDay(ds,evts,dateObj));
        body.appendChild(cell);
    }
}

function clickDay(ds, evts, dateObj) {
    selDate=ds; renderCal();
    const d=new Date(ds+'T00:00:00');
    document.getElementById('ddTitle').textContent=d.toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric',year:'numeric'});
    const body=document.getElementById('ddBody');

    if(!evts.length){
        body.innerHTML=`<div class="dd-empty"><i class="fas fa-check-circle" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#28a745;"></i><strong style="color:#155724;">Room is Available</strong><br><span style="font-size:.85rem;">No bookings on this date.</span></div>`;
    } else {
        body.innerHTML='';
        evts.forEach(e=>{
            const el=document.createElement('div');
            el.className='dd-entry'+(e.is_block?' dd-block':'');
            el.innerHTML=`
                <div class="dd-room"><i class="fas fa-${e.is_block?'ban':'bed'}" style="color:${e.is_block?'#dc3545':'#C9A961'};margin-right:.4rem;"></i>${e.room_type} Room ${e.room_number}</div>
                <div class="dd-guest">${e.is_block?'<strong style="color:#dc3545;">BLOCKED</strong>'+(e.reason?' — '+e.reason:''):'<i class="fas fa-user" style="color:#C9A961;margin-right:.3rem;"></i>'+e.guest_name}</div>
                <div class="dd-dates"><i class="fas fa-calendar-alt" style="margin-right:.3rem;"></i>${fmt(e.checkin_date)} → ${fmt(e.checkout_date)}</div>
                ${!e.is_block?`<div class="dd-price">₱${parseFloat(e.price).toLocaleString()}</div>`:''}
                <div class="dd-btns">
                    ${e.is_block
                        ?`<button class="ddbtn ddbtn-unblock" onclick="doCancel(${e.id},'Remove this block?')"><i class="fas fa-unlock"></i> Unblock</button>`
                        :`<a href="reservations.php" class="ddbtn ddbtn-view"><i class="fas fa-eye"></i> View</a>
                          <button class="ddbtn ddbtn-cancel" onclick="doCancel(${e.id},'Cancel booking for ${e.guest_name}?')"><i class="fas fa-times"></i> Cancel</button>`
                    }
                </div>`;
            body.appendChild(el);
        });
    }

    // Pre-fill forms with clicked date
    fpBFrom.setDate(ds); const nx=new Date(ds+'T00:00:00'); nx.setDate(nx.getDate()+1); const nxs=nx.toISOString().split('T')[0];
    fpBTo.setDate(nxs); fpMFrom.setDate(ds); fpMTo.setDate(nxs);
}

function doCancel(id, msg) {
    if (!confirm(msg)) return;
    const fd = new FormData();
    fd.append('action', 'cancel_reservation');
    fd.append('reservation_id', id);
    calAjax(fd, 'cancel');
}

function calAjax(fd, type) {
    fetch('calendar.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        showCalMsg(data.success, data.message);
        if (data.success) {
            // Reload events from server then re-render
            refreshEvents();
        }
    })
    .catch(() => showCalMsg(false, 'Request failed. Please try again.'));
}

function showCalMsg(ok, text) {
    const toast = document.getElementById('calToast');
    const overlay = document.getElementById('calToastOverlay');
    const icon = toast.querySelector('i');
    document.getElementById('calToastText').textContent = text;
    toast.className = ok ? 'show toast-ok' : 'show toast-err';
    icon.className = ok ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    overlay.style.display = 'block';
    const hide = () => { toast.classList.remove('show'); overlay.style.display = 'none'; };
    overlay.onclick = hide;
    clearTimeout(toast._t);
    toast._t = setTimeout(hide, ok ? 3500 : 6000);
}

function refreshEvents() {
    fetch('calendar.php?json_events=1')
        .then(r => r.json())
        .then(data => {
            ALL_EVENTS.length = 0;
            data.forEach(e => ALL_EVENTS.push(e));
            renderCal();
            // Reset day detail
            document.getElementById('ddTitle').textContent = 'Click a date';
            document.getElementById('ddBody').innerHTML = '<div class="dd-empty"><i class="fas fa-hand-pointer" style="font-size:2rem;display:block;margin-bottom:.5rem;color:#ddd;"></i>Click any date on the calendar to see details and take action</div>';
        });
}

document.getElementById('fBlock').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch('calendar.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        showCalMsg(data.success, data.message);
        if (data.success) { this.querySelector('[name="reason"]').value = ''; refreshEvents(); }
    })
    .catch(() => showCalMsg(false, 'Request failed. Please try again.'));
});

document.getElementById('fManual').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch('calendar.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        showCalMsg(data.success, data.message);
        if (data.success) { this.reset(); document.getElementById('mRoomNum').value=selRoom; document.getElementById('mRoomType').value=selType; refreshEvents(); }
    })
    .catch(() => showCalMsg(false, 'Request failed. Please try again.'));
});

function fmt(s){ return new Date(s+'T00:00:00').toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}); }
function switchTab(tab,btn){ document.querySelectorAll('.act-tab').forEach(t=>t.classList.remove('on')); btn.classList.add('on'); document.getElementById('fBlock').classList.toggle('show',tab==='block'); document.getElementById('fManual').classList.toggle('show',tab==='manual'); }
function changeMonth(d){ curMonth+=d; if(curMonth>11){curMonth=0;curYear++;} if(curMonth<0){curMonth=11;curYear--;} renderCal(); }
function goToday(){ curYear=new Date().getFullYear(); curMonth=new Date().getMonth(); renderCal(); }
</script>
<?php include 'template_footer.php'; ?>
