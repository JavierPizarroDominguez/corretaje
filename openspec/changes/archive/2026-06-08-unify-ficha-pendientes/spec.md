# Spec: Unify Ficha Pendientes with Index/Dashboard Style

**Change**: `unify-ficha-pendientes`
**Proposal**: `openspec/changes/unify-ficha-pendientes/proposal.md`

---

## Executive Summary

Cliente and Propiedad ficha pages currently display pendientes as flat lists with duplicated concepto logic, inconsistent estado colors, static empty states, and full-page reloads after payment. This change restructures both ficha pendientes sections to match the dashboard's grouped-by-propiedad card pattern with role-based columns (arrendador/arrendatario/corredor), adds the missing "incompleto" estado to the dashboard, replaces duplicated switch blocks with `CobroConceptoFormatter`, introduces scoped API endpoints for AJAX refresh, and unifies empty states and loading indicators across all three views. The approach is hybrid: initial server-rendered display for fast TTFB, AJAX-driven refresh after payment actions.

---

## 1. Functional Requirements

### 1.1 Ficha Cliente: Grouped Pendientes Display

| # | Requirement |
|---|-------------|
| FR-1.1 | The `pendientes.blade.php` component MUST render cobros grouped by `propiedad` (via `contrato.unidad.propiedad` or `cobro.Propiedad_id`). |
| FR-1.2 | Each propiedad group MUST display as a card with the propiedad `direccion` as the card header, linking to `/propiedad/ficha/{id}`. |
| FR-1.3 | Within each propiedad card, cobros MUST be bucketed by role: `arrendador`, `arrendatario`, `corredor` — matching the dashboard's `DashboardPendientesController` bucketing logic. |
| FR-1.4 | When a propiedad has **more than one unidad** with pendientes, each unidad MUST render as a nested sub-card inside the propiedad card, with `border-left` + padding to visually differentiate the nesting. |
| FR-1.5 | When a propiedad has **exactly one unidad**, the unidad sub-card MUST be omitted; cobros render directly under the propiedad card. |
| FR-1.6 | Desktop view (≥576px): render as a table with columns `Dirección` + one column per role that has cobros on the current page (dynamic column visibility, matching dashboard). |
| FR-1.7 | Mobile view (<576px): render as colored badge buttons (`btn-warning` for Pendiente, `btn-danger` for Vencido, `btn-info` for Incompleto) grouped under each propiedad card. |
| FR-1.8 | The `FichaClienteController::show()` MUST continue to provide initial `$pendientes` data via server-side pagination (`paginate(10)`). The Blade component renders the initial HTML from this data. |
| FR-1.9 | The `concepto` field for each cobro MUST be computed using `CobroConceptoFormatter::format($tipo, $fechaCobro)` instead of the existing switch block. |

### 1.2 Ficha Propiedad: Grouped Pendientes Display

| # | Requirement |
|---|-------------|
| FR-2.1 | The `pendientes-propiedad.blade.php` component MUST render cobros grouped by `unidad` (via `cobro.Unidad_id` or `contrato.unidad`). |
| FR-2.2 | Each unidad group MUST display as a card with the unidad `nombre` as the card header. |
| FR-2.3 | Within each unidad card, cobros MUST be bucketed by role: `arrendador`, `arrendatario`, `corredor`. |
| FR-2.4 | Desktop view (≥576px): render as a table with columns `Unidad` + one column per role that has cobros. |
| FR-2.5 | Mobile view (<576px): render as colored badge buttons grouped under each unidad card. |
| FR-2.6 | The `FichaPropiedadController::show()` MUST continue to provide initial `$pendientes` data via server-side pagination. |
| FR-2.7 | The `concepto` field MUST be computed using `CobroConceptoFormatter::format()` instead of the existing switch block. |

### 1.3 Dashboard: Add "Incompleto" Estado

| # | Requirement |
|---|-------------|
| FR-3.1 | `DashboardPendientesController::$estadosPendientes` MUST include `'Incompleto'` alongside `'Pendiente'` and `'Vencido'`. |
| FR-3.2 | The dashboard JS `renderCobros()` already handles `Incompleto → btn-info` (line 177). No JS changes needed for color mapping. |
| FR-3.3 | The dashboard counter (`#contador-pendientes`) and pagination MUST reflect the additional cobros from the "incompleto" estado. |

