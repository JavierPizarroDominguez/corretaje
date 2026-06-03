# Delta for Pivot Relation

## MODIFIED Requirements

### Requirement: Controller MUST resolve scoped pivot by name OR by ID

The generated scoped store/update logic MUST accept two input modes for resolving the target entity: (1) hidden FK input `{relationName}_{scopedTargetFk}` with an integer ID, or (2) select input with the same name sending an integer ID. The controller MUST resolve the target via `findOrFail` using the FK ID. The text-only name resolution mode (`firstOrCreate` by display name) MUST be removed — free-text submissions without a selected FK ID MUST fail validation before reaching the controller.

(Previously: accepted three modes — hidden ID, select ID, and text-only name with `firstOrCreate` fallback)

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

#### Scenario: Update cobro changes deudor via buscador

- GIVEN an update request with `deudor_Cliente_id = 10` replacing previous `deudor_Cliente_id = 5`
- WHEN the update method processes scoped fields
- THEN it MUST delete the existing `ParticipanteCobro` for this cobro+rol
- AND create a new `ParticipanteCobro` with `Cliente_id = 10`, `rol = 'Deudor'`

#### Scenario: No input provided — no pivot record created

- GIVEN a create request with `deudor_Cliente_id` empty and `nombre-deudor` empty
- WHEN the store method processes scoped fields
- THEN it MUST NOT create any `ParticipanteCobro` for the deudor role
- AND the cobro creation MUST succeed without error

## REMOVED Requirements

### Requirement: Controller MUST resolve scoped pivot by name OR by ID (text-only mode)

(Reason: The `firstOrCreate` text-only fallback allowed creation of incomplete records from arbitrary free text. This change enforces selection-only: the user MUST pick from search results so a valid FK ID is always available. Free-text without selection now fails at the validation layer via `required_with`.)

#### Scenario: Create cobro with text-only buscador (name input)

(Previously: `Cliente::firstOrCreate(['nombre' => 'Nuevo Cliente'])` was called when only text was provided. This scenario is now a validation error — 422 response.)
