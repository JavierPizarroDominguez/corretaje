# Design: Admin Wizard Search UI Fix

## Technical Approach

Two independent fixes in `administracion/create.blade.php` and `public/assets/css/style.css`:

1. **Mobile buscador overlay**: Add a scoped `@media (max-width: 767px)` CSS rule targeting `#lista-arrendador .text-muted` and `#lista-arrendatario .text-muted` with `display: none`. This hides the "No se encontraron resultados" row produced by `buscador.js` (line 67-70) on mobile viewports, preventing the dropdown from blocking the "Añadir" button.

2. **Duplicate HTML blocks**: Remove the second occurrence of the navigation `<div>` (lines 155-161) and resumen `<div>` (lines 163-214) from `create.blade.php`, keeping only the first occurrences at lines 95-153.

## Architecture Decisions

| Decision | Option A (chosen) | Option B (rejected) | Rationale |
|----------|-------------------|---------------------|-----------|
| Mobile no-results fix | Scoped CSS `@media (max-width: 767px)` targeting wizard-specific IDs | JS global override in `buscador.js` or inline style injection | CSS is zero-runtime, scoped to IDs so other buscador instances (deudor, acreedor, etc.) remain unaffected. JS approach would require modifying shared `buscador.js` and risk regressions across all search UIs |
| Breakpoint | `767px` (mobile-only) | `991.98px` (matches existing sidebar breakpoint) | The overlay issue only occurs on narrow viewports where the "Añadir" button sits directly below the input. Desktop has enough vertical space. Using 767px is more precise |
| Duplicate removal | Delete lines 155-214 (second occurrence) | Delete lines 95-153 (first occurrence) | Both blocks are identical in structure; removing the later one preserves natural document flow and keeps the navigation/resumen closer to the step content |

## Data Flow

No data flow changes. Both fixes are purely presentational:

```
User types in buscador input
    → buscador.js fetches /buscador?q=...
    → If no results: creates <div class="list-group-item text-muted fst-italic">
    → CSS rule @media (max-width: 767px) hides this element in wizard dropdowns only
    → Dropdown remains functional (opens/closes normally, just no no-results text on mobile)
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `public/assets/css/style.css` | Modify | Add scoped mobile CSS rule after existing `SEARCHER DROPDOWN MOBILE FIX` section (~line 551) |
| `resources/views/administracion/create.blade.php` | Modify | Remove duplicate navigation block (lines 155-161) and duplicate resumen panel (lines 163-214) |

## Interfaces / Contracts

### CSS Contract (new rule in `style.css`)

```css
/* ========================================
   ADMIN WIZARD BUSCADOR — mobile no-results hide
   Prevents "No se encontraron resultados" dropdown from
   blocking the "Añadir" button on steps 1 and 2.
   Scoped to wizard-specific IDs so other buscador instances
   (deudor, acreedor, propietario) are unaffected.
   ======================================== */
@media (max-width: 767px) {
  #lista-arrendador .text-muted,
  #lista-arrendatario .text-muted {
    display: none;
  }
}
```

### HTML Contract (create.blade.php)

After removal, the file structure inside `<div class="card-body">` becomes:

```
card-body
├── step title + subtitle (x-text)
├── step 1-8 containers (x-show)
├── navigation (lines 95-100) — SINGLE occurrence
├── resumen panel (lines 103-153) — SINGLE occurrence
└── (no duplicate blocks)
```

No `id` conflicts remain (duplicate `id="resumen-wrapper"` and `id="resumen-administracion"` are removed).

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Manual — Mobile | Type non-matching text in arrendador/arrendatario buscador on viewport ≤767px | Verify "No se encontraron resultados" is NOT visible; "Añadir" button is clickable; dropdown still opens/closes |
| Manual — Desktop | Same search on viewport >767px | Verify "No se encontraron resultados" IS visible (normal behavior) |
| Manual — Other buscadores | Search in deudor, acreedor, propietario buscadores (any viewport) | Verify no-results message still displays — CSS selector is scoped to wizard IDs only |
| Manual — Desktop layout | Open create page on desktop viewport | Verify exactly ONE "Anterior" button and ONE resumen panel visible |
| Manual — Duplicate IDs | Inspect DOM after page load | Verify no duplicate `id="resumen-wrapper"` or `id="resumen-administracion"` elements |
| Manual — Wizard flow | Complete steps 1→2→3→4 on mobile and desktop | Verify navigation and resumen update correctly without JS errors |

## Migration / Rollout

No migration required. Both changes are static asset modifications (CSS + HTML template). Deployment is immediate on next asset pipeline run.

## Open Questions

- [ ] None
