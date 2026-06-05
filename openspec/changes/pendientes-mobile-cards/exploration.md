## Exploration: Pendientes Dashboard — Mobile Card Layout

### Current State

The dashboard (`resources/views/dashboard/index.blade.php`) displays pending cobros (charges/payments) in a **JavaScript-rendered table** with 4 columns:

1. **Dirección** — property address (links to `/propiedad/ficha/{id}`)
2. **Cobros al Arrendador** — list of pending cobro buttons (color-coded by status)
3. **Cobros al Arrendatario** — list of pending cobro buttons
4. **Cobros al Corredor** — list of pending cobro buttons

The table is wrapped in `<div class="table-responsive">` which provides **horizontal scrolling** on mobile — the Bootstrap default approach. Columns are dynamically shown/hidden based on whether data exists for that role (`hayCol` JS detection).

The data comes from API endpoint `/api/dashboard/pendientes` (controlled by `DashboardPendientesController`), returns paginated JSON with `id`, `direccion`, `arrendador[]`, `arrendatario[]`, `corredor[]` arrays. Each cobro item has: `id`, `estado`, `tipo`, `monto`, `deudor`, `deudor_id`, `acreedor`, `acreedor_id`, `servicio_id`, `fecha_cobro`, `concepto`.

Key challenge: **The table is JS-rendered** — `cargarPendientes()` builds `<tr>` rows dynamically. The existing `.table-card-mobile` CSS pattern relies on `labelTable()` in `app.js` auto-assigning `data-label` attributes from `<thead>` text. But `labelTable()` runs at `DOMContentLoaded` and also watches for mutations on `.table-card-mobile` tables — so it WOULD work if the table had the class, but the pendientes table currently does NOT have it.

The existing `.table-card-mobile` CSS (style.css lines 449–487) converts tables to card layout at `≤575.98px` by:
- Hiding `<thead>`
- Making each `<tr>` a block card with border
- Making each `<td>` a flex row with label via `data-label` attribute
- Hiding empty `<td>` elements

This pattern is already applied to ficha detail tables (components/pendientes.blade.php, pendientes-propiedad.blade.php, transacciones-propiedad.blade.php) and works well for 2–3 column tables.

**The dashboard pendientes table has a structural difference**: its content cells contain **lists of buttons** (multiple cobros per role per row), not simple text. This makes the `.table-card-mobile` pattern viable but with nuances — the `data-label` labels on cells with button lists will look fine, but each card will be tall when a property has many pending cobros.

### Affected Areas

- `resources/views/dashboard/index.blade.php` — Main view: the `<table>` element, `cargarPendientes()` JS function, empty state, pagination. The `<table>` needs `.table-card-mobile` class and dynamic column visibility must still work.
- `public/assets/css/style.css` — Existing `.table-card-mobile` rules (lines 449–487). May need a dashboard-specific override if button lists need extra styling.
- `public/assets/js/app.js` — `labelTable()` function (lines 117–143) auto-labels `.table-card-mobile` tables and watches mutations. Already works for dynamically-added rows via MutationObserver.
- `resources/views/layouts/app.blade.php` — Layout with Bootstrap 5.3, sidebar, main content area. No changes needed, but relevant for breakpoint context (sidebar collapses at ≤991px, card layout at ≤575px).

### Approaches

1. **CSS `.table-card-mobile` class + existing `labelTable()`** — Add `.table-card-mobile` to the dashboard pendientes `<table>` and ensure `labelTable()` picks up dynamically-rendered rows (it already does via MutationObserver). The `<thead>` headers become labels, each property row becomes a card.
   - Pros: Reuses existing proven pattern; minimal code changes (add 1 class to `<table>`, maybe minor CSS tweaks); MutationObserver already handles dynamic rows; consistent with ficha detail tables
   - Cons: Button lists (multiple cobros per cell) create tall cards; column visibility (`hayCol` hiding empty columns) creates inconsistent card layouts between pages (some cards have 4 rows, others 2); cards at 575px breakpoint may feel cramped on phones
   - Effort: Low

2. **JS-rendered cards on mobile** — Add mobile detection in `cargarPendientes()` and render cards (div-based) instead of `<tr>` rows below a breakpoint. Two render paths based on viewport.
   - Pros: Full control over card layout and styling; can customize card structure for each property (direccion as title, cobros grouped by role underneath); better UX for button lists
   - Cons: Duplicate rendering logic (table for desktop, cards for mobile); viewport detection in JS is fragile (resize events, orientation changes); more JS to maintain; harder to keep in sync with table changes
   - Effort: Medium

3. **CSS card layout with custom dashboard styles** — Add `.table-card-mobile` but also add dashboard-specific CSS overrides to style the button lists and address card within the mobile card layout. Uses the same pattern but with enhanced visuals.
   - Pros: Reuses existing pattern but improves it for the dashboard's specific needs (button groups, role headings); responsive CSS-only (no JS branching); can hide empty role sections gracefully; consistent with project conventions
   - Cons: Slightly more CSS than option 1; need to handle `tfoot` pagination in card mode (pagination should remain horizontal)
   - Effort: Low–Medium

### Recommendation

**Approach 3: CSS `.table-card-mobile` class + dashboard-specific CSS overrides**

This is the best balance:
1. Add `.table-card-mobile` class to `<table id="tabla-pendientes">` in the Blade template
2. The existing MutationObserver in `labelTable()` (app.js) will automatically pick up the dynamically-rendered rows and assign `data-label` attributes — **no JS changes needed for basic labeling**
3. Add targeted CSS in `style.css` to enhance the card layout for the dashboard:
   - Style cobro buttons within `.table-card-mobile` cards to stack cleanly
   - Ensure the pagination `<tfoot>` remains visible and usable on mobile
   - Add `.btn-cobro` specific styling within card cells (currently `w-100` buttons work well in cards)
4. Handle column visibility: when `hayCol` hides a column, the corresponding `<th>` gets `display: none`. The `labelTable()` function reads `<thead>` text for `data-label` — but if a `<th>` is hidden, its column won't have cells, so the card row will simply have fewer `<td>` elements. This works naturally.

The key insight: the existing infrastructure (`.table-card-mobile` CSS + `labelTable()` MutationObserver) already handles dynamic content. The dashboard table just needs: (1) the class on the table element, (2) minor CSS tweaks for button styling within cards, and (3) ensuring the `tfoot` pagination survives the card transformation.

### Risks

- **Pagination in card mode**: The `<tfoot>` with pagination must remain visible and functional. The `.table-card-mobile` CSS currently doesn't address `<tfoot>`. Need to add `tfoot { display: table-footer-group }` or restructure pagination outside the table on mobile.
- **Button lists within cards**: Multiple cobro buttons per cell will stack vertically, creating tall cards. This is acceptable UX but should be tested on real devices.
- **Column visibility inconsistency**: When `hayCol` hides an empty column (e.g., no arrendatario cobros), the card simply omits that row — which is actually better UX than showing an empty "—". No risk, actually a benefit.
- **Breaking point**: The existing `.table-card-mobile` breakpoint is `≤575.98px`. On phones in portrait (320–430px), 4-column tables are unusable even with horizontal scroll. The 575px breakpoint is appropriate.

### Ready for Proposal

Yes — all affected files are identified, the existing pattern is well understood, and the approach is clear. The next step is `sdd-propose` to formalize the change scope and rollback plan.