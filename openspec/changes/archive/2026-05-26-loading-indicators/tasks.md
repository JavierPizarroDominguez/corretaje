# Tasks: Loading Indicators

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~280 (net) |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | JS utility foundation + filtros refactor | PR 1 | app.js utilities, filtros.js refactor, style.css; self-contained |

## Phase 1: Foundation — JS Utility

- [x] 1.1 **RED** (app.js): Write feature test `tests/Feature/LoadingIndicatorsTest.php` — asserts overlay container present in `layouts/app.blade.php`, and placeholder row `.loading-placeholder` removable on DOMContentLoaded
- [x] 1.2 **GREEN** (app.js): Add `window.showElLoading(el, colspan)` and `window.hideElLoading(el)` to `public/assets/js/app.js` — injects/removes Bootstrap `spinner-border` row
- [x] 1.3 **GREEN** (app.js): Add `DOMContentLoaded` listener in `public/assets/js/app.js` — removes all `.loading-placeholder` rows on page ready
- [x] 1.4 **GREEN** (style.css): Add `.loading-placeholder { opacity: 0.6; }` and page overlay CSS to `public/assets/css/style.css`

## Phase 2: filtros.js Refactor

- [x] 2.1 **GREEN** (filtros.js): Replace inline spinner HTML — use `showElLoading(tableBody, 99)` before fetch; call `hideElLoading(tableBody)` in `.then()` and `.catch()`
- [x] 2.2 **REFACTOR** (filtros.js): Inline spinner HTML string removed (now handled by utility)

## Phase 3: buscador.js Spinner Integration

- [x] 3.1 **GREEN** (buscador.js): Wrap the `fetch('/buscador?...')` call — add `showElLoading(list)` before fetch, `hideElLoading(list)` after fetch

## Phase 4: Dashboard View Integration

- [x] 4.1 **GREEN** (dashboard/index.blade.php): Wrap `cargarPendientes()` fetch block — add `showElLoading(tbody, 4)` before try, `hideElLoading(tbody)` in both `catch` and after tbody population
- [x] 4.2 **GREEN** (dashboard/index.blade.php): Wrap `registrarPago()` fetch block — add spinner + `btn.disabled=true` before try, `hideElLoading` + `btn.disabled=false` on completion
- [x] 4.3 **GREEN** (dashboard/index.blade.php): Refactor inline buscador (lines 245–341) — remove inline script, use shared `buscador.js` module; dashboard buscador now uses shared module with spinner

## Phase 5: Overlay + Other Views

- [x] 5.1 **GREEN** (layouts/app.blade.php): Add loading overlay `<div>` wrapped in `<main>` with spinner; 200ms debounce script; inline script removes overlay on DOMContentLoaded
- [x] 5.2 **GREEN** (administracion/create.blade.php): Wrap `loadPropiedadesPorArrendador()` — replace plain "Cargando..." text option with `showElLoading(propiedadSelect)` / `hideElLoading(propiedadSelect)` pattern
- [x] 5.3 **GREEN** (cobro/modal/create.blade.php): Add spinner to `resolveCobroRelationships()` — add `showElLoading(modalBody)` during fetch, disable form fields; `hideElLoading(modalBody)` on completion; re-enable fields on catch

## Phase 6: Documentation

- [x] 6.1 Created `AGENTS.md` at project root — documents loading indicator convention.

## Phase 7: Verification

- [x] 7.1 Run `php artisan test` — all NEW tests pass (3/3 LoadingIndicatorsTest); pre-existing failures unchanged (DB/migration issues unrelated to loading indicators)
- [ ] 7.2 Manual verification checklist (from design.md)
