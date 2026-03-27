# Paradise Hotel & Resort

A full-stack PHP hotel reservation and management system built as a capstone project by PlatTech Solutions.

---

## Tech Stack

- **Backend:** PHP 8+, MySQL
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Libraries:** Flatpickr (date picker), Font Awesome 6, Google Fonts (Montserrat)
- **Auth:** Session-based + Google OAuth

---

## Folder Structure

```
ParadiseHotel/
│
├── index.php                   # Landing page
├── about.php                   # About Us page
├── booking.php                 # Room & Pavilion booking wizard
├── confirmation.php            # Booking confirmation / receipt
├── cancel_booking.php          # Cancel reservation handler
├── process.php                 # Room booking form processor
├── profile.php                 # User profile & booking history
├── login.php                   # User login
├── login_ajax.php              # AJAX login handler
├── logout.php                  # Logout
├── register.php                # User registration
├── redirect.php                # Post-OAuth redirect handler
├── room-gallery.php            # Room gallery page
├── vip-gallery.php             # VIP room gallery
├── upload_profile_photo.php    # Profile photo upload handler
├── database_setup.sql          # Full DB schema
├── jsconfig.json
│
├── admin/                      # Admin panel
│   ├── index.php               # Admin dashboard
│   ├── login.php               # Admin login
│   ├── logout.php
│   ├── auth.php                # Admin auth helper
│   ├── config.php              # Admin config
│   ├── reservations.php        # Room + Pavilion reservations management
│   ├── rooms.php               # Room management
│   ├── users.php               # User management
│   ├── reviews.php             # Guest reviews
│   ├── calendar.php            # Booking calendar
│   ├── settings.php            # Site settings
│   ├── pavilion_dashboard.php  # Pavilion booking management
│   ├── restaurant_dashboard.php
│   ├── bar_dashboard.php
│   ├── spa_dashboard.php
│   ├── water_activities_dashboard.php
│   ├── restaurant_menu_handler.php
│   ├── bar_menu_handler.php
│   ├── pavilion_menu_handler.php
│   ├── spa_service_handler.php
│   ├── water_activities_handler.php
│   ├── simple_upload.php
│   ├── upload_photos.php
│   ├── delete_image.php
│   ├── delete_photo.php
│   ├── delete_room_image.php
│   ├── delete_user.php
│   ├── get_homepage_settings.php
│   ├── save_homepage_settings.php
│   ├── google_oauth_status.php
│   ├── template_header.php     # Admin layout header
│   ├── template_footer.php     # Admin layout footer
│   └── assets/
│       ├── css/
│       │   ├── admin.css
│       │   ├── restaurant-styles.css
│       │   └── spa-styles.css
│       └── js/
│           ├── admin.js
│           ├── bar-script.js
│           ├── pavilion-script.js
│           ├── restaurant-script.js
│           ├── spa-script.js
│           └── water-activities-script.js
│
├── amenities/                  # Amenity pages
│   ├── index.php
│   ├── pool.php
│   ├── spa.php
│   ├── restaurant.php
│   ├── main-bar.php
│   ├── mini-bar.php
│   ├── pavilion.php
│   └── water-activities.php
│
├── api/                        # Internal JSON API endpoints
│   ├── get_availability.php
│   ├── get_room_availability.php
│   ├── get_room_image.php
│   ├── get_room_images.php
│   └── get_room_prices.php
│
├── assets/                     # Frontend assets
│   ├── css/
│   │   ├── main.css            # Global styles & navbar
│   │   ├── booking.css         # Booking wizard styles
│   │   ├── gallery.css         # Gallery & amenity styles
│   │   ├── about.css           # About Us page styles
│   │   ├── auth.css            # Login & Register styles
│   │   ├── index.css           # Landing page float panels
│   │   └── profile.css         # User profile styles
│   ├── js/
│   │   ├── main.js             # Global JS (navbar, carousel)
│   │   ├── booking.js          # Room booking wizard JS
│   │   ├── gallery.js          # Gallery lightbox JS
│   │   └── about.js            # About page navbar toggle
│   └── images/
│       └── default-room.jpg
│
├── config/                     # Configuration
│   ├── database.php            # DB connection
│   ├── auth.php                # Session auth helpers
│   └── google_oauth.php        # Google OAuth config
│
├── includes/                   # Shared PHP helpers
│   ├── pavilion_pricing.php    # Pavilion price calculation
│   └── photo_functions.php     # Photo fetch helpers
│
├── payment/                    # Payment pages
│   ├── payment.php             # Payment options selector
│   ├── payment_method.php      # Method chooser
│   ├── credit_card_form.php
│   ├── gcash_payment.php
│   ├── paypal_payment.php
│   ├── bank_transfer_payment.php
│   ├── cash_payment.php
│   ├── otc_payment.php
│   ├── pavilion_payment.php
│   ├── complete_payment.php
│   ├── process_credit_card.php
│   ├── process_gcash_payment.php
│   ├── process_paypal_payment.php
│   ├── process_bank_transfer_payment.php
│   ├── process_cash_payment.php
│   ├── process_otc_payment.php
│   ├── process_payment.php
│   ├── pavilion_process_payment.php
│   ├── index.php
│   ├── css/
│   │   └── payment.css
│   ├── js/
│   │   └── payment.js
│   └── uploads/
│       └── payment_proofs/     # Uploaded payment proof images
│
└── uploads/                    # All user-uploaded content
    ├── .htaccess               # Security rules
    ├── index.php
    ├── avatars/                # User profile photos
    ├── carousel/               # Homepage carousel images
    ├── logo/                   # Site logo
    │   └── logo.png
    ├── bar/                    # Bar section photos
    ├── pavilion/               # Pavilion photos
    ├── pavilion_menu/          # Pavilion menu item images
    ├── pool/                   # Pool photos
    ├── restaurant/             # Restaurant photos
    ├── rooms/                  # Room photos
    │   ├── individual/
    │   ├── regular/
    │   └── deluxe/
    ├── spa/                    # Spa photos
    ├── water_activities/       # Water activities photos
    ├── team/                   # About Us team photos
    ├── payment-icons/          # Payment method logos
    └── payment_proofs/         # Payment proof copies
```

---

## Setup

1. Import `database_setup.sql` into MySQL
2. Configure `config/database.php` with your DB credentials
3. Configure `config/google_oauth.php` with your Google OAuth keys
4. Point your web server root to the project folder
5. Ensure `uploads/` is writable by the web server

---

## Key Features

- Room booking wizard (4-step: Guest Info, Room, Dates, Review)
- Pavilion / Event Space booking with dynamic pricing
- Multiple payment methods: Credit Card, GCash, PayPal, Bank Transfer, Cash, OTC
- Admin dashboard with reservations, rooms, users, reviews, and amenity management
- Google OAuth login
- User profile with photo upload and booking history
- About Us page with team section
- Responsive design for all screen sizes
