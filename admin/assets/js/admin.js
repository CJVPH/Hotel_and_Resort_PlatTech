// Paradise Hotel & Resort - Admin Dashboard JavaScript
// Common functionality for admin pages

// ============================================
// ROOMS PAGE - Image Upload & Management
// ============================================

function initializeRoomsPage() {
    const uploadForms = document.querySelectorAll('.room-upload-form');
    const navTabs = document.querySelectorAll('.room-nav-tab');
    const roomCards = document.querySelectorAll('.room-card');
    const pricingSection = document.getElementById('pricing-section');
    const roomGridSection = document.getElementById('room-grid-section');
    const deleteButtons = document.querySelectorAll('.delete-image-btn');
    
    // Restore active tab from sessionStorage after page reload
    const savedActiveTab = sessionStorage.getItem('activeRoomTab');
    if (savedActiveTab && savedActiveTab !== 'pricing') {
        const tabToActivate = document.querySelector(`[data-room-type="${savedActiveTab}"]`);
        if (tabToActivate) {
            sessionStorage.removeItem('activeRoomTab');
            setTimeout(() => tabToActivate.click(), 100);
        }
    }
    
    // Handle room type navigation
    navTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const roomType = this.dataset.roomType;
            
            // Update active tab
            navTabs.forEach(t => {
                t.classList.remove('active');
                t.style.background = 'rgba(255,255,255,0.1)';
                t.style.color = '#ccc';
            });
            
            this.classList.add('active');
            this.style.background = 'linear-gradient(135deg, #C9A961, #8B7355)';
            this.style.color = 'white';
            
            // Show/hide sections
            if (roomType === 'pricing') {
                pricingSection.style.display = 'block';
                roomGridSection.style.display = 'none';
            } else {
                pricingSection.style.display = 'none';
                roomGridSection.style.display = 'grid';
                
                // Filter room cards
                roomCards.forEach(card => {
                    card.style.display = card.dataset.roomType === roomType ? 'block' : 'none';
                });
            }
        });
    });
    
    // Handle image deletion
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const imageId = this.dataset.imageId;
            
            if (confirm('Are you sure you want to delete this image?')) {
                const formData = new FormData();
                formData.append('image_id', imageId);
                
                fetch('delete_room_image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.closest('.room-image-item').remove();
                        
                        // Update image counter
                        const roomCard = this.closest('.room-card');
                        const imageCount = roomCard.querySelector('.image-count');
                        const currentCount = parseInt(imageCount.textContent.split('/')[0]);
                        imageCount.textContent = `${currentCount - 1} / 10 images uploaded`;
                        
                        // Update file info
                        const fileInfo = roomCard.querySelector('.file-info p');
                        const remainingSlots = 10 - (currentCount - 1);
                        fileInfo.textContent = `• Up to ${remainingSlots} images`;
                    } else {
                        alert('Delete failed: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Delete error:', error);
                    alert('Delete failed. Please try again.');
                });
            }
        });
    });
    
    // Initialize automatic upload for each form
    uploadForms.forEach(form => initializeRoomAutoUpload(form));
}

function initializeRoomAutoUpload(form) {
    const dragZone = form.querySelector('.drag-drop-zone');
    const fileInput = form.querySelector('input[type="file"]');
    const progressBar = form.querySelector('.progress-bar');
    const progressFill = form.querySelector('.progress-fill');
    const statusDiv = form.querySelector('.upload-status');
    
    // Click to browse
    dragZone.addEventListener('click', (e) => {
        if (e.target === dragZone || e.target.closest('.drag-drop-content')) {
            fileInput.click();
        }
    });
    
    // File input change - automatic upload
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleRoomAutoUpload(e.target.files, form, progressBar, progressFill, statusDiv);
        }
    });
    
    // Drag and drop events
    dragZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dragZone.style.borderColor = '#8B7355';
        dragZone.style.background = 'rgba(201, 169, 97, 0.2)';
    });
    
    dragZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dragZone.style.borderColor = '#C9A961';
        dragZone.style.background = 'rgba(201, 169, 97, 0.1)';
    });
    
    dragZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dragZone.style.borderColor = '#C9A961';
        dragZone.style.background = 'rgba(201, 169, 97, 0.1)';
        
        if (e.dataTransfer.files.length > 0) {
            handleRoomAutoUpload(e.dataTransfer.files, form, progressBar, progressFill, statusDiv);
        }
    });
}

