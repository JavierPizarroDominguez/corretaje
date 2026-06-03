# CRUD Generator Specification

## Purpose

Generates Laravel CRUD scaffolding (models, controllers, views, routes) from a MySQL schema. Handles standard FK relations, scoped hasOne-through-pivot relations, and buscader JS component calls.

## Requirements

### Requirement: Scoped relation search MUST emit target model table

When `buildCreateBuscadorCalls()` processes a scoped hasOne-through-pivot relation, the emitted `tipo` parameter MUST reference the target model's table (e.g., `cliente`), not the pivot table. The target model is resolved from the pivot's `belongsTo` definitions.

#### Scenario: Deudor searches cliente

- GIVEN `Cobro.deudor()` is `hasOne(ParticipanteCobro)->where('rol', 'Deudor')`
- AND ParticipanteCobro has `belongsTo(Cobro, 'Cobro_id')` and `belongsTo(Cliente, 'Cliente_id')`
- WHEN `buildCreateBuscadorCalls()` processes the deudor column
- THEN `tipo` MUST be `'cliente'`

#### Scenario: Standard FK unchanged

- GIVEN a direct FK column with `referencedTable` set to `contrato`
- WHEN `buildCreateBuscadorCalls()` processes it
- THEN `tipo` MUST be `'contrato'` (pivot-aware logic does not apply)

### Requirement: Pivot store input name MUST use buscadorInputName()

`buildPivotStoreFields()` MUST call `buscadorInputName()` to produce a relation-discriminated input name, not `Str::snake($pivotModelShort)`. This prevents collisions when multiple scoped relations share the same pivot table.

#### Scenario: Distinct names for deudor and acreedor

- GIVEN two scoped relations (deudor, acreedor) on the same pivot model (ParticipanteCobro)
- WHEN `buildPivotStoreFields()` generates code for both
- THEN deudor input name MUST be `nombre-deudor` and acreedor MUST be `nombre-acreedor`
- AND they MUST NOT collide

### Requirement: Controller MUST create pivot records with explicit FKs

Generated store/update code MUST create a new pivot record with explicit FK assignments: parent FK (`Cobro_id`), target FK (`Cliente_id`), and scope column (`rol`). It MUST NOT set attributes on the parent model (no `$cobro->deudor = $id`).

#### Scenario: Store creates ParticipanteCobro for deudor

- GIVEN a Cobro create request with `deudor_Cliente_id = 5`
- WHEN the store method runs the generated scoped pivot code
- THEN it MUST resolve the target via `Cliente::findOrFail(5)`
- AND create a new ParticipanteCobro with `Cobro_id = $cobro->id`, `Cliente_id = $cliente->id`, `rol = 'Deudor'`

### Requirement: SchemaBuilder MUST set referencedTable to target model

In `buildScopedColumn()`, the `referencedTable` field MUST be the target model's table (e.g., `cliente`), not the pivot table. The `pivotFk` MUST be the FK from pivot to parent model, resolved from the explicit `belongsTo` definition.

#### Scenario: Deudor references cliente

- GIVEN a scoped hasOne resolving with target model Cliente (table `cliente`)
- WHEN `buildScopedColumn()` constructs the ColumnMetadata
- THEN `referencedTable` MUST be `'cliente'`

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

### Requirement: View data generation MUST include scoped pivot columns

`buildFkData()`, `buildFkCompact()`, and `buildFkCompactArray()` MUST NOT skip columns with `sqlType === 'special_relation'`. Scoped pivot columns MUST be processed identically to direct FK columns for view data output, producing `$xCount` and `$xOptions` variables for the controller. The existing `$seen[$relatedVar]` dedup MUST prevent duplicate output when multiple scoped relations resolve to the same target model variable.

#### Scenario: buildFkData generates view data for scoped deudor relation

- GIVEN a `Cobro` schema with a scoped `deudor` column (`sqlType='special_relation'`, `referencedTable='cliente'`, `relatedModelVariable='cliente'`)
- WHEN `buildFkData()` processes the schema columns
- THEN it MUST produce a data line for `$clienteCount` and `$clienteOptions`
- AND the guard `|| $col->sqlType === 'special_relation'` MUST NOT be present in the skip condition

#### Scenario: buildFkCompact lists scoped relation variables

- GIVEN a `Contrato` schema with scoped relations `arrendador`, `arrendatario`, `corredor` all targeting `Cliente`
- WHEN `buildFkCompact()` processes the schema columns
- THEN the output MUST include `'clienteCount'` and `'clienteOptions'` exactly once
- AND the `$seen` dedup MUST prevent duplicate entries for the same `$relatedVar`

#### Scenario: buildFkCompactArray generates compact lines for scoped relations

- GIVEN a `Cobro` schema with scoped `deudor` and `acreedor` both targeting `Cliente`
- WHEN `buildFkCompactArray()` processes the schema columns
- THEN it MUST produce exactly one compact line referencing `$clienteCount` and `$clienteOptions`
- AND the guard `|| $col->sqlType === 'special_relation'` MUST NOT be present in the skip condition

