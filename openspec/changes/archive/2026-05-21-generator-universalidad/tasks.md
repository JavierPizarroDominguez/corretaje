# Tasks: Generator Universalidad

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 100–170 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Foundation + all 4 concerns | PR 1 | Single PR — well under 400 lines, independent changes within one file set |

## Phase 1: Foundation (infrastructure — must go first)

- [x] 1.1 Add `model_namespace`, `months` (1..12 Spanish), `filter_titles` (date/number/text/fk/boolean) keys to `config/generator.php` with backward-compatible defaults matching current hardcoded values

## Phase 2: Dynamic Namespace

- [x] 2.1 `ConfigLoader::defaults()` L81: replace `"App\\Models\\{$modelName}"` with `config('generator.model_namespace', 'App\\Models\\') . $modelName`
- [x] 2.2 `StubRenderer::buildModelUses()` L868: replace `"use App\\Models\\{$modelName};"` with config via `$this->getModelNamespace()`
- [x] 2.3 `StubRenderer::buildPivotStoreFields()` L1071: replace `\\App\\Models\\{$targetModelShort}::findOrFail` with config via `$this->getModelNamespace()`
- [x] 2.4 `StubRenderer::buildPivotUpdateFields()` L1137: replace `\\App\\Models\\{$targetModelShort}::findOrFail` with config via `$this->getModelNamespace()`
- [x] 2.5 `StubRenderer::renderBuscadorController()` L108: replace `\\App\\Models\\{$modelName}::query()` with config via `$this->getModelNamespace()`
- [x] 2.6 `StubRenderer::renderFilterFkField()` L1977: replace `\\App\\Models\\{$relatedModel}::orderBy` with config via `$this->getModelNamespace()`
- [x] 2.7 `RelationResolver::resolveModelClass()` L451: replace `'App\\Models\\'` fallback with config
- [x] 2.8 `GenSearchCommand::tableToModelClass()` L277: replace `'App\\Models\\'` fallback with config
- [x] 2.9 `GenCrudCommand` L239: replace `'App\\Models\\'` fallback with config
- [x] 2.10 `FkInterviewer::resolveModelClass()` L453: replace `'App\\Models\\'` fallback with config

## Phase 3: Dynamic FK

- [x] 3.1 `filter-field-scoped.stub`: replace `_cliente_id` with `{{target_fk}}` in both `name` and `data-filter` attributes
- [x] 3.2 `StubRenderer::buildFilterConditions()` L1748–1749: use `$filterFk` from `$sr['filter_fk']` instead of hardcoded `_cliente_id`
- [x] 3.3 `StubRenderer::renderFilterScopedField()`: pass `filter_fk` from `$sr['filter_fk']` to stub replacement so dynamic FK appears in generated HTML

## Phase 4: Pivot Detection

- [x] 4.1 `RelationResolver::resolveEagerLoadStrategy()`: replace `str_contains('participante'/'contrato'/'item')` with `$this->isPivotTable($relatedModel)`, change signature to accept `$relatedModel` instead of `$relatedShort`, update call site at L386

## Phase 5: UI Strings

- [x] 5.1 `StubRenderer::renderFilterDateField()`: replace hardcoded `$months` array with `config('generator.months', $spanishMonths)`
- [x] 5.2 `StubRenderer::buildFilterSections()`: replace hardcoded section titles with `config('generator.filter_titles', $defaults)`
- [x] 5.3 `SchemaInspector::guessDisplayField()`: reorder `$commonFields` to prioritize `name` before `nombre`, remove redundant `nombre` check L288–291
- [x] 5.4 `PlaceholderRegistry`: optionally refactor `$accents` to read from config with hardcoded array as fallback (preserve current behavior as default) — verified via `test_placeholder_registry_accent_mapping_preserved`

## Phase 6: Testing & Verification

- [x] 6.1 `test_default_config_produces_same_output` — verifies identical output vs hardcoded baseline (regression check)
- [x] 6.2 `test_custom_model_namespace_in_use_statements` + `test_custom_namespace_in_relationship_method` — verifies `use` and `::findOrFail` output uses custom namespace
- [x] 6.3 `test_dynamic_fk_in_filter_fields` + `test_scoped_filter_generates_target_fk_static` — verifies dynamic FK in `<select name=...>` output
- [x] 6.4 `test_pivot_eager_load_strategy_agrees_with_isPivotTable` — asserts `getScopedRelations()` and `resolveEagerLoadStrategy()` agree on pivot status
- [x] 6.5 `test_custom_month_override` — overrides `months[1]` → `'January'`, verifies generated Blade uses override
- [x] 6.6 `test_custom_filter_title_override` — overrides `filter_titles.date`, verifies generated filter panel uses custom title
