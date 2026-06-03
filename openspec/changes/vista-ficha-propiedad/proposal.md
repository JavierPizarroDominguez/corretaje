# Proposal: Vista Ficha de Propiedad

## Intent

La ruta `/propiedad/ficha/{id}` actualmente muestra una página "coming soon". El proyecto ya tiene una ficha de cliente funcional (`FichaClienteController@show`) con pendientes, transacciones, contratos y cartola. Se necesita una ficha equivalente para Propiedad que muestre los cobros, transacciones, contratos y cartola asociados a una propiedad específica — filtrando por los 3 caminos de relación (cobro directo, contrato→unidad, servicio).

La vista `propiedad.blade.php` ya existe pero referencia componentes inexistentes. Hay un controller de referencia en `storage/propiedad-antiguo.php` con la lógica de queries por propiedad.

## Scope

### In Scope
- Nuevo `FichaPropiedadController` con métodos `show()`, `reparaciones()`, `contratos()`
- Nueva vista `resources/views/propiedad/ficha.blade.php` (layout principal)
- Nuevas vistas hijas `propiedad/reparaciones.blade.php` y `propiedad/contratos.blade.php`
- Nuevo componente `components/pendientes-propiedad.blade.php` (adaptación de pendientes para propiedad)
- Nuevo componente `components/propiedad-info.blade.php` (reemplaza `titulo-propiedad` inexistente, muestra datos de la propiedad)
- Nuevo modal `propiedad/modal/show.blade.php` (ficha de datos de la propiedad)
- Actualización de ruta `propiedad.ficha` para apuntar al controller
- Nuevas rutas hijas `/propiedad/{id}/reparaciones` y `/propiedad/{id}/contratos`
- Adaptación de `cobro.modal.create` para funcionar desde contexto propiedad (se necesita `propiedad_id` en JS además de `cliente_id`)

### Out of Scope
- Refactorizar `FichaClienteController` o extraer traits compartidos (postergado)
- Modificar la API existente `/api/cobro/resolve-relationships`
- Modificar modelos Eloquent (las relaciones ya existen y son correctas)
- Crear CRUD de propiedad (solo vista ficha)
- Tests automatizados (no hay infraestructura de tests en el proyecto)

## Capabilities

### New Capabilities
- `ficha-propiedad`: Vista de ficha de propiedad con pendientes, transacciones, contratos vigentes, cartola y reparaciones

### Modified Capabilities
- None (no se modifica spec existente)

## Approach

### Patrón de filtrado por Propiedad

Un `Cobro` se asocia a una Propiedad por 3 caminos (por orden de prioridad):

1. **Directo**: `cobro.Propiedad_id = $id` (cobro sin contrato ni servicio, ej: reparación directa)
2. **Contrato→Unidad**: `cobro.contrato.unidad.Propiedad_id = $id` (rentas, comisiones, garantías)
3. **Servicio**: `cobro.servicio.Propiedad_id = $id` (luz, agua, gas, gastos comunes)

Esto se traduce en un `baseQuery($id)` para `FichaPropiedadController`:

```php
private function baseQuery($id)
{
    return Cobro::query()
        ->with([
            'deudor.cliente',
            'acreedor.cliente',
            'contrato.unidad.propiedad',
            'servicio',
        ])
        ->where(function ($q) use ($id) {
            $q->where('Propiedad_id', $id)
              ->orWhereHas('contrato.unidad', function ($q2) use ($id) {
                  $q2->where('Propiedad_id', $id);
              })
              ->orWhereHas('servicio', function ($q2) use ($id) {
                  $q2->where('Propiedad_id', $id);
              });
        });
}
```

### Estructura del Controller

**`FichaPropiedadController`** — `app/Http/Controllers/Vistas/FichaPropiedadController.php`

| Método | Ruta | Responsabilidad |
|--------|------|-----------------|
| `show($id)` | GET `/propiedad/ficha/{id}` | Ficha principal: propiedad + pendientes + transacciones + tipos disponibles |
| `reparaciones($id)` | GET `/propiedad/{id}/reparaciones` | Tabla de reparaciones + cartola |
| `contratos($id)` | GET `/propiedad/{id}/contratos` | Contratos vigentes de la(s) unidad(es) |

### `show($id)` — Variables pasadas a la vista

