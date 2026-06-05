# Mobile Cobro Navigation Specification

## Purpose

Provide reliable back navigation from cobro detail view using an explicit `?from=` query parameter instead of `history.back()`.

## Requirements

### Requirement: Smart back button with from parameter

The system MUST render a back button on /cobro/{id} that navigates to the URL specified by the `?from=` query parameter. If `?from=` is absent, the button MUST navigate to the default /cobro index.

#### Scenario: Back button returns to specified origin

- GIVEN user navigates to /cobro/5?from=/dashboard
- WHEN user clicks back button
- THEN browser navigates to /dashboard

#### Scenario: Back button defaults to cobro index when no from param

- GIVEN user navigates to /cobro/5 (no ?from= parameter)
- WHEN user clicks back button
- THEN browser navigates to /cobro

#### Scenario: From parameter is URL-encoded

- GIVEN user navigates to /cobro/5?from=%2Fcliente%2Fficha%2F10
- WHEN user clicks back button
- THEN browser navigates to /cliente/ficha/10

#### Scenario: Malicious from parameter is rejected

- GIVEN user navigates to /cobro/5?from=https://external-site.com
- WHEN user clicks back button
- THEN browser navigates to /cobro (external URLs are rejected, default used)
