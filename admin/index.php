<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin login
requireAdminLogin();

// Get dashboard statistics
try {
    $conn = getDBConnection();
    
    // Total reservations
    $totalReservations = 0;
    $result = $conn->query("SELECT COUNT(*) as count FROM reservations");
    if ($result) {
        $row = $result->fetch_assoc();
        $totalReservations = $row['count'];
    }
    
    // Pending reservations
    $pendingReservations = 0;
    $result = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'pending'");
    if ($result) {
        $row = $result->fetch_assoc();
        $pendingReservations = $row['count'];
    }
    
    // Confirmed reservations
    $confirmedReservations = 0;
    $result = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'confirmed'");
    if ($result) {
        $row = $result->fetch_assoc();
        $confirmedReservations = $row['count'];
    }
    
    // Total revenue
    $totalRevenue = 0;
    $result = $conn->query("SELECT SUM(payment_amount) as total FROM reservations WHERE payment_status IN ('completed', 'pending')");
    if ($result) {
        $row = $result->fetch_assoc();
        $totalRevenue = $row['total'] ?? 0;
    }
    
    // Monthly revenue (current month)
    $monthlyRevenue = 0;
    $currentMonth = date('Y-m');
    $result = $conn->query("SELECT SUM(payment_amount) as total FROM reservations WHERE payment_status IN ('completed', 'pending') AND DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth'");
    if ($result) {
        $row = $result->fetch_assoc();
        $monthlyRevenue = $row['total'] ?? 0;
    }
    
    // Today's reservations
    $todayReservations = 0;
    $today = date('Y-m-d');
    $result = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE DATE(created_at) = '$today'");
    if ($result) {
        $row = $result->fetch_assoc();
        $todayReservations = $row['count'];
    }
    
    // Cancelled reservations
    $cancelledReservations = 0;
    $result = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'cancelled'");
    if ($result) {
        $row = $result->fetch_assoc();
        $cancelledReservations = $row['count'];
    }
    
    // Average booking value
    $averageBookingValue = 0;
    $result = $conn->query("SELECT AVG(payment_amount) as average FROM reservations WHERE payment_status IN ('completed', 'pending')");
    if ($result) {
        $row = $result->fetch_assoc();
        $averageBookingValue = $row['average'] ?? 0;
    }
    
    // Most popular room type
    $popularRoomType = 'N/A';
    $result = $conn->query("SELECT room_type, COUNT(*) as count FROM reservations GROUP BY room_type ORDER BY count DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $popularRoomType = $row['room_type'];
    }
    
    // Total users
    $totalUsers = 0;
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0");
    if ($result) {
        $row = $result->fetch_assoc();
        $totalUsers = $row['count'];
    }
    
    // Recent reservations
    $recentReservations = [];
    $result = $conn->query("SELECT r.*, u.username FROM reservations r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recentReservations[] = $row;
        }
    }
    
    // Get monthly revenue data for chart (last 6 months)
    $monthlyData = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthLabel = date('M Y', strtotime("-$i months"));
        
        $result = $conn->query("SELECT SUM(payment_amount) as total FROM reservations WHERE payment_status IN ('completed', 'pending') AND DATE_FORMAT(created_at, '%Y-%m') = '$month'");
        if ($result) {
            $row = $result->fetch_assoc();
            $monthlyData[] = [
                'month' => $monthLabel,
                'revenue' => $row['total'] ?? 0
            ];
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $totalReservations = $pendingReservations = $confirmedReservations = $totalRevenue = $totalUsers = 0;
    $monthlyRevenue = $todayReservations = $cancelledReservations = $averageBookingValue = 0;
    $popularRoomType = 'N/A';
    $recentReservations = [];
    $monthlyData = [];
}

// Set page variables for template
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
?>
<?php include 'template_header.php'; ?>

<style>
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid rgba(201, 169, 97, 0.2);
}

.dashboard-title h1 {
    color: #2C3E50;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dashboard-title h1 i {
    background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.dashboard-title p {
    color: #6c757d;
    font-size: 0.95rem;
    margin: 0;
}

.system-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
    box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
}

