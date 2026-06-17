# Tasks: Terminar contrato front-end preview

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 600-850 |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 data/display → PR 2 preview modal/calculator → PR 3 detail/tests polish |
| Delivery strategy | force-chained / user chose chained PRs after forecast exceeded budget |
| Chain strategy | feature-branch-chain |

Decision needed before apply: No
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Dependencies / Verification |
|------|------|-----------|-----------------------------|
| 1 | Read-only contract data and corrected participant display | PR 1 | None; feature tests for ficha participant/cobro scenarios |
| 2 | Server-rendered termination preview modal and JS calculator | PR 2 | Unit 1; browser/manual calculator checks + static no-dialog/no-fetch review |
| 3 | Readable contract detail and final verification | PR 3 | Unit 1; feature test for `contrato.show` outside generated blocks |

## Front-End-Only Boundaries

- Do not add termination routes, migrations, writes, status/date updates, cobro/payment/accounting creation, or destructive DB commands.
- Read-only controller eager-loading is allowed; avoid generated `[GEN:START/END]` edits.
- No `fetch()` is planned; if introduced, wrap with `showElLoading`/`hideElLoading`.
- Use Bootstrap `abrirModal`/`#modalPrincipal`; never use `alert`, `confirm`, or `prompt`.

## Phase 1: RED — Data and Display Tests

- [x] 1.1 Add failing feature tests for `propiedad/contratos`: active contract action, participant `cliente` names/links, pending states.
- [x] 1.2 Add failing feature tests for `cliente/contratos`: context participant display and cobros scoped by `participante_cobros.Cliente_id`.
- [x] 1.3 Add failing feature/static assertions that opening/closing preview does not expose persistence endpoints or native dialogs.

## Phase 2: GREEN — Read-Only Data Wiring

- [x] 2.1 Update `FichaPropiedadController::contratos($id)` and ficha active-contract query with required eager-loads and pending-state filter.
- [x] 2.2 Update `FichaClienteController::contratos($id)` with same eager-loads plus client-context pending-cobro constraint.
- [x] 2.3 Update `resources/views/cliente/contratos.blade.php` and `resources/views/propiedad/contratos.blade.php` to pass context to the shared component.
- [x] 2.4 Fix `resources/views/components/contratos.blade.php` participant/date/guarantee/property-unit rendering using safe `ParticipanteContrato->cliente` access.
- [x] 2.5 Verify: run targeted PHPUnit tests from Phase 1 and confirm no destructive DB command was used.

## Phase 3: GREEN — Preview Modal and Calculator

- [x] 3.1 In `resources/views/components/contratos.blade.php`, render `Terminar contrato` buttons and hidden `vista-terminar-contrato-{id}` modal sources.
- [x] 3.2 Add modal copy for preview-only warning, inspection guidance, proportional-services notice, guarantee, start date, today end date, pending cobros/empty state, and default `Aseo Final`.
- [x] 3.3 Add delegated JS in the component for add/edit/remove adjustment rows, CLP parsing/formatting via global utilities, and charge/refund/return totals.
- [ ] 3.4 Verify: manual browser check on cliente and propiedad pages; ensure no network request or persistence occurs.

## Phase 4: RED/GREEN — Contract Detail

- [x] 4.1 Add failing feature test for `contrato.show` readable participants, property/unit, dates, guarantee, and cobros outside generated-owned sections.
- [x] 4.2 Update `ContratoController::show($id)` with read-only relationship eager-loading only.
- [x] 4.3 Update `resources/views/contrato/show.blade.php` outside `[GEN:START/END]` with readable summary/cobros.

## Phase 5: Verification and Refactor

- [ ] 5.1 Run `./vendor/bin/phpunit` and fix regressions without widening scope.
- [x] 5.2 Grep/review changed files for no native dialogs, no termination route/write path, no unwrapped `fetch()`, and no generated-block edits.
- [ ] 5.3 Refactor duplicate Blade/JS helpers only inside the touched component; keep work-unit commits aligned with the three suggested PR slices.
