<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/photo_functions.php';

// Get bar photos
$barPhotos = getPhotosWithFallback('bar', 20);

// Get mini bar items from database
$barItems = [];
try {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM bar_menu WHERE bar_type = 'mini' AND available = 1 ORDER BY created_at DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $barItems[] = $row;
        }
    }
    $conn->close();
} catch (Exception $e) {
    error_log("Error loading mini bar items: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini Bar - Paradise Hotel & Resort</title>
    <meta name="description" content="Enjoy premium beverages and snacks from your in-room mini bar at Paradise Hotel & Resort.">
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
                <h1><i class="fas fa-glass-cheers"></i> In-Room Mini Bar</h1>
                <p>Premium beverages and snacks available in your room</p>
                <div class="gallery-breadcrumb">
                    <a href="../index.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Mini Bar</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Mini Bar Details -->
    <section class="gallery-section">
        <div class="container">
            <!-- Mini Bar Overview -->
            <div class="facility-overview">
                <div class="overview-content">
                    <h2>In-Room Convenience</h2>
                    <p>Enjoy the convenience of our fully-stocked in-room mini bar featuring premium beverages, snacks, and refreshments. Perfect for relaxing in the comfort of your room after a long day of activities.</p>
                </div>
                <div class="facility-features">
                    <div class="feature">
                        <i class="fas fa-wine-bottle"></i>
                        <span>Premium Wines</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-beer"></i>
                        <span>Craft Beers</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-glass-whiskey"></i>
                        <span>Spirits</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-coffee"></i>
                        <span>Soft Drinks</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-cookie"></i>
                        <span>Snacks</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-snowflake"></i>
                        <span>Always Cold</span>
                    </div>
                </div>
            </div>

            <!-- Photo Gallery -->
            <div class="gallery-grid">
                <?php if (!empty($barPhotos)): ?>
                    <?php foreach ($barPhotos as $index => $photo): ?>
                    <div class="gallery-item" data-index="<?php echo $index; ?>">
                        <img src="../<?php echo htmlspecialchars($photo['file_path']); ?>" alt="Mini Bar <?php echo $index + 1; ?>" loading="lazy">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-photos">
                        <i class="fas fa-image"></i>
                        <h3>No Photos Available</h3>
                        <p>Mini bar photos will be displayed here once uploaded in the admin panel.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mini Bar Menu -->
            <div class="menu-highlights">
                <h2>Mini Bar Selection</h2>
                <?php if (!empty($barItems)): ?>
                    <div class="menu-items-list">
                        <?php foreach ($barItems as $item): ?>
                            <div class="menu-list-item">
                                <div class="menu-item-image-small">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="../uploads/bar/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        <img src="../assets/images/default-room.jpg" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="menu-list-content">
                                    <div class="menu-list-header">
                                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <span class="price">₱<?php echo number_format($item['price'], 2); ?></span>
                                    </div>
                                    <p><?php echo htmlspecialchars($item['description']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-services">
                        <i class="fas fa-glass-cheers"></i>
                        <h3>Menu Coming Soon</h3>
                        <p>Our mini bar menu will be displayed here once items are added in the admin panel.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mini Bar Information -->
            <div class="pool-info">
                <div class="info-section">
                    <h3><i class="fas fa-info-circle"></i> Mini Bar Policy</h3>
                    <ul>
                        <li>Available 24/7 in your room</li>
                        <li>Charges added to room bill</li>
                        <li>Restocked daily by housekeeping</li>
                        <li>Special requests available</li>
                    </ul>
                </div>
                <div class="info-section">
                    <h3><i class="fas fa-concierge-bell"></i> Room Service</h3>
                    <ul>
                        <li>24-hour room service available</li>
                        <li>Additional items can be ordered</li>
                        <li>Ice delivery upon request</li>
                        <li>Glassware provided</li>
                    </ul>
                </div>
            </div>

            <!-- Booking Section -->
            <div class="gallery-booking">
                <div class="booking-card">
                    <h3><i class="fas fa-calendar-check"></i> Book Your Stay</h3>
                    <p>Enjoy the convenience of our in-room mini bar during your stay</p>
                    <div class="booking-features">
                        <div class="feature">
                            <i class="fas fa-phone"></i>
                            <span>Call: +1 (555) 123-4567</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-clock"></i>
                            <span>24/7 Availability</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-snowflake"></i>
                            <span>Always Chilled</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-star"></i>
                            <span>Premium Selection</span>
                        </div>
                    </div>
                    <a href="../booking.php" class="btn btn-primary btn-large">
                        <i class="fas fa-calendar-check"></i>
                        Book Your Room
                    </a>
                    <p style="margin-top: 1rem; text-align: center;">
                        <a href="main-bar.php" style="color: #C9A961; text-decoration: none;">
                            <i class="fas fa-cocktail"></i> Visit our Main Bar for more options
                        </a>
                    </p>
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
                <span id="lightbox-current">1</span> / <span id="lightbox-total"><?php echo count($barPhotos); ?></span>
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



