# Mobile Table Cards Specification

## Purpose

Convert ficha detail-page tables to vertical card layout on mobile (≤991.98px) using existing `.table-card-mobile` CSS and `labelTable()` JS, eliminating horizontal scroll on detail views.

## Requirements

### Requirement: Ficha tables render as cards on mobile

The system MUST apply `.table-card-mobile` class to all `<table>` elements within ficha detail views (cliente.blade.php, propiedad.blade.php). The system MUST call `labelTable()` on these tables at DOMContentLoaded to generate `data-label` attributes for card layout.

#### Scenario: Ficha table displays as vertical cards on mobile

- GIVEN viewport width is 375px and user views /cliente/ficha/{id}
- WHEN page renders
- THEN each table row is displayed as a vertical card with column headers as labels
- AND no horizontal scroll is required

#### Scenario: Index tables are NOT converted to cards

- GIVEN viewport width is 375px and user views /cliente (index)
- WHEN page renders
- THEN table remains horizontal with scroll (no `.table-card-mobile` class applied)

#### Scenario: Desktop ficha tables unchanged

- GIVEN viewport width is 1200px and user views /cliente/ficha/{id}
- WHEN page renders
- THEN table renders in standard horizontal layout (card styles not applied)

#### Scenario: labelTable() auto-applied to marked tables

- GIVEN a table has class `.table-card-mobile`
- WHEN DOMContentLoaded fires
- THEN `labelTable()` is called on that table automatically by app.js

### Requirement: Non-essential columns hidden on mobile

The system MUST hide non-essential columns on mobile (≤991.98px) using `.d-none.d-sm-table-cell` to prevent excessively tall card stacks on tables with 12+ columns.

#### Scenario: Wide ficha table hides secondary columns on mobile

- GIVEN a ficha table with 12+ columns and viewport is 375px
- WHEN page renders
- THEN columns marked `.d-none.d-sm-table-cell` are hidden
- AND card stack height is reduced
