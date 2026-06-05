# Apply Progress: mobile-ui-ux-overhaul

**Status**: All 18 tasks completed ✅

**Mode**: Standard (no Strict TDD)

**Date**: 2026-06-05

---

## Completed Tasks

### Phase 1: Foundation / CSS (M1, M2, M5)

- [x] **1.1** Body font ≥16px at ≤991.98px — added `@media (max-width: 991.98px)` in `style.css`
- [x] **1.2** Heading scale ≥14px at ≤575.98px — added `@media (max-width: 575.98px)` h1-h6 in `style.css`
- [x] **1.3** Mobile padding on `<main>` — added `pt-5 pt-md-0` class in `layouts/app.blade.php`
- [x] **1.4** Searcher dropdown mobile CSS — added max-height 280px + overflow-y + z-index 1050 in `style.css`

### Phase 2: Table Card Markup (M3)

- [x] **2.1** `table-card-mobile` on `components/pendientes-propiedad.blade.php` table (used by `propiedad.blade.php`)
- [x] **2.2** `table-card-mobile` on `components/pendientes.blade.php` table (used by `cliente.blade.php`)
- [x] **2.3** `table-card-mobile` on `components/pendientes.blade.php` table
- [x] **2.4** `table-card-mobile` on `components/pendientes-propiedad.blade.php` table
- [x] **2.5** `table-card-mobile` + `.d-none.d-sm-table-cell` on Deudor/Acreedor/Estado in `components/transacciones-propiedad.blade.php`
- [x] **2.6** No JS changes needed — `labelTable()` in `app.js` auto-applies to `.table-card-mobile` at DOMContentLoaded

### Phase 3: Route + Controller Fixes (M4)

- [x] **3.1** `BuscadorController.php` line 46: cliente URL changed from `/cliente/{id}` → `/cliente/ficha/{id}`
- [x] **3.2** Removed line 53 from `routes/web.php` (coming-soon stub that overwrote generated.php route)

### Phase 4: Wizard JS Tweaks (M5, M6, M7, M8, M9, M10)

- [x] **4.1** `loadPropiedadesPorArrendador()`: when `data.length === 0`, shows `#nuevaPropiedadInput` text input directly (returns early, doesn't add disabled option)
- [x] **4.2** `#resumen-wrapper` moved from before step title to after navigation (below step 8) in `create.blade.php`
- [x] **4.3** Commission auto-init: `wizard.step === 5 && !wizard.sin_administracion` → `comision_inicial = Math.floor(renta / 2)` (only if empty)
- [x] **4.4** Guarantee auto-init: `wizard.step === 7 && !wizard.sin_administracion` → `garantia = renta` (only if empty)
- [x] **4.5** `AdministracionController::store()` redirect changed from `contrato.show` → `propiedad.ficha` with flash message

### Phase 5: Cobro Back Button (M11)

- [x] **5.1** `cobro/show.blade.php` back button reads `?from=` param, validates URL safety (must start with `/`, no protocol-relative or javascript:), defaults to `/cobro`

---

## Files Changed

| File | Action | What Was Done |
|------|--------|---------------|
| `public/assets/css/style.css` | Modified | Added mobile typography scaling (991.98px body, 575.98px headings) + searcher dropdown max-height/z-index |
| `resources/views/layouts/app.blade.php` | Modified | Added `pt-5 pt-md-0` to `<main>` for mobile toggle clearance |
| `resources/views/components/pendientes.blade.php` | Modified | Added `table-card-mobile` class to table |
| `resources/views/components/pendientes-propiedad.blade.php` | Modified | Added `table-card-mobile` class to table |
| `resources/views/components/transacciones-propiedad.blade.php` | Modified | Added `table-card-mobile` + `.d-none.d-sm-table-cell` on Deudor/Acreedor/Estado |
| `app/Http/Controllers/BuscadorController.php` | Modified | Changed cliente URL from `/cliente/{id}` → `/cliente/ficha/{id}` |
| `routes/web.php` | Modified | Removed line 53 coming-soon stub for `/cliente/ficha/{id}` |
| `resources/views/administracion/create.blade.php` | Modified | loadPropiedadesPorArrendador no-prop text input, resumen moved below navigation, commission+guarantee auto-init hooks in jumpOrAdvance |
| `app/Http/Controllers/AdministracionController.php` | Modified | Redirect changed to `propiedad.ficha` route |
| `resources/views/cobro/show.blade.php` | Modified | Smart back button with `?from=` JS validation |

---

## Deviations from Design

None — implementation matches design for all 11 modifiers.

**Note on M3 (table-card-mobile)**: The tasks specified adding `table-card-mobile` to `propiedad.blade.php` and `cliente.blade.php` directly, but these files don't contain `<table>` elements — they include component partials. The class was correctly added to the component tables (`components/pendientes.blade.php`, `components/pendientes-propiedad.blade.php`, `components/transacciones-propiedad.blade.php`) which are used in the ficha detail views.

---

## Issues Found

1. **create.blade.php structure corruption during edit**: When moving `#resumen-wrapper`, the file had duplicate content after `@endsection` (step title + steps 1-8 were duplicated outside the card-body). Cleaned up by removing the duplicate block and reinserting the correct structure with step title, steps 1-8, navigation, and resumen-wrapper in the proper order.

---

## Workload / PR Boundary

- **Mode**: Single PR
- **Estimated changed lines**: ~200 (under 400-line budget)
- **Chained PRs**: No
- **Review budget**: ~200 lines, well within budget

---

## Next Steps

- Ready for `sdd-verify` phase to validate implementation against specs
- Manual testing scenarios are documented in `openspec/changes/mobile-ui-ux-overhaul/tasks.md` (lines 75-95)

---

## Verification Status

All 18 tasks marked `[x]` complete in `openspec/changes/mobile-ui-ux-overhaul/tasks.md`.
Engram progress saved to `sdd/mobile-ui-ux-overhaul/apply-progress`.