# Design: Unify Ficha Pendientes with Index/Dashboard Style

## Technical Approach

Hybrid server-render + AJAX refresh. Initial page load uses existing controllers with grouped data (fast TTFB). After payment, JS fetches from new scoped API endpoints and re-renders the pendientes section without full reload. Both ficha Blade components are restructured to match the dashboard's grouped card pattern with role-based columns and consistent estado colors. Dashboard gets "Incompleto" added to its estado filter.

## Architecture Decisions

### Decision 1: Keep existing controllers, add grouped data transformation

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Replace controllers entirely | Breaks existing ficha flow, high risk | Rejected |
| New API controllers only, no server render | Slower TTFB, worse UX | Rejected |
| Keep controllers, add grouping after paginate | Minimal risk, preserves existing behavior | **Selected** |

**Rationale**: The controllers already provide `$pendientes` as a paginated collection. After the `foreach` that computes concepto (replaced by `CobroConceptoFormatter`), add a second pass that groups cobros by propiedad/unidad and buckets by role. Pass both the raw paginator (for `$pendientes->links()`) and the grouped structure to the view.

### Decision 2: Grouping logic lives in controllers, not Blade

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Group in Blade with @php blocks | Duplicates logic, harder to test | Rejected |
| Extract to a service class | Over-engineering for simple grouping | Rejected |
| Inline in controller after concepto loop | Single location, easy to verify | **Selected** |

**Rationale**: Grouping is a simple collection operation. Keeping it in the controller avoids Blade complexity and makes it reusable by the new API controllers.

### Decision 3: Estado color mapping via CSS classes + JS constant

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Inline @php in Blade (current) | Duplicated across 3 views | Rejected |
| JS constant only | Server-render doesn't benefit | Rejected |
| CSS utility classes + shared JS constant | Both server and client use same mapping | **Selected** |

**Rationale**: Define CSS classes `.badge-pendiente`, `.badge-vencido`, `.badge-incompleto` that map to Bootstrap's `btn-warning`, `btn-danger`, `btn-info`. Blade uses the CSS class names. JS `ESTADO_COLORS` constant mirrors the mapping for dynamic rendering. Single source of truth conceptually, two physical locations (CSS + JS) for server/client contexts.

### Decision 4: API controllers mirror DashboardPendientesController pattern

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Reuse DashboardPendientesController with params | Different grouping logic (by client vs by property) | Rejected |
| New dedicated controllers | Clear separation, easier to test | **Selected** |

**Rationale**: `ClientePendientesController` groups by propiedad (with optional unidad nesting). `PropiedadPendientesController` groups by unidad. Both use the same response shape and `CobroConceptoFormatter`, but the query and grouping differ enough to warrant separate controllers.

### Decision 5: AJAX refresh reuses dashboard JS patterns

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Copy-paste dashboard JS into ficha views | Duplication, maintenance burden | Rejected |
| Extract shared module to app.js | Breaks existing app.js pattern (IIFE) | Rejected |
| Inline ficha-specific JS matching dashboard pattern | Consistent behavior, isolated scope | **Selected** |

**Rationale**: The ficha JS (`cargarFichaPendientes`, `renderFichaPendientes`) mirrors the dashboard's `cargarPendientes`/`renderCobros` pattern but is scoped to the ficha's DOM structure. This avoids coupling the two views while keeping the same UX.

## Data Flow

```
Initial Load (Server-Rendered):
  FichaClienteController::show()
    └─ Cobro::query()->paginate(10)
    └─ CobroConceptoFormatter::format() → concepto
    └─ Group by propiedad → unidad (if >1) → role bucket
    └─ View: cliente.blade.php → componentes/pendientes.blade.php
       └─ Renders grouped cards/tables from $groupedPendientes

AJAX Refresh (After Payment):
  User clicks "Registrar pago" in #modalCobro
    └─ registrarPago(cobro) → POST /api/cobro/pagar
    └─ Success → cargarFichaPendientes(paginaActual)
       └─ GET /api/cliente/{id}/pendientes?pagina=N&por_pagina=10
          └─ ClientePendientesController::index()
             └─ Same query + grouping as controller
             └─ JSON: { data: [...], total, pagina, por_pagina, total_paginas }
       └─ renderFichaPendientes(json) → replaces #pendientes-section innerHTML
       └─ showElLoading / hideElLoading during fetch

Dashboard (Modified):
  DashboardPendientesController::index()
    └─ $estadosPendientes = ['Pendiente', 'Vencido', 'Incompleto']  ← ADD
    └─ Existing grouping + CobroConceptoFormatter (unchanged)
    └─ JS renderCobros() already handles Incompleto → btn-info (line 177)
```

## Component Structure

### Blade Components

