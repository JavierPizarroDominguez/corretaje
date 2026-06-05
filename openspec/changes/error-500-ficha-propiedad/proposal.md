# Proposal: Fix HTTP 500 on /propiedad/ficha/{id} — Pivot Table Casing

## Intent

Fix HTTP 500 error on `GET /propiedad/ficha/{id}` caused by case-sensitive pivot table name mismatch. The `belongsToMany` relationship references `'transaccion_cobro'` (lowercase) but the actual table is `Transaccion_Cobro` (PascalCase), consistent with the project's DB schema convention.

## Scope

### In Scope
- Correct pivot table case in `app/Models/Transaccion.php` line 67
- Correct pivot table case in `app/Models/Cobro.php` line 113

### Out of Scope
- Try/catch wrappers or graceful degradation in controller
- Database schema changes or migrations
- Any other route or controller
- Style refactors or code quality improvements

## Capabilities

> Bug fix — no spec-level behavior changes.

### New Capabilities
None

### Modified Capabilities
None

## Approach

Change the hardcoded pivot table name in both `belongsToMany` relationship definitions from `'transaccion_cobro'` to `'Transaccion_Cobro'` to match the database table as defined in `corretaje-bd.sql`. No other changes needed — the table already exists, and the query logic works correctly once the name resolves.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Models/Transaccion.php:67` | Modified | Change `'transaccion_cobro'` → `'Transaccion_Cobro'` |
| `app/Models/Cobro.php:113` | Modified | Change `'transaccion_cobro'` → `'Transaccion_Cobro'` |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Regression in other Transaccion/Cobro queries | Very Low | Both relationships use the same pivot table; fixing both ensures consistency |
| MySQL `lower_case_table_names=1` makes change invisible in tests | Low | Verify on the production-like DB (MySQL on Linux, case-sensitive) |

## Rollback Plan

Revert the two string changes. The previous lowercase values were not causing errors on case-insensitive filesystems but are technically incorrect.

## Dependencies

None.

## Success Criteria

- [ ] `GET /propiedad/ficha/1` returns HTTP 200 with full page render
- [ ] `Transaccion::cobros` relationship resolves correctly
- [ ] `Cobro::transaccions` relationship resolves correctly
- [ ] All PHPUnit tests pass