### 1.4 Unify Estado Color Mapping

| # | Requirement |
|---|-------------|
| FR-4.1 | All three views MUST use the same estado → color mapping: `Pendiente → warning` (yellow), `Vencido → danger` (red), `Incompleto → info` (blue). |
| FR-4.2 | The mapping MUST be centralized: CSS classes `badge-pendiente`, `badge-vencido`, `badge-incompleto` OR a shared JS constant `ESTADO_COLORS = { Pendiente: 'warning', Vencido: 'danger', Incompleto: 'info' }`. |
| FR-4.3 | The existing inline `@php` color computation in both ficha components MUST be replaced with the centralized mapping. |

### 1.5 Replace Duplicated Switch Logic with `CobroConceptoFormatter`

| # | Requirement |
|---|-------------|
| FR-5.1 | `FichaClienteController::show()` MUST replace the switch block (lines 67-98) with `CobroConceptoFormatter::format($cobro->tipo, $cobro->fecha_cobro)`. |
| FR-5.2 | `FichaPropiedadController::show()` MUST replace the switch block (lines 66-97) with `CobroConceptoFormatter::format($cobro->tipo, $cobro->fecha_cobro)`. |
| FR-5.3 | If `$cobro->tipo` is null, the concepto MUST be `'Sin tipo'` (preserve existing behavior). |
| FR-5.4 | The `CobroConceptoFormatter` service MUST NOT be modified — it is considered stable and battle-tested. |

### 1.6 AJAX Refresh Endpoints for Fichas

| # | Requirement |
|---|-------------|
| FR-6.1 | Create `GET /api/cliente/{id}/pendientes` → `ClientePendientesController::index()`. |
| FR-6.2 | Create `GET /api/propiedad/{id}/pendientes` → `PropiedadPendientesController::index()`. |
| FR-6.3 | Both endpoints MUST support pagination via `?pagina=N&por_pagina=10` (default `por_pagina=10`). |
| FR-6.4 | Both endpoints MUST return JSON in the same shape as `DashboardPendientesController`: |
| | ```json |
| | { "data": [...], "total": N, "pagina": N, "por_pagina": N, "total_paginas": N } |
| | ``` |
| FR-6.5 | `ClientePendientesController` MUST group cobros by `propiedad`, then by `unidad` (if >1), then bucket by role. |
| FR-6.6 | `PropiedadPendientesController` MUST group cobros by `unidad`, then bucket by role. |
| FR-6.7 | Both endpoints MUST use `CobroConceptoFormatter::format()` for concepto computation. |
| FR-6.8 | Both endpoints MUST include all three estados: `pendiente`, `vencido`, `incompleto`. |
| FR-6.9 | Both endpoints MUST eager-load relationships: `participante_cobros.cliente`, `contrato.participante_contratos`, `contrato.unidad`, `servicio`. |

### 1.7 Replace `location.reload()` with AJAX Refresh

| # | Requirement |
|---|-------------|
| FR-7.1 | In `cliente.blade.php`, the `registrarPago()` function MUST replace `location.reload()` (line 66) with a call to `cargarFichaPendientes()` that fetches from `/api/cliente/{id}/pendientes` and re-renders the pendientes section. |
| FR-7.2 | In `propiedad.blade.php`, the `registrarPago()` function MUST replace `location.reload()` (line 65) with a call to `cargarFichaPendientes()` that fetches from `/api/propiedad/{id}/pendientes` and re-renders the pendientes section. |
| FR-7.3 | The refresh function MUST preserve the current pagination page after refresh. |
| FR-7.4 | The refresh function MUST show a loading indicator during the fetch (`showElLoading`). |

### 1.8 Unify Empty State to Animated SVG

| # | Requirement |
|---|-------------|
| FR-8.1 | Both ficha components MUST replace the static `<div class="alert alert-light border">` with the animated SVG empty state from the dashboard. |
| FR-8.2 | The empty state MUST use the same CSS animations: `fadeInUp`, `bounceIn`, `pulse` (defined in dashboard `@push('styles')`). |
| FR-8.3 | The empty state text MUST be context-appropriate: "No hay transacciones pendientes por el momento." |
| FR-8.4 | The empty state MUST be shown when `total === 0` on both initial render and AJAX refresh. |

### 1.9 Loading Indicators on All Fetch Calls

