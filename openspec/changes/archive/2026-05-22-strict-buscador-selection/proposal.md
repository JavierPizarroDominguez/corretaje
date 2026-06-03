# Proposal: Strict Buscador Selection

## Intent

The buscador (autocomplete) component has five interdependent bugs that together break its core purpose: the user selects an entity from search results, and the FK ID is submitted with the form. Currently: (1) the API omits `id` so `onSelect` gets `undefined`, (2) `onSelect` never sets the hidden FK input for regular (non-scoped) relations, (3) no hidden input exists in the buscador stub, (4) `firstOrCreate` creates incomplete records on free-text, and (5) validation permits free-text without any FK ID. The buscador must be strict: selection-only, never creation.

## Scope

### In Scope
- Add `'id' => $item->id` to every result array in `buscador-block.stub`
- Add hidden FK input (`<input type="hidden">`) to `create-field-fk-buscador.stub`
- Add hidden-input assignment to ALL `onSelect` callbacks (not just scoped) in `create-buscador-call.stub` and `buildCreateBuscadorCalls()`
- Replace `firstOrCreate` fallback with `findOrFail` (requires hidden FK ID) in `store-field-relation-buscador.stub` and `buildPivotStoreFields()` / `buildPivotUpdateFields()`
- Change validation rules for buscador FK fields: hidden FK input becomes `required_with:{buscador_input_name}|integer|exists:{table},id`; text input becomes `sometimes|nullable|string`
- Clear hidden input when user clears the visible input (buscador.js)

### Out of Scope
- Regenerating existing controllers/views (manual regeneration after fix)
- Refactoring the stub template system
- Adding E2E/integration tests (unit tests only)
- Changing the buscador JS callback contract beyond the additions above

## Capabilities

### New Capabilities
None

### Modified Capabilities
- `buscador`: API response MUST include `id` in every result item. `onSelect` MUST set hidden ID input for ALL buscador fields (not just scoped). Hidden input MUST be cleared when visible input is cleared. Validation MUST enforce that a FK ID accompanies any submitted buscador text.
- `crud-generator`: `buildCreateBuscadorCalls()` MUST always emit `document.getElementById('input-create-{field}-id').value = item.id`. `buildValidationRules()` MUST generate `required_with:{buscador_input_name}|integer|exists:{table},id` for the hidden FK input. `store-field-relation-buscador.stub` MUST use `findOrFail` instead of `firstOrCreate`. `buildPivotStoreFields()` and `buildPivotUpdateFields()` MUST remove `firstOrCreate` fallback.
- `pivot-relation`: "Controller MUST resolve scoped pivot by name OR by ID" requirement changes: text-only name resolution (`firstOrCreate`) is removed. The controller MUST require an integer FK ID from the hidden input. No entity creation from free text.

## Approach

1. Fix `buscador-block.stub` — add `'id' => $item->id` to both the direct and relation result arrays builders.
2. Add hidden `<input type="hidden" name="{{fk_column}}" id="input-create-{{field_id}}-id">` inside `create-field-fk-buscador.stub`.
3. Update `create-buscador-call.stub` to always set the hidden ID input in `onSelect`.
4. Remove the scoped-relation guard in `buildCreateBuscadorCalls()` (lines 633-635) — always emit the hidden-input assignment.
5. Replace `firstOrCreate` with `findOrFail` in `store-field-relation-buscador.stub` and `buildPivotStoreFields()` / `buildPivotUpdateFields()`.
6. Tighten validation: hidden FK becomes `required_with:{buscador_text_input}|integer|exists:{table},id`.
7. In `buscador.js`, add logic to clear the hidden input when the visible input is emptied.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `stubs/fragments/buscador-block.stub` | Modified | Add `id` to API response arrays |
| `stubs/fragments/create-buscador-call.stub` | Modified | Add hidden input assignment in `onSelect` |
| `stubs/fragments/create-field-fk-buscador.stub` | Modified | Add hidden `<input type="hidden">` |
| `stubs/fragments/store-field-relation-buscador.stub` | Modified | `firstOrCreate` → `findOrFail` |
| `app/Generator/Rendering/StubRenderer.php:611-643` | Modified | `buildCreateBuscadorCalls()` — always set hidden input |
| `app/Generator/Rendering/StubRenderer.php:896-951` | Modified | `buildValidationRules()` — require hidden FK with buscador text |
| `app/Generator/Rendering/StubRenderer.php:1056-1120` | Modified | `buildPivotStoreFields()` — remove `firstOrCreate` fallback |
| `app/Generator/Rendering/StubRenderer.php:1128+` | Modified | `buildPivotUpdateFields()` — remove `firstOrCreate` fallback |
| `public/js/buscador.js` | Modified | Clear hidden input when visible input emptied |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Existing controllers still use `firstOrCreate` — won't get fix until regenerated | High | Document regeneration requirement; no migration needed |
| Users who type without selecting get 422 validation error | Medium | Intentional — buscador is selection-only. JS clears hidden input on backspace for clear UX |
| `findOrFail` throws 404 if hidden input tampered | Low | Hidden FK has `exists:{table},id` rule — validation catches it before controller |

## Rollback Plan

1. Revert all 5 stub files and `StubRenderer.php` to pre-fix state
2. Revert `buscador.js` if modified
3. Re-run generator — original output restored
4. No database migrations involved — rollback is code-only

## Dependencies

- Prior change `fix-hidden-input-override` (hidden-input placement fix) should be merged first to avoid merge conflicts in `StubRenderer::buildCreateFormFields()`

## Success Criteria

- [ ] Buscador API response includes `id` for every result item
- [ ] `onSelect` sets hidden FK input for ALL buscador fields (contrato, servicio, propiedad, unidad, deudor, acreedor)
- [ ] Buscador stub generates hidden `<input type="hidden">` on the form
- [ ] Submitting buscador text without a hidden FK ID returns validation error
- [ ] No `firstOrCreate` remains in any generated store/update code
- [ ] Clearing the visible buscador input clears the hidden FK input