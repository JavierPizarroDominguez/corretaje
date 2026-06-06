# Tasks: Admin Wizard Search UI Fix

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 10–15 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Fix both UI bugs in admin wizard | PR 1 | CSS fix + HTML duplicate removal; self-contained |

---

## Phase 1: Mobile CSS Fix (style.css)

- [ ] 1.1 Add `@media (max-width: 767px)` rule in `public/assets/css/style.css` after existing `SEARCHER DROPDOWN MOBILE FIX` section (~line 551)
- [ ] 1.2 Rule must target `#lista-arrendador .text-muted` and `#lista-arrendatario .text-muted` with `display: none`
- [ ] 1.3 Verify rule does NOT affect other buscador instances (deudor, acreedor, propietario)

## Phase 2: Desktop Duplicate Removal (create.blade.php)

- [ ] 2.1 Remove duplicate navigation `<div>` block (lines 155–161) from `resources/views/administracion/create.blade.php`
- [ ] 2.2 Remove duplicate resumen panel `<div>` block (lines 163–214) from `resources/views/administracion/create.blade.php`
- [ ] 2.3 Verify exactly ONE "Anterior" button and ONE resumen panel remains in DOM
- [ ] 2.4 Verify no duplicate `id="resumen-wrapper"` or `id="resumen-administracion"` after removal

## Phase 3: Manual Verification

- [ ] 3.1 Mobile (≤767px): Type non-matching text in arrendador buscador → "No se encontraron resultados" hidden, "Añadir" button clickable
- [ ] 3.2 Mobile (≤767px): Same test for arrendatario buscador
- [ ] 3.3 Desktop (>767px): "No se encontraron resultados" visible (unchanged behavior)
- [ ] 3.4 Other buscadores: Search in deudor, acreedor, propietario → no-results message still displays normally
- [ ] 3.5 Desktop layout: Exactly one "Anterior" button and one resumen panel visible
- [ ] 3.6 Wizard navigation: Complete steps 1→2→3→4 → no JS errors, navigation works correctly

---

**Dependencies**: Phase 2 does not depend on Phase 1. Both are independent fixes.
**Testing**: Manual verification only (no automated tests required for UI/CSS changes).