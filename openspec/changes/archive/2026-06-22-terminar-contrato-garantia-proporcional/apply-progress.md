# Apply Progress: Terminar Contrato con Garantía Proporcional

## Slice

Slice 1 — schema + backend core for chained PR #1 (`feature-branch-chain`).
Slice 2 — refund finalization + pending payloads for chained PR #2 (`feature-branch-chain`).
Slice 3 — frontend warning/refund modal routing and view tests for chained PR #3 (`feature-branch-chain`).

## Mode

Strict TDD, resolved from `openspec/config.yaml` (`testing.strict_tdd: true`, PHPUnit runner available).

## Completed Tasks

- [x] 1.1 Update `corretaje-bd.sql` `Cobro.tipo` enum with both proportional types.
- [x] 1.2 Add safe MySQL ALTER migration/SQL preserving values; migration file created but not executed.
- [x] 1.3 Update backend type support in `config/cobro_roles.php`, `CobroController`, and `CobroConceptoFormatter`.
- [x] 2.1 Add proportional rent calculator with real month divisor, clamped `dia_pago`, inclusive start, exclusive end.
- [x] 2.2 Termination request/controller no longer validate or pass discount rows.
- [x] 2.3 Termination service locks idempotently, sets `fecha_termino`, creates pending refund with arrendador debtor/arrendatario creditor and proportional ingreso/egreso cobros.
- [x] 2.4 Tests verify termination creates no discount cobros, `Descuento_Garantia`, `Transaccion`, or `Transaccion_Cobro`.
- [x] 3.1 Created `GarantiaRefundService`, `GarantiaRefundRequest`, controller action, and `POST /api/cobros/{cobro}/devolver-garantia`.
- [x] 3.2 Finalization locks, rejects duplicates/excess, validates discount concepts, creates discount cobros as `Pagado`, links `Descuento_Garantia`, pays refund, and creates exactly one positive-amount transaction/pivot.
- [x] 3.3 Generic `PagarCobroController` now rejects guarantee refund cobros so the dedicated workflow cannot be bypassed.
- [x] 3.4 Dashboard/cliente/propiedad pending APIs include refund metadata: flag, contract id, `fecha_termino`, `plazo_restante_dias`, deadline, and base amount.
- [x] 4.1 Termination confirmation in `resources/views/components/contratos.blade.php` now shows warning copy, disables/loading the confirm button, uses modal feedback, and submits no discount rows.
- [x] 4.2 Added reusable guarantee refund modal with `Plazo restante`, editable discount rows, `Devolver garantía`, local button loading helpers, and dedicated endpoint submission.
- [x] 4.3 Dashboard, cliente ficha, and propiedad ficha pending click handlers route `is_guarantee_refund` cobros to the refund modal while normal cobros keep the existing detail/payment modal.
- [x] 5.1 Add PHPUnit formula tests for 28/29/30/31-day months, clamp, and zero-day cases.
- [x] 5.2 Add termination tests for idempotency, pending refund participants, proportional cobros, and no discounts/transactions.
- [x] 5.3 Add `Devolver garantía` tests: positive, zero, excessive, duplicate, invalid concept, and discount cobros `Pagado`.
- [x] 5.4 Added focused frontend/view contract tests for warning copy, no native dialogs, refund metadata usage, `Plazo restante`, loading helpers, and refund endpoint wiring.

## TDD Cycle Evidence

| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
|------|-----------|-------|------------|-----|-------|-------------|----------|
| 1.1 | `tests/Feature/Api/TerminarContratoControllerTest.php` | Feature/schema artifact | N/A (schema assertion added before implementation) | ✅ Schema dump assertion failed before enum update | ✅ 10/10 focused tests passed | ➖ Single artifact assertion | ✅ Clean |
| 1.2 | `tests/Feature/Api/TerminarContratoControllerTest.php` | Feature/schema artifact | N/A (new migration) | ✅ Schema/type support covered before migration implementation | ✅ 10/10 focused tests passed | ➖ Structural migration | ✅ Clean |
| 1.3 | `tests/Feature/Api/TerminarContratoControllerTest.php` | Feature | ⚠️ `CobroConceptoFormatterTest` had 3 pre-existing expectation failures before this slice | ✅ Proportional cobros required new type support | ✅ 10/10 focused tests passed | ✅ Role assertions for ingreso and egreso participants | ✅ Clean |
| 2.1 | `tests/Unit/Services/TerminarContratoServiceTest.php` | Unit | N/A (new calculator API) | ✅ Undefined `calculateProportionalRent()` failed | ✅ 10/10 focused tests passed | ✅ 6 cases: 28/29/30/31, clamp, zero-day | ✅ Pure function extracted |
| 2.2 | `tests/Feature/Api/TerminarContratoControllerTest.php` | Feature | ✅ `TerminarContratoControllerTest` baseline 5/5 passed | ✅ Discount payload expected ignored but old validation rejected | ✅ 10/10 focused tests passed | ✅ Excessive discount payload still terminates without discount rows | ✅ Removed obsolete request discount contract |
| 2.3 | `tests/Feature/Api/TerminarContratoControllerTest.php` | Feature | ✅ `TerminarContratoControllerTest` baseline 5/5 passed | ✅ Old service created discount/refund transaction and duplicate cobros | ✅ 10/10 focused tests passed | ✅ Repeated termination returns same ids and one cobro per type; refund participants are arrendador debtor/arrendatario creditor | ✅ Shared `firstOrCreateCobro()` helper |
| 2.4 | `tests/Feature/Api/TerminarContratoControllerTest.php` | Feature | ✅ `TerminarContratoControllerTest` baseline 5/5 passed | ✅ No-discount/no-transaction assertions failed against old behavior | ✅ 10/10 focused tests passed | ✅ Asserts no discount cobros, links, transactions, or pivots | ✅ Clean |
| 3.1 | `tests/Feature/Api/GarantiaRefundControllerTest.php` | Feature | N/A (new endpoint/service/request/controller) | ✅ Endpoint/service contract was covered by finalization tests before route/service existed | ✅ 5/5 refund tests passed | ✅ Endpoint exercised positive, zero, excessive, duplicate, invalid concept paths | ✅ Thin invokable controller delegates to service |
| 3.2 | `tests/Feature/Api/GarantiaRefundControllerTest.php` | Feature | N/A (new finalization service) | ✅ Positive/zero/excessive/duplicate tests failed before service behavior existed; invalid concept test failed with 200 before validation | ✅ 5/5 refund tests passed | ✅ Positive amount creates one transaction/pivot; zero creates none; duplicate/excess/invalid concept create no rows | ✅ Atomic service with finalization guard and helper methods |
| 3.3 | `tests/Feature/Api/PagarCobroControllerTest.php` | Feature | ✅ Existing payment controller tests stayed green in focused run | ✅ Generic guarantee refund payment expected 422 before guard | ✅ 8/8 payment tests passed in focused slice run | ✅ Normal pending/vencido cobros still pay successfully | ✅ Guard placed before transaction creation |
| 3.4 | `tests/Feature/Api/PendingGuaranteeRefundMetadataTest.php` | Feature/API payload | N/A (new metadata helper) | ✅ Pending API metadata assertions failed before payload fields were added | ✅ 2/2 pending metadata tests passed in focused slice run | ✅ Dashboard, cliente, and propiedad endpoints all include refund metadata | ✅ Shared `GarantiaRefundMetadata` helper prevents duplicated mapping |
| 4.1 | `tests/Feature/GuaranteeRefundFrontendContractsTest.php`, `tests/Feature/FichaContratosDisplayTest.php` | Feature/view contract | ✅ `FichaContratosDisplayTest` baseline 9/9 passing before frontend changes | ✅ Warning/no-discount assertions failed against old discount-submitting termination modal | ✅ 12/12 focused frontend tests passed | ✅ Warning copy covers pending refund, 30-day discount window, proportional cobros, no native dialogs, and empty termination payload | ✅ Removed obsolete termination discount UI and validation surface |
| 4.2 | `tests/Feature/GuaranteeRefundFrontendContractsTest.php` | Feature/view contract | N/A (new reusable modal partials) | ✅ Refund modal assertions failed before `modalGarantiaRefund`, `Plazo restante`, and endpoint wiring existed | ✅ 12/12 focused frontend tests passed | ✅ Modal includes editable discount rows, top cards, local loading helpers, and `Devolver garantía` endpoint | ✅ Shared modal/script partial avoids divergent dashboard/ficha flows |
| 4.3 | `tests/Feature/GuaranteeRefundFrontendContractsTest.php` | Feature/view contract | ✅ Existing `FichaContratosDisplayTest` preserved normal cobro modal assertions | ✅ Routing assertions failed before dashboard/cliente/propiedad checked `is_guarantee_refund` | ✅ 12/12 focused frontend tests passed | ✅ Dashboard, cliente ficha, propiedad ficha route refund cobros to refund modal and preserve normal modal path | ✅ Server-rendered ficha controllers now reuse `GarantiaRefundMetadata` for initial pending buttons |
| 5.1 | `tests/Unit/Services/TerminarContratoServiceTest.php` | Unit | N/A (new calculator API) | ✅ See task 2.1 | ✅ 10/10 slice 1 focused tests passed | ✅ 6 formula cases | ✅ Clean |
| 5.2 | `tests/Feature/Api/TerminarContratoControllerTest.php` | Feature | ✅ Baseline 5/5 before slice 1 production changes | ✅ See tasks 2.2–2.4 | ✅ 10/10 slice 1 focused tests passed | ✅ Idempotency, participants, proportional cobros, no discounts/transactions | ✅ Clean |
| 5.3 | `tests/Feature/Api/GarantiaRefundControllerTest.php` | Feature | N/A (new finalization tests) | ✅ New finalization scenarios failed before implementation; invalid concept scenario failed with HTTP 200 before concept validation | ✅ 5/5 refund tests passed | ✅ Positive, zero, excessive, duplicate, invalid concept, and `Pagado` discount cobros covered | ✅ Test helpers keep setup local to API tests |
| 5.4 | `tests/Feature/GuaranteeRefundFrontendContractsTest.php`, `tests/Feature/FichaContratosDisplayTest.php` | Feature/view contract | ✅ `FichaContratosDisplayTest` baseline 9/9 passing before frontend changes | ✅ New frontend contract tests failed before implementation | ✅ 12/12 focused frontend tests passed | ✅ View contracts cover routing metadata, `Plazo restante`, loading helpers, no native dialogs, and initial ficha metadata helper usage | ✅ Clean |

