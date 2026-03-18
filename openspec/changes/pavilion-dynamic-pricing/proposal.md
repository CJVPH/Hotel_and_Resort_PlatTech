## Why

The pavilion booking form currently charges a flat price for all events regardless of event type or guest count. This means a 10-person birthday party costs the same as a 300-person wedding, which is neither fair nor commercially sensible. Dynamic pricing based on event type and guest count is needed to reflect actual resource usage and market expectations.

## What Changes

- Remove the single flat `pavilion_price` setting from `site_settings`
- Introduce base prices per event type (Wedding, Corporate Event, Anniversary/Family Reunion, Birthday Party/Graduation)
- Add guest-count surcharge: every group of 10 guests (rounded up) adds ₱7,000 to the total
- Display the calculated price live on the booking form as the user selects event type and enters guest count
- Store the computed price in `pavilion_bookings.price` at booking time
- Update admin dashboard to show the pricing formula and allow base price overrides per event type

## Capabilities

### New Capabilities

- `pavilion-pricing`: Calculates total pavilion cost using base price (by event type) + ⌈guests/10⌉ × ₱7,000. Exposed as a shared PHP function and mirrored in JS for live preview.

### Modified Capabilities

- `pavilion-booking`: Booking form now requires event type selection before price can be calculated; price field becomes read-only and computed dynamically.

## Impact

- `booking.php` — pavilion panel JS updated for live price calculation
- `pavilion-booking.php` — server-side price calculation replaces flat `site_settings` lookup
- `admin/pavilion_dashboard.php` — pricing config panel updated; manual booking form shows computed price
- `payment/pavilion_payment.php` — price displayed comes from `pavilion_bookings.price` (no change needed if already reading from DB)
- `database_setup.sql` — new `pavilion_event_prices` table for per-event-type base prices; migration removes dependency on `site_settings` for pavilion price
