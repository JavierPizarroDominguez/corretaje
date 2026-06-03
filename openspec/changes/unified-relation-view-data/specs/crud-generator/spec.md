# Delta for CRUD Generator

## ADDED Requirements

### Requirement: RelationshipViewDataProvider MUST generate belongsToMany view data

`RelationshipViewDataProvider::buildViewData(TableSchema)` MUST include items for belongsToMany relations. Each item MUST contain `relatedVar`, `relatedModelName`, `displayField`, and `referencedTable`. The generated view MUST receive `$relatedVarCount` and `$relatedVarOptions` for each item.

#### Scenario: Cobro view includes $transaccionCount

- GIVEN `Cobro` has a `belongsToMany(Transaccion)` relation
- WHEN `buildViewData()` processes the Cobro schema
- THEN it MUST return an item with `relatedVar = 'transaccion'`, `relatedModelName = 'Transaccion'`, and `referencedTable = 'transaccion'`

#### Scenario: Entity without belongsToMany yields no btm items

- GIVEN a table with no belongsToMany relations defined
- WHEN `buildViewData()` runs
- THEN no belongsToMany items appear in the result

### Requirement: RelationshipViewDataProvider MUST generate scoped pivot view data

`buildViewData()` MUST generate items for `special_relation` columns where `pivotModel` is set. The target model MUST be resolved from the pivot's `belongsTo` definitions via `RelationResolver`.

#### Scenario: Cobro deudor view includes $clienteCount

- GIVEN `Cobro.deudor` is a scoped hasOne-through-pivot with `pivotModel = ParticipanteCobro`
- AND ParticipanteCobro has `belongsTo(Cliente, 'Cliente_id')`
- WHEN `buildViewData()` processes the Cobro schema
- THEN it MUST return an item with `relatedVar = 'cliente'`, `relatedModelName = 'Cliente'`, `referencedTable = 'cliente'`

#### Scenario: Scoped relation with non-standard FK names

- GIVEN a scoped relation with `pivotModel = ParticipanteContrato` and target FK `Propietario_id` resolving to table `propietario`
- WHEN `buildViewData()` processes the schema
- THEN the item MUST use `referencedTable = 'propietario'` and `relatedVar = 'propietario'`

### Requirement: RelationshipViewDataProvider MUST unify all three patterns

`buildViewData(TableSchema)` MUST return a single flat array containing items from all three pattern types: direct belongsTo FK columns, scoped pivot (`special_relation` + `pivotModel`), and belongsToMany. Each item MUST include: `relatedVar`, `relatedModelName`, `displayField`, `isRelational`, `referencedTable`.

#### Scenario: Cobro schema returns items covering all patterns

- GIVEN a Cobro schema with one direct FK (`contrato_id`), one scoped pivot (`deudor`), and one belongsToMany (`transaccions`)
- WHEN `buildViewData()` runs
- THEN it MUST return exactly 3 items, one per pattern type

#### Scenario: Table without FK or relations returns empty array

- GIVEN a table with no foreign keys, no `special_relation` columns, and no belongsToMany relations
- WHEN `buildViewData()` runs
- THEN it MUST return an empty array

## MODIFIED Requirements

None modified. The existing `StubRenderer` public API (`buildFkData()`, `buildFkCompact()`, `buildFkCompactArray()`) delegates to the provider internally — all existing requirements remain unchanged and continue passing.
