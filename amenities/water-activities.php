<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/photo_functions.php';

// Get water activities photos
$waterPhotos = getPhotosWithFallback('water_activities', 20);

// Get water activities from database
$waterActivities = [];
try {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM water_activities_menu WHERE available = 1 ORDER BY created_at DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $waterActivities[] = $row;
        }
    }
    $conn->close();
} catch (Exception $e) {
    error_log("Error loading water activities: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Water Activities - Paradise Hotel & Resort</title>
    <meta name="description" content="Enjoy exciting water activities and adventures at Paradise Hotel & Resort.">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/gallery.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php#top" class="nav-logo">
                <img src="../uploads/logo/logo.png" alt="Paradise Hotel & Resort" class="nav-logo-img" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                <span>Paradise Hotel & Resort</span>
            </a>
            <button class="nav-toggle" aria-label="Toggle navigation menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-menu">
                <div class="nav-dropdown">
                    <a href="javascript:void(0)" class="nav-link">
                        Rooms
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="../room-gallery.php?type=regular" class="dropdown-item">
                            <i class="fas fa-bed"></i> Regular
                        </a>
                        <a href="../room-gallery.php?type=deluxe" class="dropdown-item">
                            <i class="fas fa-crown"></i> Deluxe
                        </a>
                        <a href="../room-gallery.php?type=vip" class="dropdown-item">
                            <i class="fas fa-gem"></i> VIP
                        </a>
                    </div>
                </div>
                
                <a href="pavilion.php" class="nav-link">
                    <i class="fas fa-building"></i> Pavilion
                </a>
                
                <div class="nav-dropdown">
                    <a href="javascript:void(0)" class="nav-link">
                        Activities
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="pool.php" class="dropdown-item">
                            <i class="fas fa-swimming-pool"></i> Pool
                        </a>
                        <a href="spa.php" class="dropdown-item">
                            <i class="fas fa-spa"></i> Spa
                        </a>
                        <a href="water-activities.php" class="dropdown-item">
                            <i class="fas fa-water"></i> Water Activities
                        </a>
                    </div>
                </div>
                
                <div class="nav-dropdown">
                    <a href="javascript:void(0)" class="nav-link">
                        Dining
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="mini-bar.php" class="dropdown-item">
                            <i class="fas fa-glass-cheers"></i> Mini Bar
                        </a>
                        <a href="main-bar.php" class="dropdown-item">
                            <i class="fas fa-cocktail"></i> Main Bar
                        </a>
                        <a href="restaurant.php" class="dropdown-item">
                            <i class="fas fa-utensils"></i> Restaurant
                        </a>
                    </div>
                </div>
                
                <?php if (isLoggedIn()): ?>
                    <a href="../booking.php" class="nav-link book-now">
                        <i class="fas fa-calendar-check"></i>
                        Book Now
                    </a>
                    <a href="../profile.php" class="nav-user" style="text-decoration: none; cursor: pointer;"><i class="fas fa-user-circle"></i><span>Hello, <?php echo htmlspecialchars(getFirstName() ?? getUsername()); ?></span></a>
                <?php else: ?>
                    <a href="../booking.php" class="nav-link book-now">
                        <i class="fas fa-calendar-check"></i>
                        Book Now
                    </a>
                    <a href="../login.php" class="nav-link">
                        <i class="fas fa-user"></i>
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Gallery Header -->
    <section class="gallery-header">
        <div class="container">
            <div class="gallery-header-content">
                <h1><i class="fas fa-water"></i> Water Activities</h1>
                <p>Dive into adventure with our exciting water sports and activities</p>
                <div class="gallery-breadcrumb">
                    <a href="../index.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Water Activities</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Water Activities Details -->
    <section class="gallery-section">
        <div class="container">
            <!-- Activities Overview -->
            <div class="facility-overview">
                <div class="overview-content">
                    <h2>Aquatic Adventures</h2>
                    <p>Experience the thrill of water sports and activities at our resort. From peaceful kayaking to adrenaline-pumping jet skiing, we offer a wide range of water-based adventures suitable for all skill levels and ages.</p>
                </div>
                <div class="facility-features">
                    <div class="feature">
                        <i class="fas fa-ship"></i>
                        <span>Boat Tours</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-swimmer"></i>
                        <span>Snorkeling</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-water"></i>
                        <span>Jet Skiing</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-anchor"></i>
                        <span>Kayaking</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-fish"></i>
                        <span>Fishing</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-life-ring"></i>
                        <span>Safety First</span>
                    </div>
                </div>
            </div>

            <!-- Photo Gallery -->
            <div class="gallery-grid">
                <?php if (!empty($waterPhotos)): ?>
                    <?php foreach ($waterPhotos as $index => $photo): ?>
                    <div class="gallery-item" data-index="<?php echo $index; ?>">
                        <img src="../<?php echo htmlspecialchars($photo['file_path']); ?>" alt="Water Activity <?php echo $index + 1; ?>" loading="lazy">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-photos">
                        <i class="fas fa-image"></i>
                        <h3>No Photos Available</h3>
                        <p>Water activities photos will be displayed here once uploaded in the admin panel.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Activities Menu -->
            <div class="menu-highlights">
                <h2>Available Activities</h2>
                <?php if (!empty($waterActivities)): ?>
                    <div class="menu-items-list">
                        <?php foreach ($waterActivities as $activity): ?>
                            <div class="menu-list-item">
                                <div class="menu-item-image-small">
                                    <?php if (!empty($activity['image'])): ?>
                                        <img src="../uploads/water_activities/<?php echo htmlspecialchars($activity['image']); ?>" alt="<?php echo htmlspecialchars($activity['name']); ?>">
                                    <?php else: ?>
                                        <img src="../assets/images/default-room.jpg" alt="<?php echo htmlspecialchars($activity['name']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="menu-list-content">
                                    <div class="menu-list-header">
                                        <h4><?php echo htmlspecialchars($activity['name']); ?></h4>
                                        <span class="price">₱<?php echo number_format($activity['price'], 2); ?></span>
                                    </div>
                                    <p><?php echo htmlspecialchars($activity['description']); ?></p>
                                    <span class="prep-time"><i class="fas fa-clock"></i> <?php echo intval($activity['duration']); ?> min</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-services">
                        <i class="fas fa-water"></i>
                        <h3>Activities Coming Soon</h3>
                        <p>Our water activities will be displayed here once they are added in the admin panel.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Safety Information -->
            <div class="pool-info">
                <div class="info-section">
                    <h3><i class="fas fa-life-ring"></i> Safety Guidelines</h3>
                    <ul>
                        <li>Life jackets provided for all activities</li>
                        <li>Certified instructors available</li>
                        <li>Weather conditions monitored</li>
                        <li>First aid station on-site</li>
                    </ul>
                </div>
                <div class="info-section">
                    <h3><i class="fas fa-info-circle"></i> Booking Information</h3>
                    <ul>
                        <li>Advance booking recommended</li>
                        <li>Age restrictions may apply</li>
                        <li>Group discounts available</li>
                        <li>Equipment included in price</li>
                    </ul>
                </div>
            </div>

            <!-- Booking Section -->
            <div class="gallery-booking">
                <div class="booking-card">
                    <h3><i class="fas fa-calendar-check"></i> Book Your Water Adventure</h3>
                    <p>Create unforgettable memories with our exciting water activities</p>
                    <div class="booking-features">
                        <div class="feature">
                            <i class="fas fa-phone"></i>
                            <span>Call: +1 (555) 123-4567</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-users"></i>
                            <span>Group Packages</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-life-ring"></i>
                            <span>Safety Equipment</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-certificate"></i>
                            <span>Certified Guides</span>
                        </div>
                    </div>
                    <a href="../booking.php" class="btn btn-primary btn-large">
                        <i class="fas fa-calendar-check"></i>
                        Book Your Activity
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Lightbox Modal -->
    <div id="lightbox" class="lightbox">
        <div class="lightbox-content">
            <span class="lightbox-close">&times;</span>
            <img id="lightbox-image" src="" alt="">
            <div class="lightbox-nav">
                <button id="lightbox-prev" class="lightbox-btn">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button id="lightbox-next" class="lightbox-btn">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="lightbox-counter">
                <span id="lightbox-current">1</span> / <span id="lightbox-total"><?php echo count($waterPhotos); ?></span>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-hotel"></i> Paradise Hotel & Resort</h3>
                    <p>Experience luxury and comfort in our world-class resort with premium amenities and exceptional service.</p>
                </div>
                <div class="footer-section">
                    <h3><i class="fas fa-map-marker-alt"></i> Contact Info</h3>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> info@paradisehotel.com</p>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Paradise Lane, Resort City</p>
                </div>
                <div class="footer-section">
                    <h3><i class="fas fa-clock"></i> Quick Links</h3>
                    <p><a href="pool.php">Swimming Pool</a></p>
                    <p><a href="spa.php">Spa & Wellness</a></p>
                    <p><a href="restaurant.php">Fine Dining</a></p>
                    <p><a href="../booking.php">Book Now</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Paradise Hotel & Resort. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/gallery.js"></script>
</body>
</html>



