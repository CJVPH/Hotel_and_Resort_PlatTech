<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin login
requireAdminLogin();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $reservationId = intval($_POST['reservation_id'] ?? 0);
    $action = $_POST['action'];
    
    if ($reservationId > 0) {
        try {
            $conn = getDBConnection();
            
            if ($action === 'confirm') {
                $stmt = $conn->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ?");
                $stmt->bind_param("i", $reservationId);
                $stmt->execute();
                $stmt->close();
            } elseif ($action === 'cancel') {
                $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
                $stmt->bind_param("i", $reservationId);
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->close();
            
        } catch (Exception $e) {
            error_log("Reservation update error: " . $e->getMessage());
        }
    }
}

// Get reservations with pagination
$page = intval($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$statusFilter = $_GET['status'] ?? '';
$searchTerm = $_GET['search'] ?? '';

try {
    $conn = getDBConnection();
    
    // Build query
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if (!empty($statusFilter)) {
        $whereConditions[] = "r.status = ?";
        $params[] = $statusFilter;
        $types .= 's';
    }
    
    if (!empty($searchTerm)) {
        $whereConditions[] = "(r.guest_name LIKE ? OR r.email LIKE ? OR r.phone LIKE ?)";
        $searchParam = "%{$searchTerm}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM reservations r {$whereClause}";
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalResult = $countStmt->get_result();
    $totalReservations = $totalResult->fetch_assoc()['total'];
    $countStmt->close();
    
    // Get reservations
    $sql = "SELECT r.*, u.username FROM reservations r 
            LEFT JOIN users u ON r.user_id = u.id 
            {$whereClause} 
            ORDER BY r.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $reservations = [];
    
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    $totalPages = ceil($totalReservations / $limit);
    
} catch (Exception $e) {
    error_log("Reservations page error: " . $e->getMessage());
    $reservations = [];
    $totalReservations = 0;
    $totalPages = 0;
}

// Set page variables for template
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
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
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
                            <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><strong>#<?php echo $reservation['id']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($reservation['guest_name']); ?></strong>
                                    <div class="reservation-details">
                                        <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($reservation['email']); ?></div>
                                        <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($reservation['phone']); ?></div>
                                        <div><i class="fas fa-users"></i> <?php echo $reservation['guests']; ?> guests</div>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($reservation['room_type']); ?></strong>
                                    <div class="reservation-details">
                                        <div><i class="fas fa-calendar"></i> <?php echo date('M j', strtotime($reservation['checkin_date'])); ?> - <?php echo date('M j, Y', strtotime($reservation['checkout_date'])); ?></div>
                                        <?php
                                        $checkin = new DateTime($reservation['checkin_date']);
                                        $checkout = new DateTime($reservation['checkout_date']);
                                        $nights = $checkin->diff($checkout)->days;
                                        ?>
                                        <div><i class="fas fa-moon"></i> <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="payment-info">
                                        <div class="payment-amount">₱<?php echo number_format($reservation['price'], 2); ?></div>
                                        <?php if ($reservation['payment_amount'] > 0): ?>
                                        <div>Paid: ₱<?php echo number_format($reservation['payment_amount'], 2); ?> (<?php echo $reservation['payment_percentage']; ?>%)</div>
                                        <?php endif; ?>
                                        <?php if (!empty($reservation['payment_method'])): ?>
                                        <div class="payment-method"><?php echo str_replace('_', ' ', $reservation['payment_method']); ?></div>
                                        <?php endif; ?>
                                        <div>
                                            <span class="status-badge status-<?php echo $reservation['payment_status']; ?>">
                                                <?php echo ucfirst($reservation['payment_status']); ?>
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
                                    <?php if ($reservation['status'] === 'pending'): ?>
                                    <div class="reservation-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                            <input type="hidden" name="action" value="confirm">
                                            <button type="submit" class="btn-action btn-confirm" onclick="return confirm('Confirm this reservation?')">
                                                <i class="fas fa-check"></i> Confirm
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="btn-action btn-cancel" onclick="return confirm('Cancel this reservation?')">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </form>
                                    </div>
                                    <?php else: ?>
                                    <span style="color: #666; font-style: italic;">No actions</span>
                                    <?php endif; ?>
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