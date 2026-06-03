# Proposal: Generator Pivot-Table Name Lookup

## Intent

Five bugs break scoped (pivot-table) relation handling in generated CRUD — namespace errors crash controllers, select inputs send wrong names, validation rejects valid inputs, inline edit lacks hidden fields, and text-only buscador input is ignored. The controller must resolve `ParticipanteCobro` by name (buscador) **or** by ID (select).

## Scope

### In Scope
- Fix select input name mismatch for scoped relations
- Fix overly strict validation (`required` → conditional)
- Add hidden inputs for scoped relations in inline edit forms
- Fix namespace bug (`App\Models\X` → `\App\Models\X`)
- Add `firstOrCreate` by name fallback for text-only buscador input

### Out of Scope
- Standard FK relations (already working)
- `belongsToMany` relations
- Morph/polymorphic relations
- Buscador JS component changes

## Capabilities

### New Capabilities
None

### Modified Capabilities
- `pivot-relation`: Input name normalization, conditional validation, namespace fix, name-based lookup requirement
- `crud-generator`: Select stub name for scoped relations, inline edit hidden input, buscador `onSelect` for scoped relations, store/update fallback logic

## Approach

1. **Normalize select `name`**: Change `<select name="{{fk_column}}">` to `<select name="{{fk_column}}_{{scopedTargetFk}}">` for scoped relations (e.g., `deudor_Cliente_id`). Both buscador-hidden and select send the same key → fixes Bugs 1 & 2.
2. **Conditional validation**: Change `required|integer|exists:...` to `sometimes|nullable|integer|exists:...` for scoped FK fields. The store/update logic enforces presence programmatically.
3. **Inline edit hidden input**: Add `<input type="hidden" name="{{fk_column}}_{{scopedTargetFk}}">` to `component-inline-relation-fk.stub` for scoped relations; update buscador `onSelect` to populate it → fixes Bug 3.
4. **Namespace prefix**: Prepend `\` to `{$pivotModel}` in `buildPivotStoreFields()` and `buildPivotUpdateFields()` → fixes Bug 4.
5. **Name-based fallback**: In generated scoped store/update code, add `elseif` branch: if `deudor_Cliente_id` is empty but `nombre-deudor` has text, `firstOrCreate` by display field → fixes Bug 5.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Generator/Rendering/StubRenderer.php` | Modified | Validation rules, pivot store/update, buscador calls |
| `stubs/fragments/create-field-fk-select.stub` | Modified | Select name for scoped relations |
| `stubs/component-inline-relation-fk.stub` | Modified | Hidden input for scoped inline edit |
| Generated controllers/views | Regenerated | CobroController, create.blade, show.blade |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Select name change breaks hand-written forms | Low | All forms are generated; regeneration handles it |
| `firstOrCreate` creates duplicate by similar name | Medium | Same risk as existing FK buscador pattern; accepted |
| Namespace fix changes all generated pivot code | Low | Bug is currently crash-on-use; fix is strictly correct |

## Rollback Plan

Revert the 3 stub/StubRenderer changes and regenerate affected controllers/views. The namespace bug is currently fatal, so rollback returns to the known-broken state.

## Dependencies

- None external

## Success Criteria

- [ ] Generated `<select>` for scoped relations sends `{relation}_{targetFk}` key
- [ ] Controller accepts both ID-based (select/hidden) and name-based (buscador text) input
- [ ] Inline edit form includes hidden input for scoped relation FK
- [ ] No `ClassNotFoundError` for pivot model instantiation
- [ ] Validation passes when either select or buscador provides input
