## ADDED Requirements

### Requirement: Price formula calculation
The system SHALL calculate pavilion booking price as `Base Price + (⌈guests / 10⌉ × 7000)` where Base Price is determined by event type.

Base prices:
- Wedding: ₱50,000
- Corporate Event: ₱40,000
- Anniversary: ₱25,000
- Family Reunion: ₱25,000
- Birthday Party: ₱15,000
- Graduation: ₱15,000
- Other: ₱15,000

#### Scenario: Wedding with 50 guests
- **WHEN** event type is "Wedding" and guest count is 50
- **THEN** total price SHALL be ₱50,000 + (5 × ₱7,000) = ₱85,000

#### Scenario: Birthday with 12 guests rounds up
- **WHEN** event type is "Birthday Party" and guest count is 12
- **THEN** guest groups = ⌈12/10⌉ = 2, total price SHALL be ₱15,000 + (2 × ₱7,000) = ₱29,000

#### Scenario: Corporate with exactly 10 guests
- **WHEN** event type is "Corporate Event" and guest count is 10
- **THEN** guest groups = ⌈10/10⌉ = 1, total price SHALL be ₱40,000 + (1 × ₱7,000) = ₱47,000

#### Scenario: Anniversary with 1 guest
- **WHEN** event type is "Anniversary" and guest count is 1
- **THEN** guest groups = ⌈1/10⌉ = 1, total price SHALL be ₱25,000 + (1 × ₱7,000) = ₱32,000

### Requirement: Server-side price recalculation
The system SHALL recalculate the price server-side at booking submission using the formula, ignoring any client-submitted price value.

#### Scenario: Client submits manipulated price
- **WHEN** a booking POST is received with a tampered price field
- **THEN** the system SHALL discard the submitted price and compute it from event type and guest count

### Requirement: Live price preview on booking form
The system SHALL display a live price calculation on the pavilion booking form that updates whenever event type or guest count changes.

#### Scenario: User selects event type and enters guests
- **WHEN** user selects an event type and enters a guest count ≥ 1
- **THEN** the price summary SHALL update immediately showing the computed total

#### Scenario: Incomplete inputs
- **WHEN** event type is not selected OR guest count is empty
- **THEN** the price summary SHALL show a prompt to complete both fields, not ₱0

### Requirement: Admin-configurable base prices
The system SHALL allow admins to update base prices per event type via the admin dashboard without code changes.

#### Scenario: Admin updates Wedding base price
- **WHEN** admin submits a new base price for "Wedding" in the dashboard
- **THEN** the system SHALL persist the new price and use it for all subsequent bookings

#### Scenario: Admin sets price to zero
- **WHEN** admin submits a base price of 0 or negative
- **THEN** the system SHALL reject the input with a validation error