.system-status i {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.compact-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.25rem;
    margin-bottom: 2rem;
}

.compact-stat-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 1.25rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(201, 169, 97, 0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.compact-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%);
}

.compact-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.compact-stat-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.compact-stat-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: white;
    box-shadow: 0 3px 10px rgba(201, 169, 97, 0.3);
}

.compact-stat-label {
    color: #6c757d;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.compact-stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #2C3E50;
    margin-top: 0.5rem;
}
</style>

<!-- Dashboard Header with Status -->
<div class="dashboard-header">
    <div class="dashboard-title">
        <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        <p>Welcome to Paradise Hotel & Resort Administration Panel</p>
    </div>
    <div class="system-status">
        <i class="fas fa-circle"></i>
        <span>System Online</span>
    </div>
</div>

<!-- Compact Statistics Cards -->
<div class="compact-stats-grid">
    <div class="compact-stat-card">
        <div class="compact-stat-header">
            <div class="compact-stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="compact-stat-label">Total Reservations</div>
        </div>
        <div class="compact-stat-value"><?php echo number_format($totalReservations); ?></div>
    </div>

    <div class="compact-stat-card">
        <div class="compact-stat-header">
            <div class="compact-stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="compact-stat-label">Pending</div>
        </div>
        <div class="compact-stat-value"><?php echo number_format($pendingReservations); ?></div>
    </div>

    <div class="compact-stat-card">
        <div class="compact-stat-header">
            <div class="compact-stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="compact-stat-label">Confirmed</div>
        </div>
        <div class="compact-stat-value"><?php echo number_format($confirmedReservations); ?></div>
    </div>

    <div class="compact-stat-card">
        <div class="compact-stat-header">
            <div class="compact-stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="compact-stat-label">Users</div>
        </div>
        <div class="compact-stat-value"><?php echo number_format($totalUsers); ?></div>
    </div>

    <div class="compact-stat-card">
        <div class="compact-stat-header">
            <div class="compact-stat-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="compact-stat-label">Total Revenue</div>
        </div>
        <div class="compact-stat-value">₱<?php echo number_format($totalRevenue, 0); ?></div>
    </div>

    <div class="compact-stat-card">
        <div class="compact-stat-header">
            <div class="compact-stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="compact-stat-label">This Month</div>
        </div>
        <div class="compact-stat-value">₱<?php echo number_format($monthlyRevenue, 0); ?></div>
    </div>

    <div class="compact-stat-card">
        <div class="compact-stat-header">
            <div class="compact-stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="compact-stat-label">Today's Bookings</div>
        </div>
        <div class="compact-stat-value"><?php echo number_format($todayReservations); ?></div>
    </div>

    <div class="compact-stat-card">
        <div class="compact-stat-header">
            <div class="compact-stat-icon">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="compact-stat-label">Success Rate</div>
        </div>
        <div class="compact-stat-value">
            <?php 
            $successRate = $totalReservations > 0 ? (($confirmedReservations / $totalReservations) * 100) : 0;
            echo number_format($successRate, 1) . '%';
            ?>
        </div>
    </div>
</div>

<!-- Recent Reservations -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Recent Reservations</h2>
                    <a href="reservations.php" class="btn btn-primary">View All</a>
                </div>
                
                <?php if (!empty($recentReservations)): ?>
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Guest Name</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentReservations as $reservation): ?>
                            <tr>
                                <td>#<?php echo $reservation['id']; ?></td>
                                <td><?php echo htmlspecialchars($reservation['guest_name']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['room_type']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($reservation['checkin_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($reservation['checkout_date'])); ?></td>
                                <td>₱<?php echo number_format($reservation['price'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $reservation['status']; ?>">
                                        <?php echo ucfirst($reservation['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($reservation['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Reservations Yet</h3>
                    <p>Reservations will appear here once customers start booking.</p>
                </div>
                <?php endif; ?>
            </div>

<?php include 'template_footer.php'; ?>