## Test Summary

- **Total tests written/updated**: 18 focused tests across the slice files (10 from slice 1, 5 refund tests plus pending/payment coverage in slice 2 files, 3 frontend contract tests in slice 3 plus updated ficha display assertions).
- **Total focused tests passing**: 12/12 for slice 3 focused frontend/view run; 2/2 pending metadata API regression run.
- **Layers used**: Unit (slice 1 formula tests), Feature/API (termination, refund finalization, payment guard, pending metadata), Feature/view contracts (frontend routing/modal/static contracts).
- **Approval tests**: None — behavior intentionally changed by spec.
- **Pure functions/helpers created**: `TerminarContratoService::calculateProportionalRent`, `GarantiaRefundMetadata::forCobro`.

## Tests Run

- `./vendor/bin/phpunit tests/Feature/Api/GarantiaRefundControllerTest.php --filter invalid_discount_concepts` — RED: expected 422 but old request allowed invalid discount concept and returned 200.
- `./vendor/bin/phpunit tests/Feature/Api/GarantiaRefundControllerTest.php` — interim: 4/5 passing; revealed an outdated zero-refund fixture still used invalid `Daños` concept.
- `./vendor/bin/phpunit tests/Feature/Api/GarantiaRefundControllerTest.php tests/Feature/Api/PagarCobroControllerTest.php tests/Feature/Api/PendingGuaranteeRefundMetadataTest.php` — GREEN: 15/15 passing, PHPUnit deprecation notice only.
- `php -l app/Services/GarantiaRefundService.php; ...` — no syntax errors in slice 2 backend PHP files.
- Slice 1 preserved history: `./vendor/bin/phpunit tests/Feature/Api/TerminarContratoControllerTest.php` baseline 5/5, then `./vendor/bin/phpunit tests/Unit/Services/TerminarContratoServiceTest.php tests/Feature/Api/TerminarContratoControllerTest.php` GREEN 10/10, PHPUnit deprecation notice only.
- `./vendor/bin/phpunit tests/Feature/FichaContratosDisplayTest.php` — safety net before slice 3 production changes: GREEN 9/9, 147 assertions, PHPUnit deprecation notice only.
- `./vendor/bin/phpunit tests/Feature/GuaranteeRefundFrontendContractsTest.php` — RED: warning/no-discount and refund modal routing assertions failed before frontend implementation.
- `./vendor/bin/phpunit tests/Feature/GuaranteeRefundFrontendContractsTest.php tests/Feature/FichaContratosDisplayTest.php` — GREEN: 12/12 passing, 186 assertions, PHPUnit deprecation notice only.
- `./vendor/bin/phpunit tests/Feature/Api/PendingGuaranteeRefundMetadataTest.php` — GREEN: 2/2 passing, 18 assertions, PHPUnit deprecation notice only.
- `php -l app/Http/Controllers/Vistas/FichaClienteController.php; php -l app/Http/Controllers/Vistas/FichaPropiedadController.php` — no syntax errors.

## Deviations / Notes

