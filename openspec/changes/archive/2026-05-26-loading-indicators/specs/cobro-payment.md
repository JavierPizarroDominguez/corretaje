# Delta: Cobro Payment Loading Indicator

## Modification to `cobro-payment` — spinner on payment button and resolution modal.

## Requirement: Payment button spinner during registrarPago

Button MUST show `spinner-border` and be disabled while `POST /api/cobro/pagar` is in flight. Re-enabled on completion.

#### Scenario: Spinner lifecycle

- GIVEN user clicks "Registrar Pago"
- WHEN fetch begins, THEN button shows spinner, disabled
- WHEN API returns 200, THEN spinner removed, button enabled
- WHEN API returns 4xx/5xx, THEN spinner removed, button enabled, error shown

## Requirement: Modal spinner during resolveCobroRelationships

Modal MUST show spinner while `resolveCobroRelationships()` fetches; form fields disabled during loading.

#### Scenario: Spinner lifecycle

- GIVEN modal opens, fetch begins, THEN spinner visible, fields disabled
- WHEN fetch completes, THEN spinner removed, data displayed
