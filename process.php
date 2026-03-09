<?php
require_once 'config/database.php';
require_once 'config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: booking.php?error=Invalid request method');
    exit();
}

// Get form data
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$guests = intval($_POST['guests'] ?? 0);
$checkin = $_POST['checkin'] ?? '';
$checkout = $_POST['checkout'] ?? '';
$room = $_POST['room'] ?? '';
$price = floatval($_POST['price'] ?? 0);
$specialRequests = trim($_POST['special_requests'] ?? '');
$options = $_POST['options'] ?? '';

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Full name is required';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email address is required';
}

if (empty($phone)) {
    $errors[] = 'Phone number is required';
}

if ($guests <= 0 || !in_array($guests, [2, 8, 20])) {
    $errors[] = 'Valid number of guests is required';
}

if (empty($checkin) || empty($checkout)) {
    $errors[] = 'Check-in and check-out dates are required';
}

if (empty($room)) {
    $errors[] = 'Room selection is required';
}

if ($price <= 0) {
    $errors[] = 'Invalid price calculation';
}

// Validate dates
if (!empty($checkin) && !empty($checkout)) {
    $checkinDate = new DateTime($checkin);
    $checkoutDate = new DateTime($checkout);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($checkinDate < $today) {
        $errors[] = 'Check-in date cannot be in the past';
    }
    
    if ($checkoutDate <= $checkinDate) {
        $errors[] = 'Check-out date must be after check-in date';
    }
}

// If there are errors, redirect back with error message
if (!empty($errors)) {
    $errorMessage = implode(', ', $errors);
    header('Location: booking.php?error=' . urlencode($errorMessage));
    exit();
}

try {
    $conn = getDBConnection();
    
    // Prepare the insert statement
    $sql = "INSERT INTO reservations (user_id, guest_name, email, phone, checkin_date, checkout_date, room_type, guests, price, options, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    // Combine special requests with options
    $optionsData = [];
    if (!empty($options)) {
        $optionsData = json_decode($options, true) ?? [];
    }
    if (!empty($specialRequests)) {
        $optionsData['special_requests'] = $specialRequests;
    }
    $optionsJson = json_encode($optionsData);
    
    // Get user ID if logged in, otherwise use NULL for guest bookings
    $userId = isLoggedIn() ? getUserId() : null;
    
    // Bind parameters - MySQL handles NULL properly with 'i' type
    $stmt->bind_param("isssssidss", $userId, $name, $email, $phone, $checkin, $checkout, $room, $guests, $price, $optionsJson);
    
    if ($stmt->execute()) {
        $reservationId = $conn->insert_id;
        $stmt->close();
        $conn->close();
        
        // Redirect to payment page
        header('Location: payment/payment.php?reservation_id=' . $reservationId);
        exit();
    } else {
        throw new Exception("Database execution failed: " . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Booking process error: " . $e->getMessage());
    header('Location: booking.php?error=Database error, please try again');
    exit();
}
?>