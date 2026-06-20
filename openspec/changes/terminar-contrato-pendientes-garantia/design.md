# Design: Terminar contrato pending UI and guarantee discounts

## Technical Approach

First restore the baseline contract-card heading rule in `resources/views/components/contratos.blade.php`: single-unit properties show only the property, while multi-unit properties show unidad + property. Then keep the termination change local to contract views: reshape each contract's pending `cobros` to the same array contract consumed by `components._pendientes-cobros-buttons`, render the ficha/index `.table-card-mobile pendientes-dashboard-table ficha-pendientes-table` pattern inside the Terminar Contrato hidden modal content, and keep pending cobros out of guarantee math. Discount rows are the only calculation inputs.

## Architecture Decisions

| Decision | Alternatives considered | Rationale |
|---|---|---|
| Mirror ficha/index table locally and reuse `_pendientes-cobros-buttons` | Extract a shared table/payment module now; use `_pendientes-role-table` | Lowest regression scope and matches current ficha/index UI contract without touching dashboard/ficha flows. |
| Gate heading display by property unit count | Always show unidad; always hide unidad | Matches clarified baseline behavior and prevents `FichaContratosDisplayTest` from mixing baseline failures with the termination-modal RED step. |
| Map cobros in Blade using existing eager-loaded relations and `CobroConceptoFormatter` | Add a new API endpoint or controller DTO | Contract pages already render server-side and controllers load pending cobros; no new fetch is needed. |
| Include one contracts-page `#modalCobro` plus idempotent delegated JS in `components.contratos` | Add `Agregar cobro`; link to CRUD show modal | User explicitly forbids `Agregar cobro`; payment must reuse the existing `.btn-cobro` detail/payment model. |
| Re-label cloned mobile tables after `abrirModal()` | Depend only on `app.js` DOMContentLoaded observer | `abrirModal()` clones hidden content after initial labeling; cloned tables need labels applied safely. |
| Use an inline Bootstrap warning for zero discounts | Custom confirmation modal; native `confirm()` | The changed requirement removes confirmation entirely while preserving the no-native-dialog convention and the business warning text. |

## Data Flow

```text
Contrato cobros (pending states)
  -> Blade maps to {id, estado, tipo, monto, deudor, acreedor, servicio_id, fecha_cobro, concepto}
  -> role buckets by Deudor's ParticipanteContrato rol
  -> table-card-mobile + _pendientes-cobros-buttons
  -> .btn-cobro opens #modalCobro
  -> Registrar pago POST /api/cobro/pagar
  -> success: Bootstrap/flash feedback + safe page refresh or button-state update

Discount rows only
  -> sum .terminacion-ajuste .terminacion-amount
  -> Total descuentos
  -> Garantía - descuentos = Monto a devolver
```

## File Changes

| File | Action | Description |
|---|---|---|
| `resources/views/components/contratos.blade.php` | Modify | Build pending cobro role groups, render ficha/index table classes, add `Extra`, separate calculation selectors, zero-discount inline warning, cloned-table relabeling, and contracts `#modalCobro` behavior. |
| `resources/views/cliente/contratos.blade.php` | Review/possible no-op | Already includes `components.contratos`; no separate `Agregar cobro` surface should be added. |
| `resources/views/propiedad/contratos.blade.php` | Review/possible no-op | Same as cliente page. |
| `tests/Feature/FichaContratosDisplayTest.php` | Modify | Replace stale assertions with pending-table/payment button, `Extra`, inline warning text, no native dialogs, and math selector contract. |

Baseline heading contract: before termination-modal work, update tests and component logic so a property with one unidad renders the property only, and a property with multiple unidades renders unidad + property.

## Interfaces / Contracts

Pending button data MUST match existing payment JS expectations:

```php
[
  'id' => $cobro->id,
  'estado' => $cobro->estado,
  'tipo' => $cobro->tipo,
  'monto' => $cobro->monto,
  'deudor' => $deudorNombre,
  'deudor_id' => $deudorId,
  'acreedor' => $acreedorNombre,
  'acreedor_id' => $acreedorId,
  'servicio_id' => $cobro->Servicio_id,
  'fecha_cobro' => optional($fecha)->toIso8601String(),
  'concepto' => CobroConceptoFormatter::format(...),
]
```

Discount row contract: `.terminacion-ajuste` rows contain `.terminacion-amount`; pending cobro rows MUST NOT use `.terminacion-row` or `data-sign`. `Total descuentos = sum(adjustments)`. Refund MAY be negative if discounts exceed guarantee; do not clamp unless a later spec requires it.

## Desktop/Mobile and Stacked Modal Safety

The cloned modal content should call a local `labelTerminacionTables(preview)` from `initTerminacionContratoPreview()` to set `td[data-label]` from current `thead th`. Use delegated click handling for `.btn-cobro` because buttons live inside cloned content. When opening `#modalCobro` over `#modalPrincipal`, keep `#modalCobro` outside cloned hidden content, create/get the Bootstrap instance idempotently, and verify backdrops/focus manually.

## Testing Strategy

| Layer | What to Test | Approach |
|---|---|---|
| Feature | Contract termination markup | PHPUnit: pending table classes, role headers, `.btn-cobro data-cobro`, `Extra`, no `Agregar cobro`, inline warning text, no `alert/confirm/prompt`. |
| Feature | Contract card heading baseline | PHPUnit: single-unit property hides unidad in the card heading; multi-unit property shows unidad + property. |
| JS contract/manual | Calculations and row removal | Manual/browser: add/remove all discounts without confirmation, verify inline warning appears when no discounts remain, verify `Total descuentos` ignores pending cobros and refund equals guarantee minus discounts. |
| Integration/manual | Payment and stacked modals | Open Terminar Contrato, open cobro detail, register payment, verify loading spinner on button, Bootstrap feedback, modal close behavior, and mobile labels. |

## Migration / Rollout

No migration required. Roll out as Blade/JS-only changes. Rollback is reverting the touched Blade/test files.

## Risks / Rollback

- Stacked modals may leave body/backdrop state inconsistent; mitigate with manual verification and idempotent Bootstrap instances.
- Blade mapping duplicates API grouping logic; acceptable for scope, rollback is local.
- Dynamic mobile labels can regress after clone; mitigate with explicit relabeling during initialization.

## Open Questions

None.
