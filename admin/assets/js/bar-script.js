// Bar items storage
let items = [];
let selectedItemIndex = null;
let currentBarType = 'mini'; // Default to mini bar

console.log('🔵 BAR SCRIPT LOADED');

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔵 DOM Content Loaded - Initializing bar dashboard');
    loadItems();
});

// Switch bar type
function switchBarType(barType) {
    console.log('🔄 Switching to', barType, 'bar');
    currentBarType = barType;
    
    // Update button states
    document.getElementById('miniBarBtn').classList.toggle('active', barType === 'mini');
    document.getElementById('mainBarBtn').classList.toggle('active', barType === 'main');
    
    // Update labels
    const label = barType === 'mini' ? 'Mini Bar' : 'Main Bar';
    document.getElementById('barTypeLabel').textContent = label;
    document.getElementById('statsBarTypeLabel').textContent = label;
    document.getElementById('galleryBarTypeLabel').textContent = label;
    
    // Load items for selected bar type
    loadItems();
}

// Load items from database
function loadItems() {
    const formData = new FormData();
    formData.append('action', 'get_items');
    formData.append('bar_type', currentBarType);
    
    fetch('bar_menu_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            items = data.items;
            console.log('✅ Loaded', items.length, 'items from database for', currentBarType, 'bar');
            updateDisplay();
        } else {
            console.error('❌ Error loading items:', data.message);
            showNotification('Error loading items', 'error');
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showNotification('Error loading items', 'error');
    });
}

// Add item
function addItem() {
    console.log('🔵 ADD ITEM CLICKED');
    
    const name = document.getElementById('itemName').value.trim();
    const description = document.getElementById('itemDescription').value.trim();
    const price = parseFloat(document.getElementById('itemPrice').value);
    const imageFile = document.getElementById('itemImage').files[0];

    console.log('📝 Form values:', { name, description, price, barType: currentBarType, hasImage: !!imageFile });

    // Validation
    if (!name) {
        console.log('❌ Validation failed: name');
        showNotification('Please enter item name', 'error');
        return;
    }
    if (!description) {
        console.log('❌ Validation failed: description');
        showNotification('Please enter item description', 'error');
        return;
    }
    if (!price || price <= 0) {
        console.log('❌ Validation failed: price');
        showNotification('Please enter a valid price', 'error');
        return;
    }

    console.log('✅ Validation passed');

    // Create FormData
    const formData = new FormData();
    formData.append('action', 'add_item');
    formData.append('name', name);
    formData.append('description', description);
    formData.append('bar_type', currentBarType);
    formData.append('price', price);
    
    if (imageFile) {
        console.log('📸 Adding image:', imageFile.name, imageFile.size, 'bytes');
        formData.append('image', imageFile);
    }

    // Send to server
    fetch('bar_menu_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Item added successfully');
            showNotification(data.message, 'success');
            clearAddForm();
            loadItems(); // Reload items
        } else {
            console.error('❌ Error:', data.message);
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showNotification('Error adding item', 'error');
    });
}

// Clear add form
function clearAddForm() {
    document.getElementById('itemName').value = '';
    document.getElementById('itemDescription').value = '';
    document.getElementById('itemPrice').value = '';
    document.getElementById('itemImage').value = '';
}

// Update display
function updateDisplay() {
    const gallery = document.getElementById('itemsGallery');
    gallery.innerHTML = '';

    if (items.length === 0) {
        gallery.innerHTML = '<p style="color: #999; text-align: center; padding: 40px; grid-column: 1/-1;">No items added yet. Add your first item!</p>';
        updateStats();
        return;
    }

    items.forEach((item, index) => {
        const itemElement = document.createElement('div');
        itemElement.className = `service-item ${!item.available ? 'disabled' : ''}`;
        
        let imageHtml = '';
        if (item.image) {
            imageHtml = `<div class="service-item-image">
                <img src="../uploads/bar/${item.image}" alt="${item.name}" style="width: 100%; height: 200px; object-fit: cover; margin-bottom: 15px; border-radius: 10px;">
            </div>`;
        }
        
        itemElement.innerHTML = `
            <div class="service-item-content">
                ${imageHtml}
                <div class="service-item-header">
                    <div class="service-item-title">${item.name}</div>
                    <div class="service-item-price">₱${parseFloat(item.price).toFixed(2)}</div>
                </div>
                <div class="service-item-description">${item.description}</div>
                <div class="service-item-actions">
                    <button class="btn btn-secondary btn-small" onclick="editItem(${index})">Edit</button>
                    <button class="btn btn-danger btn-small" onclick="deleteItem(${index})">Delete</button>
                </div>
                <div class="service-item-status">
                    <span class="status-label">${item.available ? 'Available' : 'Unavailable'}</span>
                    <label class="toggle-switch">
                        <input type="checkbox" ${item.available ? 'checked' : ''} onchange="toggleItem(${index})">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        `;
        gallery.appendChild(itemElement);
    });

    updateStats();
}

// Edit item
function editItem(index) {
    selectedItemIndex = index;
    const item = items[index];

    document.getElementById('editItemName').value = item.name;
    document.getElementById('editItemDescription').value = item.description;
    document.getElementById('editItemPrice').value = item.price;

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

    const item = items[selectedItemIndex];
    const name = document.getElementById('editItemName').value.trim();
    const description = document.getElementById('editItemDescription').value.trim();
    const price = parseFloat(document.getElementById('editItemPrice').value);
    const imageFile = document.getElementById('editItemImage').files[0];

    // Validation
    if (!name) {
        showNotification('Please enter item name', 'error');
        return;
    }
    if (!description) {
        showNotification('Please enter item description', 'error');
        return;
    }
    if (!price || price <= 0) {
        showNotification('Please enter a valid price', 'error');
        return;
    }

    // Create FormData
    const formData = new FormData();
    formData.append('action', 'update_item');
    formData.append('id', item.id);
    formData.append('name', name);
    formData.append('description', description);
    formData.append('price', price);
    
    if (imageFile) {
        formData.append('image', imageFile);
    }

    // Send to server
    fetch('bar_menu_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeEditModal();
            loadItems(); // Reload items
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showNotification('Error updating item', 'error');
    });
}

// Toggle item
function toggleItem(index) {
    const item = items[index];
    const newAvailable = item.available ? 0 : 1;
    
    const formData = new FormData();
    formData.append('action', 'toggle_item');
    formData.append('id', item.id);
    formData.append('available', newAvailable);
    
    fetch('bar_menu_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            loadItems(); // Reload items
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showNotification('Error toggling item', 'error');
    });
}

// Delete item
function deleteItem(index) {
    if (!confirm('Are you sure you want to delete this item?')) return;
    
    const item = items[index];
    
    const formData = new FormData();
    formData.append('action', 'delete_item');
    formData.append('id', item.id);
    
    fetch('bar_menu_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            loadItems(); // Reload items
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showNotification('Error deleting item', 'error');
    });
}

// Update statistics
function updateStats() {
    const total = items.length;
    const available = items.filter(item => item.available).length;
    const unavailable = total - available;

    document.getElementById('totalItems').textContent = total;
    document.getElementById('availableItems').textContent = available;
    document.getElementById('unavailableItems').textContent = unavailable;

    // Price statistics
    if (items.length > 0) {
        const prices = items.map(i => parseFloat(i.price));
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
