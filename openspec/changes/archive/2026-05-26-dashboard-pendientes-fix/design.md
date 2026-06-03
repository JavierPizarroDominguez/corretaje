# Technical Design: Dashboard Pendientes Fix

## Technical Approach

Three coordinated changes:

### 1. Bug Fix — Cobro FK Population
Thread `$propiedad->id` and `$unidad->id` from `crearAdministracion()` through `createCobros()` into `createCobroPair()`, and include them in the `Cobro::create()` call. Both values are already resolved in the parent method before `createCobros` is called — no new lookups needed.

### 2. New Feature — Payment Endpoint
Create `PagarCobroController` with a `pagar()` method behind `POST /api/cobro/pagar`. Accepts `{cobro_id}` only. Validates cobro exists and is in `Pendiente` or `Vencido` estado. Resolves `monto` from `cobro->monto`, and `deudor_id`/`acreedor_id` from `cobro->participante_cobros` (all backend-side). Creates a `Transaccion` (with Origen/Destino resolved from cobro participants), links via `TransaccionCobro` pivot with `monto_pagado = cobro->monto`, and updates cobro `estado` to `Pagado`. All within a `DB::transaction()`.

### 3. Dashboard Query Refactor
Remove `Incompleto` from estados filter (v1 scope). Replace the N+1 property loop with a single eager-loaded query: fetch all cobros with their participante_cobros and cliente relationships in one query, then group in PHP by Propiedad_id. Manual pagination on the grouped result.

## Architecture Decisions

### Decision 1: Inline payment logic in controller vs separate service
**Choice:** Inline in `PagarCobroController::pagar()` (with DB::transaction).
**Alternative:** Extract to `PagarCobroService`.
**Rationale:** The payment flow is a single atomic operation with ~5 lines of business logic. A separate service class adds indirection without meaningful reuse. If partial payments or complex rules are added later, extraction is trivial.

### Decision 2: Origen/Destino resolution strategy
**Choice:** Resolve OrigenTransaccion and DestinoTransaccion by `Cliente_id` + `tipo` lookup at payment time. If not found, create them on-the-fly within the transaction.
**Alternative:** Require fixed seed IDs passed in the request.
**Rationale:** The `OrigenTransaccion` and `DestinoTransaccion` tables are metadata layers linking a `Cliente_id` to a transaction type. The deudor becomes the Origen (who pays), the acreedor becomes the Destino (who receives). Looking up by `Cliente_id` keeps the endpoint simple — only `cobro_id` and `monto` are needed.

### Decision 3: Dashboard grouping in PHP vs SQL
**Choice:** Fetch all pending cobros in one query with eager loading, then group by `Propiedad_id` in PHP.
**Alternative:** SQL GROUP BY with JSON aggregation.
**Rationale:** The existing response structure nests cobros by role bucket (arrendador/arrendatario/corredor) per property. PHP grouping is simpler to maintain and matches the existing output format. With eager loading, this is 2 queries total regardless of data volume.

## Data Flow

```
Payment Flow:
  Client
    │ POST /api/cobro/pagar {cobro_id, monto}
    ▼
  PagarCobroController::pagar()
    │ 1. Validate request (PagarCobroRequest)
    │ 2. Find Cobro (404 if missing)
    │ 3. Validate estado in [Pendiente, Vencido] (422 if not)
    │ 4. Resolve deudor → OrigenTransaccion
    │ 5. Resolve acreedor → DestinoTransaccion
    ▼
  DB::transaction {
    │ 6. Create Transaccion(monto, fecha, Origen, Destino)
    │ 7. Create TransaccionCobro(Transaccion_id, Cobro_id, monto_pagado)
    │ 8. Update Cobro.estado = 'Pagado' (user-confirmed requirement)
  }
    │
    ▼
  Response 200: {transaccion_id, cobro_estado: "Pagado"}

Dashboard Flow:
  Client
    │ GET /api/dashboard/pendientes?pagina=1&por_pagina=10
    ▼
  DashboardPendientesController::index()
    │ 1. Query: Cobro::whereIn(estado, ['Pendiente','Vencido'])
    │           ->whereNotNull('Propiedad_id')
    │           ->with(['participante_cobros.cliente', 'contrato.participante_contratos'])
    │ 2. Group results by Propiedad_id in PHP
    │ 3. Paginate the grouped properties
    │ 4. Build response: property → {arrendador[], arrendatario[], corredor[]}
    ▼
  Response 200: {data: [...], total, pagina, por_pagina, total_paginas}
```

## File Changes

| Action | File | Description |
|--------|------|-------------|
| Modify | `app/Services/CrearAdministracionService.php` | Add `$propiedadId`, `$unidadId` params to `createCobros()` and `createCobroPair()`; include in `Cobro::create()` |
| Create | `app/Http/Controllers/Api/PagarCobroController.php` | New controller with `pagar()` method |
| Create | `app/Http/Requests/PagarCobroRequest.php` | FormRequest validation for `cobro_id` (required, integer, exists:cobro,id) + custom validation that cobro is in 'Pendiente' or 'Vencido' estado |
| Modify | `routes/api.php` | Add `POST /api/cobro/pagar` route in `[GEN:START:dashboard_api]` block |
| Modify | `app/Http/Controllers/Api/DashboardPendientesController.php` | Refactor query: remove `Incompleto`, single eager-loaded query, PHP grouping |

