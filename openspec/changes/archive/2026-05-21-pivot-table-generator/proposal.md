# Proposal: Pivot Table Generator — Scoped Relation View Data

## Intent

Scoped pivot relations (e.g., `Cobro.deudor()` via `participante_cobro`) produce buscador fields and pivot store/update code, but `buildFkData()`, `buildFkCompactArray()`, and `buildFkCompact()` explicitly skip `special_relation` columns. This prevents `$clienteCount` / `$clienteOptions` from reaching `create`/`show` views, causing crashes or empty dropdowns.

## Scope

### In Scope
- Remove `sqlType === 'special_relation'` guard from `buildFkData()` (L1366), `buildFkCompact()` (L1435), and `buildFkCompactArray()` (L1462)
- Verify existing `$seen` dedup prevents duplicate view data when multiple scoped relations target the same model (e.g., deudor→Cliente, acreedor→Cliente)
- Diff generator output before/after for all existing tables to catch regressions

### Out of Scope
- **`belongsToMany` (Pattern C) view data generation** — separate change requiring UI design for multi-select/sync
- `withPivot` extra-field generation (validation, form fields)
- Buscador JS changes
- Config-level opt-in flags per entity
- Other `special_relation` guards in StubRenderer that handle different concerns (L687, L1015, L1175, L1573, L1666, L1780)

## Capabilities

### New Capabilities
None

### Modified Capabilities
- `crud-generator`: View data generation (`buildFkData`, `buildFkCompact`, `buildFkCompactArray`) MUST include scoped pivot (`special_relation`) columns, not skip them. The `$seen` dedup MUST prevent duplicate view data when multiple scoped relations target the same model.
- `pivot-relation`: Scoped pivot relations MUST have their view data (`$xCount`, `$xOptions`) generated identically to direct FK relations, since their `ColumnMetadata` already carries `referencedTable` and `relatedModelName`.

## Approach

Remove the `sqlType === 'special_relation'` condition from the three guard clauses in `buildFkData()`, `buildFkCompact()`, and `buildFkCompactArray()`. No new service or DTO needed — the synthetic `ColumnMetadata` for scoped relations already carries the target model info (`referencedTable='cliente'`, `relatedModelName='Cliente'`). The existing `$seen[$relatedVar]` dedup array prevents duplicate output when both `deudor` and `acreedor` resolve to `relatedModelVariable='cliente'`.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Generator/Rendering/StubRenderer.php` L1366 | Modified | Remove `special_relation` guard from `buildFkData()` |
| `app/Generator/Rendering/StubRenderer.php` L1435 | Modified | Remove `special_relation` guard from `buildFkCompact()` |
| `app/Generator/Rendering/StubRenderer.php` L1462 | Modified | Remove `special_relation` guard from `buildFkCompactArray()` |
| Generated controllers (Cobro, Contrato) | Modified | Will now include `$clienteCount`/`$clienteOptions` |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Regression in existing belongsTo FK output | Low | Diff before/after generator output for all tables |
| Other `special_relation` guards affected | Low | This change targets ONLY three specific guards; others remain untouched |
| Duplicate view data if `$seen` dedup fails | Low | `$seen` already tested for direct FK dedup; scoped relations use same `$relatedVar` key |

## Rollback Plan

Re-add `|| $col->sqlType === 'special_relation'` to the three guard clauses. Generated views revert to lacking scoped pivot view data — no data loss, just missing features as before.

## Dependencies

None — works with current `ColumnMetadata`, `SchemaBuilder`, and `RelationResolver` APIs.

## Success Criteria

- [ ] `buildFkData()` generates `$clienteCount` / `$clienteOptions` for Cobro (deudor + acreedor → Cliente)
- [ ] `buildFkData()` generates `$clienteCount` / `$clienteOptions` for Contrato (arrendador, arrendatario, corredor → Cliente)
- [ ] `$clienteCount` / `$clienteOptions` appears exactly ONCE per model (dedup works)
- [ ] Diff of generated output for tables WITHOUT scoped relations shows zero changes
- [ ] Direct FK view data (contrato, servicio, propiedad, unidad) unchanged
