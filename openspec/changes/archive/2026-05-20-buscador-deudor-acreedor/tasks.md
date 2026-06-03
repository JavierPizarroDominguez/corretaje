# Tasks: Buscador deudor/acreedor

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~150-200 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-always |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

## Phase 1: Generator Fix (source of truth)

- [x] 1.1 `StubRenderer.php` — `buscadorInputName()`: use `$col->relationName` as discriminator, fall back to `referencedTable` when null. Gives `nombre-deudor`/`nombre-acreedor` instead of both `nombre-participante_cobro`
- [x] 1.2 `stubs/modal-create.stub` — add `<script>{{create_buscador_calls}}</script>` placeholder before `</form>`
- [x] 1.3 `StubRenderer.php` — `renderModalCreate()`: call `buildCreateBuscadorCalls($schema)` + `str_replace('{{create_buscador_calls}}', ...)`

## Phase 2: Controller Fix

- [x] 2.1 `CobroController.php` — `store()`: validate + read `nombre-deudor` with `nombre-participante_cobro` fallback
- [x] 2.2 `CobroController.php` — `store()`: validate + read `nombre-acreedor` with `nombre-participante_cobro` fallback
- [x] 2.3 `CobroController.php` — `store()`: fix `${cobro}->id` → `$cobro->id`, remove duplicate `$data['nombre-participante_cobro']` validation rule (fixed in generator `buildPivotStoreFields()` + regeneration)
- [x] 2.4 `CobroController.php` — `update()`: same dual-read + syntax fixes as `store()`

## Phase 3: Regenerate & Verify

- [x] 3.1 Run `php artisan gen:crud cobro --only=views,controller` — regenerate; diff output before committing
- [x] 3.2 Verify all buscador FK fields work in modal create (deudor, acreedor, contrato, servicio, propiedad, unidad)
- [x] 3.3 Verify full-page create + edit still work after input name change
- [x] 3.4 Verify old `nombre-participante_cobro` input still accepted (backward compat)

## Phase 4: Audit (optional)

- [ ] 4.1 Check if other generator entities have multiple FK fields to same table — same collision risk
