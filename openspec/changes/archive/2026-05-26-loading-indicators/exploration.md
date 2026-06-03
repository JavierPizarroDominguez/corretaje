# Exploration: Loading Indicators

## Current State

The app is a Laravel 10 real estate brokerage management system ("corretaje") built with Blade + Bootstrap 5.3. It uses **server-side rendering** for index pages (tables rendered by Blade with `@foreach`) and **client-side `fetch()`** for specific AJAX operations.

### Existing Loading Indicator

**Only ONE loading indicator exists in the entire codebase** — in `filtros.js` (line 65-67):
```js
tableBody.innerHTML = '<tr><td colspan="99" class="text-center py-4">' +
    '<div class="spinner-border spinner-border-sm text-secondary" role="status"></div>' +
    ' Buscando...</td></tr>';
```
This displays a Bootstrap `spinner-border` inside the table body when AJAX filters are active. It's inline JS, not reusable.

### No Other Loading States Exist

Every other data-fetching operation shows **zero visual feedback** while loading:
- Dashboard `cargarPendientes()` — fetches pendientes, clears `tbody`, renders rows. No spinner.
- Dashboard buscador — fetch search results. No loading indicator.
- `buscador.js` — autocomplete search. No loading indicator.
- Administración wizard `loadPropiedadesPorArrendador()` — only shows "Cargando..." as select option text.
- Cobro modal `resolveCobroRelationships()` — no loading state.
- All index pages (cliente, contrato, cobro, transaccion, etc.) — server-rendered, NO initial page load indicator.

### Architecture Pattern

- **Layout**: `layouts/app.blade.php` — shared layout with sidebar, overlay div, global modal
- **Index pages**: Follow a consistent pattern — `@extends('layouts.app')`, table with `@foreach`, `filtros.js` for AJAX filtering, pagination
- **JS files**: `public/js/buscador.js`, `public/js/filtros.js`, `public/js/editarCampo.js`, `public/js/alertas.js`, `public/assets/js/app.js`
- **CSS**: `public/assets/css/style.css` — custom theme over Bootstrap 5.3

## Affected Areas

### High Priority — Tables with many rows (user's main concern)

| View | Pattern | Loading Indicator? |
|------|---------|-------------------|
| `dashboard/index.blade.php` | JS `fetch()` → renders rows dynamically | **NO** |
| `cliente/index.blade.php` | Server-rendered + AJAX filter | Partial (filtros.js only for filter) |
| `contrato/index.blade.php` | Server-rendered + AJAX filter | Partial (filtros.js only for filter) |
| `cobro/index.blade.php` | Server-rendered + AJAX filter | Partial (filtros.js only for filter) |
| `transaccion/index.blade.php` | Server-rendered + AJAX filter | Partial (filtros.js only for filter) |
| `participante_cobro/index.blade.php` | Server-rendered + AJAX filter | Partial (filtros.js only for filter) |
| `participante_contrato/index.blade.php` | Server-rendered, no filter system | **NO** |

### Medium Priority — Interactive fetch() calls without feedback

| View/JS | Operation | Loading Indicator? |
|---------|-----------|-------------------|
| `dashboard/index.blade.php` | `cargarPendientes()` — initial + pagination | **NO** |
| `dashboard/index.blade.php` | Buscador autocomplete | **NO** |
| `dashboard/index.blade.php` | `registrarPago()` — payment | **NO** |
| `buscador.js` | All autocomplete searches | **NO** |
| `administracion/create.blade.php` | `loadPropiedadesPorArrendador()` | Minimal ("Cargando..." text) |
| `cobro/modal/create.blade.php` | `resolveCobroRelationships()` | **NO** |

### Low Priority — Server-rendered detail/show pages

These load data server-side; the user sees a blank page until Laravel renders. A page-level loading overlay could help perception.

| View | Type |
|------|------|
| `cliente/show.blade.php` | Server-rendered, no fetch |
| `contrato/show.blade.php` | Server-rendered, no fetch |
| `cobro/show.blade.php` | Server-rendered, no fetch |
| `transaccion/show.blade.php` | Server-rendered, no fetch |

## Approaches

### 1. Per-View Inline Spinners (Current Pattern, Extended)
- Add `spinner-border` or "Cargando..." to each view's JS `fetch()` calls
- Reuse Bootstrap's existing `spinner-border` class
- **Pros**: Minimal change, follows existing pattern, no global JS needed
- **Cons**: Repeated code, easy to miss spots, inconsistent UX if someone forgets
- **Effort**: Low

