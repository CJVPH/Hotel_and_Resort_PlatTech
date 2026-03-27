<?php
require_once 'config/database.php';
require_once 'config/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Paradise Hotel & Resort</title>
    <link rel="stylesheet" href="assets/css/main.css?v=2.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f6f8; }

        /* ── Hero ── */
        .about-hero {
            background: linear-gradient(135deg, #2C3E50 0%, #34495E 100%);
            padding: 7rem 0 5rem;
            position: relative;
            overflow: hidden;
        }
        .about-hero::after {
            content: '';
            position: absolute;
            bottom: -2px; left: 0; right: 0;
            height: 60px;
            background: #f4f6f8;
            clip-path: ellipse(55% 100% at 50% 100%);
        }
        .about-hero-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }
        .about-hero h1 {
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            font-weight: 800;
            color: #fff;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        .about-hero h1 span { color: #C9A961; }
        .about-hero p {
            color: rgba(255,255,255,0.85);
            font-size: 1.1rem;
            line-height: 1.8;
        }
        .about-hero-img {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0,0,0,0.4);
            aspect-ratio: 4/3;
            background: linear-gradient(135deg,#3d5166,#2C3E50);
            display: flex; align-items: center; justify-content: center;
        }
        .about-hero-img img { width:100%; height:100%; object-fit:cover; display:block; }
        .about-hero-img-placeholder { color: rgba(255,255,255,0.3); text-align:center; }
        .about-hero-img-placeholder i { font-size: 4rem; display:block; margin-bottom:0.5rem; }

        /* ── Sections ── */
        .about-section { padding: 5rem 0; }
        .about-section:nth-child(even) { background: #fff; }
        .about-container { max-width: 1100px; margin: 0 auto; padding: 0 2rem; }

        /* ── Story row ── */
        .about-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }
        .about-row.reverse { direction: rtl; }
        .about-row.reverse > * { direction: ltr; }
        .about-row-img {
            border-radius: 20px;
            overflow: hidden;
            aspect-ratio: 4/3;
            background: linear-gradient(135deg,#e9ecef,#dee2e6);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        .about-row-img img { width:100%; height:100%; object-fit:cover; display:block; }
        .about-row-img-placeholder { color: #aaa; text-align:center; }
        .about-row-img-placeholder i { font-size: 3rem; display:block; margin-bottom:0.5rem; }
        .about-row-text h2 {
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 800;
            color: #2C3E50;
            margin-bottom: 1rem;
            line-height: 1.3;
        }
        .about-row-text h2 span { color: #C9A961; }
        .about-row-text p {
            color: #555;
            line-height: 1.85;
            font-size: 0.97rem;
            margin-bottom: 0.85rem;
        }

        /* ── Stats ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin: 3rem 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #2C3E50, #34495E);
            border-radius: 16px;
            padding: 2rem 1rem;
            text-align: center;
            color: white;
        }
        .stat-card .num { font-size: 2.2rem; font-weight: 800; color: #C9A961; }
        .stat-card .lbl { font-size: 0.85rem; margin-top: 0.4rem; opacity: 0.85; }

        /* ── Values ── */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        .value-card {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            border-bottom: 3px solid #C9A961;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .value-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,0.1); }
        .value-card i { font-size: 2rem; color: #C9A961; margin-bottom: 1rem; display: block; }
        .value-card h4 { color: #2C3E50; font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem; }
        .value-card p { color: #666; font-size: 0.9rem; line-height: 1.7; }

        /* ── Photo placeholders row ── */
        .photo-placeholders {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 2.5rem;
        }
        .photo-placeholders .about-row-img { aspect-ratio: 4/3; }

        /* ── Team ── */
        .team-section { background: #fff; padding: 5rem 0; }
        .section-label {
            text-align: center;
            margin-bottom: 3.5rem;
        }
        .section-label h2 {
            font-size: clamp(1.6rem, 3vw, 2.2rem);
            font-weight: 800;
            color: #2C3E50;
            margin-bottom: 0.5rem;
        }
        .section-label p { color: #888; font-size: 0.97rem; }
        .team-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }
        .team-card {
            text-align: center;
            background: #f8f9fa;
            border-radius: 20px;
            padding: 2rem 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .team-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .team-photo {
            width: 110px; height: 110px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            border: 4px solid #C9A961;
            overflow: hidden;
            background: linear-gradient(135deg, #2C3E50, #34495E);
            display: flex; align-items: center; justify-content: center;
        }
        .team-photo img { width:100%; height:100%; object-fit:cover; display:block; }
        .team-photo i { font-size: 2.5rem; color: rgba(201,169,97,0.6); }
        .team-card .name { font-weight: 700; color: #2C3E50; font-size: 1rem; }
        .team-card .role { color: #C9A961; font-size: 0.82rem; margin-top: 0.3rem; font-weight: 600; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .about-hero-inner, .about-row { grid-template-columns: 1fr; gap: 2rem; }
            .about-row.reverse { direction: ltr; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .values-grid { grid-template-columns: repeat(2, 1fr); }
            .photo-placeholders { grid-template-columns: 1fr; }
            .team-grid { grid-template-columns: repeat(2, 1fr); }
            .about-hero { padding: 6rem 0 4rem; }
        }
        @media (max-width: 480px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
            .values-grid { grid-template-columns: 1fr; }
            .team-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">
            <img src="uploads/logo/logo.png" alt="Logo" class="nav-logo-img"
                 onerror="this.style.display='none'">
            <span>Paradise Hotel & Resort</span>
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
        <div class="nav-menu" id="navMenu">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
            <a href="about.php" class="nav-link" style="color:#C9A961;"><i class="fas fa-hotel"></i> About Us</a>
            <?php if (isLoggedIn()): ?>
                <a href="booking.php" class="nav-link book-now">
                    <i class="fas fa-calendar-check"></i> Book Now
                </a>
            <?php else: ?>
                <a href="login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="booking.php" class="nav-link book-now">
                    <i class="fas fa-calendar-check"></i> Book Now
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Hero -->
<section class="about-hero">
    <div class="about-hero-inner">
        <div>
            <h1>About <span>Paradise</span><br>Hotel & Resort</h1>
            <p>Nestled along the pristine shores of Nasugbu, Batangas, where the sea meets serenity and every stay becomes a memory.</p>
        </div>
        <div class="about-hero-img">
            <img src="uploads/team/about us.jpg" alt="Paradise Hotel & Resort">
        </div>
    </div>
</section>

<!-- Our Story -->
<section class="about-section">
    <div class="about-container">
        <div class="about-row">
            <div class="about-row-img">
                <img src="uploads/team/OurStory.png" alt="Our Story">
            </div>
            <div class="about-row-text">
                <h2>Our <span>Story</span></h2>
                <p>Paradise Hotel & Resort was founded with a single vision to create a sanctuary where every guest feels at home while experiencing the finest in Filipino hospitality.</p>
                <p>Situated in the coastal barangay of Calayo, Nasugbu, Batangas, our resort was built to celebrate the natural beauty of the West Philippine Sea.</p>
                <p>From our humble beginnings as a family-owned retreat, we have grown into a full-service resort offering world-class accommodations, event venues, dining, spa, and water activities, all while staying true to our roots of warm, personal service.</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card"><div class="num">10+</div><div class="lbl">Years of Excellence</div></div>
            <div class="stat-card"><div class="num">5,000+</div><div class="lbl">Happy Guests</div></div>
            <div class="stat-card"><div class="num">30+</div><div class="lbl">Room Options</div></div>
            <div class="stat-card"><div class="num">100%</div><div class="lbl">Filipino Hospitality</div></div>
        </div>
    </div>
</section>

<!-- Our Mission -->
<section class="about-section">
    <div class="about-container">
        <div class="about-row reverse">
            <div class="about-row-img">
                <img src="uploads/team/mission.png" alt="Our Mission">
            </div>
            <div class="about-row-text">
                <h2>Our <span>Mission</span></h2>
                <p>To deliver an unmatched resort experience rooted in genuine Filipino warmth, where every guest is treated like family and every detail is crafted with care.</p>
                <p>We believe that a great stay goes beyond comfortable beds and beautiful views. It is about the people, the moments, and the feeling of being truly welcomed.</p>
            </div>
        </div>
    </div>
</section>

<!-- Community Building -->
<section class="about-section">
    <div class="about-container">
        <div class="about-row">
            <div class="about-row-img">
                <img src="uploads/team/community.png" alt="Community Building">
            </div>
            <div class="about-row-text">
                <h2>Community <span>Building</span></h2>
                <p>We invest in the local community of Nasugbu, partnering with residents, supporting local businesses, and creating opportunities that grow together with our guests.</p>
                <p>Every stay at Paradise contributes to a bigger purpose, uplifting the people and culture of Batangas while sharing its beauty with the world.</p>
            </div>
        </div>
    </div>
</section>

<!-- Convenience -->
<section class="about-section">
    <div class="about-container">
        <div class="about-row reverse">
            <div class="about-row-img">
                <img src="uploads/team/Convenience.png" alt="Convenience">
            </div>
            <div class="about-row-text">
                <h2>Your <span>Convenience</span></h2>
                <p>From seamless online booking to on-site services, we make every step of your stay effortless so you can focus on what matters most, relaxing and enjoying.</p>
                <p>Our team is available around the clock to assist with anything you need, ensuring a smooth and stress-free experience from arrival to checkout.</p>
            </div>
        </div>
    </div>
</section>

<!-- Hospitality -->
<section class="about-section">
    <div class="about-container">
        <div class="about-row">
            <div class="about-row-img">
                <img src="uploads/team/hospitality.png" alt="Hospitality First">
            </div>
            <div class="about-row-text">
                <h2>Hospitality <span>First</span></h2>
                <p>Hospitality is not just a service, it is our culture. Every smile, every gesture, and every interaction is driven by a genuine desire to make your experience exceptional.</p>
                <p>We take pride in going the extra mile because at Paradise, you are never just a guest. You are part of our family.</p>
            </div>
        </div>
    </div>
</section>

<!-- Behind the Resort — Team -->
<section class="team-section">
    <div class="about-container">
        <div class="section-label">
            <h2>Behind Paradise Resort</h2>
            <p>The people who make the magic happen.</p>
        </div>

        <!-- Group photo -->
        <div class="about-row-img" style="max-width:700px;margin:0 auto 3rem;aspect-ratio:16/7;">
            <img src="uploads/team/GroupPhoto.png" alt="Team Group Photo">
        </div>

        <div class="team-grid">

            <div class="team-card">
                <div class="team-photo">
                    <img src="uploads/team/vergara.png" alt="Christian James Vergara">
                </div>
                <div class="name">Vergara, Christian James</div>
                <div class="role">Leader · Backend · Frontend</div>
            </div>

            <div class="team-card">
                <div class="team-photo">
                    <img src="uploads/team/Bunag.png" alt="Gabriel Evander Bunag">
                </div>
                <div class="name">Bunag, Gabriel Evander C.</div>
                <div class="role">Laptop Owner</div>
            </div>

            <div class="team-card">
                <div class="team-photo">
                    <img src="uploads/team/Delima.png" alt="Jedric Lloyd Delima">
                </div>
                <div class="name">Delima, Jedric Lloyd S.</div>
                <div class="role">Backend · Admin UI/UX</div>
            </div>

            <div class="team-card">
                <div class="team-photo">
                    <img src="uploads/team/Ramos.png" alt="Jan Ramos">
                </div>
                <div class="name">Ramos, Jan</div>
                <div class="role">Frontend · UI/UX</div>
            </div>

        </div>
    </div>
</section>

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
                <p><i class="fas fa-phone"></i> +63 (917) 123-4567</p>
                <p><i class="fas fa-envelope"></i> info@paradisehotel.com</p>
                <p><i class="fas fa-map-marker-alt"></i> Calayo, Nasugbu, Batangas</p>
            </div>
            <div class="footer-section">
                <h3><i class="fas fa-link"></i> Quick Links</h3>
                <p><a href="index.php">Home</a></p>
                <p><a href="booking.php">Book Now</a></p>
                <p><a href="amenities/spa.php">Spa & Wellness</a></p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Paradise Hotel & Resort. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="assets/js/main.js"></script>
<script>
    const navToggle = document.getElementById('navToggle');
    const navMenu   = document.getElementById('navMenu');
    if (navToggle) navToggle.addEventListener('click', () => {
        navToggle.classList.toggle('active');
        navMenu.classList.toggle('active');
    });
</script>
</body>
</html>