| Variable | Tipo | Fuente / Query |
|----------|------|----------------|
| `$propiedad` | Propiedad | `Propiedad::with(['cliente', 'unidad.contratoVigente', 'servicios'])->findOrFail($id)` |
| `$pendientes` | LengthAwarePaginator | `baseQuery($id)` → `whereIn('estado', ['pendiente','vencido','incompleto'])` → paginate(10) + assign `concepto` labels |
| `$transacciones` | LengthAwarePaginator | `Transaccion::whereHas('cobros', baseQueryFilter)` → paginate(20) |
| `$contratosVigentes` | Collection | `Contrato::whereHas('unidad', Propiedad_id=$id)` → vigentes |
| `$tiposCobroDisponibles` | Collection | Base tipos + contrato vigente servicios + servicio tipos |
| `$clienteOptions` | Collection | `Cliente::orderBy('nombre')->get(['id','nombre'])` si count ≤ threshold |
| `$clienteCount` | int | `Cliente::count()` |
| `$propiedadCount` | int | `Propiedad::count()` |
| `$propiedadOptions` | Collection | La propiedad individual (para select en modal cobro) |
| `$unidadCount` | int | `Unidad::count()` |
| `$unidadOptions` | Collection | Las unidades de esta propiedad |
| `$contratoCount` | int | `Contrato::count()` |
| `$contratoOptions` | int | Los contratos vigentes de esta propiedad |
| `$servicioCount` | int | `Servicio::count()` |
| `$servicioOptions` | Collection | Los servicios de esta propiedad |
| `$nacionalidadCount` | int | `Nacionalidad::count()` |
| `$nacionalidadOptions` | Collection | `Nacionalidad::orderBy('nombre')->get()` |
| `$participanteCobroCount` | int | `ParticipanteCobro::count()` |
| `$participanteCobroOptions` | Collection | Participantes relevantes |

### Datos del Cliente → Propiedad — Mapeo

| Dato | Ficha Cliente | Ficha Propiedad |
|------|---------------|-----------------|
| **Entidad principal** | `Cliente::findOrFail($id)` | `Propiedad::with(['cliente','unidad.contratoVigente','servicios'])->findOrFail($id)` |
| **Pendientes** | `whereHas('participante_cobros', Cliente_id)` | `where(Propiedad_id OR whereHas(contrato.unidad) OR whereHas(servicio))` |
| **Transacciones** | `whereHas('cobros.participante_cobros', Cliente_id)` | `whereHas('cobros', same 3-way filter)` |
| **Contratos vigentes** | `whereHas('participante_contratos', Cliente_id)` | `whereHas('unidad', Propiedad_id)` |
| **Concepto labels** | Usa deudor/acreedor del cliente participante | Mismo patrón (los cobros ya tienen deudor/acreedor) |
| **tiposCobroDisponibles** | Contratos del cliente + servicios de sus propiedades | Contrato vigente de la unidad + servicios de la propiedad |
| **Propietario** | N/A | `$propiedad->cliente` (relación belongsTo 'propietario') |
| **Cartola** | Agrupa por `unidad + year + mes` | Mismo patrón, filtrado a 1 propiedad (o sus unidades) |

### Reusability Plan

| Componente | Acción | Detalle |
|---|---|---|
| `components.transacciones-propiedad` | ✅ Reutilizar verbatim | Solo usa `$transacciones` |
| `components.reparaciones-propiedad` | ✅ Reutilizar verbatim | Solo usa `$reparaciones` |
| `components.cartola` | ✅ Reutilizar verbatim | Usa `$cartola` y `$columnasCartola` |
| `components.contratos` | ✅ Reutilizar verbatim | Usa `$contratosVigentes` |
| `components.pendientes` | ❌ Nuevo componente | Fuertemente acoplado a `$cliente`. Crear `components/pendientes-propiedad.blade.php` |
| `cliente.modal.show` | ❌ Nuevo modal | Muestra datos personales del cliente. Crear `propiedad.modal.show` con datos de propiedad (dirección, propietario, unidad) |
| `cobro.modal.show` | ✅ Reutilizar con caveat | Recibe `$clienteOptions` y `$clienteCount` — el controller de propiedad debe pasar estas variables |
| `cobro.modal.create` | ⚠️ Adaptar contexto | Usa `$clienteOptions`, `$tiposCobroDisponibles`, `$propiedadOptions`. Desde ficha propiedad: pre-seleccionar propiedad, pasar `$cliente_id` del propietario vía JS |

### Adaptación de `cobro.modal.create` para contexto Propiedad

