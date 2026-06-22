# Design: Terminar Contrato con Garantía y Renta Proporcional

## Technical Approach

Keep the existing Laravel MVC + service shape, but enforce two lifecycle boundaries. `TerminarContratoService` only terminates the contract and creates pending obligations. A new guarantee refund finalization service owns discount cobro creation, `Descuento_Garantia` linkage, final refund recalculation, payment marking, and the single transaction/pivot creation. UI feedback stays modal-based with local loading indicators; no `alert`, `confirm`, or `prompt`.

## Architecture Decisions

| Decision | Choice | Alternatives considered | Rationale |
|---|---|---|---|
| Termination payload | Remove `descuentos` from `TerminarContratoRequest`/service contract. If old clients send rows during rollout, ignore them and log/return no discount state. | Continue validating/persisting discounts at termination. | Specs now require warning confirmation without discount submission; persistence belongs to refund finalization. |
| Refund amount at termination | Create one pending `Devolución Garantía Arrendatario` for base `contrato.garantia` (or the already-defined refundable base if implementation adds a workflow field later), with arrendador as debtor and arrendatario as creditor. | Create refund net of discounts. | Discounts are editable until `Devolver garantía`; storing the base amount preserves auditability and lets finalization recalculate. The refund is still an obligation between participants, not a transaction origin/destination. |
| Discount cobro ownership | Move/reuse current private `createDiscountCobro()` logic from `TerminarContratoService` into `GarantiaRefundService` (or a small shared helper used only there). | Duplicate discount creation code. | Existing participant/detail behavior is useful, but invoking it during termination is now wrong. |
| Finalization endpoint | Add dedicated endpoint, e.g. `POST /api/cobros/{cobro}/devolver-garantia`. | Use generic `/api/cobro/pagar`. | Generic payment cannot validate final discounts, create links, or protect guarantee-specific idempotency. |
| Proportional formula | `round(renta / daysInMonth(fecha_termino) * max(0, day(fecha_termino) - min(dia_pago, daysInMonth)))`. | Fixed 30-day divisor. | Matches locked spec: payment day inclusive, termination day exclusive, real month length. |

## Data Flow

```text
Warning modal ─accept→ TerminarContratoController ─→ TerminarContratoService
  └ no discount rows     ├ lock contract and detect existing termination artifacts
                         ├ set fecha_termino
                         ├ create pending base refund cobro
                         └ create proportional ingreso/egreso cobros

Pending refund button ─→ Refund modal ─Devolver garantía→ GarantiaRefundService
  editable discounts                            ├ lock refund cobro/contract
                                                ├ create discount cobros
                                                ├ create Descuento_Garantia links
                                                ├ set final refund monto/estado Pagado
                                                └ create exactly one Transaccion + pivot if monto > 0
```

## File Changes

| File | Action | Description |
|---|---|---|
| `app/Services/TerminarContratoService.php` | Modify | Remove discount persistence, `DescuentoGarantia`, and transaction creation; add proportional cobros and idempotency. |
| `app/Services/GarantiaRefundService.php` | Create | Finalize pending refund, own moved `createDiscountCobro`, recalculate amount, pay/refuse duplicates atomically. |
| `app/Http/Requests/TerminarContratoRequest.php` | Modify | Stop accepting discount rows for termination; keep confirmation/date inputs only if needed. |
| `app/Http/Requests/GarantiaRefundRequest.php` | Create | Validate final discount rows/concepts and reject totals greater than refundable base. |
| `app/Http/Controllers/Api/*`, `routes/api.php` | Modify | Keep termination focused; add refund finalization route/controller; block generic payment bypass for guarantee refunds. |
| `config/cobro_roles.php`, `CobroController`, formatters | Modify | Add proportional types and keep guarantee refund labels/roles consistent. |
| `corretaje-bd.sql`, planned migration, SQLite test schema/constants | Modify/Create | Add proportional enum values safely; do not run migrations in this phase. |
| `resources/views/components/contratos.blade.php`, dashboard/cliente/propiedad pending views/JS | Modify | Warning flow, refund modal with `Plazo restante`, local spinners, Bootstrap modal feedback only. |

## Interfaces / Contracts

Termination response returns `contrato_id`, `fecha_termino`, `devolucion_cobro_id`, proportional cobro ids, and no discount/transaction ids. The pending refund cobro stores arrendador as debtor and arrendatario as creditor. Pending payloads add `is_guarantee_refund`, `contrato_id`, `fecha_termino`, `plazo_restante_dias`, `refund_deadline`, and refundable/base amount metadata.

Finalization request accepts `descuentos[]` with `concepto`, `detalle`, `monto`. It locks the refund cobro, verifies it is pending `Devolución Garantía Arrendatario`, creates paid discount cobros and links, updates refund `monto = max(0, base - totalDescuentos)`, marks it `Pagado`, and creates one transaction/pivot only when final amount is positive.

Termination idempotency: under contract lock, existing `fecha_termino` or existing termination cobros returns existing ids without duplicates. Finalization idempotency: if refund is already `Pagado` or has a pivot/links, reject duplicate finalization with 422 and create no new rows.

## Testing Strategy

| Layer | What to Test | Approach |
|---|---|---|
| Unit | Proportional calculator edge cases. | PHPUnit service tests. |
| Feature | Termination creates pending base refund + proportional cobros, no discounts/links/transactions; repeated calls do not duplicate. | API tests with transactions. |
| Feature | Finalization creates discount cobros/links, recalculates refund, pays with exactly one transaction; zero refund has no transaction; duplicates rejected. | API tests. |
| View/API | Pending routing to refund modal, no native dialogs, fetch loading indicators. | Feature/view/JS-oriented assertions. |

## Migration / Rollout

No live DB changes in design. Implementation updates `corretaje-bd.sql` and ships a non-destructive enum migration preserving existing values. Roll out backend first with termination ignoring legacy discount rows, then UI removes discount submission from termination and sends rows only to finalization.

## Review Workload Forecast

Decision needed before apply: Yes
Chained PRs recommended: Yes
400-line budget risk: High

Suggested slices: (1) backend termination/finalization services, requests, routes, tests; (2) schema/type support and pending API metadata; (3) UI modal/routing/loading feedback.

## Open Questions

- [ ] Should proportional egreso always use full `contrato.renta`, matching current `Egreso Renta Arrendador`, even when commissions exist elsewhere?
