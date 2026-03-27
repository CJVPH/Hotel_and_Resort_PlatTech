<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'includes/photo_functions.php';

// Base URL for assets (handles subdirectory installs like /Hotel_and_Resort_PlatTech/)
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = rtrim($scriptDir, '/') . '/';

// Get photos for different sections
$carouselPhotos = getPhotosWithFallback('carousel', 3);
$poolPhotos = getPhotosWithFallback('pool', 3);
$spaPhotos = getPhotosWithFallback('spa', 3);
$restaurantPhotos = getPhotosWithFallback('restaurant', 3);
$pavilionPhotos = getPhotosWithFallback('pavilion', 3);

// Load guest reviews from DB
$guestReviews = [];
try {
    $conn = getDBConnection();
    $res = $conn->query("SELECT * FROM guest_reviews WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC");
    if ($res) { while ($row = $res->fetch_assoc()) $guestReviews[] = $row; }
    $conn->close();
} catch (Exception $e) { $guestReviews = []; }
?>
<!DOCTYPE html>
<html lang="en" id="top">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paradise Hotel & Resort - Luxury Accommodation</title>
    <meta name="description" content="Experience luxury at Paradise Hotel & Resort. Premium accommodations, world-class amenities, and exceptional service.">
    <link rel="stylesheet" href="assets/css/main.css?v=2.0">
    <link rel="stylesheet" href="assets/css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="#top" class="nav-logo">
                <img src="<?php echo $baseUrl; ?>uploads/logo/logo.png" alt="Paradise Hotel & Resort" class="nav-logo-img" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;">
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
                        <a href="room-gallery.php?type=regular" class="dropdown-item">
                            <i class="fas fa-bed"></i> Regular
                        </a>
                        <a href="room-gallery.php?type=deluxe" class="dropdown-item">
                            <i class="fas fa-crown"></i> Deluxe
                        </a>
                        <a href="room-gallery.php?type=vip" class="dropdown-item">
                            <i class="fas fa-gem"></i> VIP
                        </a>
                    </div>
                </div>
                
                <a href="#pavilion" class="nav-link">
                    <i class="fas fa-building"></i> Pavilion
                </a>
                
                <div class="nav-dropdown">
                    <a href="javascript:void(0)" class="nav-link">
                        Activities
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="amenities/pool.php" class="dropdown-item">
                            <i class="fas fa-swimming-pool"></i> Pool
                        </a>
                        <a href="amenities/spa.php" class="dropdown-item">
                            <i class="fas fa-spa"></i> Spa
                        </a>
                        <a href="amenities/water-activities.php" class="dropdown-item">
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
                        <a href="amenities/mini-bar.php" class="dropdown-item">
                            <i class="fas fa-glass-cheers"></i> Mini Bar
                        </a>
                        <a href="amenities/main-bar.php" class="dropdown-item">
                            <i class="fas fa-cocktail"></i> Main Bar
                        </a>
                        <a href="amenities/restaurant.php" class="dropdown-item">
                            <i class="fas fa-utensils"></i> Restaurant
                        </a>
                    </div>
                </div>

                <div class="nav-dropdown">
                    <a href="javascript:void(0)" class="nav-link">
                        <i class="fas fa-info-circle"></i> About
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="about.php" class="dropdown-item">
                            <i class="fas fa-hotel"></i> About Us
                        </a>
                        <a href="javascript:void(0)" class="dropdown-item" onclick="toggleFloat('faq-float')">
                            <i class="fas fa-question-circle"></i> FAQ
                        </a>
                    </div>
                </div>
                
                <?php if (isLoggedIn()): ?>
                    <a href="booking.php" class="nav-link book-now">
                        <i class="fas fa-calendar-check"></i>
                        Book Now
                    </a>
                    <a href="profile.php" class="nav-user" style="text-decoration: none; cursor: pointer;">
                        <i class="fas fa-user-circle"></i>
                        <span>Hello, <?php echo htmlspecialchars(getFirstName() ?? getUsername()); ?></span>
                    </a>
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

    <!-- Hero Carousel -->
    <section class="hero-carousel">
        <?php if (!empty($carouselPhotos)): ?>
            <?php 
            $carouselTitles = [
                'Welcome to Paradise<br>Hotel & Resort',
                'Special Offers',
                'Luxury Redefined',
                'Unforgettable Experience',
                'Premium Amenities',
                'Paradise Awaits'
            ];
            $carouselSubtitles = [
                'Experience luxury, comfort, and unforgettable memories',
                'Discover amazing deals and packages for your perfect getaway',
                'Indulge in premium amenities and exceptional service',
                'Create memories that will last a lifetime',
                'Enjoy world-class facilities and personalized service',
                'Your perfect vacation destination awaits you'
            ];
            $carouselButtons = [
                ['icon' => 'fas fa-calendar-check', 'text' => 'Book Your Stay Now'],
                ['icon' => 'fas fa-tags', 'text' => 'View Offers'],
                ['icon' => 'fas fa-crown', 'text' => 'Experience Luxury'],
                ['icon' => 'fas fa-heart', 'text' => 'Discover Paradise'],
                ['icon' => 'fas fa-star', 'text' => 'Premium Experience'],
                ['icon' => 'fas fa-gem', 'text' => 'Book Paradise']
            ];
            ?>
            
            <?php foreach ($carouselPhotos as $index => $photo): ?>
            <div class="carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>" 
                 style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('<?php echo $baseUrl . $photo['file_path']; ?>');">
                <div class="carousel-content">
                    <h1><?php echo $carouselTitles[$index % count($carouselTitles)]; ?></h1>
                    <p><?php echo $carouselSubtitles[$index % count($carouselSubtitles)]; ?></p>
                    
                    <!-- Service Features -->
                    <div class="service-features">
                        <div class="feature-item">
                            <i class="fas fa-star"></i>
                            <span>5 Star Luxury</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-wifi"></i>
                            <span>Free WIFI</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-parking"></i>
                            <span>Free Parking</span>
                        </div>
                    </div>
                    
                    <a href="booking.php" class="carousel-btn">
                        <i class="<?php echo $carouselButtons[$index % count($carouselButtons)]['icon']; ?>"></i> 
                        <?php echo $carouselButtons[$index % count($carouselButtons)]['text']; ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            
        <?php else: ?>
            <!-- Default slide when no images are uploaded -->
            <div class="carousel-slide active" style="background: linear-gradient(135deg, #2C3E50 0%, #34495E 100%);">
                <div class="carousel-content">
                    <h1>Welcome to Paradise<br>Hotel & Resort</h1>
                    <p>Experience luxury, comfort, and unforgettable memories</p>
                    
                    <!-- Service Features -->
                    <div class="service-features">
                        <div class="feature-item">
                            <i class="fas fa-star"></i>
                            <span>5 Star Luxury</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-wifi"></i>
                            <span>Free WIFI</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-parking"></i>
                            <span>Free Parking</span>
                        </div>
                    </div>
                    
                    <a href="booking.php" class="carousel-btn">
                        <i class="fas fa-calendar-check"></i> Book Your Stay Now
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Dynamic Carousel Indicators -->
        <?php if (!empty($carouselPhotos) && count($carouselPhotos) > 1): ?>
        <div class="carousel-indicators">
            <?php foreach ($carouselPhotos as $index => $photo): ?>
            <div class="indicator <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>"></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Pavilion & Event Section -->
        <section id="pavilion" class="section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-building"></i> Pavilion & Event
                    </h2>
                    <p class="section-subtitle">
                        Host your special events in our elegant pavilions and event spaces
                    </p>
                </div>
                <div class="photo-gallery pavilion-gallery">
                    <?php foreach ($pavilionPhotos as $index => $photo): ?>
                    <div class="photo-item">
                        <a href="amenities/pavilion.php">
                            <img src="<?php echo $baseUrl . $photo['file_path']; ?>" alt="Pavilion <?php echo $index + 1; ?>">
                            <div class="photo-overlay">
                                <h3><i class="fas fa-building"></i> Pavilion & Events</h3>
                                <p>Host weddings, reunions, and corporate events in style</p>
                                <div class="view-more">
                                    <i class="fas fa-arrow-right"></i>
                                    <span>Explore Venue</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Swimming Pool Section -->
        <section id="pool" class="section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-swimming-pool"></i> Swimming Pool
                    </h2>
                    <p class="section-subtitle">
                        Dive into luxury with our pristine swimming pools and aquatic facilities
                    </p>
                </div>
                <div class="photo-gallery pool-gallery">
                    <?php foreach ($poolPhotos as $index => $photo): ?>
                    <div class="photo-item">
                        <a href="amenities/pool.php">
                            <img src="<?php echo $baseUrl . $photo['file_path']; ?>" alt="Pool <?php echo $index + 1; ?>">
                            <div class="photo-overlay">
                                <h3><i class="fas fa-swimming-pool"></i> Swimming Pool</h3>
                                <p>Dive in and unwind in our crystal-clear pools</p>
                                <div class="view-more">
                                    <i class="fas fa-arrow-right"></i>
                                    <span>See More</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Restaurant Section -->
        <section id="restaurant" class="section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-utensils"></i> Restaurant
                    </h2>
                    <p class="section-subtitle">
                        Savor exquisite cuisine crafted by our world-renowned chefs
                    </p>
                </div>
                <div class="photo-gallery restaurant-gallery">
                    <?php foreach ($restaurantPhotos as $index => $photo): ?>
                    <div class="photo-item">
                        <a href="amenities/restaurant.php">
                            <img src="<?php echo $baseUrl . $photo['file_path']; ?>" alt="Restaurant <?php echo $index + 1; ?>">
                            <div class="photo-overlay">
                                <h3><i class="fas fa-utensils"></i> Restaurant</h3>
                                <p>Fresh flavors, local ingredients, unforgettable dining</p>
                                <div class="view-more">
                                    <i class="fas fa-arrow-right"></i>
                                    <span>View Menu</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Spa Section -->
        <section id="spa" class="section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-spa"></i> Spa
                    </h2>
                    <p class="section-subtitle">
                        Rejuvenate your body and soul with our premium spa treatments
                    </p>
                </div>
                <div class="photo-gallery spa-gallery">
                    <?php foreach ($spaPhotos as $index => $photo): ?>
                    <div class="photo-item">
                        <a href="amenities/spa.php">
                            <img src="<?php echo $baseUrl . $photo['file_path']; ?>" alt="Spa <?php echo $index + 1; ?>">
                            <div class="photo-overlay">
                                <h3><i class="fas fa-spa"></i> Spa & Wellness</h3>
                                <p>Relax, recharge, and treat yourself to pure bliss</p>
                                <div class="view-more">
                                    <i class="fas fa-arrow-right"></i>
                                    <span>Book a Treatment</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section id="testimonials" class="section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-star"></i> Guest Reviews
                    </h2>
                    <p class="section-subtitle">
                        Hear what our valued guests have to say about their experience
                    </p>
                </div>
                <div class="testimonials">
                    <?php if (!empty($guestReviews)): ?>
                        <?php foreach ($guestReviews as $review): ?>
                        <div class="testimonial">
                            <div class="testimonial-content">
                                "<?php echo htmlspecialchars($review['review_text']); ?>"
                            </div>
                            <div class="testimonial-author">
                                <div class="author-avatar"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($review['guest_name'], 0, 2))); ?></div>
                                <div class="author-info">
                                    <h4><?php echo htmlspecialchars($review['guest_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($review['guest_type']); ?></p>
                                    <div class="stars">
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <i class="fas fa-star<?php echo $s > $review['rating'] ? '-o' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align:center;color:rgba(255,255,255,0.6);">No reviews yet.</p>
                    <?php endif; ?>
                </div>
                <div style="text-align: center; margin-top: 3rem;">
                    <a href="<?php echo isLoggedIn() ? 'booking.php' : 'login.php'; ?>" class="btn btn-primary">
                        <i class="fas fa-calendar-check"></i> Book Your Stay
                    </a>
                </div>
            </div>
        </section>

    </main>

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
                    <p><a href="#pool">Swimming Pool</a></p>
                    <p><a href="#spa">Spa & Wellness</a></p>
                    <p><a href="#restaurant">Fine Dining</a></p>
                    <p><a href="<?php echo isLoggedIn() ? 'booking.php' : 'login.php'; ?>">Book Now</a></p>
                </div>
            </div>

            <!-- Map inside footer -->
            <div style="margin:2rem 0;border-radius:12px;overflow:hidden;border:2px solid rgba(201,169,97,0.3);">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15534.234567890123!2d120.6234567!3d13.9876543!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd6f5e5e5e5e5e%3A0x5e5e5e5e5e5e5e5e!2sCalayo%2C%20Nasugbu%2C%20Batangas%2C%20Philippines!5e0!3m2!1sen!2sph!4v1707567890123!5m2!1sen!2sph"
                    width="100%" height="300" style="display:block;border:0;"
                    allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Paradise Hotel & Resort. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        function toggleFaq(btn) {
            const answer = btn.nextElementSibling;
            const isOpen = btn.classList.contains('open');
            document.querySelectorAll('.faq-question').forEach(q => q.classList.remove('open'));
            document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('open'));
            if (!isOpen) { btn.classList.add('open'); answer.classList.add('open'); }
        }
        function toggleFloat(id) {
            const panel = document.getElementById(id);
            const isOpen = panel.classList.contains('open');
            document.querySelectorAll('.float-panel').forEach(p => p.classList.remove('open'));
            if (!isOpen) panel.classList.add('open');
        }
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.float-panel') && !e.target.closest('.nav-dropdown')) {
                document.querySelectorAll('.float-panel').forEach(p => p.classList.remove('open'));
            }
        });
    </script>

    <!-- Floating FAQ Panel -->
    <div id="faq-float" class="float-panel">
        <div class="float-panel-header">
            <h3><i class="fas fa-question-circle"></i> FAQ</h3>
            <button onclick="toggleFloat('faq-float')"><i class="fas fa-times"></i></button>
        </div>
        <div class="float-panel-body">
            <div class="faq-list">
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)"><span>What time is check-in and check-out?</span><i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Check-in is at <strong>2:00 PM</strong> and check-out is at <strong>12:00 PM</strong>. Early check-in and late check-out may be arranged subject to availability.</p></div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)"><span>Do I need an account to book?</span><i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>You can browse as a guest, but an account is required to complete payment. You can sign in quickly with Google.</p></div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)"><span>What payment methods are accepted?</span><i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>We accept <strong>GCash, PayPal, credit/debit cards, bank transfer, OTC</strong>, and cash payments.</p></div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)"><span>Can I cancel my reservation?</span><i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Yes, through your profile page. Please cancel at least <strong>48 hours before</strong> check-in to avoid fees.</p></div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)"><span>Is the resort pet-friendly?</span><i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>We currently do not allow pets inside the resort to ensure the comfort of all guests.</p></div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)"><span>Are the pool and spa open to all guests?</span><i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Yes, the pool is open to all registered guests. Spa services are by appointment with additional charges.</p></div>
                </div>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)"><span>Where is the resort located?</span><i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p><strong>Calayo, Nasugbu, Batangas, Philippines</strong> — about 2–3 hours from Metro Manila along the West Philippine Sea.</p></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Developer Panel -->
</body>
</html>
