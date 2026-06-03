# Design: Separar Reparaciones, Cartolas y Contratos de la Ficha de Cliente

## Technical Approach

Extract 3 data-heavy query blocks from `FichaClienteController@show()` into 2 new controller methods (`reparaciones()`, `contratos()`) plus a shared private `baseQuery($id)` helper. Create 2 new Blade views under `resources/views/cliente/` that reuse existing `@include` components unchanged. Add 2 named routes. Slim `show()` to keep only pendientes, transacciones, options, and tiposCobroDisponibles.

## Architecture Decisions

| Decision | Option A (chosen) | Option B (rejected) | Rationale |
|----------|-------------------|---------------------|-----------|
| Query extraction | Private `baseQuery($id)` on same controller | New dedicated controller | Keeps all cliente-ficha logic together; no new class needed |
| View location | `resources/views/cliente/reparaciones.blade.php` | Flat `resources/views/` | Follows existing `cliente/` namespace pattern |
| Component reuse | `@include` without modification | Refactor components for standalone | Zero-risk; components already accept isolated variables |
| Pagination params | Default `page` on dedicated pages | Keep `reparaciones_page` etc. | Dedicated pages don't need namespacing; simpler URLs |
| Data grouping (reparaciones page) | Single controller method returns both `$reparaciones` + `$cartola` | Split into 2 pages | Proposal groups them; 1 route, 1 view, 2 components |

## Data Flow

### Reparaciones Page

```
GET /cliente/{id}/reparaciones
    │
    ├─ FichaClienteController@reparaciones($id)
    │   ├─ baseQuery($id) → Cobro query with relaciones
    │   ├─ $cliente = Cliente::findOrFail($id)
    │   ├─ $reparaciones = baseQuery→whereIn(tipos)→paginate(20)
    │   ├─ $cartola, $columnasCartola = baseQuery→whereIn(cartola tipos)→get→group
    │   └─ view('cliente.reparaciones', compact(...))
    │
    └─ cliente/reparaciones.blade.php
        ├─ @extends('layouts.app')
        ├─ @include('components.reparaciones-propiedad', ['reparaciones' => $reparaciones])
        └─ @include('components.cartola', ['cartola' => $cartola, 'columnasCartola' => $columnasCartola])
```

### Contratos Page

```
GET /cliente/{id}/contratos
    │
    ├─ FichaClienteController@contratos($id)
    │   ├─ $cliente = Cliente::findOrFail($id)
    │   └─ $contratosVigentes = Contrato query (same as show, extracted)
    │   └─ view('cliente.contratos', compact(...))
    │
    └─ cliente/contratos.blade.php
        ├─ @extends('layouts.app')
        └─ @include('components.contratos', ['contratosVigentes' => $contratosVigentes])
```

### Slimmed show() (after extraction)

```
GET /cliente/{id}
    │
    ├─ FichaClienteController@show($id)
    │   ├─ $cliente (with relations)
    │   ├─ $pendientes (clone baseQuery → pendientes)
    │   ├─ $transacciones (Transaccion query — unchanged)
    │   ├─ $options (cliente, contrato, servicio, propiedad, unidad, nacionalidad, participanteCobro)
    │   └─ $tiposCobroDisponibles (collection logic — unchanged)
    │   └─ view('cliente', compact(...))
    │
    └─ cliente.blade.php
        ├─ @include('components.pendientes', ...)
        ├─ @include('cliente.modal.show', ...)
        ├─ @include('components.transacciones-propiedad', ...)
        └─ 2 nav buttons → route('cliente.reparaciones'), route('cliente.contratos')
```

## File Changes

| File | Action | Description | Est. Lines |
|------|--------|-------------|------------|
| `app/Http/Controllers/Vistas/FichaClienteController.php` | Modify | Add `reparaciones()`, `contratos()`, `private baseQuery($id)`; slim `show()` | +45, -60 |
| `routes/web.php` | Modify | Add 2 routes in `[GEN:START:custom_routes]` block | +4 |
| `resources/views/cliente.blade.php` | Modify | Remove 3 `@include`, add 2 nav buttons | +6, -3 |
| `resources/views/cliente/reparaciones.blade.php` | Create | New view: extends layouts.app, includes 2 components + back button | ~20 |
| `resources/views/cliente/contratos.blade.php` | Create | New view: extends layouts.app, includes contratos component + back button | ~15 |

## Interfaces / Contracts

### New Controller Method Signatures

```php
// app/Http/Controllers/Vistas/FichaClienteController.php

/**
 * GET /cliente/{id}/reparaciones
 * Displays reparaciones table + cartola for a client.
 */
public function reparaciones($id): \Illuminate\View\View

/**
 * GET /cliente/{id}/contratos
 * Displays active contratos for a client.
 */
public function contratos($id): \Illuminate\View\View

/**
 * Shared base query for Cobro scoped to a client.
 * Used by show(), reparaciones().
 */
private function baseQuery($id): \Illuminate\Database\Eloquent\Builder
```

### New Routes

```php
// routes/web.php — inside [GEN:START:custom_routes]
Route::get('/cliente/{id}/reparaciones', [FichaClienteController::class, 'reparaciones'])
    ->name('cliente.reparaciones');
Route::get('/cliente/{id}/contratos', [FichaClienteController::class, 'contratos'])
    ->name('cliente.contratos');
```

### View Variable Contracts (must match component expectations)

| View | Variable | Type | Used By Component |
|------|----------|------|-------------------|
| `cliente.reparaciones` | `$reparaciones` | `LengthAwarePaginator` | `components.reparaciones-propiedad` |
| `cliente.reparaciones` | `$cartola` | `array` | `components.cartola` |
| `cliente.reparaciones` | `$columnasCartola` | `array` | `components.cartola` |
| `cliente.contratos` | `$contratosVigentes` | `Collection` | `components.contratos` |

## Testing Strategy

No automated test infrastructure exists. Manual verification via browser:

| Check | Steps |
|-------|-------|
| Reparaciones page loads | Visit `/cliente/{id}/reparaciones` with client that has reparaciones |
| Reparaciones empty state | Visit with client that has no reparaciones — see alert message |
| Cartola renders | Verify cartola table appears below reparaciones when data exists |
| Cartola hidden when empty | Verify no cartola section when no cartola data |
| Contratos page loads | Visit `/cliente/{id}/contratos` with client that has active contratos |
| Contratos empty state | Visit with client that has no active contratos |
| Expired contratos excluded | Verify contratos with `fecha_termino` in past are not shown |
| 404 on missing client | Visit `/cliente/999999/reparaciones` and `/cliente/999999/contratos` |
| Navigation buttons | From ficha page, click both buttons → correct pages |
| Back navigation | Each page has "Volver a ficha" button → returns to `/cliente/{id}` |
| Pagination works | Navigate page 2+ on reparaciones — URL uses `?page=2` |
| Ficha page slimmed | Verify ficha still shows pendientes, transacciones, modal |

## Migration / Rollout

No migration required. This is a pure code restructuring — no database changes, no data migration, no feature flags. Rollback: delete 2 new files, remove 2 routes, restore 3 `@include` lines in `cliente.blade.php`, restore original `show()` method.

## Open Questions

- None — all decisions resolved from proposal and specs.