| # | Requirement |
|---|-------------|
| FR-9.1 | The ficha pendientes `<tbody>` MUST include a `.loading-placeholder` row (matching the convention in `AGENTS.md`). |
| FR-9.2 | All `fetch()` calls in ficha pendientes MUST be wrapped with `showElLoading(container, colspan)` before and `hideElLoading(container)` after (in both `.then()` and `.catch()`). |
| FR-9.3 | The `registrarPago()` button MUST show a spinner AND be disabled during the fetch. |
| FR-9.4 | The initial server-rendered page does NOT need a loading indicator (data is already present). |

---

## 2. Non-Functional Requirements

| # | Requirement |
|---|-------------|
| NFR-1 | **Performance**: Initial page load MUST NOT be slower than current. Server-rendered HTML is served first; AJAX is only for refresh. |
| NFR-2 | **Performance**: API endpoints MUST respond within 500ms for typical data sets (<100 pendientes). |
| NFR-3 | **Responsive**: Nested unidad cards MUST be visually distinct on mobile (≤576px) using `border-left: 3px solid var(--bs-border-color)` + `padding-left: 12px`. |
| NFR-4 | **Accessibility**: All loading indicators MUST include `role="status"` and `aria-live="polite"` (already in `showElLoading`). |
| NFR-5 | **Accessibility**: Empty state SVG MUST include `aria-hidden="true"` and text MUST be in a `<h3>` with proper heading hierarchy. |
| NFR-6 | **Accessibility**: Colored badge buttons MUST have sufficient color contrast (Bootstrap `btn-warning`, `btn-danger`, `btn-info` meet WCAG AA). |
| NFR-7 | **Maintainability**: Estado color mapping MUST be defined in one place (CSS or JS constant), not duplicated across components. |
| NFR-8 | **Security**: API endpoints MUST NOT expose sensitive data. Only return fields already exposed by `DashboardPendientesController`. |
| NFR-9 | **Compatibility**: All changes MUST work with Bootstrap 5.3 and the existing `app.js` utilities (`showElLoading`, `hideElLoading`, `formatCLP`). |
| NFR-10 | **No breaking changes**: Existing `#modalPrincipal` ("Revisar" desktop), `#modalCobro` (lightweight detail), and "Agregar cobro" MUST continue to work without modification. |

---

## 3. User Scenarios

### SC-1: Cliente Ficha Loads and Shows Grouped Propiedad Cards

**Given** a client with 2 propiedades, one with 3 pending cobros and another with 1 pending cobro
**When** the user navigates to `/cliente/ficha/{id}`
**Then** the pendientes section displays 2 propiedad cards
**And** the first card shows the propiedad address as header with 3 cobros bucketed by role
**And** the second card shows the other propiedad address with 1 cobro
**And** each cobro badge is colored according to its estado (warning/danger/info)

### SC-2: Cliente Ficha Mobile — Nested Unidad Cards Are Visually Distinct

**Given** a client with a propiedad that has 3 unidades, each with pending cobros
**When** the user views the ficha on a mobile device (≤576px)
**Then** the propiedad card shows 3 nested unidad sub-cards
**And** each unidad sub-card has a visible `border-left` accent and left padding
**And** cobro badges within each unidad are grouped under their respective unidad header

### SC-3: Propiedad Ficha Loads and Shows Grouped Unidad Cards

**Given** a propiedad with 2 unidades, each with pending cobros
**When** the user navigates to `/propiedad/ficha/{id}`
**Then** the pendientes section displays 2 unidad cards
**And** each card shows the unidad name as header with cobros bucketed by role
**And** clicking a cobro badge opens the `#modalCobro` lightweight detail modal

### SC-4: Dashboard Shows Incompleto Cobros

**Given** there are cobros with estado `Incompleto` in the database
**When** the user loads the dashboard `/`
**Then** the `DashboardPendientesController` includes `Incompleto` cobros in the response
**And** the dashboard displays these cobros with `btn-info` (blue) badges
**And** the pendientes counter includes propiedades with only `Incompleto` cobros

### SC-5: All Views Show Distinct State Colors

**Given** cobros with estados `Pendiente`, `Vencido`, and `Incompleto`
**When** the user views pendientes on any of the three views (dashboard, cliente ficha, propiedad ficha)
**Then** `Pendiente` cobros display with yellow/warning badges
**And** `Vencido` cobros display with red/danger badges
**And** `Incompleto` cobros display with blue/info badges
**And** the color mapping is consistent across all three views

