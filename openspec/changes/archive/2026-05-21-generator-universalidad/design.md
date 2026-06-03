# Design: Generator Universalidad

## Technical Approach

Pure refactor of generator source: extract hardcoded assumptions (namespace `\App\Models\`, FK suffix `_cliente_id`, pivot name heuristics, Spanish UI strings) into `config/generator.php` with defaults matching current behavior. No schema, DB, or generated-file changes. All 4 issues are independent.

## Architecture Decisions

### Decision: Config-driven model namespace

| Option | Tradeoff | Decision |
|--------|----------|----------|
| `config('generator.model_namespace', 'App\\Models\\')` | Simple, follows existing `$repositoryNamespace` pattern | ✅ Adopt |
| Auto-detect from model files | Fragile, extra IO on every generation | ❌ |
| Namespace resolver class | Over-engineering for one config key | ❌ |

### Decision: Composite-PK pivot detection via `isPivotTable()`

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Replace `str_contains()` with `$this->isPivotTable($relatedModel)` | Already tested, structural, consistent with `getScopedRelations()` | ✅ Adopt |
| Keep both with fallback | Two paths diverge → hard-to-debug inconsistency | ❌ |

### Decision: UI strings as config arrays with Spanish defaults

| Option | Tradeoff | Decision |
|--------|----------|----------|
| `config('generator.months', [...])`, `config('generator.filter_titles', [...])` | Simple, no i18n framework | ✅ Adopt |
| Full gettext/locale system | Out of scope per proposal | ❌ |

### Decision: `guessDisplayField()` reorder `name` before `nombre`

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Reorder to `['name', 'nombre', ...]` | English-neutral default, still falls back to Spanish | ✅ Adopt |
| Make priority list configurable | More complex, spec only asks for reorder | ❌ |

## Data Flow

```
config/generator.php
  ├── model_namespace ──→ StubRenderer (5 use/query/FK locations)
  │                       ConfigLoader::defaults() model key
  │                       RelationResolver::resolveModelClass() fallback
  │                       GenSearchCommand::tableToModelClass() fallback
  │                       GenCrudCommand:: model class fallback
  │                       FkInterviewer::resolveModelClass() fallback
  │
  ├── months[], filter_titles[] ──→ StubRenderer::renderFilterDateField()
  │                                  StubRenderer::buildFilterSections()
  │
  └── ColumnMetadata::scopedTargetFk (ALREADY populated)
                      ──→ filter-field-scoped.stub ({{target_fk}})
                      ──→ StubRenderer::buildFilterConditions()
                      ──→ StubRenderer::renderFilterScopedField()

RelationResolver::isPivotTable($relatedModel)
  └── replaces str_contains() in resolveEagerLoadStrategy()
      (getScopedRelations() already uses isPivotTable())
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `config/generator.php` | Modify | Add `model_namespace`, `months`, `filter_titles` keys with defaults |
| `stubs/fragments/filter-field-scoped.stub` | Modify | `_cliente_id` → `{{target_fk}}` in name + data-filter attrs |
| `app/Generator/Rendering/StubRenderer.php` | Modify | **5** namespace: `buildModelUses`(L868), `buildPivotStoreFields`(L1071), `buildPivotUpdateFields`(L1137), `renderBuscadorController`(L108), `renderFilterFkField`(L1977). **3** FK suffix: `buildFilterConditions`(L1748-1749), `renderFilterScopedField`(pass `filter_fk` to stub). **2** UI strings: `renderFilterDateField`(months), `buildFilterSections`(titles) |
| `app/Generator/Introspection/RelationResolver.php` | Modify | `resolveEagerLoadStrategy` L409-411: `str_contains('participante')`, `'contrato'`, `'item'` → `$this->isPivotTable($relatedModel)` (requires passing `$relatedModel` instead of `$relatedShort`). `resolveModelClass` L451: use config namespace fallback |
| `app/Generator/Config/ConfigLoader.php` | Modify | `defaults()` L81: `'model'` value uses `config('generator.model_namespace', 'App\\Models\\')` |
| `app/Generator/Commands/GenSearchCommand.php` | Modify | `tableToModelClass` L277: use config namespace fallback |
| `app/Generator/Commands/GenCrudCommand.php` | Modify | L239: `'App\\Models\\'` fallback → `config('generator.model_namespace', 'App\\Models\\')` |
| `app/Generator/Commands/FkInterviewer.php` | Modify | `resolveModelClass` L453: `'App\\Models\\'` fallback → `config('generator.model_namespace', 'App\\Models\\')` |
| `app/Generator/Introspection/SchemaInspector.php` | Modify | `guessDisplayField` L267: reorder `name` before `nombre`, remove redundant `nombre` special case L289-291 |
| `app/Generator/Rendering/PlaceholderRegistry.php` | Modify | Preserve accent mapping as-is — refactor to read from config with hardcoded fallback |

## Interfaces / Contracts

```php
// config/generator.php — new default keys
'model_namespace' => 'App\\Models\\',
'months' => [1 => 'Enero', 2 => 'Febrero', ... 12 => 'Diciembre'],
'filter_titles' => [
    'date'    => 'Filtrar por fechas',
    'number'  => 'Filtrar por montos',
    'text'    => 'Filtrar por texto',
    'fk'      => 'Filtrar por relaciones',
    'boolean' => 'Filtrar por opciones',
],
// PlaceholderRegistry::$accents preserved as default fallback
```

## Testing Strategy

| Layer | What | How |
|-------|------|-----|
| Unit | Namespace config applied | Generator with custom `model_namespace`, verify `use` + `::findOrFail` output |
| Unit | Dynamic FK suffix | Generator with FK `Propietario_id`, verify `<select name="filter[propietario_Propietario_id]">` |
| Unit | Pivot detection consistency | Assert `getScopedRelations()` and `resolveEagerLoadStrategy()` agree on pivot status for same model |
| Unit | UI strings via config | Override `months[1]` → `'January'`, verify generated Blade uses it |
| E2E | Existing project regression | Generated output identical for current project config with defaults |

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Config key typo breaks generation | Med | All `config()` calls include hardcoded defaults matching current behavior |
| `isPivotTable()` diverges from name heuristic | Low | Test edge cases: table with `participante` in name but single PK (should NOT be pivot), table with composite PK not matching names (SHOULD be pivot) |
| Namespace config breaks existing projects | Low | Default matches `App\\Models\\` exactly; old configs without key keep working via default |

## Dependency Graph

```
P1 (Dynamic FK)   ─┐
P2 (Namespace)    ─┤── All independent — no file-level conflicts
P3 (Pivot detect) ─┤    Can parallelize in a single batch
P4 (UI strings)   ─┘
```

No merge conflicts: each issue touches different methods/lines within the same files. Apply order within StubRenderer doesn't matter — changes are additive `str_replace()` swaps.
