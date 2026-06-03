# Design: Loading Indicators

## Approach

Two-layer loading strategy: (1) a **page-level overlay** for initial server-rendered page loads, (2) **local spinners** inside specific containers for every `fetch()` call. Both layers use the same Bootstrap `spinner-border` component, wrapped in two utility functions in `app.js`.

### Layer 1: Page-Level Overlay (Initial Load Only, `<main>` Only)

A new loading overlay div is added inside `layouts/app.blade.php` — positioned **over the `<main>` content area only**, not the sidebar or header. This means:
- The sidebar remains clickable during page load
- The header (logo, nav) stays accessible
- The user can navigate away if a page is slow

The overlay starts **visible** (CSS `display: block`), then an inline `<script>` in the `<head>` starts a 200ms `setTimeout`. If `DOMContentLoaded` fires before 200ms, the timer is cancelled and the overlay never appears (no flicker). If the timer fires first, the overlay shows until `DOMContentLoaded` hides it permanently.

The overlay is **one-shot**: after `DOMContentLoaded`, the script removes the timer reference and the overlay handler, so subsequent `fetch()` calls never trigger it.

### Layer 2: Local Spinners via `showElLoading` / `hideElLoading`

Two functions added to `app.js` (exposed on `window`):

```js
window.showElLoading = function(container, colspan) {
  // container: HTMLElement (tbody, dropdown, button wrapper, etc.)
  // colspan: optional number for table <tr> colspan
  // Clears existing loading content, appends Bootstrap spinner-border
};

window.hideElLoading = function(container) {
  // Removes all .loading-indicator children from container
};
```

Each `fetch()` call wraps: `showElLoading(target)` → `fetch(...)` → `hideElLoading(target)` in both `.then()` and `.catch()`.

### Per-View Integration Map

| View / File | fetch() Call | Container | Spinner Pattern |
|---|---|---|---|
| `dashboard/index.blade.php` | `cargarPendientes()` | `#body-pendientes` (tbody) | `<tr>` with colspan=4 |
| `dashboard/index.blade.php` | buscador autocomplete | `#autocomplete-list` | `<div>` with spinner + text |
| `dashboard/index.blade.php` | `registrarPago()` | `#btn-registrar` (button) | Inline spinner, `disabled` |
| `buscador.js` | `fetch('/buscador?...')` | `list` (dropdown) | `<div>` with spinner |
| `administracion/create.blade.php` | `loadPropiedadesPorArrendador()` | `#propiedadSelect` | `<option>` with spinner text → replaced by utility |
| `cobro/modal/create.blade.php` | `resolveCobroRelationships()` | Modal body or form | Spinner overlay on form fields, `disabled` |
| `filtros.js` | `applyFilters()` | `tableBody` (tbody) | Uses `showElLoading(tbody, 99)` |

### Index Table Placeholder Rows

All server-rendered index tables (`cobro/index`, `cliente/index`, `contrato/index`, etc.) get a `<tr class="loading-placeholder">` with "Cargando..." in their `<tbody>`. A single `DOMContentLoaded` listener in `app.js` removes all `.loading-placeholder` rows on page ready.

## Key Decisions

1. **`showElLoading` clears container first** — avoids stacking multiple spinners if a fetch is triggered while one is already visible (e.g., rapid pagination clicks).
2. **`colspan=99` for tbody spinners** — existing `filtros.js` uses this pattern; it spans all columns regardless of table width.
3. **Page overlay is CSS-visible by default, JS-hidden** — no flash of unstyled content; the 200ms debounce prevents visible flicker on fast loads.
4. **Button spinner uses `disabled` attribute** — prevents double-submission during `registrarPago()`.
5. **`buscador.js` spinner goes inside the dropdown list** — replaces the list content temporarily, consistent with the autocomplete pattern.
6. **`filtros.js` refactors to use the utility** — removes the inline spinner HTML (lines 65-67) and replaces with `showElLoading(tableBody, 99)`.
7. **`app.js` stays as IIFE but exports utilities on `window`** — minimal change to existing structure; utilities are globally available for inline scripts in Blade views.
8. **No new CSS file** — spinner styles use Bootstrap 5 classes exclusively. Only one new CSS rule needed: `.loading-placeholder` opacity for visual distinction.

