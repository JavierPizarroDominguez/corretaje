# Exploration: mobile-ui-ux-overhaul

## Current State

### Layout & Mobile Toggle (Point 1 & 2)

- **Main layout**: `resources/views/layouts/app.blade.php` — sidebar (240px fixed) + content area with `margin-left: 240px`.
- **Mobile toggle button**: `<button id="mobileBtn">` at `position: fixed; top: 10px; left: 10px; z-index: 1050;` — a small icon button that opens the sidebar on mobile.
- **Mobile CSS** (`public/assets/css/style.css`): At `≤991.98px`, sidebar slides from `-240px` off-screen, content gets `margin-left: 0 !important`. The button is hidden on `≥992px`.
- **Problem**: The mobile toggle button overlays the top-left of the main content. The `<h1 class="fs-3 mb-1">Buscador</h1>` in dashboard is at the very top of `.container-fluid` — no top padding exists to push it below the button. The content starts immediately where the button sits.

### Font Sizes (Point 1)

- Body font: `--bs-body-font-size: 0.875rem` (14px) globally. No mobile-specific font overrides exist.
- The only mobile typography rule is font-size `0.8rem` for pagination `.page-link` at `≤575.98px`.
- Headings use `fs-3` (1.5rem) in dashboard and `h2` in index pages — no mobile scaling.
- iOS zoom prevention: `.form-control, .form-select { font-size: 16px }` at `≤575.98px` — only affects inputs.

### Tables (Point 3)

- **CSS `.table-card-mobile`** exists in `style.css` (lines 449–487) with responsive card behavior via `data-label` attributes. It hides `thead`, stacks `tr` as blocks, and uses `td::before { content: attr(data-label) }`.
- **HOWEVER**: No table in the project uses this class. All index tables use `class="table table-bordered table-hover"` (or `table-hover`) without `.table-card-mobile`.
- **`app.js`** (lines 117–143) has `labelTable()` that auto-assigns `data-label` from `thead` text and watches for mutations — but it only runs on `.table-card-mobile` tables, which don't exist yet.
- **Index tables that need conversion**:
  - `cobro/index.blade.php` — 12-column table (Fecha Cobro, Estado, Tipo, Monto, Detalle, Contrato, Servicio, Propiedad, Unidad, Deudor, Acreedor, Acciones)
  - `cliente/index.blade.php` — 8-column table
  - `contrato/index.blade.php` — 13-column table
  - `propiedad/index.blade.php` — 3-column table
  - `transaccion/index.blade.php` — 6-column table
  - `dashboard/index.blade.php` — JS-rendered pendientes table (4 columns: Dirección, Cobros Arrendador, Cobros Arrendatario, Cobros Corredor)
  - Component tables: `components/pendientes.blade.php`, `components/pendientes-propiedad.blade.php` — 2-column tables
