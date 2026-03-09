// Services storage
let services = [];
let selectedServiceIndex = null;

console.log('🔵 SPA SCRIPT LOADED - Version 2.0');

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔵 DOM Content Loaded - Initializing spa dashboard');
    loadServices();
});

// Load services from database
function loadServices() {
    const formData = new FormData();
    formData.append('action', 'get_services');
    
    fetch('spa_service_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            services = data.services;
            console.log('✅ Loaded', services.length, 'services from database');
            updateDisplay();
        } else {
            console.error('❌ Error loading services:', data.message);
            showNotification('Error loading services', 'error');
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showNotification('Error loading services', 'error');
    });
}

// Add service
function addService() {
    console.log('🔵 ADD SERVICE CLICKED');
    
    const name = document.getElementById('serviceName').value.trim();
    const description = document.getElementById('serviceDescription').value.trim();
    const price = parseFloat(document.getElementById('servicePrice').value);
    const duration = parseInt(document.getElementById('serviceDuration').value);
    const imageFile = document.getElementById('serviceImage').files[0];

    console.log('📝 Form values:', { name, description, price, duration, hasImage: !!imageFile });

    // Validation
    if (!name) {
        console.log('❌ Validation failed: name');
        showNotification('Please enter service name', 'error');
        return;
    }
    if (!description) {
        console.log('❌ Validation failed: description');
        showNotification('Please enter service description', 'error');
        return;
    }
    if (!price || price <= 0) {
        console.log('❌ Validation failed: price');
        showNotification('Please enter a valid price', 'error');
        return;
    }
    if (!duration || duration <= 0) {
        console.log('❌ Validation failed: duration');
        showNotification('Please enter a valid duration', 'error');
        return;
    }

    console.log('✅ Validation passed');

    // Create FormData
    const formData = new FormData();
    formData.append('action', 'add_service');
    formData.append('name', name);
    formData.append('description', description);
    formData.append('price', price);
    formData.append('duration', duration);
    
    if (imageFile) {
        console.log('📸 Adding image:', imageFile.name, imageFile.size, 'bytes');
        formData.append('image', imageFile);
    }

    // Send to server
    fetch('spa_service_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Service added successfully');
            showNotification(data.message, 'success');
            clearAddForm();
            loadServices(); // Reload services
        } else {
            console.error('❌ Error:', data.message);
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showNotification('Error adding service', 'error');
    });
}

// Clear add form
function clearAddForm() {
    document.getElementById('serviceName').value = '';
    document.getElementById('serviceDescription').value = '';
    document.getElementById('servicePrice').value = '';
    document.getElementById('serviceDuration').value = '';
    document.getElementById('serviceImage').value = '';
}

// Update display
function updateDisplay() {
    const gallery = document.getElementById('servicesGallery');
    gallery.innerHTML = '';

    if (services.length === 0) {
        gallery.innerHTML = '<p style="color: #999; text-align: center; padding: 40px; grid-column: 1/-1;">No services added yet. Add your first service!</p>';
        updateStats();
        return;
    }

    services.forEach((service, index) => {
        const serviceItem = document.createElement('div');
        serviceItem.className = `service-item ${!service.enabled ? 'disabled' : ''}`;
        
        let imageHtml = '';
        if (service.image) {
            imageHtml = `<div class="service-item-image">
                <img src="../uploads/spa/${service.image}" alt="${service.name}" style="width: 100%; height: 200px; object-fit: cover; margin-bottom: 15px; border-radius: 10px;">
            </div>`;
        }
        
        serviceItem.innerHTML = `
            <div class="service-item-content">
                ${imageHtml}
                <div class="service-item-duration">${service.duration} MINUTES</div>
                <div class="service-item-header">
                    <div class="service-item-title">${service.name}</div>
                    <div class="service-item-price">₱${parseFloat(service.price).toFixed(2)}</div>
                </div>
                <div class="service-item-description">${service.description}</div>
                <div class="service-item-actions">
                    <button class="btn btn-secondary btn-small" onclick="editService(${index})">Edit</button>
                    <button class="btn btn-danger btn-small" onclick="deleteService(${index})">Delete</button>
                </div>
                <div class="service-item-status">
                    <span class="status-label">${service.enabled ? 'Available' : 'Unavailable'}</span>
                    <label class="toggle-switch">
                        <input type="checkbox" ${service.enabled ? 'checked' : ''} onchange="toggleService(${index})">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        `;
        gallery.appendChild(serviceItem);
    });

    updateStats();
}

// Edit service
function editService(index) {
    selectedServiceIndex = index;
    const service = services[index];

    document.getElementById('editServiceName').value = service.name;
    document.getElementById('editServiceDescription').value = service.description;
    document.getElementById('editServicePrice').value = service.price;
    document.getElementById('editServiceDuration').value = service.duration;

    document.getElementById('editModal').classList.add('show');
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
    selectedServiceIndex = null;
}

// Save edit
function saveEdit() {
    if (selectedServiceIndex === null) return;

    const service = services[selectedServiceIndex];
    const name = document.getElementById('editServiceName').value.trim();
    const description = document.getElementById('editServiceDescription').value.trim();
    const price = parseFloat(document.getElementById('editServicePrice').value);
    const duration = parseInt(document.getElementById('editServiceDuration').value);
    const imageFile = document.getElementById('editServiceImage').files[0];

    // Validation
    if (!name) {
        showNotification('Please enter service name', 'error');
        return;
    }
    if (!description) {
        showNotification('Please enter service description', 'error');
        return;
    }
    if (!price || price <= 0) {
        showNotification('Please enter a valid price', 'error');
        return;
    }
    if (!duration || duration <= 0) {
        showNotification('Please enter a valid duration', 'error');
        return;
    }

    // Create FormData
    const formData = new FormData();
    formData.append('action', 'update_service');
    formData.append('id', service.id);
    formData.append('name', name);
    formData.append('description', description);
    formData.append('price', price);
    formData.append('duration', duration);
    
    if (imageFile) {
        formData.append('image', imageFile);
    }

    // Send to server
    fetch('spa_service_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeEditModal();
            loadServices(); // Reload services
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showNotification('Error updating service', 'error');
    });
}

// Toggle service
function toggleService(index) {
    const service = services[index];
    const newEnabled = service.enabled ? 0 : 1;
    
    const formData = new FormData();
    formData.append('action', 'toggle_service');
    formData.append('id', service.id);
    formData.append('enabled', newEnabled);
    
    fetch('spa_service_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            loadServices(); // Reload services
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showNotification('Error toggling service', 'error');
    });
}

// Delete service
function deleteService(index) {
    if (!confirm('Are you sure you want to delete this service?')) return;
    
    const service = services[index];
    
    const formData = new FormData();
    formData.append('action', 'delete_service');
    formData.append('id', service.id);
    
    fetch('spa_service_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            loadServices(); // Reload services
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showNotification('Error deleting service', 'error');
    });
}

// Update statistics
function updateStats() {
    const total = services.length;
    const active = services.filter(service => service.enabled).length;
    const disabled = total - active;

    document.getElementById('totalServices').textContent = total;
    document.getElementById('activeServices').textContent = active;
    document.getElementById('disabledServices').textContent = disabled;

    // Price statistics
    if (services.length > 0) {
        const prices = services.map(s => parseFloat(s.price));
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

// Bulk operations removed for simplicity - can be added back if needed
