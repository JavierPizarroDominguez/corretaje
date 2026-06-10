# Proposal: Unify Ficha Pendientes with Index/Dashboard Style

## Intent

Cliente and Propiedad ficha pages display pendientes as flat lists with inconsistent layout, duplicated concepto logic, and missing the grouped-by-propiedad card pattern that makes the dashboard clear and navigable. This change unifies the visual hierarchy and behavior across all three views: ficha pendientes will group cobros by propiedad (cliente) or unidad (propiedad), use consistent estado color badges, and the dashboard index will also include the "incompleto" state it currently lacks.

## Scope

### In Scope
- Restructure Cliente Ficha pendientes to group by propiedad (with nested unidad cards when applicable)
- Restructure Propiedad Ficha pendientes to group by unidad
- Add "incompleto" cobro state to DashboardPendientesController and dashboard view
- Unify estado color mapping across all three views (pendiente→warning, vencido→danger, incompleto→info)
- Replace duplicated switch-based concepto logic in FichaClienteController and FichaPropiedadController with CobroConceptoFormatter
- Replace `location.reload()` after payment with AJAX refresh of pendientes section
- Align empty state across fichas (animated SVG instead of static alert)
- Add `showElLoading`/`hideElLoading` feedback to ficha pendientes AJAX loads

### Out of Scope
- Refactoring the full ficha page layout (only pendientes section changes)
- Creating a shared Blade `<x-pendientes-table>` component (future iteration)
- Adding "Agregar cobro" to the dashboard (stays ficha-only)
- Changing the desktop "Revisar" modal (`#modalPrincipal`) behavior
- Changing Cobro model or estado definitions

## Capabilities

### New Capabilities
- `ficha-pendientes-grouped`: Grouped pendientes display in ficha pages (propiedad→unidad cards, role-based badge layout)

### Modified Capabilities
- `ficha-pendientes-mobile`: Desktop table and mobile badge rendering now grouped; concepto uses CobroConceptoFormatter; estado colors unified; AJAX refresh replaces full reload
- `cobro-payment`: DashboardPendientesController now includes "incompleto" estado; all views show three states with distinct colors

## Approach

**Hybrid server-render + AJAX refresh** (Approach 2 from exploration):

1. **Keep existing controllers** — FichaClienteController and FichaPropiedadController continue to provide initial page data. Replace the switch-based concepto computation with `CobroConceptoFormatter::format()` calls.

2. **Add scoped API endpoints** — `GET /api/cliente/{id}/pendientes` and `GET /api/propiedad/{id}/pendientes` that return grouped pendientes (by propiedad for cliente, by unidad for propiedad) in the same JSON shape as `DashboardPendientesController`. Both endpoints use `CobroConceptoFormatter` and include all three estados.

3. **Restructure Blade components** — `pendientes.blade.php` renders initial HTML grouped by propiedad (with unidad sub-cards where applicable). `pendientes-propiedad.blade.php` renders grouped by unidad. Both use the same responsive card/table layout and estado color scheme as the dashboard.

4. **AJAX refresh after payment** — Replace `location.reload()` with a JS function that re-fetches pendientes from the new API endpoint and re-renders the section, matching the dashboard's `cargarPendientes()` pattern.

5. **Add "incompleto" to dashboard** — Update `DashboardPendientesController::$estadosPendientes` to include `'Incompleto'`. The JS `renderCobros()` already handles it (line 177). Verify the dashboard empty state still accounts for the additional cobros.

6. **Nesting for multi-unidad propiedades** — In Cliente Ficha, when a propiedad has more than one unidad, render unidad cards inside the propiedad card with distinct border/padding. In Propiedad Ficha, each unidad gets its own card. Use CSS border-left + padding to visually differentiate nesting on mobile.

7. **Preserve existing modals** — Keep `#modalPrincipal` (Revisar) and `#modalCobro` (lightweight detail) unchanged. Keep "Agregar cobro" button.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Http/Controllers/Api/DashboardPendientesController.php` | Modified | Add 'Incompleto' to `$estadosPendientes` |
| `app/Http/Controllers/Api/ClientePendientesController.php` | New | API endpoint for grouped cliente pendientes |
| `app/Http/Controllers/Api/PropiedadPendientesController.php` | New | API endpoint for grouped propiedad pendientes |
| `app/Http/Controllers/Vistas/FichaClienteController.php` | Modified | Replace switch with CobroConceptoFormatter; restructure pendientes data for grouped display |
| `app/Http/Controllers/Vistas/FichaPropiedadController.php` | Modified | Replace switch with CobroConceptoFormatter; restructure pendientes data for grouped display |
| `resources/views/components/pendientes.blade.php` | Modified | Grouped layout with propiedad cards, unidad sub-cards, role columns, estado colors |
| `resources/views/components/pendientes-propiedad.blade.php` | Modified | Grouped layout with unidad cards, role columns, estado colors |
| `resources/views/dashboard/index.blade.php` | Modified | Verify incompleto state renders correctly |
| `routes/api.php` | Modified | Add two new API routes |
| `public/assets/js/app.js` | Modified (potentially) | Shared refresh function or helpers |
| `resources/views/cliente.blade.php` | Modified | Include updated pendientes component, add refresh JS |
| `resources/views/propiedad.blade.php` | Modified | Include updated pendientes component, add refresh JS |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Controller refactoring breaks existing ficha data flow | Low | Keep controller logic intact; only replace concepto switch. Add new API controllers alongside existing ones. |
| Nested card styling breaks on mobile viewports | Medium | Test with real multi-unidad propiedades; use border-left + padding with responsive utilities; establish visual test spec. |
| Modal conflicts between `#modalPrincipal` and `#modalCobro` | Low | Both modals already coexist; no changes to modal IDs or event handlers. |
| Estado color inconsistency across views | Low | Centralize color mapping in a shared JS constant or CSS class map; all three views reference the same map. |
| Pagination mismatch (AJAX vs server-side) | Medium | New API endpoints support `?pagina=N&por_pagina=10` like dashboard. Initial render uses server-side data; refresh uses API. |
| Incomplete estado addition changes dashboard counts | Low | "Incompleto" cobros are genuinely pending — showing them is correct behavior, not a bug. |

## Rollback Plan

1. Revert changed Blade components — they are self-contained includes.
2. Remove the two new API controllers and routes.
3. Revert controller concepto changes — restore the original switch blocks.
4. Remove `'Incompleto'` from `DashboardPendientesController::$estadosPendientes`.
5. All changes are in views/controllers/routes — no schema changes, no data loss.

## Dependencies

- `CobroConceptoFormatter` service already exists and is battle-tested in the dashboard API.
- Bootstrap 5.3 responsive utilities for card nesting are available in the project.
- `showElLoading`/`hideElLoading` are already in `public/assets/js/app.js`.

## Success Criteria

- [ ] Cliente Ficha pendientes display grouped by propiedad, with unidad sub-cards when >1 unidad
- [ ] Propiedad Ficha pendientes display grouped by unidad
- [ ] All three views (dashboard, cliente ficha, propiedad ficha) show pendiente/vencido/incompleto with consistent colors (warning/danger/info)
- [ ] Dashboard includes "incompleto" cobros in pendientes count and display
- [ ] Payment in ficha pendientes refreshes section via AJAX (no `location.reload()`)
- [ ] Concepto text uses `CobroConceptoFormatter` in all three views (no switch blocks)
- [ ] Empty state shows animated SVG in ficha views
- [ ] Loading indicators appear during AJAX fetches in ficha views
- [ ] Existing "Revisar" and "Agregar cobro" modals still work