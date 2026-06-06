# Ficha Pendientes Mobile Specification

## Purpose

Redesign mobile pendientes cards in ficha pages (propiedad and cliente detail) to match the dashboard's styled cobro card experience with colored estado badges and a lightweight detail modal, while keeping the desktop table layout intact.

## Requirements

### Requirement: Mobile Card Badge Rendering

The system MUST render each pending cobro as a colored badge button on mobile viewports (<=575.98px). The badge color SHALL reflect the cobro's estado: `warning` for "Pendiente", `danger` for "Vencido", `info` for "Incompleto". Each badge button MUST display the cobro's concepto text and carry serialized cobro data via a `data-cobro` JSON attribute.

#### Scenario: Pendiente cobro renders as warning badge on mobile

- GIVEN a propiedad ficha page has pending cobros with estado "Pendiente"
- WHEN the page renders on a viewport <=575.98px
- THEN each cobro appears as a `btn-warning` badge button showing its concepto text
- AND each button has a `data-cobro` attribute containing valid JSON with id, concepto, tipo, estado, monto, fecha_cobro, deudor, deudor_id, acreedor, acreedor_id

#### Scenario: Vencido cobro renders as danger badge on mobile

- GIVEN a pending cobro has estado "Vencido"
- WHEN the page renders on mobile
- THEN the cobro appears as a `btn-danger` badge button

#### Scenario: Null relations handled gracefully

- GIVEN a cobro has a null deudor or acreedor relation
- WHEN the `data-cobro` JSON is serialized
- THEN the missing field defaults to 'N/A' or null without causing a blade error

### Requirement: Mobile Card Visual Style

The system MUST apply the same card styling to ficha mobile pendiente cards as the dashboard's `#tabla-pendientes` cards: thick border (`2px solid #adb5bd`), bottom margin (`16px`), and shadow (`0 4px 8px rgba(0,0,0,0.15)`). Badge buttons SHALL be full-width and stacked vertically within each card.

#### Scenario: Ficha mobile cards match dashboard style

- GIVEN a user views pendientes on a ficha page at <=575.98px
- WHEN the cards render
- THEN each card has a 2px gray border, 16px bottom margin, and visible shadow
- AND badge buttons span full width and stack vertically with 0.5rem spacing

### Requirement: Cobro Detail Modal

The system MUST provide a `#modalCobro` on ficha pages that displays cobro details when a mobile badge is tapped. The modal SHALL show: tipo de cobro, deudor (with link to client ficha if available), acreedor (with link to client ficha if available), formatted monto in CLP, and formatted fecha de cobro. The modal MUST include a "Registrar pago" button.

#### Scenario: Tapping badge opens detail modal

- GIVEN a user is on a ficha page at <=575.98px with pending cobros
- WHEN the user taps a colored badge button
- THEN `#modalCobro` opens showing tipo, deudor, acreedor, monto (formatted CLP), and fecha de cobro
- AND the "Registrar pago" button is visible in the modal footer

#### Scenario: Deudor/acreedor links navigate to client ficha

- GIVEN a cobro's deudor has a valid deudor_id
- WHEN the modal renders the deudor field
- THEN the deudor name is a clickable link to `/cliente/ficha/{deudor_id}`
- AND the link has no underline decoration

#### Scenario: Missing date shows placeholder

- GIVEN a cobro has no fecha_cobro value
- WHEN the modal renders
- THEN the fecha field displays "No definida"

### Requirement: Desktop Table Unchanged

The system MUST preserve the original 2-column desktop table layout (Concepto + "Revisar" button) for viewports >=576px. The mobile badge rendering SHALL be hidden on desktop, and the desktop table SHALL be hidden on mobile.

#### Scenario: Desktop table renders normally at >=576px

- GIVEN a ficha page loads on a viewport >=576px
- WHEN the pendientes section renders
- THEN the original 2-column table with "Revisar" button is visible
- AND the mobile badge divs are hidden (`d-sm-none`)

#### Scenario: Mobile badges hidden on desktop

- GIVEN a ficha page loads on a viewport >=768px (sm breakpoint)
- WHEN the pendientes section renders
- THEN no mobile badge elements are visible
- AND only the desktop table is shown

### Requirement: Existing "Revisar" Modal Preserved

The system MUST keep the existing `cobro/modal/show` include and `abrirModal` "Revisar" button functionality on desktop. The new `#modalCobro` is an additional lightweight summary modal, not a replacement.

#### Scenario: Desktop "Revisar" button still works

- GIVEN a user is on desktop (>=576px)
- WHEN the user clicks "Revisar" on a pending cobro
- THEN the existing `#modalPrincipal` opens with the full `cobro/modal/show` content
- AND the new `#modalCobro` is not involved