## Interfaces / Contracts

### POST /api/cobro/pagar

**Request Body:**
```json
{
  "cobro_id": 42
}
```
Note: `monto` is resolved from `cobro->monto` server-side. `deudor_id`, `acreedor_id`, and `servicio_id` are also resolved from the cobro's relationships, not passed in the request.

**Success Response (200):**
```json
{
  "transaccion_id": 15,
  "cobro_estado": "Pagado"
}
```

**Error Responses:**
- `404` — Cobro not found: `{"message": "Cobro no encontrado"}`
- `422` — Invalid estado: `{"errors": {"cobro_id": ["El cobro no se encuentra en estado pendiente o vencido"]}}`
- `422` — Validation: `{"errors": {"cobro_id": ["El campo cobro_id es obligatorio"]}}`

### GET /api/dashboard/pendientes

**Query Params:** `pagina` (int, default 1), `por_pagina` (int, default 10, max 100)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "direccion": "Av. Italia 1234",
      "arrendador": [{"id", "estado", "tipo", "monto", "deudor", "deudor_id", "acreedor", "acreedor_id", "servicio_id"}],
      "arrendatario": [...],
      "corredor": [...]
    }
  ],
  "total": 5,
  "pagina": 1,
  "por_pagina": 10,
  "total_paginas": 1
}
```

## Testing Strategy

Strict TDD — tests first, then implementation.

### Tests for Cobro FK Fix
- `CrearAdministracionServiceTest::cobros_have_propiedad_and_unidad_ids_on_creation` — verify all Cobro rows have non-null FKs after `crearAdministracion()`
- `CrearAdministracionServiceTest::cobros_have_correct_fk_values_when_entities_reused` — verify FKs match existing Propiedad/Unidad when reused

### Tests for Payment Endpoint
- `PagarCobroControllerTest::pays_pendiente_cobro_successfully` — full flow: Transaccion + TransaccionCobro created, estado = Pagado
- `PagarCobroControllerTest::pays_vencido_cobro_successfully` — same for Vencido
- `PagarCobroControllerTest::rejects_already_paid_cobro` — 422 response
- `PagarCobroControllerTest::rejects_anulado_cobro` — 422 response
- `PagarCobroControllerTest::returns_404_for_nonexistent_cobro`
- `PagarCobroControllerTest::validates_missing_cobro_id` — 422

### Tests for Dashboard Refactor
- `DashboardPendientesControllerTest::only_pendiente_and_vencido_in_results` — Pagado/Anulado/Incompleto excluded
- `DashboardPendientesControllerTest::paginates_by_property` — correct pagination behavior
- `DashboardPendientesControllerTest::cobros_grouped_by_role_bucket` — arrendador/arrendatario/corredor buckets correct

**Test runner:** `php artisan test`

## Migration / Rollout

No database migrations required. All changes are application-level.

**Rollout order:**
1. FK population fix (lowest risk, fixes root cause)
2. Payment endpoint (new functionality, no existing behavior changed)
3. Dashboard refactor (modifies existing endpoint — test thoroughly)

**Rollback:** Revert commits in reverse order. No data migration needed since existing cobros with null FKs are unaffected.

## Open Questions

All open questions resolved based on analysis of original PHP code (`pagar-cobro.php`) and user confirmation:

| # | Question | Resolution |
|---|----------|------------|
| 1 | Origen/Destino `tipo` values | **Resolved**: Origen always `tipo='Cuenta Bancaria'`, `Cliente_id=deudor`, `Cuenta_Bancaria_id=null`. Destino: `tipo='Cuenta Bancaria'` if `Servicio_id=null` else `tipo='Empresa de servicio'`. |
| 2 | Partial payments / monto in request | **Out of scope v1**. Original pays full `cobro.monto` only. Request only needs `cobro_id`; `monto` resolved server-side. |
| 3 | Comprobante upload | **Out of scope v1**. `url_comprobante` field exists in `Transaccion` but unused in original. |
| 4 | `Incompleto` in dashboard | **Resolved**: Excluded per user requirement. Only `Pendiente` and `Vencido` shown. |
| 5 | Update cobro estado on payment | **Resolved**: User confirmed estado MUST change to `'Pagado'` after creating Transaccion + TransaccionCobro. |

### OrigenTransaccion / DestinoTransaccion Resolution Detail

From original `pagar-cobro.php`:

**OrigenTransaccion** (the payer / deudor):
```php
$tipo = 'Cuenta Bancaria';
$cuenta_bancaria_id = null;
// Upsert by (tipo, Cliente_id, Cuenta_Bancaria_id)
```

**DestinoTransaccion** (the receiver / acreedor):
```php
if ($servicio_id === null) {
    $tipo = 'Cuenta Bancaria';      // Cliente_id = acreedor_id, Servicio_id = null
} else {
    $tipo = 'Empresa de servicio';  // Servicio_id = servicio_id, Cliente_id = null
}
```

**Laravel implementation**: Use `firstOrCreate()` with the exact composite keys above. The endpoint resolves `deudor_id` and `acreedor_id` from `cobro->participante_cobros` rather than trusting frontend input, making it more robust than the original.
