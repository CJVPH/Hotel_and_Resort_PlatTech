// Activities storage
let activities = [];
let selectedActivityIndex = null;

console.log('🔵 WATER ACTIVITIES SCRIPT LOADED');

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔵 DOM Content Loaded - Initializing water activities dashboard');
    loadActivities();
});

// Load activities from database
function loadActivities() {
    const formData = new FormData();
    formData.append('action', 'get_activities');
    
    fetch('water_activities_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            activities = data.activities;
            console.log('✅ Loaded', activities.length, 'activities from database');
            updateDisplay();
        } else {
            console.error('❌ Error loading activities:', data.message);
            showNotification('Error loading activities', 'error');
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showNotification('Error loading activities', 'error');
    });
}

// Add activity
function addActivity() {
    console.log('🔵 ADD ACTIVITY CLICKED');
    
    const name = document.getElementById('activityName').value.trim();
    const description = document.getElementById('activityDescription').value.trim();
    const price = parseFloat(document.getElementById('activityPrice').value);
    const duration = parseInt(document.getElementById('activityDuration').value);
    const imageFile = document.getElementById('activityImage').files[0];

    console.log('📝 Form values:', { name, description, price, duration, hasImage: !!imageFile });

    // Validation
    if (!name) {
        console.log('❌ Validation failed: name');
        showNotification('Please enter activity name', 'error');
        return;
    }
    if (!description) {
        console.log('❌ Validation failed: description');
        showNotification('Please enter activity description', 'error');
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
    formData.append('action', 'add_activity');
    formData.append('name', name);
    formData.append('description', description);
    formData.append('price', price);
    formData.append('duration', duration);
    
    if (imageFile) {
        console.log('📸 Adding image:', imageFile.name, imageFile.size, 'bytes');
        formData.append('image', imageFile);
    }

    // Send to server
    fetch('water_activities_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Activity added successfully');
            showNotification(data.message, 'success');
            clearAddForm();
            loadActivities(); // Reload activities
        } else {
            console.error('❌ Error:', data.message);
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showNotification('Error adding activity', 'error');
    });
}

// Clear add form
function clearAddForm() {
    document.getElementById('activityName').value = '';
    document.getElementById('activityDescription').value = '';
    document.getElementById('activityPrice').value = '';
    document.getElementById('activityDuration').value = '';
    document.getElementById('activityImage').value = '';
}

// Update display
function updateDisplay() {
    const gallery = document.getElementById('activitiesGallery');
    gallery.innerHTML = '';

    if (activities.length === 0) {
        gallery.innerHTML = '<p style="color: #999; text-align: center; padding: 40px; grid-column: 1/-1;">No activities added yet. Add your first activity!</p>';
        updateStats();
        return;
    }

    activities.forEach((activity, index) => {
        const activityElement = document.createElement('div');
        activityElement.className = `service-item ${!activity.available ? 'disabled' : ''}`;
        
        let imageHtml = '';
        if (activity.image) {
            imageHtml = `<div class="service-item-image">
                <img src="../uploads/water_activities/${activity.image}" alt="${activity.name}" style="width: 100%; height: 200px; object-fit: cover; margin-bottom: 15px; border-radius: 10px;">
            </div>`;
        }
        
        activityElement.innerHTML = `
            <div class="service-item-content">
                ${imageHtml}
                <div class="service-item-duration">${activity.duration} MINUTES</div>
                <div class="service-item-header">
                    <div class="service-item-title">${activity.name}</div>
                    <div class="service-item-price">₱${parseFloat(activity.price).toFixed(2)}</div>
                </div>
                <div class="service-item-description">${activity.description}</div>
                <div class="service-item-actions">
                    <button class="btn btn-secondary btn-small" onclick="editActivity(${index})">Edit</button>
                    <button class="btn btn-danger btn-small" onclick="deleteActivity(${index})">Delete</button>
                </div>
                <div class="service-item-status">
                    <span class="status-label">${activity.available ? 'Available' : 'Unavailable'}</span>
                    <label class="toggle-switch">
                        <input type="checkbox" ${activity.available ? 'checked' : ''} onchange="toggleActivity(${index})">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        `;
        gallery.appendChild(activityElement);
    });

    updateStats();
}

// Edit activity
function editActivity(index) {
    selectedActivityIndex = index;
    const activity = activities[index];

    document.getElementById('editActivityName').value = activity.name;
    document.getElementById('editActivityDescription').value = activity.description;
    document.getElementById('editActivityPrice').value = activity.price;
    document.getElementById('editActivityDuration').value = activity.duration;

    document.getElementById('editModal').classList.add('show');
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
    selectedActivityIndex = null;
}

// Save edit
function saveEdit() {
    if (selectedActivityIndex === null) return;

    const activity = activities[selectedActivityIndex];
    const name = document.getElementById('editActivityName').value.trim();
    const description = document.getElementById('editActivityDescription').value.trim();
    const price = parseFloat(document.getElementById('editActivityPrice').value);
    const duration = parseInt(document.getElementById('editActivityDuration').value);
    const imageFile = document.getElementById('editActivityImage').files[0];

    // Validation
    if (!name) {
        showNotification('Please enter activity name', 'error');
        return;
    }
    if (!description) {
        showNotification('Please enter activity description', 'error');
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
    formData.append('action', 'update_activity');
    formData.append('id', activity.id);
    formData.append('name', name);
    formData.append('description', description);
    formData.append('price', price);
    formData.append('duration', duration);
    
    if (imageFile) {
        formData.append('image', imageFile);
    }

    // Send to server
    fetch('water_activities_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeEditModal();
            loadActivities(); // Reload activities
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showNotification('Error updating activity', 'error');
    });
}

// Toggle activity
function toggleActivity(index) {
    const activity = activities[index];
    const newAvailable = activity.available ? 0 : 1;
    
    const formData = new FormData();
    formData.append('action', 'toggle_activity');
    formData.append('id', activity.id);
    formData.append('available', newAvailable);
    
    fetch('water_activities_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            loadActivities(); // Reload activities
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showNotification('Error toggling activity', 'error');
    });
}

// Delete activity
function deleteActivity(index) {
    if (!confirm('Are you sure you want to delete this activity?')) return;
    
    const activity = activities[index];
    
    const formData = new FormData();
    formData.append('action', 'delete_activity');
    formData.append('id', activity.id);
    
    fetch('water_activities_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            loadActivities(); // Reload activities
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showNotification('Error deleting activity', 'error');
    });
}

// Update statistics
function updateStats() {
    const total = activities.length;
    const available = activities.filter(activity => activity.available).length;
    const unavailable = total - available;

    document.getElementById('totalActivities').textContent = total;
    document.getElementById('availableActivities').textContent = available;
    document.getElementById('unavailableActivities').textContent = unavailable;

    // Price statistics
    if (activities.length > 0) {
        const prices = activities.map(a => parseFloat(a.price));
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
