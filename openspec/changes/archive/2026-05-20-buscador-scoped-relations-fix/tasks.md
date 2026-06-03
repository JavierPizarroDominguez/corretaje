# Tasks: Buscador Scoped Relations Fix

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 80–110 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-always |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

## Phase 1: Foundation — New Property & Pivot BelongsTo Resolution

- [x] 1.1 **ColumnMetadata.php**: Add `public readonly ?string $scopedTargetFk = null` property after `pivotExtraFields`
- [x] 1.2 **RelationResolver::getScopedRelations()**: After detecting scoped hasOne with `isPivotTable`, call `RelationResolver->resolve($pivotModel)` to find pivot's belongsTo relations. The belongsTo matching parent model class is `parentFk`; the other belongsTo is `targetFk`, `targetModel`, `targetTable`. Add to return array.

## Phase 2: Core Generator Changes

- [x] 2.1 **SchemaBuilder::buildScopedColumn()**: Use `rel['targetTable']` for `referencedTable`, `rel['targetModel']` for `relatedModelName`/`relatedModelVariable`, pass `rel['targetFk']` as `scopedTargetFk`
- [x] 2.2 **StubRenderer::buildCreateBuscadorCalls()**: When `col->pivotModel` is set, add hidden input `<input type="hidden" name="{relationName}_{scopedTargetFk}">` and set `item.id` in onSelect
- [x] 2.3 **StubRenderer::buildPivotStoreFields()**: Use `buscadorInputName($col)` instead of hardcoded `Str::snake($pivotModelShort)`; read `{relationName}_{scopedTargetFk}` from request; use `pivotFk` for parent FK, `scopedTargetFk` for target FK
- [x] 2.4 **StubRenderer::buildStoreFields()**: Skip columns where `sqlType === 'special_relation'` — they are handled by pivotStoreFields
- [x] 2.5 **StubRenderer::buildUpdateFields()**: Skip columns where `sqlType === 'special_relation'` — handled by pivotStoreFields
- [x] 2.6 **StubRenderer::buildValidationRules()**: For scoped relations (pivotModel set), add rule for the hidden `{relationName}_{scopedTargetFk}` input

## Phase 3: Model Fix

- [x] 3.1 **ParticipanteCobro.php**: Change `$fillable` from associative array to simple array: `['Cliente_id', 'Cobro_id', 'monto', 'rol']`

## Phase 4: Verification

- [x] 4.1 Assert `getScopedRelations()` returns correct `parentFk`, `targetFk`, `targetModel`, `targetTable` for Cobro+ParticipanteCobro (⚠️ DB-dependent: verify with DB only)
- [x] 4.2 Assert `buildScopedColumn()` sets `referencedTable=cliente`, `scopedTargetFk=Cliente_id` (✅ BuscadorScopedRelationsTest)
- [x] 4.3 Assert `buildCreateBuscadorCalls()` emits `tipo: 'cliente'` and hidden input for scoped relations (✅ BuscadorScopedRelationsTest)
- [x] 4.4 Assert `buildPivotStoreFields()` uses `buscadorInputName()` producing distinct names per relation (✅ BuscadorScopedRelationsTest)
- [x] 4.5 Regenerated Cobro controller and views — store() creates ParticipanteCobro with correct `Cobro_id`, `Cliente_id`, `rol` ; buscador queries `cliente` table with `tipo: 'cliente'`
