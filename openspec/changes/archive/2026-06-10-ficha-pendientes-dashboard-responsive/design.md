# Design: Ficha pendientes dashboard responsive

## Technical Approach

Use the dashboard pendientes table as the visual contract and make ficha initial Blade and AJAX refresh render the same structure: one Bootstrap card, one `table.table-card-mobile`, role columns, `td-cobros`, full-width centered cobro buttons showing `concepto`, and pagination in `tfoot`. Pagination is enforced on container groups, never individual cobros: dashboard/index and cliente ficha page property groups with a hard maximum of 3 properties per page; propiedad ficha pages unit groups with a hard maximum of 3 units per page.

## Architecture Decisions

| Decision | Choice | Alternatives considered | Rationale |
|---|---|---|---|
| Source of truth | Mirror dashboard markup/classes in ficha Blade and JS, with small ficha-specific IDs. | Keep nested ficha cards and tune CSS; large shared renderer extraction. | Nested cards cannot be visually identical. Full extraction is cleaner but likely exceeds the 400-line budget. |
| Initial/AJAX drift | Update `components/pendientes*.blade.php` and `cliente.blade.php`/`propiedad.blade.php` render functions together. | AJAX-only initial load. | Preserves current server-rendered first paint and avoids layout changing after payment refresh. |
| Group pagination | Build the page from distinct group IDs first, then fetch all pending cobros for those IDs. Use `LengthAwarePaginator` for initial Blade groups and `slice()`/metadata for API groups. | Paginate cobros first and group afterward. | Cobro-first pagination can split one property/unit across pages and can show more/less than 3 container groups. |
| Page size guard | Define one constant/parameter per surface and clamp server-side to `min(requested, 3)`: dashboard properties=3, cliente properties=3, propiedad units=3. | Trust JS `POR_PAGINA`/`FICHA_POR_PAGINA` only. | API callers can bypass JS; enforcement must live in controllers. |
| Propiedad unit rule | Compute `show_unidad = Unidad::where('Propiedad_id', $propiedadId)->count() > 1`; pass `$showUnidadColumn` to Blade and `show_unidad`/`unidad_count` in API. | Count only units with pending cobros; use `Propiedad::unidad` relation. | Requirement says real units, not pending units. Existing `Propiedad::unidad()` is `hasOne`, so it is unsafe for total count. |
| CSS reuse | Give ficha tables shared dashboard-like class/ID scope such as `.pendientes-dashboard-table` and extend existing dashboard mobile overrides from `#tabla-pendientes` to that class. | Duplicate full CSS under ficha selectors. | Keeps visual parity and lowers future drift while avoiding unrelated table impact. |

## Data Flow

```text
Pending scope query ──→ distinct group IDs ──→ page 3 group IDs ──→ all cobros for those groups
        │
        ├── dashboard API: property groups
        ├── cliente initial/API: property groups
        └── propiedad initial/API: unit groups + show_unidad
              └── Blade/JS render same table cells/buttons
```

Dashboard rows stay property-based and must change `POR_PAGINA` from 5 to 3 while the API clamps `por_pagina` to 3.

Cliente rows stay property-based: first column `Dirección` links to `/propiedad/ficha/{id}`. The initial controller must stop paginating the cobro query at 10; instead, collect distinct property IDs for the cliente, paginate those IDs at 3 per page, then merge all pending cobros for each selected property into role buckets. AJAX already follows this shape and must keep total metadata as property-count metadata.

Propiedad rows stay unit-based. The initial controller must stop paginating the cobro query at 10; instead, collect distinct unit IDs for the propiedad, paginate those IDs at 3 per page, then fetch all pending cobros for each selected unit. Header becomes optional `Unidad`; when `show_unidad` is false, omit the header/cells and only render visible role columns.

## File Changes

