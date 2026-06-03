# Delta for CRUD Generator

## ADDED Requirements

### Requirement: Edit form MUST emit hidden FK input for buscador-mode scoped relations

When `buildEditFormFields()` processes a scoped relation with buscador mode, it MUST emit a hidden `<input>` with `name="{relationName}_{scopedTargetFk}"` (e.g., `deudor_Cliente_id`) inside the buscador branch. This ensures the buscador's `onSelect` callback can populate the FK before form submission, matching the create form pattern.

#### Scenario: Edit form buscador branch includes hidden input

- GIVEN a scoped relation `deudor` with `scopedTargetFk = 'Cliente_id'` in buscador mode
- WHEN `buildEditFormFields()` renders the edit form
- THEN the buscador branch MUST contain `<input type="hidden" name="deudor_Cliente_id">`
- AND the buscador `onSelect` MUST populate this hidden input

#### Scenario: Edit form select branch has no hidden input

- GIVEN the same scoped relation `deudor` in select mode (small client list)
- WHEN `buildEditFormFields()` renders the edit form
- THEN the select branch MUST NOT contain a hidden input with name `deudor_Cliente_id`
- AND the `<select>` element MUST have `name="deudor_Cliente_id"`

## MODIFIED Requirements

### Requirement: Create form hidden input MUST be conditional on buscador mode

When `buildCreateFormFields()` processes a scoped relation, the hidden `<input>` with `name="{relationName}_{scopedTargetFk}"` MUST only be emitted inside the buscador stub branch. It MUST NOT be emitted unconditionally after the `@if/@else` block. This prevents the hidden input from overriding the `<select>` value when PHP processes form data (last value wins).

(Previously: Hidden input was appended unconditionally after the @if/@else block, causing it to override select values in create forms)

#### Scenario: Select mode produces no hidden input

- GIVEN a scoped relation `deudor` with `scopedTargetFk = 'Cliente_id'` in select mode
- WHEN `buildCreateFormFields()` renders the create form
- THEN the output MUST contain exactly ONE input with `name="deudor_Cliente_id"` (the `<select>`)
- AND there MUST NOT be a hidden `<input>` with the same name

#### Scenario: Buscador mode produces hidden input inside buscador branch

- GIVEN the same scoped relation `deudor` in buscador mode
- WHEN `buildCreateFormFields()` renders the create form
- THEN the buscador branch MUST contain a hidden `<input>` with `name="deudor_Cliente_id"`
- AND this hidden input MUST be inside the buscador stub fragment, not outside the `@if/@else` block

#### Scenario: Multiple scoped relations each have correct input count

- GIVEN two scoped relations `deudor` and `acreedor` both targeting `Cliente`
- WHEN `buildCreateFormFields()` renders the create form
- THEN each relation MUST have exactly ONE input per mode (select or hidden, not both)
- AND no name collision MUST occur between the two relations
