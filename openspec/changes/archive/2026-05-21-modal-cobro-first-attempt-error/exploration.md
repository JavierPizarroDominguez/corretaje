# Exploration: Modal cobro first-attempt error

## Current State

The cobro creation modal is triggered from `pendientes.blade.php` via `abrirModal()`, which clones HTML from a hidden `div#vista-agregar-cobro` into a Bootstrap modal. The form uses a `buscador` (search/autocomplete) component for foreign-key fields (contrato, servicio, propiedad, unidad, deudor, acreedor) when record counts exceed 15.

The bug: **first form submission fails, second attempt with same data succeeds**.

### Root Cause Analysis

Three interlocking bugs were identified:

#### Bug 1 (PRIMARY — triggers the "first fails, second works" pattern): Buscador API missing `id` field

**`BuscadorController::index()`** returns items with `{ tipo, texto, url }` — **no `id` field**:

```php
$resultados[] = [
    'tipo'  => 'cliente',
    'texto' => $this->getSearchText($item, ["nombre"]),
    'url'   => '/cliente/' . $item->id,
];
```

**`create.blade.php` onSelect callbacks** for deudor and acreedor reference `item.id`:

```javascript
document.getElementById('input-create-deudor-id').value = item.id;   // undefined!
document.getElementById('input-create-acreedor-id').value = item.id; // undefined!
```

Since `item.id` is `undefined`, the hidden inputs `deudor_Cliente_id` and `acreedor_Cliente_id` get set to the **string `"undefined"`**.

**First attempt**: Form POSTs `deudor_Cliente_id = "undefined"` → Laravel's `ConvertEmptyStringsToNull` middleware does NOT convert "undefined" (it only converts empty strings) → validation rule `'deudor_Cliente_id' => 'sometimes|nullable|integer|exists:cliente,id'` fails because `"undefined"` is not an integer → **422 validation error**.

**Second attempt** (after error redirect): The hidden inputs have NO `value="{{ old('deudor_Cliente_id') }}"` attribute, so they're **empty strings** → `ConvertEmptyStringsToNull` converts them to `null` → `nullable` validation passes → `firstOrCreate` by name handles the relationship → **SUCCESS**.

#### Bug 2 (SECONDARY): Buscador onSelect callbacks missing hidden-input updates for contrato, servicio, propiedad, unidad

```javascript
// Contrato — sets visible input only, hidden input NOT set
buscador({ input: '#input-create-contrato', onSelect: function(item) {
    document.getElementById('input-create-contrato').value = item.texto;
    // MISSING: document.getElementById('input-create-contrato-id').value = item.id;
}});

// Same bug for servicio, propiedad, unidad
```

These four buscador fields never populate their hidden ID inputs. However, since Bug 3 below prevents results from appearing at all for contrato/servicio/propiedad, this bug is masked for those three fields. For unidad, the buscador does return results but the ID is still missing from the response.

#### Bug 3 (TERTIARY): BuscadorController only handles `unidad` and `cliente` types

The controller has `if ($request->has('unidad'))` and `if ($request->has('cliente'))` handlers, but **no handlers for contrato, servicio, or propiedad**. Searches for these types always return empty results ("No se encontraron resultados"), making those buscador fields non-functional.

## Affected Areas

- `app/Http/Controllers/BuscadorController.php` — Missing `id` in response; missing handlers for contrato, servicio, propiedad
- `resources/views/cobro/modal/create.blade.php` — onSelect callbacks reference `item.id` (undefined); contrato/servicio/propiedad/unidad callbacks don't set hidden inputs
- `resources/views/components/pendientes.blade.php` — Trigger point for the modal
- `resources/views/layouts/app.blade.php` — `abrirModal()` function (not buggy, but relevant context)
- `public/js/buscador.js` — Search component (not buggy, but relevant context)
- `app/Http/Controllers/Crud/CobroController.php` — Store validation and firstOrCreate logic
- All other modal create views that use buscador with `item.id` or similar patterns

## Approaches

### 1. Fix BuscadorController Response + Fix onSelect Callbacks
- Add `id` field to BuscadorController response for ALL entity types
- Add missing `onSelect` hidden-input assignments in `create.blade.php`
- Add handlers for contrato, servicio, propiedad in BuscadorController

**Pros**: Complete fix; all buscador fields work correctly; consistent API contract
**Cons**: Touches multiple files; need to verify all modal create views
**Effort**: Medium

### 2. Fix onSelect to extract ID from URL
- In each `onSelect` callback, extract the ID from `item.url` (e.g., `/cliente/5` → `5`)
- Also add missing hidden-input assignments for contrato, servicio, propiedad, unidad

**Pros**: Minimally invasive; no backend changes; works with existing API
**Cons**: Parsing IDs from URL strings is fragile; different URL patterns per entity
**Effort**: Low

### 3. Hybrid: Fix BuscadorController + Fix all onSelect Callbacks
- Add `id` field to BuscadorController response (proper fix)
- Fix ALL onSelect callbacks across all create modal views to set hidden inputs
- Add missing BuscadorController handlers for contrato, servicio, propiedad

**Pros**: Root cause fixed; consistent across all forms; extensible for future entities
**Cons**: Most files to touch; need to audit all modal views
**Effort**: Medium-High

## Recommendation

**Approach 3 (Hybrid)** — The BuscadorController must return `id` in its response — this is the canonical fix. Then fix all `onSelect` callbacks to set hidden inputs. Finally, add the missing entity search handlers. The `item.id` → `undefined` → validation failure chain is the root cause of the "first attempt fails, second works" pattern, and fixing it at the source (the API) is the safest long-term approach.

Critical files to audit for the same `item.id` / missing hidden-input pattern:
- `cobro/modal/create.blade.php`
- `cobro/modal/show.blade.php`
- `cobro/edit.blade.php`
- `cobro/create.blade.php`
- All other entity modal views using buscador

## Risks

- **Other entities may have the same bug**: Every modal create view that uses `buscador` with `onSelect(item)` referencing `item.id` has the same `undefined` problem. Need to audit all of them.
- **BuscadorController regressions**: Adding new entity handlers must not break existing unidad/cliente searches. Write tests first.
- **Form data after error redirect**: The hidden inputs (`deudor_Cliente_id`, `acreedor_Cliente_id`) lack `value="{{ old(...) }}"`, which means after a validation error, user selections are lost. This is a UX issue that should be addressed alongside the bug fix.
- **Modal state after reopen**: The `abrirModal()` function restores innerHTML from `savedHTML`, which does NOT include any JavaScript state (event listeners set via JS, only DOM attributes). However, since `buscador` uses `document.addEventListener`, this is not an issue here.

## Ready for Proposal

Yes — the root cause is clear (BuscadorController missing `id` field + incomplete onSelect callbacks), the fix scope is well-defined, and the approach is straightforward.