# Apply Progress: terminar-contrato-devolucion-garantia

## Mode

Strict TDD — `./vendor/bin/phpunit`

## Workload / PR Boundary

- Mode: chained PR slice
- Chain strategy: feature-branch-chain
- Current work unit: PR 3 UI wiring
- Boundary: Blade contract modal UI integration plus UI source/assertion tests only, based on PR 2 backend API/service
- Explicitly excluded: backend route/request/controller/service changes and unrelated working-tree changes

## Completed Tasks

- [x] 1.1 Create `tests/Feature/Models/DescuentoGarantiaTest.php` asserting refund→discount and discount→refund relations.
- [x] 1.2 Create `tests/Feature/Api/TerminarContratoControllerTest.php` for positive refund, zero refund, excessive discounts rollback, and participants.
- [x] 2.1 Create `database/migrations/*_create_descuento_garantia_table.php` for `Cobro_Devolucion_id` and `Cobro_Descuento_id`; do not run migrations against real MySQL.
- [x] 2.2 Create `app/Models/DescuentoGarantia.php` with explicit table, non-incrementing keys, fillable fields, no timestamps, and `devolucion()`/`descuento()` relations.
- [x] 2.3 Update `app/Models/Cobro.php` with directional `descuentosGarantia()` and `devolucionGarantia()` relations.
- [x] 3.1 Add `POST /api/contratos/{contrato}/terminar` in `routes/api.php` pointing to `TerminarContratoController`.
- [x] 3.2 Create `app/Http/Requests/TerminarContratoRequest.php` validating discounts, allowed concepts, integer amounts, and `sum(descuentos) <= garantía`.
- [x] 3.3 Create `app/Http/Controllers/Api/TerminarContratoController.php` as a thin JSON controller delegating to the service.
- [x] 3.4 Create `app/Services/TerminarContratoService.php` with `DB::transaction()`, locked contract reload, participant resolution, and `fecha_termino = now()`.
- [x] 3.5 In the service, create paid discount cobros with debtor arrendatario, creditor arrendador, and auditable contract/concept/detail context.
- [x] 3.6 In the service, create `Devolución Garantía Arrendatario`: pending when refund > 0, paid with monto 0 when refund = 0.
- [x] 3.7 In the service, create `Descuento_Garantia` links and create `Transaccion`/`Transaccion_Cobro` only when refund > 0.
- [x] 1.3 Extend `tests/Feature/FichaContratosDisplayTest.php` to assert no native dialogs, `fetch()`, `showElLoading`/`hideElLoading`, disabled confirm, and frontend ceiling validation.
- [x] 4.1 Update `resources/views/components/contratos.blade.php` to collect discount payload and block submit when discounts exceed garantía.
- [x] 4.2 Add confirm `fetch()` to `/api/contratos/{id}/terminar` with CSRF, disabled button, `showElLoading(btn)`, `hideElLoading(btn)`, and `flashModal` feedback only.
- [x] 4.3 Refresh or remove the terminated contract from the visible active-contract UI after success.
- [x] 5.3 Run `./vendor/bin/phpunit --filter FichaContratosDisplayTest`.

## TDD Cycle Evidence

| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
|------|-----------|-------|------------|-----|-------|-------------|----------|
| 1.1 | `tests/Feature/Models/DescuentoGarantiaTest.php` | Feature model | N/A (new test) | ✅ `DescuentoGarantia` missing class failed first | ✅ `./vendor/bin/phpunit tests/Feature/Models/DescuentoGarantiaTest.php` passed 2/2 | ✅ 2 relation directions: refund→discount and discount→refund | ✅ Table setup only drops table when created by the test |
| 2.1 | `tests/Feature/Models/DescuentoGarantiaTest.php` | Feature model | N/A (new migration file; migration not run) | ✅ Same RED required `Descuento_Garantia` link columns in test setup | ✅ Filtered model tests passed after migration file creation | ➖ Structural table definition; columns match both relation paths | ✅ No DB migration command executed |
| 2.2 | `tests/Feature/Models/DescuentoGarantiaTest.php` | Feature model | N/A (new model) | ✅ Same RED failed because `App\\Models\\DescuentoGarantia` did not exist | ✅ Model fillable/table/timestamps supported relationship persistence | ✅ `devolucion()` and `descuento()` both exercised | ✅ Kept explicit table, casts, fillable, no timestamps |
| 2.3 | `tests/Feature/Models/DescuentoGarantiaTest.php` | Feature model | ✅ `./vendor/bin/phpunit tests/Unit/PivotTableCasingTest.php` passed 9/9 before modifying `Cobro` | ✅ Same RED would fail without `Cobro` directional relation methods after model exists | ✅ `descuentosGarantia()` and `devolucionGarantia` returned persisted links | ✅ Both directions covered by separate tests | ✅ Existing `Cobro` relations left unchanged; only added directional methods |
| 1.2 | `tests/Feature/Api/TerminarContratoControllerTest.php` | Feature API | N/A (new test file) | ✅ New API tests failed 4/4 with 404 before route/controller/service existed | ✅ `./vendor/bin/phpunit tests/Feature/Api/TerminarContratoControllerTest.php` passed 4/4 after minimal backend implementation | ✅ Added validation triangulation for allowed concepts and integer amounts; final file passed 5/5 | ✅ Kept assertions behavioral and used schema helper only when the PR 1 table is absent in test DB |
| 3.1 | `tests/Feature/Api/TerminarContratoControllerTest.php` | Feature API | ✅ `./vendor/bin/phpunit tests/Feature/Api/PagarCobroControllerTest.php` passed 7/7 before modifying `routes/api.php` | ✅ New tests received 404 without the POST route | ✅ Route wired to invokable controller and API tests passed | ✅ Positive, zero, validation, and participant scenarios exercise the route | ✅ Existing API routes preserved |
| 3.2 | `tests/Feature/Api/TerminarContratoControllerTest.php` | Feature API | N/A (new request) | ✅ Excessive discount test expected 422 and no partial writes | ✅ Request validates sum <= garantía and tests passed | ✅ Unknown concept and non-integer amount test passed with specific validation errors | ✅ Validation failures return existing JSON `errors` shape |
| 3.3 | `tests/Feature/Api/TerminarContratoControllerTest.php` | Feature API | N/A (new controller) | ✅ Route/controller missing produced API RED failures | ✅ Thin controller delegates to service and returns service JSON | ✅ All API scenarios exercise the controller through HTTP | ✅ Controller contains no workflow logic |
| 3.4 | `tests/Feature/Api/TerminarContratoControllerTest.php` | Feature API | N/A (new service) | ✅ Positive refund test expected `fecha_termino` and persisted cobros | ✅ Service uses transaction, lock reload, participant lookup, and `now()` termination | ✅ Excessive-discount rollback and participant tests exercise service branches | ✅ Kept relationship resolver out per design decision |
| 3.5 | `tests/Feature/Api/TerminarContratoControllerTest.php` | Feature API | N/A (new service behavior) | ✅ Positive refund test expected paid discount cobros with contract/unit/property context | ✅ Discount cobros are `Pagado`, typed by submitted concept, carry detail, and use arrendatario→arrendador participants | ✅ Two concepts and participant assertions cover non-trivial behavior | ✅ Helper extracted for participant creation |
| 3.6 | `tests/Feature/Api/TerminarContratoControllerTest.php` | Feature API | N/A (new service behavior) | ✅ Positive and zero refund tests expected different refund state/monto outcomes | ✅ Refund cobro is `Pendiente` for positive refund and `Pagado` with monto 0 for full discount | ✅ Both refund branches covered | ✅ Shared refund cobro helper keeps branching localized |
| 3.7 | `tests/Feature/Api/TerminarContratoControllerTest.php` | Feature API | N/A (new service behavior) | ✅ Positive refund test expected links and transaction; zero refund expected no transaction rows | ✅ Service creates `Descuento_Garantia` links always and transaction rows only when refund > 0 | ✅ Positive vs zero refund branches covered | ✅ Transaction helper isolates origin/destination setup |
| 1.3 | `tests/Feature/FichaContratosDisplayTest.php` | Feature UI source/render assertions | ✅ `./vendor/bin/phpunit tests/Feature/FichaContratosDisplayTest.php` passed 7/7 before modifying UI/test files | ✅ New UI source assertion failed first because confirm button/fetch/validation code was absent | ✅ `./vendor/bin/phpunit tests/Feature/FichaContratosDisplayTest.php --filter termination_modal_confirm` passed 1/1 after UI implementation | ✅ Rendered-output assertions cover visible confirm/error/card hooks; source assertions cover no native dialogs, fetch, loading, disabled button, flashModal | ✅ Kept checks behavioral/source-contract focused, no CSS-only assertions beyond required hook strings |
| 4.1 | `tests/Feature/FichaContratosDisplayTest.php` | Feature UI source/render assertions | ✅ Same 7/7 safety net before Blade changes | ✅ Test required `collectTerminationDiscounts(preview)`, `validateTerminationDiscounts(preview)`, and excessive-discount message before code existed | ✅ Source and rendered assertions passed after collecting non-zero discount rows and blocking invalid totals | ✅ Payload and validation paths both asserted | ✅ Helper functions isolate collection/total/validation logic |
| 4.2 | `tests/Feature/FichaContratosDisplayTest.php` | Feature UI source assertions | ✅ Same 7/7 safety net before Blade changes | ✅ Test required POST fetch, CSRF header, disabled button, `showElLoading(btn)`, `hideElLoading(btn)`, and `showMessage()` feedback | ✅ Filtered UI test passed after wiring confirm action | ✅ Success and error feedback source paths asserted; native dialogs remain absent | ✅ Reused existing `showMessage()` flashModal helper |
| 4.3 | `tests/Feature/FichaContratosDisplayTest.php` | Feature UI source/render assertions | ✅ Same 7/7 safety net before Blade changes | ✅ Test required active-card hook and `removeTerminatedContractFromActiveUi(preview)` before code existed | ✅ Full UI test passed after removing the matching active-contract card and hiding the modal | ✅ Rendered card data hook plus source removal helper asserted | ✅ No page reload added; UI updates locally |
| 5.3 | `tests/Feature/FichaContratosDisplayTest.php` | Feature verification | ✅ Relevant file already passed before changes | ✅ Covered by PR 3 RED/GREEN cycle | ✅ `./vendor/bin/phpunit --filter FichaContratosDisplayTest` passed 8/8 | ✅ Full feature display file covers original and new UI assertions | ➖ Verification command only |

