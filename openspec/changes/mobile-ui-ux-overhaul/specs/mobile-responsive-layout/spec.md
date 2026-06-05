# Mobile Responsive Layout Specification

## Purpose

Ensure the app is usable on mobile phones (≤991.98px) by scaling fonts, preventing toggle button overlap, and adjusting content spacing — without redesigning the desktop layout.

## Requirements

### Requirement: Mobile font scaling

The system MUST increase body font size to ≥16px on viewports ≤991.98px. Headings (h1-h6) MUST scale proportionally on viewports ≤575.98px to remain readable.

#### Scenario: Body text readable on tablet-sized viewport

- GIVEN viewport width is 768px
- WHEN page renders
- THEN body font size is ≥16px

#### Scenario: Headings scale on small phones

- GIVEN viewport width is 375px
- WHEN page renders
- THEN h1-h6 font sizes are reduced proportionally but remain ≥14px

#### Scenario: Desktop fonts unchanged

- GIVEN viewport width is 1200px
- WHEN page renders
- THEN body font size remains at original 14px (no mobile override applied)

### Requirement: Main content padding below mobile toggle

The system MUST add sufficient top padding to `<main>` content on viewports ≤991.98px so that page titles are not obscured by the fixed mobile toggle button.

#### Scenario: Page title fully visible on mobile

- GIVEN viewport width is 375px and mobile toggle button is visible
- WHEN user views any page
- THEN the page title is fully visible below the toggle button with no overlap

#### Scenario: No extra padding on desktop

- GIVEN viewport width is 1024px
- WHEN user views any page
- THEN `<main>` top padding is unchanged from desktop default

### Requirement: Index tables keep horizontal scroll

The system MUST NOT alter horizontal scroll behavior for index tables on mobile. Index tables MUST remain horizontally scrollable — only ficha detail tables convert to cards.

#### Scenario: Index table scrolls horizontally on mobile

- GIVEN viewport width is 375px and user is on an index view (e.g., /cliente)
- WHEN table has more columns than fit the viewport
- THEN user can scroll horizontally to see all columns
