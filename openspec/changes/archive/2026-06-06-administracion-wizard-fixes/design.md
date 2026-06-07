# Design: Administracion Wizard Fixes

## Technical Approach

Four independent bug fixes in the administracion wizard, each touching validation, navigation, initialization logic, and input formatting. No database changes. All fixes follow existing MVC + vanilla JS + Alpine.js patterns.

## Architecture Decisions

### Decision: CLP Formatting Utilities Location

| Option | Tradeoff | Decision |
|--------|----------|----------|
| `public/assets/js/app.js` as global `window.formatCLP`/`window.stripCLP` | Reusable across views, consistent with `showElLoading` pattern | **Chosen** — follows existing convention |
| Inline in `create.blade.php` | Self-contained but duplicated if other views need it | Rejected — violates DRY |
| Separate `clp-format.js` file | Clean separation but extra HTTP request | Rejected — overkill for 2 small functions |

### Decision: Commission Initialization Strategy

| Option | Tradeoff | Decision |
|--------|----------|----------|
| `Math.round(renta * 0.1)` on step 6 entry + `input` listener on renta | Simple, reactive, no state management needed | **Chosen** — matches existing egreso/comision bidirectional sync pattern |
| Alpine.js reactive computed property | More idiomatic but requires refactoring wizard state | Rejected — too invasive for a bug fix |

### Decision: Redirect Null Safety

| Option | Tradeoff | Decision |
|--------|----------|----------|
| `$contrato->unidad?->Propiedad_id` with null check | Safe but masks deeper data issues | **Chosen** — safe fallback + explicit error logging |
| `$contrato->load('unidad')` then `$contrato->unidad->Propiedad_id` | Crashes if unidad is null | Rejected — too fragile |

## Data Flow

### Fix 2: Redirect After Creation

```
AdministracionController::store()
    └─ $service->crearAdministracion($request) → returns $contrato
    └─ $contrato->load('unidad')              → eager-load relationship
    └─ $contrato->unidad?->Propiedad_id       → traverse to Propiedad
    └─ Redirect::route('propiedad.ficha', ...) → redirect to property page
```

### Fix 3: Commission Auto-Calculation

```
User enters renta (step 4)
    └─ rentaInput value set
    └─ User advances to step 6
        └─ jumpOrAdvance() detects step === 6
        └─ comisionMensual = Math.round(renta * 0.1)
        └─ egresoRenta = renta - comisionMensual
    └─ If user goes back and changes renta
        └─ renta 'input' listener fires (only if step 6 was visited)
        └─ Recalculate comision + egreso
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Http/Requests/CrearAdministracionRequest.php` | Modify | `dia_pago` rule `between:1,31` → `between:1,28`, message `1 y 31` → `1 y 28` |
| `app/Http/Controllers/AdministracionController.php` | Modify | Line 48: add `$contrato->load('unidad')`, use `$contrato->unidad?->Propiedad_id` with null fallback |
| `app/Http/Controllers/Crud/ContratoController.php` | Modify | `dia_pago` validation `sometimes\|nullable\|integer` → add `between:1,28` constraint |
| `resources/views/administracion/create.blade.php` | Modify | Update `dayOutOfRange` threshold 31→28, add renta→comision listener, strip CLP before submit, update validation message |
| `resources/views/administracion/partials/step-04-contrato.blade.php` | Modify | `max="31"` → `max="28"`, renta input: `type="number"` → `type="text" inputmode="numeric"` |
| `resources/views/administracion/partials/step-05-comision.blade.php` | Modify | `comision_inicial` input: `type="number"` → `type="text" inputmode="numeric"` |
| `resources/views/administracion/partials/step-06-egreso.blade.php` | Modify | Both inputs: `type="number"` → `type="text" inputmode="numeric"` |
| `resources/views/administracion/partials/step-07-garantia.blade.php` | Modify | `garantia` input: `type="number"` → `type="text" inputmode="numeric"` |
| `resources/views/administracion/partials/step-08-servicios.blade.php` | Modify | `max="31"` → `max="28"` on dia_pago, monto input: `type="number"` → `type="text" inputmode="numeric"` |
| `public/assets/js/app.js` | Modify | Add `window.formatCLP()` and `window.stripCLP()` utilities |
| `tests/Unit/Requests/CrearAdministracionRequestTest.php` | Modify | Update test name and assertions from 31 to 28 |

## Interfaces / Contracts

### CLP Formatting Utilities (app.js)

