# Archive Report: generator-pivot-name-lookup

**Archived**: 2026-05-21
**Status**: Verified PASS
**Artifact Store**: Hybrid (Engram + OpenSpec)

## Source Artifacts (Traceability)

| Artifact | Engram ID | File Path |
|----------|-----------|-----------|
| Proposal | #54 | `openspec/changes/archive/2026-05-21-generator-pivot-name-lookup/proposal.md` |
| Spec | #55 | `openspec/changes/archive/2026-05-21-generator-pivot-name-lookup/specs/{domain}/spec.md` |
| Design | #56 | (Engram only) |
| Tasks | N/A (filesystem) | `openspec/changes/archive/2026-05-21-generator-pivot-name-lookup/tasks.md` |
| Apply Progress | #57 | (Engram only) |
| Verify Report | #59 | (Engram only) |

## Delta Spec Sync

### Domain: pivot-relation
**Action**: Updated
- **Added**: 1 requirement — Inline edit forms MUST include hidden FK input for scoped relations
- **Modified**: 1 requirement — Controller MUST resolve scoped pivot by name OR by ID (three input modes: hidden FK ID, select ID, text name with firstOrCreate fallback)

### Domain: crud-generator
**Action**: Updated
- **Modified**: 3 requirements
  - Select field name for scoped relations MUST match hidden FK key
  - Validation for scoped FK inputs MUST be conditional (sometimes|nullable instead of required)
  - Pivot model instantiation MUST use absolute namespace (leading backslash)
- **Removed**: 1 requirement — Text-only buscador input validation without consumption (replaced by name resolution logic)

**Synced to**:
- `openspec/specs/pivot-relation/spec.md`
- `openspec/specs/crud-generator/spec.md`

## Final Implementation Summary

Fixed 5 bugs in the CRUD generator's handling of scoped (pivot-table) relations:

1. **Select name mismatch** (Bug #1): Select `name` attribute for scoped relations now uses `{relationName}_{scopedTargetFk}` instead of `{relationName}` or `{fk_column}`.
2. **Strict validation** (Bug #2): Scoped hidden FK validation changed from `required|integer|exists` to `sometimes|nullable|integer|exists`.
3. **Missing hidden inputs** (Bug #3): Inline edit forms now include hidden FK inputs for scoped relations, populated by buscador onSelect.
4. **Namespace crash** (Bug #4): Pivot model instantiation now uses absolute namespace with leading backslash (`\App\Models\...`).
5. **Unused text buscador** (Bug #5): Added `firstOrCreate` fallback for buscador text input when no hidden FK ID is provided.

**Files changed**:
- `app/Generator/Rendering/StubRenderer.php` (4 methods: `buildValidationRules`, `buildPivotStoreFields`, `buildPivotUpdateFields`, `buildEditFormFields`)
- `stubs/fragments/create-field-fk-select.stub`
- `stubs/component-inline-relation-fk.stub`
- `tests/Unit/GeneratorPivotNameLookupTest.php` (created, 16 tests)
- Regenerated cobro views (`create.blade.php`, `edit.blade.php`, `show.blade.php`, `modal/create.blade.php`)

## Test Results

- **Total tests passing**: 72
- **New unit tests**: 16 (all passing)
- **Incident-fix verification tests**: 2 (restored `buildPivotUpdateFields()` + placeholder resolution)
- **Stale test fix**: 1 (`BuscadorScopedRelationsTest` updated to assert `sometimes|nullable` instead of old `required` rule)
- **Pre-existing failures**: 6 (unrelated to this change; 4 in `GeneratorUniversalidadTest`, 1 in `ClienteConstraintMessagesTest`, 1 in `ExampleTest`)
- **Build**: Passed (PHP syntax valid; generated views compile without syntax errors)

**Spec compliance**: 21/21 scenarios compliant.

## Incident Log

### Incident #1: `{{scoped_fk_name}}` placeholder not replaced in generated views
- **Discovered during**: Verification phase (runtime inspection of generated cobro views)
- **Impact**: Generated create views contained literal `name="{{scoped_fk_name}}"` instead of resolved `name="deudor_Cliente_id"`
- **Root cause**: `buildCreateFormFields` computed `$scopedFkName` but did not add it to the `$vars` array passed to `renderFragment`, so the stub's `{{scoped_fk_name}}` token was left unreplaced.
- **Fix**: Added `$scopedFkName` to the `$vars` array in `buildCreateFormFields`.
- **Verification**: Grep of regenerated cobro views confirmed no literal `{{scoped_fk_name}}` remains; select elements show correct resolved names.

### Incident #2: Extra braces in generated controllers (accidentally deleted `buildPivotUpdateFields()`)
- **Discovered during**: Verification phase (tests failing with missing method + PHP compile errors on generated CobroController)
- **Impact**: Generated `CobroController` had unbalanced braces and referenced a non-existent `buildPivotUpdateFields()` method, causing fatal compile errors.
- **Root cause**: During the apply phase, `buildPivotUpdateFields()` was accidentally deleted from `StubRenderer.php` while editing nearby `buildPivotStoreFields()`. The `renderController()` template still called the method, producing broken generated code.
- **Fix**: Restored the accidentally deleted `buildPivotUpdateFields()` method in `StubRenderer.php` with the correct leading-backslash namespace fix and firstOrCreate fallback.
- **Verification**: 
  - `php -l` on generated `CobroController.php` reports no syntax errors.
  - Brace balance check: 50 open / 50 close — perfectly balanced.
  - 9 previously failing tests (that depended on `buildPivotUpdateFields()`) now pass.

## Rollback Instructions

1. Revert the 3 implementation files to pre-change state:
   - `app/Generator/Rendering/StubRenderer.php`
   - `stubs/fragments/create-field-fk-select.stub`
   - `stubs/component-inline-relation-fk.stub`
2. Delete `tests/Unit/GeneratorPivotNameLookupTest.php`
3. Regenerate the Cobro CRUD scaffold from the reverted generator:
   ```bash
   php artisan make:crud Cobro --force
   ```
4. No database migrations to roll back.

## Lessons Learned

1. **Placeholder propagation**: When adding a new stub placeholder (`{{scoped_fk_name}}`), verify it is registered in the `$vars` array of *every* rendering path that uses the stub. The incident fix taught us that create and edit forms may have divergent variable assembly.
2. **Method deletion guard**: When editing large rendering methods, accidental deletion of adjacent methods is easy. A CI check that reflects on `StubRenderer` to confirm all methods referenced in `renderController()` actually exist would have caught the missing `buildPivotUpdateFields()` immediately.
3. **Stale test debt**: Changing validation rules from `required` to `sometimes|nullable` broke an existing test that asserted the old rule. Tests that hardcode generated output strings are brittle; they must be updated alongside the generator change or replaced with pattern assertions.
4. **Namespace context sensitivity**: Generated controllers live in `App\Http\Controllers\Crud`. Any inline model instantiation MUST use absolute namespaces. This was a fatal, not cosmetic, bug.
5. **Buscador text consumption**: Text inputs that are validated but not consumed in store/update logic create silent failures. The fix (`firstOrCreate` fallback) aligns the generator with existing buscador behavior for standard FKs.
6. **Verification coverage**: Runtime inspection (grep of generated views + PHP lint + brace balance) is a necessary complement to unit tests for stub-based generators. Unit tests validate the renderer; runtime inspection validates the end-to-end pipeline.

## SDD Cycle Complete

The change has been fully planned, implemented, verified, and archived.
Ready for the next change.