### SC-6: Payment Modal Refreshes List Without Reload

**Given** the user is on a cliente ficha with pending cobros
**When** the user clicks a cobro badge, opens `#modalCobro`, and clicks "Registrar pago"
**And** the payment is successful
**Then** the success modal shows "El pago se ha registrado correctamente"
**And** the `#modalCobro` closes
**And** the pendientes section refreshes via AJAX (no full page reload)
**And** the paid cobro is no longer visible in the list
**And** a loading spinner appears during the refresh

---

## 4. Data Contracts

### 4.1 Existing Controller Data (Server-Rendered Initial Load)

**FichaClienteController::show()** currently provides:
```php
$pendientes = Cobro::query()
    ->with(['deudor.cliente', 'acreedor.cliente', 'contrato.unidad.propiedad', 'servicio'])
    ->whereHas('participante_cobros', fn($q) => $q->where('Cliente_id', $id))
    ->whereIn('estado', ['pendiente', 'vencido', 'incompleto'])
    ->latest('fecha_cobro')
    ->paginate(10, ['*'], 'pendientes_page');
```

**FichaPropiedadController::show()** currently provides:
```php
$pendientes = Cobro::query()
    ->with(['deudor.cliente', 'acreedor.cliente', 'contrato.unidad.propiedad', 'servicio'])
    ->where(/* 3 OR conditions for Propiedad_id */)
    ->whereIn('estado', ['pendiente', 'vencido', 'incompleto'])
    ->latest('fecha_cobro')
    ->paginate(10, ['*'], 'pendientes_page');
```

**After change**: Controllers MUST restructure `$pendientes` into grouped format for Blade:
```php
// Grouped structure for Blade initial render
$groupedPendientes = [
    [
        'id' => 1,              // propiedad_id (cliente ficha) or unidad_id (propiedad ficha)
        'direccion' => '...',   // propiedad direccion or unidad nombre
        'unidad_count' => 2,    // number of distinct unidades with pendientes (cliente ficha only)
        'unidades' => [         // only for cliente ficha when unidad_count > 1
            [
                'id' => 5,
                'nombre' => 'Unidad A',
                'arrendador' => [...],
                'arrendatario' => [...],
                'corredor' => [...],
            ],
        ],
        'arrendador' => [...],  // when unidad_count <= 1 (cliente ficha) or always (propiedad ficha)
        'arrendatario' => [...],
        'corredor' => [...],
    ],
];
```

### 4.2 New API Endpoint Response Shape

Both `GET /api/cliente/{id}/pendientes` and `GET /api/propiedad/{id}/pendientes` MUST return:

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
                    "arrendador": [
                        {
                            "id": 10,
                            "estado": "Pendiente",
                            "tipo": "Egreso Renta Arrendador",
                            "monto": 500000,
                            "deudor": "Juan Pérez",
                            "deudor_id": 3,
                            "acreedor": "María González",
                            "acreedor_id": 1,
                            "servicio_id": null,
                            "fecha_cobro": "2025-01-01T00:00:00+00:00",
                            "concepto": "Transferir renta enero 2025"
                        }
                    ],
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

**Cobro item fields** (each entry in role buckets):
| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Cobro ID |
| `estado` | string | Capitalized: `Pendiente`, `Vencido`, `Incompleto` |
| `tipo` | string | Raw cobro tipo from DB |
| `monto` | int|null | Amount in CLP |
| `deudor` | string | Deudor client name or "Desconocido" |
| `deudor_id` | int|null | Deudor client ID |
| `acreedor` | string | Acreedor client name or "Desconocido" |
| `acreedor_id` | int|null | Acreedor client ID |
| `servicio_id` | int|null | Associated service ID |
| `fecha_cobro` | string|null | ISO 8601 date |
| `concepto` | string | Formatted via `CobroConceptoFormatter` |

### 4.3 Pagination Parameters

| Parameter | Type | Default | Constraints |
|-----------|------|---------|-------------|
| `pagina` | int | 1 | `>= 1` |
| `por_pagina` | int | 10 | `1..100` |

---

## 5. Edge Cases

