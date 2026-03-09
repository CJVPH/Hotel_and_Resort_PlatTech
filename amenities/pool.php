<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/photo_functions.php';

// Get pool photos
$poolPhotos = getPhotosWithFallback('pool', 20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swimming Pool - Paradise Hotel & Resort</title>
    <meta name="description" content="Dive into luxury with our pristine swimming pools and aquatic facilities at Paradise Hotel & Resort.">
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
                <h1><i class="fas fa-swimming-pool"></i> Swimming Pool</h1>
                <p>Dive into luxury with our pristine swimming pools and aquatic facilities</p>
                <div class="gallery-breadcrumb">
                    <a href="../index.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Swimming Pool</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Pool Details -->
    <section class="gallery-section">
        <div class="container">
            <!-- Pool Overview -->
            <div class="facility-overview">
                <div class="overview-content">
                    <h2>Aquatic Paradise</h2>
                    <p>Our swimming pool complex offers a perfect blend of relaxation and recreation. Whether you're looking to swim laps, lounge poolside, or enjoy water activities with family, our facilities provide the ultimate aquatic experience in a luxurious setting.</p>
                </div>
                <div class="facility-features">
                    <div class="feature">
                        <i class="fas fa-swimming-pool"></i>
                        <span>Olympic-size Pool</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-child"></i>
                        <span>Kids' Pool Area</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-hot-tub"></i>
                        <span>Jacuzzi & Hot Tub</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-umbrella-beach"></i>
                        <span>Poolside Loungers</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-cocktail"></i>
                        <span>Pool Bar Service</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-life-ring"></i>
                        <span>Lifeguard on Duty</span>
                    </div>
                </div>
            </div>

            <!-- Photo Gallery -->
            <div class="gallery-grid">
                <?php if (!empty($poolPhotos)): ?>
                    <?php foreach ($poolPhotos as $index => $photo): ?>
                    <div class="gallery-item" data-index="<?php echo $index; ?>">
                        <img src="<?php echo htmlspecialchars($photo['file_path']); ?>" alt="Pool <?php echo $index + 1; ?>" loading="lazy">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-photos">
                        <i class="fas fa-image"></i>
                        <h3>No Photos Available</h3>
                        <p>Pool photos will be displayed here once uploaded in the admin panel.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pool Activities -->
            <div class="event-types">
                <h2>Pool Activities & Services</h2>
                <div class="event-grid">
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-swimmer"></i>
                        </div>
                        <h3>Swimming Lessons</h3>
                        <p>Professional swimming instruction for all ages and skill levels with certified instructors.</p>
                    </div>
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <h3>Water Aerobics</h3>
                        <p>Low-impact fitness classes in the pool, perfect for all fitness levels and joint-friendly exercise.</p>
                    </div>
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-volleyball-ball"></i>
                        </div>
                        <h3>Pool Games</h3>
                        <p>Water volleyball, pool basketball, and other fun activities for guests of all ages.</p>
                    </div>
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-sun"></i>
                        </div>
                        <h3>Poolside Relaxation</h3>
                        <p>Comfortable loungers, umbrellas, and towel service for the perfect poolside experience.</p>
                    </div>
                </div>
            </div>

            <!-- Pool Hours & Rules -->
            <div class="pool-info">
                <div class="info-section">
                    <h3><i class="fas fa-clock"></i> Pool Hours</h3>
                    <ul>
                        <li><strong>Main Pool:</strong> 6:00 AM - 10:00 PM</li>
                        <li><strong>Kids' Pool:</strong> 8:00 AM - 8:00 PM</li>
                        <li><strong>Jacuzzi:</strong> 6:00 AM - 11:00 PM</li>
                        <li><strong>Pool Bar:</strong> 10:00 AM - 9:00 PM</li>
                    </ul>
                </div>
                <div class="info-section">
                    <h3><i class="fas fa-info-circle"></i> Pool Guidelines</h3>
                    <ul>
                        <li>Children under 12 must be supervised</li>
                        <li>No glass containers in pool area</li>
                        <li>Shower before entering the pool</li>
                        <li>Pool capacity limits apply</li>
                    </ul>
                </div>
            </div>

            <!-- Booking Section -->
            <div class="gallery-booking">
                <div class="booking-card">
                    <h3><i class="fas fa-calendar-check"></i> Book Your Stay</h3>
                    <p>Enjoy unlimited access to our swimming pool facilities</p>
                    <div class="booking-features">
                        <div class="feature">
                            <i class="fas fa-towel"></i>
                            <span>Complimentary Towels</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-umbrella-beach"></i>
                            <span>Poolside Service</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-cocktail"></i>
                            <span>Pool Bar Access</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-life-ring"></i>
                            <span>Safety Equipment</span>
                        </div>
                    </div>
                    <a href="../booking.php" class="btn btn-primary btn-large">
                        <i class="fas fa-calendar-check"></i>
                        Book Your Stay Now
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
                <span id="lightbox-current">1</span> / <span id="lightbox-total"><?php echo count($poolPhotos); ?></span>
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


