# Cliente Reparaciones Page Specification

## Purpose

Dedicated page at `cliente/{id}/reparaciones` that displays reparaciones (paginated) and cartola (full) for a single client, extracted from the monolithic ficha page.

## Requirements

### Requirement: Navigation to Reparaciones Page

The system MUST provide a navigation button on the main ficha page that links to the reparaciones page for the current client.

#### Scenario: User navigates from ficha to reparaciones

- GIVEN user is viewing ficha page for client with ID 5
- WHEN user clicks "Reparaciones y Cartola" button
- THEN browser navigates to `/cliente/5/reparaciones`
- AND page renders with reparaciones table and cartola section

#### Scenario: Reparaciones button uses named route

- GIVEN the route is defined as a named route
- WHEN the button href is generated via `route()` helper
- THEN URL is correctly formed regardless of route prefix changes

### Requirement: Reparaciones Query with Pagination

The system MUST query cobros filtered by client participation and tipo IN ('Reparación', 'Devolución', 'Extra'), ordered by fecha_cobro descending, paginated at 20 per page using default `page` parameter.

#### Scenario: Reparaciones loads with data

- GIVEN client has 25 reparaciones cobros
- WHEN user visits reparaciones page
- THEN first 20 reparaciones are displayed
- AND pagination links show page 2

#### Scenario: Reparaciones empty state

- GIVEN client has no reparaciones cobros
- WHEN user visits reparaciones page
- THEN alert message "No se han registrado reparaciones ni gastos extra." is displayed
- AND no pagination links are shown

#### Scenario: Pagination uses default page param

- GIVEN user clicks page 2 link
- WHEN request is made
- THEN query parameter is `?page=2` (not `?reparaciones_page=2`)

### Requirement: Cartola Data Display

The system MUST display the cartola (renta ingresos/egresos + servicios) grouped by unidad, year, and month, identical to the current inline version.

#### Scenario: Cartola renders with data

- GIVEN client has cobros matching cartola filters
- WHEN reparaciones page loads
- THEN cartola is grouped by unidad > year > month
- AND columns show only types that have data

#### Scenario: Cartola empty state

- GIVEN client has no cartola-matching cobros
- WHEN reparaciones page loads
- THEN cartola section is not rendered (component guards with `@if(!empty($cartola))`)

### Requirement: Component Reuse

The system MUST reuse existing `reparaciones-propiedad` and `cartola` Blade components via `@include` with identical variable contracts.

#### Scenario: Reparaciones component receives correct data

- GIVEN controller passes `$reparaciones` as LengthAwarePaginator
- WHEN `@include('components.reparaciones-propiedad')` renders
- THEN component displays table with pagination links

#### Scenario: Cartola component receives correct data

- GIVEN controller passes `$cartola` array and `$columnasCartola` array
- WHEN `@include('components.cartola')` renders
- THEN component displays grouped table with correct columns

### Requirement: Client Context and Navigation Back

The system MUST load the client record and provide a way to navigate back to the main ficha page.

#### Scenario: Client not found

- GIVEN requested client ID does not exist
- WHEN user visits reparaciones page
- THEN HTTP 404 is returned (findOrFail)

#### Scenario: Back to ficha

- GIVEN user is on reparaciones page
- WHEN user clicks "Volver a ficha" button
- THEN browser navigates to `/cliente/{id}`