**`pendientes.blade.php`** (Cliente Ficha):
- Receives `$groupedPendientes` (array of propiedad groups) + `$pendientes` (paginator) + `$cliente`
- Desktop: table with dynamic role columns (arrendador/arrendatario/corredor)
- Mobile: propiedad cards → unidad sub-cards (if >1) → badge buttons with estado colors
- Empty state: animated SVG (copied from dashboard)
- Loading placeholder: `<tr class="loading-placeholder">` in tbody
- Pagination: `$pendientes->links()` for initial server render

**`pendientes-propiedad.blade.php`** (Propiedad Ficha):
- Receives `$groupedPendientes` (array of unidad groups) + `$pendientes` (paginator) + `$propiedad`
- Desktop: table with dynamic role columns
- Mobile: unidad cards → badge buttons with estado colors
- Same empty state and loading placeholder pattern

### JS Functions (inline in cliente.blade.php / propiedad.blade.php)

```
cargarFichaPendientes(pagina) — fetches from API, calls renderFichaPendientes
renderFichaPendientes(json)   — builds HTML, replaces #pendientes-section
renderFichaCobros(lista)      — returns badge buttons HTML (mirrors dashboard renderCobros)
renderFichaPaginacion(pagina, totalPaginas) — pagination controls
registrarPago(cobro)          — POST /api/cobro/pagar, then calls cargarFichaPendientes
```

### CSS Classes

```css
.badge-pendiente  → btn-warning (yellow)
.badge-vencido    → btn-danger  (red)
.badge-incompleto → btn-info    (blue)
.unidad-nested    → border-left: 3px solid var(--bs-border-color); padding-left: 12px;
```