- **`cobro.table.blade.php`** — partial used by some views, also plain table
- **Exception**: administracion/create — no tables to convert (it's a wizard form)

### Search Links (Point 4)

- **Dashboard buscador**: `DashboardBuscadorController` returns URLs like `/propiedad/ficha/{id}` and `/cliente/ficha/{id}`. The JS `buscador()` function either calls `onSelect` callback or navigates via `window.location.href`.
- **Index buscador instances**: Most index pages use `buscador()` with `tipo` filter, defaulting to the `/buscador` route (`BuscadorController`) which, for `cliente` type, returns `/cliente/{id}` (the standard CRUD show view, NOT the ficha view).
- **Cobro show page**: Links to deudor/acreedor use `/cliente/ficha/{id}` — correct ficha links.
- **Cobro index links**: Deudor links use `/cliente/ficha/{id}`, propiedad links use `/propiedad/{id}` (not ficha).
- **Note**: `/cliente/ficha/{id}` currently renders `coming-soon.blade.php` (route in web.php). But `/cliente/{id}` has a real controller at `FichaClienteController@show` registered in `routes/generated.php` at `Route::get('/cliente/ficha/{id}', ...)`.
- **Route conflict**: `web.php` defines `cliente.ficha` as `coming-soon`, but `generated.php` also defines it pointing to `FichaClienteController`. The generated route likely takes precedence or there's a conflict.
- **All "ficha" pages exist**: `propiedad.blade.php` (ficha propiedad) and `cliente.blade.php` (ficha cliente) both exist with cobros tables, edit modals, etc.

### Admin Wizard Searcher Overlap (Point 5)

- **Step 1** (arrendador): `<div id="lista-arrendador">` is `position-absolute w-100 z-index:1000` wrapping a `<div class="list-group">`. The container is `<div class="col-md-6">`.
- **Step 2** (arrendatario): Same pattern as step 1.
- **The "Añadir" button** (`btnAddArrendador`, `btnAddArrendatario`) is in `col-md-2` beside the search input.
- **On mobile**: With `col-md-6` and `col-md-2`, these become full-width stacking. The search results dropdown (`position-absolute`) can cover the button if there are no matches or many results.
- **Issue confirmed**: The autocomplete list `#lista-arrendador` uses `position-absolute` with `z-index:1000`. On mobile with narrow viewport, the dropdown can extend below the button area. When empty ("No se encontraron resultados"), it still shows a message that could overlap the action button.

### Propiedad Step — No Properties Case (Point 6)

- **Step 3** (`step-03-propiedad.blade.php`): When `loadPropiedadesPorArrendador()` fetches zero results, the select shows "Sin propiedades registradas" as an option, PLUS the "➕ Agregar nueva propiedad" option.
- **Current behavior**: Empty select is shown, not a text input. The user must manually switch to "Agregar nueva propiedad" and then a text input appears.
- **Requested behavior**: If client has no properties, show text input directly instead of select.

### Commission Initialization (Point 7)

- **Step 5** (comision): `comisionMontoInput` starts with no initial value. There's no JS that sets it to half the rent when the step appears.
- **Step 6** (egreso) has auto-fill logic: `egresoInput.value = rentaInput.value` when entering step 6, and `comisionMensualInput.value = 0`.
- **Requested**: Commission initial should auto-initialize to `renta / 2`.

### Summary Position (Point 8)

- **Current**: The `<div id="resumen-wrapper">` is placed INSIDE the `.card-body` but ABOVE the step title and step content (line 47 of create.blade.php). It appears BELOW the progress steps but ABOVE the current step form.
- **Requested**: Summary should appear BELOW the form (below the step content area).

### Guarantee = Rent (Point 9)

- **Step 7** (garantia): `garantiaInput` has no auto-initialization. Currently `value="{{ old('garantia') }}"`.
- **Requested**: Guarantee should auto-initialize to the same value as rent.

### Admin Success Redirect (Point 10)

- **Current**: `AdministracionController::store()` redirects to `route('contrato.show', ['contrato' => $contrato->id])` with a flash success message.
- **Requested**: After success, show the message, let user read it, then redirect to the property ficha (`/propiedad/ficha/{propiedad_id}`).

### Cobro Detail & Back Button (Point 11)

- **Current cobro show** (`cobro/show.blade.php`): Has `<a href="/cobro" class="btn btn-outline-secondary btn-sm">Volver</a>` — always goes to cobro index.
- **Dashboard modal**: When clicking "Detallar" in the dashboard cobro modal, `window.location.href = /cobro/${cobro.id}` navigates to the cobro show page.
- **Cobro edit page** (`cobro/edit.blade.php`): Links back to `/cobro` index.
- **Requested**: Back button should return to the previous view (e.g., the property ficha or client ficha), not always to `/cobro`.

## Affected Areas

- `public/assets/css/style.css` — Font size overrides, mobile content padding, toggle button repositioning
- `resources/views/layouts/app.blade.php` — Layout structure for mobile spacing
- `resources/views/layouts/partials/sidebar.blade.php` — Sidebar mobile behavior
- `public/assets/js/app.js` — labelTable auto-application, potential mobile viewport adjustments
- `resources/views/dashboard/index.blade.php` — Heading visibility, table card conversion, cobro modal
- `resources/views/cobro/index.blade.php` — Table → card conversion on mobile
- `resources/views/cobro/show.blade.php` — Back button behavior, link targets
- `resources/views/cobro/modal/show.blade.php` — Link targets, back button
- `resources/views/cobro/edit.blade.php` — Back button behavior
- `resources/views/cliente/index.blade.php` — Table → card, search link target
- `resources/views/contrato/index.blade.php` — Table → card
- `resources/views/propiedad/index.blade.php` — Table → card, search link target
- `resources/views/transaccion/index.blade.php` — Table → card
- `resources/views/propiedad.blade.php` (ficha) — Tables inside ficha
- `resources/views/cliente.blade.php` (ficha) — Tables inside ficha
- `resources/views/components/pendientes.blade.php` — Table → card
- `resources/views/components/pendientes-propiedad.blade.php` — Table → card
- `resources/views/administracion/create.blade.php` — Mobile layout, searcher overlap, summary position, success redirect
- `resources/views/administracion/partials/step-03-propiedad.blade.php` — No-properties text input logic
- `resources/views/administracion/partials/step-05-comision.blade.php` — Commission initialization
- `resources/views/administracion/partials/step-07-garantia.blade.php` — Guarantee initialization
- `resources/views/administracion/partials/step-01-arrendador.blade.php` — Searcher overlap
- `resources/views/administracion/partials/step-02-arrendatario.blade.php` — Searcher overlap
- `app/Http/Controllers/AdministracionController.php` — Success redirect destination
- `app/Http/Controllers/BuscadorController.php` — Search result URLs (cliente should link to ficha)
- `app/Http/Controllers/Api/DashboardBuscadorController.php` — Already links to ficha URLs
- `public/js/buscador.js` — Autocomplete list z-index and mobile positioning

## Approaches

### 1. **Incremental CSS-First Mobile Overhaul** — Add mobile media queries and restructure templates minimally

- **Pros**: Low risk — each point is independent; can be done incrementally; existing `.table-card-mobile` CSS pattern already works
- **Cons**: Template changes are still needed for `data-label` attributes on dynamic tables
- **Effort**: Medium

### 2. **Component-Based Responsive Refactor** — Create Blade components for mobile cards and reusable card-table patterns

- **Pros**: DRY — single component for all index tables; consistent card rendering
- **Cons**: More upfront work; needs to handle both server-rendered and JS-rendered tables
- **Effort**: High

### 3. **Hybrid Approach** — CSS-first for layout/typography, add `.table-card-mobile` class to existing tables, JS adjustments for wizard and cobro flows

- **Pros**: Practical — leverages existing CSS infrastructure; minimal template changes; handles both static and dynamic tables
- **Cons**: Some tables are JS-rendered (dashboard pendientes) and need different handling
- **Effort**: Medium

## Recommendation

**Approach 3 (Hybrid)** is recommended because:

1. The `.table-card-mobile` CSS already exists and works well — just needs the class added to tables and `data-label` attributes.
2. The `labelTable()` JS function already auto-labels cells — just needs to be called on more tables.
3. The mobile toggle and content spacing are pure CSS fixes.
4. The wizard changes (points 5–10) are isolated JS/template tweaks.
5. The cobro flow (point 11) needs a referer-based back button with fallback.

Key implementation order:
- **Phase 1**: CSS (font sizes, toggle/content spacing, table-card-mobile)
- **Phase 2**: Template changes (add `.table-card-mobile` to tables, dashboard JS table render update)
- **Phase 3**: Admin wizard fixes (points 5–9)
- **Phase 4**: Link targets and redirect changes (points 4, 10, 11)

## Risks

- **Dynamic dashboard table**: The pendientes table is rendered by JS (`cargarPendientes()`), not Blade. Adding `.table-card-mobile` requires class on the `<table>` and JS to add `data-label` attributes, or restructuring the JS to render cards directly on mobile.
- **Wide tables**: Cobro index has 12 columns, contrato index has 13. Vertical cards on mobile will be very long per row. Need to consider which columns are essential and which can be hidden on mobile.
- **Searcher overlap (point 5)**: On narrow screens, the absolute-positioned autocomplete list can extend beyond the viewport or overlap action buttons. Need `max-height` with scrolling, and z-index management for the wizard card context.
- **Back button (point 11)**: Using `window.history.back()` or `document.referrer` can be unreliable if the user arrived via a different path. Better approach: pass a `?from=` parameter or use session to track the source view.
- **Route conflict**: Two routes exist for `/cliente/ficha/{id}` — one in `web.php` (coming-soon) and one in `generated.php` (FichaClienteController). Need to verify which takes precedence and remove the stub.
- **Commission/guarantee auto-fill (points 7, 9)**: These happen at specific step transitions. Need to ensure the rent value is available when steps 5 and 7 are reached, considering the skip logic for `sin_administracion`.
- **Property redirect (point 10)**: The `store()` method needs the `propiedad_id` from the form. Currently it creates a contrato and redirects to contrato.show. Need to resolve the propiedad_id from the contrato relationship to build the redirect URL.

## Ready for Proposal

**Yes** — all 11 points are well-understood and documented. The change is ready for proposal. Key decisions for the user:

1. Should index tables hide non-essential columns on mobile, or show all columns as card rows?
2. For the cobro back button, should we use `?from=url` parameter or `session('previous_url')`?
3. For point 10, should the success message use the existing `flashModal` or a custom redirect-with-delay page?

---

## Critical File Summary

| File | Current State | Change Needed |
|------|--------------|---------------|
| `public/assets/css/style.css` | Body 14px, mobile sidebar CSS, `.table-card-mobile` exists but unused | Add mobile font sizes, content top padding, fix toggle overlap |
| `resources/views/layouts/app.blade.php` | Fixed toggle button, flash modal, sidebar include | Add mobile padding/spacing to `<main>` content area |
| `public/assets/js/app.js` | labelTable() only targets `.table-card-mobile` | Auto-apply to all responsive tables |
| `resources/views/dashboard/index.blade.php` | JS-rendered pendientes table, cobro modal | Convert table to cards on mobile, fix heading visibility, link fixes |
| `resources/views/administracion/create.blade.php` | 8-step wizard, Alpine.js, resumen ABOVE form | Move resumen below form, fix searcher overlap, commission/guarantee init |
| `resources/views/administration/partials/step-*.blade.php` | Steps with searchers and inputs | Step 3: no-properties text input; Steps 5,7: auto-values |
| `resources/views/cobro/show.blade.php` | Back button goes to /cobro | Smart back button |
| `resources/views/cobro/index.blade.php` | 12-column table, plain no `.table-card-mobile` | Add class, data-label attributes |
| `resources/views/cliente/index.blade.php` | 8-column table | Add class, data-label |
| `resources/views/contrato/index.blade.php` | 13-column table | Add class, data-label (may need column hiding) |
| `resources/views/propiedad/index.blade.php` | 3-column table | Add class, data-label |
| `resources/views/transaccion/index.blade.php` | 6-column table | Add class, data-label |
| `app/Http/Controllers/AdministracionController.php` | Redirects to contrato.show | Redirect to propiedad.ficha with success flash |
| `app/Http/Controllers/BuscadorController.php` | Cliente links to `/cliente/{id}` | Change to `/cliente/ficha/{id}` |