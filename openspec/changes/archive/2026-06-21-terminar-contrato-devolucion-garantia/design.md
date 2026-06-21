# Design: Terminar contrato con devolución de garantía

## Technical Approach

Persist the existing preview through a dedicated API endpoint and service, not by overloading `/api/cobro/pagar`. `TerminarContratoService` owns the atomic workflow: lock the contract, validate guarantee math again, close it with `fecha_termino = now()`, create discount/refund cobros, link discounts through `Descuento_Garantia`, and create transfer rows only for positive refunds.

## Architecture Decisions

| Decision | Choice | Alternatives considered | Rationale |
|---|---|---|---|
| Workflow boundary | New `POST /api/contratos/{contrato}/terminar` + request + controller + service | Put logic in Blade/controller; reuse `/api/cobro/pagar` | The flow has multiple writes and special zero-refund rules. A service keeps it transaction-safe and testable. |
| Discount linkage | Dedicated `DescuentoGarantia` model over `Descuento_Garantia` | Anonymous `belongsToMany`; no model | Project uses explicit models for DB-style pivots (`TransaccionCobro`, `ParticipanteCobro`). Explicit table/keys avoids Laravel casing/composite-key guesses. |
| Participants | Create participants directly in service | Use `CobroRelationshipResolver` | Existing role map conflicts with corrected termination semantics for `Reparación`/`Extra` and guarantee refund. |
| Transactions | Internal helper creates `Transaccion`/`Transaccion_Cobro` only when refund > 0 | Mark zero refund paid through payment endpoint | SQL and domain require no zero-value transaction rows; garantía is not origin/destination. |

## Data Flow

```text
Blade modal
  └─ validates discounts <= garantía, disables button, showElLoading(button)
      └─ POST /api/contratos/{id}/terminar
          └─ TerminarContratoRequest
              └─ TerminarContratoService DB::transaction()
                  ├─ lock Contrato, resolve Arrendatario/Arrendador
                  ├─ set fecha_termino = now()
                  ├─ create paid discount Cobro rows + Participante_Cobro
                  ├─ create refund Cobro: Pendiente if refund > 0, Pagado monto 0 if refund = 0
                  ├─ create Descuento_Garantia rows
                  └─ if refund > 0: create Transaccion + Transaccion_Cobro
```

## File Changes

| File | Action | Description |
|---|---|---|
| `routes/api.php` | Modify | Add named POST termination route. |
| `app/Http/Controllers/Api/TerminarContratoController.php` | Create | Thin JSON controller delegating to service. |
| `app/Http/Requests/TerminarContratoRequest.php` | Create | Validates `descuentos[]`, allowed concepts, integer amounts, and discount total. |
| `app/Services/TerminarContratoService.php` | Create | Atomic termination, cobro creation, pivot linking, positive-refund transaction branch. |
| `app/Models/DescuentoGarantia.php` | Create | Explicit pivot model. |
| `app/Models/Cobro.php` | Modify | Add named guarantee-discount/refund relationships. |
| `resources/views/components/contratos.blade.php` | Modify | Add confirm button, payload collection, frontend ceiling validation, loading, modal feedback. |
| `database/migrations/*create_descuento_garantia_table.php` | Create | Defines pivot table only; do not run destructive DB commands. |
| `tests/Feature/Api/TerminarContratoControllerTest.php` | Create | Covers happy paths, validation rollback, participants, and transaction branching. |

## Interfaces / Contracts

Request JSON:

```json
{"descuentos":[{"concepto":"Aseo Final","detalle":"Limpieza final","monto":80000}]}
```

`DescuentoGarantia` should mirror existing pivot models:

```php
protected $table = 'Descuento_Garantia';
public $incrementing = false;
public $timestamps = false;
protected $fillable = ['Cobro_Devolucion_id', 'Cobro_Descuento_id'];

public function devolucion() { return $this->belongsTo(Cobro::class, 'Cobro_Devolucion_id'); }
public function descuento() { return $this->belongsTo(Cobro::class, 'Cobro_Descuento_id'); }
```

`Cobro` relations should be explicit and directional:

- `descuentosGarantia()` → `hasMany(DescuentoGarantia::class, 'Cobro_Devolucion_id')` for refund cobro → link rows.
- `devolucionGarantia()` → `hasOne(DescuentoGarantia::class, 'Cobro_Descuento_id')` for discount cobro → refund link row.
- Avoid direct convenience `belongsToMany` unless tests prove the custom key names stay readable and safe.

## Testing Strategy

| Layer | What to Test | Approach |
|---|---|---|
| Feature API | Positive refund, full discount, excessive discounts, missing participants | `DatabaseTransactions`; assert DB rows and response JSON. |
| Model | `DescuentoGarantia` relationships from refund and discount cobros | Create minimal cobros/link row and assert relation targets. |
| UI contract | No native dialogs; fetch uses loading utilities; frontend blocks excessive discounts | Extend `FichaContratosDisplayTest` with source assertions. |

## Migration / Rollout

Create a reviewed migration for `Descuento_Garantia`; do not run `php artisan migrate` against the real MySQL database. No data backfill is required because links are only for newly terminated contracts.

## Open Questions

- None blocking.
