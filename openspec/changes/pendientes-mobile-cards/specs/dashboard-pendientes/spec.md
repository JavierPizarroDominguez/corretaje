# Delta for Dashboard Pendientes

## ADDED Requirements

### Requirement: Mobile Card Layout for Pendientes Table

The dashboard pendientes table SHALL render as stacked cards on viewports ≤575.98px using the `.table-card-mobile` pattern. Each table row becomes a card with column headers displayed as `data-label` prefixes per cell. Desktop layout (≥576px) MUST remain unchanged.

#### Scenario: Table renders as cards on mobile viewport

- GIVEN a user views the dashboard on a viewport ≤575.98px
- WHEN the pendientes table loads
- THEN each row renders as a stacked card
- AND each cell displays its column header as a `data-label` prefix

#### Scenario: Desktop layout is unchanged

- GIVEN a user views the dashboard on a viewport ≥576px
- WHEN the pendientes table loads
- THEN the table renders with standard horizontal columns
- AND no card-mode styles are applied

#### Scenario: Dynamically inserted rows inherit card layout

- GIVEN the table is in card mode on mobile
- WHEN `cargarPendientes()` inserts new rows via the API
- THEN the MutationObserver assigns `data-label` attributes to new cells
- AND new rows render as cards without additional JS

### Requirement: Cobro Button List Rendering in Card Cells

Cobro button lists within card cells SHALL remain vertically stacked, fully clickable, and color-coded. Button text MUST NOT be truncated or hidden by card overflow rules.

#### Scenario: Buttons are visible and clickable inside cards

- GIVEN a pendientes card on mobile with multiple cobro buttons in a cell
- WHEN the user views the card
- THEN all buttons are visible and stacked vertically
- AND each button retains its color-coded background

#### Scenario: Long address text wraps inside card

- GIVEN a card cell with a long property address
- WHEN the card renders on mobile
- THEN the address text wraps to multiple lines
- AND `text-nowrap` is overridden to `white-space: normal`

### Requirement: Pagination Visibility in Card Mode

The `<tfoot>` pagination controls SHALL remain visible and functional when the table is in card mode. Pagination MUST NOT be hidden by card-mode `thead`/`tfoot` suppression rules.

#### Scenario: Pagination is accessible on mobile

- GIVEN the pendientes table has multiple pages
- WHEN the user views the table on mobile (card mode)
- THEN the pagination controls in `<tfoot>` are visible
- AND the user can navigate between pages

#### Scenario: Pagination stacks vertically on narrow screens

- GIVEN the viewport is ≤575.98px
- WHEN pagination renders
- THEN pagination controls stack vertically if needed
- AND all controls remain tappable (minimum 44px touch target)
