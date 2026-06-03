# Pivot Relation Specification

## Purpose

Scoped hasOne-through-pivot relations let a parent model (Cobro) define named accessors (deudor, acreedor) backed by a shared pivot table (participante_cobro) with role discriminators. The generator, buscador, and controller all depend on correct resolution of these semantics.

## Requirements

### Requirement: Target model MUST be resolved from pivot belongsTo

When a scoped relation targets a pivot table, the search target model MUST be determined by inspecting the pivot model's `belongsTo` definitions. The target is the `belongsTo` whose referenced model does not match the parent.

#### Scenario: ParticipanteCobro resolves target to Cliente

- GIVEN ParticipanteCobro has `belongsTo(Cobro::class, 'Cobro_id')` and `belongsTo(Cliente::class, 'Cliente_id')`
- AND the scoped relation's parent is Cobro
- WHEN resolving the target model
- THEN the target MUST be `Cliente` (the non-parent belongsTo)

#### Scenario: Custom FK names in belongsTo

- GIVEN a pivot with `belongsTo(Parent::class, 'custom_parent_fk')` and `belongsTo(Target::class, 'custom_target_fk')`
- WHEN resolving
- THEN the FK used for pivot creation MUST be `'custom_parent_fk'`
- AND the target FK MUST be `'custom_target_fk'`

### Requirement: FK MUST come from explicit belongsTo definition

The FK from pivot to parent model MUST be read from the second argument of the pivot's `belongsTo($class, $fk)` call, NOT from `Model::getForeignKey()`.

#### Scenario: Parent FK is Cobro_id, not participante_cobro_id

- GIVEN `belongsTo(Cobro::class, 'Cobro_id')` on ParticipanteCobro
- WHEN `getScopedRelations()` resolves the foreign key
- THEN `foreignKey` MUST be `'Cobro_id'`
- AND NOT `'participante_cobro_id'` (which the default `getForeignKey()` would return)

### Requirement: Fillable MUST use flat array syntax

Pivot model `$fillable` arrays MUST be simple value lists (`['Col1', 'Col2']`), not associative arrays (`'Col1' => 'int'`). This complies with standard Eloquent conventions and ensures mass assignment works correctly.

#### Scenario: ParticipanteCobro fillable

- GIVEN ParticipanteCobro has columns `Cliente_id`, `Cobro_id`, `monto`, `rol`
- WHEN the model is inspected
- THEN `$fillable` MUST be `['Cliente_id', 'Cobro_id', 'monto', 'rol']`

### Requirement: Search config keys MUST match target model table

Configuration in `config/generator.php` (`search_paths`, `display_fields`) for pivot-buscador searches MUST be keyed by the target model's table, not the pivot table.

#### Scenario: Deudor uses cliente search config

- GIVEN `search_paths.cliente = ['nombre']` in `config/generator.php`
- WHEN the generator reads search config for deudor
- THEN it MUST look up `search_paths['cliente']`
- AND NOT `search_paths['participante_cobro']`

### Requirement: Pivot detection MUST use composite-PK structural check

`resolveEagerLoadStrategy()` MUST use the existing `isPivotTable()` method (composite primary key + all PK columns are foreign keys + non-incrementing) instead of the name heuristic (`str_contains` matching `participante`/`contrato`/`item`) to determine whether a related model is a join/pivot table.

#### Scenario: Pivot table with role not matching name heuristic

- GIVEN a table `equipo_usuario` with composite PK `(equipo_id, usuario_id)` and both are FKs
- WHEN `resolveEagerLoadStrategy()` evaluates `hasMany(EquipoUsuario)`
- THEN `isPivotTable()` MUST return `true`
- AND the eager load strategy MUST include a suggested nested path

#### Scenario: Non-pivot table with participante in name

- GIVEN a table `participante_evento` with a single auto-increment PK
- WHEN `resolveEagerLoadStrategy()` evaluates it
- THEN `isPivotTable()` MUST return `false`
- AND no nested eager load path MUST be suggested (the name heuristic would have incorrectly triggered)

#### Scenario: Standard hasMany with no pivot involvement

- GIVEN a `hasMany(Telefono)` where Telefono has a single auto-increment PK
- WHEN evaluating eager load strategy
- THEN `isPivotTable()` MUST return `false`
- AND no nested path MUST be suggested

### Requirement: Scoped pivot relations MUST generate view data identically to direct FK

When `StubRenderer` processes a scoped pivot column (`sqlType='special_relation'`), the view data generation methods (`buildFkData`, `buildFkCompact`, `buildFkCompactArray`) MUST treat the column as a standard FK for the purpose of producing `$xCount` and `$xOptions` variables. The `ColumnMetadata` for scoped relations already carries `referencedTable` and `relatedModelVariable` — no additional resolution logic is needed. The `$seen` dedup mechanism MUST ensure exactly one set of view data variables per unique `$relatedVar`, regardless of how many scoped relations resolve to the same target model.

