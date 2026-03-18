<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'includes/photo_functions.php';

function getRoomPhotosByType($roomType, $limit = 50) {
    try {
        $conn = getDBConnection();
        $sql = "SELECT * FROM room_images WHERE room_type = ? AND is_active = 1 ORDER BY sort_order ASC, upload_date DESC LIMIT " . intval($limit);
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $roomType);
        $stmt->execute();
        $result = $stmt->get_result();
        $photos = [];
        while ($row = $result->fetch_assoc()) $photos[] = $row;
        $stmt->close(); $conn->close();
        return $photos;
    } catch (Exception $e) { return []; }
}

$rawType = strtolower($_GET['type'] ?? 'regular');
$activeType = $rawType === 'vip' ? 'VIP' : ucfirst($rawType);
if (!in_array($activeType, ['Regular', 'Deluxe', 'VIP'])) $activeType = 'Regular';

$photos = getRoomPhotosByType($activeType);

$typeConfig = [
    'Regular' => [
        'icon'     => 'fa-bed',
        'title'    => 'Regular Rooms Gallery',
        'subtitle' => 'Comfortable and affordable rooms for a relaxing stay',
        'crumb'    => 'Regular Rooms',
        'features' => ['fa-wifi:Free WiFi','fa-snowflake:Air Conditioning','fa-tv:Flat Screen TV','fa-bath:Private Bathroom','fa-coffee:Coffee Maker','fa-concierge-bell:Room Service'],
        'btn'      => 'Book Regular Room Now',
    ],
    'Deluxe'  => [
        'icon'     => 'fa-crown',
        'title'    => 'Deluxe Rooms Gallery',
        'subtitle' => 'Experience luxury and elegance in our premium Deluxe Rooms',
        'crumb'    => 'Deluxe Rooms',
        'features' => ['fa-wifi:Premium WiFi','fa-tv:Smart TV','fa-hot-tub:Jacuzzi','fa-concierge-bell:Room Service','fa-city:City View','fa-glass-cheers:Mini Bar'],
        'btn'      => 'Book Deluxe Room Now',
    ],
    'VIP'     => [
        'icon'     => 'fa-gem',
        'title'    => 'VIP Suites Gallery',
        'subtitle' => 'The ultimate luxury experience with exclusive VIP services',
        'crumb'    => 'VIP Suites',
        'features' => ['fa-crown:VIP Service','fa-spa:Private Spa','fa-utensils:Personal Chef','fa-car:Limousine','fa-swimming-pool:Private Pool','fa-concierge-bell:24/7 Concierge'],
        'btn'      => 'Book VIP Suite Now',
    ],
];
$cfg = $typeConfig[$activeType];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $cfg['title']; ?> - Paradise Hotel & Resort</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/gallery.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ── Gallery navbar — true 3-column grid layout ── */
        .gallery-navbar {
            background: rgba(44, 62, 80, 0.95) !important;
            backdrop-filter: blur(10px) !important;
            position: fixed !important;
            top: 0; width: 100%; z-index: 1000;
            padding: 0.6rem 0 !important;
            box-shadow: 0 2px 20px rgba(0,0,0,.2) !important;
        }
        /* Override main.css flex layout with grid for true centering */
        .gallery-navbar .nav-container {
            display: grid !important;
            grid-template-columns: 1fr auto 1fr !important;
            align-items: center !important;
            padding: 0 1.5rem !important;
            width: 100% !important;
            max-width: 100% !important;
            gap: 0 !important;
            flex-wrap: unset !important;
            position: static !important;
        }
        /* Col 1: Logo — left-aligned */
        .gallery-navbar .nav-logo {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            color: white !important;
            text-decoration: none !important;
            white-space: nowrap !important;
            justify-content: flex-start !important;
            font-size: 1.6rem !important;
            font-weight: 700 !important;
        }
        .gallery-navbar .nav-logo img {
            width: 36px !important;
            height: 36px !important;
            border-radius: 50% !important;
            object-fit: cover !important;
            flex-shrink: 0 !important;
        }
        /* Col 2: Tabs — centered */
        .rg-type-tabs {
            display: flex !important;
            align-items: center !important;
            gap: 0.25rem !important;
            justify-content: center !important;
        }
        .rg-type-link {
            color: rgba(255,255,255,.75);
            text-decoration: none;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            padding: 0.7rem 1.2rem;
            border-radius: 50px;
            transition: background 0.2s, color 0.2s;
            white-space: nowrap;
            display: inline-block;
            position: relative;
            z-index: 10;
        }
        .rg-type-link:hover { background: rgba(255,255,255,.1); color: #fff; }
        .rg-type-link.active { background: rgba(255,255,255,.15); color: #fff; font-weight: 700; }
        /* Col 3: Right actions — right-aligned */
        .rg-nav-right {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 0.5rem !important;
        }
        /* Book Now gold pill */
        .rg-nav-right .nav-link.book-now {
            background: linear-gradient(135deg, #C9A961 0%, #D4AF37 100%) !important;
            color: white !important;
            font-weight: 700 !important;
            padding: 0.4rem 0.9rem !important;
            border-radius: 50px !important;
            box-shadow: 0 3px 10px rgba(201,169,97,.4) !important;
            white-space: nowrap !important;
        }
        .rg-nav-right .nav-link.book-now:hover {
            background: linear-gradient(135deg, #D4AF37 0%, #C9A961 100%) !important;
            transform: translateY(-2px) !important;
        }
        .rg-nav-right .nav-link {
            color: rgba(255,255,255,.85) !important;
            white-space: nowrap !important;
        }
        .rg-nav-right .nav-user {
            display: flex; align-items: center; gap: .4rem;
            color: rgba(255,255,255,.85); text-decoration: none;
            font-size: .9rem; white-space: nowrap; font-weight: 600;
        }
        body { padding-top: 70px; }
        .gallery-header { padding-top: 5rem !important; }

        @media (max-width: 768px) {
            .gallery-navbar .nav-logo span { display: none; }
            .rg-type-link { padding: .5rem .7rem; font-size: .85rem; }
            .gallery-navbar .nav-container {
                grid-template-columns: auto 1fr auto !important;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar gallery-navbar">
        <div class="nav-container">
            <!-- Logo -->
            <a href="index.php#top" class="nav-logo">
                <img src="uploads/logo/logo.png" alt="Paradise Hotel & Resort" class="nav-logo-img">
                <span>Paradise Hotel & Resort</span>
            </a>

            <!-- Center: Room type tabs -->
            <div class="rg-type-tabs">
                <a href="room-gallery.php?type=regular" class="rg-type-link <?php echo $activeType==='Regular'?'active':''; ?>">Regular</a>
                <a href="room-gallery.php?type=deluxe"  class="rg-type-link <?php echo $activeType==='Deluxe' ?'active':''; ?>">Deluxe</a>
                <a href="room-gallery.php?type=vip"     class="rg-type-link <?php echo $activeType==='VIP'    ?'active':''; ?>">VIP</a>
            </div>

            <!-- Right: actions -->
            <div class="rg-nav-right">
                <a href="booking.php?type=<?php echo strtolower($activeType); ?>" class="nav-link book-now">
                    <i class="fas fa-calendar-check"></i> Book Now
                </a>
                <?php if (isLoggedIn()): ?>
                    <a href="profile.php" class="nav-user">
                        <i class="fas fa-user-circle"></i>
                        <span>Hello, <?php echo htmlspecialchars(getFirstName() ?? getUsername()); ?></span>
                    </a>
                    <a href="logout.php" class="nav-link" style="padding:.4rem .8rem;">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link"><i class="fas fa-user"></i> Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <section class="gallery-header">
        <div class="container">
            <div class="gallery-header-content">
                <h1><i class="fas <?php echo $cfg['icon']; ?>"></i> <?php echo $cfg['title']; ?></h1>
                <p><?php echo $cfg['subtitle']; ?></p>
                <div class="gallery-breadcrumb">
                    <a href="index.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span><?php echo $cfg['crumb']; ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery -->
    <section class="gallery-section">
        <div class="container">
            <div class="gallery-grid">
                <?php if (!empty($photos)): ?>
                    <?php foreach ($photos as $i => $photo): ?>
                    <div class="gallery-item" data-index="<?php echo $i; ?>" data-image="<?php echo htmlspecialchars($photo['file_path']); ?>">
                        <img src="<?php echo htmlspecialchars($photo['file_path']); ?>" alt="<?php echo $activeType; ?> Room <?php echo $i+1; ?>" loading="lazy">
                        <div class="gallery-overlay"><i class="fas fa-search-plus"></i></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-photos">
                        <i class="fas fa-image"></i>
                        <h3>No Photos Available</h3>
                        <p><?php echo $activeType; ?> room photos will be displayed here once uploaded in the admin panel.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Booking CTA -->
            <div class="gallery-booking">
                <div class="booking-card">
                    <h3><i class="fas fa-calendar-check"></i> Ready to Book?</h3>
                    <p><?php echo $cfg['subtitle']; ?></p>
                    <div class="booking-features">
                        <?php foreach ($cfg['features'] as $f):
                            [$icon, $label] = explode(':', $f); ?>
                        <div class="feature">
                            <i class="fas <?php echo $icon; ?>"></i>
                            <span><?php echo $label; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="booking.php?type=<?php echo strtolower($activeType); ?>" class="btn btn-primary btn-large">
                        <i class="fas fa-calendar-check"></i> <?php echo $cfg['btn']; ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Lightbox -->
    <div id="lightbox" class="lightbox">
        <div class="lightbox-content">
            <span class="lightbox-close">&times;</span>
            <img id="lightbox-image" src="" alt="">
            <div class="lightbox-nav">
                <button id="lightbox-prev" class="lightbox-btn"><i class="fas fa-chevron-left"></i></button>
                <button id="lightbox-next" class="lightbox-btn"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="lightbox-counter">
                <span id="lightbox-current">1</span> / <span id="lightbox-total"><?php echo count($photos); ?></span>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-hotel"></i> Paradise Hotel & Resort</h3>
                    <p>Experience luxury and comfort in our world-class resort.</p>
                </div>
                <div class="footer-section">
                    <h3><i class="fas fa-map-marker-alt"></i> Contact Info</h3>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> info@paradisehotel.com</p>
                </div>
                <div class="footer-section">
                    <h3><i class="fas fa-clock"></i> Quick Links</h3>
                    <p><a href="index.php#pool">Swimming Pool</a></p>
                    <p><a href="index.php#spa">Spa & Wellness</a></p>
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
    <script>
        // Prevent main.js scroll handler from changing gallery navbar background
        (function() {
            const nb = document.querySelector('.gallery-navbar');
            if (!nb) return;
            window.addEventListener('scroll', function() {
                nb.style.background = '#1e2d3d';
            }, true);
        })();
    </script>
</body>
</html>
