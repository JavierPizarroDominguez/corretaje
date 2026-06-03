# Proposal: Dashboard Pendientes — Pagination, Display Names, and Buscador Links

## Intent

The dashboard pendientes view shows cobro types as raw DB strings (e.g. "Ingreso Renta Arrendatario") and uses incorrect navigation URLs (`/propiedad/{id}`, `/cliente/{id}`). Users need human-readable "concepto" labels (e.g. "Cobrar renta enero 2025"), 5-per-page pagination for denser information, and links that resolve to `/ficha/` detail pages.

## Scope

### In Scope
- Change `POR_PAGINA` from 10 to 5 in dashboard view
- Add `fecha_cobro` to the pendientes API cobroData so the backend can compute display names
- Compute `concepto` (display name) from `tipo` + `fecha_cobro` with the rename rules; return it in the API response
- Update buscador search API (`DashboardBuscadorController`) URLs from `/propiedad/{id}` → `/propiedad/ficha/{id}` and `/cliente/{id}` → `/cliente/ficha/{id}`
- Update dashboard view property link from `/propiedad/${item.id}` → `/propiedad/ficha/${item.id}`
- Add `/propiedad/ficha/{id}` route to `routes/web.php`
- Dashboard view renders `concepto` instead of raw `tipo` in cobro buttons

### Out of Scope
- Creating the Propiedad ficha view/controller (route only — user confirmed view will be created separately)
- Changing the cobro `tipo` column values in DB (rename is display-only)
- Modifying other views that show cobro tipo (e.g. cobro detail pages)
- Changing pagination API contract (same `por_pagina` parameter, different default)

## Capabilities

### New Capabilities
- `dashboard-pendientes`: Dashboard pendientes display — pagination, computed concepto labels, and property/client ficha links.

### Modified Capabilities
- `buscador`: URL pattern changes from `/{entity}/{id}` to `/{entity}/ficha/{id}` in search results.

## Approach

**Backend-driven display names.** Add `fecha_cobro` to the pendientes API cobroData, then compute a `concepto` field using the rename rules. The `FichaClienteController` (lines 319-328) already implements a similar pattern with a `switch` on `tipo` — extract that logic into a reusable helper or keep it inline in the controller, applying the same principle to the pendientes API.

Rename rules for `concepto`:
| `tipo` | `concepto` pattern |
|---|---|
| `Ingreso Renta Arrendatario` | "Cobrar renta {mes} {año}" |
| `Egreso Renta Arrendador` | "Transferir renta {mes} {año}" |
| `Comision inicial arrendador` / `Comision inicial arrendatario` | "Comisión inicial" |
| `Ingreso Garantía Arrendatario` | "Cobrar garantía" |
| `Egreso Garantía Arrendador` | "Transferir garantía" |
| `Luz`, `Agua`, `Gas`, `Gastos comunes` | "{tipo} {mes} {año}" |
| All others | Keep `tipo` as-is (fallback) |

`mes` and `año` come from `fecha_cobro` (Carbon). If `fecha_cobro` is null, concepto falls back to the raw `tipo`.

The buscador controller simply changes hardcoded URL strings. No JS change needed — `buscador.js` uses `item.url` from the backend.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `resources/views/dashboard/index.blade.php` | Modified | `POR_PAGINA` 10→5; render `concepto` instead of `tipo`; fix property link to `/propiedad/ficha/{id}` |
| `app/Http/Controllers/Api/DashboardPendientesController.php` | Modified | Add `fecha_cobro` to cobroData; compute and add `concepto` field |
| `app/Http/Controllers/Api/DashboardBuscadorController.php` | Modified | Change URL strings to `/ficha/` pattern |
| `routes/web.php` | Modified | Add `Route::get('/propiedad/ficha/{id}', ...)` |
| `config/cobro_roles.php` | Reference | Service tipos list for concepto logic |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Unknown `tipo` values produce ugly labels | Medium | Fallback to raw `tipo` string — safe degradation |
| `fecha_cobro` is null for some cobros | Low | Fallback to `tipo` without month/year suffix |
| `/propiedad/ficha/{id}` route has no controller/view yet | High | Add route with placeholder or existing controller; user confirmed view is coming soon |

## Rollback Plan

Revert 4 files: dashboard view (POR_PAGINA, link, tipo display), pendientes controller (remove concepto/fecha_cobro), buscador controller (revert URLs), remove route. All changes are additive to the API response (new fields); removing them is non-breaking.

## Dependencies

- `/propiedad/ficha/{id}` route must eventually point to a real controller/view (not a blocker for this change)
- `fecha_cobro` column must exist on the `cobro` table (already confirmed — it's a Carbon-cast date field)

## Success Criteria

- [ ] Dashboard shows 5 properties per page
- [ ] Cobros display "Cobrar renta enero 2025" instead of "Ingreso Renta Arrendatario"
- [ ] Service types display "Luz enero 2025" format
- [ ] Unknown tipos fall back to raw tipo string
- [ ] Search autocomplete links resolve to `/propiedad/ficha/{id}` and `/cliente/ficha/{id}`
- [ ] Property address link resolves to `/propiedad/ficha/{id}`