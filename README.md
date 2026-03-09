# 🏨 Paradise Hotel & Resort - Complete System Documentation

A comprehensive hotel reservation and management system with admin panel, menu management, and multi-image galleries.

---

## 📋 Table of Contents
1. [System Requirements](#system-requirements)
2. [Installation](#installation)
3. [Admin Credentials](#admin-credentials)
4. [Database Structure](#database-structure)
5. [Features](#features)
6. [Menu Systems](#menu-systems)
7. [File Upload Directories](#file-upload-directories)
8. [Important Notes](#important-notes)
9. [Troubleshooting](#troubleshooting)

---

## 🖥️ System Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- GD Library (for image processing)

---

## 📦 Installation

### 1. Database Setup
```bash
# Import the complete database schema
mysql -u root -p < database_setup.sql
```

### 2. Configure Database Connection
Edit `config/database.php`:
```php
$host = 'localhost';
$dbname = 'hotel_reservation';
$username = 'root';
$password = 'your_password';
```

### 3. Set Folder Permissions
```bash
chmod 755 uploads/
chmod 755 uploads/carousel/
chmod 755 uploads/rooms/
chmod 755 uploads/spa/
chmod 755 uploads/restaurant/
chmod 755 uploads/pavilion/
chmod 755 uploads/pool/
chmod 755 uploads/pavilion_menu/
chmod 755 uploads/water_activities/
chmod 755 uploads/bar/
```

---

## 🔐 Admin Credentials

**Default Admin Account:**
- **Username:** `admin`
- **Password:** `admin123`
- **Email:** `admin@paradisehotel.com`

**Admin Panel URL:** `http://yoursite.com/admin/`

⚠️ **IMPORTANT:** Change the default password after first login!

---

## 🗄️ Database Structure

### Main Tables
1. **users** - User accounts (customers and admin)
2. **reservations** - Room bookings with payment tracking
3. **room_prices** - Dynamic pricing by room type and pax
4. **room_images** - Multiple images per room (up to 10 per room)
5. **website_photos** - Gallery photos by section
6. **spa_services** - Spa treatments and services
7. **restaurant_menu_items** - Restaurant menu with categories
8. **pavilion_menu** - Pavilion food/services
9. **water_activities_menu** - Water activities offerings
10. **bar_menu** - Bar drinks (mini bar & main bar)

---

## ✨ Features

### Customer Features
- Browse rooms (Regular, Deluxe, VIP) with multiple images
- View galleries (Pool, Spa, Pavilion, Restaurant)
- Make reservations (with/without login)
- Flexible payment options (25%, 50%, 75%, 100%)
- View spa services and restaurant menu
- Category filtering on restaurant menu
- View pavilion menu, water activities, and bar menus

### Admin Features
- Dashboard with statistics
- Reservation management
- User management
- Room pricing control
- Photo gallery management (multiple images per room)
- **Spa service management** (with images)
- **Restaurant menu management** (with categories and images)
- **Pavilion menu management** (with images)
- **Water activities management** (with images)
- **Bar menu management** (toggle between mini/main bar, with images)

---

## 🍽️ Menu Systems

### Restaurant Menu
- **Categories:** Appetizers, Main Courses, Desserts, Beverages, Chef's Specials
- **Fields:** Name, Description, Category, Price, Prep Time, Image
- **Frontend:** Category filter buttons, 2-column grid layout
- **Admin:** Full CRUD operations, bulk price adjustment
- **Currency:** Philippine Peso (₱)
- **Price Step:** 10 pesos

### Spa Services
- **Fields:** Name, Description, Price, Duration, Image
- **Frontend:** 2-column grid layout
- **Admin:** Full CRUD operations, enable/disable services
- **Currency:** Philippine Peso (₱)
- **Price Step:** 10 pesos

### Pavilion Menu
- **Fields:** Name, Description, Price, Prep Time, Image
- **Frontend:** 2-column grid layout
- **Admin:** Full CRUD operations
- **Currency:** Philippine Peso (₱)
- **Price Step:** 10 pesos

### Water Activities
- **Fields:** Name, Description, Price, Duration, Image
- **Frontend:** 2-column grid layout
- **Admin:** Full CRUD operations
- **Currency:** Philippine Peso (₱)
- **Price Step:** 10 pesos

### Bar Menu (Mini Bar & Main Bar)
- **Fields:** Name, Description, Bar Type (mini/main), Price, Image
- **Frontend:** Separate pages for mini bar and main bar
- **Admin:** Single dashboard with toggle button to switch between mini/main bar
- **Currency:** Philippine Peso (₱)
- **Price Step:** 10 pesos

---

## 📁 File Upload Directories

```
uploads/
├── carousel/          # Homepage carousel images
├── logo/             # Site logo
├── rooms/            # Room gallery images
│   ├── regular/
│   ├── deluxe/
│   └── individual/   # Individual room images (up to 10 per room)
├── pavilion/         # Pavilion gallery images
├── pool/             # Pool gallery images
├── spa/              # Spa gallery & service images
├── restaurant/       # Restaurant gallery & menu item images
├── pavilion_menu/    # Pavilion menu item images
├── water_activities/ # Water activities images
└── bar/              # Bar menu item images
```

**Image Requirements:**
- Max file size: 5MB
- Allowed formats: JPG, JPEG, PNG, GIF, WebP
- Recommended dimensions: 1200x800px for galleries, 400x400px for menu items

---

## ⚠️ Important Notes

### Currency
- All prices are in **Philippine Peso (₱)**
- Price input step: **10 pesos** (not 0.01)
- This applies to all menu systems (restaurant, spa, pavilion, water activities, bar)

### Room System
- **18 Individual Rooms:** 101-106 (Regular), 201-206 (Deluxe), 301-306 (VIP)
- **Guest Capacities:** 2, 4-8, or 10-20 guests
- **Multiple Images:** Up to 10 images per room
- **Drag & Drop Upload:** Easy image management in admin panel

### Room Pricing
- Prices are set per room type and pax group (2, 8, 20 people)
- Default prices are in `database_setup.sql`
- Admin can update prices anytime

### Guest Bookings
- Users can book without logging in
- Login is required only for payment
- Guest bookings have `user_id = NULL`

### Image System
- All menu systems support image uploads
- Images are optional (fallback to default image: `assets/images/default-room.jpg`)
- Images stored in respective upload folders
- Filenames are unique: `{uniqid}_{timestamp}.{ext}`

### Menu Item Availability
- All menu items have an "available" toggle
- Unavailable items are hidden from frontend
- Admin can bulk enable/disable items

### Restaurant Category Filtering
- Frontend has category filter buttons
- Filters: All Foods, Appetizers, Main Courses, Desserts, Beverages, Chef's Specials
- JavaScript-based filtering (no page reload)

### Bar Menu Toggle
- Admin dashboard has toggle button to switch between Mini Bar and Main Bar
- Single interface manages both bar types
- Frontend has separate pages for each bar type

---

## 🔧 Troubleshooting

### Images Not Displaying
1. Check folder permissions (755)
2. Verify image exists in uploads folder
3. Check database for correct filename
4. Clear browser cache (Ctrl+Shift+R)
5. Check image path in HTML source

### Database Connection Error
1. Verify credentials in `config/database.php`
2. Ensure MySQL service is running
3. Check database name is `hotel_reservation`
4. Test connection with simple PHP script

### Admin Login Not Working
1. Verify admin account exists in database
2. Password: `admin123` (default)
3. Check `is_admin = 1` in users table
4. Clear browser cookies

### Upload Errors
1. Check PHP `upload_max_filesize` (min 5MB)
2. Check PHP `post_max_size` (min 10MB)
3. Verify folder permissions (755)
4. Check file type is allowed (JPG, PNG, GIF, WebP)
5. Check file size is under 5MB

### Menu Items Not Showing
1. Check `available = 1` in database
2. Verify image path is correct
3. Check category matches (for restaurant)
4. Check bar_type matches (for bar menu)
5. Clear browser cache

### Category Filter Not Working
1. Check JavaScript console for errors
2. Verify category attribute on menu items
3. Check button data-category values
4. Ensure gallery.js is loaded

---

## 📝 Development Notes

### Code Structure
- **Frontend:** Root directory (*.php)
- **Admin:** `/admin/` directory
- **Config:** `/config/` directory
- **Assets:** `/assets/` (CSS, JS, images)
- **Uploads:** `/uploads/` (user-uploaded content)

### JavaScript Files
- `main.js` - Main site navigation and interactions
- `gallery.js` - Lightbox and gallery functionality
- `booking.js` - Reservation form handling
- `admin.js` - Admin panel common functions
- `restaurant-script.js` - Restaurant menu management
- `spa-script.js` - Spa service management

### Admin Navigation
All menu systems are accessible from admin sidebar:
- Dashboard
- Reservations
- Rooms & Pricing
- Users
- Spa Management
- Restaurant Management
- Pavilion Menu
- Water Activities
- Bar Management
- Settings (Photo Management & Homepage Content)

---

## 🎨 Design Features

### Colors
- **Primary:** Charcoal (#2C3E50, #34495E)
- **Accent:** Gold (#C9A961, #8B7355)
- **Price Color:** Red (#e74c3c)

### Layout
- **2-Column Grid:** All menu systems use consistent 2-column layout
- **Image Left:** 120x120px square images on the left
- **Content Right:** Name, price, description, duration/prep time
- **Responsive:** Stacks to 1 column on mobile

### Pricing Structure
| Room Type | 2 Guests | 8 Guests | 20 Guests |
|-----------|----------|----------|-----------|
| Regular   | ₱1,500   | ₱3,000   | ₱6,000    |
| Deluxe    | ₱2,500   | ₱4,500   | ₱8,500    |
| VIP       | ₱4,000   | ₱7,000   | ₱12,000   |

---

## 🚀 Future Enhancements (To Be Implemented)

### Pending Features
- [ ] Frontend pages for Pavilion, Water Activities, and Bar menus
- [ ] Email notifications for bookings
- [ ] Online payment gateway integration
- [ ] Customer reviews and ratings
- [ ] Booking calendar view
- [ ] Multi-language support

---

## 📞 Support
For issues or questions, contact the development team.

---

**Last Updated:** 2026-02-10  
**Version:** 2.1  
**Status:** Production Ready (All admin menu systems complete, frontend pages pending)
