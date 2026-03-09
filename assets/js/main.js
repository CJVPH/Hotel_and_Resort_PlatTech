// ============================================
// PARADISE HOTEL & RESORT - CONSOLIDATED JAVASCRIPT
// ============================================

// Global variables
let roomPrices = {};

// Room inventory - 18 total rooms with specific room numbers
const roomInventory = {
    '2': [
        // Top row
        { id: 'regular-101', type: 'Regular', name: 'Regular Room 101', number: '101', available: true },
        { id: 'deluxe-201', type: 'Deluxe', name: 'Deluxe Room 201', number: '201', available: true },
        { id: 'vip-301', type: 'VIP', name: 'VIP Suite 301', number: '301', available: true },
        // Bottom row
        { id: 'regular-102', type: 'Regular', name: 'Regular Room 102', number: '102', available: true },
        { id: 'deluxe-202', type: 'Deluxe', name: 'Deluxe Room 202', number: '202', available: true },
        { id: 'vip-302', type: 'VIP', name: 'VIP Suite 302', number: '302', available: true }
    ],
    '8': [
        // Top row
        { id: 'regular-103', type: 'Regular', name: 'Regular Family Room 103', number: '103', available: true },
        { id: 'deluxe-203', type: 'Deluxe', name: 'Deluxe Family Suite 203', number: '203', available: true },
        { id: 'vip-303', type: 'VIP', name: 'VIP Family Suite 303', number: '303', available: true },
        // Bottom row
        { id: 'regular-104', type: 'Regular', name: 'Regular Family Room 104', number: '104', available: true },
        { id: 'deluxe-204', type: 'Deluxe', name: 'Deluxe Family Suite 204', number: '204', available: true },
        { id: 'vip-304', type: 'VIP', name: 'VIP Family Suite 304', number: '304', available: true }
    ],
    '20': [
        // Top row
        { id: 'regular-105', type: 'Regular', name: 'Regular Group Villa 105', number: '105', available: true },
        { id: 'deluxe-205', type: 'Deluxe', name: 'Deluxe Group Villa 205', number: '205', available: true },
        { id: 'vip-305', type: 'VIP', name: 'VIP Group Mansion 305', number: '305', available: true },
        // Bottom row
        { id: 'regular-106', type: 'Regular', name: 'Regular Group Villa 106', number: '106', available: true },
        { id: 'deluxe-206', type: 'Deluxe', name: 'Deluxe Group Villa 206', number: '206', available: true },
        { id: 'vip-306', type: 'VIP', name: 'VIP Group Mansion 306', number: '306', available: true }
    ]
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('Paradise Hotel & Resort - Main JS Loaded');
    
    // Initialize all components
    initializeCarousel();
    initializeNavbar();
    initializeSmoothScroll();
    loadDynamicPhotos();
    
    // Initialize booking functionality if on booking page
    if (document.getElementById('bookingForm')) {
        initializeBookingPage();
    }
    
    // Initialize legacy reservation functionality if present
    initializeLegacyReservation();
    
    console.log('All components initialized successfully');
});

// ============================================
// HERO CAROUSEL
// ============================================

function initializeCarousel() {
    const slides = document.querySelectorAll('.carousel-slide');
    const indicators = document.querySelectorAll('.indicator');
    let currentSlide = 0;
    
    if (slides.length === 0) return;
    
    // Show first slide
    slides[0].classList.add('active');
    if (indicators.length > 0) {
        indicators[0].classList.add('active');
    }
    
    // Only auto-advance if there are multiple slides
    if (slides.length > 1) {
        // Auto-advance slides every 5 seconds
        setInterval(() => {
            nextSlide();
        }, 5000);
    }
    
    // Indicator click handlers
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
            goToSlide(index);
        });
    });
    
    function nextSlide() {
        if (slides.length <= 1) return;
        currentSlide = (currentSlide + 1) % slides.length;
        goToSlide(currentSlide);
    }
    
    function goToSlide(index) {
        if (slides.length <= 1) return;
        
        // Remove active class from all slides and indicators
        slides.forEach(slide => slide.classList.remove('active'));
        indicators.forEach(indicator => indicator.classList.remove('active'));
        
        // Add active class to current slide and indicator
        slides[index].classList.add('active');
        if (indicators[index]) {
            indicators[index].classList.add('active');
        }
        
        currentSlide = index;
    }
    
    // Keyboard navigation (left/right arrows)
    document.addEventListener('keydown', (e) => {
        if (slides.length <= 1) return;
        
        if (e.key === 'ArrowLeft') {
            currentSlide = (currentSlide - 1 + slides.length) % slides.length;
            goToSlide(currentSlide);
        } else if (e.key === 'ArrowRight') {
            nextSlide();
        }
    });
    
    // Touch/swipe support for mobile
    let startX = 0;
    let endX = 0;
    
    const carousel = document.querySelector('.hero-carousel');
    if (carousel && slides.length > 1) {
        carousel.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });
        
        carousel.addEventListener('touchend', (e) => {
            endX = e.changedTouches[0].clientX;
            handleSwipe();
        });
        
        function handleSwipe() {
            const swipeThreshold = 50;
            const diff = startX - endX;
            
            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    // Swipe left - next slide
                    nextSlide();
                } else {
                    // Swipe right - previous slide
                    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
                    goToSlide(currentSlide);
                }
            }
        }
    }
}

