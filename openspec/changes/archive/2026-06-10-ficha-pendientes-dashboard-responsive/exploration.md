## Exploration: Ficha pendientes matching dashboard responsive layout

### Current State
Dashboard pendientes (`resources/views/dashboard/index.blade.php`) is the authoritative visual pattern: one responsive table with `table-card-mobile`, role columns (`Cobros al Arrendador/Arrendatario/Corredor`), JS-rendered full-width centered cobro buttons showing `concepto`, mobile card CSS overrides in `public/assets/css/style.css`, pagination in `<tfoot>`, and loading via `showElLoading(tbody, 4)`.

Cliente and propiedad fichas already use AJAX refresh endpoints (`/api/cliente/{id}/pendientes`, `/api/propiedad/{id}/pendientes`) and lightweight `#modalCobro`, but their rendered shape differs from dashboard: they show nested ficha cards, inner role tables, mobile-only badge blocks, and desktop buttons showing `estado` instead of dashboard's centered `concepto` buttons. The shared Blade partial `components._pendientes-role-table` mirrors that older ficha-specific pattern.

The APIs already return bucketed `arrendador`, `arrendatario`, `corredor` arrays with cobro objects compatible with dashboard rendering. Cliente API groups by propiedad and exposes `unidad_count`/`unidades` when multiple unidades have cobros. Propiedad API currently groups by unidad and exposes each unit as `id` + `direccion` where `direccion` is actually the unidad name; it does not expose total property unit count, so UI cannot reliably hide/show a `Unidad` column based on whether the property has more than one unit.

### Affected Areas
- `resources/views/dashboard/index.blade.php` — reference markup/JS: `renderCobros()`, `cargarPendientes()`, dynamic role column visibility, centered full-width cobro buttons.
- `public/assets/css/style.css` — mobile card rules and dashboard-specific overrides for `#tabla-pendientes`; ficha-specific overrides currently exist but do not make fichas identical.
- `public/assets/js/app.js` — `showElLoading`/`hideElLoading` and `table-card-mobile` auto-labeling via MutationObserver.
- `resources/views/components/pendientes.blade.php` — cliente initial pendientes markup, currently nested cards + `_pendientes-role-table`.
- `resources/views/components/pendientes-propiedad.blade.php` — propiedad initial pendientes markup, currently unit cards rather than one dashboard-like table.
- `resources/views/cliente.blade.php` — cliente AJAX renderer duplicates the non-dashboard ficha pattern and should be aligned with the dashboard render path.
- `resources/views/propiedad.blade.php` — propiedad AJAX renderer must become dashboard-like and replace property/direction with optional `Unidad` column.
- `app/Http/Controllers/Api/ClientePendientesController.php` — data is mostly sufficient; may need stable unidad metadata for table rows when flattening.
- `app/Http/Controllers/Api/PropiedadPendientesController.php` — needs explicit unit naming and property unit count / `show_unidad` signal for the conditional `Unidad` column.
- `app/Http/Controllers/Vistas/FichaClienteController.php` and `app/Http/Controllers/Vistas/FichaPropiedadController.php` — server-rendered initial structures must match the AJAX/API structures or be replaced by an empty AJAX-loaded dashboard-like shell.
- `tests/Feature/Api/*Pendientes*Test.php` — existing API shape tests cover grouping, statuses, pagination, concepto; add/adjust tests for propiedad unit metadata and multi-unit behavior.

### Approaches
1. **Dashboard-like table shell for fichas** — Replace ficha nested cards with one `card` + one `table.table-card-mobile`, render rows like dashboard, and keep scoped AJAX refresh.
   - Pros: Best visual match; reuses current APIs, loading utilities, modal flow, CSS/MutationObserver; avoids duplicating desktop/mobile markup.
   - Cons: Requires careful mapping of cliente multi-unidad data into rows; initial Blade and JS renderer must stay consistent.
   - Effort: Medium

2. **Keep ficha card components and tune CSS** — Preserve nested cards/role-table structure, only adjust classes/styles/buttons.
   - Pros: Smaller change to current fichas.
   - Cons: Cannot be visually identical to dashboard because DOM hierarchy, table/card boundaries, pagination placement, and mobile behavior differ.
   - Effort: Low-Medium

3. **Extract shared pendientes JS/Blade module** — Move dashboard/ficha rendering to shared helpers/components.
   - Pros: Long-term DRY and prevents drift.
   - Cons: Larger refactor, likely above the 400-line review budget unless sliced.
   - Effort: High

### Recommendation
Use Approach 1 now. Build a dashboard-like pendientes shell for cliente and propiedad fichas: same card/table/classes, same centered full-width `concepto` buttons in role cells, same column visibility detection, and local scoped AJAX refresh. For propiedad ficha, rows should use `Unidad` instead of `Dirección`, and the `Unidad` column should render only when the property has more than one unit; the API should expose enough metadata to decide that reliably (not just count units with pending cobros).

Keep `Agregar cobro` and the lightweight payment modal behavior, but make the visible pendientes region match dashboard. If implementation grows, slice into two reviewable work units: API/data-shape tests first, view/JS/CSS alignment second.

### Risks
- Current propiedad API `direccion` means unidad name, which is semantically confusing and risky for a clean `Unidad` column.
- Conditional `Unidad` visibility requires total units on the property, not just pending units; using only pending grouped rows would hide the column incorrectly when a multi-unit property has pending cobros in one unit.
- Ficha initial Blade render and AJAX render currently duplicate markup; changing only one will create visual drift after payment refresh.
- Dashboard CSS overrides are scoped to `#tabla-pendientes`; fichas may need shared classes or duplicated scoped selectors to be truly identical without affecting other responsive tables.
- Existing tests are API-heavy; visual identity will need Blade/feature assertions or careful manual responsive verification.

### Ready for Proposal
Yes. The next phase should propose a scoped implementation that aligns ficha pendientes to the dashboard visual contract, with explicit propiedad unit metadata and tests around the optional `Unidad` column.
