# Proposal: Buscador Scoped Relations Fix

## Intent

The CRUD generator produces broken buscador (search/lookup) components for scoped hasOne relations (deudor, acreedor in Cobro). The create modal searches the pivot table instead of the target model, doesn't capture the target FK, and generates incorrect column names. This blocks creating Cobro records via the generated UI.

## Scope

### In Scope
- Fix `buildCreateBuscadorCalls()` to use target model (`Cliente`) not pivot (`participante_cobro`) for search
- Fix generated `onSelect` callback to set hidden input with target model FK
- Fix `buildPivotStoreFields()` to use `buscadorInputName()` for correct input name discrimination
- Fix FK column in generated pivot store code — use FK pointing to target model, not `getForeignKey()`
- Fix controller store/update to properly create pivot records with `Cliente_id`, `Cobro_id`, `rol`
- Fix `ParticipanteCobro.$fillable` to use simple array syntax (not key→value)

### Out of Scope
- Other relation types (belongsToMany, morphMany, etc.)
- Non-scoped hasOne relations (if they work)
- Existing records migration or data backfill
- UI styling or UX improvements beyond fixing buscador behavior

## Capabilities

> No existing specs in `openspec/specs/`. These are first-time capability definitions.

### New Capabilities
- `crud-generator`: Core CRUD generator — model, controller, views, routes generation from schema
- `buscador`: Buscador (search/lookup) component generation for relation fields
- `pivot-relation`: Scoped pivot relation handling in generated CRUD (hasOne through pivot)

### Modified Capabilities
- None — all capabilities are new (first spec cycle for this project)

## Approach

1. **Infer search target**: In `buildCreateBuscadorCalls()`, when a relation has a pivot table, resolve the target model from the relation's `related` method (e.g., `$this->belongsTo(Cliente::class)`) inside the pivot model.
2. **Resolve search config**: Read the target model's buscador config from `config/generator.php` (search field, display field).
3. **Fix FK resolution**: For pivot relations, use the FK that points to the target model (e.g., `Cliente_id`) by introspecting the `belongsTo` definition in the pivot model, not `$relatedInstance->getForeignKey()`.
4. **Fix input naming**: Replace hardcoded `Str::snake($pivotModelShort)` with `buscadorInputName()`.
5. **Fix controller logic**: Generate pivot record creation with explicit `Cobro_id`, `Cliente_id`, `rol` instead of trying to set `$cobro->deudor = $id`.
6. **Fix fillable**: Change `ParticipanteCobro.$fillable` from key→value to simple array.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Generators/*.php` | Modified | Buscador call generation, pivot store fields, controller generation |
| `app/Models/ParticipanteCobro.php` | Modified | `$fillable` syntax fix |
| `config/generator.php` | Modified (maybe) | May need search config for pivot target models |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|-------------|
| Breaking existing buscadores for other relation types | Low | All changes guarded by `hasPivotTable()` check — only scoped pivot relations affected |
| Missing edge case in FK introspection (e.g., custom FK names) | Medium | Add test case with non-conventional FK; log warning if ambiguous |
| Generator output may require view regeneration | Low | Re-run generator for affected models; no migration needed |

## Rollback Plan

Revert each generator method change individually. The generator output is deterministic — re-running with reverted code produces correct output. `ParticipanteCobro` fillable change is a one-line revert.

## Dependencies

None. All fixes are local to the generator codebase and model schema.

## Success Criteria

- [ ] `buildCreateBuscadorCalls()` emits `tipo: 'cliente'` (not `participante_cobro`) for deudor/acreedor
- [ ] Generated onSelect sets hidden input with `Cliente_id` value
- [ ] Pivot store field input name is `nombre-participante_cobro-cliente-id` (discriminated by relation)
- [ ] Controller creates `ParticipanteCobro` with correct `Cobro_id`, `Cliente_id`, `rol`
- [ ] `ParticipanteCobro` is mass-assignable for `Cliente_id` and `Cobro_id`
- [ ] Cobro create modal works for deudor and acreedor buscadores
