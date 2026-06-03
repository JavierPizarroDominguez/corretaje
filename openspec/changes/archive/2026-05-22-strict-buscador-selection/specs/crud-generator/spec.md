# Delta for CRUD Generator

## MODIFIED Requirements

### Requirement: Controller MUST create pivot records with explicit FKs

Generated store/update code MUST create a new pivot record with explicit FK assignments: parent FK (`Cobro_id`), target FK (`Cliente_id`), and scope column (`rol`). It MUST NOT set attributes on the parent model (no `$cobro->deudor = $id`). The target entity MUST be resolved via `findOrFail` using the integer FK ID from the hidden input. `firstOrCreate` MUST NOT be used — free-text submissions without a selected FK ID MUST fail validation before reaching the controller.

(Previously: used `firstOrCreate` to resolve target by display name when no FK ID was provided, allowing creation of incomplete records from free text)

#### Scenario: Store creates ParticipanteCobro for deudor

- GIVEN a Cobro create request with `deudor_Cliente_id = 5`
- WHEN the store method runs the generated scoped pivot code
- THEN it MUST resolve the target via `Cliente::findOrFail(5)`
- AND create a new ParticipanteCobro with `Cobro_id = $cobro->id`, `Cliente_id = 5`, `rol = 'Deudor'`

### Requirement: Select field name for scoped relations MUST match hidden FK key

When `create-field-fk-select.stub` renders a `<select>` for a scoped relation, the `name` attribute MUST be `{relationName}_{scopedTargetFk}` (e.g., `deudor_Cliente_id`), matching the hidden input name used by the buscador. This ensures both input modes send data under the same controller key.

(Previously: no change to this requirement — retained for completeness)

#### Scenario: Select for deudor sends under deudor_Cliente_id

- GIVEN a scoped relation deudor with `scopedTargetFk = 'Cliente_id'` and client count ≤ threshold
- WHEN the create form renders a select field
- THEN the select MUST have `name="deudor_Cliente_id"`
- AND the submitted value MUST be readable as `$request->input('deudor_Cliente_id')`

#### Scenario: Select for acreedor sends under acreedor_Cliente_id

- GIVEN a scoped relation acreedor with `scopedTargetFk = 'Cliente_id'`
- WHEN the create form renders a select field
- THEN the select MUST have `name="acreedor_Cliente_id"`

### Requirement: Validation for scoped FK inputs MUST be conditional

`buildValidationRules()` MUST generate validation rules for buscador FK fields as follows: the hidden FK input MUST use `required_with:{buscador_input_name}|integer|exists:{table},id`; the text input MUST use `sometimes|nullable|string`. For non-buscador FK fields, the existing `sometimes|nullable|integer|exists:{table},id` rule remains unchanged.

(Previously: all scoped FK inputs used `sometimes|nullable|integer|exists:{table},id` — no `required_with` coupling to text input)

#### Scenario: Validation passes with select-provided ID

- GIVEN a request with `deudor_Cliente_id = 5` (from select, hidden input empty)
- WHEN validation runs
- THEN the rule `required_with:nombre-deudor|integer|exists:cliente,id` MUST pass
- AND the request MUST NOT return a 422 error

#### Scenario: Validation passes with buscador-provided ID

- GIVEN a request with `deudor_Cliente_id = 3` (from buscador onSelect) and `nombre-deudor` set
- WHEN validation runs
- THEN the `required_with` rule MUST pass for the integer value

#### Scenario: Validation fails when text provided without FK ID

- GIVEN a request with `nombre-deudor = "Juan"` and `deudor_Cliente_id` empty
- WHEN validation runs
- THEN the `required_with` rule MUST fail — 422 error on `deudor_Cliente_id`

#### Scenario: Validation passes with no input

- GIVEN a request with both `deudor_Cliente_id` and `nombre-deudor` absent or null
- WHEN validation runs
- THEN the `required_with` rule MUST NOT trigger (text input is empty)
- AND the request MUST pass validation

## ADDED Requirements

### Requirement: buildCreateBuscadorCalls MUST always emit hidden input assignment

`buildCreateBuscadorCalls()` MUST emit the hidden input assignment line `document.getElementById('input-create-{field}-id').value = item.id` for ALL buscador fields, including direct FK relations (contrato, servicio, propiedad, unidad). The scoped-relation guard that previously limited this to scoped fields MUST be removed.

#### Scenario: Direct FK contrato gets hidden input assignment

- GIVEN a direct FK field `contrato_id` with `isBuscador = true`
- WHEN `buildCreateBuscadorCalls()` processes it
- THEN the emitted `onSelect` MUST include `document.getElementById('input-create-contrato_id-id').value = item.id`

#### Scenario: Scoped relation deudor gets hidden input assignment

- GIVEN a scoped relation `deudor`
- WHEN `buildCreateBuscadorCalls()` processes it
- THEN the emitted `onSelect` MUST include `document.getElementById('input-create-deudor-id').value = item.id`

### Requirement: store-field-relation-buscador.stub MUST use findOrFail

The `store-field-relation-buscador.stub` MUST use `{Model}::findOrFail($data['{fk_column}'])` to resolve the related entity. It MUST NOT use `firstOrCreate` or any creation fallback. The FK ID is guaranteed to be valid by the `exists:{table},id` validation rule.

#### Scenario: Store resolves contrato by ID

- GIVEN generated code for a `contrato_id` buscador field
- WHEN the store method runs
- THEN it MUST execute `Contrato::findOrFail($data['contrato_id'])`
- AND MUST NOT call `firstOrCreate`

#### Scenario: Store resolves scoped target by ID

- GIVEN generated code for a `deudor` scoped relation
- WHEN the store method runs
- THEN it MUST execute `Cliente::findOrFail($data['deudor_Cliente_id'])`
- AND MUST NOT call `firstOrCreate`

### Requirement: buildPivotStoreFields MUST NOT use firstOrCreate

`buildPivotStoreFields()` MUST generate code that resolves the target entity via `findOrFail` using the FK ID from the hidden input. The `firstOrCreate` fallback by display name MUST be removed entirely.

#### Scenario: Pivot store uses findOrFail for deudor

- GIVEN `buildPivotStoreFields()` processes a deudor scoped relation
- WHEN generating the store code
- THEN the output MUST contain `Cliente::findOrFail($data['deudor_Cliente_id'])`
- AND MUST NOT contain `firstOrCreate`

### Requirement: buildPivotUpdateFields MUST NOT use firstOrCreate

`buildPivotUpdateFields()` MUST generate code that resolves the target entity via `findOrFail` using the FK ID from the hidden input. The `firstOrCreate` fallback by display name MUST be removed entirely.

#### Scenario: Pivot update uses findOrFail for deudor

- GIVEN `buildPivotUpdateFields()` processes a deudor scoped relation
- WHEN generating the update code
- THEN the output MUST contain `Cliente::findOrFail($data['deudor_Cliente_id'])`
- AND MUST NOT contain `firstOrCreate`
