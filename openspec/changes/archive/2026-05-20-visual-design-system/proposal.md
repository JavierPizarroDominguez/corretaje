# Proposal: Visual Design System

## Intent

The app looks flat and inconsistent — inputs lack Bootstrap classes, buttons are unstyled, the sidebar has no icons, and there's zero visual depth. The root cause is in the stubs (used by `gen:crud` to generate ALL Blade views) plus missing CSS and a bare sidebar. Fix the source of truth so all future views are correct.

## Scope

### In Scope
- Fix 6 stubs with proper Bootstrap classes (`form-control`, `form-label`, `mb-3`, `btn`, card wrappers)
- Replace broken Bootstrap Icons with Tabler Icons in 2 stubs
- Add shadow/depth system to CSS (3 levels), orange focus ring, button hover effects
- Add Tabler icons to sidebar nav links, fix duplicate `active` class

### Out of Scope
- Regenerating existing views from fixed stubs (deferred — user decision)
- Fixing the `<input type="select">` bug in edit view generator (pre-existing)
- Adding Bootstrap Icons CDN (moving to Tabler-only consistency)
- New feature capabilities or behavioral changes

## Capabilities

> No existing specs at `openspec/specs/`. This change is purely visual — no behavioral requirements change.

### New Capabilities
None — visual styling is not a spec-level capability.

### Modified Capabilities
None — no existing capabilities have requirement changes.

## Approach

**Approach A: Fix stubs + CSS + sidebar** (from exploration). The stubs are the source of truth for all generated views. CSS improvements apply globally immediately. Sidebar is the only hand-written view. No regeneration of existing views in this change — that's a separate decision.

1. **Stubs** (6 files): Add `form-control`, `form-label`, `mb-3`, `text-danger` to `create-field.stub` and `edit-field.stub`. Add card wrapper + `btn` classes to `view-create.stub` and `view-edit.stub`. Replace `bi-*` icons with `ti-*` in `view-index.stub` and `view-filter.stub`.
2. **CSS** (`style.css`): Add `--shadow-sm/md/lg`, card depth, custom orange focus ring, button transitions.
3. **Sidebar**: Add Tabler icons, fix duplicate `active`.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `stubs/fragments/create-field.stub` | Modified | Add `form-control`, `form-label`, `mb-3`, `text-danger` |
| `stubs/fragments/edit-field.stub` | Modified | Same classes as create |
| `stubs/view-create.stub` | Modified | Card wrapper, styled buttons |
| `stubs/view-edit.stub` | Modified | Card wrapper, styled buttons |
| `stubs/view-index.stub` | Modified | Replace `bi bi-funnel` → `ti ti-filter` |
| `stubs/view-filter.stub` | Modified | Replace `bi-*` with `ti-*` (2 icons) |
| `public/assets/css/style.css` | Modified | Add shadow system, focus ring, button effects |
| `resources/views/layouts/partials/sidebar.blade.php` | Modified | Add Tabler icons, fix active class |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Missing Bootstrap class name in stub (typo) | Low | Review each stub after edit; views won't compile if syntax breaks |
| Sidebar nav links use wrong Tabler icon name | Low | Verify icon names against Tabler docs |
| CSS shadow conflicts with existing styles | Low | Use low-opacity shadows; test on card, modal, sidebar |

## Rollback Plan

Git revert of modified files. If no git history, manually undo stub changes (they're self-contained additions), remove added CSS block, and restore sidebar to original. No DB or data impact.

## Dependencies

- None — all additions are static files (stubs, CSS, Blade). No packages, no builds.

## Success Criteria

- [ ] Every stub produces valid Blade with proper Bootstrap classes
- [ ] Sidebar shows icons and has correct active state
- [ ] CSS adds visible depth (shadows on cards, focus ring on inputs)
- [ ] All buttons are consistently styled (primary CTA, secondary cancel, compact modal)
- [ ] No broken icons — all `bi-*` replaced with working `ti-*`
