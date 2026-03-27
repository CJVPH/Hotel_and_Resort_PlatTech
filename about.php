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
    <link rel="stylesheet" href="assets/css/about.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

            <div class="team-card" onclick="showBio('vergara')">
                <div class="team-photo">
                    <img src="uploads/team/vergara.png" alt="Christian James Vergara">
                    <div class="team-photo-overlay"><i class="fas fa-user"></i> View</div>
                </div>
                <div class="name">Vergara, Christian James N.</div>
                <div class="role">Leader · Backend · Frontend</div>
            </div>

            <div class="team-card" onclick="showBio('bunag')">
                <div class="team-photo">
                    <img src="uploads/team/Bunag.png" alt="Gabriel Evander Bunag">
                    <div class="team-photo-overlay"><i class="fas fa-user"></i> View</div>
                </div>
                <div class="name">Bunag, Gabriel Evander C.</div>
                <div class="role">Laptop Owner</div>
            </div>

            <div class="team-card" onclick="showBio('delima')">
                <div class="team-photo">
                    <img src="uploads/team/Delima.png" alt="Jedric Lloyd Delima">
                    <div class="team-photo-overlay"><i class="fas fa-user"></i> View</div>
                </div>
                <div class="name">Delima, Jedric Lloyd S.</div>
                <div class="role">Backend · Admin UI/UX</div>
            </div>

            <div class="team-card" onclick="showBio('ramos')">
                <div class="team-photo">
                    <img src="uploads/team/Ramos.png" alt="Jan Ramos">
                    <div class="team-photo-overlay"><i class="fas fa-user"></i> View</div>
                </div>
                <div class="name">Ramos, Jan M.</div>
                <div class="role">Frontend · UI/UX</div>
            </div>

        </div>
    </div>
</section>

<!-- Member Bio Modal -->
<div id="bioOverlay" onclick="closeBio()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9998;"></div>
<div id="bioPanel" onclick="event.stopPropagation()" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:9999;width:360px;max-width:92vw;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,0.3);">
    <!-- Full-width rectangular image -->
    <div style="position:relative;width:100%;aspect-ratio:4/3;background:#2C3E50;overflow:hidden;">
        <img id="bioImgEl" src="" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">
        <button onclick="closeBio()" style="position:absolute;top:0.75rem;right:0.75rem;background:rgba(0,0,0,0.45);border:none;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;">&times;</button>
    </div>
    <!-- Info -->
    <div style="padding:1.25rem 1.5rem;">
        <div id="bioName" style="font-weight:800;font-size:1.05rem;color:#2C3E50;"></div>
        <div id="bioRole" style="color:#C9A961;font-size:0.82rem;font-weight:600;margin:0.2rem 0 0.85rem;"></div>
        <p id="bioBio" style="color:#555;font-size:0.88rem;line-height:1.75;margin:0 0 0.75rem;"></p>
        <div id="bioLinkWrap" style="display:none;">
            <a id="bioLink" href="#"
               onclick="event.preventDefault(); event.stopPropagation(); window.open(this.dataset.href, '_blank');"
               style="display:inline-flex;align-items:center;gap:0.4rem;color:#C9A961;font-size:0.85rem;font-weight:700;text-decoration:none;border-bottom:1.5px solid #C9A961;padding-bottom:0.1rem;"
               onmouseover="this.style.opacity=0.75" onmouseout="this.style.opacity=1">
                <i class="fas fa-external-link-alt"></i>
                <span id="bioLinkLabel"></span>
            </a>
        </div>
    </div>
</div>

<script>
const members = {
    vergara: {
        name: 'Vergara, Christian James N.',
        role: 'Leader · Backend · Frontend',
        img:  'uploads/team/vergara.png',
        bio:  'CJ leads the team and handles both backend logic and frontend development. He is responsible for the overall system architecture, database design, booking flow, and making sure everything works end to end.'
    },
    bunag: {
        name: 'Bunag, Gabriel Evander C.',
        role: 'Laptop Owner',
        img:  'uploads/team/BunagLaptop.png',
        bio:  'Gabriel is the backbone of the team in more ways than one. As the laptop owner of Acer Nitro V15 Fully Paid, he ensures the team always has the hardware needed to keep development running smoothly and on schedule.',
        link: 'https://store.acer.com/en-ph/nitro-15-anv15-51-541p-obsidian-black-gaming',
        linkLabel: 'View His Laptop'
    },
    delima: {
        name: 'Delima, Jedric Lloyd S.',
        role: 'Backend · Admin UI/UX',
        img:  'uploads/team/Delima.png',
        bio:  'Jedric handles backend development and the admin panel UI/UX. He built the admin dashboard, reservation management, and the controls that keep the resort operations running behind the scenes.'
    },
    ramos: {
        name: 'Ramos, Jan M.',
        role: 'Frontend · UI/UX',
        img:  'uploads/team/Ramos.png',
        bio:  'Jan is responsible for the frontend design and user experience. He crafted the visual layout, responsive styles, and the overall look and feel that guests see when they visit the site.'
    }
};

function showBio(key) {
    const m = members[key];
    document.getElementById('bioImgEl').src  = m.img;
    document.getElementById('bioImgEl').alt  = m.name;
    document.getElementById('bioName').textContent = m.name;
    document.getElementById('bioRole').textContent = m.role;
    document.getElementById('bioBio').textContent  = m.bio;
    const linkWrap = document.getElementById('bioLinkWrap');
    if (m.link) {
        document.getElementById('bioLink').dataset.href = m.link;
        document.getElementById('bioLinkLabel').textContent = m.linkLabel || m.link;
        linkWrap.style.display = '';
    } else {
        linkWrap.style.display = 'none';
    }
    document.getElementById('bioOverlay').style.display = '';
    document.getElementById('bioPanel').style.display   = '';
}

function closeBio() {
    document.getElementById('bioOverlay').style.display = 'none';
    document.getElementById('bioPanel').style.display   = 'none';
}
</script>

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
<script src="assets/js/about.js"></script>
</body>
</html>