## Test Summary

- Total tests written: 8
- Total tests passing: 8 in `FichaContratosDisplayTest` for PR 3 UI slice; prior PR 1/2 focused tests remain preserved above
- Layers used: Feature model (2), Feature API (5), Feature UI source/render assertions (1 new test plus extended rendered assertions)
- Approval tests: None — no refactoring task
- Pure functions created: 0

## Tests Run

- `./vendor/bin/phpunit tests/Feature/Models/DescuentoGarantiaTest.php` — RED, failed with missing `App\Models\DescuentoGarantia` class (expected)
- `./vendor/bin/phpunit tests/Unit/PivotTableCasingTest.php` — safety net, passed 9/9
- `./vendor/bin/phpunit tests/Feature/Models/DescuentoGarantiaTest.php` — GREEN, passed 2/2
- `./vendor/bin/phpunit tests/Feature/Models/DescuentoGarantiaTest.php` — REFACTOR, passed 2/2
- `./vendor/bin/phpunit --filter DescuentoGarantiaTest` — final, passed 2/2
- `./vendor/bin/phpunit tests/Feature/Api/PagarCobroControllerTest.php` — PR 2 safety net before route changes, passed 7/7
- `./vendor/bin/phpunit tests/Feature/Api/TerminarContratoControllerTest.php` — RED, failed 4/4 with 404 before backend route/controller/service existed
- `./vendor/bin/phpunit tests/Feature/Api/TerminarContratoControllerTest.php` — GREEN, passed 4/4 after backend implementation
- `./vendor/bin/phpunit tests/Feature/Api/TerminarContratoControllerTest.php` — TRIANGULATE, passed 5/5 after adding invalid concept/non-integer validation coverage
- `./vendor/bin/phpunit tests/Feature/Api/PagarCobroControllerTest.php` — post-change safety net, passed 7/7
- `./vendor/bin/phpunit --filter TerminarContratoControllerTest` — final, passed 5/5
- `./vendor/bin/phpunit tests/Feature/FichaContratosDisplayTest.php` — PR 3 safety net, passed 7/7
- `./vendor/bin/phpunit tests/Feature/FichaContratosDisplayTest.php --filter termination_modal_confirm` — RED, failed because confirm button/fetch/loading/validation source contract was absent
- `./vendor/bin/phpunit tests/Feature/FichaContratosDisplayTest.php --filter termination_modal_confirm` — GREEN, passed 1/1 after Blade UI wiring
- `./vendor/bin/phpunit tests/Feature/FichaContratosDisplayTest.php` — TRIANGULATE/REFACTOR, passed 8/8 after rendered-output assertions
- `./vendor/bin/phpunit --filter FichaContratosDisplayTest` — required PR 3 verification, passed 8/8

## Deviations

- None — implementation matches the PR 3 UI wiring boundary from `design.md`; no backend route/request/controller/service files were modified.

## Issues / Notes

- PHPUnit reports one deprecation during these runs; not introduced or investigated in this slice.
- No migrations were run against real MySQL.
- `tests/Feature/Api/TerminarContratoControllerTest.php` creates `Descuento_Garantia` only if absent because the PR 1 migration file is not executed during this apply phase.
- PR 3 uses source/render assertions for vanilla JS UI behavior; no browser/E2E runner is available in this slice.

## Remaining Tasks

- [ ] 5.1 Run `./vendor/bin/phpunit --filter DescuentoGarantiaTest` if final verification wants all historical commands rerun.
- [ ] 5.2 Run `./vendor/bin/phpunit --filter TerminarContratoControllerTest` if final verification wants all historical commands rerun.
- [ ] 5.4 Run `./vendor/bin/phpunit`; never run `php artisan migrate`, `migrate:fresh`, `migrate:reset`, or `db:wipe`.
