## Context

The pavilion booking system currently stores a single flat price in `site_settings` (`pavilion_price`) and applies it to every booking regardless of event type or guest count. The booking form shows this static price with no live feedback. The new model introduces a formula-based price that varies by event type and scales with guest count.

Current state:
- `pavilion_bookings.price` is set from `site_settings.pavilion_price` at insert time
- No per-event-type differentiation exists
- Guest count has no effect on price

## Goals / Non-Goals

**Goals:**
- Implement the pricing formula: `Total = Base Price + (⌈guests / 10⌉ × 7000)`
- Base prices by event type: Wedding ₱50,000 · Corporate ₱40,000 · Anniversary/Family Reunion ₱25,000 · Birthday/Graduation ₱15,000
- Live price preview on the booking form (JS) as user selects event type and enters guest count
- Server-side price recalculation at booking submission (never trust client price)
- Admin can override base prices per event type via the dashboard
- Store computed price in `pavilion_bookings.price`

**Non-Goals:**
- Per-date pricing overrides (out of scope)
- Discount codes or promotional pricing
- Multi-currency support
- Changing the payment flow after price is stored

## Decisions

### 1. Shared pricing logic in PHP + mirrored in JS

**Decision**: Implement a `calculatePavilionPrice($eventType, $guests)` PHP function in a shared include, and mirror the same formula in JS for live preview.

**Rationale**: Server-side recalculation prevents price tampering. JS mirror gives instant feedback without a round-trip. The formula is simple enough to safely duplicate.

**Alternative considered**: AJAX call to server for every price update — rejected as adds latency and complexity for a deterministic formula.

### 2. `pavilion_event_prices` table for base prices

**Decision**: Store base prices in a new `pavilion_event_prices` table keyed by event type slug, with a default seed. Admin can update via dashboard.

**Rationale**: Hardcoding prices in PHP makes future changes require a code deploy. A DB table lets admin adjust without touching code.

**Alternative considered**: Keep in `site_settings` as JSON blob — rejected as harder to query and validate per-type.

### 3. Event type is required before price shows

**Decision**: The price summary section stays hidden until both event type and guest count are filled. A placeholder message prompts the user.

**Rationale**: Showing ₱0 or a partial price before inputs are complete is confusing. Requiring both inputs first gives a clean UX.

### 4. Guest count rounding

**Decision**: Use `ceil(guests / 10)` — 1 guest = 1 group, 10 guests = 1 group, 11 guests = 2 groups.

**Rationale**: Matches the stated formula exactly. Implemented as `Math.ceil(guests / 10)` in JS and `ceil($guests / 10)` in PHP.

## Risks / Trade-offs

- **Price drift between JS and PHP** → Mitigation: unit-test the PHP function; JS formula is a one-liner that's easy to audit.
- **Existing bookings have prices from old flat rate** → Mitigation: no migration of historical prices; only new bookings use the formula.
- **Admin sets a base price of 0** → Mitigation: validate `price > 0` in the admin form before saving.

## Migration Plan

1. Run `ALTER TABLE` to add `pavilion_event_prices` table (in `database_setup.sql`)
2. Seed default base prices via `INSERT IGNORE`
3. Deploy code changes — old `site_settings.pavilion_price` fallback remains for any in-flight bookings
4. Admin visits Pavilion dashboard → Pricing tab to confirm/adjust base prices
5. Rollback: revert code; existing `site_settings.pavilion_price` still present as fallback

## Open Questions

- Should "Other" event type use a default base price (e.g., ₱15,000) or prompt admin to set it? → Defaulting to ₱15,000 (same as Birthday/Graduation) for now.