### EC-1: No Pendientes
- **Scenario**: Client or propiedad has zero pending cobros.
- **Behavior**: Show animated SVG empty state. Hide table. Counter shows 0.
- **API**: Returns `"data": [], "total": 0, "pagina": 1, "por_pagina": 10, "total_paginas": 1`.

### EC-2: Single Unidad Propiedad (Cliente Ficha)
- **Scenario**: A propiedad owned by the client has only 1 unidad with pendientes.
- **Behavior**: Do NOT render unidad sub-card. Cobros render directly under the propiedad card (flat within the card).
- **Rationale**: Avoid unnecessary nesting when there's only one child.

### EC-3: Cobro Without Unidad
- **Scenario**: A cobro has `Propiedad_id` but no `Unidad_id` and no `Contrato_id`.
- **Behavior**: Assign to an "Sin unidad" bucket within the propiedad card. Display as `Unidad: Sin unidad` on mobile.
- **API**: Include in `unidades` array with `id: null, nombre: 'Sin unidad'`.

### EC-4: Incompleto Estado Definition
- **Definition**: A cobro with `estado = 'incompleto'` (case-insensitive in DB, normalized to `Incompleto` in API/JS).
- **Dashboard**: Previously excluded; now included.
- **Fichas**: Already included; no behavior change, just consistent display.
- **Color**: `btn-info` (blue) — distinct from `Pendiente` (yellow) and `Vencido` (red).

### EC-5: Modal Conflicts
- **Scenario**: Ficha pages have both `#modalPrincipal` (desktop "Revisar") and `#modalCobro` (lightweight detail). Dashboard only has `#modalCobro`.
- **Mitigation**: No changes to modal IDs, event handlers, or `abrirModal()`. The `btn-cobro` click handler (delegated on `document`) opens `#modalCobro`. The "Revisar" button calls `abrirModal()` which opens `#modalPrincipal`. They coexist without conflict.

### EC-6: Pagination Boundary After Payment
- **Scenario**: User is on page 2 of pendientes. Pays the last cobro on page 2. After refresh, page 2 is empty.
- **Behavior**: Refresh should return to page 1 if the current page has no results. The API response includes `total_paginas`; JS should adjust `paginaActual` if `paginaActual > total_paginas`.

### EC-7: Cobro With Null fecha_cobro
- **Scenario**: A cobro has `fecha_cobro = null`.
- **Behavior**: `CobroConceptoFormatter::format()` returns the raw `$tipo` when `$fechaCobro` is null (existing behavior, line 31 of formatter).
- **Display**: Concepto shows the raw tipo string (e.g., "Reparación").

### EC-8: Client With No Propiedades
- **Scenario**: A client exists but owns no propiedades and has no contracts.
- **Behavior**: Empty state shows. "Agregar cobro" modal still works (but propiedad dropdown will be empty).

### EC-9: Concurrent Payment
- **Scenario**: Two users attempt to pay the same cobro simultaneously.
- **Behavior**: Handled by existing `PagarCobroController`. The second payment will fail with an error (cobro already paid). Error displays via `mostrarMensaje()`.

---

## 6. Acceptance Criteria

### AC-1: Cliente Ficha Grouped Display
- [ ] Cliente ficha shows pendientes grouped by propiedad cards
- [ ] Each propiedad card has a header with the propiedad address linking to `/propiedad/ficha/{id}`
- [ ] Cobros within each card are bucketed into arrendador/arrendatario/corredor columns
- [ ] Multi-unidad propiedades show nested unidad sub-cards with `border-left` + padding
- [ ] Single-unidad propiedades show cobros directly (no nesting)

### AC-2: Propiedad Ficha Grouped Display
- [ ] Propiedad ficha shows pendientes grouped by unidad cards
- [ ] Each unidad card has a header with the unidad name
- [ ] Cobros within each card are bucketed into arrendador/arrendatario/corredor columns

### AC-3: Dashboard Incompleto
- [ ] Dashboard API returns cobros with estado `Incompleto`
- [ ] Dashboard displays `Incompleto` cobros with blue/info badges
- [ ] Dashboard counter includes propiedades with only `Incompleto` cobros

### AC-4: Consistent Estado Colors
- [ ] `Pendiente` → yellow/warning in all three views
- [ ] `Vencido` → red/danger in all three views
- [ ] `Incompleto` → blue/info in all three views
- [ ] Color mapping is defined in one place (CSS class or JS constant)

