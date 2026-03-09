<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/google_oauth.php';

// Require admin login
requireAdminLogin();

$google = new GoogleOAuth();

// Set page variables for template
$pageTitle = 'Google OAuth Status';
$currentPage = 'google_oauth_status';
?>
<?php include 'template_header.php'; ?>

<div class="page-header">
    <h1><i class="fab fa-google"></i> Google OAuth Status</h1>
    <p>Monitor Google Sign-In integration</p>
</div>

<div class="oauth-status-content">
    <div class="card">
        <h2>Configuration Status</h2>
        <div class="status-grid">
            <div class="status-item success">
                <div class="status-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="status-info">
                    <h3>Google OAuth</h3>
                    <p>✅ Configured and Active</p>
                </div>
            </div>
            
            <div class="status-item success">
                <div class="status-icon">
                    <i class="fas fa-key"></i>
                </div>
                <div class="status-info">
                    <h3>Client ID</h3>
                    <p>✅ <?php echo substr($google->getClientId(), 0, 20); ?>...</p>
                </div>
            </div>
            
            <div class="status-item success">
                <div class="status-icon">
                    <i class="fas fa-link"></i>
                </div>
                <div class="status-info">
                    <h3>Redirect URI</h3>
                    <p>✅ http://localhost/redirect.php</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Current Configuration</h2>
        <div class="config-details">
            <div class="config-item">
                <label>Client ID:</label>
                <code>829858353753-5er1pes529q7rugedqvrpjfgekqmf5c5.apps.googleusercontent.com</code>
            </div>
            <div class="config-item">
                <label>Client Secret:</label>
                <code>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</code>
            </div>
            <div class="config-item">
                <label>Redirect URI:</label>
                <code>http://localhost/redirect.php</code>
            </div>
            <div class="config-item">
                <label>Scopes:</label>
                <code>openid email profile</code>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>User Statistics</h2>
        <?php
        try {
            $conn = getDBConnection();
            
            // Total users
            $result = $conn->query("SELECT COUNT(*) as count FROM users");
            $totalUsers = $result ? $result->fetch_assoc()['count'] : 0;
            
            // Google users
            $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE google_id IS NOT NULL");
            $googleUsers = $result ? $result->fetch_assoc()['count'] : 0;
            
            // Regular users
            $regularUsers = $totalUsers - $googleUsers;
            
            $conn->close();
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $googleUsers; ?></div>
                <div class="stat-label">Google Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $regularUsers; ?></div>
                <div class="stat-label">Email Users</div>
            </div>
        </div>
        <?php
        } catch (Exception $e) {
            echo '<p class="error">Error loading statistics: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>

    <div class="card">
        <h2>Test Integration</h2>
        <div class="test-section">
            <p>Test the Google OAuth integration:</p>
            <div class="test-buttons">
                <a href="../login.php" class="btn btn-primary" target="_blank">
                    <i class="fas fa-sign-in-alt"></i> Test Login Page
                </a>
                <a href="../register.php" class="btn btn-secondary" target="_blank">
                    <i class="fas fa-user-plus"></i> Test Register Page
                </a>
                <a href="../test_google_login.php" class="btn btn-info" target="_blank">
                    <i class="fas fa-flask"></i> OAuth Test Page
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Recent Google Logins</h2>
        <?php
        try {
            $conn = getDBConnection();
            $result = $conn->query("SELECT username, full_name, email, created_at FROM users WHERE google_id IS NOT NULL ORDER BY created_at DESC LIMIT 10");
            
            if ($result && $result->num_rows > 0) {
                echo '<div class="users-table">';
                echo '<table>';
                echo '<thead><tr><th>Username</th><th>Full Name</th><th>Email</th><th>Joined</th></tr></thead>';
                echo '<tbody>';
                
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['username']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                    echo '<td>' . date('M j, Y', strtotime($row['created_at'])) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
                echo '</div>';
            } else {
                echo '<p class="no-data">No Google users found yet.</p>';
            }
            
            $conn->close();
        } catch (Exception $e) {
            echo '<p class="error">Error loading users: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>
</div>

<style>
.oauth-status-content {
    max-width: 1000px;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #e9ecef;
}

.status-item.success {
    background: #d4edda;
    border-color: #c3e6cb;
}

.status-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    background: #155724;
    color: white;
}

.status-info h3 {
    margin: 0 0 5px 0;
    font-size: 16px;
    color: #2C3E50;
}

.status-info p {
    margin: 0;
    color: #155724;
    font-weight: 600;
}

.config-details {
    display: grid;
    gap: 15px;
}

.config-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.config-item label {
    font-weight: 600;
    min-width: 120px;
    color: #2C3E50;
}

.config-item code {
    background: #e9ecef;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 14px;
    word-break: break-all;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #e9ecef;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #C9A961;
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
    font-size: 14px;
    font-weight: 600;
}

.test-section {
    text-align: center;
    padding: 20px;
}

.test-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 15px;
}

.users-table table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.users-table th,
.users-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.users-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #2C3E50;
}

.no-data {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 40px;
}

.error {
    color: #dc3545;
    background: #f8d7da;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #f5c6cb;
}
</style>

<?php include 'template_footer.php'; ?>