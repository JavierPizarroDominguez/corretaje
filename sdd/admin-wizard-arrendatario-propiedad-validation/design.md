# Technical Design: Admin Wizard — Arrendatario ≠ Arrendador + Contrato Vigente Validation

## 1. Overview

This design adds two new validation rules to the administración wizard:

| Step | Validation | Type | Description |
|------|-----------|------|-------------|
| 2 | Arrendatario ≠ Arrendador | **Sync** (client-side) | The selected arrendatario must not be the same person as the arrendador |
| 3 | No contrato vigente | **Async** (API call) | The selected propiedad must not already have an active contract |

To support async validation, `validateStep()` is refactored from a synchronous boolean-returning function to an async function returning `Promise<boolean>`.

---

## 2. Existing `validateStep()` Analysis

### Current signature
```js
function validateStep(stepNum) {
    // ... validation checks ...
    return true; // or false
}
```

### Current callers (3 call sites)

1. **`goToStep(n)`** — line ~455-469 in `create.blade.php`:
   ```js
   if (typeof window.validateStep === 'function' && !window.validateStep(this.step)) return;
   ```
   Synchronous guard: if validation fails, the function returns early and navigation is blocked.

2. **`nextStep()`** — line ~471-478:
   ```js
   if (typeof window.validateStep === 'function' && !window.validateStep(this.step)) return;
   if (typeof window.jumpOrAdvance === 'function') {
       window.jumpOrAdvance();
   }
   ```
   Same synchronous guard pattern.

3. **Form submit handler** — line ~1031-1046:
   ```js
   for (var stepNum = 1; stepNum <= 8; stepNum++) {
       if (!validateStep(stepNum)) {
           wizard.step = stepNum;
           e.preventDefault();
           return;
       }
   }
   ```
   Iterates all steps synchronously; blocks submission on first failure.

---

## 3. Async Validation Flow Design

### 3.1 Refactored `validateStep()` signature

```js
/**
 * @param {number} stepNum
 * @returns {Promise<boolean>} true if valid, false if invalid
 */
async function validateStep(stepNum) {
    // ... existing sync checks ...

    // Step 2: arrendatario ≠ arrendador (sync)
    if (stepNum === 2) {
        if (!validateArrendatarioDiferenteArrendador()) return false;
    }

    // Step 3: no contrato vigente (async API call)
    if (stepNum === 3) {
        var valid = await validateNoContratoVigente();
        if (!valid) return false;
    }

    return true;
}
```

### 3.2 Caller adaptations

All three callers must be updated to `await` the Promise.

#### `goToStep(n)` — becomes async

```js
goToStep: async function(n) {
    if (n === this.step) return;
    if (n < this.step && this.step === this.maxReachedStep) {
        if (n <= this.maxReachedStep && !this.getSkippedSteps().has(n)) {
            this.step = n;
        }
        return;
    }
    // AWAIT the async validation
    if (typeof window.validateStep === 'function') {
        var isValid = await window.validateStep(this.step);
        if (!isValid) return;
    }
    if (n <= this.maxReachedStep && !this.getSkippedSteps().has(n)) {
        this.step = n;
    }
}
```

#### `nextStep()` — becomes async

```js
nextStep: async function() {
    if (typeof window.validateStep === 'function') {
        var isValid = await window.validateStep(this.step);
        if (!isValid) return;
    }
    if (typeof window.jumpOrAdvance === 'function') {
        window.jumpOrAdvance();
    }
}
```

#### Form submit handler — async IIFE

```js
if (wizardForm) {
    wizardForm.addEventListener('submit', async function(e) {
        var alpineEl = document.querySelector('[x-data]');
        if (!alpineEl || !alpineEl._x_dataStack) return;
        var wizard = alpineEl._x_dataStack[0];

        for (var stepNum = 1; stepNum <= 8; stepNum++) {
            var isValid = await validateStep(stepNum);
            if (!isValid) {
                wizard.step = stepNum;
                e.preventDefault();
                return;
            }
        }
    });
}
```