## Files Affected (9)

| File | Change Type | Description |
|---|---|---|
| `public/assets/js/app.js` | Modified | Add `showElLoading`, `hideElLoading`, `.loading-placeholder` remover |
| `public/assets/css/style.css` | Modified | Add `.loading-placeholder` style |
| `public/js/filtros.js` | Modified | Refactor inline spinner → `showElLoading`/`hideElLoading` |
| `public/js/buscador.js` | Modified | Add spinner to dropdown during fetch |
| `resources/views/layouts/app.blade.php` | Modified | Page-level overlay logic (200ms debounce), script order |
| `resources/views/dashboard/index.blade.php` | Modified | Spinner in `cargarPendientes`, `registrarPago`; remove inline buscador, use `buscador.js` |
| `resources/views/administracion/create.blade.php` | Modified | `loadPropiedadesPorArrendador` uses utility |
| `resources/views/cobro/modal/create.blade.php` | Modified | Spinner in `resolveCobroRelationships` |
| `AGENTS.md` | New | Loading indicator convention for all future views |

## Testing Strategy

Strict TDD with `php artisan test`. Tests focus on **behavioral verification** of the JS utilities and **integration tests** for the Blade views.

### 1. JS Utility Tests (Browser-based, if test runner supports it)
If a JS test runner (e.g., Pest + Dusk, or Vitest) is available:
- `showElLoading(tbody, 4)` appends `<tr>` with `spinner-border` and `colspan="4"`
- `hideElLoading(tbody)` removes the loading row
- Calling `showElLoading` twice on same container replaces, not stacks

### 2. Feature Tests (PHP / `php artisan test`)
- **Dashboard pendientes**: Assert `GET /dashboard` returns HTML with empty `#body-pendientes` (spinner will be JS-injected, so test verifies the container exists)
- **Buscador**: Assert `GET /buscador?q=test` returns valid JSON (existing test covers this; verify it still works)
- **Administracion wizard**: Assert `GET /api/propiedades/por-arrendador/{id}` returns JSON array (existing `PropiedadPorArrendadorControllerUnitTest` covers this)
- **Cobro payment**: Assert `POST /api/cobro/pagar` returns success JSON (existing `PagarCobroControllerTest` covers this)

### 3. Manual Verification Checklist
Since this is primarily a UI/UX change, manual verification is essential:
- [ ] Page loads show overlay only if >200ms, no flicker on fast loads
- [ ] Dashboard pendientes table shows spinner during pagination
- [ ] Buscador dropdown shows spinner while typing
- [ ] "Registrar Pago" button shows spinner + disabled during payment
- [ ] Administracion wizard shows spinner (not plain text) when loading properties
- [ ] Cobro modal shows spinner during relationship resolution
- [ ] Filtros panel shows spinner during filter apply (unchanged behavior, refactored code)
- [ ] All index tables show "Cargando..." placeholder removed on DOMContentLoaded

## Open Questions

1. **JS test runner availability** — The project has PHP tests but no visible JS test setup. Should JS utilities be tested via Dusk/browser tests, or is manual verification sufficient for this change?
2. **Dashboard buscador refactor** — The dashboard's inline buscador (lines 245-341 of `dashboard/index.blade.php`) is refactored to use the shared `public/js/buscador.js` module. **Decision: refactor now** — this removes duplication, ensures consistent behavior (including spinner) across all views, and prevents two implementations to maintain.

## Next Step

Ready for **sdd-tasks** — the design provides sufficient detail to break into implementation tasks with clear file-level boundaries.
