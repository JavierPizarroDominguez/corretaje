# Delta for Cobro Payment

## ADDED Requirements

### Requirement: Guarantee Refund Finalization

The system MUST provide a `Devolver garantía` workflow for pending `Devolución Garantía Arrendatario` cobros. When the user accepts the refund modal, finalization MUST create discount cobros from the current modal discount rows with `estado` `Pagado`, link them to the refund cobro through `Descuento_Garantia`, recalculate the refund amount, mark the refund cobro `Pagado`, and create exactly one transaction and pivot for the paid refund amount when the amount is greater than 0.

#### Scenario: Positive refund is finalized once
- GIVEN a pending guarantee refund cobro with monto 420000
- WHEN the user selects `Devolver garantía`
- THEN the cobro is marked `Pagado`
- AND exactly one transaction and one transaction-cobro link are created.

#### Scenario: Current modal discounts are finalized
- GIVEN the refund modal has edited discount rows totaling 80000 against garantía 500000
- WHEN the user accepts `Devolver garantía`
- THEN discount cobros are created for the current rows with `estado` `Pagado`
- AND those discount cobros are linked to the refund cobro through `Descuento_Garantia`
- AND the paid refund amount is recalculated as 420000.

#### Scenario: Finalization rejects duplicates
- GIVEN a guarantee refund cobro already marked `Pagado`
- WHEN `Devolver garantía` is requested again
- THEN the system rejects the request
- AND no additional transaction, pivot, discount cobro, or discount-link row is created.

#### Scenario: Zero refund is finalized without transaction
- GIVEN current modal discounts consume the full garantía
- WHEN the user accepts `Devolver garantía`
- THEN discount cobros are created once with `estado` `Pagado` and their refund links are created once
- AND the refund cobro is marked `Pagado` with monto 0
- AND no transaction or transaction-cobro link is created.

#### Scenario: Excessive discounts rejected
- GIVEN a pending guarantee refund cobro backed by garantía 500000
- WHEN final discounts exceed the refundable guarantee
- THEN the system rejects finalization
- AND no cobro state or transaction data is changed.

### Requirement: Guarantee Refund Payment Scope

Generic cobro payment MUST NOT bypass the guarantee refund finalization rules for `Devolución Garantía Arrendatario` pending cobros.

#### Scenario: Generic payment cannot skip refund workflow
- GIVEN a pending guarantee refund cobro
- WHEN the user attempts the normal `Registrar pago` flow
- THEN the system routes to or requires the `Devolver garantía` workflow
- AND finalization rules remain enforced.

#### Scenario: Normal cobros remain payable
- GIVEN a non-guarantee pending or vencido cobro
- WHEN the user registers payment
- THEN the existing payment contract remains valid.