#### `callWizardNextStep()` — must await

```js
async function callWizardNextStep() {
    var alpineEl = document.querySelector('[x-data]');
    if (alpineEl && alpineEl._x_dataStack) {
        var wizard = alpineEl._x_dataStack[0];
        if (typeof wizard.nextStep === 'function') {
            await wizard.nextStep();
        }
    }
}
```

### 3.3 Loading indicator during async step 3 validation

While the API call for contrato vigente is in flight, the propiedad select and btnAddPropiedad must be disabled with a loading indicator:

```js
async function validateNoContratoVigente() {
    var sel = document.getElementById('propiedadSelect');
    var btn = document.getElementById('btnAddPropiedad');

    // Only validate when an existing propiedad is selected (not "nueva")
    if (!sel || sel.value === 'nueva' || !sel.value) return true;

    var propiedadId = sel.value;

    // Disable UI during fetch
    if (btn) btn.disabled = true;
    if (sel) sel.disabled = true;
    if (typeof window.showElLoading === 'function') {
        window.showElLoading(document.getElementById('step-propiedad'));
    }

    try {
        var res = await fetch('/api/propiedades/' + encodeURIComponent(propiedadId) + '/contrato-vigente');
        var data = await res.json();

        if (data.has_contrato_vigente) {
            var unidadNombre = data.unidad_nombre ? ' — Unidad ' + data.unidad_nombre : '';
            showWizardError('La propiedad ya tiene un contrato vigente' + unidadNombre + '.');
            return false;
        }
        return true;
    } catch (err) {
        // On network error, allow proceeding (fail-open) but log
        console.error('Error checking contrato vigente:', err);
        return true;
    } finally {
        if (btn) btn.disabled = false;
        if (sel) sel.disabled = false;
        if (typeof window.hideElLoading === 'function') {
            window.hideElLoading(document.getElementById('step-propiedad'));
        }
    }
}
```

---

## 4. Arrendatario ≠ Arrendador Check (Sync)

### 4.1 Comparison logic

```js
function normalizeName(name) {
    return (name || '').trim().toLowerCase();
}

function validateArrendatarioDiferenteArrendador() {
    var arrendadorInput = document.querySelector('[name="arrendador_nombre"]');
    var arrendatarioInput = document.querySelector('[name="arrendatario_nombre"]');
    var hiddenArrendadorId = document.getElementById('hidden-arrendador-id');
    var hiddenArrendatarioId = document.getElementById('hidden-arrendatario-id');

    var arrendadorNombre = arrendadorInput ? arrendadorInput.value : '';
    var arrendatarioNombre = arrendatarioInput ? arrendatarioInput.value : '';
    var arrendadorId = hiddenArrendadorId ? hiddenArrendadorId.value : '';
    var arrendatarioId = hiddenArrendatarioId ? hiddenArrendatarioId.value : '';

    // Edge case: propiedad_corredor checked → arrendador is "Corredor" (id=1)
    // The arrendatario can never be the corredor, but we still check by ID
    // if both selected from the same autocomplete (cliente DB).

    // Primary check: compare hidden IDs (most reliable — both from cliente DB)
    if (arrendadorId && arrendatarioId && arrendadorId === arrendatarioId) {
        showWizardError('El arrendatario no puede ser la misma persona que el arrendador.');
        return false;
    }

    // Fallback check: compare normalized names (catches manual typing without selection)
    if (normalizeName(arrendadorNombre) && normalizeName(arrendatarioNombre)
        && normalizeName(arrendadorNombre) === normalizeName(arrendatarioNombre)) {
        showWizardError('El arrendatario no puede ser la misma persona que el arrendador.');
        return false;
    }

    return true;
}
```

### 4.2 Edge cases handled