```js
/**
 * Format a numeric value as CLP: $1.234.567 (no decimals).
 * @param {number|string} value - Raw numeric value
 * @returns {string} Formatted string like "$500.000"
 */
window.formatCLP = function(value) {
    var num = parseInt(String(value).replace(/[^0-9]/g, ''), 10);
    if (isNaN(num) || num === 0) return '$0';
    return '$' + num.toLocaleString('es-CL');
};

/**
 * Strip CLP formatting to get raw numeric string.
 * @param {string} formatted - Formatted string like "$500.000"
 * @returns {string} Raw digits like "500000"
 */
window.stripCLP = function(formatted) {
    return String(formatted).replace(/[^0-9]/g, '');
};
```

### Form Submission CLP Strip (create.blade.php)

Before form submission, iterate all monetary inputs and strip formatting:

```js
// In wizardForm submit handler, before validation loop:
var monetaryFields = ['renta', 'comision_inicial', 'egreso_renta', 'comision_mensual', 'garantia'];
monetaryFields.forEach(function(name) {
    var el = document.querySelector('[name="' + name + '"]');
    if (el && el.value) {
        el.value = window.stripCLP(el.value);
    }
});
// Also strip servicios.*.monto hidden inputs
```

### Commission Auto-Calculation (create.blade.php)

Replace the step 6 auto-fill block in `jumpOrAdvance()`:

```js
// OLD: comisionMensualInput.value = 0;
// NEW:
if (comisionMensualInput && !noComisionMensualCheck.checked) {
    var rentaVal = parseInt(window.stripCLP(rentaInput.value)) || 0;
    if (rentaVal > 0) {
        var comision = Math.round(rentaVal * 0.1);
        comisionMensualInput.value = comision;
        egresoInput.value = rentaVal - comision;
    }
}
```

Add renta input listener (only active after step 6 visited):

```js
var step6Visited = false;
// Set step6Visited = true when jumpOrAdvance enters step 6
rentaInput.addEventListener('input', function() {
    if (!step6Visited) return;
    var noComision = document.getElementById('noComisionMensual');
    if (noComision && noComision.checked) return;
    var renta = parseInt(window.stripCLP(this.value)) || 0;
    var comisionInput = document.getElementById('comisionMensualInput');
    var egresoInput = document.getElementById('egresoRentaInput');
    if (comisionInput && egresoInput && renta > 0) {
        var comision = Math.round(renta * 0.1);
        comisionInput.value = comision;
        egresoInput.value = renta - comision;
    }
});
```

### Backend: AdministracionController Redirect

```php
// Line 44-48 replacement:
$contrato = $service->crearAdministracion($request);
$contrato->load('unidad');

$propiedadId = $contrato->unidad?->Propiedad_id;
if (!$propiedadId) {
    Log::warning('Contrato created without unidad relationship', ['contrato_id' => $contrato->id]);
    Session::flash('success', 'Administración creada exitosamente.');
    return Redirect::route('dashboard');
}

Session::flash('success', 'Administración creada exitosamente.');
return Redirect::route('propiedad.ficha', ['id' => $propiedadId]);
```

### Backend: ContratoController dia_pago Validation

```php
// In store() and update() validation arrays:
'dia_pago' => 'sometimes|nullable|integer|between:1,28',
```

### Error Message Update

```php
// AdministracionController::resolveDbErrorMessage():
// Update chk_dia_pago_contrato message from "1 y 31" to "1 y 28"
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | `dia_pago` validation range 1-28 | Update existing test: assert 0 fails, 29 fails, 15 passes, 28 passes |
| Manual | Redirect after creation | Create administracion via browser, verify redirect to `propiedad.ficha/{id}` |
| Manual | Commission initializes to 10% | Enter renta=500000, advance to step 6, verify comision=50000, egreso=450000 |
| Manual | Commission recalculates on renta change | After step 6, go back to step 4, change renta, verify comision updates |
| Manual | CLP formatting | Enter 500000 in renta, blur → shows `$500.000`, focus → shows `500000` |
| Manual | CLP strip on submit | Submit form with formatted values, verify backend receives raw integers |

**CRITICAL**: Do NOT run tests with `RefreshDatabase`. The existing `CrearAdministracionRequestTest` uses `Validator::make()` directly (no DB), so it is safe. Run with `./vendor/bin/phpunit --filter CrearAdministracionRequestTest`.

## Migration / Rollout

No migration required. All changes are backwards-compatible:
- Validation tightening (1-28) only affects new submissions; existing data with dia_pago 29-31 remains valid in DB
- Input type change from `number` to `text` is transparent to backend (already sanitizes in `prepareForValidation`)
- Commission default change only affects new forms; existing `old()` values are preserved

## Open Questions

- [ ] Should the CLP formatting also apply to the resumen panel display values? (Currently uses `Number().toLocaleString()` — could unify with `formatCLP`)
- [ ] Is `Math.round(renta * 0.1)` the correct business rule, or should it be configurable? (Proposal says 10%, hardcoded for now)
