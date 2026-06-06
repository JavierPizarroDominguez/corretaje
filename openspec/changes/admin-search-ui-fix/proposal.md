# Proposal: Admin Wizard Search UI Fix

## Intent

Fix two UI bugs in `administracion/create.blade.php`:
1. **Mobile**: The buscador's "No se encontraron resultados" dropdown overlays and blocks the "Añadir" button on steps 1 and 2, preventing the user from proceeding.
2. **Desktop**: The "Anterior" navigation button and the resumen panel are duplicated in the wizard layout, making the page look broken.

## Scope

### In Scope
- Hide the "No se encontraron resultados" dropdown message on mobile viewports for the arrendador (step 1) and arrendatario (step 2) buscader instances only
- Remove the duplicate navigation block (lines 155-161) and duplicate resumen panel (lines 163-214) from `create.blade.php`
- Ensure the wizard's `position-absolute` + `z-index:1000` dropdown does not block interactive elements below it on mobile

### Out of Scope
- Modifying `buscador.js` globally — the no-results message must remain functional for other buscader instances (deudor, acreedor, etc.)
- Changing the buscader dropdown behavior on desktop — only mobile viewport is affected by the overlay issue
- Redesigning the wizard layout or navigation structure
- Adding new features or changing wizard step flow

## Capabilities

### New Capabilities
None.

### Modified Capabilities
- `administracion-wizard`: Wizard renders without duplicate navigation/resumen blocks; buscador no-results message is hidden on mobile to avoid blocking the "Añadir" button.

## Approach

**Bug 1 — Mobile buscador overlay**: Add a scoped CSS rule in `public/assets/css/style.css` that hides `#lista-arrendador` and `#lista-arrendatario` no-results items on mobile (`@media (max-width: 767px)`). The selector targets the specific wizard dropdown IDs so other buscader instances are unaffected. When `buscador.js` sets `msg.textContent = 'No se encontraron resultados.'` and appends it as a `<div class="list-group-item text-muted fst-italic">`, the CSS rule will suppress it on mobile only. On desktop, the dropdown works normally.

Approach: `@media (max-width: 767px) { #lista-arrendador .text-muted, #lista-arrendatario .text-muted { display: none; } }` — this hides the no-results row on mobile while keeping the dropdown functional when actual results exist.

**Bug 2 — Duplicate blocks**: Remove lines 155-214 from `create.blade.php` (the second navigation `<div>` and the second resumen `<div>`), keeping only the original first occurrence (lines 95-153). This is a straightforward HTML deduplication.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `resources/views/administracion/create.blade.php` | Modified | Remove duplicate navigation + resumen blocks (lines 155-214) |
| `resources/views/administracion/partials/step-01-arrendador.blade.php` | Unchanged | No template changes; CSS handles the fix |
| `resources/views/administracion/partials/step-02-arrendatario.blade.php` | Unchanged | No template changes; CSS handles the fix |
| `public/assets/css/style.css` | Modified | Add scoped mobile CSS to hide no-results in wizard buscader dropdowns |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| CSS selector is too broad and hides other text-muted items | Low | Selector targets specific IDs `#lista-arrendador` and `#lista-arrendatario`, not the global `.text-muted` class |
| Removing duplicate blocks breaks Alpine.js x-show or x-data references | Low | Both blocks are plain HTML with no Alpine directives; the duplicates are exact copies of lines 95-153 |
| Mobile users no longer see any feedback when search yields no results | Med | The dropdown still opens and closes; on mobile, an empty dropdown is preferable to one that blocks the primary CTA. Could add alternative feedback (e.g., brief border flash) in a future iteration |

## Rollback Plan

1. Revert the CSS deletion from `style.css`
2. Re-add the duplicate HTML blocks to `create.blade.php` (git revert)
3. Both changes are isolated and independent — partial rollback is safe

## Dependencies

- None beyond the existing codebase (Bootstrap 5.3, Alpine.js, buscador.js)

## Success Criteria

- [ ] On mobile (viewport ≤767px), typing in the arrendador/arrendatario buscader and getting no results does NOT block the "Añadir" button
- [ ] On desktop, the buscader no-results message still displays normally
- [ ] The wizard shows exactly ONE "Anterior" navigation block on desktop
- [ ] The wizard shows exactly ONE resumen panel on desktop
- [ ] Other buscader instances (deudor, acreedor, etc.) are unaffected