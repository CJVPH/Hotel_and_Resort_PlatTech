<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $conn = getDBConnection();
    
    // Get all room prices
    $sql = "SELECT room_type, pax_group, price FROM room_prices ORDER BY room_type, pax_group";
    $result = $conn->query($sql);
    
    $prices = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $roomType = $row['room_type'];
            $paxGroup = intval($row['pax_group']);
            $price = floatval($row['price']);
            
            if (!isset($prices[$roomType])) {
                $prices[$roomType] = [];
            }
            
            $prices[$roomType][$paxGroup] = $price;
        }
    }
    
    $conn->close();
    
    // If no prices found, return default prices
    if (empty($prices)) {
        $prices = [
            'Regular' => [2 => 1500, 8 => 3000, 20 => 6000],
            'Deluxe' => [2 => 2500, 8 => 4500, 20 => 8500],
            'VIP' => [2 => 4000, 8 => 7000, 20 => 12000]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'prices' => $prices
    ]);
    
} catch (Exception $e) {
    error_log("Get room prices error: " . $e->getMessage());
    
    // Return default prices on error
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch room prices',
        'prices' => [
            'Regular' => [2 => 1500, 8 => 3000, 20 => 6000],
            'Deluxe' => [2 => 2500, 8 => 4500, 20 => 8500],
            'VIP' => [2 => 4000, 8 => 7000, 20 => 12000]
        ]
    ]);
}
?>