// ============================================
// NAVBAR FUNCTIONALITY
// ============================================

function initializeNavbar() {
    const navbar = document.querySelector('.navbar');
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (!navbar) return;
    
    // Mobile menu toggle
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
            document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
        });
        
        // Close menu when clicking on a link
        navMenu.querySelectorAll('.nav-link, .dropdown-item').forEach(link => {
            link.addEventListener('click', () => {
                navToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!navbar.contains(e.target) && navMenu.classList.contains('active')) {
                navToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
    
    // Navbar scroll effect
    window.addEventListener('scroll', () => {
        if (window.scrollY > 100) {
            navbar.style.background = 'rgba(44, 62, 80, 0.95)';
            navbar.style.boxShadow = '0 2px 30px rgba(0, 0, 0, 0.3)';
        } else {
            navbar.style.background = 'rgba(44, 62, 80, 0.85)';
            navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.2)';
        }
    });
}

// ============================================
// SMOOTH SCROLLING
// ============================================

function initializeSmoothScroll() {
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// ============================================
// DYNAMIC PHOTO LOADING
// ============================================

function loadDynamicPhotos() {
    // Load carousel photos
    loadSectionPhotos('carousel', '.carousel-slide');
    
    // Load section photos
    loadSectionPhotos('pool', '.pool-gallery');
    loadSectionPhotos('spa', '.spa-gallery');
    loadSectionPhotos('restaurant', '.restaurant-gallery');
    loadSectionPhotos('pavilion', '.pavilion-gallery');
}

function loadSectionPhotos(section, containerSelector) {
    const container = document.querySelector(containerSelector);
    if (!container) return;
    
    fetch(`api/get_room_images.php?section=${section}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.photos.length > 0) {
                updatePhotoSection(container, data.photos, section);
            }
        })
        .catch(error => {
            console.log(`Using default photos for ${section}:`, error);
        });
}

function updatePhotoSection(container, photos, section) {
    if (section === 'carousel') {
        updateCarouselPhotos(container, photos);
    } else {
        updateGalleryPhotos(container, photos);
    }
}

function updateCarouselPhotos(container, photos) {
    const slides = container.querySelectorAll('.carousel-slide');
    
    photos.forEach((photo, index) => {
        if (slides[index]) {
            slides[index].style.backgroundImage = `url('${photo.file_path}')`;
        }
    });
}

function updateGalleryPhotos(container, photos) {
    const photoItems = container.querySelectorAll('.photo-item img');
    
    photos.forEach((photo, index) => {
        if (photoItems[index]) {
            photoItems[index].src = photo.file_path;
            photoItems[index].alt = photo.original_name;
        }
    });
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

// Redirect all booking buttons to booking page
function redirectToBooking() {
    window.location.href = 'booking.php';
}

// Add event listeners to all booking buttons
document.addEventListener('DOMContentLoaded', function() {
    const bookingButtons = document.querySelectorAll('.book-now-btn, .carousel-btn[href*="book"]');
    
    bookingButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            redirectToBooking();
        });
    });
});

// ============================================
// ANIMATIONS AND EFFECTS
// ============================================

// Intersection Observer for animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe elements for animation
document.addEventListener('DOMContentLoaded', function() {
    const animatedElements = document.querySelectorAll('.photo-item, .testimonial, .section-header');
    
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

// ============================================
// ERROR HANDLING
// ============================================

window.addEventListener('error', function(e) {
    console.log('JavaScript error caught:', e.error);
});

// ============================================
// PERFORMANCE OPTIMIZATION
// ============================================

// Lazy loading for images
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
});