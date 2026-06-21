# Proposal: Terminar contrato con devolución de garantía

## Intent

Persist the existing `Terminar contrato` preview atomically: close administration, register guarantee discounts, create the guarantee refund cobro, and create a refund transaction only when refund money is positive.

## Scope

### In Scope
- Complete `resources/views/components/contratos.blade.php` with confirm action, frontend validation, disabled button/loading, and modal feedback.
- Add `POST /api/contratos/{contrato}/terminar`, request validation, and transaction-safe `TerminarContratoService`.
- Validate `sum(descuentos) <= garantía` in frontend and backend.
- Set `Contrato.fecha_termino = now()`.
- Create paid discount cobros with rich context; participants are explicit: `Deudor = arrendatario`, `Acreedor = arrendador`.
- Create refund cobro `Devolución Garantía Arrendatario`: `Pendiente` when refund > 0; `Pagado` with monto 0 when discounts consume the guarantee. Its `Acreedor` is arrendador.
- Add `Descuento_Garantia` pivot/model linking refund cobro to each discount cobro, with explicit Eloquent config.
- Create `Transaccion`/`Transaccion_Cobro` only when refund > 0.

### Out of Scope
- Modeling garantía as origin/destination or as a standalone transaction.
- Changing generic `POST /api/cobro/pagar` behavior.
- Running destructive DB commands or applying migrations to real MySQL.

## Capabilities

### New Capabilities
None.

### Modified Capabilities
- `contract-termination-guarantee`: add persistence, backend validation, cobro creation, discount/refund linking, and refund transaction rules.

## Approach

Use a dedicated controller/request/service. The service locks/reloads the contract, resolves arrendatario/arrendador, creates cobros/participants directly instead of relying on `CobroRelationshipResolver` for termination discounts, links `Descuento_Garantia`, and creates a transaction only for positive refunds.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `resources/views/components/contratos.blade.php` | Modified | Confirm fetch, validation, loading, feedback. |
| `routes/api.php` | Modified | Termination endpoint. |
| `app/Http/Controllers/Api/*` | New | Termination API action. |
| `app/Http/Requests/*` | New | Discount/guarantee validation. |
| `app/Services/TerminarContratoService.php` | New | Atomic workflow and refund transaction branch. |
| `app/Models/DescuentoGarantia.php` | New | Explicit pivot model. |
| `database/migrations/*descuento_garantia*` | New | Pivot table definition only. |
| `tests/Feature/Api/*` | New/Modified | Workflow, validation, zero-refund, participant tests. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Zero refund accidentally routed through payment logic | Med | Keep service separate; branch on refund > 0. |
| Participant semantics drift | Med | Explicit roles: arrendatario debtor, arrendador creditor. |
| Eloquent casing/composite pivot issues | Med | Explicit model config. |

## Rollback Plan

Revert route, controller/request/service, Blade, model, migration, and tests. If migrated, drop only `Descuento_Garantia` through reviewed rollback/backup process.

## Dependencies

- Existing `contract-termination-guarantee`, `cobro-payment`, `ui-loading-indicators`, and pivot/Eloquent conventions.
- SQL constraints require no zero-value `Transaccion`/`Transaccion_Cobro` rows.

## Success Criteria

- [ ] UI confirms termination without native dialogs and wraps fetch with loading utilities.
- [ ] Backend rejects discount sum above garantía.
- [ ] Termination sets `fecha_termino`, creates discount/refund cobros, and links discounts.
- [ ] Refund > 0 creates exactly one refund transaction link; refund = 0 creates none.
- [ ] Tests cover positive refund, full-discount refund, validation failure, and participant roles.
