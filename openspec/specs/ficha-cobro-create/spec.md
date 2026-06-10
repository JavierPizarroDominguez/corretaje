# Ficha Cobro Create — Specification

## Purpose

Context-aware cobro creation from cliente and propiedad detail pages. Restricts deudor/acreedor to active-contract participants, limits cobro types to manual entries (Reparación, Devolución, Extra), hides fecha_cobro/estado with server defaults, applies CLP formatting on monto, and enforces required monto/detalle/deudor/acreedor.

## Requirements

### Requirement: Context-aware modal entry

The system MUST provide two ficha entry points (cliente detail, propiedad detail) that adapt the cobro create modal's field behavior, options, and validation based on the caller context.

| Scenario | Precondition | Action | Outcome |
|----------|-------------|--------|---------|
| Cliente "Agregar Cobro" | Cliente ficha with active contracts | User clicks button in pendientes | Modal opens; deudor/acreedor as `<select>` with "Seleccione" placeholder, options from active-contract participants; property derived from `contratosVigentes` (exactly one → locked hidden, multiple → select) |
| Propiedad "Agregar Cobro" | Propiedad ficha with active contracts | User clicks button in pendientes | Modal opens; current property locked as hidden input; deudor/acreedor options limited to property's active-contract participants |

### Requirement: Restricted cobro type list

The `tipo` `<select>` MUST show only `Reparación`, `Devolución`, and `Extra` in ficha context.

#### Scenario: Manual-only type options

- GIVEN the ficha cobro modal is open
- WHEN the user opens the tipo dropdown
- THEN only "Reparación", "Devolución", "Extra" are visible

### Requirement: Hidden date/status with server defaults

`fecha_cobro` and `estado` MUST be hidden (no visible fields) in ficha context. The server MUST default `fecha_cobro` to current datetime and `estado` to `Pendiente` when these fields are absent from the request.

#### Scenario: Store assigns safe defaults

- GIVEN a ficha cobro POST with fecha_cobro and estado omitted
- WHEN `CobroController::store()` processes the request
- THEN the controller applies `fecha_cobro = now()` and `estado = Pendiente` before validation
- AND the cobro is created with those defaults

### Requirement: Required monto and detalle

Both fields MUST be required: client-side via HTML `required` attribute, server-side via validation rules. Missing or empty values MUST return HTTP 422.

#### Scenario: Empty monto rejected

- GIVEN the ficha cobro modal is open
- WHEN the user submits without monto
- THEN client-side validation prevents submission
- AND a server POST with empty monto returns 422

#### Scenario: Empty detalle rejected

- GIVEN the ficha cobro modal is open
- WHEN the user submits without detalle
- THEN client-side validation prevents submission
- AND a server POST with empty detalle returns 422

### Requirement: CLP currency formatting

`monto` MUST use `handleCLPInput` (existing utility) to display typed numbers as `$xxx.xxx` CLP format. `stripCLP` MUST run in the form submit handler before the value reaches the server.

#### Scenario: Formatting applied and stripped on submit

- GIVEN the ficha cobro modal is open
- WHEN the user types "150000" in the monto field
- THEN the field displays "$150.000"
- WHEN the form is submitted
- THEN the payload contains `monto: "150000"` (unformatted)

### Requirement: Required deudor and acreedor constrained to contract participants

Both fields MUST be `<select>` elements with a "Seleccione" placeholder as the default unselected option, marked `required`. Options MUST be populated only from participants of the active contracts (cliente context) or the property's active contracts (propiedad context).

#### Scenario: Valid participant selection accepted

- GIVEN the ficha cobro modal with contract participants loaded
- WHEN the user selects valid participants for both deudor and acreedor
- AND submits with valid monto and detalle
- THEN the cobro is created successfully

#### Scenario: Unselected deudor rejected

- GIVEN the ficha cobro modal is open
- WHEN the user submits with deudor on "Seleccione"
- THEN client-side validation prevents submission
- AND a server POST with missing deudor returns 422

### Requirement: Loading indicators on all fetches

Every `fetch()` in the ficha cobro flow MUST call `showElLoading(container[, colspan])` before the request and `hideElLoading(container)` after success or error.

#### Scenario: Relationship fetch shows spinner

- GIVEN the user opens the ficha cobro modal
- WHEN the relationship data fetch begins
- THEN `showElLoading(modalBody)` is called
- WHEN fetch completes (any status)
- THEN `hideElLoading(modalBody)` is called

### Requirement: Errors via flashModal only

All user-facing error messages MUST use Bootstrap `flashModal`. Native browser dialogs (`alert`, `confirm`, `prompt`) are strictly forbidden.

#### Scenario: Validation error shown in flashModal

- GIVEN the user submits the ficha cobro form
- WHEN the server returns a 422 validation error
- THEN a flashModal displays the error message
- AND no `alert()` is called

#### Scenario: Network error shown in flashModal

- GIVEN a fetch is in progress from the ficha cobro modal
- WHEN the network request fails
- THEN a flashModal displays a generic error message
- AND no `alert()` is called
