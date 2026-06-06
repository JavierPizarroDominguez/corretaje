# Mobile Responsive Layout Specification

## Purpose

Ensure all pages have comfortable horizontal padding on mobile viewports (<=575.98px) so content does not touch viewport edges, and define the responsive behavior boundary where desktop layout takes over.

## Requirements

### Requirement: Mobile Horizontal Padding

The system MUST apply horizontal padding of at least 1rem to the `.content` container on viewports <=575.98px. This rule SHALL affect all pages that extend the `layouts/app.blade.php` layout, including ficha pages (propiedad, cliente), dashboard, and index pages.

#### Scenario: Ficha page on mobile viewport

- GIVEN a user navigates to a ficha page (propiedad or cliente) on a viewport <=575.98px wide
- WHEN the page renders
- THEN the `.content` container has `padding-left: 1rem` and `padding-right: 1rem`
- AND no content touches the left or right viewport edge

#### Scenario: Dashboard page on mobile viewport

- GIVEN the dashboard page loads on a viewport <=575.98px wide
- WHEN the page renders
- THEN the existing `container-fluid` gutters are preserved
- AND the `.content` padding rule does not cause double-padding or layout breakage

#### Scenario: Desktop viewport unchanged

- GIVEN any page renders on a viewport >=576px wide
- WHEN the page renders
- THEN the `.content` container uses its default `margin-left: 240px` sidebar offset
- AND no mobile padding rule is applied

### Requirement: Mobile Padding Specificity

The mobile padding rule MUST NOT override or conflict with the sidebar collapse states (`.content.full`) or the mobile sidebar behavior (<=991.98px) where `.content` already has `margin-left: 0 !important`.

#### Scenario: Collapsed sidebar on desktop

- GIVEN the sidebar is collapsed (`.sidebar.collapsed`) on a viewport >=992px
- WHEN the page renders
- THEN `.content.full` retains `margin-left: 60px`
- AND the mobile padding rule does not apply (viewport > 575.98px)

#### Scenario: Mobile sidebar open

- GIVEN the sidebar is open on mobile (`.sidebar.mobile-show`) at <=575.98px
- WHEN the page renders
- THEN the content area has horizontal padding of 1rem
- AND the sidebar overlay behavior is unaffected
