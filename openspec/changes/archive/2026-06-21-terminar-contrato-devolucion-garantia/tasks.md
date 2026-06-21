# Tasks: Terminar contrato con devolución de garantía

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 650-900 |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 data/model links → PR 2 backend API/service → PR 3 UI wiring |
| Delivery strategy | ask-on-risk |
| Chain strategy | feature-branch-chain |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Add `Descuento_Garantia` persistence model and `Cobro` relations | PR 1 | Includes migration file and relationship tests. |
| 2 | Add termination API, validation, atomic service workflow | PR 2 | Depends on PR 1; includes feature tests for specs. |
| 3 | Wire contract modal confirm action | PR 3 | Depends on PR 2; includes UI source assertions. |

## Phase 1: RED Tests

- [x] 1.1 Create `tests/Feature/Models/DescuentoGarantiaTest.php` asserting refund→discount and discount→refund relations.
- [x] 1.2 Create `tests/Feature/Api/TerminarContratoControllerTest.php` for positive refund, zero refund, excessive discounts rollback, and participants.
- [x] 1.3 Extend `tests/Feature/FichaContratosDisplayTest.php` to assert no native dialogs, `fetch()`, `showElLoading`/`hideElLoading`, disabled confirm, and frontend ceiling validation.

## Phase 2: Data Foundation

- [x] 2.1 Create `database/migrations/*_create_descuento_garantia_table.php` for `Cobro_Devolucion_id` and `Cobro_Descuento_id`; do not run migrations against real MySQL.
- [x] 2.2 Create `app/Models/DescuentoGarantia.php` with explicit table, non-incrementing keys, fillable fields, no timestamps, and `devolucion()`/`descuento()` relations.
- [x] 2.3 Update `app/Models/Cobro.php` with directional `descuentosGarantia()` and `devolucionGarantia()` relations.

## Phase 3: Backend Workflow

- [x] 3.1 Add `POST /api/contratos/{contrato}/terminar` in `routes/api.php` pointing to `TerminarContratoController`.
- [x] 3.2 Create `app/Http/Requests/TerminarContratoRequest.php` validating discounts, allowed concepts, integer amounts, and `sum(descuentos) <= garantía`.
- [x] 3.3 Create `app/Http/Controllers/Api/TerminarContratoController.php` as a thin JSON controller delegating to the service.
- [x] 3.4 Create `app/Services/TerminarContratoService.php` with `DB::transaction()`, locked contract reload, participant resolution, and `fecha_termino = now()`.
- [x] 3.5 In the service, create paid discount cobros with debtor arrendatario, creditor arrendador, and auditable contract/concept/detail context.
- [x] 3.6 In the service, create `Devolución Garantía Arrendatario`: pending when refund > 0, paid with monto 0 when refund = 0.
- [x] 3.7 In the service, create `Descuento_Garantia` links and create `Transaccion`/`Transaccion_Cobro` only when refund > 0.

## Phase 4: Frontend Integration

- [x] 4.1 Update `resources/views/components/contratos.blade.php` to collect discount payload and block submit when discounts exceed garantía.
- [x] 4.2 Add confirm `fetch()` to `/api/contratos/{id}/terminar` with CSRF, disabled button, `showElLoading(btn)`, `hideElLoading(btn)`, and `flashModal` feedback only.
- [x] 4.3 Refresh or remove the terminated contract from the visible active-contract UI after success.

## Phase 5: Verification / Cleanup

- [ ] 5.1 Run `./vendor/bin/phpunit --filter DescuentoGarantiaTest`.
- [ ] 5.2 Run `./vendor/bin/phpunit --filter TerminarContratoControllerTest`.
- [x] 5.3 Run `./vendor/bin/phpunit --filter FichaContratosDisplayTest`.
- [ ] 5.4 Run `./vendor/bin/phpunit`; never run `php artisan migrate`, `migrate:fresh`, `migrate:reset`, or `db:wipe`.
