# Delta for Pivot Relation

## MODIFIED Requirements

### Requirement: Controller MUST reset related model variable before each scoped block

The generated store and update methods MUST initialize `$related{Model} = null;` before each scoped relation block in `buildPivotStoreFields()` and `buildPivotUpdateFields()`. This prevents variable bleed between consecutive scoped blocks (e.g., `$relatedCliente` from deudor leaking into acreedor), which causes incorrect pivot records to be created with wrong FK assignments.

(Previously: `$related{Model}` was not reset between scoped blocks, causing the deudor block's resolved model to bleed into the acreedor block when both target the same model)

#### Scenario: Deudor variable does not bleed into acreedor

- GIVEN a Cobro with both `deudor` and `acreedor` scoped relations targeting `Cliente`
- WHEN the store method processes both blocks sequentially
- THEN `$relatedCliente` MUST be `null` at the start of the acreedor block
- AND the acreedor block MUST resolve its own target independently

#### Scenario: Each block resolves target from its own input

- GIVEN a create request with `deudor_Cliente_id = 5` and `acreedor_Cliente_id = 8`
- WHEN the store method runs
- THEN the deudor pivot MUST be created with `Cliente_id = 5`
- AND the acreedor pivot MUST be created with `Cliente_id = 8`
- AND the acreedor pivot MUST NOT use `Cliente_id = 5` from deudor's resolution

#### Scenario: Single scoped relation unaffected by reset

- GIVEN a Cobro with only `deudor` scoped relation
- WHEN the store method runs
- THEN `$relatedCliente = null;` MUST be set before the deudor block
- AND the deudor pivot MUST be created correctly

#### Scenario: Update method also resets variable per block

- GIVEN an update request modifying both `deudor` and `acreedor` on the same Cobro
- WHEN `buildPivotUpdateFields()` generates the update logic
- THEN each scoped block MUST start with `$related{Model} = null;`
- AND each block MUST delete the old pivot and create a new one with the correct FK
