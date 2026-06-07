# Design: Ajustes Padding Cards Mobile

## Technical Approach

Add a single `.content` mobile padding rule to `style.css`, then restructure both ficha pendientes components to render colored estado badges on mobile (matching dashboard's `btn-cobro` pattern) while preserving the existing 2-column desktop table. Share the `#modalCobro` markup and click handler by duplicating the proven dashboard pattern into both ficha views via `@push('scripts')`.

## Architecture Decisions

| Decision | Option A | Option B | Choice | Rationale |
|----------|----------|----------|--------|-----------|
| Mobile padding | CSS rule on `.content` | Add `px-3` to every `<main>` in blade | CSS rule | One change, zero blade modifications, mirrors dashboard `container-fluid` gutters |
| Mobile card rendering | Conditional blade blocks (`d-sm-none` / `d-none d-sm-table-row`) | JS transforms table to cards at runtime | Conditional blade | Server-rendered, no FOUC, follows existing `table-card-mobile` convention |
| Modal sharing | Duplicate `#modalCobro` + handler in each ficha | Extract to shared partial/component | Duplicate | Dashboard handler is page-specific (inline `@push('scripts')`), no shared JS module exists yet; extraction is out of scope |
| Cobro data serialization | Inline `@json()` in `data-cobro` attribute | New API endpoint for ficha cobros | Inline `@json()` | Cobro models already loaded by controller; no extra round-trip needed |
| Estado color mapping | Blade `@php` helper per cobro | CSS class on `<tr>` | Blade inline | Matches dashboard's `renderCobros()` JS pattern; each cobro gets its own colored button |

## Data Flow

```
Controller (FichaPropiedad/FichaCliente)
    │
    ├─ baseQuery() → Cobro::with(['deudor.cliente', 'acreedor.cliente', ...])
    │     └─ whereIn('estado', ['pendiente', 'vencido', 'incompleto'])
    │     └─ paginate(10)
    │
    ├─ foreach $pendientes → set $cobro->concepto (switch on tipo)
    │
    └─ view('propiedad' / 'cliente')
          │
          └─ @include('components/pendientes-propiedad' / 'pendientes')
                │
                ├─ Desktop: <table> (unchanged, d-none d-sm-table-row)
                │     └─ <tr> → Concepto + "Revisar" button
                │
                └─ Mobile: <div> badges (d-sm-none)
                      └─ foreach $cobro → btn btn-{color} data-cobro='@json(...)'
                            │
                            └─ Click → JS handler → populate #modalCobro → bootstrap.Modal.show()
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `public/assets/css/style.css` | Modify | Add `.content` mobile padding rule at `@media (max-width: 575.98px)`; add ficha pendientes card overrides (border, shadow, badge stacking) scoped to a new class |
| `resources/views/components/pendientes-propiedad.blade.php` | Modify | Wrap existing table in `d-none d-sm-table-row`; add mobile badge block (`d-sm-none`) with colored `btn-cobro` buttons carrying `data-cobro` JSON |
| `resources/views/components/pendientes.blade.php` | Modify | Same structural change as pendientes-propiedad |
| `resources/views/propiedad.blade.php` | Modify | Add `#modalCobro` markup + `@push('scripts')` with cobro click handler + `registrarPago()` |
| `resources/views/cliente.blade.php` | Modify | Same `#modalCobro` + handler as propiedad |

## Interfaces / Contracts

### Cobro JSON payload (data-cobro attribute)

Each mobile badge button carries this JSON structure:

```json
{
  "id": 42,
  "concepto": "Cobrar Renta Juan Pérez",
  "tipo": "Ingreso Renta Arrendatario",
  "estado": "Pendiente",
  "monto": 350000,
  "fecha_cobro": "2025-07-01T00:00:00.000000Z",
  "deudor": "Juan Pérez",
  "deudor_id": 5,
  "acreedor": "María López",
  "acreedor_id": 3,
  "servicio_id": null
}
```

### Estado → Bootstrap color mapping

| estado | Bootstrap class |
|--------|----------------|
| Pendiente | `btn-warning` |
| Vencido | `btn-danger` |
| Incompleto | `btn-info` |

### Modal body template

The `#modalCobro` body is populated by JS on click:

```html
<p><b>Tipo de cobro:</b> ${cobro.tipo}</p>
<p><b>Deudor:</b> <a href="/cliente/ficha/${cobro.deudor_id}">${cobro.deudor}</a></p>
<p><b>Acreedor:</b> <a href="/cliente/ficha/${cobro.acreedor_id}">${cobro.acreedor}</a></p>
<p><b>Monto:</b> $350.000</p>
<p><b>Fecha de pago:</b> 1 de julio de 2025</p>
```

### CSS: ficha pendientes mobile card overrides

Scoped to `.ficha-pendientes-mobile` class on the table:

```css
@media (max-width: 575.98px) {
  /* .content horizontal padding */
  .content { padding-left: 1rem; padding-right: 1rem; }

  /* Ficha pendientes card style matching dashboard */
  .table-card-mobile.ficha-pendientes-mobile tbody tr {
    border: 2px solid #adb5bd;
    margin-bottom: 16px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
  }
  .table-card-mobile.ficha-pendientes-mobile .btn-cobro {
    font-size: 0.85rem;
    padding: 0.5rem 0.75rem;
    display: block;
    width: 100%;
  }
}
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Visual (manual) | Mobile padding on ficha pages (≤575.98px) | Resize browser, verify no edge-to-edge content |
| Visual (manual) | Desktop table unchanged (≥576px) | Verify 2-column table with "Revisar" button renders normally |
| Visual (manual) | Mobile badges show correct colors | Pendiente=warning, Vencido=danger, Incompleto=info |
| Visual (manual) | Modal opens with correct data | Tap badge → verify tipo, deudor, acreedor, monto, date |
| Visual (manual) | "Registrar pago" button works | Verify navigates to cobro payment page |
| Visual (manual) | Dashboard unaffected | Verify dashboard pendientes still works identically |
| Regression | `labelTable()` MutationObserver still runs | Verify existing `table-card-mobile` behavior on other tables |

## Migration / Rollout

No migration required. This is a pure frontend change — CSS + Blade template modifications only. No database changes, no new routes, no new controllers.

## Open Questions

- None
