# Tasks: Mobile UI/UX Overhaul

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~200 |
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
| 1 | All changes | PR 1 | All 11 modifiers; CSS, markup, controllers, JS; single review |

## Phase 1: Foundation / CSS (M1, M2, M5)

- [x] 1.1 Add `@media (max-width: 991.98px)` body font-size ≥16px in `public/assets/css/style.css`
- [x] 1.2 Add `@media (max-width: 575.98px)` scaled h1–h6 sizes (≥14px) in `public/assets/css/style.css`
- [x] 1.3 Add mobile padding to `<main>` in `resources/views/layouts/app.blade.php` — add `pt-5 pt-md-0` class so toggle button doesn't cover titles
- [x] 1.4 Add searcher dropdown max-height + overflow-y + z-index CSS for `≤991.98px` in `public/assets/css/style.css`

## Phase 2: Table Card Markup (M3)

- [x] 2.1 Add `table-card-mobile` class to `<table>` in `resources/views/propiedad.blade.php`
- [x] 2.2 Add `table-card-mobile` class to `<table>` in `resources/views/cliente.blade.php`
- [x] 2.3 Add `table-card-mobile` class to `<table>` in `resources/views/components/pendientes.blade.php`
- [x] 2.4 Add `table-card-mobile` class to `<table>` in `resources/views/components/pendientes-propiedad.blade.php`
- [x] 2.5 Add `table-card-mobile` class + `.d-none.d-sm-table-cell` on non-essential columns (Deudor, Acreedor, Estado) to `<table>` in `resources/views/components/transacciones-propiedad.blade.php`
- [x] 2.6 Verify `labelTable()` in `public/assets/js/app.js` (lines 117–143) auto-applies to `.table-card-mobile` tables at DOMContentLoaded — no JS changes needed

## Phase 3: Route + Controller Fixes (M4)

- [x] 3.1 Change `BuscadorController.php` line 46: `'/cliente/' . $item->id` → `'/cliente/ficha/' . $item->id`
- [x] 3.2 Remove line 53 from `routes/web.php`: `Route::get('/cliente/ficha/{id}', fn () => view('coming-soon'))->name('cliente.ficha');`

## Phase 4: Wizard JS Tweaks (M5, M6, M7, M8, M9, M10)

- [x] 4.1 Fix `loadPropiedadesPorArrendador()` in `create.blade.php`: when `data.length === 0`, show `#nuevaPropiedadInput` text input directly instead of adding a disabled option to select
- [x] 4.2 Move `#resumen-wrapper` from inside `.card-body` (before step divs) to after step 8 div (end of `.card-body`) in `resources/views/administracion/create.blade.php`
- [x] 4.3 Add commission auto-init inside `jumpOrAdvance()` in `create.blade.php`: when `wizard.step === 5 && !wizard.sin_administracion`, set `comision_inicial = Math.floor(renta / 2)` only if field is empty
- [x] 4.4 Add guarantee auto-init inside `jumpOrAdvance()` in `create.blade.php`: when `wizard.step === 7 && !wizard.sin_administracion`, set `garantia = renta` only if field is empty
- [x] 4.5 Change `AdministracionController::store()` redirect from `contrato.show` to `propiedad.ficha` with session flash message

## Phase 5: Cobro Back Button (M11)

- [x] 5.1 In `cobro/show.blade.php`, replace hardcoded `href="/cobro"` back link with JS that reads `?from=` param, validates it starts with `/` and rejects external/protocol-relative URLs, defaulting to `/cobro`

## Files to Modify

| File | Changes |
|------|---------|
| `public/assets/css/style.css` | M1, M2, M5: mobile fonts, main padding, searcher dropdown CSS |
| `resources/views/layouts/app.blade.php` | M2: pt-5 pt-md-0 on `<main>` |
| `resources/views/propiedad.blade.php` | M3: add `table-card-mobile` to table |
| `resources/views/cliente.blade.php` | M3: add `table-card-mobile` to table |
| `resources/views/components/pendientes.blade.php` | M3: add `table-card-mobile` to table |
| `resources/views/components/pendientes-propiedad.blade.php` | M3: add `table-card-mobile` to table |
| `resources/views/components/transacciones-propiedad.blade.php` | M3: add `table-card-mobile` + hide Deudor/Acreedor/Estado on mobile |
| `app/Http/Controllers/BuscadorController.php` | M4: `/cliente/` → `/cliente/ficha/` |
| `routes/web.php` | M4: remove line 53 coming-soon stub |
| `resources/views/administracion/create.blade.php` | M5, M6, M7, M8, M9, M10: dropdown fix, no-prop text input, summary move, auto-init hooks, redirect |
| `app/Http/Controllers/AdministracionController.php` | M10: redirect to `propiedad.ficha` |
| `resources/views/cobro/show.blade.php` | M11: smart back button with `?from=` |

## Test Scenarios

| Task | Scenario |
|------|----------|
| 1.1 | Viewport 768px — body font ≥16px |
| 1.2 | Viewport 375px — h1–h6 ≥14px |
| 1.3 | Viewport 375px — page title not covered by toggle |
| 1.4 | Viewport 375px, searcher open — dropdown scrolls, no overlap with buttons |
| 2.1–2.5 | Viewport 375px on ficha — tables render as cards, no horizontal scroll |
| 2.1–2.5 | Viewport 375px on index — tables still scroll horizontally |
| 3.1 | Search cliente in buscador, click result — navigates to `/cliente/ficha/{id}` |
| 4.1 | Select arrendador with 0 properties — text input shown |
| 4.2 | Any wizard step — `#resumen-wrapper` appears below step 8 |
| 4.3 | renta=500000, advance to step 5 — comision_inicial = 250000 |
| 4.3 | Check `sin_administracion` — step 5 skipped, no auto-init |
| 4.4 | renta=500000, advance to step 7 — garantia = 500000 |
| 4.4 | Check `sin_administracion` — step 7 skipped, no auto-init |
| 4.5 | Submit wizard — redirect to `/propiedad/ficha/{id}` with flash message |
| 5.1 | Navigate `/cobro/5?from=/dashboard` — back button → `/dashboard` |
| 5.1 | Navigate `/cobro/5` — back button → `/cobro` |
| 5.1 | Navigate `/cobro/5?from=https://evil.com` — back button → `/cobro` |