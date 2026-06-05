# Proposal: Pendientes Dashboard Mobile Card Layout

## Intent

The dashboard pendientes table is unusable on mobile — it relies on `table-responsive` horizontal scroll, which is already known to be a poor UX pattern. Content cells contain lists of color-coded cobro buttons that become unreadable when squeezed. We need card-mode layout so users can read and act on pending cobros from their phones.

## Scope

### In Scope
- Add `.table-card-mobile` class to `<table id="tabla-pendientes">` in `dashboard/index.blade.php`
- Add dashboard-specific CSS overrides for button list styling inside card cells and `<tfoot>` pagination visibility on mobile
- Override `text-nowrap` on the table when in card mode (`white-space: normal`)
- Ensure `<tfoot>` pagination remains usable in mobile card layout

### Out of Scope
- Changing the API response shape or `cargarPendientes()` JS logic
- Applying `.table-card-mobile` to other views (ficha tables already handled by `mobile-ui-ux-overhaul`)
- Adding new `labelTable()` calls (MutationObserver already handles dynamic rows)

## Capabilities

### New Capabilities
- `dashboard-pendientes-mobile`: Mobile card layout for the dashboard pendientes table, including button list rendering and pagination in card mode.

### Modified Capabilities
None — the `mobile-table-cards` pattern (CSS + `labelTable()`) already fulfills its spec; this change adds dashboard-specific overrides, not spec-level changes to it.

## Approach

Add `.table-card-mobile` to `tabla-pendientes`. The existing `labelTable()` MutationObserver in `app.js` automatically assigns `data-label` from `<thead>` to dynamicallyInserted `<td>` cells — zero JS changes needed. Add dashboard-specific CSS in `style.css` for: (1) resetting `text-nowrap` inside card cells, (2) stacking cobro buttons vertically within card label sections, (3) making `<tfoot>` pagination stack and remain visible on mobile.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `resources/views/dashboard/index.blade.php` | Modified | Add `.table-card-mobile` to `<table>` class attribute |
| `public/assets/css/style.css` | Modified | Add dashboard card overrides (buttons, tfoot, text-nowrap reset) |
| `public/assets/js/app.js` | None | MutationObserver already handles dynamic rows |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Button lists create tall cards on small screens | Medium | Acceptable UX; reduce button padding inside card cells via CSS |
| `<tfoot>` pagination hidden or broken in card mode | Low | Explicit CSS override to keep `<tfoot>` visible and stacked |
| `text-nowrap` on table conflicts with card layout | Medium | Override `white-space: normal` within `.table-card-mobile` context |

## Rollback Plan

Remove `.table-card-mobile` class from `tabla-pendientes` and remove the dashboard-specific CSS block from `style.css`. Table reverts to original `table-responsive` horizontal scroll — zero data or JS changes to undo.

## Dependencies

- Existing `.table-card-mobile` CSS pattern (`style.css` lines 449–487)
- Existing `labelTable()` + MutationObserver (`app.js` lines 117–143)

## Success Criteria

- [ ] Pendientes table renders as stacked cards on viewports ≤575.98px with `data-label` headers visible
- [ ] Cobro buttons remain clickable and color-coded inside card cells
- [ ] `<tfoot>` pagination is visible and functional on mobile
- [ ] Desktop layout (≥576px) is completely unchanged
- [ ] No JS file changes required