Empty state animations copied from dashboard (`fadeInUp`, `bounceIn`, `pulse`).

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Http/Controllers/Api/DashboardPendientesController.php` | Modify | Add `'Incompleto'` to `$estadosPendientes` array (line 19) |
| `app/Http/Controllers/Api/ClientePendientesController.php` | **Create** | API: GET `/api/cliente/{id}/pendientes` — grouped by propiedad, paginated |
| `app/Http/Controllers/Api/PropiedadPendientesController.php` | **Create** | API: GET `/api/propiedad/{id}/pendientes` — grouped by unidad, paginated |
| `app/Http/Controllers/Vistas/FichaClienteController.php` | Modify | Replace switch (lines 67-98) with `CobroConceptoFormatter`. Add grouping pass. Pass `$groupedPendientes` to view. |
| `app/Http/Controllers/Vistas/FichaPropiedadController.php` | Modify | Replace switch (lines 66-97) with `CobroConceptoFormatter`. Add grouping pass. Pass `$groupedPendientes` to view. |
| `resources/views/components/pendientes.blade.php` | Modify | Grouped layout: propiedad cards, unidad sub-cards, role columns, estado CSS classes, animated SVG empty state, loading placeholder |
| `resources/views/components/pendientes-propiedad.blade.php` | Modify | Grouped layout: unidad cards, role columns, estado CSS classes, animated SVG empty state, loading placeholder |
| `resources/views/dashboard/index.blade.php` | Verify only | Confirm `Incompleto` renders (JS line 177 already handles it). No changes needed. |
| `routes/api.php` | Modify | Add routes for `ClientePendientesController` and `PropiedadPendientesController` |
| `resources/views/cliente.blade.php` | Modify | Replace `location.reload()` with `cargarFichaPendientes()`. Add JS functions. Add `@push('styles')` for empty state animations. |
| `resources/views/propiedad.blade.php` | Modify | Replace `location.reload()` with `cargarFichaPendientes()`. Add JS functions. Add `@push('styles')` for empty state animations. |
| `tests/Feature/Api/ClientePendientesControllerTest.php` | **Create** | Feature tests for new API endpoint |
| `tests/Feature/Api/PropiedadPendientesControllerTest.php` | **Create** | Feature tests for new API endpoint |

## Interfaces / Contracts

### API Response Shape (both new endpoints)

```json
{
  "data": [
    {
      "id": 1,
      "direccion": "Av. Providencia 1234",
      "unidad_count": 2,
      "unidades": [
        {
          "id": 5,
          "nombre": "Depto 1A",
          "arrendador": [{ "id": 10, "estado": "Pendiente", "tipo": "Egreso Renta Arrendador", "monto": 500000, "deudor": "Juan Pérez", "deudor_id": 3, "acreedor": "María González", "acreedor_id": 1, "servicio_id": null, "fecha_cobro": "2025-01-01T00:00:00+00:00", "concepto": "Transferir renta enero 2025" }],
          "arrendatario": [],
          "corredor": []
        }
      ],
      "arrendador": [],
      "arrendatario": [],
      "corredor": []
    }
  ],
  "total": 3,
  "pagina": 1,
  "por_pagina": 10,
  "total_paginas": 1
}
```

### Grouped Data Structure (server-rendered, passed to Blade)

```php
$groupedPendientes = [
    [
        'id' => 1,              // propiedad_id or unidad_id
        'direccion' => '...',   // propiedad direccion or unidad nombre
        'unidad_count' => 2,    // distinct unidades (cliente ficha only)
        'unidades' => [         // only when unidad_count > 1
            ['id' => 5, 'nombre' => 'Depto 1A', 'arrendador' => [...], 'arrendatario' => [...], 'corredor' => [...]],
        ],
        'arrendador' => [...],  // flat when unidad_count <= 1
        'arrendatario' => [...],
        'corredor' => [...],
    ],
];
```

### Cobro Item Fields

| Field | Type | Source |
|-------|------|--------|
| `id` | int | `cobro->id` |
| `estado` | string | `ucfirst(cobro->estado)` |
| `tipo` | string | `cobro->tipo` |
| `monto` | int\|null | `cobro->monto` |
| `deudor` | string | `deudorPc->cliente->nombre ?? 'Desconocido'` |
| `deudor_id` | int\|null | `deudorPc->Cliente_id` |
| `acreedor` | string | `acreedorPc->cliente->nombre ?? 'Desconocido'` |
| `acreedor_id` | int\|null | `acreedorPc->Cliente_id` |
| `servicio_id` | int\|null | `cobro->Servicio_id` |
| `fecha_cobro` | string\|null | ISO 8601 |
| `concepto` | string | `CobroConceptoFormatter::format()` |

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | `CobroConceptoFormatter` null tipo → `'Sin tipo'` | Existing test covers; verify no regression |
| Unit | `CobroConceptoFormatter` null fecha → raw tipo | Existing test covers |
| Feature | `DashboardPendientesController` includes Incompleto | Modify existing `test_only_pendiente_and_vencido_in_results` to assert Incompleto IS now included |
| Feature | `ClientePendientesController` groups by propiedad | New test: create client with 2 propiedades, verify response structure |
| Feature | `ClientePendientesController` nests unidades when >1 | New test: create propiedad with 3 unidades, verify `unidades` array |
| Feature | `ClientePendientesController` flat when single unidad | New test: create propiedad with 1 unidad, verify no `unidades` nesting |
| Feature | `PropiedadPendientesController` groups by unidad | New test: create propiedad with 2 unidades, verify grouping |
| Feature | Both API endpoints support pagination | New test: create >10 items, verify `?pagina=2` works |
| Feature | Both API endpoints use CobroConceptoFormatter | New test: verify `concepto` field matches formatter output |
| Manual | Cliente ficha grouped display on desktop | Visual: verify propiedad cards, role columns, dynamic visibility |
| Manual | Cliente ficha nested unidades on mobile (≤576px) | Visual: verify border-left + padding nesting |
| Manual | Propiedad ficha grouped by unidad | Visual: verify unidad cards with role buckets |
| Manual | Dashboard shows Incompleto with blue badge | Visual: verify btn-info color |
| Manual | Payment refreshes via AJAX (no reload) | Network tab: verify no full page reload, only API fetch |
| Manual | Loading spinner appears during AJAX refresh | Visual: verify spinner during fetch |
| Manual | Empty state shows animated SVG | Visual: verify animation when no pendientes |
| Manual | Existing modals still work | Click "Revisar" → #modalPrincipal; click badge → #modalCobro |
| Manual | Pagination boundary after payment | Pay last item on page 2, verify adjusts to page 1 |

## Migration / Rollout

No migration required. All changes are in views, controllers, and routes. No schema changes, no data loss. Rollback is straightforward: revert Blade components, remove API controllers/routes, restore switch blocks, remove `'Incompleto'` from dashboard.

## Edge Case Handling

| Edge Case | Handling |
|-----------|----------|
| **EC-1: No pendientes** | Animated SVG empty state shown. API returns `data: [], total: 0`. |
| **EC-2: Single unidad** | `unidad_count === 1` → skip unidad sub-cards, render cobros flat under propiedad card. |
| **EC-3: Cobro without unidad** | Assigned to `unidades` array with `id: null, nombre: 'Sin unidad'`. |
| **EC-4: Incompleto estado** | Already in ficha queries; dashboard adds it. Color: `btn-info`. |
| **EC-5: Modal conflicts** | No changes to `#modalPrincipal` or `#modalCobro` IDs/handlers. They coexist. |
| **EC-6: Pagination boundary** | JS checks `paginaActual > total_paginas` after refresh, adjusts to page 1 if needed. |
| **EC-7: Null fecha_cobro** | `CobroConceptoFormatter` returns raw tipo (line 31). No change needed. |
| **EC-8: Client with no propiedades** | Empty state shown. "Agregar cobro" modal still works. |
| **EC-9: Concurrent payment** | Handled by existing `PagarCobroController`. Error displays via `mostrarMensaje()`. |

## Dependencies

- `CobroConceptoFormatter` — already exists, stable, no modifications needed
- `showElLoading` / `hideElLoading` — already in `public/assets/js/app.js`
- Bootstrap 5.3 responsive utilities — already available
- `formatCLP` — already in `public/assets/js/app.js`
- No new libraries or packages required

## Open Questions

- None.
