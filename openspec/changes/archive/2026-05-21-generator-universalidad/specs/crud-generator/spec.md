# Delta for crud-generator

## ADDED Requirements

### Requirement: Model namespace MUST be configurable

The generator MUST read `config('generator.model_namespace')` instead of hardcoding `\App\Models\` for all generated `use` statements and model references. The config defaults to `'App\\Models\\'`. Existing projects without the config key MUST continue working via the default.

#### Scenario: Store method references model via config

- GIVEN `config/generator.php` has `'model_namespace' => 'App\\Models\\'`
- WHEN `buildPivotStoreFields()` generates `\App\Models\Cliente::findOrFail(\$data['deudor_Cliente_id'])`
- THEN the namespace MUST come from `config('generator.model_namespace')`

#### Scenario: Custom namespace for different project

- GIVEN `config/generator.php` has `'model_namespace' => 'App\\Domain\\Entities\\'`
- WHEN the generator produces model references
- THEN `use` and inline references MUST use `App\Domain\Entities\Cliente` instead of `App\Models\Cliente`

#### Scenario: Missing config key uses default

- GIVEN a project without `model_namespace` in `config/generator.php`
- WHEN the generator runs
- THEN it MUST default to `App\Models\` namespace

### Requirement: Filter field-scoped stub MUST use dynamic target FK

The `filter-field-scoped.stub` MUST use `{{target_fk}}` placeholder instead of hardcoded `_cliente_id` suffix for the HTML `name` and `data-filter` attributes. `renderFilterScopedField()` MUST pass `scopedTargetFk` from the relation's `ColumnMetadata` into the stub replacement.

#### Scenario: Deudor filter uses Cliente_id

- GIVEN a scoped relation `deudor` with `scopedTargetFk = 'Cliente_id'`
- WHEN `renderFilterScopedField()` renders the stub
- THEN the `<select name="filter[deudor_Cliente_id]">` MUST be produced

#### Scenario: Different FK for different relation

- GIVEN a scoped relation `propietario` with `scopedTargetFk = 'Propietario_id'`
- WHEN the stub is rendered
- THEN the name attribute MUST be `filter[propietario_Propietario_id]`

### Requirement: Filter query conditions MUST use dynamic target FK

`buildFilterQueryConditions()` MUST use the scoped relation's `filter_fk` for both the filter key name in `$filter[...]` and the `whereHas()` constraint column, replacing the hardcoded `_cliente_id` suffix.

#### Scenario: Deudor filter queries by Cliente_id

- GIVEN a scoped relation with `relation_name = 'deudor'` and `filter_fk = 'Cliente_id'`
- WHEN `buildFilterQueryConditions()` generates filter conditions
- THEN output MUST be `if (!empty($filter['deudor_Cliente_id'])) { $query->whereHas('deudor', fn($q) => $q->where('Cliente_id', $filter['deudor_Cliente_id'])); }`

### Requirement: guessDisplayField MUST NOT prioritize Spanish-only column names

`SchemaInspector::guessDisplayField()` MUST treat the candidate field list as configurable or use a neutral default that does NOT assume Spanish-only column names like `nombre` or `razon_social` as highest priority. The fallback order SHOULD prioritize `name` over `nombre`, or make the priority list configurable.

#### Scenario: English table returns name over nombre

- GIVEN a table with columns `name`, `nombre`, and `id`
- WHEN `guessDisplayField('users')` is called
- THEN it MUST return `'name'` (the first match in a reordered/neutral priority list)

#### Scenario: Only Spanish columns present

- GIVEN a table with columns `nombre` and `id` only
- WHEN `guessDisplayField('cliente')` is called
- THEN it MUST return `'nombre'` (fallback works correctly)

### Requirement: Filter UI strings MUST be configurable

Filter section titles and month names in `StubRenderer` MUST be extracted to `config/generator.php` with Spanish defaults. The generator MUST use `config('generator.filter_titles', $defaults)` and `config('generator.month_names', $defaults)` where defaults match the current hardcoded Spanish values.

#### Scenario: Filter titles use config with Spanish default

- GIVEN no custom filter_titles in config
- WHEN `buildFilterSections()` renders the date section title
- THEN it MUST show `'Filtrar por fechas'`

#### Scenario: Custom filter titles override defaults

- GIVEN `config/generator.php` has `'filter_titles' => ['date' => 'Filter by date']`
- WHEN `buildFilterSections()` renders the date section title
- THEN it MUST show `'Filter by date'`

#### Scenario: Month names use config with Spanish default

- GIVEN no custom month_names in config
- WHEN `renderFilterDateField()` renders month 1
- THEN the option text MUST be `'Enero'`

### Requirement: PlaceholderRegistry accent mapping MUST be preserved as default

The accent-to-correct-Spanish mapping in `PlaceholderRegistry::$accents` MUST be the default behavior. If the method is refactored to support configurable mappings, the existing hardcoded array MUST remain as the fallback default.

#### Scenario: Comision maps to Comisión by default

- GIVEN `toLabel('comision_inicial')` is called
- WHEN the accent mapping applies
- THEN output MUST be `'Comisión Inicial'`
