# Delta for CRUD Generator

## MODIFIED Requirements

### Requirement: Select field name for scoped relations MUST match hidden FK key

When `create-field-fk-select.stub` renders a `<select>` for a scoped relation, the `name` attribute MUST be `{relationName}_{scopedTargetFk}` (e.g., `deudor_Cliente_id`), matching the hidden input name used by the buscador. This ensures both input modes send data under the same controller key.

(Previously: Select used `name="{relationName}"` e.g., `name="deudor"`, which did not match the controller's expected `deudor_Cliente_id` key)

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

`buildValidationRules()` MUST generate `sometimes|nullable|integer|exists:{table},id` for scoped relation FK inputs (e.g., `deudor_Cliente_id`), NOT `required`. The actual presence check is performed in the store/update logic via `if (!empty($data['{key}']))`.

(Previously: Validation used `required|integer|exists:...`, causing 422 errors when select mode provided the value but hidden input was empty, or vice versa)

#### Scenario: Validation passes with select-provided ID

- GIVEN a request with `deudor_Cliente_id = 5` (from select, hidden input empty)
- WHEN validation runs
- THEN the rule `sometimes|nullable|integer|exists:cliente,id` MUST pass
- AND the request MUST NOT return a 422 error

#### Scenario: Validation passes with buscador-provided ID

- GIVEN a request with `deudor_Cliente_id = 3` (from buscador onSelect)
- WHEN validation runs
- THEN the rule MUST pass for the integer value

#### Scenario: Validation passes with no input

- GIVEN a request with `deudor_Cliente_id` absent or null
- WHEN validation runs
- THEN the `sometimes|nullable` rule MUST NOT fail
- AND the store logic MUST handle the empty case gracefully

### Requirement: Pivot model instantiation MUST use absolute namespace

`buildPivotStoreFields()` and `buildPivotUpdateFields()` MUST prefix the pivot model class with a leading backslash (`\`) when instantiating inline (e.g., `new \App\Models\ParticipanteCobro()`). This ensures the class resolves correctly regardless of the controller's namespace context.

(Previously: Missing leading backslash caused `App\Models\ParticipanteCobro` to resolve as `App\Http\Controllers\Crud\App\Models\ParticipanteCobro`, throwing ClassNotFoundError)

#### Scenario: Store instantiates pivot model without namespace error

- GIVEN a generated CobroController in namespace `App\Http\Controllers\Crud`
- WHEN the store method executes `new \App\Models\ParticipanteCobro()`
- THEN the class MUST resolve to `App\Models\ParticipanteCobro`
- AND no ClassNotFoundError MUST be thrown

#### Scenario: Update instantiates pivot model without namespace error

- GIVEN a generated CobroController in namespace `App\Http\Controllers\Crud`
- WHEN the update method executes `new \App\Models\ParticipanteCobro()`
- THEN the class MUST resolve correctly
- AND the pivot record MUST be created/updated without error

## REMOVED Requirements

### Requirement: Text-only buscador input validation without consumption

(Reason: The `nombre-{relation}` text input was validated but never consumed by store/update logic. This is replaced by the new "Controller MUST resolve scoped pivot by name OR by ID" requirement in the pivot-relation spec, which adds `firstOrCreate` fallback for text input.)
