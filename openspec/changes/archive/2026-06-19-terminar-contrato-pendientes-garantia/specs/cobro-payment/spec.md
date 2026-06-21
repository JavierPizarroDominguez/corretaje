# Delta for Cobro Payment

## ADDED Requirements

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
