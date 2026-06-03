# Delta for pivot-relation

## ADDED Requirements

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

### Requirement: scoped relations MUST use structural pivot detection

`getScopedRelations()` SHALL continue using `isPivotTable()` (already in place at line 176) as the authoritative check for pivot table identification. The name-based heuristic in `resolveEagerLoadStrategy()` SHALL be replaced with `isPivotTable()` for consistency across both methods.

#### Scenario: Method consistency across resolver

- GIVEN `getScopedRelations()` returns scoped pivot relations for a model
- WHEN `resolveEagerLoadStrategy()` evaluates the same pivot model
- THEN both methods MUST agree on pivot status — both using `isPivotTable()`