function handleRoomAutoUpload(files, form, progressBar, progressFill, statusDiv) {
    // Validate files
    const validFiles = Array.from(files).filter(file => {
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            showRoomStatus(statusDiv, `Invalid file type: ${file.name}. Only JPEG, PNG, and WebP are allowed.`, 'error');
            return false;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            showRoomStatus(statusDiv, `File too large: ${file.name}. Maximum size is 5MB.`, 'error');
            return false;
        }
        
        return true;
    });
    
    if (validFiles.length === 0) return;
    
    // Show progress bar
    progressBar.style.display = 'block';
    statusDiv.style.display = 'none';
    progressFill.style.width = '0%';
    
    const formData = new FormData();
    validFiles.forEach(file => formData.append('room_images[]', file));
    formData.append('room_number', form.dataset.roomNumber);
    formData.append('room_type', form.dataset.roomType);
    formData.append('pax_group', form.dataset.paxGroup);
    
    const xhr = new XMLHttpRequest();
    
    // Upload progress
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            progressFill.style.width = ((e.loaded / e.total) * 100) + '%';
        }
    });
    
    // Upload complete
    xhr.addEventListener('load', () => {
        progressBar.style.display = 'none';
        
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    showRoomStatus(statusDiv, response.message, 'success');
                    
                    // Remember current active tab
                    const activeTab = document.querySelector('.room-nav-tab.active');
                    const activeRoomType = activeTab ? activeTab.dataset.roomType : 'pricing';
                    sessionStorage.setItem('activeRoomTab', activeRoomType);
                    
                    // Reload page
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showRoomStatus(statusDiv, 'Upload failed: ' + response.error, 'error');
                }
            } catch (e) {
                showRoomStatus(statusDiv, 'Upload failed. Please try again.', 'error');
            }
        } else {
            showRoomStatus(statusDiv, `Upload failed. HTTP Status: ${xhr.status}`, 'error');
        }
    });
    
    // Upload error
    xhr.addEventListener('error', () => {
        progressBar.style.display = 'none';
        showRoomStatus(statusDiv, 'Upload failed. Please check your connection.', 'error');
    });
    
    xhr.open('POST', 'simple_upload.php');
    xhr.send(formData);
}

function showRoomStatus(statusDiv, message, type) {
    statusDiv.className = `upload-status ${type}`;
    statusDiv.textContent = message;
    statusDiv.style.display = 'block';
    
    if (type === 'success') {
        statusDiv.style.background = '#d4edda';
        statusDiv.style.color = '#155724';
        statusDiv.style.border = '1px solid #c3e6cb';
    } else {
        statusDiv.style.background = '#f8d7da';
        statusDiv.style.color = '#721c24';
        statusDiv.style.border = '1px solid #f5c6cb';
    }
    
    // Hide status after timeout
    setTimeout(() => {
        if (type === 'error') {
            statusDiv.style.display = 'none';
        }
    }, type === 'error' ? 5000 : 3000);
}

// ============================================
// SETTINGS PAGE - Photo Upload & Management
// ============================================

function initializeSettingsPage() {
    const uploadAreas = document.querySelectorAll('.upload-area');
    
    uploadAreas.forEach(area => {
        const fileInput = area.querySelector('.file-input');
        const uploadButton = area.querySelector('.upload-button');
        const section = area.dataset.section;
        
        // Click to browse
        uploadButton.addEventListener('click', () => fileInput.click());
        
        area.addEventListener('click', (e) => {
            if (e.target === area || e.target.classList.contains('upload-text') || e.target.classList.contains('upload-icon')) {
                fileInput.click();
            }
        });
        
        // File selection
        fileInput.addEventListener('change', (e) => {
            handlePhotoUpload(e.target.files, section, area);
        });
        
        // Drag and drop
        area.addEventListener('dragover', (e) => {
            e.preventDefault();
            area.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', () => {
            area.classList.remove('dragover');
        });
        
        area.addEventListener('drop', (e) => {
            e.preventDefault();
            area.classList.remove('dragover');
            handlePhotoUpload(e.dataTransfer.files, section, area);
        });
    });
}

function handlePhotoUpload(files, section, uploadArea) {
    if (files.length === 0) return;
    
    const progressBar = uploadArea.querySelector('.progress-bar');
    const progressFill = uploadArea.querySelector('.progress-fill');
    const statusDiv = uploadArea.querySelector('.upload-status');
    
    // Show progress bar
    progressBar.style.display = 'block';
    statusDiv.style.display = 'none';
    
    const formData = new FormData();
    formData.append('section', section);
    
    for (let i = 0; i < files.length; i++) {
        formData.append('photos[]', files[i]);
    }
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            progressFill.style.width = ((e.loaded / e.total) * 100) + '%';
        }
    });
    
    xhr.addEventListener('load', () => {
        progressBar.style.display = 'none';
        
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    statusDiv.className = 'upload-status success';
                    statusDiv.textContent = response.message;
                    statusDiv.style.display = 'block';
                    
                    // Reload page
                    setTimeout(() => location.reload(), 1500);
                } else {
                    statusDiv.className = 'upload-status error';
                    statusDiv.textContent = response.message;
                    statusDiv.style.display = 'block';
                }
            } catch (e) {
                statusDiv.className = 'upload-status error';
                statusDiv.textContent = 'Upload failed. Please try again.';
                statusDiv.style.display = 'block';
            }
        } else {
            statusDiv.className = 'upload-status error';
            statusDiv.textContent = 'Upload failed. Please try again.';
            statusDiv.style.display = 'block';
        }
    });
    
    xhr.addEventListener('error', () => {
        progressBar.style.display = 'none';
        statusDiv.className = 'upload-status error';
        statusDiv.textContent = 'Upload failed. Please check your connection.';
        statusDiv.style.display = 'block';
    });
    
    xhr.open('POST', 'upload_photos.php');
    xhr.send(formData);
}

function deletePhoto(photoId, section) {
    if (!confirm('Are you sure you want to delete this photo?')) {
        return;
    }
    
    fetch('delete_photo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            photo_id: photoId,
            section: section
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const photoItem = document.querySelector(`[data-photo-id="${photoId}"]`);
            if (photoItem) {
                photoItem.remove();
            }
        } else {
            alert('Failed to delete photo: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to delete photo. Please try again.');
    });
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Detect which page we're on and initialize accordingly
    if (document.querySelector('.room-upload-form')) {
        initializeRoomsPage();
    }
    
    if (document.querySelector('.upload-area')) {
        initializeSettingsPage();
    }
});