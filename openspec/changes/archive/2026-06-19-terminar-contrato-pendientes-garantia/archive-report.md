# Archive Report: terminar-contrato-pendientes-garantia

## Status

Archived on 2026-06-19 in OpenSpec mode.

## Specs Synced

| Domain | Action | Details |
|--------|--------|---------|
| `contract-termination-guarantee` | Created | Created main spec from the full delta spec; 5 requirements captured. |
| `ficha-pendientes-mobile` | Updated | Added 1 requirement for Terminar Contrato pending table desktop/mobile parity. |
| `cobro-payment` | Updated | Added 1 requirement for Terminar Contrato cobro detail/payment entry point. |

## Implementation Outcomes Captured

- Terminar Contrato pending cobros use ficha/index desktop/mobile style and detail/payment flow.
- Terminar Contrato does not expose an `Agregar cobro` action.
- Discount concepts include `Aseo final`, `Reparación`, and `Extra`; detail inputs use placeholder `Detalle`; discounts render as desktop tables/mobile cards.
- `Total descuentos` sums only discount rows; refund equals garantía minus total discounts.
- Removing all discounts shows the inline `¡Atención!` warning instead of a modal confirmation.
- The stacked cobro detail/payment modal appears above the parent termination modal and restores parent scroll/backdrop on close.
- Contract headings show property only for single-unit properties and unidad + property for multi-unit properties.
- Ficha cliente/propiedad movement pages include transaction history under `Historial de movimientos`, remove `Reparaciones y gastos extras`, and render Cartola before transaction history.

## Verification Evidence

- `apply-progress.md` records focused `FichaContratosDisplayTest` passes through the implemented termination-modal refinements.
- The orchestration prompt reports `./vendor/bin/phpunit --filter FichaContratosDisplayTest` passed after modal changes.
- The orchestration prompt reports `php artisan test --filter=FichaPendientesVisualContractTest` passed after ficha movement ordering.

## Verification Gaps

- No `verify-report.md` was present in the active change directory at archive time.
- `tasks.md` still has manual verification task `6.3` unchecked for browser validation of stacked modal cleanup and desktop/mobile termination behavior.
- The movement ordering work was captured in this archive report from orchestration context, but no delta spec for that behavior existed under this change's `specs/` directory.

## Archive Verification

- Main specs were updated before moving the change folder.
- The archived folder preserves proposal, exploration, design, tasks, specs, apply-progress, and this archive report.
- Active change folder is absent after the archive move.

## Source of Truth Updated

- `openspec/specs/contract-termination-guarantee/spec.md`
- `openspec/specs/ficha-pendientes-mobile/spec.md`
- `openspec/specs/cobro-payment/spec.md`
