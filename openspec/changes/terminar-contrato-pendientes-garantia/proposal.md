# Proposal: Terminar contrato pending UI and guarantee discounts

## Intent

Make `Terminar contrato` use the same pending-payment experience as fichas/index while fixing guarantee-return math: pending cobros are informational/payable items, and guarantee discounts come only from user-added concepts.

## Scope

### In Scope
- Replace the current simple pending-cobros table in `components.contratos` with the ficha/index responsive pending-payment contract, including desktop/mobile behavior and cobro detail/payment modal.
- Keep only the existing `Agregar descuento` action for guarantee concepts; add `Extra` as a concept option.
- Calculate `Total descuentos` as the sum of added discount concepts only, and `Monto a devolver al arrendatario = garantía - total descuentos`.
- Allow removing every discount concept and show a custom Bootstrap confirmation with: `¡Atención! se devolverá la garantía en su totalidad al arrendatario. ¿Está seguro que no hay reparaciones o aseo que pagar?`
- Preserve app conventions: no native `alert`/`confirm`/`prompt`; use existing loading/modal feedback patterns for any fetch.

### Out of Scope
- No `Agregar cobro` button/action in `Terminar contrato`.
- No broad refactor of dashboard/ficha pending-payment code unless required for safe reuse.
- No database schema or migration changes.
- No changes to payment endpoint semantics beyond invoking existing behavior.

## Capabilities

### New Capabilities
- `contract-termination-guarantee`: Covers termination modal pending-cobro display, discount concepts, zero-discount confirmation, and guarantee refund calculation.

### Modified Capabilities
- `ficha-pendientes-mobile`: Reuse/mirror the existing responsive pending-payment table/button contract in the contract termination modal.
- `cobro-payment`: Allow the existing cobro detail/payment UX to be opened from termination modal pending buttons.

## Approach

Use the exploration recommendation: map each contract cobro to the ficha/index pending data shape, render the same `.table-card-mobile pendientes-dashboard-table` pattern and `_pendientes-cobros-buttons` role cells, then add/enable the compatible `#modalCobro` payment flow on contract pages. Remove pending cobros from `.terminacion-row` calculation inputs; only `.terminacion-ajuste` rows contribute to discounts.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `resources/views/components/contratos.blade.php` | Modified | Termination modal UI, cobro grouping/rendering, concept rows, calculations, remove-all confirmation. |
| `resources/views/cliente/contratos.blade.php` | Modified | Include/support cobro detail/payment modal if needed. |
| `resources/views/propiedad/contratos.blade.php` | Modified | Include/support cobro detail/payment modal if needed. |
| `tests/Feature/FichaContratosDisplayTest.php` | Modified | Update assertions for new termination-modal contract. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Stacked Bootstrap modals conflict | Med | Verify focus/backdrop behavior for termination + cobro detail modal. |
| Dynamic cloned modal misses mobile labels | Med | Reapply/ensure table-card labels after `abrirModal()` clone. |
| Payment JS duplication/regression | Med | Keep handlers idempotent and scoped; test existing payment contract. |

## Rollback Plan

Revert the Blade/JS changes in affected contract views/components and restore previous simple pending table/calculation tests. No data rollback required.

## Dependencies

- Existing pending-button partial, ficha/index table CSS/JS contract, Bootstrap modals, and `/api/cobro/pagar`.

## Success Criteria

- [ ] Termination modal shows pending cobros with ficha/index desktop and mobile behavior.
- [ ] Pending cobros can open detail/payment modal; no `Agregar cobro` action appears.
- [ ] Concepts include `Aseo final`, `Reparación`, and `Extra`; only `Agregar descuento` adds rows.
- [ ] Removing all concepts uses the required custom confirmation and refunds full guarantee when accepted.
- [ ] `Total descuentos` equals concept sum; refund equals guarantee minus discounts.
