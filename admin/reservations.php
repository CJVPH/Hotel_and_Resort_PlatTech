<?php
require_once '../config/database.php';
require_once '../config/auth.php';

requireAdminLogin();

// Handle AJAX status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $reservationId = intval($_POST['reservation_id'] ?? 0);
    $action = $_POST['action'];
    $result = ['success' => false, 'message' => 'Invalid request'];

    if ($reservationId > 0) {
        try {
            $conn = getDBConnection();
            if ($action === 'confirm') {
                $stmt = $conn->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ?");
                $stmt->bind_param("i", $reservationId);
                $stmt->execute();
                $result = ['success' => true, 'new_status' => 'confirmed'];
                $stmt->close();
            } elseif ($action === 'cancel') {
                $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
                $stmt->bind_param("i", $reservationId);
                $stmt->execute();
                $result = ['success' => true, 'new_status' => 'cancelled'];
                $stmt->close();
            }
            $conn->close();
        } catch (Exception $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        }
    }
    echo json_encode($result);
    exit;
}

// Get reservations with pagination
$page = intval($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$statusFilter = $_GET['status'] ?? '';
$searchTerm = $_GET['search'] ?? '';

try {
    $conn = getDBConnection();

    // Count pending room reservations
    $pendingRooms = $conn->query("SELECT COUNT(*) as c FROM reservations WHERE status = 'pending'")->fetch_assoc()['c'];

    // Count pending pavilion bookings
    $pendingPavilion = 0;
    $pvCheck = $conn->query("SHOW TABLES LIKE 'pavilion_bookings'");
    if ($pvCheck && $pvCheck->num_rows > 0) {
        $pendingPavilion = $conn->query("SELECT COUNT(*) as c FROM pavilion_bookings WHERE status = 'pending'")->fetch_assoc()['c'];
    }

    // ── Room reservations ──
    $roomWhere = []; $rParams = []; $rTypes = '';
    if (!empty($statusFilter)) { $roomWhere[] = "r.status = ?"; $rParams[] = $statusFilter; $rTypes .= 's'; }
    if (!empty($searchTerm))   { $roomWhere[] = "(r.guest_name LIKE ? OR r.email LIKE ? OR r.phone LIKE ?)"; $sp = "%{$searchTerm}%"; $rParams[] = $sp; $rParams[] = $sp; $rParams[] = $sp; $rTypes .= 'sss'; }
    $rWhere = $roomWhere ? 'WHERE ' . implode(' AND ', $roomWhere) : '';

    $rSql  = "SELECT r.*, u.username, 'room' AS booking_type FROM reservations r LEFT JOIN users u ON r.user_id = u.id {$rWhere} ORDER BY r.created_at DESC";
    $rStmt = $conn->prepare($rSql);
    if ($rParams) $rStmt->bind_param($rTypes, ...$rParams);
    $rStmt->execute();
    $roomRows = $rStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $rStmt->close();

    // ── Pavilion bookings ──
    $pvRows = [];
    $pvCheck2 = $conn->query("SHOW TABLES LIKE 'pavilion_bookings'");
    if ($pvCheck2 && $pvCheck2->num_rows > 0) {
        $pvWhere = []; $pvParams = []; $pvTypes = '';
        if (!empty($statusFilter)) { $pvWhere[] = "status = ?"; $pvParams[] = $statusFilter; $pvTypes .= 's'; }
        if (!empty($searchTerm))   { $pvWhere[] = "(guest_name LIKE ? OR email LIKE ? OR phone LIKE ?)"; $sp = "%{$searchTerm}%"; $pvParams[] = $sp; $pvParams[] = $sp; $pvParams[] = $sp; $pvTypes .= 'sss'; }
        $pvW   = $pvWhere ? 'WHERE ' . implode(' AND ', $pvWhere) : '';
        $pvSql = "SELECT *, 'pavilion' AS booking_type FROM pavilion_bookings {$pvW} ORDER BY created_at DESC";
        $pvStmt = $conn->prepare($pvSql);
        if ($pvParams) $pvStmt->bind_param($pvTypes, ...$pvParams);
        $pvStmt->execute();
        $pvRows = $pvStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $pvStmt->close();
    }

    // ── Merge and sort by created_at DESC ──
    $allRows = array_merge($roomRows, $pvRows);
    usort($allRows, fn($a,$b) => strcmp($b['created_at'], $a['created_at']));

    $totalReservations = count($allRows);
    $totalPages        = ceil($totalReservations / $limit);
    $reservations      = array_slice($allRows, $offset, $limit);

    $conn->close();
    
} catch (Exception $e) {
    error_log("Reservations page error: " . $e->getMessage());
    $reservations = [];
    $totalReservations = 0;
    $totalPages = 0;
    $pendingRooms = 0;
    $pendingPavilion = 0;
}

$pageTitle = 'Reservations';
$currentPage = 'reservations';
?>

<?php include 'template_header.php'; ?>
<!-- Page specific styles -->
<style>
.filters { background: white; padding: 2rem; border-radius: 15px; margin-bottom: 2rem; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); display: flex; gap: 1rem; align-items: end; flex-wrap: wrap; }
.filter-group { flex: 1; min-width: 200px; }
.filter-group label { display: block; color: #2C3E50; font-weight: 600; margin-bottom: 0.5rem; }
.filter-group select, .filter-group input { width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1rem; transition: all 0.3s ease; }
.filter-group select:focus, .filter-group input:focus { outline: none; border-color: #C9A961; }
.filter-actions { display: flex; gap: 0.5rem; }
.btn-filter { background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; }
.btn-filter:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(201, 169, 97, 0.3); }
.btn-clear { background: #6c757d; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; cursor: pointer; font-weight: 600; text-decoration: none; transition: all 0.3s ease; }
.btn-clear:hover { background: #5a6268; transform: translateY(-2px); }

/* Table Enhancements */
.table-container { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); margin-top: 2rem; }
.admin-table { width: 100%; border-collapse: collapse; }
.admin-table thead { background: linear-gradient(135deg, #2C3E50 0%, #34495E 100%); color: white; }
.admin-table thead th { padding: 1.25rem; text-align: left; font-weight: 600; font-size: 0.95rem; letter-spacing: 0.5px; text-transform: uppercase; }
.admin-table tbody tr { border-bottom: 1px solid #e0e0e0; transition: all 0.3s ease; }
.admin-table tbody tr:hover { background: #f8f9fa; }
.admin-table tbody td { padding: 1.25rem; vertical-align: middle; }
.admin-table tbody td:first-child { font-weight: 600; color: #C9A961; }
.reservation-details { font-size: 0.85rem; color: #666; margin-top: 0.5rem; line-height: 1.6; }
.reservation-details div { margin-bottom: 0.25rem; }
.reservation-details i { color: #C9A961; width: 16px; margin-right: 0.5rem; }
.payment-info { font-size: 0.9rem; line-height: 1.8; }
.payment-amount { font-size: 1.1rem; font-weight: 700; color: #2C3E50; margin-bottom: 0.5rem; }
.payment-method { color: #666; font-size: 0.85rem; margin-top: 0.35rem; }
.status-badge { display: inline-block; padding: 0.4rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.reservation-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.btn-action { padding: 0.35rem 0.85rem; border: none; border-radius: 8px; font-size: 0.75rem; font-weight: 600; cursor: pointer; text-transform: uppercase; letter-spacing: 0.4px; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.35rem; }
.btn-confirm { background: #28a745; color: white; }
.btn-confirm:hover { background: #218838; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3); }
.btn-cancel { background: #dc3545; color: white; }
.btn-cancel:hover { background: #c82333; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3); }

.pagination { display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 2rem; padding: 1.5rem; flex-wrap: wrap; }
.pagination a, .pagination span { padding: 0.6rem 0.9rem; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; font-size: 0.9rem; }
.pagination a { background: #f8f9fa; color: #2C3E50; border: 1px solid #dee2e6; }
.pagination a:hover { background: #C9A961; color: white; border-color: #C9A961; transform: translateY(-2px); }
.pagination .current { background: #C9A961; color: white; border: 1px solid #C9A961; padding: 0.6rem 0.9rem; border-radius: 8px; }

.empty-state { text-align: center; padding: 3rem 2rem; color: #666; }
.empty-state i { font-size: 3rem; color: #C9A961; margin-bottom: 1rem; opacity: 0.5; }
.empty-state h3 { color: #2C3E50; margin: 1rem 0; }

@media (max-width: 1024px) {
    .admin-table { font-size: 0.85rem; }
    .admin-table thead th, .admin-table tbody td { padding: 0.85rem; }
    .reservation-details { font-size: 0.8rem; }
}

@media (max-width: 768px) {
    .filters { flex-direction: column; align-items: stretch; }
    .filter-actions { justify-content: center; }
    .admin-table { font-size: 0.75rem; }
    .admin-table thead th, .admin-table tbody td { padding: 0.6rem; }
    .reservation-actions { flex-direction: column; }
    .btn-action { width: 100%; justify-content: center; }
}
</style>

<!-- Main Content -->
<div class="content-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-calendar-check"></i> Reservations</h1>
        <p>Manage hotel reservations and bookings</p>
    </div>

    <!-- Pending Notifications -->
    <?php if ($pendingRooms > 0 || $pendingPavilion > 0): ?>
    <div class="pending-alerts">
        <?php if ($pendingRooms > 0): ?>
        <div class="pending-alert pending-alert-room">
            <div class="pending-alert-icon"><i class="fas fa-bed"></i></div>
            <div class="pending-alert-body">
                <strong><?php echo $pendingRooms; ?> Pending Room Reservation<?php echo $pendingRooms > 1 ? 's' : ''; ?></strong>
                <span>Waiting for your confirmation</span>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($pendingPavilion > 0): ?>
        <div class="pending-alert pending-alert-pavilion">
            <div class="pending-alert-icon"><i class="fas fa-archway"></i></div>
            <div class="pending-alert-body">
                <strong><?php echo $pendingPavilion; ?> Pending Pavilion Booking<?php echo $pendingPavilion > 1 ? 's' : ''; ?></strong>
                <span>Waiting for your confirmation</span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

            <!-- Filters -->
            <div class="filters">
                <form method="GET" action="" style="display: contents;">
                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" placeholder="Guest name, email, or phone" 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All Statuses</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>
                            Pending <?php $totalPending = $pendingRooms + $pendingPavilion; if ($totalPending > 0) echo "($totalPending)"; ?>
                        </option>
                            <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="reservations.php" class="btn-filter secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Reservations Table -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Reservations (<?php echo number_format($totalReservations); ?>)</h2>
                </div>
                
                <?php if (!empty($reservations)): ?>
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Guest Details</th>
                                <th>Room & Dates</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation):
                                $isPav = ($reservation['booking_type'] ?? '') === 'pavilion';
                                $actId = ($isPav ? 'pv' : 'rm') . $reservation['id'];
                            ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo $reservation['id']; ?></strong>
                                    <?php if ($isPav): ?>
                                    <div style="font-size:0.7rem;background:#e8f4ff;color:#3b82f6;border-radius:4px;padding:0.1rem 0.35rem;margin-top:0.2rem;display:inline-block;font-weight:700;">PAVILION</div>
                                    <?php else: ?>
                                    <div style="font-size:0.7rem;background:#fff3cd;color:#856404;border-radius:4px;padding:0.1rem 0.35rem;margin-top:0.2rem;display:inline-block;font-weight:700;">ROOM</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($reservation['guest_name']); ?></strong>
                                    <div class="reservation-details">
                                        <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($reservation['email']); ?></div>
                                        <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($reservation['phone']); ?></div>
                                        <div><i class="fas fa-users"></i> <?php echo $isPav ? ($reservation['pax'] ?? 0) : $reservation['guests']; ?> guests</div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isPav): ?>
                                        <strong><i class="fas fa-archway" style="color:#C9A961;margin-right:0.3rem;"></i><?php echo htmlspecialchars($reservation['event_type'] ?? 'Event'); ?></strong>
                                        <div class="reservation-details">
                                            <div><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($reservation['event_date'])); ?></div>
                                            <?php if (!empty($reservation['event_time'])): ?>
                                            <div><i class="fas fa-clock"></i> <?php echo htmlspecialchars($reservation['event_time']); ?><?php echo !empty($reservation['event_end_time']) ? ' - ' . htmlspecialchars($reservation['event_end_time']) : ''; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else:
                                        $rOpts  = json_decode($reservation['options'] ?? '{}', true);
                                        $rNum   = $rOpts['individual_room']['room_number'] ?? '';
                                        $rType  = $rOpts['individual_room']['room_type']   ?? $reservation['room_type'] ?? '';
                                        if (strpos($rType, ' - ') !== false) $rType = explode(' - ', $rType)[0];
                                        $label  = $rType ? $rType . ($rNum ? ' Room ' . $rNum : '') : 'N/A';
                                    ?>
                                        <strong><?php echo htmlspecialchars($label); ?></strong>
                                        <div class="reservation-details">
                                            <div><i class="fas fa-calendar"></i> <?php echo date('M j', strtotime($reservation['checkin_date'])); ?> - <?php echo date('M j, Y', strtotime($reservation['checkout_date'])); ?></div>
                                            <?php $nights = (new DateTime($reservation['checkin_date']))->diff(new DateTime($reservation['checkout_date']))->days; ?>
                                            <div><i class="fas fa-moon"></i> <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="payment-info">
                                        <div class="payment-amount">₱<?php echo number_format($reservation['price'], 2); ?></div>
                                        <?php if (($reservation['payment_amount'] ?? 0) > 0): ?>
                                        <div>Paid: ₱<?php echo number_format($reservation['payment_amount'], 2); ?> (<?php echo $reservation['payment_percentage'] ?? 0; ?>%)</div>
                                        <?php endif; ?>
                                        <?php if (!empty($reservation['payment_method'])): ?>
                                        <div class="payment-method"><?php echo str_replace('_', ' ', $reservation['payment_method']); ?></div>
                                        <?php endif; ?>
                                        <div>
                                            <span class="status-badge status-<?php echo $reservation['payment_status'] ?? 'pending'; ?>">
                                                <?php echo ucfirst($reservation['payment_status'] ?? 'pending'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $reservation['status']; ?>">
                                        <?php echo ucfirst($reservation['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($reservation['created_at'])); ?></td>
                                <td>
                                    <?php
                                    $proofFile = null;
                                    $proofDir  = '../payment/uploads/payment_proofs/';
                                    if (is_dir($proofDir)) {
                                        $method = $reservation['payment_method'] ?? '';
                                        $rid    = $reservation['id'];
                                        foreach (glob($proofDir . $method . '_' . $rid . '_*') as $f) { $proofFile = basename($f); break; }
                                    }
                                    $isPending  = $reservation['status'] === 'pending';
                                    $hasPayment = !empty($reservation['payment_method']) || $proofFile;
                                    $panelData  = json_encode([
                                        'actId'   => $actId,
                                        'id'      => $reservation['id'],
                                        'isPav'   => $isPav,
                                        'pending' => $isPending,
                                        'method'  => ucwords(str_replace('_', ' ', $reservation['payment_method'] ?? '')),
                                        'ref'     => $reservation['payment_reference'] ?? '',
                                        'proof'   => $proofFile ? '../payment/uploads/payment_proofs/' . $proofFile : '',
                                        'hasPayment' => $hasPayment,
                                        'guest'   => $reservation['guest_name'],
                                        'ctrlNum' => $isPav ? 'PAV-' . str_pad($reservation['id'], 6, '0', STR_PAD_LEFT) : 'RES-' . str_pad($reservation['id'], 6, '0', STR_PAD_LEFT),
                                        'status'  => $reservation['status'],
                                    ]);
                                    ?>
                                    <button class="btn-ctrl" onclick='openCtrl(<?php echo htmlspecialchars($panelData, ENT_QUOTES); ?>)'>
                                        <i class="fas fa-ellipsis-v"></i> Actions
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($searchTerm); ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($searchTerm); ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($searchTerm); ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Reservations Found</h3>
                    <p>
                        <?php if (!empty($searchTerm) || !empty($statusFilter)): ?>
                            No reservations match your current filters.
                        <?php else: ?>
                            No reservations have been made yet.
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
<?php include 'template_footer.php'; ?>

<!-- Floating Control Panel -->
<div id="ctrlOverlay" onclick="closeCtrl()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9998;"></div>
<div id="ctrlPanel" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:9999;width:340px;background:#fff;border-radius:18px;box-shadow:0 24px 60px rgba(0,0,0,0.35);overflow:hidden;">
    <!-- Header -->
    <div style="background:linear-gradient(135deg,#2C3E50,#34495E);padding:1.1rem 1.4rem;display:flex;justify-content:space-between;align-items:center;">
        <div>
            <div style="color:#C9A961;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;">Reservation Controls</div>
            <div id="ctrlGuest" style="color:#fff;font-weight:700;font-size:1rem;margin-top:0.15rem;"></div>
            <div id="ctrlNum" style="color:rgba(255,255,255,0.55);font-size:0.75rem;margin-top:0.1rem;font-family:monospace;"></div>
        </div>
        <button onclick="closeCtrl()" style="background:rgba(255,255,255,0.1);border:none;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;">&times;</button>
    </div>

    <!-- Actions -->
    <div style="padding:1.25rem;display:flex;flex-direction:column;gap:0.75rem;">

        <!-- Confirm / Cancel -->
        <div id="ctrlPendingSection">
            <div style="font-size:0.72rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:0.5rem;">Booking Status</div>
            <div style="display:flex;gap:0.6rem;">
                <button id="ctrlConfirmBtn" onclick="ctrlAction('confirm')"
                    style="flex:1;padding:0.65rem;border:none;border-radius:10px;background:#28a745;color:#fff;font-weight:700;font-size:0.88rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.4rem;transition:opacity 0.2s;">
                    <i class="fas fa-check"></i> Confirm
                </button>
                <button id="ctrlCancelBtn" onclick="ctrlAction('cancel')"
                    style="flex:1;padding:0.65rem;border:none;border-radius:10px;background:#dc3545;color:#fff;font-weight:700;font-size:0.88rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.4rem;transition:opacity 0.2s;">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>

        <!-- Cancel only (for confirmed bookings) -->
        <div id="ctrlCancelOnly" style="display:none;">
            <button onclick="ctrlAction('cancel')"
                style="width:100%;padding:0.65rem;border:none;border-radius:10px;background:#dc3545;color:#fff;font-weight:700;font-size:0.88rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.4rem;">
                <i class="fas fa-times"></i> Cancel Booking
            </button>
        </div>

        <!-- Payment info -->
        <div id="ctrlPaymentSection">
            <div style="font-size:0.72rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:0.75rem;">Payment Details</div>
            <div style="background:#f8f9fa;border-radius:8px;padding:0.75rem;margin-bottom:0.6rem;">
                <div style="font-size:0.7rem;color:#aaa;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:0.2rem;">Method</div>
                <div id="ctrlPayMethod" style="font-size:0.92rem;font-weight:700;color:#2C3E50;"></div>
            </div>
            <div style="background:#f8f9fa;border-radius:8px;padding:0.75rem;margin-bottom:0.75rem;">
                <div style="font-size:0.7rem;color:#aaa;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:0.2rem;">Transaction ID / Reference</div>
                <div id="ctrlPayRef" style="font-size:0.88rem;font-weight:600;color:#2C3E50;word-break:break-all;font-family:monospace;"></div>
            </div>
            <div id="ctrlProofWrap">
                <a id="ctrlProofLink" href="#" target="_blank">
                    <img id="ctrlProofImg" src="" alt="Proof"
                         style="width:100%;border-radius:10px;border:2px solid #e0e0e0;cursor:pointer;display:block;">
                </a>
                <p style="font-size:0.75rem;color:#aaa;text-align:center;margin-top:0.4rem;">Click to open full size</p>
            </div>
            <div id="ctrlNoProof" style="color:#bbb;font-style:italic;font-size:0.82rem;text-align:center;padding:0.5rem 0;">No proof uploaded yet.</div>
        </div>

    </div>
</div>

<style>
.btn-ctrl {
    background: linear-gradient(135deg,#2C3E50,#34495E);
    color: white; border: none; padding: 0.4rem 0.9rem;
    border-radius: 8px; font-size: 0.78rem; font-weight: 600;
    cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem;
    transition: all 0.2s;
}
.btn-ctrl:hover { background: linear-gradient(135deg,#C9A961,#8B7355); }

/* Pending alerts */
.pending-alerts { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1.5rem; }
.pending-alert { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 1rem; border-radius: 8px; border-left: 4px solid; }
.pending-alert-room     { background: #fff8e8; border-color: #C9A961; }
.pending-alert-pavilion { background: #e8f4ff; border-color: #3b82f6; }
.pending-alert-icon { font-size: 1rem; flex-shrink: 0; }
.pending-alert-room     .pending-alert-icon { color: #C9A961; }
.pending-alert-pavilion .pending-alert-icon { color: #3b82f6; }
.pending-alert-body { flex: 1; display: flex; align-items: center; gap: 0.5rem; }
.pending-alert-body strong { color: #2C3E50; font-size: 0.88rem; }
.pending-alert-body span  { color: #888; font-size: 0.8rem; }
</style>

<script>
let _ctrl = {};

function openCtrl(data) {
    _ctrl = data;
    document.getElementById('ctrlGuest').textContent = data.guest;
    document.getElementById('ctrlNum').textContent   = data.ctrlNum || '';

    // Show correct action buttons based on status
    const pendSec      = document.getElementById('ctrlPendingSection');
    const cancelOnly   = document.getElementById('ctrlCancelOnly');
    const isCancelled  = data.status === 'cancelled';

    pendSec.style.display    = data.pending    ? '' : 'none';
    cancelOnly.style.display = (!data.pending && !isCancelled) ? '' : 'none';

    // Payment section
    const paySec = document.getElementById('ctrlPaymentSection');
    paySec.style.display = data.hasPayment ? '' : 'none';
    if (data.hasPayment) {
        document.getElementById('ctrlPayMethod').textContent = data.method || 'Not specified';
        document.getElementById('ctrlPayRef').textContent    = data.ref   || 'No reference';
        if (data.proof) {
            document.getElementById('ctrlProofImg').src   = data.proof;
            document.getElementById('ctrlProofLink').href = data.proof;
            document.getElementById('ctrlProofWrap').style.display = '';
            document.getElementById('ctrlNoProof').style.display   = 'none';
        } else {
            document.getElementById('ctrlProofWrap').style.display = 'none';
            document.getElementById('ctrlNoProof').style.display   = '';
        }
    }

    document.getElementById('ctrlOverlay').style.display = '';
    document.getElementById('ctrlPanel').style.display   = '';
}

function closeCtrl() {
    document.getElementById('ctrlOverlay').style.display = 'none';
    document.getElementById('ctrlPanel').style.display   = 'none';
}

function ctrlAction(action) {
    const label = action === 'confirm' ? 'Confirm this booking?' : 'Cancel this booking?';
    if (!confirm(label)) return;

    const confirmBtn = document.getElementById('ctrlConfirmBtn');
    const cancelBtn  = document.getElementById('ctrlCancelBtn');
    confirmBtn.disabled = cancelBtn.disabled = true;
    confirmBtn.style.opacity = cancelBtn.style.opacity = '0.5';

    const fd = new FormData();
    fd.append('action', _ctrl.isPav ? (action === 'confirm' ? 'confirm_booking' : 'cancel_booking') : action);
    if (_ctrl.isPav) fd.append('id', _ctrl.id);
    else { fd.append('reservation_id', _ctrl.id); }

    const url = _ctrl.isPav ? 'pavilion_dashboard.php' : 'reservations.php';

    fetch(url, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert('Error: ' + (data.message || 'Unknown')); confirmBtn.disabled = cancelBtn.disabled = false; confirmBtn.style.opacity = cancelBtn.style.opacity = '1'; return; }
            const newStatus = action === 'confirm' ? 'confirmed' : 'cancelled';
            applyStatusUpdate(_ctrl.actId, newStatus);
            // Hide pending section in panel
            document.getElementById('ctrlPendingSection').style.display = 'none';
            document.getElementById('ctrlCancelOnly').style.display     = 'none';
            closeCtrl();
        })
        .catch(() => { alert('Request failed.'); confirmBtn.disabled = cancelBtn.disabled = false; confirmBtn.style.opacity = cancelBtn.style.opacity = '1'; });
}

function applyStatusUpdate(actId, newStatus) {
    // Update all status badges in the row
    const btn = document.querySelector(`[onclick*="${actId}"]`);
    if (btn) {
        const row = btn.closest('tr');
        if (row) {
            row.querySelectorAll('.status-badge').forEach(b => {
                b.className = 'status-badge status-' + newStatus;
                b.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            });
        }
    }

    // Update pending alert counts
    const isPav = actId.startsWith('pv');
    const alertClass = isPav ? '.pending-alert-pavilion' : '.pending-alert-room';
    const alertEl = document.querySelector(alertClass);
    if (alertEl) {
        const strong = alertEl.querySelector('strong');
        if (strong) {
            const match = strong.textContent.match(/(\d+)/);
            if (match) {
                const newCount = parseInt(match[1]) - 1;
                if (newCount <= 0) {
                    alertEl.remove();
                    const wrapper = document.querySelector('.pending-alerts');
                    if (wrapper && wrapper.children.length === 0) wrapper.remove();
                } else {
                    const label = isPav ? 'Pending Pavilion Booking' : 'Pending Room Reservation';
                    strong.textContent = newCount + ' ' + label + (newCount > 1 ? 's' : '');
                }
            }
        }
    }

    // Update filter dropdown count
    const pendingOpt = document.querySelector('select[name="status"] option[value="pending"]');
    if (pendingOpt) {
        const match = pendingOpt.textContent.match(/(\d+)/);
        if (match) {
            const newTotal = parseInt(match[1]) - 1;
            pendingOpt.textContent = newTotal > 0 ? 'Pending (' + newTotal + ')' : 'Pending';
        }
    }

    // Mark ctrl data as no longer pending
    _ctrl.pending = false;
}

function updateReservation(id, action, actId) {
    _ctrl = { id, isPav: false, actId };
    ctrlAction(action);
}
function updatePavilion(id, action, actId) {
    _ctrl = { id, isPav: true, actId };
    ctrlAction(action === 'confirm_booking' ? 'confirm' : 'cancel');
}
</script>