<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin login
requireAdminLogin();

// Set page variables for template
$pageTitle = 'Water Activities Management';
$currentPage = 'water_activities_dashboard';
?>
<?php include 'template_header.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="fas fa-water"></i> Water Activities Management</h1>
    <p>Manage your water activities and experiences</p>
</div>

<div class="spa-dashboard-content">
    <div class="dashboard-grid">
        <!-- Add Activity Section -->
        <div class="card">
            <h2>Add Activity</h2>
            <div class="form-group">
                <label>Activity Name</label>
                <input type="text" id="activityName" placeholder="Enter activity name">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="activityDescription" placeholder="Enter activity description"></textarea>
            </div>
            <div class="form-group">
                <label>Price (₱)</label>
                <input type="number" id="activityPrice" placeholder="0.00" step="10" min="0">
            </div>
            <div class="form-group">
                <label>Duration (minutes)</label>
                <input type="number" id="activityDuration" placeholder="60" min="15" step="15">
            </div>
            <div class="form-group">
                <label>Activity Image</label>
                <input type="file" id="activityImage" accept="image/*">
                <small>Upload an image for this activity (optional, max 5MB)</small>
            </div>
            <button class="btn" onclick="addActivity()">Add Activity</button>
        </div>

        <!-- Stats Section -->
        <div class="card">
            <h2>Activities Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" id="totalActivities">0</div>
                    <div class="stat-label">Total Activities</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="availableActivities">0</div>
                    <div class="stat-label">Available</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="unavailableActivities">0</div>
                    <div class="stat-label">Unavailable</div>
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

    <!-- Activities Gallery -->
    <div class="card">
        <h2>Activities List</h2>
        <div class="services-gallery" id="activitiesGallery">
            <!-- Activity items will be added here dynamically -->
        </div>
    </div>
</div>

<div class="notification" id="notification"></div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Activity</h2>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Activity Name</label>
                <input type="text" id="editActivityName" placeholder="Enter activity name">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="editActivityDescription" placeholder="Enter activity description"></textarea>
            </div>
            <div class="form-group">
                <label>Price (₱)</label>
                <input type="number" id="editActivityPrice" placeholder="0.00" step="10" min="0">
            </div>
            <div class="form-group">
                <label>Duration (minutes)</label>
                <input type="number" id="editActivityDuration" placeholder="60" min="15" step="15">
            </div>
            <div class="form-group">
                <label>Activity Image</label>
                <input type="file" id="editActivityImage" accept="image/*">
                <small>Upload a new image to replace current one (optional)</small>
            </div>
            <button class="btn" onclick="saveEdit()">Save Changes</button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="assets/css/spa-styles.css?v=<?php echo time(); ?>">
<script src="assets/js/water-activities-script.js?v=<?php echo time(); ?>"></script>

<?php include 'template_footer.php'; ?>
