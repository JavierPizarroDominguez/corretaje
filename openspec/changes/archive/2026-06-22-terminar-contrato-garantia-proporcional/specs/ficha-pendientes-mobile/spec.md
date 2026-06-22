# Delta for Ficha Pendientes Mobile

## ADDED Requirements

### Requirement: Guarantee Refund Pending Routing

Dashboard, cliente ficha, and propiedad ficha pending cobro buttons MUST identify pending `Devolución Garantía Arrendatario` cobros and open the guarantee refund/devolution modal instead of the generic payment modal. The modal MUST allow discount rows to be edited before `Devolver garantía`; opening the modal MUST NOT create discount cobros or `Descuento_Garantia` rows.

#### Scenario: Dashboard routes refund pending
- GIVEN the dashboard shows a pending guarantee refund cobro
- WHEN the user selects it
- THEN the guarantee refund modal opens
- AND the generic cobro payment modal does not open.

#### Scenario: Cliente and propiedad fichas route refund pending
- GIVEN cliente ficha or propiedad ficha shows a pending guarantee refund cobro
- WHEN the user selects it on desktop or mobile
- THEN the same guarantee refund modal workflow opens.

#### Scenario: Opening refund modal does not finalize discounts
- GIVEN a pending guarantee refund cobro is visible
- WHEN the user opens its refund modal
- THEN no discount cobro or `Descuento_Garantia` row is created until `Devolver garantía` is accepted.

#### Scenario: Non-refund cobros keep existing behavior
- GIVEN any pending cobro that is not a guarantee refund
- WHEN the user selects it
- THEN the normal cobro detail/payment behavior is preserved.

### Requirement: Guarantee Refund Modal Remaining Term

The guarantee refund modal opened from a pending refund cobro MUST show `Plazo restante` instead of the original guarantee card/value. The remaining term MUST be based on the 30-day refund window from `fecha_termino`.

#### Scenario: Remaining term shown
- GIVEN a pending refund cobro for a terminated contract
- WHEN the refund modal opens from pendientes
- THEN the top card label is `Plazo restante`
- AND it shows the remaining days in the 30-day refund window.

#### Scenario: Expired term does not go negative
- GIVEN more than 30 days passed since `fecha_termino`
- WHEN the refund modal opens
- THEN `Plazo restante` is shown as zero or expired
- AND no negative day count is displayed.

## MODIFIED Requirements

### Requirement: Cobro Detail Modal

The system MUST preserve ficha cobro detail/payment behavior for normal cobros. Selecting a normal cobro on desktop or mobile SHALL open `#modalCobro` with tipo, linked deudor/acreedor when available, CLP-formatted monto, fecha text, and `Registrar pago`. Selecting a pending guarantee refund cobro SHALL open the guarantee refund/devolution modal. Payment success or error MUST use Bootstrap modal feedback, not native browser dialogs.
(Previously: every ficha cobro opened `#modalCobro`.)

#### Scenario: Selecting normal cobro opens payment modal
- GIVEN a ficha pending cobro is visible and is not a guarantee refund
- WHEN the user selects its cobro button
- THEN `#modalCobro` opens with the required detail fields
- AND the registrar button can submit payment.

#### Scenario: Selecting guarantee refund opens devolution modal
- GIVEN a ficha pending guarantee refund cobro is visible
- WHEN the user selects its cobro button
- THEN the guarantee refund/devolution modal opens
- AND `#modalCobro` is not used for that action.
