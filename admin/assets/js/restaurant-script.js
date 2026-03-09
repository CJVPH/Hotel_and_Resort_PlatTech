// Menu items storage
let menuItems = [];
let selectedItemIndex = null;
let currentFilter = '';

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadMenuItems();
});

// Load menu items from database
function loadMenuItems() {
    fetch('restaurant_menu_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_items'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            menuItems = data.items || [];
            updateDisplay();
        } else {
            menuItems = [];
            showNotification('Error loading menu items: ' + data.message, 'error');
            updateDisplay();
        }
    })
    .catch(error => {
        console.error('Error loading menu items:', error);
        menuItems = [];
        updateDisplay();
    });
}

// Add menu item
function addMenuItem() {
    const name = document.getElementById('itemName').value.trim();
    const description = document.getElementById('itemDescription').value.trim();
    const category = document.getElementById('itemCategory').value;
    const price = parseFloat(document.getElementById('itemPrice').value);
    const prepTime = parseInt(document.getElementById('itemPrepTime').value);

    // Validation
    if (!name) {
        showNotification('Please enter item name', 'error');
        return;
    }
    if (!description) {
        showNotification('Please enter item description', 'error');
        return;
    }
    if (!category) {
        showNotification('Please select a category', 'error');
        return;
    }
    if (!price || price <= 0) {
        showNotification('Please enter a valid price', 'error');
        return;
    }
    if (!prepTime || prepTime <= 0) {
        showNotification('Please enter a valid preparation time', 'error');
        return;
    }

    // Create FormData
    const formData = new FormData();
    formData.append('action', 'add_item');
    formData.append('name', name);
    formData.append('description', description);
    formData.append('category', category);
    formData.append('price', price);
    formData.append('prep_time', prepTime);
    
    // Add image if selected
    const imageFile = document.getElementById('itemImage').files[0];
    if (imageFile) {
        formData.append('image', imageFile);
    }

    // Send to server
    fetch('restaurant_menu_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text().then(text => {
            console.log('Response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON:', e);
                throw new Error('Invalid JSON response: ' + text);
            }
        });
    })
    .then(data => {
        console.log('Parsed data:', data);
        if (data.success) {
            // Clear form
            document.getElementById('itemName').value = '';
            document.getElementById('itemDescription').value = '';
            document.getElementById('itemCategory').value = '';
            document.getElementById('itemPrice').value = '';
            document.getElementById('itemPrepTime').value = '';
            document.getElementById('itemImage').value = '';

            // Reload menu items
            loadMenuItems();
            showNotification('Menu item added successfully', 'success');
        } else {
            showNotification(data.message || 'Error adding menu item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error adding menu item: ' + error.message, 'error');
    });
}

// Get category display name
function getCategoryDisplayName(category) {
    const categoryNames = {
        'appetizer': 'Appetizer',
        'main': 'Main Course',
        'dessert': 'Dessert',
        'beverage': 'Beverage',
        'special': "Chef's Special"
    };
    return categoryNames[category] || category;
}

// Get category color
function getCategoryColor(category) {
    const categoryColors = {
        'appetizer': '#28a745',
        'main': '#dc3545',
        'dessert': '#ffc107',
        'beverage': '#17a2b8',
        'special': '#6f42c1'
    };
    return categoryColors[category] || '#6c757d';
}

// Update display
function updateDisplay() {
    const gallery = document.getElementById('menuGallery');
    gallery.innerHTML = '';

    let itemsToShow = menuItems;
    if (currentFilter) {
        itemsToShow = menuItems.filter(item => item.category === currentFilter);
    }

    if (itemsToShow.length === 0) {
        const message = currentFilter ? 
            `No items found in ${getCategoryDisplayName(currentFilter)} category.` : 
            'No menu items added yet. Add your first menu item!';
        gallery.innerHTML = `<p style="color: #999; text-align: center; padding: 40px; grid-column: 1/-1;">${message}</p>`;
        updateStats();
        return;
    }

    itemsToShow.forEach((item, index) => {
        const actualIndex = menuItems.indexOf(item);
        const menuItem = document.createElement('div');
        menuItem.className = `menu-item ${!item.available ? 'disabled' : ''}`;
        
        let imageHtml = '';
        if (item.image) {
            imageHtml = `<div class="menu-item-image">
                <img src="../uploads/restaurant/${item.image}" alt="${item.name}" style="width: 100%; height: 150px; object-fit: cover; margin-bottom: 10px; border-radius: 8px;">
            </div>`;
        }
        
        menuItem.innerHTML = `
            <div class="menu-item-content">
                ${imageHtml}
                <div class="menu-item-category" style="background-color: ${getCategoryColor(item.category)}">
                    ${getCategoryDisplayName(item.category)}
                </div>
                <div class="menu-item-prep-time">${item.prep_time} MIN PREP</div>
                <div class="menu-item-header">
                    <div class="menu-item-title">${item.name}</div>
                    <div class="menu-item-price">₱${item.price.toFixed(2)}</div>
                </div>
                <div class="menu-item-description">${item.description}</div>
                <div class="menu-item-actions">
                    <button class="btn btn-secondary btn-small" onclick="editMenuItem(${actualIndex})">Edit</button>
                    <button class="btn btn-danger btn-small" onclick="deleteMenuItem(${actualIndex})">Delete</button>
                </div>
                <div class="menu-item-status">
                    <span class="status-label">${item.available ? 'Available' : 'Unavailable'}</span>
                    <label class="toggle-switch">
                        <input type="checkbox" ${item.available ? 'checked' : ''} onchange="toggleMenuItem(${actualIndex})">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        `;
        gallery.appendChild(menuItem);
    });

    updateStats();
}

