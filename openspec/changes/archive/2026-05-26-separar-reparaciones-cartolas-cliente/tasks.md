# Tasks: Separar Reparaciones y Cartolas de la Ficha de Cliente

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~110 (+69 additions, -60 deletions net) |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

---

## Phase 1: Infrastructure — Controller Refactor

- [x] 1.1 **Extract `baseQuery($id)` private helper** in `FichaClienteControllerController`
  - Move the `Cobro::query()->with(...)->whereHas('participante_cobros', ...)` block (lines 37–46) to `private function baseQuery($id): Builder`
  - Return the query builder; clone it where needed in `show()`
  - File: `app/Http/Controllers/Vistas/FichaClienteController.php`
  - Verify: Controller still compiles, `php artisan route:list` still works

- [x] 1.2 **Add `reparaciones($id)` method** to `FichaClienteController`
  - Clone `baseQuery($id)` and add `whereIn('tipo', ['Reparación','Devolución','Extra'])->latest('fecha_cobro')->paginate(20)` (uses default `page` param — not `reparaciones_page`)
  - Clone `baseQuery($id)` and build `$cartola` + `$columnasCartola` logic (lines 96–149 from current `show()`)
  - Load `$cliente` with eager-load from `baseQuery` chain
  - Return `view('cliente.reparaciones', compact('cliente', 'reparaciones', 'cartola', 'columnasCartola'))`
  - File: `app/Http/Controllers/Vistas/FichaClienteController.php`
  - Verify: Route `/cliente/{id}/reparaciones` returns a view with those 4 variables

- [x] 1.3 **Add `contratos($id)` method** to `FichaClienteController`
  - Load `$cliente` via `Cliente::with([...eager...])->findOrFail($id)` (same eager-load block as `show()`)
  - Run the `contratosVigentes` query (lines 189–203 from current `show()`)
  - Return `view('cliente.contratos', compact('cliente', 'contratosVigentes'))`
  - File: `app/Http/Controllers/Vistas/FichaClienteController.php`
  - Verify: Route `/cliente/{id}/contratos` returns a view with those 2 variables

## Phase 2: Views — Create New Pages

- [x] 2.1 **Create `resources/views/cliente/reparaciones.blade.php`**
  - `@extends('layouts.app')`, `@section('title', 'Reparaciones y Cartola')`, `@section('content')`
  - `@include('components.reparaciones-propiedad', ['reparaciones' => $reparaciones])`
  - `@include('components.cartola', ['cartola' => $cartola, 'columnasCartola' => $columnasCartola])`
  - Back button: `<a href="{{ route('fichacliente.show', $cliente->id) }}" class="btn btn-sm btn-secondary">Volver a Ficha</a>`
  - Files: create `resources/views/cliente/reparaciones.blade.php`
  - Verify: Page renders without errors in browser

- [x] 2.2 **Create `resources/views/cliente/contratos.blade.php`**
  - `@extends('layouts.app')`, `@section('title', 'Contratos Vigentes')`, `@section('content')`
  - `@include('components.contratos', ['contratosVigentes' => $contratosVigentes])`
  - Back button: `<a href="{{ route('fichacliente.show', $cliente->id) }}" class="btn btn-sm btn-secondary">Volver a Ficha</a>`
  - Files: create `resources/views/cliente/contratos.blade.php`
  - Verify: Page renders without errors in browser

## Phase 3: Routing — Add Named Routes

- [x] 3.1 **Add 2 routes in `routes/web.php`** (inside `[GEN:START/END]` custom block)
  - `Route::get('/cliente/{id}/reparaciones', [FichaClienteController::class, 'reparaciones'])->name('cliente.reparaciones');`
  - `Route::get('/cliente/{id}/contratos', [FichaClienteController::class, 'contratos'])->name('cliente.contratos');`
  - File: `routes/web.php`
  - Verify: `php artisan route:list | grep cliente` shows all 5 routes

## Phase 4: Slim FichaClienteController@show()

- [x] 4.1 **Remove cartola block** from `show()` (lines 91–150)
  - File: `app/Http/Controllers/Vistas/FichaClienteController.php`
  - Verify: `show()` no longer builds `$cartola` or `$columnasCartola`

- [x] 4.2 **Remove reparaciones block** from `show()` (lines 155–164)
  - File: `app/Http/Controllers/Vistas/FichaClienteController.php`
  - Verify: `show()` no longer builds `$reparaciones`

- [x] 4.3 **Remove contratosVigentes block** from `show()` (lines 183–203)
  - File: `app/Http/Controllers/Vistas/FichaClienteController.php`
  - Verify: `show()` no longer builds `$contratosVigentes`

- [x] 4.4 **Remove cartola/contratos/reparaciones from `show()` compact()** (lines 311–334)
  - Remove `'cartola'`, `'columnasCartola'`, `'reparaciones'`, `'contratosVigentes'` from the `return view()` compact list
  - File: `app/Http/Controllers/Vistas/FichaClienteController.php`
  - Verify: `show()` only passes the original 4 variables plus options

## Phase 5: Ficha View — Remove Includes, Add Nav Buttons

- [x] 5.1 **Remove 3 `@include` lines** from `resources/views/cliente.blade.php`
  - Remove line 5: `@include('components.reparaciones-propiedad', ['reparaciones' => $reparaciones])`
  - Remove line 6: `@include('components.cartola', ['cartola' => $cartola, 'columnasCartola' => $columnasCartola])`
  - Remove line 8: `@include('components.contratos', ['contratosVigentes' => $contratosVigentes])`

- [x] 5.2 **Add 2 navigation buttons** to `resources/views/cliente.blade.php`
  - After the `pendientes` include, add:
    - `<a href="{{ route('cliente.reparaciones', $cliente->id) }}" class="btn btn-sm btn-primary">Reparaciones y Cartola</a>`
    - `<a href="{{ route('cliente.contratos', $cliente->id) }}" class="btn btn-sm btn-primary">Contratos Vigentes</a>`
  - File: `resources/views/cliente.blade.php`
  - Verify: Ficha page shows 2 new buttons that navigate to the new pages

## Phase 6: Manual Verification

- [ ] 6.1 **Test reparaciones page**: load `/cliente/{id}/reparaciones` — data, empty state, pagination
- [ ] 6.2 **Test contratos page**: load `/cliente/{id}/contratos` — active contratos, empty state, expired excluded
- [ ] 6.3 **Test ficha page**: load `/cliente/{id}/ficha` — pendientes, transacciones, options, tiposCobroDisponibles render correctly; 3 removed sections gone
- [ ] 6.4 **Test 404**: visit `/cliente/99999/reparaciones` and `/cliente/99999/contratos` — expect 404
- [ ] 6.5 **Test back buttons**: verify back navigation from both new pages returns to ficha
