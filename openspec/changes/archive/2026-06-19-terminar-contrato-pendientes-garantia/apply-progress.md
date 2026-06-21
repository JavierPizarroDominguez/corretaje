# Apply Progress: Terminar contrato pending UI and guarantee discounts

## Status

- Phase 1 through Phase 6.2 are complete in `tasks.md`.
- Phase 6.3 remains open because stacked-modal cleanup still requires browser verification.
- Continuation bugfix applied: closing the child cobro/full-refund modal now restores the parent `Terminar Contrato` modal state.
- Follow-up refinement applied: if Bootstrap removes the parent backdrop while a child modal closes, the termination flow recreates a parent backdrop and removes it when no modal remains; discount rows now use the same responsive table/card contract as pending cobros.
- Requirement-change continuation applied: final discount removal no longer opens a confirmation modal; removing all discount rows immediately recalculates the full guarantee refund and reveals an inline Bootstrap warning near the discounts section.
- Small refinement applied: initial and newly added discount detail inputs now expose `placeholder="Detalle"`, including the empty-table row creation path after all discounts are removed.

## TDD Cycle Evidence

| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
|------|-----------|-------|------------|-----|-------|-------------|----------|
| Stacked child modal cleanup bugfix | `tests/Feature/FichaContratosDisplayTest.php` | Feature / JS markup contract | ✅ 7/7 baseline passed | ✅ Added assertions requiring `shown.bs.modal`, no `show.bs.modal` stacking, and parent-state restore helper | ✅ `./vendor/bin/phpunit --filter FichaContratosDisplayTest` passed 7/7 | ✅ Contract covers both z-index timing and parent modal state restoration | ✅ Split modal z-index preparation, backdrop stacking, and parent restoration helpers |
| Parent backdrop restoration + discount responsive cards | `tests/Feature/FichaContratosDisplayTest.php` | Feature / JS markup contract | ✅ 7/7 baseline passed | ✅ Added assertions requiring `ensureTerminacionParentBackdrop`, fallback parent backdrop markup, and discount table mobile-card classes | ✅ `./vendor/bin/phpunit --filter FichaContratosDisplayTest` passed 7/7 | ✅ Covered both modal-backdrop restoration and discount table/card responsive contract | ✅ Reused local table labeler for pending and discount tables; scoped backdrop cleanup to termination modal flow |
| Requirement change: inline full-refund warning | `tests/Feature/FichaContratosDisplayTest.php` | Feature / JS markup contract | ✅ 7/7 baseline passed | ✅ Added assertions requiring inline warning markup and absence of obsolete full-refund modal/accept handler before production changes | ✅ `./vendor/bin/phpunit --filter FichaContratosDisplayTest` passed 7/7 | ✅ Covered modal removal, no native dialogs, warning styling/text, zero-row recalculation, and add-row recovery path | ✅ Removed obsolete modal state and reused recalculation to toggle the warning |
| Small refinement: discount detail placeholder | `tests/Feature/FichaContratosDisplayTest.php` | Feature / JS markup contract | ✅ 7/7 baseline passed | ✅ Added assertions requiring rendered `placeholder="Detalle"` and JS reset to `description.placeholder = 'Detalle'` | ✅ `./vendor/bin/phpunit --filter FichaContratosDisplayTest` passed 7/7 | ✅ Covered initial row, cloned row preservation/reset, and empty-table `createAdjustmentRow()` path | ➖ None needed — scoped attribute/reset only |

## Tests

- ✅ Baseline safety net: `./vendor/bin/phpunit --filter FichaContratosDisplayTest` — 7 tests, 106 assertions passing; 1 PHPUnit deprecation.
- ✅ RED confirmation: focused test failed before implementation on missing discount responsive table classes and missing parent-backdrop restoration helper.
- ✅ GREEN: `./vendor/bin/phpunit --filter FichaContratosDisplayTest` — 7 tests, 110 assertions passing; 1 PHPUnit deprecation.
- ✅ Requirement-change safety net: `./vendor/bin/phpunit --filter FichaContratosDisplayTest` — 7 tests, 110 assertions passing; 1 PHPUnit deprecation.
- ✅ Requirement-change RED: focused test failed before implementation on missing inline full-refund warning.
- ✅ Requirement-change GREEN: `./vendor/bin/phpunit --filter FichaContratosDisplayTest` — 7 tests, 114 assertions passing; 1 PHPUnit deprecation.
- ✅ Placeholder-refinement safety net: `./vendor/bin/phpunit --filter FichaContratosDisplayTest` — 7 tests, 114 assertions passing; 1 PHPUnit deprecation.
- ✅ Placeholder-refinement RED: focused test failed before implementation on missing `placeholder="Detalle"` and cloned-row placeholder reset.
- ✅ Placeholder-refinement GREEN: `./vendor/bin/phpunit --filter FichaContratosDisplayTest` — 7 tests, 117 assertions passing; 1 PHPUnit deprecation.

## Manual Verification Still Needed

1. Open a contract page and click `Terminar contrato`.
2. Click a pending cobro button inside the termination modal.
3. Confirm the cobro detail modal appears above `Terminar contrato`.
4. Close the cobro detail modal.
5. Confirm the parent `Terminar contrato` modal remains dimmed by a backdrop, can still scroll, and its `Cerrar` button works.
6. Remove all discount rows and confirm no confirmation modal opens; the inline full-guarantee warning appears near discounts.
7. On desktop, confirm discounts render as a table.
8. On mobile width, confirm each discount row renders as a card with labels for Concepto, Detalle, Monto, and Acciones.
9. Confirm the default discount detail input shows placeholder `Detalle`.
10. Click `Agregar descuento` after removing all rows and confirm a fresh discount row appears with placeholder `Detalle` and the inline warning hides after recalculation.
