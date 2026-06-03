# Proposal: Dashboard Pendientes Fix

## Intent

The "Pendientes" dashboard is broken: cobros created by `CrearAdministracionService` lack `Propiedad_id` and `Unidad_id`, so the dashboard query (`whereNotNull('Propiedad_id')`) filters them all out, showing an empty table. Additionally, the "Registrar pago" button calls `POST /api/cobro/pagar` which 404s because the route and controller don't exist. This change fixes both the data integrity bug and the missing payment endpoint.

## Scope

### In Scope
- Fix `CrearAdministracionService::createCobroPair()` to set `Propiedad_id` and `Unidad_id` on every Cobro (application-level only, no DB schema changes)
- Create `POST /api/cobro/pagar` route, controller, and business logic (reusable across dashboard and client view)
- Refactor `DashboardPendientesController` query for efficiency while keeping property-level pagination
- Ensure only `Pendiente` and `Vencido` cobros appear in dashboard index

### Out of Scope
- Migrating existing cobros with null FKs
- Dashboard UI/blade changes
- Buscador or other unrelated features
- "Incompleto" estado handling (deferred to future version)
- DB schema changes or constraints
- Multi-user concurrency handling (single-user system)

## Capabilities

### New Capabilities
- `cobro-payment`: Register a payment (cobro → Pagado) via `POST /api/cobro/pagar`, creating a Transaccion and TransaccionCobro pivot, updating cobro estado. Reusable from dashboard and client view.

### Modified Capabilities
- `administracion-wizard`: Cobro creation MUST include `Propiedad_id` and `Unidad_id` at application level (no DB schema changes), so dashboard grouping works for newly created cobros.

## Approach

1. **Bug fix**: Pass `$propiedad->id` and `$unidad->id` (already available in `crearAdministracion`) into `createCobros` → `createCobroPair`, and include them in `Cobro::create()`.
2. **Payment endpoint**: Create `PagarCobroController` with a `pagar` method. Accept `{cobro_id, monto}`. Validate cobro exists and is in a payable state. Create a `Transaccion` (with Origen/Destino), attach via `TransaccionCobro` with `monto_pagado`, and set cobro `estado` to `'Pagado'`. Register route `POST /api/cobro/pagar`.
3. **Dashboard query refactor**: Refactor `DashboardPendientesController` to load properties and cobros efficiently with eager loading while keeping property-level pagination. Only `Pendiente` and `Vencido` cobros should be returned.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Services/CrearAdministracionService.php` | Modified | Add Propiedad_id, Unidad_id to createCobroPair |
| `app/Http/Controllers/Api/PagarCobroController.php` | New | Payment endpoint controller |
| `routes/api.php` | Modified | Add `POST /api/cobro/pagar` route |
| `app/Http/Requests/PagarCobroRequest.php` | New | Validation for payment request |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Payment logic allows paying already-paid cobros | Medium | Validate estado in PagarCobroRequest; only allow 'Pendiente','Vencido' |
| Breaking createCobroPair signature for other callers | Low | Method is protected; review all internal callers before changing signature |
| Query performance degrades with many properties | Low | Refactor to single query with eager loading while keeping property-level pagination |
| No DB schema changes allowed limits data integrity | Low | Keep FKs nullable in DB; enforce at application level in creation service |

## Rollback Plan

Revert the `Propiedad_id`/`Unidad_id` additions from `createCobroPair`, remove `PagarCobroController` and its route. Existing cobros remain unchanged.

## Dependencies

- `transaccion` and `transaccion_cobro` tables must support the payment flow (already exist per model defs)
- `Origen_Transaccion` and `Destino_Transaccion` seed data for payment origins/destinations

## Success Criteria

- [ ] Cobros created via administracion wizard have `Propiedad_id` and `Unidad_id` populated (no DB schema changes)
- [ ] `POST /api/cobro/pagar` returns 200 and sets cobro estado to `Pagado`, creates Transaccion + TransaccionCobro
- [ ] Dashboard pendientes table displays only `Pendiente` and `Vencido` cobros, grouped by property with pagination
- [ ] Payment endpoint rejects already-paid cobros or cobros in invalid states with 422
- [ ] Client view can reuse the same payment endpoint for its "revisar" button