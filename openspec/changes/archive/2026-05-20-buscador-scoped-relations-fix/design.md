# Design: Buscador Scoped Relations Fix

## Technical Approach

Fix the CRUD generator to emit correct target model, FK, input names, and controller logic for hasOne-through-pivot relations (deudor, acreedor). The pivot model's own `belongsTo` definitions are the source of truth for resolving target model and FK columns. All changes are guarded by `hasPivotTable` / `pivotModel` — non-scoped buscadores are untouched.

## Architecture Decisions

### Decision: Resolve target model from pivot's belongsTo

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Parse FK from hasOne method source | Fragile, breaks with formatting changes | ❌ |
| Use `getForeignKey()` on pivot | Returns `participante_cobro_id` not `Cobro_id` | ❌ |
| Use `RelationResolver::resolve()` on pivot model | Robust, handles custom FK names, follows existing pattern | ✅ |

Use `RelationResolver->resolve($pivotModel)` to introspect the pivot's `belongsTo` relations. The `belongsTo` whose `relatedModel` matches the parent is the parent FK; the other is the target FK + target model.

### Decision: Store target metadata on ColumnMetadata

Add `scopedTargetFk` and replace pivot-related fields with resolved values:

| Field | Current (wrong) | Fixed |
|-------|-----------------|-------|
| `referencedTable` | pivot table (`participante_cobro`) | target table (`cliente`) |
| `relatedModelName` | pivot short class (`ParticipanteCobro`) | target short class (`Cliente`) |
| `relatedModelVariable` | pivot variable (`participanteCobro`) | target variable (`cliente`) |
| `pivotFk` | `getForeignKey()` (`participante_cobro_id`) | explicit belongsTo FK (`Cobro_id`) |
| *(new)* `scopedTargetFk` | — | target FK from pivot's belongsTo (`Cliente_id`) |

This makes `buildCreateBuscadorCalls()` automatically emit correct `tipo: 'cliente'` and the correct `firstOrCreate` model, while `buildPivotStoreFields()` has the FK columns it needs.

### Decision: Skip scoped relations in base store/update fields

Current base `store_fields` block generates `$cobro->deudor = $id` which fails for hasOne. Fix: skip `sqlType === 'special_relation'` columns in `buildStoreFields()` and `buildUpdateFields()` — they are handled exclusively by `buildPivotStoreFields()`. Guarded by existing `pivotModel` check.

### Decision: onSelect sets hidden FK input

For scoped relations, the buscador's `onSelect` callback also sets a hidden input `<input type="hidden" name="{relationName}_{scopedTargetFk}">` with `item.id`. This value is submitted with the form and used by controller pivot creation code. Non-scoped buscadores unchanged.

### Decision: Stub changes vs PHP-only

No new stubs needed. Hidden inputs are appended in `buildCreateFormFields()` via inline HTML after the buscador fragment. The existing `modal-create.stub` uses `{{create_fields}}` placeholder which captures everything.

## Data Flow

```
User types in buscador input (e.g. "nombre-deudor")
    ↓
buscador JS queries /buscador?q=X&{tipo}=1   (tipo = 'cliente')
    ↓
BuscadorController searches Cliente table
    ↓
User selects item → onSelect fires
    ↓
  sets display input "nombre-deudor" = item.texto
  sets hidden input "deudor_Cliente_id" = item.id
    ↓
Form submitted → CobroController::store()
    ↓
validation: nombre-deudor (string), deudor_Cliente_id (exists:cliente,id)
    ↓
scoped_store_fields: Cliente::firstOrCreate(['nombre' => ...])
    → new ParticipanteCobro({Cobro_id, Cliente_id, rol: 'Deudor'})
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Generator/Introspection/ColumnMetadata.php` | Modify | Add `scopedTargetFk` property |
| `app/Generator/Introspection/RelationResolver.php` | Modify | `getScopedRelations()`: resolve pivot's belongsTo for parent/target FK and target model |
| `app/Generator/Schema/SchemaBuilder.php` | Modify | `buildScopedColumn()`: use resolved target model for `referencedTable`, `relatedModelName`, `relatedModelVariable`; pass `scopedTargetFk` |
| `app/Generator/Rendering/StubRenderer.php` | Modify | `buildCreateBuscadorCalls()`: add hidden FK in onSelect for scoped; `buildPivotStoreFields()`: use `buscadorInputName()`, target model firstOrCreate; `buildStoreFields()`: skip scoped; `buildUpdateFields()`: add scoped update; `buildValidationRules()`: add hidden FK rule |
| `app/Models/ParticipanteCobro.php` | Modify | Fix `$fillable` to simple array syntax |
| `stubs/fragments/create-field-fk-buscador.stub` | None | No change — hidden input added inline in PHP |

## Interfaces / Contracts

### ColumnMetadata additions (after line 71)

```php
public readonly ?string $scopedTargetFk = null,
```

### getScopedRelations() new return keys

```php
[
    // ... existing keys ...
    'parentFk'     => 'Cobro_id',          // explicit FK from pivot's belongsTo(Cobro)
    'targetFk'     => 'Cliente_id',        // FK from pivot's belongsTo(Cliente)
    'targetModel'  => 'App\Models\Cliente',
    'targetTable'  => 'cliente',
]
```

### Hidden input naming convention

Input name: `{relationName}_{scopedTargetFk}` → e.g. `deudor_Cliente_id`, `acreedor_Cliente_id`  
Input id: `input-create-{relationName}-id` → e.g. `input-create-deudor-id`

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Generator unit | `getScopedRelations()` returns correct parentFk/targetFk/targetModel | Assert against Cobro + ParticipanteCobro |
| Generator unit | `buildScopedColumn()` sets referencedTable=cliente, scopedTargetFk=Cliente_id | Assert ColumnMetadata output |
| Generator unit | `buildCreateBuscadorCalls()` emits tipo='cliente' for deudor | Assert generated JS string |
| Generator unit | `buildPivotStoreFields()` uses buscadorInputName (nombre-deudor vs nombre-acreedor) | Assert distinct input names |
| Integration | Re-generate Cobro controller, assert store() creates ParticipanteCobro correctly | Run generator, inspect output |
| Manual | Create Cobro via UI with deudor + acreedor | Verify both pivot records created |

## Migration / Rollout

No migration required. Re-run CRUD generator for `cobro` table. Existing records unchanged. `ParticipanteCobro.$fillable` fix is a one-line model edit — safe for existing code.

## Open Questions

- [ ] What happens to the update path for scoped hasOne? Current generated code uses `$cobro->deudor` which is broken. The fix should find existing pivot record by parent FK + scope and update `Cliente_id`, or create if not found. Confirm approach in spec.
- [ ] `buildEditBuscadorCalls()` (line 398) also generates buscador calls — should scoped relations also get the hidden FK input there? Current implementation only covers `buildCreateBuscadorCalls`.
- [ ] Is there a `participanteCobroCount` / `participanteCobroOptions` variable dependency in the create view that needs updating? Currently uses pivot model count — should use target model count.
