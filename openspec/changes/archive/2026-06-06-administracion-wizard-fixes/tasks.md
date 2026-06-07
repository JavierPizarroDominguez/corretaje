# Tasks: administracion-wizard-fixes

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~120–150 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-always |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | All 4 fixes — backend validation + frontend JS + views | PR 1 | Self-contained; includes test update |

---

## Phase 1: Foundation — Backend Validation & CLP Utilities

- [x] 1.1 `app/Http/Requests/CrearAdministracionRequest.php` — change `dia_pago` rule from `between:1,31` to `between:1,28`; update error message from "1 y 31" to "1 y 28`
- [x] 1.2 `app/Http/Controllers/Crud/ContratoController.php` — add `between:1,28` constraint to `dia_pago` validation in `store()` and `update()`
- [x] 1.3 `app/Http/Controllers/AdministracionController.php` — add `$contrato->load('unidad')` after creation; replace `$contrato->propiedad_id` with `$contrato->unidad?->Propiedad_id` + null fallback to `dashboard`
- [x] 1.4 `public/assets/js/app.js` — add `window.formatCLP(value)` and `window.stripCLP(formatted)` utilities (see design.md for implementation)

---

## Phase 2: Core Implementation — Frontend Views & Wizard JS

- [x] 2.1 `resources/views/administracion/create.blade.php` — update `dayOutOfRange` threshold 31→28; update frontend validation message "1-31" → "1-28"; add `step6Visited` flag and renta `input` listener that recalculates comision + egreso when step 6 was visited; add CLP strip loop for monetary fields before form submit
- [x] 2.2 `resources/views/administracion/partials/step-04-contrato.blade.php` — change `max="31"` → `max="28"` on dia_pago; change renta input `type="number"` → `type="text" inputmode="numeric"`; add `formatCLP` on blur, `stripCLP` on focus
- [x] 2.3 `resources/views/administracion/partials/step-05-comision.blade.php` — change `comision_inicial` input `type="number"` → `type="text" inputmode="numeric"`; add `formatCLP`/`stripCLP`
- [x] 2.4 `resources/views/administracion/partials/step-06-egreso.blade.php` — change `comision_mensual` and `egreso_renta` inputs to `type="text" inputmode="numeric"`; add `formatCLP`/`stripCLP`; replace `comisionMensualInput.value = 0` with `Math.round(renta * 0.1)` auto-fill on step 6 entry
- [x] 2.5 `resources/views/administracion/partials/step-07-garantia.blade.php` — change `garantia` input `type="number"` → `type="text" inputmode="numeric"`; add `formatCLP`/`stripCLP`
- [x] 2.6 `resources/views/administracion/partials/step-08-servicios.blade.php` — change `max="31"` → `max="28"` on dia_pago fields; change `monto` inputs to `type="text" inputmode="numeric"`; add `formatCLP`/`stripCLP`

---

## Phase 3: Testing & Verification

- [x] 3.1 `tests/Unit/Requests/CrearAdministracionRequestTest.php` — update `dia_pago` test: assert 0 fails, 29 fails, 15 passes, 28 passes (change 31→28 in assertions and test name)
- [ ] 3.2 Manual verification: Create administracion via browser, verify redirect to `propiedad.ficha` for correct property
- [ ] 3.3 Manual verification: Enter renta=500000, advance to step 6, verify comision=50000, egreso=450000
- [ ] 3.4 Manual verification: After step 6, go back to step 4, change renta, verify comision and egreso recalculate
- [ ] 3.5 Manual verification: Enter 500000 in any monetary field, blur → shows `$500.000`, focus → shows `500000`, submit → backend receives raw integer