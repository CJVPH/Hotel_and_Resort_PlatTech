## 1. Database

- [ ] 1.1 Add `pavilion_event_prices` table to `database_setup.sql` with columns: `id`, `event_type` (VARCHAR unique), `base_price` (DECIMAL)
- [ ] 1.2 Add seed `INSERT IGNORE` statements for all 7 event types with default base prices
- [ ] 1.3 Run the migration on the local DB (or append ALTER/CREATE to `database_setup.sql`)

## 2. Shared Pricing Logic

- [ ] 2.1 Create `includes/pavilion_pricing.php` with `calculatePavilionPrice($eventType, $guests, $conn)` function that reads base price from DB and applies the formula
- [ ] 2.2 Add fallback hardcoded base prices in the function for when DB lookup fails

## 3. Booking Form — Live Price Preview

- [ ] 3.1 In `booking.php` pavilion panel, make event type a required field that triggers price recalculation
- [ ] 3.2 Add JS `calcPvPrice(eventType, guests)` function mirroring the formula with hardcoded defaults
- [ ] 3.3 Wire `pvType` and `pvPax` input events to call `calcPvPrice` and update the price summary
- [ ] 3.4 Hide price summary until both event type and guest count are filled; show prompt otherwise
- [ ] 3.5 Display computed price in the "Complete Booking" button label and price summary row

## 4. Server-Side Booking Handler

- [ ] 4.1 In `pavilion-booking.php`, replace flat `site_settings` price lookup with `calculatePavilionPrice()` call
- [ ] 4.2 Ensure submitted price from client is ignored — always use server-computed value
- [ ] 4.3 Validate that event type is present and recognized before accepting booking

## 5. Admin Dashboard — Pricing Config

- [ ] 5.1 In `admin/pavilion_dashboard.php`, add a "Pricing" tab to the actions panel
- [ ] 5.2 Render a form listing all 7 event types with their current base prices as editable inputs
- [ ] 5.3 Add `set_event_prices` AJAX action that validates (price > 0) and upserts into `pavilion_event_prices`
- [ ] 5.4 Update manual booking form to show computed price preview using the same formula

## 6. Admin Dashboard — Manual Booking Price

- [ ] 6.1 Add JS price preview to the manual booking form in the admin dashboard (event type + pax → computed price shown read-only)

## 7. Verification

- [ ] 7.1 Test Wedding 50 guests → ₱85,000
- [ ] 7.2 Test Birthday 12 guests → ₱29,000 (rounding)
- [ ] 7.3 Test that submitting a tampered price is overridden server-side
- [ ] 7.4 Test admin price update persists and affects next booking
- [ ] 7.5 Test price summary hides when event type or guest count is missing
