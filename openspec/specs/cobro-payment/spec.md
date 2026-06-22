# Cobro Payment Specification

## Purpose

Register a payment against a cobro, transitioning it to `Pagado` estado. Creates a Transaccion and TransaccionCobro pivot. Reusable from dashboard pendientes and client detail view.

## Requirements

### Requirement: Payment endpoint contract

The system MUST provide `POST /api/cobro/pagar` accepting `{cobro_id: int, monto: int}`. On success, returns HTTP 200 with created Transaccion id and updated cobro estado.

#### Scenario: Successful payment of Pendiente cobro

- GIVEN a cobro with estado="Pendiente", monto=500000
- WHEN POST /api/cobro/pagar with `{cobro_id: <id>, monto: 500000}`
- THEN response is 200 with `{transaccion_id: N, cobro_estado: "Pagado"}`
- AND a Transaccion row is created with correct Origen/Destino
- AND a TransaccionCobro pivot links Transaccion to cobro with monto_pagado=500000
- AND cobro.estado is updated to "Pagado"

#### Scenario: Successful payment of Vencido cobro

- GIVEN a cobro with estado="Vencido", monto=300000
- WHEN POST /api/cobro/pagar with `{cobro_id: <id>, monto: 300000}`
- THEN response is 200, cobro.estado="Pagado", Transaccion + TransaccionCobro created

### Requirement: Payable estado validation

The system MUST reject payments for cobros not in `Pendiente` or `Vencido` estado. Returns HTTP 422 with error on `cobro_id`.

#### Scenario: Already-paid cobro rejected

- GIVEN a cobro with estado="Pagado"
- WHEN POST /api/cobro/pagar with that cobro_id
- THEN response is 422

#### Scenario: Anulado cobro rejected

- GIVEN a cobro with estado="Anulado"
- WHEN POST /api/cobro/pagar with that cobro_id
- THEN response is 422

### Requirement: Cobro existence validation

The system MUST return HTTP 404 if cobro_id does not exist.

#### Scenario: Non-existent cobro

- WHEN POST /api/cobro/pagar with `{cobro_id: 99999, monto: 100000}`
- THEN response is 404

### Requirement: Input validation

The system MUST validate `cobro_id` and `monto` as positive integers. Missing or invalid fields return HTTP 422.

#### Scenario: Missing or invalid monto

- WHEN POST /api/cobro/pagar with `{cobro_id: 5}` or `{cobro_id: 5, monto: 0}`
- THEN response is 422 with error on `monto`

#### Scenario: Non-integer values

- WHEN POST /api/cobro/pagar with `{cobro_id: "abc", monto: "xyz"}`
- THEN response is 422 with errors on both fields

### Requirement: Termination Modal Cobro Detail Entry Point

The system MUST allow pending cobro buttons shown in `Terminar Contrato` to open the existing cobro detail/payment experience. Payment behavior SHALL keep the existing `POST /api/cobro/pagar` contract and user feedback conventions.

#### Scenario: Pending cobro opens detail/payment modal

- GIVEN a pending cobro is visible in `Terminar Contrato`
- WHEN the user selects its cobro button
- THEN the existing cobro detail/payment modal opens with the cobro details
- AND the user can initiate the existing payment flow.

#### Scenario: Payment feedback remains modal-based

- GIVEN the user registers a payment from the termination modal cobro detail
- WHEN payment succeeds or fails
- THEN feedback is shown through Bootstrap/custom modal UI
- AND native `alert`, `confirm`, or `prompt` dialogs are not used.

#### Scenario: No new cobro creation from termination payment flow

- GIVEN the cobro detail/payment modal was opened from `Terminar Contrato`
- WHEN the modal is displayed
- THEN it exposes existing detail/payment behavior only
- AND it does not expose an `Agregar cobro` action.

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

---

## Delta: Loading Indicators (Archived 2026-05-26)

### Requirement: Payment button spinner during registrarPago

Button MUST show `spinner-border` and be disabled while `POST /api/cobro/pagar` is in flight. Re-enabled on completion.

#### Scenario: Spinner lifecycle

- GIVEN user clicks "Registrar Pago"
- WHEN fetch begins, THEN button shows spinner, disabled
- WHEN API returns 200, THEN spinner removed, button enabled
- WHEN API returns 4xx/5xx, THEN spinner removed, button enabled, error shown

### Requirement: Modal spinner during resolveCobroRelationships

Modal MUST show spinner while `resolveCobroRelationships()` fetches; form fields disabled during loading.

#### Scenario: Spinner lifecycle

- GIVEN modal opens, fetch begins, THEN spinner visible, fields disabled
- WHEN fetch completes, THEN spinner removed, data displayed
