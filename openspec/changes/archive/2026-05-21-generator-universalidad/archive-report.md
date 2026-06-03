# Archive Report: generator-universalidad

**Archived**: 2026-05-21
**Source**: `openspec/changes/generator-universalidad/`
**Destination**: `openspec/changes/archive/2026-05-21-generator-universalidad/`

## Summary

Pure refactor of the CRUD generator to remove hardcoded assumptions (`App\Models\` namespace, `_cliente_id` FK suffix, pivot name heuristics, Spanish UI strings) and replace them with config-driven defaults. All changes are self-contained in generator source files — no migrations, no DB changes, no generated file updates.

## What Was Accomplished

1. **Config-driven model namespace** — Added `model_namespace` to `config/generator.php` with default `'App\\Models\\'`. Replaced all hardcoded `\App\Models\` references (10 locations) with `config()` or `getModelNamespace()` calls.

2. **Dynamic FK suffix** — Replaced hardcoded `_cliente_id` in `filter-field-scoped.stub`, `buildFilterConditions()`, and `renderFilterScopedField()` with dynamic `{{target_fk}}` / `filter_fk` from `ColumnMetadata::scopedTargetFk`.

3. **Composite-PK pivot detection** — Replaced `str_contains('participante'/'contrato'/'item')` name heuristic in `resolveEagerLoadStrategy()` with `isPivotTable()` (composite PK structural check), matching the approach already used by `getScopedRelations()`.

4. **UI string extraction** — Moved month names and filter section titles from hardcoded arrays in `StubRenderer` to `config/generator.php` with Spanish defaults.

5. **`guessDisplayField()` reorder** — Reordered candidate field list to prioritize `name` over `nombre`, making the generator language-neutral by default.

## Specs Synced

| Domain | Action | Details |
|--------|--------|---------|
| crud-generator | Updated | 6 new requirements appended (model namespace, dynamic FK, filter FK, guessDisplayField reorder, UI strings configurable, PlaceholderRegistry preserved) — total 10 requirements |
| pivot-relation | Updated | 2 new requirements appended (composite-PK pivot detection, structural detection consistency) — total 6 requirements |
| buscador | No change | Filter field changes covered in crud-generator spec; no buscador delta needed |

## Archive Contents

| Artifact | Path |
|----------|------|
| Proposal | `proposal.md` |
| Design | `design.md` |
| Tasks | `tasks.md` |
| Verify Report | `verify-report.md` |
| Delta Spec: crud-generator | `specs/crud-generator/spec.md` |
| Delta Spec: pivot-relation | `specs/pivot-relation/spec.md` |
| Archive Report | `archive-report.md` |

## Verification Outcome

**PASS WITH WARNINGS** — 24/24 change-specific tests pass (46 assertions), full Unit suite 56/56 pass. Spec compliance: 15/16 scenarios (93.75%) COMPLIANT, 1 PARTIAL. The sole PARTIAL (filter title Spanish defaults through renderer) is a minor gap mitigated by config file verification. No CRITICAL code issues. The only CRITICAL flag was a missing apply-progress artifact (documentation gap, not code).

## Tasks Status

20/21 tasks complete. Task 5.4 (PlaceholderRegistry optional config refactor) was explicitly optional and unchecked — preservation of existing behavior confirmed.

## Source of Truth Updated

- `openspec/specs/crud-generator/spec.md` — now includes configurable namespace, dynamic FK, structural pivot detection, configurable UI strings
- `openspec/specs/pivot-relation/spec.md` — now includes composite-PK pivot detection requirements
