# Delta for Contract Termination Guarantee

## ADDED Requirements

### Requirement: Final Termination Action

The system MUST complete the final `Terminar contrato` action from the contract card modal without native browser dialogs. The action MUST validate the discount total before submitting, disable the action while the request is in flight, use local loading indicators, and show success or error feedback through modal UI.

#### Scenario: User submits a valid termination

- GIVEN the termination modal has garantía 500000 and discounts totaling 80000
- WHEN the user confirms `Terminar contrato`
- THEN the request is submitted with the discount details
- AND the confirm action is disabled with loading feedback until completion.

#### Scenario: Frontend rejects excessive discounts

- GIVEN the termination modal has garantía 500000
- WHEN the user attempts to submit discounts totaling 500001
- THEN the request MUST NOT be sent
- AND modal or inline UI MUST explain that discounts cannot exceed garantía.

### Requirement: Termination Persistence

The backend MUST provide an atomic contract termination workflow. It MUST validate that `sum(descuentos) <= garantía`, set `Contrato.fecha_termino = now()`, and reject invalid submissions without partial writes.

#### Scenario: Backend terminates contract atomically

- GIVEN an active contract with garantía 500000
- WHEN a valid termination request is processed
- THEN the contract fecha_termino is set to the current time
- AND all termination cobros and links are persisted together.

#### Scenario: Backend rejects excessive discounts

- GIVEN an active contract with garantía 500000
- WHEN a termination request has discounts totaling 500001
- THEN the backend returns a validation error
- AND no contract, cobro, transaction, or discount-link rows are created or changed.

### Requirement: Guarantee Discount and Refund Cobros

The system MUST create one paid discount cobro for each submitted discount, with debtor arrendatario, creditor arrendador, and enough context to audit the contract, concept, and reason. It MUST create one refund cobro named `Devolución Garantía Arrendatario`: `Pendiente` when refund is greater than 0, or `Pagado` with monto 0 when discounts consume the full guarantee. Garantía MUST NOT be modeled as a transaction, origin, destination, debtor, or creditor.

#### Scenario: Positive refund creates discount and pending refund cobros

- GIVEN garantía is 500000 and discounts total 80000
- WHEN the contract is terminated
- THEN paid discount cobros are created for 80000 total
- AND a `Devolución Garantía Arrendatario` cobro is created as `Pendiente` for 420000.

#### Scenario: Full discount creates zero paid refund cobro

- GIVEN garantía is 500000 and discounts total 500000
- WHEN the contract is terminated
- THEN the refund cobro is `Pagado` with monto 0
- AND garantía is not used as a transaction origin or destination.

### Requirement: Refund Transaction and Discount Linkage

The system MUST create `Transaccion` and `Transaccion_Cobro` only when the calculated refund is greater than 0. It MUST NOT create either row for a zero refund. The system MUST link the refund cobro to each discount cobro through `Descuento_Garantia` using explicit Eloquent-safe table, key, and relationship naming.

#### Scenario: Positive refund creates transaction link

- GIVEN the calculated refund is 420000
- WHEN the contract is terminated
- THEN exactly one refund transaction is created
- AND `Transaccion_Cobro` links it to the refund cobro.

#### Scenario: Zero refund creates no transaction rows

- GIVEN the calculated refund is 0
- WHEN the contract is terminated
- THEN no `Transaccion` is created
- AND no `Transaccion_Cobro` is created.

#### Scenario: Discounts are auditable from refund

- GIVEN termination creates two discount cobros
- WHEN the refund cobro is persisted
- THEN `Descuento_Garantia` links that refund cobro to both discount cobros.

## MODIFIED Requirements

### Requirement: Guarantee Refund Calculation

The system MUST calculate `Total descuentos` as the sum of added discount concept amounts only. Pending cobros MUST NOT affect `Total descuentos`. `Monto a devolver al arrendatario` MUST equal `garantía - total descuentos`, and total discounts MUST NOT exceed garantía.
(Previously: calculated the displayed refund but did not require the discount ceiling validation.)

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
