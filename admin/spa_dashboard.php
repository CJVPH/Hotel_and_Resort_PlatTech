<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin login
requireAdminLogin();

// Set page variables for template
$pageTitle = 'Spa Management';
$currentPage = 'spa_dashboard';
?>
<?php include 'template_header.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="fas fa-spa"></i> Spa & Wellness Management</h1>
    <p>Manage your spa services and treatments</p>
</div>

<div class="spa-dashboard-content">
    <div class="dashboard-grid">
        <!-- Add Service Section -->
        <div class="card">
            <h2>Add Service</h2>
            <div class="form-group">
                <label>Service Name</label>
                <input type="text" id="serviceName" placeholder="Enter service name">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="serviceDescription" placeholder="Enter service description"></textarea>
            </div>
            <div class="form-group">
                <label>Price (PHP)</label>
                <input type="number" id="servicePrice" placeholder="0.00" step="10" min="0">
            </div>
            <div class="form-group">
                <label>Duration (minutes)</label>
                <input type="number" id="serviceDuration" placeholder="60" min="15" step="15">
            </div>
            <div class="form-group">
                <label>Service Image</label>
                <input type="file" id="serviceImage" accept="image/*">
                <small>Upload an image for this service (optional, max 5MB)</small>
            </div>
            <button class="btn" onclick="addService()">Add Service</button>
        </div>

        <!-- Stats Section -->
        <div class="card">
            <h2>Service Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" id="totalServices">0</div>
                    <div class="stat-label">Total Services</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="activeServices">0</div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="disabledServices">0</div>
                    <div class="stat-label">Disabled</div>
                </div>
            </div>
            <div class="price-range-section">
                <h3>Price Range</h3>
                <div class="price-info">
                    <div class="price-item">
                        <span class="price-label">Lowest Price</span>
                        <span class="price-value" id="lowestPrice">₱0.00</span>
                    </div>
                    <div class="price-item">
                        <span class="price-label">Highest Price</span>
                        <span class="price-value" id="highestPrice">₱0.00</span>
                    </div>
                    <div class="price-item">
                        <span class="price-label">Average Price</span>
                        <span class="price-value" id="averagePrice">₱0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Controls -->
    <div class="card">
        <h2>Admin Controls</h2>
        <div class="admin-controls">
            <div class="control-item">
                <span class="control-label">Bulk Price Adjustment</span>
                <div class="control-actions">
                    <input type="number" id="bulkAdjustment" placeholder="0" step="1">
                    <select id="adjustmentType">
                        <option value="percent">%</option>
                        <option value="amount">₱</option>
                    </select>
                    <button class="btn btn-secondary" onclick="applyBulkPriceAdjustment()">Apply to All</button>
                </div>
            </div>
            <div class="control-item">
                <span class="control-label">Quick Actions</span>
                <div class="control-actions">
                    <button class="btn btn-secondary" onclick="enableAllServices()">Enable All</button>
                    <button class="btn btn-secondary" onclick="disableAllServices()">Disable All</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Services Gallery -->
    <div class="card">
        <h2>Service List</h2>
        <div class="services-gallery" id="servicesGallery">
            <!-- Service items will be added here dynamically -->
        </div>
    </div>
</div>

<div class="notification" id="notification"></div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Service</h2>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Service Name</label>
                <input type="text" id="editServiceName" placeholder="Enter service name">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="editServiceDescription" placeholder="Enter service description"></textarea>
            </div>
            <div class="form-group">
                <label>Price (PHP)</label>
                <input type="number" id="editServicePrice" placeholder="0.00" step="10" min="0">
            </div>
            <div class="form-group">
                <label>Duration (minutes)</label>
                <input type="number" id="editServiceDuration" placeholder="60" min="15" step="15">
            </div>
            <div class="form-group">
                <label>Service Image</label>
                <input type="file" id="editServiceImage" accept="image/*">
                <small>Upload a new image to replace current one (optional)</small>
            </div>
            <button class="btn" onclick="saveEdit()">Save Changes</button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="assets/css/spa-styles.css?v=<?php echo time(); ?>">
<script src="assets/js/spa-script.js?v=<?php echo time(); ?>"></script>

<?php include 'template_footer.php'; ?>