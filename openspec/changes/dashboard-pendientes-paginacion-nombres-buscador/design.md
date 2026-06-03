# Design: Dashboard Pendientes — Pagination, Display Names, and Buscador Links

## Technical Approach

Backend-driven `concepto` computation via a new `CobroConceptoFormatter` service class. The pendientes API (`DashboardPendientesController`) adds `fecha_cobro` and `concepto` to each cobroData object. The dashboard view renders `concepto` instead of raw `tipo`, changes `POR_PAGINA` from 10 to 5, and fixes property links to `/propiedad/ficha/{id}`. The buscador API updates URL strings for propiedad and cliente results. A new placeholder route is added for `/propiedad/ficha/{id}`.

## Architecture Decisions

### Decision: Extract concepto formatting into a dedicated service class

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Inline in `DashboardPendientesController` | Fast, but duplicates logic if other controllers need it | Rejected |
| Reuse `FichaClienteController` switch | Different format (names vs dates), tightly coupled to Eloquent models | Rejected |
| New `CobroConceptoFormatter` service | Extra file, but reusable, testable, follows existing service pattern | **Chosen** |

**Rationale**: The project already uses service classes (`CobroRelationshipResolver`, `CrearAdministracionService`). The `FichaClienteController` concept logic is fundamentally different — it appends debtor/creditor names, not month/year from `fecha_cobro`. A shared helper would need branching per context. A dedicated formatter with a simple static method `format(tipo, fecha_cobro): string` is clean and testable.

### Decision: Use Carbon's `translatedFormat('F')` for Spanish month names

**Choice**: `Carbon::parse($fecha_cobro)->locale('es')->translatedFormat('F')` produces lowercase Spanish month names (enero, febrero, etc.).

**Rationale**: The app already uses Carbon with locale support (seen in `FichaClienteController` line 315). No custom month mapping needed.

### Decision: Placeholder route for `/propiedad/ficha/{id}`

**Choice**: Add a closure route returning a simple "coming soon" view. No new controller class.

**Rationale**: No `PropiedadController` exists for views. The user confirmed the ficha view will be created separately. A closure avoids boilerplate.

## Data Flow

```
Browser (dashboard/index.blade.php)
  │
  │ fetch(`/api/dashboard/pendientes?pagina=1&por_pagina=5`)
  ▼
DashboardPendientesController::index()
  │
  ├── Fetch paginated property IDs with pending cobros
  ├── For each property, load cobros with relationships
  ├── For each cobro, build cobroData:
  │     ├── tipo, monto, estado, deudor, acreedor, servicio_id
  │     ├── fecha_cobro (ISO string)
  │     └── concepto = CobroConceptoFormatter::format(tipo, fecha_cobro)
  └── Return JSON: { data: [...], total, pagina, por_pagina, total_paginas }
        │
        ▼
  View JS: renderCobros() uses c.concepto for button text
           property link → `/propiedad/ficha/${item.id}`

Buscador flow (separate):
  Browser → fetch(`/api/dashboard/buscador?q=...`)
  DashboardBuscadorController::search()
    → returns url: '/propiedad/ficha/{id}' or '/cliente/ficha/{id}'
  buscador.js navigates to item.url unchanged
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Services/CobroConceptoFormatter.php` | **Create** | Static `format(string $tipo, ?Carbon $fechaCobro): string` — applies rename rules |
| `app/Http/Controllers/Api/DashboardPendientesController.php` | Modify | Add `fecha_cobro` to cobroData; call `CobroConceptoFormatter::format()` for `concepto` |
| `app/Http/Controllers/Api/DashboardBuscadorController.php` | Modify | Change URL strings: `/propiedad/` → `/propiedad/ficha/`, `/cliente/` → `/cliente/ficha/` |
| `resources/views/dashboard/index.blade.php` | Modify | `POR_PAGINA` 10→5; `${c.tipo}` → `${c.concepto}`; link `/propiedad/${item.id}` → `/propiedad/ficha/${item.id}` |
| `routes/web.php` | Modify | Add `Route::get('/propiedad/ficha/{id}', ...)` placeholder route |

## Interfaces / Contracts

### CobroConceptoFormatter

```php
namespace App\Services;

use Carbon\Carbon;

class CobroConceptoFormatter
{
    /**
     * Compute a human-readable concepto from cobro tipo and fecha_cobro.
     *
     * @param string       $tipo        Raw cobro tipo from DB
     * @param Carbon|null  $fechaCobro  Cobro date (may be null)
     * @return string                   Display name
     */
    public static function format(string $tipo, ?Carbon $fechaCobro): string;
}
```

### API Response Shape (unchanged envelope, new fields per cobro)

```json
{
  "data": [
    {
      "id": 1,
      "direccion": "Av. Providencia 1234",
      "arrendador": [
        {
          "id": 10,
          "estado": "Pendiente",
          "tipo": "Egreso Renta Arrendador",
          "monto": 500000,
          "deudor": "Juan Pérez",
          "deudor_id": 5,
          "acreedor": "María López",
          "acreedor_id": 3,
          "servicio_id": null,
          "fecha_cobro": "2025-01-15T00:00:00.000000Z",
          "concepto": "Transferir renta enero 2025"
        }
      ],
      "arrendatario": [],
      "corredor": []
    }
  ],
  "total": 12,
  "pagina": 1,
  "por_pagina": 5,
  "total_paginas": 3
}
```

### Concepto Format Rules (implemented in `CobroConceptoFormatter`)

| `tipo` | `concepto` |
|--------|-----------|
| `Ingreso Renta Arrendatario` | `"Cobrar renta {mes} {año}"` |
| `Egreso Renta Arrendador` | `"Transferir renta {mes} {año}"` |
| `Comision inicial arrendador` / `Comision inicial arrendatario` | `"Comisión inicial"` |
| `Ingreso Garantía Arrendatario` | `"Cobrar garantía"` |
| `Egreso Garantía Arrendador` | `"Transferir garantía"` |
| `Luz`, `Agua`, `Gas`, `Gastos comunes` | `"{tipo} {mes} {año}"` |
| Any other | Raw `tipo` (fallback) |

If `fecha_cobro` is null, all date-dependent rules fall back to raw `tipo`.

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | `CobroConceptoFormatter::format()` for each tipo variant + null fecha_cobro | PHPUnit data provider with all 7+ cases |
| Unit | Buscador URL pattern changes | Assert response `url` field contains `/ficha/` |
| Integration | Pendientes API returns `concepto` and `fecha_cobro` fields | Feature test hitting `/api/dashboard/pendientes` |
| Manual | Dashboard shows 5 items per page, concepto labels, ficha links | Browser test |

## Migration / Rollout

No migration required. All changes are additive to the API response (new `concepto` and `fecha_cobro` fields). The `/propiedad/ficha/{id}` route returns a placeholder until the real view is built.

## Open Questions

- [ ] Should the placeholder `/propiedad/ficha/{id}` route redirect to an existing propiedad index, or return a "coming soon" view? (Design assumes "coming soon" view — adjust if user prefers redirect.)
