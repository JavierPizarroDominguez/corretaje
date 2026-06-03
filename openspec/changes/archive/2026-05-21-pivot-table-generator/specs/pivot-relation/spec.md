# Delta for Pivot Relation

## ADDED Requirements

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

## MODIFIED Requirements

None — existing pivot-relation requirements remain unchanged.

## REMOVED Requirements

None.