| File | Action | Description |
|---|---|---|
| `resources/views/components/pendientes.blade.php` | Modify | Replace nested cards with dashboard-like card/table, initial rows, `tfoot`, and no `_pendientes-role-table`. |
| `resources/views/components/pendientes-propiedad.blade.php` | Modify | Same table shell, optional `Unidad` column from `$showUnidadColumn`. |
| `resources/views/dashboard/index.blade.php` | Modify | Set dashboard `POR_PAGINA` to 3 so UI requests at most 3 property groups. |
| `resources/views/cliente.blade.php` | Modify | Replace `renderFichaPendientes()`/`renderRoleTable()` with dashboard-style `renderCobros()` and row renderer; keep `cargarFichaPendientes()` and payment modal flow. |
| `resources/views/propiedad.blade.php` | Modify | Same as cliente, but render optional `Unidad`. |
| `app/Http/Controllers/Api/DashboardPendientesController.php` | Modify | Clamp `por_pagina` to max 3 and keep pagination over distinct property IDs. |
| `app/Http/Controllers/Api/ClientePendientesController.php` | Modify | Clamp `por_pagina` to max 3; paginate distinct property IDs before loading cobros; keep response backward-compatible. |
| `app/Http/Controllers/Api/PropiedadPendientesController.php` | Modify | Clamp `por_pagina` to max 3; paginate distinct unit IDs before loading cobros; return `unidad_id`, `unidad_nombre`, `unidad_count`, `show_unidad`. |
| `app/Http/Controllers/Vistas/FichaClienteController.php` | Modify | Replace cobro `paginate(10)` with property-group pagination at 3 and build initial grouped data from selected property IDs. |
| `app/Http/Controllers/Vistas/FichaPropiedadController.php` | Modify | Replace cobro `paginate(10)` with unit-group pagination at 3; compute `$showUnidadColumn` from real `Unidad` rows. |
| `public/assets/css/style.css` | Modify | Extend dashboard card overrides to ficha pendientes table class. |
| `tests/Feature/Api/*PendientesControllerTest.php` | Modify | Add/adjust API shape tests for unit metadata and real-unit conditional visibility. |

## Interfaces / Contracts

All pending APIs return pagination metadata where `total` is group count, not cobro count. Server-side maximum page size is 3 even if the query string asks for more.

Propiedad API adds:

```json
{"show_unidad": true, "unidad_count": 2, "data": [{"unidad_id": 7, "unidad_nombre": "Unidad A", "arrendador": [], "arrendatario": [], "corredor": []}]}
```

Cliente API may include `unidad_nombre` inside flattened unit rows when a property has multiple pending units, but the ficha cliente table still leads with `Dirección` to match dashboard.

## Testing Strategy

| Layer | What to Test | Approach |
|---|---|---|
| Feature/API | Propiedad exposes `show_unidad=true` when real unit count > 1, even if only one unit has pending cobros; false for one unit. | PHPUnit with `DatabaseTransactions`; never run destructive migrations. |
| Feature/API | Dashboard and cliente responses with 4 pending properties return 3 groups on page 1, 1 group on page 2, and `total=4`; requesting `por_pagina=99` still returns 3. | Controller feature tests against JSON metadata and `count(data)`. |
| Feature/API | Propiedad response with 4 pending units returns 3 unit groups on page 1, 1 on page 2, and never splits a unit's cobros across pages. | Seed multiple cobros per unit; assert all cobros for selected units are present. |
| Feature/View | Initial cliente/propiedad ficha renders at most 3 groups and paginator totals are group-based. | HTTP/view assertions against row count and paginator text/links. |
| Feature/API | Unit metadata uses `unidad_nombre`, not overloaded `direccion`. | Extend existing `PropiedadPendientesControllerTest`. |
| Manual responsive | Desktop table and mobile cards match dashboard/index after initial load and after payment refresh. | Browser check at `<576px` and desktop widths. |

## Migration / Rollout

No migration required. Do not run `php artisan migrate`, `migrate:fresh`, `migrate:reset`, or `db:wipe`.

## Open Questions

- [ ] None blocking.