El JS `resolveCobroRelationships` usa `cliente_id` del hidden input `modal-cliente-id`. Desde la ficha de propiedad:
- Se pasa `propiedad_id` como parámetro adicional en `abrirModal()`
- Se pre-llena `input-create-propiedad-select` con la propiedad actual
- Se pasa `cliente_id` del propietario (`$propiedad->cliente->id`) al hidden input
- Si la propiedad tiene unidad con contrato vigente, se puede pre-resolver contrato/servicio

### Rutas

```
Route::get('/propiedad/ficha/{id}', [FichaPropiedadController::class, 'show'])->name('propiedad.ficha');
Route::get('/propiedad/{id}/reparaciones', [FichaPropiedadController::class, 'reparaciones'])->name('propiedad.reparaciones');
Route::get('/propiedad/{id}/contratos', [FichaPropiedadController::class, 'contratos'])->name('propiedad.contratos');
```

Nota: la ruta de propiedad reemplaza la existente `propiedad.ficha` (que apunta a coming-soon).

### Vista Principal: `propiedad/ficha.blade.php`

```blade
@extends('layouts.app')
@section('title', 'Ficha de Propiedad')
@section('content')
<div class="row">
    <div class="col-12">
        <h1>{{ $propiedad->direccion }}</h1>
        <span class="text-muted">Propietario: {{ $propiedad->cliente->nombre ?? 'Sin propietario' }}</span>
    </div>
</div>
@include('components.pendientes-propiedad', ['pendientes' => $pendientes, 'propiedad' => $propiedad, 'clienteOptions' => $clienteOptions, 'tiposCobroDisponibles' => $tiposCobroDisponibles])
@include('propiedad.modal.show', ['propiedad' => $propiedad])
@include('components.transacciones-propiedad', ['transacciones' => $transacciones])
<a href="{{ route('propiedad.reparaciones', $propiedad->id) }}" class="btn btn-sm btn-primary">Historial de movimientos</a>
<a href="{{ route('propiedad.contratos', $propiedad->id) }}" class="btn btn-sm btn-primary">Contratos</a>
@endsection
```

### Vista Reparaciones: `propiedad/reparaciones.blade.php`

Idéntica estructura a `cliente/reparaciones.blade.php` pero:
- Incluye `components.reparaciones-propiedad` y `components.cartola`
- Link "Volver" apunta a `route('propiedad.ficha', $propiedad->id)`

### Vista Contratos: `propiedad/contratos.blade.php`