- Full frontend modal routing was implemented in slice 3; slice 2 backend-only note is preserved historically.
- No migration was executed, respecting the database protection rule.
- `GarantiaRefundRequest` currently accepts the discount concepts exposed by the existing guarantee discount modal (`Aseo Final`, `Reparación`) and rejects unknown concepts before DB persistence.
- `tests/Unit/Services/CobroConceptoFormatterTest.php` had 3 pre-existing failures before slice 1 (`Cobrar renta`/`Cobrar garantía` expectations differ from current formatter output). This was not fixed because it is outside the slice behavior.

## Remaining Tasks

- None for this change; all planned tasks are marked complete.

## Files Changed

- `corretaje-bd.sql` — added proportional enum values.
- `database/migrations/2026_06_22_000001_add_proportional_rent_cobro_types.php` — safe enum ALTER migration file; not executed.
- `app/Services/TerminarContratoService.php` — termination now creates only pending refund/proportional cobros and is idempotent; refund participant rule is arrendador debtor/arrendatario creditor.
- `app/Http/Requests/TerminarContratoRequest.php` — discount rows no longer validated for termination.
- `app/Http/Controllers/Api/TerminarContratoController.php` — calls termination service without discounts.
- `config/cobro_roles.php` — added proportional role mappings.
- `app/Http/Controllers/Crud/CobroController.php` — allows proportional types in validation.
- `app/Services/CobroConceptoFormatter.php` — formats proportional rent labels.
- `app/Services/GarantiaRefundService.php` — finalizes pending guarantee refunds atomically, creates paid discount cobros/links, recalculates/refunds amount, creates one transaction only for positive refunds, and rejects duplicates.
- `app/Http/Requests/GarantiaRefundRequest.php` — validates final discount rows, allowed concepts, details, and non-negative integer amounts.
- `app/Http/Controllers/Api/GarantiaRefundController.php` — exposes the `Devolver garantía` endpoint.
- `routes/api.php` — adds `POST /api/cobros/{cobro}/devolver-garantia`.
- `app/Http/Controllers/Api/PagarCobroController.php` — blocks generic payment for guarantee refund cobros.
- `app/Services/GarantiaRefundMetadata.php` — centralizes pending refund API metadata.
- `app/Http/Controllers/Api/DashboardPendientesController.php` — includes guarantee refund metadata in dashboard pending payloads.
- `app/Http/Controllers/Api/ClientePendientesController.php` — includes guarantee refund metadata in cliente pending payloads.
- `app/Http/Controllers/Api/PropiedadPendientesController.php` — includes guarantee refund metadata in propiedad pending payloads.
- `tests/Unit/Services/TerminarContratoServiceTest.php` — proportional formula coverage.
- `tests/Feature/Api/TerminarContratoControllerTest.php` — termination persistence/idempotency coverage.
- `tests/Feature/Api/GarantiaRefundControllerTest.php` — finalization coverage for positive, zero, excessive, duplicate, invalid concept, and paid discount cobros.
- `tests/Feature/Api/PagarCobroControllerTest.php` — generic payment guard coverage while preserving normal payments.
- `tests/Feature/Api/PendingGuaranteeRefundMetadataTest.php` — pending API metadata coverage.
- `resources/views/components/contratos.blade.php` — termination warning confirmation now submits no discount rows and keeps local loading/modal feedback.
- `resources/views/components/guarantee-refund-modal.blade.php` — reusable pending guarantee refund modal with `Plazo restante` and editable discounts.
- `resources/views/components/guarantee-refund-scripts.blade.php` — shared refund modal routing/finalization JS using `showElLoading`/`hideElLoading` and the dedicated endpoint.
- `resources/views/dashboard/index.blade.php` — routes guarantee refund pending cobros to refund modal and reloads dashboard after finalization.
- `resources/views/cliente.blade.php` — routes ficha guarantee refund pending cobros to refund modal and reloads ficha pendientes after finalization.
- `resources/views/propiedad.blade.php` — routes propiedad guarantee refund pending cobros to refund modal and reloads ficha pendientes after finalization.
- `app/Http/Controllers/Vistas/FichaClienteController.php` — includes guarantee refund metadata for server-rendered ficha pending buttons.
- `app/Http/Controllers/Vistas/FichaPropiedadController.php` — includes guarantee refund metadata for server-rendered ficha pending buttons.
- `tests/Feature/GuaranteeRefundFrontendContractsTest.php` — frontend contract coverage for warning copy, refund routing/modal, loading helpers, no native dialogs, and metadata helper usage.
- `tests/Feature/FichaContratosDisplayTest.php` — updated termination preview assertions for warning-only flow with no discount submission.
- `openspec/changes/terminar-contrato-garantia-proporcional/tasks.md` — marked all remaining slice 3 frontend/view tasks complete.

## Status

Slice 3 complete. All planned tasks are complete and ready for verification.
