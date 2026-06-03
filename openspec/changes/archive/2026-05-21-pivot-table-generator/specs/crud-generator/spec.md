# Delta for CRUD Generator

## ADDED Requirements

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

## REMOVED Requirements

None — existing requirements remain unchanged.