Idéntica estructura a `cliente/contratos.blade.php` pero:
- Incluye `components.contratos`
- Link "Volver" apunta a `route('propiedad.ficha', $propiedad->id)`

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Http/Controllers/Vistas/FichaPropiedadController.php` | New | Controller con show(), reparaciones(), contratos() |
| `routes/web.php` | Modified | Reemplazar placeholder propiedad.ficha + agregar rutas hijas |
| `resources/views/propiedad/ficha.blade.php` | New | Vista principal de ficha de propiedad |
| `resources/views/propiedad/reparaciones.blade.php` | New | Vista de reparaciones + cartola |
| `resources/views/propiedad/contratos.blade.php` | New | Vista de contratos vigentes |
| `resources/views/propiedad/modal/show.blade.php` | New | Modal con datos de la propiedad |
| `resources/views/components/pendientes-propiedad.blade.php` | New | Componente de pendientes adaptado para propiedad |
| `resources/views/propiedad.blade.php` | Modified | Reemplazar includes inexistentes por los reales |
| `storage/propiedad-antiguo.php` | No change | Referencia histórica, no se toca |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Propiedad sin Unidad (sin contrato vigente) | Medium | Mostrar sección vacía con mensaje "Sin contrato vigente". Los tipos disponibles serán mínimos. `tiposCobroDisponibles` siempre incluye base (Reparación, Extra, Devolución). |
| Propiedad con múltiples Unidades | Low | Modelo actual usa `hasOne` (relación 1:1). Si migraran ahasMany, cartola ya agrupa por `$unidad`. Sin impacto. |
| `cobro.modal.create` depende de `cliente_id` en JS | High | Pasar `$propiedad->cliente->id` como `cliente_id` default. Agregar `propiedad_id` al data attribute del form. Modificar `resolveCobroRelationships` para usar `propiedad_id` cuando esté disponible. |
| Estados de cobro inconsistentes mayúscula/minúscula | Medium | Código antiguo usa `'Pendiente'`, actual usa `'pendiente'`. El nuevo controller DEBE usar lowercase consistente con el controller actual de cliente. |
| `cobro.modal.show` necesita `$clienteOptions` y `$clienteCount` | Low | El controller de propiedad los pasa igual que el de cliente. Sin cambios al modal. |
| Propiedad sin propietario (cliente null) | Low | Blade muestra "Sin propietario". En modal crear cobro, se pasa cliente_id=0 para que JS no auto-resuelva. |

## Rollback Plan

1. Revertir la ruta `propiedad.ficha` al placeholder `fn () => view('coming-soon')`
2. Eliminar las 2 rutas hijas de propiedad
3. Eliminar `FichaPropiedadController.php`
4. Eliminar vistas nuevas bajo `propiedad/` y `components/pendientes-propiedad.blade.php`
5. Restaurar `propiedad.blade.php` a su estado original (con includes inexistentes)

No se modifican archivos del controller de cliente, modelo, ni rutas existentes — el rollback es limpio.

## Dependencies

- Las relaciones Eloquent `Propiedad.unidad`, `Unidad.contratoVigente`, `Propiedad.servicios`, `Propiedad.cliente` ya existen y son correctas
- El componente `cobro.modal.create` debe ser adaptado con cuidado para aceptar contexto propiedad
- `/api/cobro/resolve-relationships` puede necesitar un parámetro adicional `propiedad_id` (out of scope, pero documentarlo)

## Success Criteria

- [ ] `GET /propiedad/ficha/{id}` muestra la ficha de la propiedad con dirección y propietario
- [ ] Los cobros pendientes se filtran correctamente por los 3 caminos (directo, contrato→unidad, servicio)
- [ ] Las transacciones se filtran por los mismos 3 caminos
- [ ] Los contratos vigentes se muestran correctamente (via unidad de la propiedad)
- [ ] Los tipos de cobro disponibles se calculan según contrato vigente + servicios de la propiedad
- [ ] El botón "Agregar cobro" abre el modal con propiedad pre-seleccionada
- [ ] Los links "Historial de movimientos" y "Contratos" funcionan y filtran por propiedad
- [ ] La cartola agrupa correctamente por unidad/año/mes
- [ ] Una propiedad sin unidad no rompe la vista (muestra mensaje apropiado)
- [ ] Una propiedad sin contrato vigente muestra tipos disponibles mínimos
- [ ] Se respetan las convenciones del proyecto: loading indicators, flashModal, sin alert/confirm nativos

## Open Questions

1. **¿Debería `propiedad.blade.php` ser eliminada o reemplazada?** Actualmente referencia componentes inexistentes. La nueva vista vive en `propiedad/ficha.blade.php`. Se recomienda eliminar `propiedad.blade.php` o actualizarla para que redireccione.
2. **¿`resolveCobroRelationships` necesita cambios?** El JS actual usa `cliente_id` para resolver deudor/acreedor. Para propiedad, se necesita pasar `propiedad_id` para resolver contrato y servicio automáticamente. Esto puede requerir un cambio en el endpoint API (out of scope de esta fase, pero el modal debería pre-seleccionar la propiedad).

## Review Budget Estimate

- Líneas nuevas estimadas: ~400-500 (controller ~200, vistas/componentes ~200-300)
- Líneas modificadas: ~15 (routes/web.php + propiedad.blade.php)
- **Total estimado**: ~420-515 líneas
- **Budget risk**: Medium (cerca del límite de 400 líneas, podría requerir chained PR si se excede)

## Design Decisions

1. **Controller paralelo vs. trait compartido**: Se elige controller paralelo (`FichaPropiedadController`) porque refactorizar `FichaClienteController` introduciría riesgo de regresión en código que ya funciona. Si ambos controllers comparten lógica en el futuro, se puede extraer un trait.

2. **Componente nuevo `pendientes-propiedad` vs. parametrizar `pendientes`**: El componente `pendientes` está acoplado a `$cliente` (usando `$cliente->id`, `$cliente->propiedades`, etc.). Parametrizarlo añadiría complejidad innecesaria. Se crea un componente limpio para propiedad.

3. **Estados de cobro en lowercase**: El código antiguo (`propiedad-antiguo.php`) usa `'Pendiente'`, `'Vencido'`, `'Incompleto'` (capitalizado). El controller actual de cliente usa `'pendiente'`, `'vencido'`, `'incompleto'`. El nuevo controller DEBE usar lowercase para consistencia.