| Scenario | How it's handled |
|----------|-----------------|
| Same person, different casing (`"Juan Perez"` vs `"juan perez"`) | `toLowerCase()` normalization catches it |
| Leading/trailing spaces (`" Juan "` vs `"Juan"`) | `trim()` normalization catches it |
| Both selected from autocomplete (IDs available) | ID comparison is primary — exact match |
| One typed manually, one from autocomplete | Name comparison fallback catches it |
| `propiedad_corredor` checked (arrendador = "Corredor", id=1) | Arrendatario autocomplete won't return id=1 typically, but ID check still covers it |
| Neither selected yet (both empty) | Both checks pass — empty names are not compared |

---

## 5. Contrato Vigente API Design

### 5.1 Route

```php
// routes/api.php — inside [GEN:START:administracion_api] block
Route::get('/propiedades/{id}/contrato-vigente', [PropiedadContratoVigenteController::class, 'show'])
    ->name('api.propiedades.contrato-vigente');
```

### 5.2 Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Propiedad;
use Illuminate\Http\JsonResponse;

class PropiedadContratoVigenteController extends Controller
{
    /**
     * Check if a propiedad has an active (vigente) contract.
     *
     * Resolution chain: Propiedad → Unidad → Contrato (vigente)
     */
    public function show(int $id): JsonResponse
    {
        $propiedad = Propiedad::with('unidad')->find($id);

        if (!$propiedad) {
            return response()->json([
                'error' => 'Propiedad no encontrada',
            ], 404);
        }

        // A propiedad may not have a unidad (e.g., newly created without unidad)
        if (!$propiedad->unidad) {
            return response()->json([
                'has_contrato_vigente' => false,
                'unidad_id' => null,
                'unidad_nombre' => null,
                'contrato_id' => null,
            ]);
        }

        // Use existing Unidad::contratoVigente() relationship
        $contratoVigente = $propiedad->unidad->contratoVigente()->first();

        return response()->json([
            'has_contrato_vigente' => $contratoVigente !== null,
            'unidad_id' => $propiedad->unidad->id,
            'unidad_nombre' => $propiedad->unidad->nombre,
            'contrato_id' => $contratoVigente?->id,
        ]);
    }
}
```

### 5.3 Response format

**Success — no active contract:**
```json
{
    "has_contrato_vigente": false,
    "unidad_id": 42,
    "unidad_nombre": "Depto 3B",
    "contrato_id": null
}
```

**Success — active contract exists:**
```json
{
    "has_contrato_vigente": true,
    "unidad_id": 42,
    "unidad_nombre": "Depto 3B",
    "contrato_id": 157
}
```

**Error — propiedad not found:**
```json
{
    "error": "Propiedad no encontrada"
}
```
HTTP 404.

### 5.4 How `Unidad::contratoVigente()` works

Existing method in `app/Models/Unidad.php` (line 39-46):

```php
public function contratoVigente()
{
    return $this->hasOne(Contrato::class)
        ->where(function ($query) {
            $query->where('fecha_termino', '>', now())
                ->orWhereNull('fecha_termino');
        });
}
```

A contract is considered "vigente" (active) when:
- `fecha_termino` is in the future (`> now()`), OR
- `fecha_termino` is NULL (open-ended contract)

This is the **exact same logic** used elsewhere in the system. We reuse it via the relationship rather than duplicating the query.

---

## 6. Edge Cases Summary

| Edge Case | Step | Handling |
|-----------|------|----------|
| Arrendatario typed with different casing than arrendador | 2 | `trim().toLowerCase()` comparison |
| Leading/trailing spaces in names | 2 | `trim()` in normalization |
| `propiedad_corredor` checked (arrendador = "Corredor") | 2 | ID comparison still applies; name comparison handles "Corredor" literal |
| "Nueva propiedad" selected (no existing propiedad) | 3 | **Skipped** — no API call needed, new properties can't have contracts |
| Propiedad has no unidad | 3 | API returns `has_contrato_vigente: false` — safe to proceed |
| Network error during API call | 3 | **Fail-open** — `catch` returns `true`, error logged to console |
| User navigates away during API call (goToStep while loading) | 3 | `finally` block always re-enables UI; no race condition |
| Both arrendador and arrendatario empty | 2 | Validation passes (empty ≠ empty is not checked) |
| Arrendador from autocomplete, arrendatario typed manually | 2 | Name comparison fallback catches it |
| Multiple rapid clicks on btnAddPropiedad | 3 | Button disabled during fetch prevents double-submit |

---

## 7. Sequence Diagram — Step 3 Async Validation

```
User                    btnAddPropiedad           validateStep()              API Controller              DB
 |                          |                          |                           |                       |
 |-- click "Añadir" ------>|                          |                           |                       |
 |                          |-- disable btn+select -->|                           |                       |
 |                          |-- showElLoading ------> |                           |                       |
 |                          |                          |-- fetch GET /api/... --->|                       |
 |                          |                          |                           |-- find Propiedad --->|
 |                          |                          |                           |<-- Propiedad + Unidad |
 |                          |                          |                           |-- unidad.contratoVigente()
 |                          |                          |                           |                       |
 |                          |                          |                           |<-- Contrato or null   |
 |                          |                          |<-- JSON response --------|                       |
 |                          |                          |                           |                       |
 |                          |                          |-- has_contrato?           |                       |
 |                          |                          |  YES: showWizardError     |                       |
 |                          |                          |  NO:  return true         |                       |
 |                          |<-- enable btn+select ----|                           |                       |
 |                          |<-- hideElLoading --------|                           |                       |
 |<-- advance or stay ------|                          |                           |                       |
