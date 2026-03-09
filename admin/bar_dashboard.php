<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin login
requireAdminLogin();

// Set page variables for template
$pageTitle = 'Bar Management';
$currentPage = 'bar_dashboard';
?>
<?php include 'template_header.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="fas fa-cocktail"></i> Bar Management</h1>
    <p>Manage your mini bar and main bar menu items</p>
</div>

<div class="spa-dashboard-content">
    <!-- Bar Type Toggle -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="bar-type-toggle">
            <button class="toggle-btn active" id="miniBarBtn" onclick="switchBarType('mini')">
                <i class="fas fa-wine-bottle"></i> Mini Bar
            </button>
            <button class="toggle-btn" id="mainBarBtn" onclick="switchBarType('main')">
                <i class="fas fa-glass-martini-alt"></i> Main Bar
            </button>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Add Item Section -->
        <div class="card">
            <h2>Add <span id="barTypeLabel">Mini Bar</span> Item</h2>
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
                <label>Item Image</label>
                <input type="file" id="itemImage" accept="image/*">
                <small>Upload an image for this item (optional, max 5MB)</small>
            </div>
            <button class="btn" onclick="addItem()">Add Item</button>
        </div>

        <!-- Stats Section -->
        <div class="card">
            <h2><span id="statsBarTypeLabel">Mini Bar</span> Overview</h2>
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

    <!-- Items Gallery -->
    <div class="card">
        <h2><span id="galleryBarTypeLabel">Mini Bar</span> Menu</h2>
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
            <h2>Edit Bar Item</h2>
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
                <label>Item Image</label>
                <input type="file" id="editItemImage" accept="image/*">
                <small>Upload a new image to replace current one (optional)</small>
            </div>
            <button class="btn" onclick="saveEdit()">Save Changes</button>
        </div>
    </div>
</div>

<style>
.bar-type-toggle {
    display: flex;
    gap: 1rem;
    justify-content: center;
    padding: 1rem;
}

.toggle-btn {
    flex: 1;
    max-width: 300px;
    padding: 1.5rem 2rem;
    font-size: 1.2rem;
    font-weight: 600;
    border: 2px solid #C9A961;
    background: white;
    color: #333;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.toggle-btn:hover {
    background: #f8f8f8;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.toggle-btn.active {
    background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%);
    color: white;
    box-shadow: 0 5px 20px rgba(201, 169, 97, 0.4);
}

.toggle-btn i {
    font-size: 1.5rem;
}
</style>

<link rel="stylesheet" href="assets/css/spa-styles.css?v=<?php echo time(); ?>">
<script src="assets/js/bar-script.js?v=<?php echo time(); ?>"></script>

<?php include 'template_footer.php'; ?>