### AC-5: CobroConceptoFormatter Usage
- [ ] `FichaClienteController` has NO switch block for concepto computation
- [ ] `FichaPropiedadController` has NO switch block for concepto computation
- [ ] Both controllers call `CobroConceptoFormatter::format()`
- [ ] Null tipo produces `'Sin tipo'`
- [ ] Null fecha_cobro produces raw tipo string

### AC-6: API Endpoints
- [ ] `GET /api/cliente/{id}/pendientes` returns grouped JSON with pagination
- [ ] `GET /api/propiedad/{id}/pendientes` returns grouped JSON with pagination
- [ ] Both endpoints use `CobroConceptoFormatter`
- [ ] Both endpoints include all three estados
- [ ] Both endpoints support `?pagina=N&por_pagina=N` parameters

### AC-7: AJAX Refresh After Payment
- [ ] `cliente.blade.php` `registrarPago()` does NOT call `location.reload()`
- [ ] `propiedad.blade.php` `registrarPago()` does NOT call `location.reload()`
- [ ] Payment success triggers AJAX refresh of pendientes section
- [ ] Loading spinner appears during refresh
- [ ] Pagination is preserved or adjusted if page becomes empty

### AC-8: Empty State
- [ ] Both ficha components show animated SVG when no pendientes
- [ ] Empty state uses same CSS animations as dashboard (`fadeInUp`, `bounceIn`, `pulse`)
- [ ] Empty state is hidden when pendientes exist

### AC-9: Loading Indicators
- [ ] Ficha pendientes `<tbody>` includes `.loading-placeholder` row
- [ ] `.loading-placeholder` is removed by `DOMContentLoaded` listener in `app.js`
- [ ] All `fetch()` calls in ficha pendientes use `showElLoading`/`hideElLoading`
- [ ] Payment button shows spinner and is disabled during fetch

### AC-10: Existing Features Preserved
- [ ] "Revisar" button opens `#modalPrincipal` with full CRUD detail
- [ ] Mobile cobro badges open `#modalCobro` with lightweight summary
- [ ] "Agregar cobro" button opens create modal
- [ ] Server-side pagination (`$pendientes->links()`) works on initial load
- [ ] No JavaScript errors in browser console

---

## 7. Affected Files Summary

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Http/Controllers/Api/DashboardPendientesController.php` | Modified | Add `'Incompleto'` to `$estadosPendientes` |
| `app/Http/Controllers/Api/ClientePendientesController.php` | **New** | API endpoint for grouped cliente pendientes |
| `app/Http/Controllers/Api/PropiedadPendientesController.php` | **New** | API endpoint for grouped propiedad pendientes |
| `app/Http/Controllers/Vistas/FichaClienteController.php` | Modified | Replace switch with `CobroConceptoFormatter`; restructure `$pendientes` for grouped display |
| `app/Http/Controllers/Vistas/FichaPropiedadController.php` | Modified | Replace switch with `CobroConceptoFormatter`; restructure `$pendientes` for grouped display |
| `resources/views/components/pendientes.blade.php` | Modified | Grouped layout: propiedad cards, unidad sub-cards, role columns, estado colors, empty state |
| `resources/views/components/pendientes-propiedad.blade.php` | Modified | Grouped layout: unidad cards, role columns, estado colors, empty state |
| `resources/views/dashboard/index.blade.php` | Verified | Confirm `Incompleto` renders correctly (JS already handles it) |
| `routes/api.php` | Modified | Add two new API routes |
| `resources/views/cliente.blade.php` | Modified | Replace `location.reload()` with AJAX refresh; add `cargarFichaPendientes()` |
| `resources/views/propiedad.blade.php` | Modified | Replace `location.reload()` with AJAX refresh; add `cargarFichaPendientes()` |
| `public/assets/js/app.js` | Unchanged | `showElLoading`/`hideElLoading` already available |

---

## 8. Out of Scope (Explicitly Excluded)

- Refactoring the full ficha page layout (only pendientes section changes)
- Creating a shared Blade `<x-pendientes-table>` component (future iteration)
- Adding "Agregar cobro" to the dashboard (stays ficha-only)
- Changing the desktop "Revisar" modal (`#modalPrincipal`) behavior
- Changing Cobro model, estado definitions, or database schema
- Modifying `CobroConceptoFormatter` service (considered stable)
- Changing transacciones section or reparaciones/contratos sub-pages
