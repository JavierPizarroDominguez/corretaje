# Proposal: Mobile UI/UX Overhaul

## Intent

The app is nearly unusable on phones: fonts are too small (14px body, no mobile override), the mobile toggle button covers page titles, detail-page tables require horizontal scroll, the admin wizard has overlapping searchers and missing auto-features, and cobro back navigation is broken. This change makes the app mobile-first usable without redesigning the desktop layout.

## Scope

### In Scope

- **M1**: Increase body font size on ≤991.98px viewports; scale headings on ≤575.98px
- **M2**: Add top padding to `<main>` below the fixed mobile toggle button
- **M3**: Convert ficha detail tables to vertical card layout on mobile using existing `.table-card-mobile` + `labelTable()`; index tables keep horizontal scroll
- **M4**: Fix `BuscadorController` cliente links to point to `/cliente/ficha/{id}` instead of `/cliente/{id}`; resolve route conflict with the coming-soon stub
- **M5**: Fix admin wizard searcher dropdowns overlapping action buttons on mobile (max-height + scroll, z-index)
- **M6**: Show text input directly when arrendador has no properties in step 3
- **M7**: Auto-initialize commission (step 5) to `renta / 2`
- **M8**: Move `#resumen-wrapper` below the form steps
- **M9**: Auto-initialize guarantee (step 7) to `renta` value
- **M10**: After admin wizard success, redirect immediately to `/propiedad/ficha/{propiedad_id}` with flash message (no confirmation modal)
- **M11**: Smart back button on cobro detail using `?from=` query parameter

### Out of Scope

- Desktop layout redesign or sidebar rework
- New CSS framework or component library adoption
- Index table responsive redesign (they keep horizontal scroll)
- PWA or offline support
- Server-side pagination for ficha tables

## Capabilities

### New Capabilities

- `mobile-responsive-layout`: Font sizes, content padding, toggle overlap fix — CSS media queries + minor layout template changes
- `mobile-table-cards`: Ficha detail tables → vertical card layout on mobile using `.table-card-mobile` and `labelTable()`
- `mobile-cobro-navigation`: `?from=`-based smart back button for cobro detail

### Modified Capabilities

- `administracion-wizard`: Searcher mobile overlap fix (M5), no-properties text input (M6), commission auto-init (M7), summary position (M8), guarantee auto-init (M9), success redirect to property ficha (M10)
- `buscador`: Change cliente result URLs from `/cliente/{id}` to `/cliente/ficha/{id}` (M4)

## Approach

Hybrid CSS-first + targeted template changes. Leverage existing `.table-card-mobile` CSS and `labelTable()` JS — add the class to ficha tables and call `labelTable()` on them. CSS rules handle font scaling and toggle overlap. Wizard changes are isolated JS tweaks. Back button uses `?from=` parameter for reliability over `history.back()`.

Implementation order: (1) CSS — font, padding, toggle; (2) Template — add `.table-card-mobile` to ficha tables; (3) Wizard — steps 3/5/7 auto-values, searcher, summary; (4) Links + redirect + back button.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `public/assets/css/style.css` | Modified | Font overrides, `.main` padding, table-card refinements |
| `resources/views/layouts/app.blade.php` | Modified | Mobile padding on `<main>` |
| `public/assets/js/app.js` | Modified | Auto-apply `labelTable()` to `.table-card-mobile` tables |
| `resources/views/propiedad.blade.php` | Modified | Add `.table-card-mobile`, `data-label` attrs |
| `resources/views/cliente.blade.php` | Modified | Add `.table-card-mobile`, `data-label` attrs |
| `resources/views/cobro/show.blade.php` | Modified | Smart back button with `?from=` |
| `resources/views/administracion/create.blade.php` | Modified | Summary position, searcher overlap, success redirect |
| `resources/views/administracion/partials/step-*.blade.php` | Modified | Steps 3, 5, 7 logic changes |
| `app/Http/Controllers/AdministracionController.php` | Modified | Success redirect to `propiedad.ficha` |
| `app/Http/Controllers/BuscadorController.php` | Modified | Cliente URL → ficha route |
| `routes/web.php` | Modified | Remove `/cliente/ficha/{id}` coming-soon stub |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Wide ficha tables (12+ cols) produce very tall cards | Medium | Hide non-essential columns on mobile via `.d-none.d-sm-table-cell` |
| `history.back()` unreliable | Low | Use explicit `?from=` parameter instead |
| Route conflict: `web.php` coming-soon vs `generated.php` controller | Medium | Remove stub from `web.php`; verify `generated.php` route takes precedence |
| Success redirect: user may miss flash message | Low | Immediate redirect with flash message; user reads ficha page for confirmation |
| Commission/guarantee auto-fill with skip logic | Medium | Only set default when step is visible (administracion=true) |

## Rollback Plan

All changes are CSS/template/JS with no schema migrations. Rollback per area:
- **CSS**: Revert mobile media queries in `style.css`
- **Tables**: Remove `.table-card-mobile` class from ficha templates
- **Wizard**: Revert step partials to original JS; revert controller redirect
- **Links**: Revert `BuscadorController` URL change; restore `web.php` stub
- **Cobro back**: Revert to hardcoded `/cobro` link

Each area is independently revertible with no cross-dependencies.

## Dependencies

- Existing `.table-card-mobile` CSS (lines 449–487 in `style.css`) and `labelTable()` JS (lines 117–143 in `app.js`) must be intact
- `FichaClienteController@show` route must be active (remove `web.php` coming-soon override)
- `flashModal` infrastructure in `layouts/app.blade.php`

## Success Criteria

- [ ] Body text ≥16px on ≤991.98px viewports
- [ ] Page titles fully visible on mobile (not covered by toggle button)
- [ ] Ficha detail tables render as vertical cards on ≤991.98px — no horizontal scroll
- [ ] Index tables still scroll horizontally on mobile
- [ ] Buscador cliente links navigate to `/cliente/ficha/{id}`
- [ ] Admin wizard step 3 shows text input when arrendador has no properties
- [ ] Step 5 auto-fills commission = renta / 2
- [ ] Step 7 auto-fills guarantee = renta
- [ ] Summary panel appears below form steps
- [ ] Searcher dropdowns don't overlap action buttons on mobile
- [ ] After wizard success, redirects immediately to property ficha with flash message
- [ ] Cobro detail back button returns to previous view via `?from=` parameter