# Contract Termination Guarantee Specification

## Purpose

Define how the `Terminar Contrato` modal presents pending cobros and computes the guarantee refund from discount concepts only.

## Requirements

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

The system MUST calculate `Total descuentos` as the sum of added discount concept amounts only. Pending cobros MUST NOT affect `Total descuentos`. `Monto a devolver al arrendatario` MUST equal `garantía - total descuentos`.

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
