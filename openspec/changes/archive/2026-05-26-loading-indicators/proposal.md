# Proposal: Loading Indicators

## Intent

Every view that fetches data from the server currently shows zero visual feedback while loading. Tables with many rows feel "broken" during AJAX fetches. This change establishes loading spinners as a mandatory pattern for all server-data views and persists the convention so future views automatically follow it.

## Scope

### In Scope
- Create a reusable `showElLoading(el, colspan)` / `hideElLoading(el)` utility in `public/assets/js/app.js`
- Add spinner states to all unprotected `fetch()` calls: dashboard `cargarPendientes()`, buscador autocomplete, `loadPropiedadesPorArrendador()`, `resolveCobroRelationships()`, `registrarPago()`
- Upgrade the existing `filtros.js` spinner to use the shared utility
- Print a page-level loading indicator in `layouts/app.blade.php` (hidden on `DOMContentLoaded`) for server-rendered pages
- Add initial "Cargando..." placeholder rows to server-rendered index table `<tbody>` elements
- Document the convention in an `AGENTS.md` file at project root so all future code follows this pattern

### Out of Scope
- Converting server-rendered Blade `@foreach` tables to full AJAX-loaded data tables (separate refactor)
- Skeleton loading / shimmer animations (over-engineering for current needs)
- Show/detail pages (low priority — server-rendered, no user complaint yet)

## Capabilities

### New Capabilities
- `loading-indicator`: Reusable JS loading indicator utility and mandatory convention for all views that fetch server data. Covers the utility functions, Bootstrap spinner patterns, and the agent convention rule.

### Modified Capabilities
- `buscador`: Buscador searches MUST show a spinner in the autocomplete dropdown while fetching results.
- `cobro-payment`: The payment `registrarPago()` flow MUST show a spinner on the action button during the fetch.
- `administracion-wizard`: The `loadPropiedadesPorArrendador()` select MUST show a Bootstrap spinner instead of plain "Cargando..." text.

## Approach

Use Bootstrap 5 `spinner-border` / `spinner-grow` (already in the codebase) wrapped in two small utility functions added to `public/assets/js/app.js`. Each existing `fetch()` call wraps in `showElLoading` → `hideElLoading`. Server-rendered index pages get a visible "Cargando..." `<tr>` placeholder removed on `DOMContentLoaded`. A root-level `AGENTS.md` files the convention so agents and developers always add loading indicators by default.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `public/assets/js/app.js` | Modified | Add `showElLoading` / `hideElLoading` utility |
| `public/js/filtros.js` | Modified | Refactor to use shared utility |
| `resources/views/dashboard/index.blade.php` | Modified | Spinner in pendientes tbody, buscador dropdown, payment button |
| `resources/views/layouts/app.blade.php` | Modified | Page-level loading overlay |
| `resources/views/administracion/create.blade.php` | Modified | Replace "Cargando..." with spinner |
| `resources/views/cobro/modal/create.blade.php` | Modified | Spinner during `resolveCobroRelationships` |
| `public/assets/css/style.css` | Modified | Optional `.table-loading-overlay` helpers |
| `public/js/buscador.js` | Modified | Spinner in results dropdown during fetch |
| Index blade files (6+) | Modified | Initial placeholder row in tbody |
| `AGENTS.md` | New | Document loading indicator convention |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Page-load spinner flickers on fast cached pages | Low | Only show after 200ms debounce, hide on DOMContentLoaded |
| Missing a fetch() call during implementation | Medium | Audit all `fetch(` occurrences with grep before starting |
| Spinner overlaps content on small tables | Low | Use tbody row pattern (not overlay) for tables |

## Rollback Plan

Remove spinner utility from `app.js`, revert all blade/js changes to previous commits. The utility is additive — removing it only restores the pre-change state with no data loss.

## Dependencies

- Bootstrap 5.3 (already included in layout)

## Success Criteria

- [ ] Every `fetch()` call in the codebase shows a visual spinner before the request and hides it on completion
- [ ] All index page tables show "Cargando..." row placeholder until server content renders
- [ ] `showElLoading` / `hideElLoading` in `app.js` is the single source of truth for table spinners
- [ ] `AGENTS.md` convention file exists and instructs that loading indicators are mandatory for all server-data views