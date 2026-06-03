# Proposal: Fix Hidden Input Override in Code Generator

## Intent

The CRUD generator produces forms where a hidden `<input>` and a `<select>` share the same `name` attribute (`deudor_Cliente_id`). PHP takes the last value — the empty hidden input wins, silently discarding the user's selection. Two additional bugs compound this: controller variable bleed creates wrong pivot records, and the edit form lacks a hidden input for buscador mode, causing duplicate Client entries.

## Scope

### In Scope
- **BUG-1 (CRITICAL)**: Remove unconditional hidden input after `@if/@else` block in `buildCreateFormFields()`; place hidden input inside the buscador (`@if`) branch only
- **BUG-2 (HIGH)**: Reset `$related{Model}` variable to `null` before each scoped relation block in `buildPivotStoreFields()` and `buildPivotUpdateFields()`
- **BUG-3 (MEDIUM)**: Add hidden input for scoped FK in `buildEditFormFields()` buscador branch, mirroring the create form fix

### Out of Scope
- Refactoring the stub template system (separate change)
- Regenerating existing controllers/views (manual regeneration after fix)
- Adding integration/E2E tests (unit tests only)
- Changing the buscador JS callback contract

## Capabilities

### New Capabilities
None

### Modified Capabilities
- `crud-generator`: Hidden input placement must be conditional (buscador branch only), not unconditional. Edit form must include hidden input for scoped FK in buscador mode. Controller template must isolate `$related{Model}` per scoped relation block.
- `pivot-relation`: Store/update logic must not reuse `$related{Model}` across scoped relation blocks. Each block must start with a clean variable scope.

## Approach

**BUG-1**: In `StubRenderer::buildCreateFormFields()` (lines 467-473), move the hidden input generation inside the buscador stub fragment (`create-field-fk-buscador.stub`) so it only appears when the buscador branch is active. Remove the unconditional `$fields[]` append. The select branch already sends the FK under the same name — no hidden input needed.

**BUG-2**: In `buildPivotStoreFields()` and `buildPivotUpdateFields()`, prepend `$related{TargetModelShort} = null;` before each `if (!empty(...))` block. This ensures stale values from a previous scoped relation don't leak into the next one.

**BUG-3**: In `buildEditFormFields()` (line 534-555), add hidden input generation for scoped relations matching BUG-1's pattern — inside the buscador branch of the edit stub, not unconditional.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Generator/Rendering/StubRenderer.php:467-473` | Modified | Move hidden input from unconditional to buscador-only |
| `app/Generator/Rendering/StubRenderer.php:518-555` | Modified | Add hidden input for scoped FK in edit form buscador branch |
| `app/Generator/Rendering/StubRenderer.php:1049-1112` | Modified | Reset `$related{Model}` before each scoped block in store |
| `app/Generator/Rendering/StubRenderer.php:1120+` | Modified | Reset `$related{Model}` before each scoped block in update |
| `resources/stubs/fragments/create-field-fk-buscador.stub` | Modified | Include hidden input inline in buscador fragment |
| `tests/Unit/BuscadorScopedRelationsTest.php` | Modified | Assert no duplicate hidden input in select mode |
| `tests/Unit/GeneratorPivotNameLookupTest.php` | Modified | Assert variable isolation between scoped blocks |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Regenerated views lose buscador hidden input if stub change is incomplete | Low | Unit test asserts hidden input present in buscador branch |
| Existing generated controllers still have the bleed bug | High | This fix only changes the generator; existing files need manual regeneration — document in release notes |
| Edit stub fragment differs from create stub fragment | Medium | Use same pattern for both; share hidden input generation logic |

## Rollback Plan

1. Revert `StubRenderer.php` to pre-fix state (3 methods: `buildCreateFormFields`, `buildEditFormFields`, `buildPivotStoreFields/UpdateFields`)
2. Revert stub fragment `create-field-fk-buscador.stub` if modified
3. Re-run generator to confirm original output restored
4. No database migrations involved — rollback is code-only

## Dependencies

- None (generator-level fix, no runtime dependency changes)

## Success Criteria

- [ ] Generated `create.blade.php` has exactly ONE input named `deudor_Cliente_id` per scoped relation (no duplicates)
- [ ] When select mode is active, no hidden input with the same name as the select exists
- [ ] When buscador mode is active, hidden input is present inside the buscador branch
- [ ] Generated controller code resets `$related{Model} = null;` before each scoped relation block
- [ ] Generated `edit.blade.php` includes hidden input for scoped FK in buscador branch
- [ ] All existing unit tests pass after fix
