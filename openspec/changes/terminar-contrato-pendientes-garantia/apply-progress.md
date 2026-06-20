# Apply Progress: Terminar contrato pending UI and guarantee discounts

## Status

- Phase 1 through Phase 6.2 are complete in `tasks.md`.
- Phase 6.3 remains open because stacked-modal cleanup still requires browser verification.
- Continuation bugfix applied: closing the child cobro/full-refund modal now restores the parent `Terminar Contrato` modal state.

## TDD Cycle Evidence

| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
|------|-----------|-------|------------|-----|-------|-------------|----------|
| Stacked child modal cleanup bugfix | `tests/Feature/FichaContratosDisplayTest.php` | Feature / JS markup contract | ✅ 7/7 baseline passed | ✅ Added assertions requiring `shown.bs.modal`, no `show.bs.modal` stacking, and parent-state restore helper | ✅ `./vendor/bin/phpunit --filter FichaContratosDisplayTest` passed 7/7 | ✅ Contract covers both z-index timing and parent modal state restoration | ✅ Split modal z-index preparation, backdrop stacking, and parent restoration helpers |

## Tests

- ✅ `./vendor/bin/phpunit --filter FichaContratosDisplayTest` — 7 tests, 106 assertions passing; 1 PHPUnit deprecation.

## Manual Verification Still Needed

1. Open a contract page and click `Terminar contrato`.
2. Click a pending cobro button inside the termination modal.
3. Confirm the cobro detail modal appears above `Terminar contrato`.
4. Close the cobro detail modal.
5. Confirm the parent `Terminar contrato` modal can still scroll and its `Cerrar` button works.
6. Repeat with the full-guarantee warning modal after removing the last discount.
