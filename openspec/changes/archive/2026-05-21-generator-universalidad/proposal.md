# Proposal: Generator Universalidad

## Intent

The CRUD generator hardcodes `App\Models\` namespace, `_cliente_id` FK suffix, pivot name heuristics, and Spanish UI strings. This prevents use with any MySQL database imported via Laravel Reliese that doesn't match these assumptions. Remove the hardcodes so the generator works universally.

## Scope

### In Scope
1. Replace `_cliente_id` hardcode with dynamic `scopedTargetFk` in filter stub, StubRenderer, and GenSearchCommand
2. Add `model_namespace` config to `config/generator.php` and use it everywhere instead of `\App\Models\`
3. Replace pivot name heuristic (`str_contains` for `participante`/`contrato`/`item`) with existing `isPivotTable()` (composite PK detection)
4. Extract Spanish UI strings (months, filter titles, labels) to `config/generator.php` defaults

### Out of Scope
- Full i18n / locale system — just extract to config with Spanish defaults
- FkInterviewer flow changes
- Database migrations, seed changes, or existing generated files migration
- Generator output format changes (Blade, JS output unchanged)

## Capabilities

> Contract between proposal and specs phases.

### New Capabilities
None — pure refactor of existing `crud-generator`.

### Modified Capabilities
- `crud-generator`: namespace config option, dynamic scoped FK suffix, composite-PK pivot detection, UI string extraction
- `pivot-relation`: pivot detection shifts from naming heuristic to structural (composite PK) — delta spec needed
- `buscador`: filter field name construction becomes dynamic instead of `_cliente_id` suffix — delta spec needed

## Approach

1. **Dynamic FK suffix**: Replace every `'{$relationName}_cliente_id'` with `'{$relationName}_{$col->scopedTargetFk}'` — `scopedTargetFk` already populated by `ColumnMetadata`
2. **Namespace config**: Add `'model_namespace' => 'App\\Models\\'` to `config/generator.php`. Replace all `\App\Models\{$modelName}` with `config('generator.model_namespace') . $modelName`
3. **Pivot detection**: Replace `str_contains()` block in `RelationResolver::getScopedRelations()` with call to existing `isPivotTable()` method
4. **UI strings**: Move month names, filter titles, and labels from `StubRenderer` and `PlaceholderRegistry` to `config/generator.php` with Spanish defaults

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `config/generator.php` | Modified | Add `model_namespace`, UI string config entries |
| `src/GenSearchCommand.php:215` | Modified | Replace hardcoded `_cliente_id` suffix |
| `src/StubRenderer.php` | Modified | 5+ hardcoded namespace references, filter fields, UI strings |
| `src/RelationResolver.php` | Modified | Replace pivot name heuristic with `isPivotTable()` |
| `stubs/fragments/filter-field-scoped.stub` | Modified | Replace `_cliente_id` with dynamic FK |
| `src/PlaceholderRegistry.php` | Modified | Extract accent mapping to config |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Existing generated files reference old namespace | Med | Out of scope — existing projects need regeneration |
| Pivot detection change misses edge cases | Low | `isPivotTable()` already tested, fallback to name heuristic as second check |
| Config key typo breaks generation | Low | Use config helper with defaults that match current hardcoded values |

## Rollback Plan

Revert commits per file. Every change is local to the generator source — no migrations, no DB. Old configs keep working since new config keys have hardcoded defaults matching current behavior.

## Dependencies

None. All changes are self-contained in generator source files and stubs.

## Success Criteria

- [ ] Generator produces valid code for a database without `_cliente_id` FKs and without `App\Models\` namespace
- [ ] Generator produces identical output for existing projects (config defaults match current values)
- [ ] Pivot tables with composite PKs not matching `participante`/`contrato`/`item` name heuristic are correctly detected
- [ ] All existing `crud-generator`, `pivot-relation`, and `buscador` spec scenarios pass
