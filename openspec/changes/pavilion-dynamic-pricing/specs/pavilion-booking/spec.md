## MODIFIED Requirements

### Requirement: Event type is required for booking
The system SHALL require event type selection before the price summary is shown or the booking can be submitted.

#### Scenario: User skips event type
- **WHEN** user attempts to submit the pavilion booking form without selecting an event type
- **THEN** the system SHALL block submission and display "Please select an event type"

#### Scenario: User selects event type
- **WHEN** user selects a valid event type from the dropdown
- **THEN** the price summary section SHALL become visible and show the computed price based on current guest count

### Requirement: Price field is read-only and computed
The system SHALL display the pavilion booking price as a read-only computed value, not an editable input.

#### Scenario: Price updates on input change
- **WHEN** user changes event type or guest count
- **THEN** the displayed price SHALL update immediately using the pricing formula

#### Scenario: Price is locked at submission
- **WHEN** user submits the booking form
- **THEN** the price stored in the database SHALL be the server-computed value, not any client-side value
