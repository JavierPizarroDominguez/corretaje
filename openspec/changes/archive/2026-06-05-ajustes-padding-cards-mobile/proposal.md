# Proposal: Ajustes Padding Cards Mobile

## Intent

Ficha detail pages (propiedad, cliente) have no horizontal padding on mobile — content touches viewport edges. Additionally, their pendientes tables render as generic card rows (Concepto label + "Revisar" button) instead of matching the dashboard's styled cobro cards with colored badges and detail modal. This change adds mobile padding globally and brings the ficha pendientes mobile experience to parity with the dashboard.

## Scope

### In Scope
- Add mobile horizontal padding to `<main class="content">` so ficha pages aren't edge-to-edge on small screens
- Redesign mobile pendientes cards in `pendientes-propiedad.blade.php` to show colored estado badges (warning/danger/info) like the dashboard
- Redesign mobile pendientes cards in `pendientes.blade.php` (cliente ficha) identically
- Add `#modalCobro` markup and click handler to both ficha pages for instant cobro detail view
- Serialize cobro data (`data-cobro` JSON) on each badge button from Eloquent models
- Desktop table layout (≥576px) remains completely unchanged

### Out of Scope
- Changing the dashboard pendientes implementation (already working)
- Modifying the "Revisar" modal (`cobro/modal/show.blade.php`) — we're adding a simpler summary modal, not replacing the edit modal
- Adding new API endpoints
- Changing the `labelTable()` MutationObserver in `app.js`

## Capabilities

### New Capabilities
- `ficha-pendientes-mobile`: Mobile card layout for ficha pendientes with colored estado badges and cobro detail modal, matching dashboard visual patterns.

### Modified Capabilities
- `mobile-responsive-layout`: Add mobile horizontal padding rule for `.content` to prevent edge-to-edge content on ficha pages.

## Approach

**Padding**: Add a `@media (max-width: 575.98px)` rule in `style.css` targeting `.content` with `padding-left: 1rem; padding-right: 1rem;`. This mirrors the dashboard's `container-fluid` gutters without requiring blade changes to every page.

**Ficha mobile cards**: On mobile (≤575.98px), replace the generic 2-column table card (Concepto + "Revisar") with styled badges per cobro showing: concepto text on a colored button (warning=Pendiente, danger=Vencido, info=Incompleto). Each button carries `data-cobro` JSON with id, concepto, tipo, estado, monto, fecha_cobro, deudor, deudor_id, acreedor, acreedor_id. Clicking a badge opens `#modalCobro` — a lightweight summary modal showing tipo, deudor/acreedor links, formatted CLP monto, formatted date, and "Registrar pago" button.

**Implementation**: Conditionally render mobile badges and desktop table in the blade template using Bootstrap's responsive utility classes (`d-sm-none` for mobile-only badges, `d-none d-sm-table-row` for the table on desktop). Add `#modalCobro` markup and a click handler script in a `@push('scripts')` block in both ficha pages. CSS overrides add thick border, shadow, and badge stacking matching dashboard `#tabla-pendientes` card style.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `public/assets/css/style.css` | Modified | Add `.content` mobile padding rule; add ficha pendientes card overrides (border, shadow, badge stacking) |
| `resources/views/components/pendientes-propiedad.blade.php` | Modified | Add mobile badge rendering with `data-cobro` JSON; keep desktop table |
| `resources/views/components/pendientes.blade.php` | Modified | Same badge rendering as pendientes-propiedad |
| `resources/views/propiedad.blade.php` | Modified | Add `#modalCobro` markup + cobro click handler script |
| `resources/views/cliente.blade.php` | Modified | Add `#modalCobro` markup + cobro click handler script |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| `#modalCobro` ID collision with dashboard | Low | Dashboard and ficha pages are separate navigations; no conflict. |
| Cobro Eloquent serialization misses null relations | Medium | Use null-safe operators (`??`) in blade; default to 'N/A' for missing deudor/acreedor. |
| CSS specificity: `.content` padding breaks dashboard layout | Low | Dashboard uses `container-fluid` which applies its own gutters; override only applies at ≤575.98px. |
| Colored badges on mobile hide "Revisar" functionality | Low | The "Revisar" link still exists inside `#modalCobro` as "Ver detalle"; desktop table keeps "Revisar" button intact. |

## Rollback Plan

1. Remove `.content` mobile padding rule from `style.css`
2. Remove ficha card override CSS block from `style.css`
3. Remove mobile `<div>` badge blocks and `@push('scripts')` from both pendientes components
4. Remove `#modalCobro` markup and handler from both ficha views
5. Desktop table rendering (`table-card-mobile` class + `labelTable()`) continues working unchanged

## Dependencies

- Existing `.table-card-mobile` CSS + `labelTable()` MutationObserver (unchanged)
- Dashboard `#modalCobro` pattern as reference (JS handler logic ported)

## Success Criteria

- [ ] Ficha pages have comfortable horizontal padding on viewports ≤575.98px (no edge-to-edge content)
- [ ] Mobile pendientes cards show colored estado badges (warning/danger/info) with concepto text
- [ ] Tapping a badge opens `#modalCobro` showing tipo, deudor/acreedor links, formatted monto, formatted date
- [ ] "Registrar pago" button in `#modalCobro` works (navigates to cobro payment page)
- [ ] Desktop table (≥576px) is completely unchanged — original 2-column table with "Revisar" button
- [ ] Dashboard pendientes page is unaffected