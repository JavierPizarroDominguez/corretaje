# Design: Unified Relationship View Data Provider

## Technical Approach

Extract FK-to-view-data logic from `StubRenderer` into a dedicated `RelationshipViewDataProvider` that handles all three patterns uniformly. The existing stubs (`fk-data-line.stub`, `fk-compact-array-line.stub`) are already generic — they only need `relatedVar`, `RelatedModel`, and `displayField`. The provider's job is to collect those three values from three sources: direct FK columns (same as today), scoped pivot columns (currently skipped), and belongsToMany relations (currently invisible).

## Architecture Decisions

### Decision 1: Location

| Option | Tradeoff | Decision |
|--------|----------|----------|
| `app/Generator/Rendering/` | Same namespace as `StubRenderer`; rendering concern | ✅ **Chosen** — view data IS rendering, not schema |
| `app/Generator/Rendering/ViewData/` | Cleaner sub-namespace but single-class dir | ❌ Over-engineered for one class |
| `app/Generator/Schema/` | Not a schema concern | ❌ Wrong layer |

### Decision 2: Dependency injection

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Constructor: `RelationResolver`, Method: `buildViewData(TableSchema)` | Resolver is a stable service dependency; TableSchema is per-call data | ✅ **Chosen** — clean separation |
| Everything via method arguments | Verbose callers | ❌ |
| Everything via constructor | Can't reuse provider across tables | ❌ |

### Decision 3: Return type

| Option | Tradeoff | Decision |
|--------|----------|----------|
| `array[]` — flat array of associative arrays | Simple, matches StubRenderer's style | ✅ **Chosen** — no new DTO needed |
| Typed `ViewDataItem` DTO | Type safety but overhead for one consumer | ❌ |
| Collection of objects | Extra abstraction | ❌ |

### Decision 4: belongsToMany source

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Call `RelationResolver::resolve($modelClass)` filter `belongsToMany` | Works now, no schema changes | ✅ **Chosen** — SchemaBuilder unchanged |
| Surface belongsToMany in `TableSchema.columns` | Requires new synthetic ColumnMetadata entries | ❌ Mixes concerns |

### Decision 5: How StubRenderer consumes it

| Option | Tradeoff | Decision |
|--------|----------|----------|
| `buildFkData()` delegates to provider; keeps itself as public API | Zero external API change; `renderController()` unchanged | ✅ **Chosen** — internal refactor only |
| Replace `buildFkData()` callsites directly | Touches `renderController()`, `renderShowView()`, etc. | ❌ Unnecessary churn |

### Decision 6: N+1 prevention

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Provider returns metadata only; eager load generated separately | Works today: `resolveEagerLoadStrategy()` already includes `belongsToMany` | ✅ **Chosen** — no new query logic in provider |
| Provider generates `withCount()` calls | Duplicates eager load logic | ❌ |

## Data Flow

    GenCrudCommand
         │
         ├─ SchemaBuilder::build($table)
         │    └─ TableSchema (columns, modelClass, etc.)
         │
         ├─ RelationResolver (singleton)
         │
         └─ StubRenderer::renderController($schema)
              │
              ├─ buildFkData($schema)
              │    └─ RelationshipViewDataProvider::buildViewData($schema)
              │         ├─ [direct FK]  columns where isForeignKey && !special_relation
              │         ├─ [scoped]     columns where sqlType=special_relation && pivotModel
              │         └─ [btm]        RelationResolver::resolve($modelClass) filter belongsToMany
              │         └─ returns array of [relatedVar, RelatedModel, displayField, referencedTable]
              │
              ├─ buildFkCompact($schema)       ─┐
              ├─ buildFkCompactArray($schema)   ─┤─ all delegate to provider
              └─ buildEagerLoad($schema)        ─┘ (unchanged)

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Generator/Rendering/RelationshipViewDataProvider.php` | **Create** | New service with `buildViewData(TableSchema): array` |
| `app/Generator/Rendering/StubRenderer.php` | **Modify** | `buildFkData()`, `buildFkCompact()`, `buildFkCompactArray()` delegate to provider; inject provider via constructor |
| `app/Generator/GeneratorServiceProvider.php` | **Modify** | Register `RelationshipViewDataProvider` as singleton |
| `app/Generator/Introspection/RelationResolver.php` | **Modify** | Add `getBelongsToManyRelations(string $modelClass): array` helper method |
| `stubs/fragments/fk-data-line.stub` | None | Already generic — no change needed |
| `stubs/fragments/fk-compact-array-line.stub` | None | Already generic — no change needed |

## Interfaces / Contracts

```php
// New provider — single public method
class RelationshipViewDataProvider
{
    public function __construct(private RelationResolver $relationResolver) {}

    /**
     * @return array<int, array{
     *   relatedVar: string,
     *   relatedModelName: string,
     *   displayField: string,
     *   isRelational: bool,
     *   referencedTable: string,
     * }>
     */
    public function buildViewData(TableSchema $schema): array;
}

// New helper on RelationResolver
class RelationResolver
{
    /** @return RelationMetadata[] filtered to type === 'belongsToMany' */
    public function getBelongsToManyRelations(string $modelClass): array;
}
```

Each returned item maps directly to stub replacements:
```
{{related_var}}    → item['relatedVar']       (e.g. 'cliente')
{{RelatedModel}}   → item['relatedModelName'] (e.g. 'Cliente')
{{display_field}}  → item['displayField']     (e.g. 'nombre')
```

## Testing Strategy

| Layer | What | How |
|-------|------|-----|
| Unit | Provider emits correct items for each pattern | Mock `RelationResolver`, create `TableSchema` with known columns; assert 3 items (1 direct FK, 1 scoped, 1 btm) |
| Unit | Scoped pivot includes `pivotModel` columns | ColumnMetadata with `sqlType=special_relation`, `pivotModel=ParticipanteCobro` → produces Cliente item |
| Unit | belongsToMany filtered from resolver result | Mock `getBelongsToManyRelations` returns 2 relations → provider includes both |
| Integration | StubRenderer output identical for existing belongsTo FK | Diff `buildFkData()` output before/after refactor on a test schema with only direct FKs |
| Integration | Output includes scoped + btm items | Same schema with added special_relation columns and mocked belongsToMany → items appear in output |

## Migration / Rollout

No data migration required. Rollback: delete provider file, revert StubRenderer changes, remove singleton registration.

## Open Questions

None.
