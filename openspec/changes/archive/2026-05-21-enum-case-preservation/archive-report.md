# Archive Report: enum-case-preservation

**Archived**: 2026-05-21
**Status**: Verified PASS
**Artifact Store**: Hybrid (Engram + OpenSpec)

## Source Artifacts (Traceability)

| Artifact | Engram ID | File Path |
|----------|-----------|-----------|
| Proposal | #64 | `openspec/changes/archive/2026-05-21-enum-case-preservation/proposal.md` |
| Spec | #65 | `openspec/changes/archive/2026-05-21-enum-case-preservation/specs/crud-generator/spec.md` |
| Design | #66 | (Engram only) |
| Tasks | #67 | (Engram only) |
| Apply Progress | #68 | (Engram only) |
| Verify Report | #69 | (Engram only) |

## Delta Spec Sync

### Domain: crud-generator
**Action**: Updated
- **Added**: 2 requirements
  - ENUM values MUST preserve original database casing (with 3 scenarios: create view, show view, mixed-case values)
  - Boolean TINYINT(1) detection MUST remain case-insensitive (with 3 scenarios: lowercase, uppercase, non-boolean)
- **Removed**: 1 implicit behavior — Global lowercase normalization of column types (the `strtolower($row->COLUMN_TYPE)` on line 141 was replaced with targeted lowercase only for boolean detection)

**Synced to**:
- `openspec/specs/crud-generator/spec.md`

## Final Implementation Summary

Fixed ENUM value casing destruction in the CRUD generator's schema introspection layer:

1. **Line 141**: Changed `$colType = strtolower($row->COLUMN_TYPE)` → `$colType = $row->COLUMN_TYPE` — preserves original ENUM value casing from MySQL.
2. **Line 148**: Changed `str_contains($colType, '(1)')` → `str_contains(strtolower($colType), '(1)')` — boolean detection remains case-insensitive.
3. **Line 235**: Changed `str_starts_with($columnType, 'enum(')` → `str_starts_with(strtolower($columnType), 'enum(')` — handles uppercase `ENUM(...)` prefixes from MySQL.

**Files changed**:
- `app/Generator/Introspection/SchemaInspector.php` (3 lines modified)
- `tests/Unit/SchemaInspectorEnumCaseTest.php` (created, 12 unit tests)

## Integration Verification Results

AC-4 (regeneration without errors) was verified manually after the verify phase:

- Regenerated Cobro views (`create.blade.php`, `show.blade.php`) using the fixed generator.
- Confirmed ENUM `<option>` values preserve original database casing:
  - `Ingreso Renta Arrendatario`
  - `Egreso Renta Arrendador`
  - `Devolución Garantía Arrendatario`
- No generator errors or Blade syntax errors during regeneration.
- All other ENUM models (`cliente`, `participante_contrato`, `participante_cobro`) regenerate successfully.

## Final Test Results

- **Total tests passing**: 84 (12 new + 72 existing)
- **New unit tests**: 12 (all passing)
- **Pre-existing failures**: 6 (unrelated to this change)
  - 3 in `GeneratorUniversalidadTest` (missing config keys: `model_namespace`, `months`, `filter_titles`)
  - 1 in `GeneratorUniversalidadTest` (`Undefined array key "number"` in `StubRenderer.php`)
  - 1 in `ClienteConstraintMessagesTest` (session missing errors key)
  - 1 in `ExampleTest` (404 on `/`)
- **Build**: Passed (PHP syntax valid; generated views compile without syntax errors)

**Spec compliance**: 12/12 scenarios compliant.

## Rollback Instructions

1. Revert the single file change:
   - `app/Generator/Introspection/SchemaInspector.php`
   - Restore line 141 to `strtolower($row->COLUMN_TYPE)`
   - Remove the added `strtolower` on line 148
   - Remove the added `strtolower` on line 235
2. Delete `tests/Unit/SchemaInspectorEnumCaseTest.php`
3. Regenerate affected views for all ENUM models:
   ```bash
   php artisan make:crud Cobro --force
   php artisan make:crud Cliente --force
   php artisan make:crud ParticipanteContrato --force
   php artisan make:crud ParticipanteCobro --force
   ```
4. No database migrations to roll back.

## Lessons Learned

1. **Global normalization is dangerous**: Applying `strtolower()` to an entire database metadata string destroys user-defined casing (ENUM values). Normalization should be applied only at the point of comparison, never to raw data that carries semantic meaning.
2. **Targeted case folding**: The fix demonstrates the correct pattern: preserve original casing in the variable, and apply `strtolower()` only inside the specific comparison that needs it (boolean detection, prefix matching).
3. **Generator introspection vs. runtime**: Bugs in `SchemaInspector` affect every generated view. A 3-line fix in introspection propagates to all models with ENUM columns — high leverage, but also high blast radius if wrong.
4. **Integration verification matters**: AC-4 required running the actual generator against a real database and inspecting Blade output. Unit tests validate `parseEnumValues` logic, but end-to-end verification is needed to confirm the generator pipeline uses that logic correctly.
5. **Pre-existing test noise**: The 6 pre-existing failures in `GeneratorUniversalidadTest`, `ClienteConstraintMessagesTest`, and `ExampleTest` are unrelated but create noise in CI. They should be tracked as separate tech-debt issues.

## SDD Cycle Complete

The change has been fully planned, implemented, verified, and archived.
Ready for the next change.
