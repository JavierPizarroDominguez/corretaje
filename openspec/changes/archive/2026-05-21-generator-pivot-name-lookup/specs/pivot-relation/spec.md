# Delta for Pivot Relation

## ADDED Requirements

### Requirement: Inline edit forms MUST include hidden FK input for scoped relations

When rendering the show/edit inline form for a scoped relation (deudor, acreedor), the form MUST include a hidden input named `{relationName}_{scopedTargetFk}` (e.g., `deudor_Cliente_id`). The buscador's `onSelect` callback MUST populate this hidden input with the selected entity's ID before form submission.

#### Scenario: Inline edit for deudor includes hidden input

- GIVEN a Cobro show view with inline edit for the deudor scoped relation
- WHEN the inline edit form is rendered
- THEN the form MUST contain `<input type="hidden" name="deudor_Cliente_id" id="deudor_Cliente_id">`
- AND the buscador `onSelect` MUST set this hidden input's value to `item.id`

#### Scenario: Buscador selection populates hidden input before submit

- GIVEN the inline edit form with hidden `deudor_Cliente_id`
- WHEN a user selects a client from the buscador dropdown
- THEN the hidden input value MUST be set to the selected client's ID
- AND the text input `nombre-deudor` MUST display the selected client's name

## MODIFIED Requirements

### Requirement: Controller MUST resolve scoped pivot by name OR by ID

The generated scoped store/update logic MUST accept three input modes for resolving the target entity: (1) hidden FK input `{relationName}_{scopedTargetFk}` with an integer ID, (2) select input with the same name sending an integer ID, or (3) text input `nombre-{relationName}` for name-based lookup. The controller MUST check inputs in this priority order: hidden/select ID first, then text name with `firstOrCreate` fallback.

(Previously: Controller only read `deudor_Cliente_id` via `findOrFail`, ignoring text input entirely)

#### Scenario: Create cobro with buscador-selected deudor (ID input)

- GIVEN a create request with `deudor_Cliente_id = 5` and `nombre-deudor = "Juan Pérez"`
- WHEN the store method processes scoped fields
- THEN it MUST resolve the target via `Cliente::findOrFail(5)`
- AND create a `ParticipanteCobro` with `Cliente_id = 5`, `rol = 'Deudor'`

#### Scenario: Create cobro with select dropdown deudor (ID input)

- GIVEN a create request with `deudor_Cliente_id = 3` (from select, no buscador text)
- WHEN the store method processes scoped fields
- THEN it MUST resolve via `Cliente::findOrFail(3)`
- AND create the pivot record with `Cliente_id = 3`

#### Scenario: Create cobro with text-only buscador (name input)

- GIVEN a create request with `nombre-deudor = "Nuevo Cliente"` and empty `deudor_Cliente_id`
- WHEN the store method processes scoped fields
- THEN it MUST call `Cliente::firstOrCreate(['nombre' => 'Nuevo Cliente'])`
- AND create the pivot record with the resolved or newly created client's ID

#### Scenario: Update cobro changes deudor via buscador

- GIVEN an update request with `deudor_Cliente_id = 10` replacing previous `deudor_Cliente_id = 5`
- WHEN the update method processes scoped fields
- THEN it MUST delete the existing `ParticipanteCobro` for this cobro+rol
- AND create a new `ParticipanteCobro` with `Cliente_id = 10`, `rol = 'Deudor'`

#### Scenario: No input provided — no pivot record created

- GIVEN a create request with both `deudor_Cliente_id` empty and `nombre-deudor` empty
- WHEN the store method processes scoped fields
- THEN it MUST NOT create any `ParticipanteCobro` for the deudor role
- AND the cobro creation MUST succeed without error

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
