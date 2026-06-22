# Contract Termination Guarantee Specification

## Purpose

Define how the `Terminar Contrato` modal presents pending cobros, computes the guarantee refund from discount concepts only, and persists contract termination with guarantee refund/discount records.

## Requirements

### Requirement: Termination Warning Confirmation Flow

The system MUST show a warning confirmation before terminating a contract and MUST NOT collect guarantee discounts in that warning step.

#### Scenario: User confirms warning

- GIVEN an active contract
- WHEN the user selects `Terminar Contrato`
- THEN a modal warning explains that termination sets `fecha_termino`, creates pending guarantee refund, and creates proportional rent cobros
- AND confirmation requires an explicit user action without native dialogs.

#### Scenario: User cancels warning

- GIVEN the warning modal is open
- WHEN the user cancels
- THEN no contract, cobro, discount-link, or transaction row is changed.

### Requirement: Proportional Rent Cobros

Termination MUST create exactly one `Ingreso Proporcional Renta Arrendatario` cobro for the arrendatario and one `Egreso Proporcional Renta Arrendador` cobro for the arrendador, both tied to the terminated contract and pending payment when amount is greater than 0.

#### Scenario: Participants are assigned correctly

- GIVEN an active contract with arrendatario, arrendador, renta, dia_pago, and fecha_termino
- WHEN termination succeeds
- THEN the ingreso proporcional is owed by the arrendatario
- AND the egreso proporcional is owed to the arrendador.

#### Scenario: Zero proportional days

- GIVEN termination day is less than or equal to clamped dia_pago
- WHEN termination succeeds
- THEN proportional amount is 0
- AND no duplicate positive proportional cobros are created.

### Requirement: Proportional Rent Calculation

The system MUST compute proportional rent as `round(renta / daysInMonth(fecha_termino) * days)`, where days count from clamped `dia_pago` inclusive to termination day exclusive. If `dia_pago` exceeds the month length, it MUST be clamped to that month’s last day.

#### Scenario: Thirty-day month exclusivity

- GIVEN renta 300000, fecha_termino day 10 in a 30-day month, and dia_pago 5
- WHEN proportional rent is calculated
- THEN 5 days are counted
- AND the amount is 50000.

#### Scenario: February and leap year divisor

- GIVEN the same renta and dia_pago 1
- WHEN fecha_termino is in February
- THEN the divisor is 28 in non-leap years and 29 in leap years.

#### Scenario: Month lengths and clamp

- GIVEN dia_pago is 31
- WHEN fecha_termino is in a 30-day month or February
- THEN dia_pago is clamped to that month’s last day
- AND 31-day months use divisor 31.

### Requirement: Cobro Type Schema Source

`corretaje-bd.sql` table `Cobro`, column `tipo` MUST include enum values `Ingreso Proporcional Renta Arrendatario` and `Egreso Proporcional Renta Arrendador`; any safe existing-DB update plan MUST preserve data and MUST NOT require destructive commands.

#### Scenario: Schema artifacts include new types

- GIVEN implementation updates cobro type support
- WHEN schema artifacts are inspected
- THEN `corretaje-bd.sql` contains both proportional enum values.

### Requirement: Contract Card Property/Unidad Heading Baseline

The system MUST render each contract card heading according to property unit count: if the property has only one unidad, show only the property; if the property has more than one unidad, show unidad plus property.

#### Scenario: Single-unit property shows only property

- GIVEN a contract belongs to a property with exactly one unidad
- WHEN the contract card is rendered
- THEN the card heading shows the property
- AND it does not duplicate the unidad name.

#### Scenario: Multi-unit property shows unidad and property

- GIVEN a contract belongs to a property with more than one unidad
- WHEN the contract card is rendered
- THEN the card heading shows both the unidad and the property.

### Requirement: Pending Cobros Are Informational Payable Items

The system MUST show pending cobros in the termination modal as existing payable items and MUST NOT provide an `Agregar cobro` button or action there.

#### Scenario: Pending cobros visible without creation action

- GIVEN a contract has pending cobros
- WHEN the user opens `Terminar Contrato`
- THEN pending cobros are displayed in the pending-payment section
- AND no `Agregar cobro` button, link, or action is present.

#### Scenario: No pending cobros state

- GIVEN a contract has no pending cobros
- WHEN the user opens `Terminar Contrato`
- THEN a clear empty state indicates that there are no pending cobros.

### Requirement: Discount Concepts

The system MUST allow guarantee discount rows to use `Aseo final`, `Reparación`, or `Extra`. The existing `Agregar descuento` action SHALL be the only action that adds discount concept rows.

#### Scenario: Extra concept available

- GIVEN the termination modal is open
- WHEN the user opens a discount concept selector
- THEN `Extra` is available alongside the existing discount concepts.

### Requirement: Guarantee Refund Calculation

The system MUST calculate `Total descuentos` as the sum of added discount concept amounts only. Pending cobros MUST NOT affect `Total descuentos`. `Monto a devolver al arrendatario` MUST equal `garantía - total descuentos`, and total discounts MUST NOT exceed garantía.

#### Scenario: Pending cobros excluded from discount total

