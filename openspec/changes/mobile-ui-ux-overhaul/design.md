# Design: Mobile UI/UX Overhaul

## Technical Approach

CSS-first responsive fixes + targeted Alpine.js/wizard JS tweaks. Leverages existing `.table-card-mobile` CSS (style.css:449-487) and `labelTable()` JS (app.js:117-143). No new dependencies. All changes are scoped to media queries (`≤991.98px` for layout, `≤575.98px` for card tables) or conditional JS logic gated by `sin_administracion`.

## Architecture Decisions

| Decision | Options | Tradeoff | Decision |
|----------|---------|----------|----------|
| Mobile detection | CSS media queries vs JS `matchMedia` | CSS is declarative, zero perf cost, aligns with existing pattern | CSS media queries at `≤991.98px` and `≤575.98px` |
| Table card approach | Existing `.table-card-mobile` + `labelTable()` vs new component | Existing code already handles `data-label` injection; no rewrite needed | Reuse existing pattern, add class to ficha tables |
| Wizard auto-init timing | On step visibility change vs on `nextStep`/`jumpOrAdvance` | `jumpOrAdvance` already has step 6 egreso auto-fill; extend same hook | Add step 5 commission and step 7 guarantee hooks inside `jumpOrAdvance` |
| Commission default | `Math.floor(renta / 2)` vs `Math.round` | Spec says "rounded down" | `Math.floor(renta / 2)` |
| Success redirect | Immediate redirect vs timed redirect | User explicitly wants immediate redirect, no confirmation modal | Immediate redirect with session flash message |
| Cobro back button | `?from=` query param vs `session('from_url')` | Query param is explicit, stateless, works with bookmarks | `?from=` with local URL validation |
| Buscador URL fix | Change controller vs route alias | Controller is single source of truth; route alias would need maintenance | Change `BuscadorController` line 44 |

## Data Flow

### Wizard Auto-Initialization Flow

```
User sets renta ──→ Step 4 (Contrato) "Añadir" clicked
                        │
                        ▼
                  jumpOrAdvance()
                        │
              step becomes 5? ──→ sin_administracion=false? ──→ comision_inicial = Math.floor(renta / 2)
                        │
              step becomes 6? ──→ (existing egreso = renta logic)
                        │
              step becomes 7? ──→ sin_administracion=false? ──→ garantia = renta
```

### sin_administracion Interaction

```
Checkbox unchecked (default):
  Steps 5,6,7 visible → auto-init fires on entry

Checkbox checked:
  Steps 5,6,7 SKIPPED (jump to step 8)
  sinAdminSnapshot captures egreso, comision_mensual, garantia, servicios
  renta/dia_pago disabled + cleared

Checkbox unchecked again:
  sinAdminSnapshot restored to inputs
  Auto-init does NOT re-fire (values already restored)
```

### Cobro Back Navigation

```
Link to /cobro/5?from=/dashboard
                    │
                    ▼
        cobro/show.blade.php reads request('from')
                    │
                    ▼
        Back button href = validated URL
         (starts with /, no external scheme)
                    │
                    ▼
        No ?from= → defaults to /cobro
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `public/assets/css/style.css` | Modify | Add `@media (max-width: 991.98px)` body font ≥16px, heading scale at `≤575.98px`, `<main>` top padding, searcher dropdown max-height + z-index |
| `resources/views/layouts/app.blade.php` | Modify | Add `pt-5 pt-md-0` class (or equivalent inline style) to `<main>` for mobile toggle clearance |
| `public/assets/js/app.js` | Modify | No changes needed — `labelTable()` already auto-applies to `.table-card-mobile` at DOMContentLoaded |
| `resources/views/components/pendientes.blade.php` | Modify | Add `table-card-mobile` class to `<table>` |
| `resources/views/components/pendientes-propiedad.blade.php` | Modify | Add `table-card-mobile` class to `<table>` |
| `resources/views/components/transacciones-propiedad.blade.php` | Modify | Add `table-card-mobile` class to `<table>`; add `.d-none.d-sm-table-cell` to non-essential columns (Deudor, Acreedor, Estado) |
| `resources/views/cobro/show.blade.php` | Modify | Replace hardcoded `/cobro` back button with `?from=`-aware link; add inline script to read `request('from')` |
| `resources/views/administracion/create.blade.php` | Modify | Move `#resumen-wrapper` from inline position to after step 8 (before closing `</div>` of card-body) |
| `resources/views/administracion/partials/step-03-propiedad.blade.php` | Modify | Add text input fallback when arrendador has 0 properties (controlled by `loadPropiedadesPorArrendador`) |
| `resources/views/administracion/partials/step-05-comision.blade.php` | No change | Auto-init handled by JS in `create.blade.php` |
| `resources/views/administracion/partials/step-07-garantia.blade.php` | No change | Auto-init handled by JS in `create.blade.php` |
| `app/Http/Controllers/AdministracionController.php` | Modify | Change `store()` redirect to `/propiedad/ficha/{id}` with session flash message |
| `app/Http/Controllers/BuscadorController.php` | Modify | Line 44: change `'/cliente/' . $item->id` to `'/cliente/ficha/' . $item->id` |
| `routes/web.php` | Modify | Line 53: remove `/cliente/ficha/{id}` coming-soon stub (let `generated.php` route take precedence) |

