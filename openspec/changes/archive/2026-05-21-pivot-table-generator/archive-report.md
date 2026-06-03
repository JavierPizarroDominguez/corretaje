# Archive Report: pivot-table-generator

**Change**: pivot-table-generator
**Archived**: 2026-05-21
**Status**: Verified PASS — all 8 spec scenarios compliant, 27 tests pass, no regressions.

---

## Artifact Traceability

| Artifact | Store | ID/Path |
|----------|-------|---------|
| Proposal | Engram | #45 — `sdd/pivot-table-generator/proposal` |
| Spec | Engram | #46 — `sdd/pivot-table-generator/spec` |
| Design | Engram | #47 — `sdd/pivot-table-generator/design` |
| Tasks | Engram | #48 — `sdd/pivot-table-generator/tasks` |
| Apply Progress | Engram | #49 — `sdd/pivot-table-generator/apply-progress` |
| Verification Report | Engram | #50 — `sdd/pivot-table-generator/verify-report` |
| Archive Report | Engram | `sdd/pivot-table-generator/archive-report` (this observation) |
| Proposal | Filesystem | `openspec/changes/archive/2026-05-21-pivot-table-generator/proposal.md` |
| Specs (crud-generator delta) | Filesystem | `openspec/changes/archive/2026-05-21-pivot-table-generator/specs/crud-generator/spec.md` |
| Specs (pivot-relation delta) | Filesystem | `openspec/changes/archive/2026-05-21-pivot-table-generator/specs/pivot-relation/spec.md` |

---

## Delta Specs Synced

### Domain: crud-generator
- **Action**: Updated — 1 requirement added
- **Added**: `Requirement: View data generation MUST include scoped pivot columns`
  - 5 scenarios added: buildFkData scoped deudor, buildFkCompact dedup, buildFkCompactArray compact lines, direct FK unchanged, zero diff for tables without scoped relations
- **Preserved**: All 10 pre-existing requirements remain intact

### Domain: pivot-relation
- **Action**: Updated — 1 requirement added
- **Added**: `Requirement: Scoped pivot relations MUST generate view data identically to direct FK`
  - 3 scenarios added: deudor produces cliente view data, multiple scoped relations dedup, mixed direct FK + scoped relations
- **Preserved**: All 6 pre-existing requirements remain intact

---

## Final Implementation Summary

### What Changed
Removed the `sqlType === 'special_relation'` guard from three `StubRenderer` methods responsible for generating FK view data in controllers:

| File | Line | Method | Change |
|------|------|--------|--------|
| `app/Generator/Rendering/StubRenderer.php` | ~1366 | `buildFkData()` | Removed `\|\| $col->sqlType === 'special_relation'` from guard |
| `app/Generator/Rendering/StubRenderer.php` | ~1435 | `buildFkCompact()` | Removed `\|\| $col->sqlType === 'special_relation'` from guard |
| `app/Generator/Rendering/StubRenderer.php` | ~1462 | `buildFkCompactArray()` | Removed `\|\| $col->sqlType === 'special_relation'` from guard |

### Tests Added
| Test | File | Purpose |
|------|------|---------|
| `test_buildFkData_generates_cliente_view_data_for_scoped_relation()` | `tests/Unit/GeneratorUniversalidadTest.php` | Verifies `$clienteCount`/`$clienteOptions` emitted for scoped `deudor` |
| `test_buildFkCompact_includes_scoped_relation_variables_once()` | `tests/Unit/GeneratorUniversalidadTest.php` | Verifies dedup produces exactly one entry per target model |
| `test_buildFkCompactArray_generates_one_compact_line_for_scoped_relation()` | `tests/Unit/GeneratorUniversalidadTest.php` | Verifies compact line emitted for scoped relation |

### Scope Enforcement
- **In scope**: Scoped pivot relations (`special_relation` columns) view data generation
- **Out of scope**: `belongsToMany` (Pattern C), `withPivot` extra fields, buscador JS, config flags, other `special_relation` guards in StubRenderer

---

## Test Results

| Metric | Value |
|--------|-------|
| Total tests | 27 (24 pre-existing + 3 new) |
| Passed | 27 |
| Failed | 0 |
| Skipped | 0 |
| Assertions | 56 |
| Duration | 2.41s |
| Coverage | Not available (no tooling configured) |

### Spec Compliance
| Requirement | Scenarios | Result |
|-------------|-----------|--------|
| View data generation MUST include scoped pivot columns | 5/5 | ✅ COMPLIANT |
| Scoped pivot relations MUST generate view data identically to direct FK | 3/3 | ✅ COMPLIANT |
| **Total** | **8/8** | **✅ PASS** |

---

## Rollback Instructions

1. Re-add `|| $col->sqlType === 'special_relation'` to the three guard clauses in `app/Generator/Rendering/StubRenderer.php`:
   - `buildFkData()` at ~L1366
   - `buildFkCompact()` at ~L1435
   - `buildFkCompactArray()` at ~L1462
2. Re-run generator for affected controllers (`CobroController`, `ContratoController`) to revert view data output.
3. Remove the 3 new tests from `tests/Unit/GeneratorUniversalidadTest.php` if full revert is desired.

**Risk**: Low — no data loss; views revert to previous behavior (missing scoped pivot dropdown data).

---

## Lessons Learned

1. **Guard removal can be surgical**: Removing a single boolean condition in three places unlocked a full feature category (scoped pivot view data) without touching any other logic. The existing `$seen` dedup mechanism handled the complexity of multiple relations to the same model.

2. **Synthetic ColumnMetadata is sufficient**: No new DTO or service was needed because `buildScopedColumn()` already populates `referencedTable`, `relatedModelVariable`, and `relationDisplayField` on the synthetic `ColumnMetadata`. The view data methods only needed to stop skipping it.

3. **Diff-based regression testing is powerful**: The zero-diff guarantee for tables without `special_relation` columns gives high confidence that a small guard removal doesn't break existing behavior.

4. **Unit tests with helpers scale well**: Reusing `makeSchemaWithScopedRelation()` and adding `makeScopedRelationColumn()` kept test code DRY and focused on the behavior change, not setup boilerplate.

---

## SDD Cycle Complete

This change has been fully planned, implemented, verified, and archived. Ready for the next change.