- GIVEN garantía is 500000, pending cobros total 200000, and discount concepts total 80000
- WHEN the modal recalculates totals
- THEN `Total descuentos` is 80000
- AND `Monto a devolver al arrendatario` is 420000.

#### Scenario: No discount concepts refunds full guarantee

- GIVEN garantía is 500000 and no discount concepts remain
- WHEN the modal recalculates totals
- THEN `Total descuentos` is 0
- AND `Monto a devolver al arrendatario` is 500000.

#### Scenario: Discount total cannot exceed guarantee

- GIVEN garantía is 500000
- WHEN discount concepts total 500001
- THEN the system marks the total as invalid
- AND termination cannot be submitted.

### Requirement: Removing All Discount Concepts

The system MUST allow the user to remove every discount concept without confirmation. When no discount concepts remain, the system MUST show an inline Bootstrap warning near the discounts section communicating: `¡Atención! se devolverá la garantía en su totalidad al arrendatario. ¿Está seguro que no hay reparaciones o aseo que pagar?` Native `alert`, `confirm`, and `prompt` MUST NOT be used.

#### Scenario: Last discount removal shows inline warning

- GIVEN only one discount concept remains
- WHEN the user removes it
- THEN the discount concept is removed without opening a confirmation modal
- AND an inline warning shows the required business message
- AND no native browser dialog is invoked.

#### Scenario: Full guarantee refund remains editable

- GIVEN no discount concepts remain
- WHEN the user adds a new discount concept
- THEN a new discount row is available
- AND the warning is hidden after recalculation.

### Requirement: Final Termination Action

The system MUST complete `Terminar contrato` after the warning confirmation without native dialogs. The action MUST disable confirmation while in flight, use local loading indicators, and show success or error feedback through modal UI. It MUST NOT submit guarantee discounts during termination.

#### Scenario: User submits a valid termination

- GIVEN the warning confirmation is visible for an active contract
- WHEN the user confirms `Terminar contrato`
- THEN termination is submitted without discount details
- AND the confirm action is disabled with loading feedback until completion.

#### Scenario: Frontend keeps feedback modal-based

- GIVEN termination succeeds or fails
- WHEN feedback is shown
- THEN Bootstrap/custom modal UI is used
- AND native `alert`, `confirm`, or `prompt` dialogs are not used.

### Requirement: Termination Persistence

The backend MUST provide an atomic, idempotent contract termination workflow. It MUST only set `Contrato.fecha_termino`, create one pending `Devolución Garantía Arrendatario` cobro, and create pending proportional ingreso/egreso rent cobros. It MUST NOT create discount cobros, `Descuento_Garantia` rows for user-entered discounts, refund transactions, or transaction pivots during termination. Repeated termination for the same contract MUST NOT create duplicates.

#### Scenario: Backend terminates contract atomically

- GIVEN an active contract
- WHEN a valid termination request is processed
- THEN `fecha_termino` is set
- AND exactly one pending refund cobro and the pending proportional rent cobros are persisted together.

#### Scenario: Duplicate termination prevented

- GIVEN a contract already has `fecha_termino` or termination cobros
- WHEN termination is requested again
- THEN no duplicate refund, discount, transaction, or proportional cobros are created.

#### Scenario: Termination ignores modal discount rows

- GIVEN the user entered guarantee discount rows before termination
- WHEN termination succeeds
- THEN no discount cobro is created
- AND no `Descuento_Garantia` row is created for those rows.

### Requirement: Guarantee Refund Cobro at Termination

The system MUST create one pending `Devolución Garantía Arrendatario` cobro at termination based on the refundable guarantee before final discount edits. The pending refund cobro MUST model the arrendador as debtor and the arrendatario as creditor. Discount cobros and `Descuento_Garantia` links MUST only be created later by the guarantee refund finalization workflow. Garantía MUST NOT be modeled as a transaction origin or destination.

#### Scenario: Termination creates pending refund cobro

- GIVEN garantía is 500000
- WHEN the contract is terminated
- THEN one `Devolución Garantía Arrendatario` cobro is created as `Pendiente`
- AND its debtor is the arrendador and its creditor is the arrendatario
- AND no finalized discount rows are linked to it.

#### Scenario: Full discount is deferred to refund finalization

- GIVEN user-entered discounts would consume the full guarantee
- WHEN the contract is terminated
- THEN the refund cobro remains pending until `Devolver garantía`
- AND garantía is not used as a transaction origin or destination.

### Requirement: Refund Transaction and Discount Linkage

The system MUST create `Transaccion` and `Transaccion_Cobro` for guarantee refund only when `Devolver garantía` finalizes a positive pending refund. It MUST NOT create either row at termination. The system MUST create finalized discount cobros only during refund finalization, set each discount cobro `estado` to `Pagado`, and link them to the refund through `Descuento_Garantia`.

#### Scenario: Termination creates no refund transaction

- GIVEN the calculated refund is positive
- WHEN the contract is terminated
- THEN no refund `Transaccion` or `Transaccion_Cobro` is created.

#### Scenario: Discounts are auditable from refund

- GIVEN finalization creates discount cobros
- WHEN the refund is paid
- THEN each discount cobro is created with `estado` `Pagado`
- AND `Descuento_Garantia` links that refund cobro to those discount cobros.