// Filter by category
function filterByCategory() {
    currentFilter = document.getElementById('categoryFilter').value;
    updateDisplay();
}

// Edit menu item
function editMenuItem(index) {
    selectedItemIndex = index;
    const item = menuItems[index];

    document.getElementById('editItemName').value = item.name;
    document.getElementById('editItemDescription').value = item.description;
    document.getElementById('editItemCategory').value = item.category;
    document.getElementById('editItemPrice').value = item.price;
    document.getElementById('editItemPrepTime').value = item.prep_time;

    document.getElementById('editModal').classList.add('show');
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
    selectedItemIndex = null;
}

// Save edit
function saveEdit() {
    if (selectedItemIndex === null) return;

    const name = document.getElementById('editItemName').value.trim();
    const description = document.getElementById('editItemDescription').value.trim();
    const category = document.getElementById('editItemCategory').value;
    const price = parseFloat(document.getElementById('editItemPrice').value);
    const prepTime = parseInt(document.getElementById('editItemPrepTime').value);

    // Validation
    if (!name) {
        showNotification('Please enter item name', 'error');
        return;
    }
    if (!description) {
        showNotification('Please enter item description', 'error');
        return;
    }
    if (!category) {
        showNotification('Please select a category', 'error');
        return;
    }
    if (!price || price <= 0) {
        showNotification('Please enter a valid price', 'error');
        return;
    }
    if (!prepTime || prepTime <= 0) {
        showNotification('Please enter a valid preparation time', 'error');
        return;
    }

    const item = menuItems[selectedItemIndex];
    
    // Create FormData
    const formData = new FormData();
    formData.append('action', 'update_item');
    formData.append('id', item.id);
    formData.append('name', name);
    formData.append('description', description);
    formData.append('category', category);
    formData.append('price', price);
    formData.append('prep_time', prepTime);

    // Send to server
    fetch('restaurant_menu_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeEditModal();
            loadMenuItems();
            showNotification('Menu item updated successfully', 'success');
        } else {
            showNotification(data.message || 'Error updating menu item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating menu item', 'error');
    });
}

// Toggle menu item
function toggleMenuItem(index) {
    const item = menuItems[index];
    const newStatus = !item.available;
    
    const formData = new FormData();
    formData.append('action', 'toggle_item');
    formData.append('id', item.id);
    formData.append('available', newStatus ? 1 : 0);
    
    fetch('restaurant_menu_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadMenuItems();
            const status = newStatus ? 'available' : 'unavailable';
            showNotification(`Menu item marked as ${status}`, 'success');
        } else {
            showNotification(data.message || 'Error updating menu item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating menu item', 'error');
    });
}

// Delete menu item
function deleteMenuItem(index) {
    if (confirm('Are you sure you want to delete this menu item?')) {
        const item = menuItems[index];
        const itemName = item.name;
        
        const formData = new FormData();
        formData.append('action', 'delete_item');
        formData.append('id', item.id);
        
        fetch('restaurant_menu_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadMenuItems();
                showNotification(`"${itemName}" deleted`, 'success');
            } else {
                showNotification(data.message || 'Error deleting menu item', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error deleting menu item', 'error');
        });
    }
}

// Admin Controls - Bulk price adjustment
function applyBulkPriceAdjustment() {
    const adjustment = parseFloat(document.getElementById('bulkAdjustment').value);
    const type = document.getElementById('adjustmentType').value;

    if (!adjustment || adjustment === 0) {
        showNotification('Please enter an adjustment value', 'error');
        return;
    }

    if (!confirm(`Are you sure you want to apply this adjustment to all menu items?`)) {
        return;
    }

    menuItems.forEach(item => {
        if (type === 'percent') {
            item.price = item.price * (1 + adjustment / 100);
        } else {
            item.price = item.price + adjustment;
        }
        // Ensure price doesn't go negative
        if (item.price < 0) {
            item.price = 0;
        }
    });

    updateDisplay();
    document.getElementById('bulkAdjustment').value = '';
    const adjustmentText = type === 'percent' ? `${adjustment}%` : `₱${adjustment}`;
    showNotification(`Bulk price adjustment of ${adjustmentText} applied to all menu items`, 'success');
}

// Enable all items
function enableAllItems() {
    if (!confirm('Make all menu items available?')) return;
    menuItems.forEach(item => item.available = true);
    updateDisplay();
    showNotification('All menu items are now available', 'success');
}

// Disable all items
function disableAllItems() {
    if (!confirm('Make all menu items unavailable?')) return;
    menuItems.forEach(item => item.available = false);
    updateDisplay();
    showNotification('All menu items are now unavailable', 'success');
}

// Update statistics
function updateStats() {
    const total = menuItems.length;
    const available = menuItems.filter(item => item.available).length;
    const unavailable = total - available;

    document.getElementById('totalItems').textContent = total;
    document.getElementById('availableItems').textContent = available;
    document.getElementById('unavailableItems').textContent = unavailable;

    // Price statistics
    if (menuItems.length > 0) {
        const prices = menuItems.map(item => item.price);
        const lowest = Math.min(...prices);
        const highest = Math.max(...prices);
        const average = prices.reduce((a, b) => a + b, 0) / prices.length;

        document.getElementById('lowestPrice').textContent = `₱${lowest.toFixed(2)}`;
        document.getElementById('highestPrice').textContent = `₱${highest.toFixed(2)}`;
        document.getElementById('averagePrice').textContent = `₱${average.toFixed(2)}`;
    } else {
        document.getElementById('lowestPrice').textContent = '₱0.00';
        document.getElementById('highestPrice').textContent = '₱0.00';
        document.getElementById('averagePrice').textContent = '₱0.00';
    }
}

// Show notification
function showNotification(message, type) {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = `notification ${type} show`;
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeEditModal();
    }
}