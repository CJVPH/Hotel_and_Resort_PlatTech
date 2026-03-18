// ============================================
// GALLERY LIGHTBOX FUNCTIONALITY
// ============================================

(function() {
    'use strict';
    
    console.log('Gallery lightbox loaded');
    
    let lightboxInstance = null;
    
    function initLightbox() {
        console.log('Initializing gallery lightbox...');
        
        const galleryItems = document.querySelectorAll('.gallery-item');
        const lightbox = document.getElementById('lightbox');
        
        console.log('Found gallery items:', galleryItems.length);
        console.log('Found lightbox:', !!lightbox);
        
        if (!lightbox) {
            console.error('Lightbox element not found');
            return;
        }
        
        if (galleryItems.length === 0) {
            console.log('No gallery items found');
            return;
        }
        
        const lightboxImage = document.getElementById('lightbox-image');
        const lightboxClose = document.querySelector('.lightbox-close');
        const lightboxPrev = document.getElementById('lightbox-prev');
        const lightboxNext = document.getElementById('lightbox-next');
        const lightboxCurrent = document.getElementById('lightbox-current');
        const lightboxTotal = document.getElementById('lightbox-total');
        
        if (!lightboxImage) {
            console.error('Lightbox image not found');
            return;
        }
        
        if (!lightboxClose) {
            console.error('Lightbox close button not found');
            return;
        }
        
        console.log('All lightbox elements found');
        
        // Collect images
        const images = [];
        galleryItems.forEach((item, index) => {
            const img = item.querySelector('img');
            if (img && img.src) {
                images.push({
                    src: img.src,
                    alt: img.alt || `Gallery image ${index + 1}`
                });
            }
        });
        
        console.log('Collected images:', images.length);
        
        if (lightboxTotal) {
            lightboxTotal.textContent = images.length;
        }
        
        let currentIndex = 0;
        
        // Open lightbox function
        function openLightbox(index) {
            console.log('Opening lightbox for index:', index);
            
            if (index < 0 || index >= images.length) {
                console.error('Invalid index:', index);
                return;
            }
            
            currentIndex = index;
            
            // Set image
            lightboxImage.src = images[currentIndex].src;
            lightboxImage.alt = images[currentIndex].alt;
            
            // Update counter
            if (lightboxCurrent) {
                lightboxCurrent.textContent = currentIndex + 1;
            }
            
            // Show lightbox
            lightbox.style.display = 'flex';
            lightbox.style.opacity = '1';
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
            
            console.log('Lightbox opened');
        }
        
        // Close lightbox function
        function closeLightbox() {
            console.log('Closing lightbox');
            
            lightbox.style.opacity = '0';
            
            setTimeout(() => {
                lightbox.style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
        }
        
        // Navigation functions
        function showPrevious() {
            if (images.length <= 1) return;
            currentIndex = currentIndex > 0 ? currentIndex - 1 : images.length - 1;
            openLightbox(currentIndex);
        }
        
        function showNext() {
            if (images.length <= 1) return;
            currentIndex = currentIndex < images.length - 1 ? currentIndex + 1 : 0;
            openLightbox(currentIndex);
        }
        
        // Add click listeners to gallery items
        galleryItems.forEach((item, index) => {
            item.style.cursor = 'pointer';
            
            item.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Gallery item clicked:', index);
                openLightbox(index);
            });
            
            console.log('Added click listener to item:', index);
        });
        
        // Close button
        lightboxClose.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeLightbox();
        });
        
        // Navigation buttons
        if (lightboxPrev) {
            lightboxPrev.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showPrevious();
            });
        }
        
        if (lightboxNext) {
            lightboxNext.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showNext();
            });
        }
        
        // Background click to close
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) {
                closeLightbox();
            }
        });
        
        // Keyboard navigation
        function handleLightboxKeydown(e) {
            if (lightbox.style.display === 'flex') {
                switch(e.key) {
                    case 'Escape':
                        e.preventDefault();
                        e.stopPropagation();
                        closeLightbox();
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        e.stopPropagation();
                        showPrevious();
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        e.stopPropagation();
                        showNext();
                        break;
                }
            }
        }
        
        document.addEventListener('keydown', handleLightboxKeydown);
        
        // Initialize lightbox
        lightbox.style.display = 'none';
        lightbox.style.opacity = '0';
        lightbox.style.transition = 'opacity 0.3s ease';
        
        console.log('Gallery lightbox initialization complete');
        
        // Store instance for debugging
        lightboxInstance = {
            openLightbox,
            closeLightbox,
            showPrevious,
            showNext,
            images,
            currentIndex: () => currentIndex
        };
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLightbox);
    } else {
        initLightbox();
    }
    
    // Expose re-init for tab switching (room-gallery.php)
    window.initRgLightbox = initLightbox;

})();
