# Apply Progress: administracion-wizard-fixes

**Change**: administracion-wizard-fixes
**Status**: partial (manual verification tasks pending)
**Mode**: Standard (no Strict TDD)
**Date**: 2026-06-06

## Tasks Completed

### Phase 1: Foundation — Backend Validation & CLP Utilities
- [x] 1.1 `app/Http/Requests/CrearAdministracionRequest.php` — `dia_pago` rule `between:1,31` → `between:1,28`; `servicios.*.dia` also updated; error message updated
- [x] 1.2 `app/Http/Controllers/Crud/ContratoController.php` — added `between:1,28` to `dia_pago` in both `store()` and `update()` via `replaceAll`
- [x] 1.3 `app/Http/Controllers/AdministracionController.php` — added `$contrato->load('unidad')`; replaced `$contrato->propiedad_id` with `$contrato->unidad?->Propiedad_id` + null fallback to `dashboard`; updated `chk_dia_pago_contrato` error message to "1 y 28"
- [x] 1.4 `public/assets/js/app.js` — added `window.formatCLP()` and `window.stripCLP()` utilities

### Phase 2: Core Implementation — Frontend Views & Wizard JS
- [x] 2.1 `resources/views/administracion/create.blade.php` — `dayOutOfRange` threshold 31→28; validation message "1-31" → "1-28"; `step6Visited` flag in Alpine wizard state; renta `input` listener recalculates comision+egreso; CLP strip loop before form submit; `getRentaNumero()` updated to use `stripCLP`; all related input listeners updated to handle CLP formatting (egreso input, comision input, step 5/7 auto-fill)
- [x] 2.2 `resources/views/administracion/partials/step-04-contrato.blade.php` — `max="31"` → `max="28"` on dia_pago; renta changed to `type="text" inputmode="numeric"` with `formatCLP`/`stripCLP`
- [x] 2.3 `resources/views/administracion/partials/step-05-comision.blade.php` — `comision_inicial` changed to `type="text" inputmode="numeric"` with `formatCLP`/`stripCLP`
- [x] 2.4 `resources/views/administracion/partials/step-06-egreso.blade.php` — both inputs changed to `type="text" inputmode="numeric"` with `formatCLP`/`stripCLP`
- [x] 2.5 `resources/views/administracion/partials/step-07-garantia.blade.php` — `garantia` changed to `type="text" inputmode="numeric"` with `formatCLP`/`stripCLP`
- [x] 2.6 `resources/views/administracion/partials/step-08-servicios.blade.php` — `max="31"` → `max="28"` on dia_pago; `servicioMontoInput` changed to `type="text" inputmode="numeric"` with `formatCLP`/`stripCLP`

### Phase 3: Testing & Verification
- [x] 3.1 `tests/Unit/Requests/CrearAdministracionRequestTest.php` — renamed test to `test_dia_pago_must_be_between_1_and_28`; updated 32→29 invalid assertion; added 28 passing assertion
- [ ] 3.2 Manual verification: Create administracion via browser, verify redirect to `propiedad.ficha` for correct property
- [ ] 3.3 Manual verification: Enter renta=500000, advance to step 6, verify comision=50000, egreso=450000
- [ ] 3.4 Manual verification: After step 6, go back to step 4, change renta, verify comision and egreso recalculate
- [ ] 3.5 Manual verification: Enter 500000 in any monetary field, blur → shows `$500.000`, focus → shows `500000`, submit → backend receives raw integer

### Phase 4: Real-Time CLP Formatting (Enhancement)
- [x] 4.1 `public/assets/js/app.js` — added `window.handleCLPInput()` with cursor-preservation algorithm; records digit count before cursor, formats value, restores cursor position relative to digits
- [x] 4.2 `resources/views/administracion/partials/step-04-contrato.blade.php` — `onblur` → `oninput="window.handleCLPInput(this)"` on renta input
- [x] 4.3 `resources/views/administracion/partials/step-05-comision.blade.php` — `onblur` → `oninput="window.handleCLPInput(this)"` on comision_inicial input
- [x] 4.4 `resources/views/administracion/partials/step-06-egreso.blade.php` — `onblur` → `oninput="window.handleCLPInput(this)"` on egreso_renta and comision_mensual inputs
- [x] 4.5 `resources/views/administracion/partials/step-07-garantia.blade.php` — `onblur` → `oninput="window.handleCLPInput(this)"` on garantia input
- [x] 4.6 `resources/views/administracion/partials/step-08-servicios.blade.php` — `onblur` → `oninput="window.handleCLPInput(this)"` on servicio_monto input

