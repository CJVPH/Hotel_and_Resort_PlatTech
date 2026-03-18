<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/photo_functions.php';

// Require admin login
requireAdminLogin();

// Get photos for different sections
$carouselPhotos = getPhotosForSection('carousel');
$poolPhotos = getPhotosForSection('pool');
$spaPhotos = getPhotosForSection('spa');
$restaurantPhotos = getPhotosForSection('restaurant');
$pavilionPhotos = getPhotosForSection('pavilion');

// Set page variables for template
$pageTitle = 'Settings';
$currentPage = 'settings';
?>

<?php include 'template_header.php'; ?>
<!-- Page specific styles -->
<style>
    /* Tab Navigation Styles */
    .settings-tabs {
        display: flex;
        gap: 0;
        margin-bottom: 2rem;
        border-bottom: 2px solid #e0e0e0;
        padding-bottom: 0;
    }
    
    .settings-tab-btn {
        background: transparent;
        border: none;
        padding: 1rem 2rem;
        font-size: 1.1rem;
        font-weight: 600;
        color: #666;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        position: relative;
        bottom: -2px;
    }
    
    .settings-tab-btn:hover {
        color: #C9A961;
        background: rgba(201, 169, 97, 0.05);
    }
    
    .settings-tab-btn.active {
        color: #C9A961;
        border-bottom-color: #C9A961;
        background: rgba(201, 169, 97, 0.1);
    }
    
    .settings-tab-btn i {
        font-size: 1.2rem;
    }
    
    .settings-tab-content {
        display: none;
    }
    
    .settings-tab-content.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Form Styles */
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #C9A961;
        box-shadow: 0 0 0 3px rgba(201, 169, 97, 0.1);
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #2C3E50;
    }
    
    .form-group small {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.85rem;
    }
    
    /* Photo Upload Styles */
    .photo-upload-section { margin-bottom: 3rem; }
    .upload-area { border: 2px dashed #C9A961; border-radius: 15px; padding: 2rem; text-align: center; background: #f8f9fa; transition: all 0.3s ease; cursor: pointer; margin-bottom: 2rem; }
    .upload-area:hover { background: #e9ecef; border-color: #8B7355; }
    .upload-area.dragover { background: rgba(201, 169, 97, 0.1); border-color: #C9A961; }
    .upload-icon { font-size: 3rem; color: #C9A961; margin-bottom: 1rem; }
    .upload-text { color: #666; font-size: 1.1rem; margin-bottom: 1rem; }
    .upload-button { background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%); color: white; border: none; padding: 1rem 2rem; border-radius: 25px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; }
    .upload-button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(201, 169, 97, 0.3); }
    .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-top: 2rem; }
    .photo-item { position: relative; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; aspect-ratio: 4/3; }
    .photo-item:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); }
    .photo-item img { width: 100%; height: 100%; object-fit: cover; }
    .photo-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); display: flex; align-items: center; justify-content: center; opacity: 0; transition: all 0.3s ease; }
    .photo-item:hover .photo-overlay { opacity: 1; }
    .delete-btn { background: #dc3545; color: white; border: none; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem; }
    .delete-btn:hover { background: #c82333; transform: scale(1.05); }
    .progress-bar { width: 100%; height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden; margin-top: 1rem; display: none; }
    .progress-fill { height: 100%; background: linear-gradient(135deg, #C9A961 0%, #8B7355 100%); width: 0%; transition: width 0.3s ease; }
    .upload-status { margin-top: 1rem; padding: 1rem; border-radius: 10px; display: none; }
    .upload-status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .upload-status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>

<!-- Main Content -->
<div class="content-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-cog"></i> Website Settings</h1>
        <p>Manage your website content and photos</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="settings-tabs" style="display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid #e0e0e0; padding-bottom: 0;">
        <button class="settings-tab-btn active" data-tab="homepage-content">
            <i class="fas fa-home"></i> Homepage Content
        </button>
        <button class="settings-tab-btn" data-tab="photo-management">
            <i class="fas fa-images"></i> Photo Management
        </button>
    </div>

    <!-- Homepage Content Section -->
    <div id="homepage-content" class="settings-tab-content active">
        <div class="admin-section">
            <div class="section-header">
                <h2><i class="fas fa-edit"></i> Edit Homepage Content</h2>
                <span style="color: #666; font-size: 0.9rem;">Customize main homepage text and information</span>
            </div>
            
            <form id="homepageSettingsForm" style="max-width: 800px;">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label><i class="fas fa-heading"></i> Site Title</label>
                        <input type="text" name="site_title" id="site_title" class="form-control" placeholder="Paradise Hotel & Resort">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-quote-right"></i> Site Tagline</label>
                        <input type="text" name="site_tagline" id="site_tagline" class="form-control" placeholder="Experience luxury, comfort, and unforgettable memories">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-star"></i> Hero Title</label>
                        <input type="text" name="hero_title" id="hero_title" class="form-control" placeholder="Welcome to Paradise Hotel & Resort">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Hero Subtitle</label>
                        <textarea name="hero_subtitle" id="hero_subtitle" class="form-control" rows="2" placeholder="Experience luxury, comfort, and unforgettable memories"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-info-circle"></i> About Title</label>
                        <input type="text" name="about_title" id="about_title" class="form-control" placeholder="About Paradise Hotel & Resort">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-file-alt"></i> About Description</label>
                        <textarea name="about_description" id="about_description" class="form-control" rows="4" placeholder="Welcome to Paradise Hotel & Resort..."></textarea>
                    </div>
                    
                    <div style="border-top: 2px solid #e0e0e0; padding-top: 1.5rem; margin-top: 1rem;">
                        <h3 style="margin-bottom: 1rem; color: #2C3E50;"><i class="fas fa-phone"></i> Contact Information</h3>
                        
                        <div class="form-group">
                            <label><i class="fas fa-phone-alt"></i> Phone Number</label>
                            <input type="text" name="contact_phone" id="contact_phone" class="form-control" placeholder="+1 (555) 123-4567">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email Address</label>
                            <input type="email" name="contact_email" id="contact_email" class="form-control" placeholder="info@paradisehotel.com">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Address</label>
                            <input type="text" name="contact_address" id="contact_address" class="form-control" placeholder="123 Paradise Lane, Resort City">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-save"></i> Save Homepage Settings
                    </button>
                </div>
            </form>
            
            <div id="settingsMessage" style="margin-top: 1rem; padding: 1rem; border-radius: 8px; display: none;"></div>
        </div>
    </div>

    <!-- Photo Management Section -->
    <div id="photo-management" class="settings-tab-content">
        <div class="admin-section">
            <div class="section-header">
                <h2><i class="fas fa-images"></i> Upload & Manage Photos</h2>
                <span style="color: #666; font-size: 0.9rem;">Upload photos for different sections of your website</span>
            </div>
        </div>

            <!-- Carousel Photos -->
            <div class="admin-section photo-upload-section">
                <div class="section-header">
                    <h2><i class="fas fa-images"></i> Carousel Photos</h2>
                    <span style="color: #666; font-size: 0.9rem;">Main homepage slideshow images</span>
                </div>
                
                <div class="upload-area" data-section="carousel">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">
                        Drag & drop carousel images here or click to browse
                    </div>
                    <button type="button" class="upload-button">
                        <i class="fas fa-plus"></i> Add Carousel Photos
                    </button>
                    <input type="file" class="file-input" accept="image/*" multiple style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="upload-status"></div>
                </div>

                <div class="photo-grid" id="carousel-photos">
                    <?php foreach ($carouselPhotos as $photo): ?>
                    <div class="photo-item" data-photo-id="<?php echo $photo['id']; ?>">
                        <img src="../<?php echo $photo['file_path']; ?>" alt="<?php echo htmlspecialchars($photo['original_name']); ?>">
                        <div class="photo-overlay">
                            <button class="delete-btn" onclick="deletePhoto(<?php echo $photo['id']; ?>, 'carousel')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pavilion & Event Photos -->
            <div class="admin-section photo-upload-section">
                <div class="section-header">
                    <h2><i class="fas fa-building"></i> Pavilion & Event Photos</h2>
                    <span style="color: #666; font-size: 0.9rem;">Event spaces, pavilions, and conference facilities</span>
                </div>
                
                <div class="upload-area" data-section="pavilion">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">
                        Drag & drop pavilion images here or click to browse
                    </div>
                    <button type="button" class="upload-button">
                        <i class="fas fa-plus"></i> Add Pavilion Photos
                    </button>
                    <input type="file" class="file-input" accept="image/*" multiple style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="upload-status"></div>
                </div>

                <div class="photo-grid" id="pavilion-photos">
                    <?php foreach ($pavilionPhotos as $photo): ?>
                    <div class="photo-item" data-photo-id="<?php echo $photo['id']; ?>">
                        <img src="../<?php echo $photo['file_path']; ?>" alt="<?php echo htmlspecialchars($photo['original_name']); ?>">
                        <div class="photo-overlay">
                            <button class="delete-btn" onclick="deletePhoto(<?php echo $photo['id']; ?>, 'pavilion')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pool Photos -->
            <div class="admin-section photo-upload-section">
                <div class="section-header">
                    <h2><i class="fas fa-swimming-pool"></i> Pool Photos</h2>
                    <span style="color: #666; font-size: 0.9rem;">Swimming pool and aquatic facilities</span>
                </div>
                
                <div class="upload-area" data-section="pool">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">
                        Drag & drop pool images here or click to browse
                    </div>
                    <button type="button" class="upload-button">
                        <i class="fas fa-plus"></i> Add Pool Photos
                    </button>
                    <input type="file" class="file-input" accept="image/*" multiple style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="upload-status"></div>
                </div>

                <div class="photo-grid" id="pool-photos">
                    <?php foreach ($poolPhotos as $photo): ?>
                    <div class="photo-item" data-photo-id="<?php echo $photo['id']; ?>">
                        <img src="../<?php echo $photo['file_path']; ?>" alt="<?php echo htmlspecialchars($photo['original_name']); ?>">
                        <div class="photo-overlay">
                            <button class="delete-btn" onclick="deletePhoto(<?php echo $photo['id']; ?>, 'pool')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Spa Photos -->
            <div class="admin-section photo-upload-section">
                <div class="section-header">
                    <h2><i class="fas fa-spa"></i> Spa Photos</h2>
                    <span style="color: #666; font-size: 0.9rem;">Spa treatments and wellness facilities</span>
                </div>
                
                <div class="upload-area" data-section="spa">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">
                        Drag & drop spa images here or click to browse
                    </div>
                    <button type="button" class="upload-button">
                        <i class="fas fa-plus"></i> Add Spa Photos
                    </button>
                    <input type="file" class="file-input" accept="image/*" multiple style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="upload-status"></div>
                </div>

                <div class="photo-grid" id="spa-photos">
                    <?php foreach ($spaPhotos as $photo): ?>
                    <div class="photo-item" data-photo-id="<?php echo $photo['id']; ?>">
                        <img src="../<?php echo $photo['file_path']; ?>" alt="<?php echo htmlspecialchars($photo['original_name']); ?>">
                        <div class="photo-overlay">
                            <button class="delete-btn" onclick="deletePhoto(<?php echo $photo['id']; ?>, 'spa')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Restaurant Photos -->
            <div class="admin-section photo-upload-section">
                <div class="section-header">
                    <h2><i class="fas fa-utensils"></i> Restaurant Photos</h2>
                    <span style="color: #666; font-size: 0.9rem;">Dining areas and culinary experiences</span>
                </div>
                
                <div class="upload-area" data-section="restaurant">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">
                        Drag & drop restaurant images here or click to browse
                    </div>
                    <button type="button" class="upload-button">
                        <i class="fas fa-plus"></i> Add Restaurant Photos
                    </button>
                    <input type="file" class="file-input" accept="image/*" multiple style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="upload-status"></div>
                </div>

                <div class="photo-grid" id="restaurant-photos">
                    <?php foreach ($restaurantPhotos as $photo): ?>
                    <div class="photo-item" data-photo-id="<?php echo $photo['id']; ?>">
                        <img src="../<?php echo $photo['file_path']; ?>" alt="<?php echo htmlspecialchars($photo['original_name']); ?>">
                        <div class="photo-overlay">
                            <button class="delete-btn" onclick="deletePhoto(<?php echo $photo['id']; ?>, 'restaurant')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- End Photo Management Section -->
</div>

    <script src="assets/js/admin.js?v=<?php echo filemtime(__DIR__ . '/assets/js/admin.js'); ?>"></script>
    <script>
        // Tab switching functionality
        document.querySelectorAll('.settings-tab-btn').forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                document.querySelectorAll('.settings-tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.settings-tab-content').forEach(content => content.classList.remove('active'));
                this.classList.add('active');
                document.getElementById(tabName).classList.add('active');
                sessionStorage.setItem('settingsTab', tabName);
            });
        });

        // Restore last active tab + load settings on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            const saved = sessionStorage.getItem('settingsTab');
            if (saved) {
                document.querySelectorAll('.settings-tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.settings-tab-content').forEach(c => c.classList.remove('active'));
                const btn = document.querySelector(`.settings-tab-btn[data-tab="${saved}"]`);
                const content = document.getElementById(saved);
                if (btn) btn.classList.add('active');
                if (content) content.classList.add('active');
            }
            loadHomepageSettings();
        });
        
        // Load homepage settings from database
        function loadHomepageSettings() {
            fetch('get_homepage_settings.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate form fields
                        Object.keys(data.settings).forEach(key => {
                            const field = document.getElementById(key);
                            if (field) {
                                field.value = data.settings[key];
                            }
                        });
                    }
                })
                .catch(error => console.error('Error loading settings:', error));
        }
        
        // Save homepage settings
        document.getElementById('homepageSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'save_homepage_settings');
            
            fetch('save_homepage_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('settingsMessage');
                messageDiv.style.display = 'block';
                
                if (data.success) {
                    messageDiv.className = 'upload-status success';
                    messageDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                } else {
                    messageDiv.className = 'upload-status error';
                    messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                }
                
                // Hide message after 3 seconds
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                const messageDiv = document.getElementById('settingsMessage');
                messageDiv.style.display = 'block';
                messageDiv.className = 'upload-status error';
                messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error saving settings';
            });
        });
    </script>

<?php include 'template_footer.php'; ?>