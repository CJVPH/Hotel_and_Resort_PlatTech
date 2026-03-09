<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Get availability for specific dates and room type
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';
$roomType = $_GET['room_type'] ?? '';
$guests = intval($_GET['guests'] ?? 0);

if (empty($checkin) || empty($checkout)) {
    echo json_encode([
        'success' => false,
        'error' => 'Check-in and check-out dates are required'
    ]);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Get all reservations that overlap with the requested dates
    $sql = "SELECT room_type, COUNT(*) as booked_rooms 
            FROM reservations 
            WHERE status IN ('confirmed', 'pending') 
            AND (
                (checkin_date <= ? AND checkout_date > ?) OR
                (checkin_date < ? AND checkout_date >= ?) OR
                (checkin_date >= ? AND checkout_date <= ?)
            )";
    
    $params = [$checkin, $checkin, $checkout, $checkout, $checkin, $checkout];
    
    if (!empty($roomType)) {
        $sql .= " AND room_type = ?";
        $params[] = $roomType;
    }
    
    $sql .= " GROUP BY room_type";
    
    $stmt = $conn->prepare($sql);
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookedRooms = [];
    while ($row = $result->fetch_assoc()) {
        $bookedRooms[$row['room_type']] = intval($row['booked_rooms']);
    }
    
    // Total available rooms per type
    $totalRooms = [
        'Regular' => 6,
        'Deluxe' => 6,
        'VIP' => 6
    ];
    
    $availability = [];
    foreach ($totalRooms as $type => $total) {
        $booked = $bookedRooms[$type] ?? 0;
        $available = $total - $booked;
        
        $availability[$type] = [
            'total' => $total,
            'booked' => $booked,
            'available' => max(0, $available),
            'is_available' => $available > 0
        ];
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'checkin' => $checkin,
        'checkout' => $checkout,
        'availability' => $availability
    ]);
    
} catch (Exception $e) {
    error_log("Availability check error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to check availability'
    ]);
}
?>