#### Scenario: Direct FK view data unchanged after guard removal

- GIVEN a schema with a direct FK column `contrato_id` (`sqlType='int'`, `referencedTable='contrato'`)
- WHEN `buildFkData()`, `buildFkCompact()`, and `buildFkCompactArray()` process the schema
- THEN the output for `$contratoCount` and `$contratoOptions` MUST be identical to pre-change output

#### Scenario: Tables without scoped relations produce zero diff

- GIVEN a schema with no `special_relation` columns (only direct FKs and primitives)
- WHEN the generator runs after the guard removal
- THEN the generated output MUST be byte-identical to pre-change output

### Requirement: PlaceholderRegistry accent mapping MUST be preserved as default

The accent-to-correct-Spanish mapping in `PlaceholderRegistry::$accents` MUST be the default behavior. If the method is refactored to support configurable mappings, the existing hardcoded array MUST remain as the fallback default.

#### Scenario: Comision maps to Comisión by default

- GIVEN `toLabel('comision_inicial')` is called
- WHEN the accent mapping applies
- THEN output MUST be `'Comisión Inicial'`

## Requirements (Delta: enum-case-preservation)

### Requirement: ENUM values MUST preserve original database casing

The system MUST preserve the exact casing of MySQL ENUM values when generating `<option>` elements in create and show views. The `SchemaInspector::getColumnType()` method MUST NOT apply `strtolower()` to the raw `COLUMN_TYPE` string before parsing ENUM values.

#### Scenario: ENUM values retain original casing in create view

- GIVEN a MySQL table with an ENUM column defined as `ENUM('Ingreso', 'Renta', 'Arrendador')`
- WHEN the generator produces the create view for this table
- THEN the `<option>` elements MUST have `value="Ingreso"`, `value="Renta"`, `value="Arrendador"` (exact casing)
- AND the displayed text MUST match the value casing

#### Scenario: ENUM values retain original casing in show view

- GIVEN a MySQL table with an ENUM column defined as `ENUM('Activo', 'Inactivo', 'Pendiente')`
- WHEN the generator produces the show view for this table
- THEN any ENUM-based `<select>` or display elements MUST reflect the original casing

#### Scenario: Mixed-case ENUM values preserved

- GIVEN an ENUM column with mixed-case values like `ENUM('PDF', 'pdf', 'Pdf')`
- WHEN the generator processes this column
- THEN each value MUST be preserved exactly as defined: `'PDF'`, `'pdf'`, `'Pdf'`

### Requirement: Boolean TINYINT(1) detection MUST remain case-insensitive

The system MUST continue to detect `TINYINT(1)` columns as boolean fields regardless of the casing of the `COLUMN_TYPE` string returned by MySQL introspection. A targeted `strtolower()` MUST be applied only to the boolean detection comparison, not to the entire column type string.

#### Scenario: TINYINT(1) detected with uppercase type

- GIVEN a column with `COLUMN_TYPE` = `tinyint(1)`
- WHEN boolean detection runs
- THEN the column MUST be identified as a boolean field

#### Scenario: TINYINT(1) detected with mixed case type

- GIVEN a column with `COLUMN_TYPE` = `TINYINT(1)`
- WHEN boolean detection runs
- THEN the column MUST be identified as a boolean field

#### Scenario: Non-boolean TINYINT not misdetected

- GIVEN a column with `COLUMN_TYPE` = `tinyint(4)`
- WHEN boolean detection runs
- THEN the column MUST NOT be identified as a boolean field

## Requirements (Delta: generator-pivot-name-lookup)

### Requirement: Select field name for scoped relations MUST match hidden FK key

When `create-field-fk-select.stub` renders a `<select>` for a scoped relation, the `name` attribute MUST be `{relationName}_{scopedTargetFk}` (e.g., `deudor_Cliente_id`), matching the hidden input name used by the buscador. This ensures both input modes send data under the same controller key.

#### Scenario: Select for deudor sends under deudor_Cliente_id

- GIVEN a scoped relation deudor with `scopedTargetFk = 'Cliente_id'` and client count ≤ threshold
- WHEN the create form renders a select field
- THEN the select MUST have `name="deudor_Cliente_id"`
- AND the submitted value MUST be readable as `$request->input('deudor_Cliente_id')`

#### Scenario: Select for acreedor sends under acreedor_Cliente_id

- GIVEN a scoped relation acreedor with `scopedTargetFk = 'Cliente_id'`
- WHEN the create form renders a select field
- THEN the select MUST have `name="acreedor_Cliente_id"`

### Requirement: Validation for scoped FK inputs MUST be conditional

`buildValidationRules()` MUST generate validation rules for buscador FK fields as follows: the hidden FK input MUST use `required_with:{buscador_input_name}|integer|exists:{table},id`; the text input MUST use `sometimes|nullable|string`. For non-buscador FK fields, the existing `sometimes|nullable|integer|exists:{table},id` rule remains unchanged.

#### Scenario: Validation passes with select-provided ID

