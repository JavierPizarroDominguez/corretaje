# Tasks: Terminar contrato pending UI and guarantee discounts

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 260-380 |
| 400-line budget risk | Medium |
| Chained PRs recommended | No |
| Suggested split | Single PR; tests and Blade/JS changes together |
| Delivery strategy | ask-always |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Medium

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Restore contract-card heading baseline | PR 1 | Fix `FichaContratosDisplayTest` safety net before new RED work. |
| 2 | Test-first termination modal contract | PR 1 | Feature tests define markup, math selectors, no native dialogs. |
| 3 | Blade/JS implementation | PR 1 | Server-render pending table, discount math, modal payment wiring. |
| 4 | Verification polish | PR 1 | PHPUnit plus manual stacked-modal/mobile checks. |

## Phase 1: Restore Baseline Test Safety Net

- [x] 1.1 Update `tests/Feature/FichaContratosDisplayTest.php` fixtures/assertions so single-unit properties expect only property in contract-card headings.
- [x] 1.2 Add/adjust a multi-unit property scenario in `tests/Feature/FichaContratosDisplayTest.php` that expects unidad + property in the card heading.
- [x] 1.3 Update `resources/views/components/contratos.blade.php` heading rendering to pass the clarified single-unit vs multi-unit rule before changing termination-modal behavior.
- [x] 1.4 Run `./vendor/bin/phpunit --filter FichaContratosDisplayTest` and confirm remaining failures are only authorized termination-modal RED expectations.

## Phase 2: RED Tests

- [x] 2.1 Update `tests/Feature/FichaContratosDisplayTest.php` to assert ficha/index-style pending table classes, role headers, centered `.btn-cobro`, and clear empty state.
- [x] 2.2 Add assertions that Terminar Contrato exposes `Agregar descuento`, includes `Extra`, and never renders `Agregar cobro`.
- [x] 2.3 Add assertions for calculation selectors: pending rows lack `.terminacion-row`/`data-sign`; discount rows use `.terminacion-ajuste .terminacion-amount`.
- [x] 2.4 Add assertions for exact full-guarantee confirmation text and absence of `alert(`, `confirm(`, and `prompt(` in the termination component.

## Phase 3: Pending Cobro Rendering

- [x] 3.1 In `resources/views/components/contratos.blade.php`, map contract cobros to the `_pendientes-cobros-buttons` data shape using existing relations and `CobroConceptoFormatter`.
- [x] 3.2 Render the pending section with `.table-card-mobile.pendientes-dashboard-table.ficha-pendientes-table`, role buckets, centered cobro buttons, and clear no-pending copy.
- [x] 3.3 Review `resources/views/cliente/contratos.blade.php` and `resources/views/propiedad/contratos.blade.php`; only adjust includes if the shared component requires it.

## Phase 4: Discount Math and Confirmation

- [x] 4.1 In `resources/views/components/contratos.blade.php`, add `Extra` to discount concept options while keeping `Agregar descuento` as the only row-creation action.
- [x] 4.2 Refactor termination JS totals so `Total descuentos` sums only `.terminacion-ajuste .terminacion-amount` and refund equals garantía minus discounts.
- [x] 4.3 Replace final-discount removal with a Bootstrap/custom confirmation modal using the required Spanish text; do not use native dialogs.

## Phase 5: Payment Modal Integration

- [x] 5.1 Add one contracts-page `#modalCobro` integration in `resources/views/components/contratos.blade.php`, outside cloned hidden modal content.
- [x] 5.2 Use idempotent delegated handlers for `.btn-cobro` to open existing detail/payment behavior and preserve `/api/cobro/pagar` feedback conventions.
- [x] 5.3 Call local table-labeling after `abrirModal()` clones hidden content so mobile `td[data-label]` values match current headers.

## Phase 6: Verification

- [x] 6.1 Run `./vendor/bin/phpunit --filter FichaContratosDisplayTest`; fix failures without broad refactors.
- [x] 6.2 Run `./vendor/bin/phpunit` for regression coverage; never run destructive migrations.
- [ ] 6.3 Manually verify desktop/mobile Terminar Contrato: pending payment modal, spinner/modal feedback, no `Agregar cobro`, zero-discount confirmation, and stacked-modal cleanup.
