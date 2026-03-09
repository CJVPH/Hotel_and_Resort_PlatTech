<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Require admin login
requireAdminLogin();

// Set page variables for template
$pageTitle = 'Restaurant Management';
$currentPage = 'restaurant_dashboard';
?>
<?php include 'template_header.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="fas fa-utensils"></i> Restaurant Management</h1>
    <p>Manage your restaurant menu and items</p>
</div>

<div class="restaurant-dashboard-content">
    <div class="dashboard-grid">
        <!-- Add Menu Item Section -->
        <div class="card">
            <h2>Add Menu Item</h2>
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" id="itemName" placeholder="Enter menu item name">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="itemDescription" placeholder="Enter item description"></textarea>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select id="itemCategory">
                    <option value="">Select Category</option>
                    <option value="appetizer">Appetizer</option>
                    <option value="main">Main Course</option>
                    <option value="dessert">Dessert</option>
                    <option value="beverage">Beverage</option>
                    <option value="special">Chef's Special</option>
                </select>
            </div>
            <div class="form-group">
                <label>Price (PHP)</label>
                <input type="number" id="itemPrice" placeholder="0.00" step="10" min="0">
            </div>
            <div class="form-group">
                <label>Preparation Time (minutes)</label>
                <input type="number" id="itemPrepTime" placeholder="15" min="5" step="5">
            </div>
            <div class="form-group">
                <label>Menu Item Image</label>
                <input type="file" id="itemImage" accept="image/*">
                <small>Upload an image for this menu item (optional, max 5MB)</small>
            </div>
            <button class="btn" onclick="addMenuItem()">Add Menu Item</button>
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
                    <button class="btn btn-secondary" onclick="enableAllItems()">Make All Available</button>
                    <button class="btn btn-secondary" onclick="disableAllItems()">Make All Unavailable</button>
                </div>
            </div>
            <div class="control-item">
                <span class="control-label">Filter by Category</span>
                <div class="control-actions">
                    <select id="categoryFilter" onchange="filterByCategory()">
                        <option value="">All Categories</option>
                        <option value="appetizer">Appetizers</option>
                        <option value="main">Main Courses</option>
                        <option value="dessert">Desserts</option>
                        <option value="beverage">Beverages</option>
                        <option value="special">Chef's Specials</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu Items Gallery -->
    <div class="card">
        <h2>Menu Items</h2>
        <div class="menu-gallery" id="menuGallery">
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
                <input type="text" id="editItemName" placeholder="Enter menu item name">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="editItemDescription" placeholder="Enter item description"></textarea>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select id="editItemCategory">
                    <option value="appetizer">Appetizer</option>
                    <option value="main">Main Course</option>
                    <option value="dessert">Dessert</option>
                    <option value="beverage">Beverage</option>
                    <option value="special">Chef's Special</option>
                </select>
            </div>
            <div class="form-group">
                <label>Price (PHP)</label>
                <input type="number" id="editItemPrice" placeholder="0.00" step="10" min="0">
            </div>
            <div class="form-group">
                <label>Preparation Time (minutes)</label>
                <input type="number" id="editItemPrepTime" placeholder="15" min="5" step="5">
            </div>
            <button class="btn" onclick="saveEdit()">Save Changes</button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="assets/css/restaurant-styles.css?v=<?php echo time(); ?>">
<script src="assets/js/restaurant-script.js?v=<?php echo time(); ?>"></script>

<?php include 'template_footer.php'; ?>