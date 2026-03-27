<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/photo_functions.php';

// Get restaurant photos
$restaurantPhotos = getPhotosWithFallback('restaurant', 20);

// Get restaurant menu items from database
$menuItems = [];
try {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM restaurant_menu_items WHERE available = 1 ORDER BY category, name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $menuItems[] = $row;
        }
    }
    $conn->close();
} catch (Exception $e) {
    error_log("Error loading menu items: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant - Paradise Hotel & Resort</title>
    <meta name="description" content="Savor exquisite cuisine crafted by our world-renowned chefs at Paradise Hotel & Resort.">
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
                <h1><i class="fas fa-utensils"></i> Restaurant</h1>
                <p>Savor exquisite cuisine crafted by our world-renowned chefs</p>
                <div class="gallery-breadcrumb">
                    <a href="../index.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Restaurant</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Restaurant Details -->
    <section class="gallery-section">
        <div class="container">
            <!-- Restaurant Overview -->
            <div class="facility-overview">
                <div class="overview-content">
                    <h2>Culinary Excellence</h2>
                    <p>Our restaurant offers an exceptional dining experience featuring international cuisine prepared by award-winning chefs. From intimate dinners to family celebrations, we provide an elegant atmosphere with breathtaking views and impeccable service.</p>
                </div>
                <div class="facility-features">
                    <div class="feature">
                        <i class="fas fa-chef-hat"></i>
                        <span>Award-Winning Chefs</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-globe"></i>
                        <span>International Cuisine</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-wine-glass"></i>
                        <span>Premium Wine Selection</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-mountain"></i>
                        <span>Scenic Views</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-leaf"></i>
                        <span>Fresh Local Ingredients</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-clock"></i>
                        <span>All-Day Dining</span>
                    </div>
                </div>
            </div>

            <!-- Photo Gallery -->
            <div class="gallery-grid">
                <?php if (!empty($restaurantPhotos)): ?>
                    <?php foreach ($restaurantPhotos as $index => $photo): ?>
                    <div class="gallery-item" data-index="<?php echo $index; ?>">
                        <img src="../<?php echo htmlspecialchars($photo['file_path']); ?>" alt="Restaurant <?php echo $index + 1; ?>" loading="lazy">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-photos">
                        <i class="fas fa-image"></i>
                        <h3>No Photos Available</h3>
                        <p>Restaurant photos will be displayed here once uploaded in the admin panel.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Dining Options -->
            <div class="event-types">
                <h2>Dining Experiences</h2>
                <div class="event-grid">
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-sun"></i>
                        </div>
                        <h3>Breakfast</h3>
                        <p>Start your day with our extensive breakfast buffet featuring fresh pastries, tropical fruits, and made-to-order specialties.</p>
                    </div>
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h3>À La Carte</h3>
                        <p>Enjoy our carefully curated menu featuring signature dishes and seasonal specialties prepared with the finest ingredients.</p>
                    </div>
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-wine-glass"></i>
                        </div>
                        <h3>Fine Dining</h3>
                        <p>Experience our premium tasting menu with wine pairings in an elegant setting perfect for special occasions.</p>
                    </div>
                    <div class="event-type">
                        <div class="event-icon">
                            <i class="fas fa-birthday-cake"></i>
                        </div>
                        <h3>Private Events</h3>
                        <p>Host intimate celebrations with customized menus and dedicated service in our private dining areas.</p>
                    </div>
                </div>
            </div>

            <!-- Restaurant Hours & Info -->
            <div class="pool-info">
                <div class="info-section">
                    <h3><i class="fas fa-clock"></i> Dining Hours</h3>
                    <ul>
                        <li><strong>Breakfast:</strong> 6:30 AM - 10:30 AM</li>
                        <li><strong>Lunch:</strong> 12:00 PM - 3:00 PM</li>
                        <li><strong>Dinner:</strong> 6:00 PM - 10:00 PM</li>
                        <li><strong>Room Service:</strong> 24/7 Available</li>
                    </ul>
                </div>
                <div class="info-section">
                    <h3><i class="fas fa-info-circle"></i> Reservations</h3>
                    <ul>
                        <li>Advance reservations recommended</li>
                        <li>Dress code: Smart casual</li>
                        <li>Special dietary requirements accommodated</li>
                        <li>Private dining rooms available</li>
                    </ul>
                </div>
            </div>

            <!-- Menu Highlights -->
            <div class="menu-highlights">
                <h2>Our Menu</h2>
                
                <?php if (!empty($menuItems)): ?>
                    <!-- Category Filter Buttons -->
                    <div class="category-filter-buttons">
                        <button class="category-btn active" data-category="all">All Foods</button>
                        <?php
                        $categories = [
                            'appetizer' => 'Appetizers',
                            'main' => 'Main Courses',
                            'dessert' => 'Desserts',
                            'beverage' => 'Beverages',
                            'special' => "Chef's Specials"
                        ];
                        
                        // Check which categories have items
                        $availableCategories = array_unique(array_column($menuItems, 'category'));
                        
                        foreach ($categories as $catKey => $catName):
                            if (in_array($catKey, $availableCategories)):
                        ?>
                            <button class="category-btn" data-category="<?php echo $catKey; ?>"><?php echo $catName; ?></button>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                    
                    <div class="menu-items-list">
                        <?php foreach ($menuItems as $item): ?>
                            <div class="menu-list-item" data-category="<?php echo htmlspecialchars($item['category']); ?>">
                                <div class="menu-item-image-small">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="../uploads/restaurant/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
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
                                    <span class="prep-time"><i class="fas fa-clock"></i> <?php echo intval($item['prep_time']); ?> min</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-menu-items">
                        <i class="fas fa-utensils"></i>
                        <h3>Menu Coming Soon</h3>
                        <p>Our delicious menu items will be displayed here once they are added in the admin panel.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Booking Section -->
            <div class="gallery-booking">
                <div class="booking-card">
                    <h3><i class="fas fa-calendar-check"></i> Make a Reservation</h3>
                    <p>Experience exceptional dining at our award-winning restaurant</p>
                    <div class="booking-features">
                        <div class="feature">
                            <i class="fas fa-phone"></i>
                            <span>Call: +1 (555) 123-4567</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-envelope"></i>
                            <span>Email Reservations</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-users"></i>
                            <span>Group Bookings</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-gift"></i>
                            <span>Special Occasions</span>
                        </div>
                    </div>
                    <a href="../booking.php" class="btn btn-primary btn-large">
                        <i class="fas fa-calendar-check"></i>
                        Book Your Stay & Dining
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
                <span id="lightbox-current">1</span> / <span id="lightbox-total"><?php echo count($restaurantPhotos); ?></span>
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
    <script>
        // Category filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const categoryButtons = document.querySelectorAll('.category-btn');
            const menuItems = document.querySelectorAll('.menu-list-item');
            
            categoryButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const category = this.getAttribute('data-category');
                    
                    // Update active button
                    categoryButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter menu items
                    menuItems.forEach(item => {
                        if (category === 'all') {
                            item.classList.remove('hidden');
                        } else {
                            if (item.getAttribute('data-category') === category) {
                                item.classList.remove('hidden');
                            } else {
                                item.classList.add('hidden');
                            }
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>


