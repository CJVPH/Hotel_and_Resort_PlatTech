<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/photo_functions.php';

// Get pavilion photos
$pavilionPhotos = getPhotosWithFallback('pavilion', 20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pavilion & Event Spaces - Paradise Hotel & Resort</title>
    <meta name="description" content="Host your special events in our elegant pavilions and event spaces at Paradise Hotel & Resort.">
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
                <h1><i class="fas fa-building"></i> Pavilion & Event Spaces</h1>
                <p>Host your special events in our elegant pavilions and versatile event spaces</p>
                <div class="gallery-breadcrumb">
                    <a href="../index.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Pavilion & Events</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Pavilion Details -->
    <section class="gallery-section">
        <div class="container">
            <!-- Event Spaces Overview -->
            <div class="facility-overview">
                <div class="overview-content">
                    <h2>Premium Event Venues</h2>
                    <p>Our pavilions and event spaces are designed to accommodate a wide range of occasions, from intimate gatherings to grand celebrations. Each venue features modern amenities, elegant décor, and flexible layouts to ensure your event is memorable and successful.</p>
                </div>
                <div class="facility-features">
                    <div class="feature">
                        <i class="fas fa-users"></i>
                        <span>Capacity: 50-500 guests</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-microphone"></i>
                        <span>Audio/Visual Equipment</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-wifi"></i>
                        <span>High-Speed WiFi</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-parking"></i>
                        <span>Dedicated Parking</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-utensils"></i>
                        <span>Catering Services</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-snowflake"></i>
                        <span>Climate Control</span>
                    </div>
                </div>
            </div>

            <!-- Photo Gallery -->
            <div class="gallery-grid">
                <?php if (!empty($pavilionPhotos)): ?>
                    <?php foreach ($pavilionPhotos as $index => $photo): ?>
                    <div class="gallery-item" data-index="<?php echo $index; ?>">
                        <img src="../<?php echo htmlspecialchars($photo['file_path']); ?>" alt="Pavilion <?php echo $index + 1; ?>" loading="lazy">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-photos">
                        <i class="fas fa-image"></i>
                        <h3>No Photos Available</h3>
                        <p>Pavilion photos will be displayed here once uploaded in the admin panel.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Event Types -->
            <div class="event-types">
                <h2>Perfect for Your Special Events</h2>
                <div class="event-grid">
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3>Weddings</h3>
                        <p>Create magical moments with our romantic pavilion settings and comprehensive wedding packages.</p>
                    </div>
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h3>Corporate Events</h3>
                        <p>Professional venues equipped with modern technology for conferences, meetings, and corporate gatherings.</p>
                    </div>
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-birthday-cake"></i>
                        </div>
                        <h3>Celebrations</h3>
                        <p>Birthday parties, anniversaries, and special celebrations in our beautifully decorated spaces.</p>
                    </div>
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3>Graduations</h3>
                        <p>Celebrate academic achievements with elegant venues perfect for graduation parties and ceremonies.</p>
                    </div>
                </div>
            </div>

            <!-- Booking Section -->
            <div class="gallery-booking">
                <div class="booking-card">
                    <h3><i class="fas fa-calendar-check"></i> Book Your Event</h3>
                    <p>Ready to host your special event in our beautiful pavilions?</p>
                    <div class="booking-features">
                        <div class="feature">
                            <i class="fas fa-phone"></i>
                            <span>Event Planning Support</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-utensils"></i>
                            <span>Custom Catering</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-music"></i>
                            <span>Entertainment Options</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-camera"></i>
                            <span>Photography Services</span>
                        </div>
                    </div>
                    <a href="../booking.php?tab=pavilion" class="btn btn-primary btn-large">
                        <i class="fas fa-calendar-check"></i>
                        Book Event Space Now
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
                <span id="lightbox-current">1</span> / <span id="lightbox-total"><?php echo count($pavilionPhotos); ?></span>
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