- GIVEN a request with `deudor_Cliente_id = 5` (from select, hidden input empty)
- WHEN validation runs
- THEN the rule `required_with:nombre-deudor|integer|exists:cliente,id` MUST pass
- AND the request MUST NOT return a 422 error

#### Scenario: Validation passes with buscador-provided ID

- GIVEN a request with `deudor_Cliente_id = 3` (from buscador onSelect) and `nombre-deudor` set
- WHEN validation runs
- THEN the `required_with` rule MUST pass for the integer value

#### Scenario: Validation fails when text provided without FK ID

- GIVEN a request with `nombre-deudor = "Juan"` and `deudor_Cliente_id` empty
- WHEN validation runs
- THEN the `required_with` rule MUST fail — 422 error on `deudor_Cliente_id`

#### Scenario: Validation passes with no input

- GIVEN a request with both `deudor_Cliente_id` and `nombre-deudor` absent or null
- WHEN validation runs
- THEN the `required_with` rule MUST NOT trigger (text input is empty)
- AND the request MUST pass validation

### Requirement: Pivot model instantiation MUST use absolute namespace

`buildPivotStoreFields()` and `buildPivotUpdateFields()` MUST prefix the pivot model class with a leading backslash (`\`) when instantiating inline (e.g., `new \App\Models\ParticipanteCobro()`). This ensures the class resolves correctly regardless of the controller's namespace context.

#### Scenario: Store instantiates pivot model without namespace error

- GIVEN a generated CobroController in namespace `App\Http\Controllers\Crud`
- WHEN the store method executes `new \App\Models\ParticipanteCobro()`
- THEN the class MUST resolve to `App\Models\ParticipanteCobro`
- AND no ClassNotFoundError MUST be thrown

#### Scenario: Update instantiates pivot model without namespace error

- GIVEN a generated CobroController in namespace `App\Http\Controllers\Crud`
- WHEN the update method executes `new \App\Models\ParticipanteCobro()`
- THEN the class MUST resolve correctly
- AND the pivot record MUST be created/updated without error

## Requirements (Delta: strict-buscador-selection)

### Requirement: buildCreateBuscadorCalls MUST always emit hidden input assignment

`buildCreateBuscadorCalls()` MUST emit the hidden input assignment line `document.getElementById('input-create-{field}-id').value = item.id` for ALL buscador fields, including direct FK relations (contrato, servicio, propiedad, unidad). The scoped-relation guard that previously limited this to scoped fields MUST be removed.

#### Scenario: Direct FK contrato gets hidden input assignment

- GIVEN a direct FK field `contrato_id` with `isBuscador = true`
- WHEN `buildCreateBuscadorCalls()` processes it
- THEN the emitted `onSelect` MUST include `document.getElementById('input-create-contrato_id-id').value = item.id`

#### Scenario: Scoped relation deudor gets hidden input assignment

- GIVEN a scoped relation `deudor`
- WHEN `buildCreateBuscadorCalls()` processes it
- THEN the emitted `onSelect` MUST include `document.getElementById('input-create-deudor-id').value = item.id`

### Requirement: store-field-relation-buscador.stub MUST use findOrFail

The `store-field-relation-buscador.stub` MUST use `{Model}::findOrFail($data['{fk_column}'])` to resolve the related entity. It MUST NOT use `firstOrCreate` or any creation fallback. The FK ID is guaranteed to be valid by the `exists:{table},id` validation rule.

#### Scenario: Store resolves contrato by ID

- GIVEN generated code for a `contrato_id` buscador field
- WHEN the store method runs
- THEN it MUST execute `Contrato::findOrFail($data['contrato_id'])`
- AND MUST NOT call `firstOrCreate`

#### Scenario: Store resolves scoped target by ID

- GIVEN generated code for a `deudor` scoped relation
- WHEN the store method runs
- THEN it MUST execute `Cliente::findOrFail($data['deudor_Cliente_id'])`
- AND MUST NOT call `firstOrCreate`

### Requirement: buildPivotStoreFields MUST NOT use firstOrCreate

`buildPivotStoreFields()` MUST generate code that resolves the target entity via `findOrFail` using the FK ID from the hidden input. The `firstOrCreate` fallback by display name MUST be removed entirely.

#### Scenario: Pivot store uses findOrFail for deudor

- GIVEN `buildPivotStoreFields()` processes a deudor scoped relation
- WHEN generating the store code
- THEN the output MUST contain `Cliente::findOrFail($data['deudor_Cliente_id'])`
- AND MUST NOT contain `firstOrCreate`

### Requirement: buildPivotUpdateFields MUST NOT use firstOrCreate

`buildPivotUpdateFields()` MUST generate code that resolves the target entity via `findOrFail` using the FK ID from the hidden input. The `firstOrCreate` fallback by display name MUST be removed entirely.

#### Scenario: Pivot update uses findOrFail for deudor

- GIVEN `buildPivotUpdateFields()` processes a deudor scoped relation
- WHEN generating the update code
- THEN the output MUST contain `Cliente::findOrFail($data['deudor_Cliente_id'])`
- AND MUST NOT contain `firstOrCreate`
