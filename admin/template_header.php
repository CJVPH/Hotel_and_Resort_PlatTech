<?php
// This file contains the common header and navigation for all admin pages
// Include this at the top of each admin page after the PHP logic
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin'; ?> - Paradise Hotel Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-body">
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-left">
            <h1>
                <img src="../uploads/logo/logo.png" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:0.5rem;">
                paradise hotel and resort admin
            </h1>
        </div>
        <div class="admin-header-right">
            <span class="admin-name"><?php echo htmlspecialchars(getAdminFullName() ?? getAdminUsername()); ?></span>
            <a href="logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> log out
            </a>
        </div>
    </header>

    <div class="admin-layout">
        <!-- Sidebar Navigation -->
        <nav class="admin-sidebar">
            <div class="sidebar-content">
                <a href="index.php" class="nav-item <?php echo ($currentPage ?? '') === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="reservations.php" class="nav-item <?php echo ($currentPage ?? '') === 'reservations' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i> Reservations
                </a>
                <a href="calendar.php" class="nav-item <?php echo ($currentPage ?? '') === 'calendar' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i> Calendar Management
                </a>
                <a href="rooms.php" class="nav-item <?php echo ($currentPage ?? '') === 'rooms' ? 'active' : ''; ?>">
                    <i class="fas fa-bed"></i> Rooms & Pricing
                </a>
                <a href="users.php" class="nav-item <?php echo ($currentPage ?? '') === 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="spa_dashboard.php" class="nav-item <?php echo ($currentPage ?? '') === 'spa_dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-spa"></i> Spa Management
                </a>
                <a href="restaurant_dashboard.php" class="nav-item <?php echo ($currentPage ?? '') === 'restaurant_dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-utensils"></i> Restaurant Management
                </a>
                <a href="pavilion_dashboard.php" class="nav-item <?php echo ($currentPage ?? '') === 'pavilion_dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-archway"></i> Pavilion
                </a>
                <a href="water_activities_dashboard.php" class="nav-item <?php echo ($currentPage ?? '') === 'water_activities_dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-water"></i> Water Activities
                </a>
                <a href="bar_dashboard.php" class="nav-item <?php echo ($currentPage ?? '') === 'bar_dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-cocktail"></i> Bar Management
                </a>
                <a href="settings.php" class="nav-item <?php echo ($currentPage ?? '') === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="../index.php" class="nav-item" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View Site
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="content-container">