### 2. Global Loading Overlay Component + Per-View tbody Spinners
- Create a reusable `showLoading(element)` / `hideLoading(element)` JS utility in `app.js`
- Add a global page-load overlay in `layouts/app.blade.php` that shows spinner until content renders
- Add tbody spinners for all index tables (both initial load and AJAX)
- Add spinner states for all `fetch()` calls in dashboard and buscador
- **Pros**: Consistent UX, single source of truth, covers both page-load and AJAX
- **Cons**: More initial work, needs careful CSS for overlay
- **Effort**: Medium

### 3. Skeleton Loading (CSS-only placeholder animation)
- Add CSS skeleton shimmer animation to `style.css`
- Show skeleton rows while data loads, replace with real data
- Best for perceived performance on index pages
- **Pros**: Modern UX, perceived speed improvement, no JS dependency for the visual
- **Cons**: More complex HTML structure, harder to retrofit into existing `@foreach` tables
- **Effort**: Medium-High

## Recommendation

**Approach 2** — Global Loading Overlay + Per-View tbody Spinners.

Rationale:
1. The app already uses Bootstrap 5.3 `spinner-border` in `filtros.js`. Consistency favors continuing with Bootstrap's built-in spinner system.
2. A small utility in `app.js` (`showElLoading/ hideElLoading`) can wrap the repeated "show spinner → fetch → hide spinner" pattern.
3. For the **user's primary complaint** (tables with lots of data), the most impactful thing is: (a) a `spinner-border` row in `tbody` while AJAX filters re-fetch, and (b) a page-level indicator for initial server-rendered loads.
4. The dashboard's `cargarPendientes()` is the most visible gap — it clears the `tbody` and renders nothing while waiting.

### Specific Implementation Scope

| What | File | Change |
|------|------|--------|
| Loading utility | `public/assets/js/app.js` | Add `showElLoading(el, colspan)` and `hideElLoading(el)` functions |
| Dashboard pendientes | `dashboard/index.blade.php` | Show spinner in `#body-pendientes` before `fetch`, hide on render |
| Dashboard buscador | `dashboard/index.blade.php` | Show spinner in `#autocomplete-list` while fetching |
| Global page load | `layouts/app.blade.php` | Add `<div id="page-loader">` hidden after content renders via JS |
| Index pages (filter) | `filtros.js` | Already has spinner — **already done** ✓ |
| Index pages (initial) | All index blade files | Add `id="table-[entity]-body"` and initial spinner row if converting to AJAX load |
| Admin wizard propiedad | `administracion/create.blade.php` | Replace "Cargando..." text with proper spinner |
| Cobro modal resolve | `cobro/modal/create.blade.php` | Add spinner while `resolveCobroRelationships` runs |
| CSS | `style.css` | Add `.table-loading-overlay` styles if needed |

### Priority Order (by user impact)

1. **Dashboard pendientes table** — most visible, loads on every page visit, frequently has many rows
2. **All AJAX fetch() calls without spinners** — `cargarPendientes`, buscador, `registrarPago`, `resolveCobroRelationships`
3. **Index page initial load** — if tables have many rows, a brief "Cargando..." row in tbody before server renders helps perceived performance
4. **Admin wizard** — менее urgente но pendiente completitud

## Risks

- **Page-load spinner for server-rendered content**: Since Laravel renders HTML server-side, adding a "loading" state requires either (a) showing it briefly until `DOMContentLoaded` fires (which is almost instant for cached pages), or (b) converting tables to AJAX-loaded (bigger refactor). Option (a) gives minimal benefit; option (b) is a bigger change.
- **AJAX tables refactoring**: Converting index tables from server-rendered Blade `@foreach` to AJAX-loaded data would be a significant architectural change. The current pattern (server-render with AJAX filter) works well. The only real gap is the **initial page load** — but Laravel renders before sending, so the user never sees an empty table.
- **Over-engineering**: Adding spinners everywhere could clutter the UI. The `filtros.js` pattern (spinner row in tbody during filter) is good. Don't overbuild.

## Ready for Proposal

**Yes.** The scope is clear and the user's concern is well-defined: visual confirmation when data is loading, especially in tables with many rows. The main gaps are:
1. Dashboard `cargarPendientes()` — completely lacks loading feedback
2. Buscador autocomplete — no loading indicator
3. `resolveCobroRelationships()` — no loading indicator
4. The filtros.js pattern works well and should be the template for other JS fetch calls

The orchestrator should ask the user if they want to also convert index pages to AJAX-loaded tables (removing Blade `@foreach`), or keep them server-rendered and only add spinners to the client-side fetch operations.