# Dashboard Pendientes Specification

## Purpose

Dashboard pendientes view displays pending cobros grouped by property with pagination, computed human-readable concepto labels, and navigation links to ficha detail pages.

---

## Requirements

### Requirement: Paginated property list

The dashboard pendientes view SHALL display 5 properties per page (`POR_PAGINA = 5`) with pagination controls. The API SHALL accept `por_pagina` and `pagina` query parameters and return paginated results.

#### Scenario: Default page size is 5

- GIVEN the dashboard loads
- WHEN the initial API request is made
- THEN `por_pagina=5` is sent
- AND the response contains up to 5 properties in `data`

#### Scenario: Pagination metadata is present

- GIVEN 12 properties with pending cobros exist
- WHEN the API returns page 1 with `por_pagina=5`
- THEN `total=12`, `pagina=1`, `por_pagina=5`, `total_paginas=3`

#### Scenario: Navigate to page 2

- GIVEN the user clicks page 2
- WHEN the API request is made with `pagina=2`
- THEN properties 6-10 are returned

---

### Requirement: Computed concepto display name

Each cobro in the pendientes API response SHALL include a `concepto` field computed from `tipo` and `fecha_cobro` using the rename rules. The view SHALL render `concepto` in cobro button text instead of raw `tipo`.

#### Scenario: Renta ingreso displays "Cobrar renta {mes} {año}"

- GIVEN a cobro with `tipo = "Ingreso Renta Arrendatario"` and `fecha_cobro = "2025-01-15"`
- WHEN concepto is computed
- THEN concepto = "Cobrar renta enero 2025"

#### Scenario: Renta egreso displays "Transferir renta {mes} {año}"

- GIVEN a cobro with `tipo = "Egreso Renta Arrendador"` and `fecha_cobro = "2025-03-01"`
- WHEN concepto is computed
- THEN concepto = "Transferir renta marzo 2025"

#### Scenario: Commission displays "Comisión inicial"

- GIVEN a cobro with `tipo = "Comision inicial arrendador"` or `"Comision inicial arrendatario"`
- WHEN concepto is computed
- THEN concepto = "Comisión inicial" (no date suffix)

#### Scenario: Guarantee displays action + "garantía"

- GIVEN `tipo = "Ingreso Garantía Arrendatario"` → concepto = "Cobrar garantía"
- GIVEN `tipo = "Egreso Garantía Arrendador"` → concepto = "Transferir garantía"

#### Scenario: Service type displays "{tipo} {mes} {año}"

- GIVEN a cobro with `tipo = "Luz"` and `fecha_cobro = "2025-06-10"`
- WHEN concepto is computed
- THEN concepto = "Luz junio 2025"

#### Scenario: Unknown tipo falls back to raw tipo

- GIVEN a cobro with `tipo = "Algun tipo desconocido"`
- WHEN concepto is computed
- THEN concepto = "Algun tipo desconocido"

#### Scenario: Null fecha_cobro falls back to raw tipo

- GIVEN a cobro with `tipo = "Ingreso Renta Arrendatario"` and `fecha_cobro = null`
- WHEN concepto is computed
- THEN concepto = "Ingreso Renta Arrendatario" (no month/year, raw tipo)

---

### Requirement: Property address links to ficha page

The property address link in the dashboard view SHALL navigate to `/propiedad/ficha/{id}` instead of `/propiedad/{id}`.

#### Scenario: Click property address navigates to ficha

- GIVEN a property with `id = 42`
- WHEN the user clicks the address link
- THEN the browser navigates to `/propiedad/ficha/42`

---

### Requirement: Loading indicators on fetch

All `fetch()` calls in the dashboard view SHALL wrap with `showElLoading`/`hideElLoading` per AGENTS.md convention. No native browser dialogs SHALL be used for error display.

#### Scenario: Spinner during pendientes load

- GIVEN the dashboard page loads
- WHEN the fetch for pendientes begins
- THEN `showElLoading(tbody, columnCount)` is called
- AND on success or error, `hideElLoading(tbody)` is called

#### Scenario: Error shown in flashModal

- GIVEN a fetch fails with 500
- WHEN the error handler runs
- THEN `showWizardError` or `flashModal` displays the error
- AND NO `alert()` is called

---

### Requirement: API response includes fecha_cobro and concepto

The pendientes API (`DashboardPendientesController`) SHALL include `fecha_cobro` and `concepto` fields in each cobro object within the `cobroData` arrays.

#### Scenario: Cobro object includes new fields

- GIVEN a cobro exists with `tipo`, `fecha_cobro`, `monto`, `estado`
- WHEN the API returns cobroData
- THEN each cobro object contains: `id`, `estado`, `tipo`, `monto`, `deudor`, `deudor_id`, `acreedor`, `acreedor_id`, `servicio_id`, `fecha_cobro`, `concepto`
