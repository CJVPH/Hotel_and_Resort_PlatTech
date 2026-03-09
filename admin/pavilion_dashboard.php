<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin login
requireAdminLogin();

// Set page variables for template
$pageTitle = 'Pavilion Menu Management';
$currentPage = 'pavilion_dashboard';
?>
<?php include 'template_header.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="fas fa-utensils"></i> Pavilion Menu Management</h1>
    <p>Manage your pavilion menu items</p>
</div>

<div class="spa-dashboard-content">
    <div class="dashboard-grid">
        <!-- Add Menu Item Section -->
        <div class="card">
            <h2>Add Menu Item</h2>
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" id="itemName" placeholder="Enter item name">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="itemDescription" placeholder="Enter item description"></textarea>
            </div>
            <div class="form-group">
                <label>Price (₱)</label>
                <input type="number" id="itemPrice" placeholder="0.00" step="10" min="0">
            </div>
            <div class="form-group">
                <label>Prep Time (minutes)</label>
                <input type="number" id="itemPrepTime" placeholder="15" min="5" step="5">
            </div>
            <div class="form-group">
                <label>Item Image</label>
                <input type="file" id="itemImage" accept="image/*">
                <small>Upload an image for this item (optional, max 5MB)</small>
            </div>
            <button class="btn" onclick="addItem()">Add Menu Item</button>
        </div>

        <!-- Stats Section -->
        <div class="card">
            <h2>Menu Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" id="totalItems">0</div>
                    <div class="stat-label">Total Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="availableItems">0</div>
                    <div class="stat-label">Available</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="unavailableItems">0</div>
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

    <!-- Menu Gallery -->
    <div class="card">
        <h2>Menu Items</h2>
        <div class="services-gallery" id="itemsGallery">
            <!-- Menu items will be added here dynamically -->
        </div>
    </div>
</div>

<div class="notification" id="notification"></div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Menu Item</h2>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" id="editItemName" placeholder="Enter item name">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="editItemDescription" placeholder="Enter item description"></textarea>
            </div>
            <div class="form-group">
                <label>Price (₱)</label>
                <input type="number" id="editItemPrice" placeholder="0.00" step="10" min="0">
            </div>
            <div class="form-group">
                <label>Prep Time (minutes)</label>
                <input type="number" id="editItemPrepTime" placeholder="15" min="5" step="5">
            </div>
            <div class="form-group">
                <label>Item Image</label>
                <input type="file" id="editItemImage" accept="image/*">
                <small>Upload a new image to replace current one (optional)</small>
            </div>
            <button class="btn" onclick="saveEdit()">Save Changes</button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="assets/css/spa-styles.css?v=<?php echo time(); ?>">
<script src="assets/js/pavilion-script.js?v=<?php echo time(); ?>"></script>

<?php include 'template_footer.php'; ?>
