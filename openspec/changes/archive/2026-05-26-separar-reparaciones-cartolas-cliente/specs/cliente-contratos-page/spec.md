# Cliente Contratos Page Specification

## Purpose

Dedicated page at `cliente/{id}/contratos` that displays contratos vigentes for a single client, extracted from the monolithic ficha page.

## Requirements

### Requirement: Navigation to Contratos Page

The system MUST provide a navigation button on the main ficha page that links to the contratos page for the current client.

#### Scenario: User navigates from ficha to contratos

- GIVEN user is viewing ficha page for client with ID 5
- WHEN user clicks "Contratos Vigentes" button
- THEN browser navigates to `/cliente/5/contratos`
- AND page renders with contratos list

#### Scenario: Contratos button uses named route

- GIVEN the route is defined as a named route
- WHEN the button href is generated via `route()` helper
- THEN URL is correctly formed regardless of route prefix changes

### Requirement: Contratos Vigentes Query

The system MUST query contratos where the client participates (via participante_contratos), that are currently active (fecha_termino IS NULL OR fecha_termino > now), ordered by fecha_inicio descending.

#### Scenario: Contratos loads with active contracts

- GIVEN client participates in 3 active contratos
- WHEN user visits contratos page
- THEN all 3 contratos are displayed as cards
- AND each card shows renta, garantia, dates, and parties

#### Scenario: Contratos empty state

- GIVEN client has no active contratos
- WHEN user visits contratos page
- THEN page renders with empty state message
- AND no contract cards are displayed

#### Scenario: Expired contracts excluded

- GIVEN client has 2 active and 1 expired contrato (fecha_termino in past)
- WHEN user visits contratos page
- THEN only 2 active contratos are displayed
- AND expired contrato is not shown

### Requirement: Component Reuse

The system MUST reuse existing `contratos` Blade component via `@include` with identical variable contract (`$contratosVigentes` as collection).

#### Scenario: Contratos component receives correct data

- GIVEN controller passes `$contratosVigentes` as collection of Contrato models
- WHEN `@include('components.contratos')` renders
- THEN each contrato displays as a card with property address, renta, garantia, dates, and linked parties

#### Scenario: Contract party links work

- GIVEN contrato has arrendador and arrendatario
- WHEN contrato card renders
- THEN party names link to their respective ficha pages (`/cliente/{id}`)

### Requirement: Client Context and Navigation Back

The system MUST load the client record and provide a way to navigate back to the main ficha page.

#### Scenario: Client not found

- GIVEN requested client ID does not exist
- WHEN user visits contratos page
- THEN HTTP 404 is returned (findOrFail)

#### Scenario: Back to ficha

- GIVEN user is on contratos page
- WHEN user clicks "Volver a ficha" button
- THEN browser navigates to `/cliente/{id}`

### Requirement: Base Query Extraction

The system MUST extract the shared `$baseQuery` (Cobro query with eager loads and client filter) into a private helper method on FichaClienteController to avoid duplication across `show()`, `reparaciones()`, and `cartolas()` methods.

#### Scenario: Base query helper is reusable

- GIVEN private method `baseQuery($clientId)` exists
- WHEN called from `show()`, `reparaciones()`, or `cartolas()`
- THEN identical query builder is returned with correct eager loads and client filter
