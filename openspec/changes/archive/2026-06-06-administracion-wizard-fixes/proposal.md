# Proposal: Administracion Wizard Fixes

## Intent

Four bugs/UX issues in the administracion wizard: día de pago allows invalid days (29-31), submit redirect crashes because `Contrato` has no `propiedad_id` column, comisión mensual initializes to 0 instead of 10% of renta, and monetary inputs show raw integers instead of CLP-formatted values (`$xxx.xxx`).

## Scope

### In Scope

- Change `dia_pago` validation range from 1-31 to 1-28 (backend + frontend + test)
- Fix redirect after administracion creation: `$contrato->propiedad_id` → `$contrato->unidad->Propiedad_id` with eager-load
- Initialize comisión mensual as `Math.round(renta * 0.1)` and propagate changes when renta updates
- CLP formatting on monetary inputs (`$xxx.xxx`): change type to text+inputmode, format on blur, strip on focus and before submit

### Out of Scope

- Database constraint changes (migrations forbidden)
- Modifying other views beyond administracion wizard and contrato dia_pago
- Touching `app/Services/CrearAdministracionService.php` business logic
- Changing the Cobro/ParticipanteCobro creation logic

## Capabilities

### New Capabilities

- `clp-input-format`: Reusable CLP monetary formatting utility for input fields (format on blur, strip on focus, strip before form submission)

### Modified Capabilities

- `administracion-wizard`: dia_pago range 1-28 (was 1-31), redirect to propiedad ficha (was crash), comision_mensual auto-calc (was 0), monetary inputs CLP-formatted (was raw integer)

## Approach

1. **Dia de pago 1-28**: Update `CrearAdministracionRequest` validation rule `between:1,31` → `between:1,28`, update error message. Update `ContratoController` validation. Update frontend `dayOutOfRange` checks and `max="31"` → `max="28"` in step-04 and step-08. Update existing test assertion. Add frontend validation message change.

2. **Redirect fix**: Change `AdministracionController::store()` line 48 from `$contrato->propiedad_id` to `$contrato->unidad->Propiedad_id`. Add `$contrato->load('unidad')` eager-load before the redirect to avoid lazy-load crash.

3. **Comisión mensual 10%**: In `step-06-egreso.blade.php` (or the JS in `create.blade.php`), change `comisionMensualInput.value = 0` → `Math.round(renta * 0.1)`. Add an `input` listener on the renta field that recalculates `comision_mensual` and `egreso = renta - comision` when step 6 has been visited.

4. **CLP formatting**: Create reusable `formatCLP(value)` and `stripCLP(formatted)` functions in `app.js`. Change monetary inputs from `type="number"` to `type="text" inputmode="numeric"`. Add `blur` → formatCLP, `focus` → stripCLP. Before form submission, strip all formatted fields. Backend `prepareForValidation()` already exists and will continue to sanitize.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Http/Requests/CrearAdministracionRequest.php` | Modified | Change `dia_pago` rule from `between:1,31` to `between:1,28`, update error message |
| `app/Http/Controllers/AdministracionController.php` | Modified | Fix redirect from `$contrato->propiedad_id` to `$contrato->unidad->Propiedad_id` with eager-load |
| `app/Http/Controllers/Crud/ContratoController.php` | Modified | Add `between:1,28` constraint to `dia_pago` validation |
| `resources/views/administracion/create.blade.php` | Modified | Add renta → comision listener, update dayOutOfRange to 28, strip CLP before submit |
| `resources/views/administracion/partials/step-04-contrato.blade.php` | Modified | Change `max="31"` → `max="28"`, add CLP format on renta input |
| `resources/views/administracion/partials/step-05-comision.blade.php` | Modified | Add CLP format on comision_inicial input |
| `resources/views/administracion/partials/step-06-egreso.blade.php` | Modified | Initialize comision_mensual as 10% of renta, add CLP format on egreso/comision_mensual inputs |
| `resources/views/administracion/partials/step-07-garantia.blade.php` | Modified | Add CLP format on garantia input |
| `resources/views/administracion/partials/step-08-servicios.blade.php` | Modified | Change `max="31"` → `max="28"` on dia_pago fields, add CLP format on monto inputs |
| `public/assets/js/app.js` | New utility | Add `formatCLP()` and `stripCLP()` reusable functions |
| `tests/Unit/Requests/CrearAdministracionRequestTest.php` | Modified | Update dia_pago test range assertion from 31 to 28 |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| DB constraint `BETWEEN 1 AND 31` allows values frontend now rejects (29-31) | Low | Validation at app layer is the guard; DB constraint is a safety net only. If legacy data has dia_pago=29-31, edit flows still work |
| Changing input type from `number` to `text` may break existing parseInt logic | Med | Audit all `parseInt()` calls on affected fields; wrap with `stripCLP()` before parsing |
| Eager-load `unidad` may not exist on Contrato in edge cases | Low | Use optional chaining `$contrato->unidad?->Propiedad_id` or null-safe accessor |
| CLP format ambiguity with `.` as thousands separator vs decimal | Low | CLP has no decimals; strip function removes all non-digits; format always uses `.` as thousands separator, no decimals |

## Rollback Plan

Each fix is independent — revert individual commits:
1. Dia de pago: revert `between:1,28` → `between:1,31` in Request + controller + views + test
2. Redirect: revert `$contrato->unidad->Propiedad_id` → `$contrato->propiedad_id`
3. Comisión: revert `Math.round(renta * 0.1)` → `0`
4. CLP format: revert input types back to `number`, remove `formatCLP`/`stripCLP` calls

## Dependencies

- None external. All changes are within existing codebase.

## Success Criteria

- [ ] Day-of-month inputs reject 29-31 and accept 1-28 (frontend + backend)
- [ ] After creating administracion, browser redirects to `propiedad.ficha` for the correct property
- [ ] Comisión mensual initializes to 10% of renta (rounded) on step 6
- [ ] Changing renta after step 6 visit recalculates comisión mensual and egreso
- [ ] All monetary inputs display `$xxx.xxx` format on blur and accept raw input on focus
- [ ] Existing `CrearAdministracionRequestTest` passes with updated range