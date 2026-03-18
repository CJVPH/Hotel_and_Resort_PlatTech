<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$roomNumber = $_GET['room_number'] ?? '';
$roomType   = $_GET['room_type']   ?? '';

if (empty($roomNumber) || empty($roomType)) {
    echo json_encode(['success' => false, 'error' => 'room_number and room_type are required']);
    exit();
}

try {
    $conn = getDBConnection();

    // Match any reservation for this specific room number stored in options JSON
    $roomNumPattern = '%"room_number":"' . $conn->real_escape_string($roomNumber) . '"%';
    $sql = "SELECT checkin_date, checkout_date
            FROM reservations
            WHERE status IN ('confirmed', 'pending')
              AND options LIKE ?
            ORDER BY checkin_date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $roomNumPattern);
    $stmt->execute();
    $result = $stmt->get_result();

    $bookedRanges = [];
    while ($row = $result->fetch_assoc()) {
        $bookedRanges[] = [
            'from' => $row['checkin_date'],
            'to'   => $row['checkout_date']
        ];
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success'      => true,
        'room_number'  => $roomNumber,
        'room_type'    => $roomType,
        'booked_dates' => $bookedRanges
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
}
?>
