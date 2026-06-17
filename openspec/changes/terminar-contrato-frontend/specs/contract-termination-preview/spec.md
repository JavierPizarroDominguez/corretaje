# Contract Termination Preview Specification

## Purpose

Provide a front-end-only review modal for ending an active contract, so users can preview charges, refunds, and guarantee return without persisting termination data.

## Requirements

### Requirement: Termination entry point

The system MUST show a `Terminar contrato` action for each active contract in property and client contract views.

#### Scenario: Active contract action is available

- GIVEN a property or client profile lists an active contract
- WHEN the contract row or card is rendered
- THEN it shows a `Terminar contrato` action for that contract

#### Scenario: No persistence from entry point

- GIVEN the termination action is shown
- WHEN the user opens or closes it
- THEN no contract status, end date, cobro, payment, or accounting record is changed

### Requirement: Bootstrap review modal

The system MUST open a Bootstrap modal for termination review and MUST NOT use native `alert`, `confirm`, or `prompt` dialogs.

#### Scenario: Modal opens

- GIVEN a user selects `Terminar contrato`
- WHEN the action runs
- THEN a Bootstrap modal opens for the selected contract
- AND no native browser dialog is displayed

#### Scenario: Review-only copy is clear

- GIVEN the modal is open
- WHEN the user reads the modal header or body
- THEN the modal states that this is a preview and does not terminate the contract

### Requirement: Modal summary content

The modal MUST show inspection warning, automatic proportional services/common-expenses notice, original guarantee, start date, and end date as today.

#### Scenario: Required summary is visible

- GIVEN the modal opens for a contract
- WHEN the summary content is rendered
- THEN it shows inspection guidance before returning the property
- AND it shows guarantee, start date, and today's date as termination date

#### Scenario: Proportional charges notice

- GIVEN the modal opens
- WHEN the summary content is rendered
- THEN it explains that proportional services/common expenses are automatic notice items only in this preview

### Requirement: Cobros and adjustments

The modal MUST include pending cobros when present, a pre-added `Aseo Final` row, and temporary add/edit/remove rows for repairs, refunds, extras, and similar adjustments.

#### Scenario: Default and pending rows appear

- GIVEN a contract has pending cobros
- WHEN the modal opens
- THEN pending cobros are listed
- AND an editable `Aseo Final` row is pre-added

#### Scenario: No pending cobros

- GIVEN a contract has no pending cobros
- WHEN the modal opens
- THEN the modal states there are no pending cobros
- AND still includes `Aseo Final`

#### Scenario: Temporary rows can change

- GIVEN the modal is open
- WHEN the user adds, edits, or removes a repair, refund, or extra row
- THEN only the preview rows and totals change in the browser

### Requirement: Guarantee return calculation

The modal MUST calculate original guarantee, total discounts/charges, and amount returned to tenant; refunds or negative adjustments MUST increase the returned amount clearly.

#### Scenario: Charges reduce return

- GIVEN guarantee is `$500.000` and charges total `$80.000`
- WHEN totals recalculate
- THEN amount returned to tenant is `$420.000`

#### Scenario: Refunds increase return

- GIVEN guarantee is `$500.000`, charges are `$80.000`, and refunds are `$20.000`
- WHEN totals recalculate
- THEN total discount/charges is `$60.000`
- AND amount returned to tenant is `$440.000`
