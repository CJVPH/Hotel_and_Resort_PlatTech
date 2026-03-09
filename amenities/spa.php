<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/photo_functions.php';

// Get spa photos
$spaPhotos = getPhotosWithFallback('spa', 20);

// Get spa services from database
$spaServices = [];
try {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM spa_services WHERE enabled = 1 ORDER BY created_at DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $spaServices[] = $row;
        }
    }
    $conn->close();
} catch (Exception $e) {
    error_log("Error loading spa services: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spa & Wellness - Paradise Hotel & Resort</title>
    <meta name="description" content="Rejuvenate your body and soul with our premium spa treatments at Paradise Hotel & Resort.">
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
                <i class="fas fa-hotel"></i>
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
                        <a href="regular-gallery.php" class="dropdown-item">
                            <i class="fas fa-bed"></i> Regular
                        </a>
                        <a href="deluxe-gallery.php" class="dropdown-item">
                            <i class="fas fa-crown"></i> Deluxe
                        </a>
                        <a href="vip-gallery.php" class="dropdown-item">
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
                    <a href="../logout.php" class="nav-link">Logout</a>
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
                <h1><i class="fas fa-spa"></i> Spa & Wellness</h1>
                <p>Rejuvenate your body and soul with our premium spa treatments</p>
                <div class="gallery-breadcrumb">
                    <a href="../index.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Spa & Wellness</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Spa Details -->
    <section class="gallery-section">
        <div class="container">
            <!-- Spa Overview -->
            <div class="facility-overview">
                <div class="overview-content">
                    <h2>Wellness Sanctuary</h2>
                    <p>Our world-class spa offers a tranquil escape where ancient healing traditions meet modern wellness techniques. Experience ultimate relaxation with our comprehensive range of treatments designed to restore balance, rejuvenate your spirit, and enhance your well-being.</p>
                </div>
                <div class="facility-features">
                    <div class="feature">
                        <i class="fas fa-hands"></i>
                        <span>Expert Therapists</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-leaf"></i>
                        <span>Organic Products</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-hot-tub"></i>
                        <span>Thermal Pools</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-dumbbell"></i>
                        <span>Fitness Center</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-meditation"></i>
                        <span>Meditation Garden</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-clock"></i>
                        <span>Extended Hours</span>
                    </div>
                </div>
            </div>

            <!-- Photo Gallery -->
            <div class="gallery-grid">
                <?php if (!empty($spaPhotos)): ?>
                    <?php foreach ($spaPhotos as $index => $photo): ?>
                    <div class="gallery-item" data-index="<?php echo $index; ?>">
                        <img src="<?php echo htmlspecialchars($photo['file_path']); ?>" alt="Spa <?php echo $index + 1; ?>" loading="lazy">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-photos">
                        <i class="fas fa-image"></i>
                        <h3>No Photos Available</h3>
                        <p>Spa photos will be displayed here once uploaded in the admin panel.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Spa Treatments -->
            <div class="event-types">
                <h2>Signature Treatments</h2>
                <div class="event-grid">
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-hands"></i>
                        </div>
                        <h3>Therapeutic Massage</h3>
                        <p>Deep tissue, Swedish, hot stone, and aromatherapy massages to relieve tension and promote relaxation.</p>
                    </div>
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-gem"></i>
                        </div>
                        <h3>Facial Treatments</h3>
                        <p>Rejuvenating facials using premium skincare products to cleanse, hydrate, and revitalize your skin.</p>
                    </div>
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h3>Body Wraps</h3>
                        <p>Detoxifying and nourishing body treatments using natural ingredients and healing clays.</p>
                    </div>
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3>Couples Packages</h3>
                        <p>Romantic spa experiences designed for couples, including side-by-side treatments and private suites.</p>
                    </div>
                </div>
            </div>

            <!-- Spa Hours & Info -->
            <div class="pool-info">
                <div class="info-section">
                    <h3><i class="fas fa-clock"></i> Spa Hours</h3>
                    <ul>
                        <li><strong>Treatment Rooms:</strong> 9:00 AM - 9:00 PM</li>
                        <li><strong>Thermal Pools:</strong> 7:00 AM - 10:00 PM</li>
                        <li><strong>Fitness Center:</strong> 24/7 Access</li>
                        <li><strong>Meditation Garden:</strong> 6:00 AM - 8:00 PM</li>
                    </ul>
                </div>
                <div class="info-section">
                    <h3><i class="fas fa-info-circle"></i> Spa Etiquette</h3>
                    <ul>
                        <li>Advance booking recommended</li>
                        <li>Arrive 15 minutes before treatment</li>
                        <li>Quiet zones for relaxation</li>
                        <li>Robes and slippers provided</li>
                    </ul>
                </div>
            </div>

            <!-- Treatment Menu -->
            <div class="menu-highlights">
                <h2>Treatment Menu</h2>
                <?php if (!empty($spaServices)): ?>
                    <div class="menu-items-list">
                        <?php foreach ($spaServices as $service): ?>
                            <div class="menu-list-item">
                                <div class="menu-item-image-small">
                                    <?php if (!empty($service['image'])): ?>
                                        <img src="uploads/spa/<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>">
                                    <?php else: ?>
                                        <img src="../assets/images/default-room.jpg" alt="<?php echo htmlspecialchars($service['name']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="menu-list-content">
                                    <div class="menu-list-header">
                                        <h4><?php echo htmlspecialchars($service['name']); ?></h4>
                                        <span class="price">₱<?php echo number_format($service['price'], 2); ?></span>
                                    </div>
                                    <p><?php echo htmlspecialchars($service['description']); ?></p>
                                    <span class="prep-time"><i class="fas fa-clock"></i> <?php echo intval($service['duration']); ?> min</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-services">
                        <i class="fas fa-spa"></i>
                        <h3>Services Coming Soon</h3>
                        <p>Our spa services will be displayed here once they are added in the admin panel.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Wellness Programs -->
            <div class="wellness-programs">
                <h2>Wellness Programs</h2>
                <div class="program-grid">
                    <div class="program-item">
                        <i class="fas fa-meditation"></i>
                        <h4>Daily Yoga Classes</h4>
                        <p>Morning and evening yoga sessions in our peaceful meditation garden</p>
                    </div>
                    <div class="program-item">
                        <i class="fas fa-dumbbell"></i>
                        <h4>Personal Training</h4>
                        <p>One-on-one fitness sessions with certified personal trainers</p>
                    </div>
                    <div class="program-item">
                        <i class="fas fa-apple-alt"></i>
                        <h4>Nutrition Consultation</h4>
                        <p>Personalized nutrition plans and healthy lifestyle guidance</p>
                    </div>
                    <div class="program-item">
                        <i class="fas fa-water"></i>
                        <h4>Aqua Therapy</h4>
                        <p>Therapeutic water exercises in our heated mineral pools</p>
                    </div>
                </div>
            </div>

            <!-- Booking Section -->
            <div class="gallery-booking">
                <div class="booking-card">
                    <h3><i class="fas fa-calendar-check"></i> Book Your Spa Experience</h3>
                    <p>Treat yourself to ultimate relaxation and rejuvenation</p>
                    <div class="booking-features">
                        <div class="feature">
                            <i class="fas fa-phone"></i>
                            <span>Call: +1 (555) 123-4567</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-gift"></i>
                            <span>Spa Packages Available</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-heart"></i>
                            <span>Couples Treatments</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-certificate"></i>
                            <span>Gift Certificates</span>
                        </div>
                    </div>
                    <a href="../booking.php" class="btn btn-primary btn-large">
                        <i class="fas fa-calendar-check"></i>
                        Book Your Spa Treatment
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
                <span id="lightbox-current">1</span> / <span id="lightbox-total"><?php echo count($spaPhotos); ?></span>
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
        <script src="../assets/js/main.js"></script>
    <script src="../assets/js/gallery.js"></script>
</body>
</html>


