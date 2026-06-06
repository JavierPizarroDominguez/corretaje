# Proposal: Property Select Fallback to Text Input for New/Empty Users

## Intent

When the arrendador (landlord) is a new user not yet in the database, or when a selected arrendador has no properties, the user sees an empty `<select>` dropdown in Step 3 with only "Seleccionar propiedad..." and "➕ Agregar nueva propiedad" — neither useful. The text input for typing a property address should be shown directly instead, so users can immediately enter the address without extra clicks.

## Scope

### In Scope
- Detect new-user scenario (no `hidden-arrendador-id`) when "Añadir" is clicked on Step 1
- Hide `#propiedadSelect` and show `#nuevaPropiedadInput` automatically for new users
- Reset Step 3 state properly when the user goes back and selects an existing arrendador
- Ensure `validateStep(3)` works correctly when the text input is shown instead of the select

### Out of Scope
- Changing the buscador autocomplete component
- Modifying the backend API or validation rules
- Changing the `propiedadCorredor` checkbox behavior (already works — sets id=1, properties load)
- Adding mobile-specific layout changes beyond the select/input show/hide logic

## Capabilities

### New Capabilities
None.

### Modified Capabilities
- `administracion-wizard`: Step 3 property selection must show a text input instead of an empty select when the arrendador is new (not in DB) or has no properties. The existing empty-array and error fallback paths in `loadPropiedadesPorArrendador()` are extended to also cover the "no ID" case.

## Approach

Add a check in the `btnAddArrendador` click handler and in `callWizardNextStep`/`validateStep(1)`: when Step 1 validation passes and `hidden-arrendador-id` is empty (meaning a new client name was typed, not selected from buscador), directly manipulate Step 3's DOM to hide `#propiedadSelect` and show `#nuevaPropiedadInput`.

This reuses the existing show/hide pattern already implemented in `loadPropiedadesPorArrendador()` (lines 330–377 of `create.blade.php`) where `data.length === 0` hides the select and shows the input. The new logic triggers the same visual state without calling the API, since there's no arrendador ID to query.

Additionally, reset Step 3 when the arrendador changes: if `buscador` `onSelect` fires (existing user selected), call `loadPropiedadesPorArrendador(id)` which naturally shows the select. If the user goes back to Step 1 and changes the name, Step 3 should reset to the appropriate state.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `resources/views/administracion/create.blade.php` | Modified | `btnAddArrendador` handler + new `showTextInputForNewPropiedad()` helper |
| `resources/views/administracion/partials/step-03-propiedad.blade.php` | Modified | No structural HTML change needed — existing `#nuevaPropiedadInput` is already there |
| `resources/views/administracion/partials/step-01-arrendador.blade.php` | Unchanged | No changes needed to the partial itself |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| User selects existing arrendador, then goes back and types a new name — Step 3 still shows select from previous selection | Medium | On arrendador input change/clear, reset Step 3 state (hide select, clear options) |
| `validateStep(3)` fails when select is hidden because `sel.value` is empty but text input has content | Low | Current validation at line 1095 already handles `!sel.value && !inp.value.trim()` — and when select is hidden, `sel.value` will be empty, but the text input path will have content |
| Mobile layout breaks with text input alone | Low | Text input uses `form-control` with `mt-2`, same width as select — `col-md-6` already constrains both identically |

## Rollback Plan

Remove the `showTextInputForNewPropiedad()` helper and its call from `btnAddArrendador`/`validateStep(1)`. The existing `loadPropiedadesPorArrendador()` fallback paths remain untouched and continue to work for the empty-properties and error cases.

## Dependencies

- None beyond the existing codebase.

## Success Criteria

- [ ] When user types a new arrendador name (no `hidden-arrendador-id`), Step 3 shows the text input directly instead of an empty select
- [ ] When user selects an existing arrendador with properties, Step 3 shows the populated select as before
- [ ] When user selects an existing arrendador with NO properties, Step 3 shows the text input (existing behavior preserved)
- [ ] `validateStep(3)` works correctly in both select and text input modes
- [ ] `updateResumen()` correctly displays the typed address when text input is active