# Proposal: Fix pivot table casing mismatches in Eloquent models

## Intent

Route `GET /cliente/ficha/{id}` throws HTTP 500 because Eloquent `belongsToMany()` calls use lowercase pivot table names (`telefono_cliente`, `clausula_contrato`), but MySQL on Linux resolves table names case-sensitively. The actual database tables use PascalCase (`Telefono_Cliente`, `Clausula_Contrato`). This is a MySQL-on-Linux deployment issue — the dev environment (Windows/Mac with `lower_case_table_names=1`) masks it.

## Scope

### In Scope
- Fix `telefono_cliente` → `Telefono_Cliente` in `Cliente` and `Telefono` models
- Fix `clausula_contrato` → `Clausula_Contrato` in `Contrato` and `Clausula` models
- Expand `PivotTableCasingTest` to cover all 4 pivot relationships
- Run full test suite to confirm no regressions

### Out of Scope
- Other snake_case → PascalCase table references in the codebase (not known to cause errors)
- Renaming actual database tables (the DB is authoritative)
- Adding new capabilities or features to any model

## Capabilities

### New Capabilities
None

### Modified Capabilities
None

## Approach

1. Change 4 pivot table strings in `belongsToMany()` calls across 4 model files
2. Add test methods to `PivotTableCasingTest` for the 4 affected relationships (mirroring the existing `Transaccion_Cobro` pattern)
3. Run `php artisan test` to verify no regressions

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Models/Cliente.php` | Modified | Fix `telefono_cliente` → `Telefono_Cliente` (L99) |
| `app/Models/Telefono.php` | Modified | Fix `telefono_cliente` → `Telefono_Cliente` (L37) |
| `app/Models/Contrato.php` | Modified | Fix `clausula_contrato` → `Clausula_Contrato` (L105) |
| `app/Models/Clausula.php` | Modified | Fix `clausula_contrato` → `Clausula_Contrato` (L41) |
| `tests/Unit/PivotTableCasingTest.php` | Modified | Add 4-6 test methods for new fixes |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Missing other pivot mismatches | Low | Search all `belongsToMany` calls for lowercase snake_case names |
| Existing test broken | Low | Run `php artisan test` before/after |

## Rollback Plan

Revert the 4 model file changes. Tests will fail but production code will work (reverts to current behavior).

## Dependencies

- MySQL on Linux with `lower_case_table_names=0` (proven by the 500 error)

## Success Criteria

- [ ] `GET /cliente/ficha/{id}` loads without 500
- [ ] All 6 pivot table assertions in `PivotTableCasingTest` pass
- [ ] Full `php artisan test` suite passes with no regressions
