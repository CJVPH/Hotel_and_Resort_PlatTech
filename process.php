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

    // Parse options JSON to extract clean room_type and room_number
    $optionsData = [];
    if (!empty($options)) {
        $optionsData = json_decode($options, true) ?? [];
    }
    if (!empty($specialRequests)) {
        $optionsData['special_requests'] = $specialRequests;
    }
    $optionsJson = json_encode($optionsData);

    // Extract clean room_type (e.g. "Regular", "Deluxe", "VIP") from options JSON
    $roomType   = $optionsData['individual_room']['room_type']   ?? '';
    $roomNumber = $optionsData['individual_room']['room_number'] ?? '';

    // Fallback: parse from $room string if options missing
    if (empty($roomType)) {
        $parts    = explode(' - ', $room);
        $roomType = trim($parts[0] ?? $room);
    }

    // ── CONFLICT CHECK ──────────────────────────────────────────────
    // Always block if room_number is known and dates overlap any pending/confirmed booking.
    // Uses 3 strategies to catch all storage formats in the DB.
    if (!empty($roomNumber)) {
        $roomNumPattern = '%"room_number":"' . $conn->real_escape_string($roomNumber) . '"%';

        // Strategy 1: NEW format — room_type is clean ("Regular") + room_number in options JSON
        // Strategy 2: OLD format — room_type column contains room number as part of string
        // Strategy 3: Any record where options JSON has this room_number (regardless of room_type)
        $conflictSql = "SELECT id FROM reservations
                        WHERE status IN ('confirmed', 'pending')
                          AND checkin_date  < ?
                          AND checkout_date > ?
                          AND options LIKE ?
                        LIMIT 1";
        $cStmt = $conn->prepare($conflictSql);
        $cStmt->bind_param('sss', $checkout, $checkin, $roomNumPattern);
        $cStmt->execute();
        $cResult = $cStmt->get_result();

        if ($cResult->num_rows > 0) {
            $cStmt->close();
            $conn->close();
            header('Location: booking.php?error=' . urlencode(
                "Room {$roomNumber} is already booked for the selected dates. Please choose different dates or another room."
            ));
            exit();
        }
        $cStmt->close();
    }
    // ────────────────────────────────────────────────────────────────

    // Insert reservation using clean room_type
    $sql  = "INSERT INTO reservations (user_id, guest_name, email, phone, checkin_date, checkout_date, room_type, guests, price, options, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }

    $userId = isLoggedIn() ? getUserId() : null;
    $stmt->bind_param("isssssidss", $userId, $name, $email, $phone, $checkin, $checkout, $roomType, $guests, $price, $optionsJson);

    if ($stmt->execute()) {
        $reservationId = $conn->insert_id;
        $stmt->close();
        $conn->close();

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