## Interfaces / Contracts

### Cobro Back Button URL Validation (JS)

```js
function getSafeBackUrl() {
    var params = new URLSearchParams(window.location.search);
    var from = params.get('from');
    if (!from) return '/cobro';
    // Reject external URLs: must start with /
    if (from.charAt(0) !== '/') return '/cobro';
    // Reject protocol-relative and javascript: URLs
    if (from.indexOf('//') === 0 || from.toLowerCase().indexOf('javascript:') === 0) return '/cobro';
    return from;
}
```

### Wizard Auto-Init Hook (inside `jumpOrAdvance`)

```js
// Inside jumpOrAdvance(), after step advancement:
if (wizard.step === 5 && !wizard.sin_administracion) {
    var renta = parseInt(document.querySelector('[name="renta"]')?.value) || 0;
    var comision = document.querySelector('[name="comision_inicial"]');
    if (comision && !comision.value && renta > 0) {
        comision.value = Math.floor(renta / 2);
    }
}
if (wizard.step === 7 && !wizard.sin_administracion) {
    var renta = document.querySelector('[name="renta"]')?.value || '';
    var garantia = document.getElementById('garantiaInput');
    if (garantia && !garantia.value && renta) {
        garantia.value = renta;
    }
}
```

### Wizard Success Redirect (Controller)

**Chosen approach**: Change `store()` to redirect directly to `/propiedad/ficha/{id}` with a session flash message (e.g., `with('success', 'Administración creada')`). No confirmation modal, no delay — the user reads the success message on the ficha page itself.

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Manual | Body font ≥16px at 768px viewport | Chrome DevTools device emulation, inspect computed styles |
| Manual | Headings scale at 375px | Same, verify h1-h6 sizes ≥14px |
| Manual | Page title not covered by toggle | Visual check at 375px |
| Manual | Ficha tables as cards at 375px | Navigate to `/propiedad/ficha/{id}`, verify no horizontal scroll |
| Manual | Index tables still scroll | Navigate to `/cliente`, verify horizontal scroll preserved |
| Manual | Buscador cliente links → ficha | Search for client, click result, verify URL is `/cliente/ficha/{id}` |
| Manual | Step 3 no-properties text input | Select arrendador with 0 properties, verify text input shown |
| Manual | Step 5 commission auto-fill | Set renta=500000, advance to step 5, verify comision=250000 |
| Manual | Step 5 commission NOT set when sin_admin | Check sin_administracion, verify step 5 skipped |
| Manual | Step 7 guarantee auto-fill | Set renta=500000, advance to step 7, verify garantia=500000 |
| Manual | Summary below form steps | Visual check — resumen appears after all step content |
| Manual | Searcher dropdown scroll on mobile | Type in searcher at 375px, verify dropdown scrolls, no overlap |
| Manual | Success redirect | Submit wizard, verify immediate redirect to propiedad ficha with flash message |
| Manual | Cobro back button with `?from=` | Navigate `/cobro/5?from=/dashboard`, click back, verify `/dashboard` |
| Manual | Cobro back button without `?from=` | Navigate `/cobro/5`, click back, verify `/cobro` |
| Manual | External `?from=` rejected | Navigate `/cobro/5?from=https://evil.com`, click back, verify `/cobro` |

## Migration / Rollout

No migration required. All changes are CSS/template/JS with no schema changes. Each area is independently revertible:

- **CSS**: Revert media query additions in `style.css`
- **Tables**: Remove `table-card-mobile` class from component templates
- **Wizard**: Revert step partials and `jumpOrAdvance` hooks; revert controller redirect
- **Links**: Revert `BuscadorController` URL; restore `web.php` stub
- **Cobro back**: Revert to hardcoded `/cobro` link

## Open Questions

- [x] **Verified**: `generated.php` defines `/cliente/ficha/{id}` → `FichaClienteController@show` (name: `fichacliente.show`). The `web.php` stub at line 53 overwrites it because `web.php` requires `generated.php` at line 23, then defines its own route later. **Fix**: remove line 53 from `web.php`.
- [x] **Resolved**: Success redirect is immediate (no modal), with flash message shown on the target ficha page. The controller resolves `propiedad_id` from the created contrato via `contrato->propiedad_id`.