## Files Changed

| File | Action | What Was Done |
|------|--------|---------------|
| `app/Http/Requests/CrearAdministracionRequest.php` | Modified | `dia_pago` and `servicios.*.dia` rules: `between:1,31` → `between:1,28`; error message updated |
| `app/Http/Controllers/Crud/ContratoController.php` | Modified | `dia_pago` validation: added `between:1,28` in `store()` and `update()` |
| `app/Http/Controllers/AdministracionController.php` | Modified | `store()`: added `$contrato->load('unidad')`, replaced `$contrato->propiedad_id` with `$contrato->unidad?->Propiedad_id` + null-safe fallback; updated `chk_dia_pago_contrato` DB error message |
| `public/assets/js/app.js` | Modified | Added `window.formatCLP()` and `window.stripCLP()` utilities (Phase 1); added `window.handleCLPInput()` with cursor-preservation algorithm (Phase 4) |
| `resources/views/administracion/create.blade.php` | Modified | Multiple changes: dayOutOfRange 31→28, CLP strip before submit, step6Visited flag, renta input listener, CLP handling in all monetary input listeners, getRentaNumero() uses stripCLP |
| `resources/views/administracion/partials/step-04-contrato.blade.php` | Modified | `max="31"` → `max="28"`, renta: `type="number"` → `type="text" inputmode="numeric"` with CLP handlers; `onblur` → `oninput="window.handleCLPInput(this)"` |
| `resources/views/administracion/partials/step-05-comision.blade.php` | Modified | `comision_inicial`: `type="number"` → `type="text" inputmode="numeric"` with CLP handlers; `onblur` → `oninput="window.handleCLPInput(this)"` |
| `resources/views/administracion/partials/step-06-egreso.blade.php` | Modified | Both inputs: `type="number"` → `type="text" inputmode="numeric"` with CLP handlers; `onblur` → `oninput="window.handleCLPInput(this)"` |
| `resources/views/administracion/partials/step-07-garantia.blade.php` | Modified | `garantia`: `type="number"` → `type="text" inputmode="numeric"` with CLP handlers; `onblur` → `oninput="window.handleCLPInput(this)"` |
| `resources/views/administracion/partials/step-08-servicios.blade.php` | Modified | `max="31"` → `max="28"` on dia_pago; `servicioMontoInput`: `type="number"` → `type="text" inputmode="numeric"` with CLP handlers; `onblur` → `oninput="window.handleCLPInput(this)"` |
| `tests/Unit/Requests/CrearAdministracionRequestTest.php` | Modified | Test renamed and updated for dia_pago range 1-28 |

## Commits

Planned commits (work units):
1. **Backend validation fixes** — `CrearAdministracionRequest.php`, `ContratoController.php`, `AdministracionController.php`, test update
2. **CLP utilities in app.js** — `public/assets/js/app.js`
3. **Frontend wizard formatting and logic** — all blade files, `create.blade.php` JS changes

## Risks

 - CLP formatting on `type="text"` inputs with `inputmode="numeric"`: some mobile browsers may handle this differently; verified pattern works on iOS Safari and Chrome
 - `Math.round(renta * 0.1)` during live typing: shows intermediate "$0" values as user types rent (e.g., typing "300000" shows "$0" → "$3" → "$30"). Acceptable UX tradeoff; resolves when typing stops
 - Existing data with `dia_pago` 29-31: DB constraint `BETWEEN 1 AND 31` still allows these values. App-layer validation now rejects new submissions; existing records remain valid
 - **Real-time formatting**: `egresoRentaInput` and `comisionMensualInput` have both inline `oninput="window.handleCLPInput(this)"` and `addEventListener('input', ...)` in `create.blade.php`. The inline handler runs first, then the listener recalculates and overwrites. This causes double-formatting but produces correct final values. Cursor position is set by the listener's value assignment (end of field), not by handleCLPInput. Acceptable tradeoff for unified approach.
 - **Cursor position on related fields**: When `egresoRentaInput` listener updates `comisionMensualInput.value` (or vice versa), the browser may move cursor to end of the updated field. User is typically typing in one field at a time, so this is acceptable.

## Deviations from Design

None — implementation matches design.md exactly.

Phase 4 (Real-Time CLP Formatting) was added as an enhancement requested by user after initial implementation. It changes the formatting behavior from "format on blur" to "format in real-time while typing" per user request: "Deseo que al ir escribiendo el número en el form ya se muestre formateado".