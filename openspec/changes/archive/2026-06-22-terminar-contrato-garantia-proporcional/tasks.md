# Tasks: Terminar Contrato con Garantía Proporcional

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 700-950 |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR1 schema+backend core → PR2 pending APIs+payment guard → PR3 UI+view tests |
| Delivery strategy | force-chained |
| Chain strategy | feature-branch-chain |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: High

### Work Units

| Unit | Goal | PR | Dependency |
|------|------|----|------------|
| 1 | Schema/types + termination/proportional tests | PR 1 | none |
| 2 | Refund finalization + pending payloads | PR 2 | PR 1 |
| 3 | Warning/refund modals + routing | PR 3 | PR 2 |

## Dependencies

Schema/types → termination pending refund → finalization endpoint → pending payloads → frontend routing.

## Phase 1: Schema and Domain Foundation

- [x] 1.1 Update `corretaje-bd.sql` `Cobro.tipo` enum with both proportional types.
- [x] 1.2 Add safe MySQL ALTER migration/SQL preserving values; no `migrate:fresh`, resets, wipes, or table recreation.
- [x] 1.3 Update `app/Models/Cobro.php`, `config/cobro_roles.php`, `CobroController`, and `CobroConceptoFormatter` for proportional types.

## Phase 2: Backend Termination Core

- [x] 2.1 Add calculator in/near `TerminarContratoService`: real month divisor, clamped `dia_pago`, start inclusive, end exclusive.
- [x] 2.2 Modify `TerminarContratoRequest`/`TerminarContratoController` so termination ignores/removes discount rows.
- [x] 2.3 Modify `TerminarContratoService` to lock idempotently, set `fecha_termino`, create one pending refund with arrendador debtor/arrendatario creditor, and proportional ingreso/egreso.
- [x] 2.4 Verify termination creates no discount cobros, `Descuento_Garantia`, `Transaccion`, or `Transaccion_Cobro`.

## Phase 3: Refund Finalization and Pending APIs

- [x] 3.1 Create `GarantiaRefundService`, `GarantiaRefundRequest`, controller action, and `POST /api/cobros/{cobro}/devolver-garantia`.
- [x] 3.2 Finalization locks, rejects duplicates/excess, creates discount cobros as `Pagado`, links `Descuento_Garantia`, pays refund, and creates one positive-amount transaction/pivot.
- [x] 3.3 Update `PagarCobroController` to block generic payment bypass for guarantee refunds.
- [x] 3.4 Add dashboard/cliente/propiedad pending metadata: refund flag, contract id, `fecha_termino`, `plazo_restante_dias`, deadline, base amount.

## Phase 4: Frontend Integration

- [x] 4.1 Update `resources/views/components/contratos.blade.php` warning flow: disabled/loading submit, modal feedback, no termination discounts.
- [x] 4.2 Add refund modal mode with `Plazo restante`, editable discounts, and `Devolver garantía` using local loading helpers.
- [x] 4.3 Update dashboard, cliente, propiedad, and pending partial JS/Blade: refund opens devolution modal; normal cobros unchanged.

## Phase 5: Tests

- [x] 5.1 Add PHPUnit formula tests for 28, 29, 30, 31-day months, clamp, and zero-day cases.
- [x] 5.2 Add termination tests for idempotency, pending refund participants, proportional cobros, and no discounts/transactions.
- [x] 5.3 Add `Devolver garantía` tests: positive, zero, excessive, duplicate, and discount cobros `Pagado`.
- [x] 5.4 Add pending API/view tests for routing, `Plazo restante`, loading helpers, and no native dialogs.