```

---

## 8. Code Diff Summary

### Files changed

| File | Change |
|------|--------|
| `resources/views/administracion/create.blade.php` | Refactor `validateStep()` to async; add `normalizeName()`, `validateArrendatarioDiferenteArrendador()`, `validateNoContratoVigente()`; update `goToStep`, `nextStep`, `callWizardNextStep`, form submit handler to async/await |
| `routes/api.php` | Add route: `GET /propiedades/{id}/contrato-vigente` |
| `app/Http/Controllers/Api/PropiedadContratoVigenteController.php` | **New file** — controller for contrato vigente check |

### Lines estimate

| File | Lines added | Lines removed | Lines changed |
|------|------------|---------------|---------------|
| `create.blade.php` | ~80 | ~30 | ~50 |
| `routes/api.php` | 2 | 0 | 0 |
| `PropiedadContratoVigenteController.php` | ~40 | 0 | 0 |
| **Total** | **~122** | **~30** | **~50** |

---

## 9. Testing Considerations

### Client-side (manual/browser)
1. Select arrendador "Juan Perez", then arrendatario "Juan Perez" → should block with error
2. Select arrendador "Juan Perez", then arrendatario " juan perez " → should block (normalization)
3. Select arrendador "Juan Perez", then arrendatario "Maria Lopez" → should pass
4. Check `propiedad_corredor`, then arrendatario "Corredor" → should block
5. Select existing propiedad with active contract → should block with API error
6. Select existing propiedad without contract → should pass
7. Select "nueva propiedad" → should skip API check, pass
8. Select propiedad, lose network → should fail-open (allow proceed, log error)

### Server-side (PHPUnit)
- `PropiedadContratoVigenteControllerTest`:
  - Returns 404 for non-existent propiedad
  - Returns `has_contrato_vigente: false` when no unidad
  - Returns `has_contrato_vigente: false` when unidad has no contracts
  - Returns `has_contrato_vigente: false` when contrato `fecha_termino` is in the past
  - Returns `has_contrato_vigente: true` when contrato `fecha_termino` is in the future
  - Returns `has_contrato_vigente: true` when contrato `fecha_termino` is NULL
