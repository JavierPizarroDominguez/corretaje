# Ficha Pendientes Mobile Specification

## Purpose

Render cliente and propiedad ficha pending-payment sections with the same responsive dashboard/index contract: desktop tables and mobile cards, centered full-width cobro buttons, group-level pagination, and propiedad unit labeling when applicable.

## Requirements

### Requirement: Mobile Card Badge Rendering

The system MUST render cliente and propiedad ficha pendientes with the dashboard/index pending-payment contract. Mobile viewports MUST present each table row as a card, and each pending cobro MUST appear as a full-width centered button showing `concepto`. Button color SHALL reflect `estado`: `warning` for "Pendiente", `danger` for "Vencido", `info` for "Incompleto". Each button MUST carry serialized cobro data.

#### Scenario: Ficha mobile matches dashboard cards

- GIVEN a cliente ficha has pending cobros
- WHEN it renders on a mobile viewport
- THEN pending rows appear as dashboard/index-style cards
- AND cobro buttons are full-width, centered, and display `concepto`.

#### Scenario: Cobro data remains available

- GIVEN any ficha cobro has nullable relations or date
- WHEN its button is rendered initially or after AJAX refresh
- THEN modal data is valid and missing values do not break rendering.

### Requirement: Mobile Card Visual Style

The system MUST make cliente and propiedad ficha mobile cards visually identical to dashboard/index pending-payment cards, including border, spacing, shadow, centered cell content, and centered small/title-like labels. The dashboard/index visual proposal SHALL be the source of truth when styles conflict.

#### Scenario: Visual parity with index

- GIVEN identical pending-payment data appears on dashboard/index and a ficha
- WHEN viewed on mobile
- THEN card borders, spacing, shadows, labels, and cobro button alignment match.

### Requirement: Cobro Detail Modal

The system MUST preserve ficha cobro detail/payment behavior. Selecting a cobro on desktop or mobile SHALL open `#modalCobro` with tipo, linked deudor/acreedor when available, CLP-formatted monto, fecha text, and "Registrar pago". Payment success or error MUST use Bootstrap modal feedback, not native browser dialogs.

#### Scenario: Selecting cobro opens payment modal

- GIVEN a ficha pending cobro is visible
- WHEN the user selects its cobro button
- THEN `#modalCobro` opens with the required detail fields
- AND the registrar button can submit payment.

### Requirement: Desktop Table Unchanged

The system MUST replace the old ficha desktop layout with the dashboard/index table pattern. Desktop ficha pendientes MUST render one responsive table with a primary descriptor column plus dynamic role columns for arrendador, arrendatario, and corredor. Role cells MUST contain centered full-width cobro buttons showing `concepto`. Cliente fichas use the dashboard-like property descriptor; propiedad fichas MUST use `Unidad` instead of property/direction and MUST show it only when the property has more than one real unit.

#### Scenario: Desktop uses dashboard table

- GIVEN a cliente or propiedad ficha has pending cobros
- WHEN rendered on a desktop viewport
- THEN a dashboard/index-like table is visible
- AND separate mobile-only badge lists are not used.

#### Scenario: Propiedad unidad column is conditional

- GIVEN a propiedad has one real unit
- WHEN its pendientes render
- THEN no `Unidad` column is shown.
- GIVEN the propiedad has more than one real unit
- WHEN its pendientes render
- THEN the first descriptor column is `Unidad`.

### Requirement: Ficha Render Consistency

The system MUST produce equivalent pending-payment structure on initial Blade render and after AJAX refresh following payment or pagination. The refresh MUST preserve visible columns, mobile cards, centered title-like labels, buttons, pagination/empty state behavior, and scoped loading indicators via `showElLoading`/`hideElLoading`.

#### Scenario: AJAX refresh preserves structure

- GIVEN a user registers a payment from a ficha pendiente
- WHEN the ficha refreshes pending cobros through AJAX
- THEN the refreshed DOM has the same table/card contract as initial render
- AND loading and feedback modal conventions remain intact.

### Requirement: Group-Level Pending Pagination

The system MUST paginate pending-payment containers by their parent group, never by individual cobro. Dashboard/index and cliente ficha MUST show at most 3 property groups per page. Propiedad ficha MUST show at most 3 unit groups per page.

#### Scenario: Dashboard paginates properties

- GIVEN dashboard/index has pending cobros grouped under 4 properties
- WHEN the first pending page renders
- THEN at most 3 property groups are visible
- AND no property group is split because it contains multiple cobros.

#### Scenario: Cliente ficha paginates properties

- GIVEN a cliente ficha has pending cobros grouped under 4 properties
- WHEN the first pending page renders initially or after AJAX refresh
- THEN at most 3 property groups are visible
- AND all cobros for each visible property remain inside that property group.

#### Scenario: Propiedad ficha paginates units

- GIVEN a propiedad ficha has pending cobros grouped under 4 real units
- WHEN the first pending page renders initially or after AJAX refresh
- THEN at most 3 unit groups are visible
- AND all cobros for each visible unit remain inside that unit group.

### Requirement: Termination Modal Pending Table Parity

The system MUST render `Terminar Contrato` pending cobros using the ficha/index pending-payment table contract for desktop and mobile. The table SHALL use the same responsive card behavior and role-button presentation as ficha/index pending cobros.

#### Scenario: Desktop termination modal uses ficha/index format

- GIVEN a contract has pending cobros
- WHEN `Terminar Contrato` opens on a desktop viewport
- THEN pending cobros appear in the ficha/index-style pending table
- AND role cells show centered cobro buttons.

#### Scenario: Mobile termination modal uses ficha/index cards

- GIVEN a contract has pending cobros
- WHEN `Terminar Contrato` opens on a mobile viewport
- THEN pending rows appear as mobile cards
- AND cobro buttons remain full-width, centered, and readable.

#### Scenario: Empty pending state remains clear

- GIVEN a contract has no pending cobros
- WHEN `Terminar Contrato` opens
- THEN the pending-cobros section clearly communicates that no pending cobros exist.
