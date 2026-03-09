<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin login
requireAdminLogin();

// Get users with pagination
$page = intval($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$searchTerm = $_GET['search'] ?? '';

try {
    $conn = getDBConnection();
    
    // Build query
    $whereClause = '';
    $params = [];
    $types = '';
    
    if (!empty($searchTerm)) {
        $whereClause = "WHERE (username LIKE ? OR email LIKE ? OR full_name LIKE ?) AND is_admin = 0";
        $searchParam = "%{$searchTerm}%";
        $params = [$searchParam, $searchParam, $searchParam];
        $types = 'sss';
    } else {
        $whereClause = "WHERE is_admin = 0";
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM users {$whereClause}";
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalResult = $countStmt->get_result();
    $totalUsers = $totalResult->fetch_assoc()['total'];
    $countStmt->close();
    
    // Get users
    $sql = "SELECT id, username, email, full_name, created_at FROM users 
            {$whereClause} 
            ORDER BY created_at DESC 
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
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
        // Get reservation count for each user
        $reservationStmt = $conn->prepare("SELECT COUNT(*) as count FROM reservations WHERE user_id = ?");
        $reservationStmt->bind_param("i", $row['id']);
        $reservationStmt->execute();
        $reservationResult = $reservationStmt->get_result();
        $row['reservation_count'] = $reservationResult->fetch_assoc()['count'];
        $reservationStmt->close();
        
        $users[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    $totalPages = ceil($totalUsers / $limit);
    
} catch (Exception $e) {
    error_log("Users page error: " . $e->getMessage());
    $users = [];
    $totalUsers = 0;
    $totalPages = 0;
}
// Set page variables for template
$pageTitle = 'Users';
$currentPage = 'users';
?>
<?php include 'template_header.php'; ?>

<!-- Page specific styles -->
<style>
.search-bar {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    display: flex;
    gap: 1rem;
    align-items: end;
}

.search-group {
    flex: 1;
}

.search-group label {
    display: block;
    color: #2C3E50;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.search-group input {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 0.95rem;
}

.search-group input:focus {
    outline: none;
    border-color: #C9A961;
}

.search-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-search {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-search.primary {
    background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%);
    color: white;
}

.btn-search.secondary {
    background: #f8f9fa;
    color: #666;
    border: 1px solid #e0e0e0;
}

.btn-search:hover {
    transform: translateY(-2px);
}

.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    font-weight: 700;
    margin-right: 1rem;
}

.user-info {
    display: flex;
    align-items: center;
}

.user-details {
    flex: 1;
}

.user-name {
    font-weight: 700;
    color: #2C3E50;
    margin-bottom: 0.25rem;
}

.user-email {
    color: #666;
    font-size: 0.9rem;
}

.user-stats {
    text-align: center;
    color: #666;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #C9A961;
    display: block;
}

.stat-label {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.pagination a,
.pagination span {
    padding: 0.75rem 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    text-decoration: none;
    color: #666;
    font-weight: 600;
    transition: all 0.3s ease;
}

.pagination a:hover {
    background: #C9A961;
    color: white;
    border-color: #C9A961;
}

.pagination .current {
    background: #C9A961;
    color: white;
    border-color: #C9A961;
}
</style>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="fas fa-users"></i> User Management</h1>
    <p>Manage registered users and their accounts</p>
</div>

            <!-- Search Bar -->
            <div class="search-bar">
                <form method="GET" action="" style="display: contents;">
                    <div class="search-group">
                        <label for="search">Search Users</label>
                        <input type="text" id="search" name="search" placeholder="Username, email, or full name" 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    <div class="search-actions">
                        <button type="submit" class="btn-search primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="users.php" class="btn-search secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Registered Users (<?php echo number_format($totalUsers); ?>)</h2>
                </div>
                
                <?php if (!empty($users)): ?>
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Reservations</th>
                                <th>Joined</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)); ?>
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" style="color: #C9A961; text-decoration: none;">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="user-stats">
                                        <span class="stat-number"><?php echo $user['reservation_count']; ?></span>
                                        <span class="stat-label">Bookings</span>
                                    </div>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                    <div style="font-size: 0.85rem; color: #666;">
                                        <?php echo date('g:i A', strtotime($user['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-confirmed">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
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
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchTerm); ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchTerm); ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No Users Found</h3>
                    <p>
                        <?php if (!empty($searchTerm)): ?>
                            No users match your search criteria.
                        <?php else: ?>
                            No users have registered yet.
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <!-- User Statistics -->
            <div class="admin-section">
                <div class="section-header">
                    <h2><i class="fas fa-chart-bar"></i> User Statistics</h2>
                </div>
                
                <div class="quick-actions">
                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="action-content">
                            <h3>Total Registered Users</h3>
                            <p><strong><?php echo number_format($totalUsers); ?></strong> users have created accounts on your website.</p>
                        </div>
                    </div>

                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="action-content">
                            <h3>Active Customers</h3>
                            <p>Users who have made at least one reservation and are actively using your services.</p>
                        </div>
                    </div>

                    <div class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="action-content">
                            <h3>Communication</h3>
                            <p>Contact users directly via email for special offers, updates, or customer service.</p>
                        </div>
                    </div>
                </div>
            </div>
<?php include 'template_footer.php'; ?>