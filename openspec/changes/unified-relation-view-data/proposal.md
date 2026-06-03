# Proposal: Unified Relationship View Data Provider

## Intent

Close the gap where scoped pivot (Pattern B) and belongsToMany (Pattern C) relations lack view data (`$xCount`, `$xOptions`). `buildFkData()` explicitly skips `special_relation` columns and has no belongsToMany awareness — forms with buscadores render, but views crash or show empty data because related records aren't loaded.

## Scope

### In Scope
- `RelationshipViewDataProvider` service handling belongsTo, scoped pivot (hasOne+where), and belongsToMany uniformly
- Refactor `buildFkData()` / `buildFkCompactArray()` to consume the provider
- Add belongsToMany view data to form field generators (create, edit, buscador calls)
- Surface belongsToMany metadata through SchemaBuilder → TableSchema → ColumnMetadata
- N+1-safe querying for belongsToMany (eager-loadable)

### Out of Scope
- Pivot `withPivot` extra-field generation (validation, form fields)
- Regenerating existing controllers/views proactively
- Buscador JS changes
- Config-level opt-in flags per entity (must be universal)

## Capabilities

### New Capabilities
None — pure extension of existing `crud-generator`.

### Modified Capabilities
- `crud-generator`: View data generation MUST include scoped pivot (Pattern B) and belongsToMany (Pattern C) relations — not just direct belongsTo FK columns. The `RelationshipViewDataProvider` MUST be the single source of truth for all relationship view data.

## Approach

Unified `RelationshipViewDataProvider`. A new service encapsulates all relationship-to-view-data logic:
1. **belongsTo FK** — reads `referencedTable`, `relatedModelName` from ColumnMetadata (same as current `buildFkData()`)
2. **Scoped pivot** — reads `special_relation` columns with `pivotModel` set, resolves target via RelationResolver
3. **belongsToMany** — iterates `RelationResolver::getBelongsToManyRelations()`, generates count/options with eager-loaded pivot data

StubRenderer passes schema metadata to the provider and consumes returned view data arrays. Form field builders call the provider instead of inline lookup logic.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Generator/Rendering/RelationshipViewDataProvider.php` | **New** | Unified view data service |
| `app/Generator/Rendering/StubRenderer.php` | Modified | Refactor `buildFkData()` and form field builders |
| `app/Generator/Schema/TableSchema.php` | Modified | Expose belongsToMany list |
| `app/Generator/Introspection/ColumnMetadata.php` | Modified | belongsToMany metadata fields |
| `app/Generator/Schema/SchemaBuilder.php` | Modified | Surface belongsToMany in `build()` |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Regressions in existing belongsTo FK output | Low | Diff before/after output for existing tables |
| N+1 queries for belongsToMany | Medium | Use `withCount()` / eager load in provider |
| Missing edge case in scoped pivot target resolution | Low | Reuse existing RelationResolver logic |

## Rollback Plan

Remove `RelationshipViewDataProvider.php`, revert StubRenderer refactors. `buildFkData()` returns to skipping `special_relation`. No data loss — generated views simply lack scoped/belongsToMany view data as before.

## Dependencies

None — works with current ConfigLoader, RelationResolver, SchemaBuilder APIs.

## Success Criteria

- [ ] `RelationshipViewDataProvider` produces correct `$xCount`/`$xOptions` for all three pattern types on Cobro and Contrato entities
- [ ] `buildFkData()` refactor produces identical output for existing belongsTo FK columns (diff-tested)
- [ ] Generated views for entities with belongsToMany (Cobro↔Transaccion) include `$transaccionCount` and `$transaccionOptions`
- [ ] Generated views for entities with scoped pivots (Cobro.deudor) include `$clienteCount` and `$clienteOptions`
- [ ] No N+1 — belongsToMany view data uses `withCount()` or eager load
