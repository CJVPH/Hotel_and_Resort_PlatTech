<?php
/**
 * Pavilion Dynamic Pricing
 * Formula: Total = Base Price + (ceil(guests / 10) * 7000)
 */

// Default base prices (used if DB table not yet migrated)
const PAVILION_DEFAULT_PRICES = [
    'Wedding'         => 50000,
    'Corporate Event' => 40000,
    'Anniversary'     => 25000,
    'Family Reunion'  => 25000,
    'Birthday Party'  => 15000,
    'Graduation'      => 15000,
    'Other'           => 15000,
];

const PAVILION_GUEST_SURCHARGE_PER_10 = 7000;

/**
 * Calculate pavilion booking price.
 *
 * @param string $eventType  Event type string (must match a key in pavilion_event_prices)
 * @param int    $guests     Number of guests
 * @param mysqli $conn       Optional DB connection; if null, uses defaults
 * @return array ['base' => float, 'surcharge' => float, 'total' => float]
 */
function calculatePavilionPrice(string $eventType, int $guests, $conn = null): array {
    $base = PAVILION_DEFAULT_PRICES[$eventType] ?? PAVILION_DEFAULT_PRICES['Other'];

    if ($conn) {
        $stmt = $conn->prepare("SELECT base_price FROM pavilion_event_prices WHERE event_type = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $eventType);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $base = floatval($res->fetch_assoc()['base_price']);
            }
            $stmt->close();
        }
    }

    $surcharge = (int) ceil($guests / 10) * PAVILION_GUEST_SURCHARGE_PER_10;
    $total     = $base + $surcharge;

    return [
        'base'      => $base,
        'surcharge' => $surcharge,
        'total'     => $total,
    ];
}

/**
 * Get all event type base prices from DB (or defaults).
 *
 * @param mysqli $conn
 * @return array  ['EventType' => base_price, ...]
 */
function getPavilionEventPrices($conn): array {
    $prices = PAVILION_DEFAULT_PRICES;
    $res = $conn->query("SELECT event_type, base_price FROM pavilion_event_prices");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $prices[$r['event_type']] = floatval($r['base_price']);
        }
    }
    return $prices;
}
