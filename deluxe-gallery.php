<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'includes/photo_functions.php';

// Get all deluxe room photos from room_images table
function getRoomPhotos($roomType, $limit = null) {
    try {
        $conn = getDBConnection();
        $sql = "SELECT * FROM room_images WHERE room_type = ? AND is_active = 1 ORDER BY sort_order ASC, upload_date DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $roomType);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $photos = [];
        while ($row = $result->fetch_assoc()) {
            $photos[] = $row;
        }
        
        $stmt->close();
        $conn->close();
        
        return $photos;
    } catch (Exception $e) {
        error_log("Error fetching room photos: " . $e->getMessage());
        return [];
    }
}

$deluxePhotos = getRoomPhotos('Deluxe', 50);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deluxe Rooms Gallery - Paradise Hotel & Resort</title>
    <meta name="description" content="Browse our Deluxe Rooms gallery and book your perfect luxury stay at Paradise Hotel & Resort.">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/gallery.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php#top" class="nav-logo">
                <i class="fas fa-hotel"></i>
                <span>Paradise Hotel & Resort</span>
            </a>
            <button class="nav-toggle" aria-label="Toggle navigation menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-menu">
                <a href="regular-gallery.php" class="nav-link">Regular</a>
                <a href="deluxe-gallery.php" class="nav-link">Deluxe</a>
                <a href="vip-gallery.php" class="nav-link">VIP</a>
                
                <?php if (isLoggedIn()): ?>
                    <a href="booking.php" class="nav-link book-now">
                        <i class="fas fa-calendar-check"></i>
                        Book Now
                    </a>
                    <a href="profile.php" class="nav-user" style="text-decoration: none; cursor: pointer;"><i class="fas fa-user-circle"></i><span>Hello, <?php echo htmlspecialchars(getFirstName() ?? getUsername()); ?></span></a>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="booking.php" class="nav-link book-now">
                        <i class="fas fa-calendar-check"></i>
                        Book Now
                    </a>
                    <a href="login.php" class="nav-link">
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
                <h1><i class="fas fa-crown"></i> Deluxe Rooms Gallery</h1>
                <p>Experience luxury and elegance in our premium Deluxe Rooms</p>
                <div class="gallery-breadcrumb">
                    <a href="index.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Deluxe Rooms</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Content -->
    <section class="gallery-section">
        <div class="container">
            <div class="gallery-grid">
                <?php if (!empty($deluxePhotos)): ?>
                    <?php foreach ($deluxePhotos as $index => $photo): ?>
                    <div class="gallery-item" data-index="<?php echo $index; ?>" data-image="<?php echo htmlspecialchars($photo['file_path']); ?>">
                        <img src="<?php echo htmlspecialchars($photo['file_path']); ?>" alt="Deluxe Room <?php echo $index + 1; ?>" loading="lazy">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-photos">
                        <i class="fas fa-image"></i>
                        <h3>No Photos Available</h3>
                        <p>Deluxe room photos will be displayed here once uploaded in the admin panel.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Book Now Section -->
            <div class="gallery-booking">
                <div class="booking-card">
                    <h3><i class="fas fa-calendar-check"></i> Ready to Book?</h3>
                    <p>Indulge in luxury and premium amenities in our Deluxe Rooms</p>
                    <div class="booking-features">
                        <div class="feature">
                            <i class="fas fa-wifi"></i>
                            <span>Premium WiFi</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-tv"></i>
                            <span>Smart TV</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-hot-tub"></i>
                            <span>Jacuzzi</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-concierge-bell"></i>
                            <span>Room Service</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-mountain"></i>
                            <span>City View</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-glass-cheers"></i>
                            <span>Mini Bar</span>
                        </div>
                    </div>
                    <a href="booking.php" class="btn btn-primary btn-large">
                        <i class="fas fa-calendar-check"></i>
                        Book Deluxe Room Now
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
                <span id="lightbox-current">1</span> / <span id="lightbox-total"><?php echo count($deluxePhotos); ?></span>
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
                    <p><a href="index.php#pool">Swimming Pool</a></p>
                    <p><a href="index.php#spa">Spa & Wellness</a></p>
                    <p><a href="index.php#restaurant">Fine Dining</a></p>
                    <p><a href="booking.php">Book Now</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Paradise Hotel & Resort. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/gallery.js"></script>
</body>
</html>