#### Scenario: Deudor scoped relation produces cliente view data

- GIVEN `Cobro.deudor()` is a scoped hasOne-through-pivot with `referencedTable='cliente'` and `relatedModelVariable='cliente'`
- WHEN the generator processes the Cobro schema for view data
- THEN `$clienteCount` and `$clienteOptions` MUST be generated in the controller's `create` method
- AND `$clienteCount` and `$clienteOptions` MUST be passed to the `create` view

#### Scenario: Multiple scoped relations to same model produce single view data set

- GIVEN `Contrato` has three scoped relations (`arrendador`, `arrendatario`, `corredor`) all resolving to `relatedModelVariable='cliente'`
- WHEN view data generation runs
- THEN `$clienteCount` and `$clienteOptions` MUST appear exactly once in the generated controller
- AND `$seen['cliente']` MUST prevent the second and third relation from duplicating output

#### Scenario: Mixed direct FK and scoped relations both generate view data

- GIVEN a schema with a direct FK `servicio_id` (target: `servicio`) AND a scoped relation `deudor` (target: `cliente`)
- WHEN view data generation runs
- THEN both `$servicioCount`/`$servicioOptions` AND `$clienteCount`/`$clienteOptions` MUST be generated
- AND each MUST be deduped independently via `$seen`

### Requirement: Scoped relations MUST use structural pivot detection

`getScopedRelations()` SHALL continue using `isPivotTable()` (already in place at line 176) as the authoritative check for pivot table identification. The name-based heuristic in `resolveEagerLoadStrategy()` SHALL be replaced with `isPivotTable()` for consistency across both methods.

#### Scenario: Method consistency across resolver

- GIVEN `getScopedRelations()` returns scoped pivot relations for a model
- WHEN `resolveEagerLoadStrategy()` evaluates the same pivot model
- THEN both methods MUST agree on pivot status — both using `isPivotTable()`

## Requirements (Delta: generator-pivot-name-lookup)

### Requirement: Inline edit forms MUST include hidden FK input for scoped relations

When rendering the show/edit inline form for a scoped relation (deudor, acreedor), the form MUST include a hidden input named `{relationName}_{scopedTargetFk}` (e.g., `deudor_Cliente_id`). The buscador's `onSelect` callback MUST populate this hidden input with the selected entity's ID before form submission.

#### Scenario: Inline edit for deudor includes hidden input

- GIVEN a Cobro show view with inline edit for the deudor scoped relation
- WHEN the inline edit form is rendered
- THEN the form MUST contain `<input type="hidden" name="deudor_Cliente_id" id="deudor_Cliente_id">`
- AND the buscador `onSelect` MUST set this hidden input's value to `item.id`

#### Scenario: Buscador selection populates hidden input before submit

- GIVEN the inline edit form with hidden `deudor_Cliente_id`
- WHEN a user selects a client from the buscador dropdown
- THEN the hidden input value MUST be set to the selected client's ID
- AND the text input `nombre-deudor` MUST display the selected client's name

### Requirement: Controller MUST resolve scoped pivot by name OR by ID

The generated scoped store/update logic MUST accept two input modes for resolving the target entity: (1) hidden FK input `{relationName}_{scopedTargetFk}` with an integer ID, or (2) select input with the same name sending an integer ID. The controller MUST resolve the target via `findOrFail` using the FK ID. The text-only name resolution mode (`firstOrCreate` by display name) MUST be removed — free-text submissions without a selected FK ID MUST fail validation before reaching the controller.

#### Scenario: Create cobro with buscador-selected deudor (ID input)

- GIVEN a create request with `deudor_Cliente_id = 5` and `nombre-deudor = "Juan Pérez"`
- WHEN the store method processes scoped fields
- THEN it MUST resolve the target via `Cliente::findOrFail(5)`
- AND create a `ParticipanteCobro` with `Cliente_id = 5`, `rol = 'Deudor'`

#### Scenario: Create cobro with select dropdown deudor (ID input)

- GIVEN a create request with `deudor_Cliente_id = 3` (from select, no buscador text)
- WHEN the store method processes scoped fields
- THEN it MUST resolve via `Cliente::findOrFail(3)`
- AND create the pivot record with `Cliente_id = 3`

#### Scenario: Update cobro changes deudor via buscador

- GIVEN an update request with `deudor_Cliente_id = 10` replacing previous `deudor_Cliente_id = 5`
- WHEN the update method processes scoped fields
- THEN it MUST delete the existing `ParticipanteCobro` for this cobro+rol
- AND create a new `ParticipanteCobro` with `Cliente_id = 10`, `rol = 'Deudor'`

#### Scenario: No input provided — no pivot record created

- GIVEN a create request with `deudor_Cliente_id` empty and `nombre-deudor` empty
- WHEN the store method processes scoped fields
- THEN it MUST NOT create any `ParticipanteCobro` for the deudor role
- AND the cobro creation MUST succeed without error
