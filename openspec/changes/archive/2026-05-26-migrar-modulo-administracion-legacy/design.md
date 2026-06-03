# Design: Migrar Módulo Administración Legacy

## Technical Approach

Replace the legacy `agregar-administracion.html` → `agregar-administracion.php` → `sp_crear_contrato` stored procedure flow with a native Laravel wizard. The wizard collects 19 fields across 9 visual steps, validates via `CrearAdministracionRequest`, then delegates to `CrearAdministracionService::crearAdministracion()` which wraps all 8 entity-creation steps in a single `DB::transaction()`. Two API endpoints support autocomplete (cliente search, properties by owner). Zero schema changes — all new files, zero-downtime rollback by deletion.

Maps directly to spec domains: `administracion-wizard` (10 reqs, 18 scenarios) and `buscador` delta (2 reqs, 5 scenarios).

## Architecture Decisions

| Decision | Option A (chosen) | Option B (rejected) | Rationale |
|----------|-------------------|---------------------|-----------|
| Service location | `app/Services/CrearAdministracionService.php` — single static-like method | Split into 8 micro-services | SP has 8 sequential steps; splitting adds indirection with no benefit. Single service = single transaction boundary. |
| Controller namespace | `app/Http/Controllers/AdministracionController.php` (root) | `app/Http/Controllers/Crud/AdministracionController.php` | Not a CRUD resource — only `create` + `store`. Root namespace matches existing non-Crud controllers (`BuscadorController`). |
| API controller namespace | `app/Http/Controllers/Api/ClienteSearchController.php` | Reuse existing `BuscadorController` | `BuscadorController` handles generic search; wizard needs dedicated JSON endpoint with specific response shape. Separation keeps concerns clean. |
| Validation | `CrearAdministracionRequest` (Form Request) | Inline `$request->validate()` in controller | 19 rules + custom messages — Form Request keeps controller thin and enables `authorize()` gate. Matches Laravel convention. |
| View state management | Alpine.js `x-data` for step navigation + conditional visibility | Vanilla JS + manual DOM manipulation | Project already uses Bootstrap 5 + vanilla JS; Alpine is lightweight (15KB CDN) and declarative. No build step needed. |
| Entity reuse | `firstOrCreate` inside transaction | Pre-check existence then branch | `firstOrCreate` is atomic under `DB::transaction()`. Pre-check introduces TOCTOU race. |
| Error handling | Catch `\Throwable`, let transaction roll back, redirect back with error | Try/catch per step with partial rollback | Single catch + automatic DB rollback is simpler and safer. Partial rollback is error-prone. |

## Data Flow

```
Browser (Wizard UI)
  │
  ├─ GET /administracion/create ──→ AdministracionController@create
  │                                    │
  │                                    └─→ view: administracion/create.blade.php
  │                                         (9 step partials, Alpine.js state)
  │
  ├─ GET /api/clientes/search?q= ──→ ClienteSearchController@search
  │                                     │
  │                                     └─ JSON: [{id, texto, tipo}]
  │
  ├─ GET /api/propiedades/por-arrendador/{id} ──→ PropiedadPorArrendadorController@index
  │                                                  │
  │                                                  └─ JSON: [{id, direccion}]
  │
  └─ POST /administracion ──→ AdministracionController@store
                                 │
                                 ├─ CrearAdministracionRequest (19 rules, authorize)
                                 │   └─ FAIL → redirect back + errors
                                 │
                                 └─ PASS → CrearAdministracionService::crearAdministracion($request)
                                              │
                                              ├─ DB::transaction() {
                                              │    1. resolveOrCreateArrendador()  → Cliente (firstOrCreate by rut/nombre)
                                              │    2. resolveOrCreateArrendatario() → Cliente (firstOrCreate by rut/nombre)
                                              │    3. resolveOrCreatePropiedad()    → Propiedad (firstOrCreate by direccion+propietario)
                                              │    4. resolveOrCreateUnidad()       → Unidad (firstOrCreate by Propiedad_id)
                                              │    5. createContrato()              → Contrato (create with all contract fields)
                                              │    6. createParticipantes()         → ParticipanteContrato × 3 (Arrendador, Arrendatario, Corredor)
                                              │    7. createCobros()                → Cobro × N + ParticipanteCobro × 2N (conditional)
                                              │    8. createServicios()             → Servicio × N (conditional on dia_pago)
                                              │  }
                                              │
                                              └─ redirect → route('contrato.show', $contrato->id) + success flash
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Services/CrearAdministracionService.php` | Create | Core service — 8 steps inside `DB::transaction()`, returns `Contrato` |
| `app/Http/Requests/CrearAdministracionRequest.php` | Create | Form Request with 19 validation rules + custom messages + `authorize()` |
| `app/Http/Controllers/AdministracionController.php` | Create | Wizard controller: `create()` + `store()` |
| `app/Http/Controllers/Api/ClienteSearchController.php` | Create | `GET /api/clientes/search?q=` → JSON autocomplete |
| `app/Http/Controllers/Api/PropiedadPorArrendadorController.php` | Create | `GET /api/propiedades/por-arrendador/{id}` → JSON |
| `routes/web.php` | Modify | Add 2 web routes inside `[GEN:START:custom_routes]` block |
| `routes/api.php` | Modify | Add 2 API routes at end of file |
| `resources/views/administracion/create.blade.php` | Create | Wizard layout with Alpine.js step state |
| `resources/views/administracion/partials/step-01-arrendador.blade.php` | Create | Step 1: Arrendador form fields |
| `resources/views/administracion/partials/step-02-arrendatario.blade.php` | Create | Step 2: Arrendatario form fields |
| `resources/views/administracion/partials/step-03-propiedad.blade.php` | Create | Step 3: Propiedad fields + propiedad search |
| `resources/views/administracion/partials/step-04-contrato.blade.php` | Create | Step 4: Contrato financial fields |
| `resources/views/administracion/partials/step-05-fechas.blade.php` | Create | Step 5: Date fields |
| `resources/views/administracion/partials/step-06-cobros-iniciales.blade.php` | Create | Step 6: Comision inicial + garantia |
| `resources/views/administracion/partials/step-07-servicios.blade.php` | Create | Step 7: Servicio checkboxes (Luz, Agua, Gas, GC) |
| `resources/views/administracion/partials/step-08-resumen.blade.php` | Create | Step 8: Summary/confirmation |
| `resources/views/administracion/partials/step-09-corredor.blade.php` | Create | Step 9: Corredor info (auto = logged-in user) |

## Interfaces / Contracts

### CrearAdministracionService

```php
<?php

namespace App\Services;

use App\Http\Requests\CrearAdministracionRequest;
use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\Cobro;
use App\Models\ParticipanteContrato;
use App\Models\ParticipanteCobro;
use App\Models\Propiedad;
use App\Models\Servicio;
use App\Models\Unidad;
use Illuminate\Support\Facades\DB;

class CrearAdministracionService
{
    /**
     * Main entry point. Wraps all 8 steps in a single transaction.
     * Returns the created Contrato with loaded relationships.
     *
     * @throws \Throwable on any DB constraint violation or business rule failure
     */
    public function crearAdministracion(CrearAdministracionRequest $request): Contrato
    {
        return DB::transaction(function () use ($request) {
            // Step 1: Resolve or create Arrendador (owner)
            $arrendador = $this->resolveOrCreateCliente(
                $request->input('arrendador_rut'),
                $request->input('arrendador_nombre'),
                $request->input('arrendador_email'),
                $request->input('arrendador_estado_civil'),
            );

            // Step 2: Resolve or create Arrendatario (tenant)
            $arrendatario = $this->resolveOrCreateCliente(
                $request->input('arrendatario_rut'),
                $request->input('arrendatario_nombre'),
                $request->input('arrendatario_email'),
                $request->input('arrendatario_estado_civil'),
            );

            // Step 3: Resolve or create Propiedad
            $propiedad = Propiedad::firstOrCreate(
                [
                    'direccion'   => $request->input('propiedad_direccion'),
                    'propietario' => $arrendador->id,
                ],
                ['direccion' => $request->input('propiedad_direccion'), 'propietario' => $arrendador->id],
            );

            // Step 4: Resolve or create Unidad (1:1 with Propiedad in this app)
            $unidad = Unidad::firstOrCreate(
                ['Propiedad_id' => $propiedad->id],
                ['nombre' => $request->input('unidad_nombre') ?: $propiedad->direccion],
            );

            // Step 5: Create Contrato
            $contrato = Contrato::create([
                'Unidad_id'          => $unidad->id,
                'administracion'     => $request->boolean('administracion'),
                'comision_inicial'   => $request->input('comision_inicial'),
                'garantia'           => $request->input('garantia'),
                'renta'              => $request->input('renta'),
                'dia_pago'           => $request->input('dia_pago'),
                'comision_mensual'   => $request->input('comision_mensual'),
                'fecha_firma'        => $request->input('fecha_firma'),
                'fecha_inicio'       => $request->input('fecha_inicio'),
                'fecha_termino'      => $request->input('fecha_termino'),
                'url_pdf'            => $request->input('url_pdf'),
                'Ciudad_id'          => $request->input('Ciudad_id'),
            ]);

            // Step 6: Create ParticipanteContrato × 3
            $this->createParticipante($contrato, $arrendador, 'Arrendador');
            $this->createParticipante($contrato, $arrendatario, 'Arrendatario');

            // Corredor = fixed Cliente id=1 (no auth system implemented yet)
            $corredor = Cliente::findOrFail(1);
            $this->createParticipante($contrato, $corredor, 'Corredor');

            // Step 7: Create Cobros (only if administracion = true)
            if ($request->boolean('administracion')) {
                $corredorEsArrendador = ($arrendador->id === 1);
                $this->createCobros($contrato, $arrendador, $arrendatario, $corredor, $request, $corredorEsArrendador);
            }

            // Step 8: Create Servicios (only if dia_pago is set)
            if ($request->filled('dia_pago')) {
                $this->createServicios($propiedad, $request);
            }

            return $contrato->load(['participante_contratos', 'cobros']);
        });
    }

    /**
     * Resolve existing Cliente by RUT, or create by name.
     */
    protected function resolveOrCreateCliente(?string $rut, string $nombre, ?string $email, ?string $estadoCivil): Cliente
    {
        if ($rut) {
            $cliente = Cliente::where('rut', $rut)->first();
            if ($cliente) {
                return $cliente;
            }
        }

        return Cliente::firstOrCreate(
            ['nombre' => $nombre],
            [
                'rut'            => $rut,
                'email'          => $email,
                'estado_civil'   => $estadoCivil,
                'fecha_creacion' => now(),
            ],
        );
    }

    protected function createParticipante(Contrato $contrato, Cliente $cliente, string $rol): void
    {
        ParticipanteContrato::create([
            'Contrato_id' => $contrato->id,
            'Cliente_id'  => $cliente->id,
            'rol'         => $rol,
        ]);
    }

    protected function createCobros(
        Contrato $contrato,
        Cliente $arrendador,
        Cliente $arrendatario,
        Cliente $corredor,
        CrearAdministracionRequest $request,
        bool $corredorEsArrendador,
    ): void {
        $today = now()->format('Y-m-d');
        $roleMap = config('cobro_roles.tipo_role_map');

        // Helper to create a cobro + its 2 participantes
        $makeCobro = function (string $tipo, ?int $monto, string $deudorRol, string $acreedorRol) use (
            $contrato, $arrendador, $arrendatario, $corredor, $today, $deudorRol, $acreedorRol
        ) {
            if ($monto === null || $monto <= 0) {
                return;
            }

            $cobro = Cobro::create([
                'fecha_cobro' => $today,
                'estado'      => 'Pendiente',
                'tipo'        => $tipo,
                'monto'       => $monto,
                'Contrato_id' => $contrato->id,
            ]);

            // Resolve cliente by role
            $deudor = match ($deudorRol) {
                'Arrendador'  => $arrendador,
                'Arrendatario' => $arrendatario,
                'Corredor'    => $corredor,
            };
            $acreedor = match ($acreedorRol) {
                'Arrendador'  => $arrendador,
                'Arrendatario' => $arrendatario,
                'Corredor'    => $corredor,
            };

            ParticipanteCobro::create([
                'Cobro_id'    => $cobro->id,
                'Cliente_id'  => $deudor->id,
                'rol'         => 'Deudor',
            ]);
            ParticipanteCobro::create([
                'Cobro_id'    => $cobro->id,
                'Cliente_id'  => $acreedor->id,
                'rol'         => 'Acreedor',
            ]);
        };

        // Ingreso Renta Arrendatario (always)
        $makeCobro('Ingreso Renta Arrendatario', $contrato->renta, 'Arrendatario', 'Arrendador');

        // Egreso Renta Arrendador (skip if corredor = arrendador)
        if (! $corredorEsArrendador) {
            $egreso = $contrato->renta - $contrato->comision_mensual;
            $makeCobro('Egreso Renta Arrendador', $egreso, 'Corredor', 'Arrendador');
        }

        // Comision inicial (skip if null)
        if ($request->filled('comision_inicial')) {
            $makeCobro('Comision inicial arrendador', $contrato->comision_inicial, 'Arrendador', 'Corredor');
            $makeCobro('Comision inicial arrendatario', $contrato->comision_inicial, 'Arrendatario', 'Corredor');
        }

        // Garantia (skip if null)
        if ($request->filled('garantia')) {
            $makeCobro('Ingreso Garantía Arrendatario', $contrato->garantia, 'Arrendatario', 'Arrendador');
            if (! $corredorEsArrendador) {
                $makeCobro('Egreso Garantía Arrendador', $contrato->garantia, 'Corredor', 'Arrendador');
            }
        }
    }

    protected function createServicios(Propiedad $propiedad, CrearAdministracionRequest $request): void
    {
        $servicios = ['Luz', 'Agua', 'Gas', 'Gastos comunes'];

        foreach ($servicios as $tipo) {
            if ($request->boolean("servicio_{$tipo}")) {
                Servicio::firstOrCreate(
                    ['Propiedad_id' => $propiedad->id, 'tipo' => $tipo],
                    [
                        'dia_pago'  => $request->input('dia_pago'),
                        'estado'    => 'Activo',
                    ],
                );
            }
        }
    }
}
```

### CrearAdministracionRequest

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearAdministracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // No auth system implemented yet; open access
    }

    public function rules(): array
    {
        return [
            // Arrendador
            'arrendador_rut'          => 'nullable|string|max:12',
            'arrendador_nombre'       => 'required|string|max:255',
            'arrendador_email'        => 'nullable|email|max:255',
            'arrendador_estado_civil' => 'nullable|in:Soltero,Casado,Viudo,Divorciado',
            // Arrendatario
            'arrendatario_rut'          => 'nullable|string|max:12',
            'arrendatario_nombre'       => 'required|string|max:255',
            'arrendatario_email'        => 'nullable|email|max:255',
            'arrendatario_estado_civil' => 'nullable|in:Soltero,Casado,Viudo,Divorciado',
            // Propiedad
            'propiedad_direccion' => 'required|string|max:500',
            'unidad_nombre'       => 'nullable|string|max:255',
            // Contrato
            'administracion'   => 'required|boolean',
            'renta'            => 'required|integer|min:0',
            'comision_mensual' => 'nullable|integer|min:0',
            'dia_pago'         => 'nullable|integer|between:1,28',
            'comision_inicial' => 'nullable|integer|min:0',
            'garantia'         => 'nullable|integer|min:0',
            'fecha_firma'      => 'nullable|date',
            'fecha_inicio'     => 'required|date|after_or_equal:fecha_firma',
            'fecha_termino'    => 'nullable|date|after:fecha_inicio',
            'Ciudad_id'        => 'nullable|integer|exists:ciudad,id',
            'url_pdf'          => 'nullable|url|max:2048',
            // Servicios
            'servicio_Luz'           => 'nullable|boolean',
            'servicio_Agua'          => 'nullable|boolean',
            'servicio_Gas'           => 'nullable|boolean',
            'servicio_Gastos comunes' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'arrendador_nombre.required'       => 'El nombre del arrendador es obligatorio.',
            'arrendatario_nombre.required'     => 'El nombre del arrendatario es obligatorio.',
            'propiedad_direccion.required'     => 'La dirección de la propiedad es obligatoria.',
            'renta.required'                   => 'La renta es obligatoria.',
            'renta.integer'                    => 'La renta debe ser un número entero.',
            'renta.min'                        => 'La renta no puede ser negativa.',
            'fecha_inicio.required'            => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.after_or_equal'      => 'La fecha de inicio debe ser igual o posterior a la fecha de firma.',
            'fecha_termino.after'              => 'La fecha de término debe ser posterior a la fecha de inicio.',
            'dia_pago.between'                 => 'El día de pago debe estar entre 1 y 28.',
            'Ciudad_id.exists'                 => 'La ciudad seleccionada no existe.',
            'url_pdf.url'                      => 'La URL del PDF debe ser una dirección web válida.',
        ];
    }
}
```

### Controller

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\CrearAdministracionRequest;
use App\Services\CrearAdministracionService;
use App\Models\Ciudad;

class AdministracionController extends Controller
{
    public function create()
    {
        $ciudadCount   = Ciudad::count();
        $ciudadOptions = Ciudad::orderBy('nombre')->get(['id', 'nombre']);

        return view('administracion.create', [
            'ciudadCount'   => $ciudadCount,
            'ciudadOptions' => $ciudadOptions,
        ]);
    }

    public function store(CrearAdministracionRequest $request, CrearAdministracionService $service)
    {
        try {
            $contrato = $service->crearAdministracion($request);

            return redirect()
                ->route('contrato.show', $contrato->id)
                ->with('success', 'Administración creada correctamente.');
        } catch (\Throwable $e) {
            \Log::error('CrearAdministracionService failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al crear la administración: ' . $e->getMessage());
        }
    }
}
```

### API Endpoints

**`GET /api/clientes/search?q={query}`**

```php
// ClienteSearchController@search
public function search(Request $request): JsonResponse
{
    $q = trim($request->query('q', ''));

    if (strlen($q) < 2) {
        return response()->json([]);
    }

    $clientes = Cliente::where('nombre', 'like', "%{$q}%")
        ->orWhere('rut', 'like', "%{$q}%")
        ->orderBy('nombre')
        ->limit(20)
        ->get(['id', 'nombre', 'rut'])
        ->map(fn ($c) => [
            'id'    => $c->id,
            'texto' => $c->nombre . ($c->rut ? " ({$c->rut})" : ''),
            'tipo'  => 'cliente',
        ]);

    return response()->json($clientes);
}
```

Request: `GET /api/clientes/search?q=juan`
Response:
```json
[
  {"id": 42, "texto": "Juan Pérez (12.345.678-9)", "tipo": "cliente"},
  {"id": 87, "texto": "Juana Gómez (9.876.543-2)", "tipo": "cliente"}
]
```

**`GET /api/propiedades/por-arrendador/{id}`**

```php
// PropiedadPorArrendadorController@index
public function index(int $arrendadorId): JsonResponse
{
    $propiedades = Propiedad::where('propietario', $arrendadorId)
        ->with('unidad')
        ->get(['id', 'direccion'])
        ->map(fn ($p) => [
            'id'        => $p->id,
            'direccion' => $p->direccion,
            'unidad_id' => $p->unidad?->id,
        ]);

    return response()->json($propiedades);
}
```

Request: `GET /api/propiedades/por-arrendador/42`
Response:
```json
[
  {"id": 10, "direccion": "Av. Providencia 1234, Depto 5B", "unidad_id": 15},
  {"id": 22, "direccion": "Las Condes 567", "unidad_id": 30}
]
```

## Route Design

**`routes/web.php`** — add inside `[GEN:START:custom_routes]` block:

```php
// [GEN:START:administracion_routes]
use App\Http\Controllers\AdministracionController;
Route::get('/administracion/create', [AdministracionController::class, 'create'])->name('administracion.create');
Route::post('/administracion', [AdministracionController::class, 'store'])->name('administracion.store');
// [GEN:END:administracion_routes]
```

Middleware: `web` group (applied automatically via `routes/web.php`). No `auth` middleware — the app does not have authentication implemented yet.

**`routes/api.php`** — append at end:

```php
// [GEN:START:administracion_api_routes]
use App\Http\Controllers\Api\ClienteSearchController;
use App\Http\Controllers\Api\PropiedadPorArrendadorController;
Route::get('/clientes/search', [ClienteSearchController::class, 'search'])->name('api.clientes.search');
Route::get('/propiedades/por-arrendador/{id}', [PropiedadPorArrendadorController::class, 'index'])->name('api.propiedades.por-arrendador');
// [GEN:END:administracion_api_routes]
```

Middleware: `api` group (applied automatically).

## View Design

### Main layout: `resources/views/administracion/create.blade.php`

```blade
@extends('layouts.app')

@section('content')
<div class="container" x-data="administracionWizard()">
    <h2>Nueva Administración</h2>

    <!-- Step indicator -->
    <div class="d-flex justify-content-between mb-4">
        <template x-for="(stepLabel, idx) in stepLabels" :key="idx">
            <div class="text-center" :class="{'text-primary fw-bold': currentStep === idx + 1, 'text-muted': currentStep !== idx + 1}">
                <span x-text="idx + 1"></span>
                <small x-text="stepLabel"></small>
            </div>
        </template>
    </div>

    <form method="POST" action="{{ route('administracion.store') }}" id="administracionForm">
        @csrf

        <!-- Step 1: Arrendador -->
        <div x-show="currentStep === 1" x-cloak>
            @include('administracion.partials.step-01-arrendador')
        </div>

        <!-- Step 2: Arrendatario -->
        <div x-show="currentStep === 2" x-cloak>
            @include('administracion.partials.step-02-arrendatario')
        </div>

        <!-- Step 3: Propiedad -->
        <div x-show="currentStep === 3" x-cloak>
            @include('administracion.partials.step-03-propiedad')
        </div>

        <!-- Step 4: Contrato financial -->
        <div x-show="currentStep === 4" x-cloak>
            @include('administracion.partials.step-04-contrato')
        </div>

        <!-- Step 5: Fechas -->
        <div x-show="currentStep === 5" x-cloak>
            @include('administracion.partials.step-05-fechas')
        </div>

        <!-- Step 6: Cobros iniciales -->
        <div x-show="currentStep === 6" x-cloak>
            @include('administracion.partials.step-06-cobros-iniciales')
        </div>

        <!-- Step 7: Servicios -->
        <div x-show="currentStep === 7" x-cloak>
            @include('administracion.partials.step-07-servicios')
        </div>

        <!-- Step 8: Resumen -->
        <div x-show="currentStep === 8" x-cloak>
            @include('administracion.partials.step-08-resumen')
        </div>

        <!-- Step 9: Corredor (auto-filled) -->
        <div x-show="currentStep === 9" x-cloak>
            @include('administracion.partials.step-09-corredor')
        </div>

        <!-- Navigation -->
        <div class="d-flex justify-content-between mt-4 pt-3 border-top">
            <button type="button" class="btn btn-outline-secondary"
                    x-show="currentStep > 1"
                    @click="currentStep--">Anterior</button>
            <div></div>
            <button type="button" class="btn btn-primary"
                    x-show="currentStep < 9"
                    @click="currentStep++">Siguiente</button>
            <button type="submit" class="btn btn-success"
                    x-show="currentStep === 9">Crear Administración</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function administracionWizard() {
    return {
        currentStep: 1,
        stepLabels: ['Arrendador', 'Arrendatario', 'Propiedad', 'Contrato', 'Fechas', 'Cobros', 'Servicios', 'Resumen', 'Corredor'],
    }
}
</script>
<script>
    // Cliente autocomplete for arrendador/arrendatario fields
    // Reuses project's existing buscador() pattern from buscador.js
    buscador({
        input: '#input-arrendador-search',
        list:  '#lista-arrendador',
        tipo:  'cliente-api',  // new tipo that hits /api/clientes/search
        onSelect: function(item) {
            document.getElementById('input-arrendador-search').value = item.texto;
            document.getElementById('arrendador_id').value = item.id;
            // Optionally load existing cliente data
        }
    });
    // Same pattern for arrendatario
</script>
@endpush
```

### Conditional rendering logic

| Partial | Conditional show/hide | Trigger |
|---------|----------------------|---------|
| `step-06-cobros-iniciales` | Show comision fields only if `administracion = 1` | `x-show="administracion === '1'"` on fieldset |
| `step-07-servicios` | Show only if `administracion = 1` AND `dia_pago` is filled | `x-show="administracion === '1' && diaPago"` |
| `step-08-resumen` | Always visible on step 8; dynamically populated from form state | Alpine `$data` binding |
| `step-09-corredor` | Always visible; corredor is hardcoded Cliente id=1 (no auth yet) | Hidden inputs with server-rendered values |

### Partial field mapping (summary)

| Partial | Fields |
|---------|--------|
| `step-01-arrendador` | `arrendador_rut`, `arrendador_nombre` (with autocomplete), `arrendador_email`, `arrendador_estado_civil` |
| `step-02-arrendatario` | `arrendatario_rut`, `arrendatario_nombre` (with autocomplete), `arrendatario_email`, `arrendatario_estado_civil` |
| `step-03-propiedad` | `propiedad_direccion`, `unidad_nombre`, `Ciudad_id` (select/search) |
| `step-04-contrato` | `administracion` (select Sí/No), `renta`, `comision_mensual`, `dia_pago` |
| `step-05-fechas` | `fecha_firma`, `fecha_inicio`, `fecha_termino`, `url_pdf` |
| `step-06-cobros-iniciales` | `comision_inicial`, `garantia` |
| `step-07-servicios` | `servicio_Luz`, `servicio_Agua`, `servicio_Gas`, `servicio_Gastos comunes` (checkboxes) |
| `step-08-resumen` | Read-only summary of all entered data (Alpine-bound) |
| `step-09-corredor` | Hidden: corredor is hardcoded to Cliente id=1 (no auth system yet) |

## Error Handling

| Error type | Where caught | User-facing behavior |
|------------|-------------|---------------------|
| Validation failure | Laravel Form Request (before controller) | Redirect back with `$errors`, fields highlighted via `@error` directives, `old()` values preserved |
| DB constraint violation (e.g., `chk_renta_contrato`) | `AdministracionController@store` catch `\Throwable` | Redirect back with `error` flash, `old()` preserved. Logged to `storage/logs/laravel.log` with full trace |
| Business rule: `arrendador_id = arrendatario_id` | Validation rule `different:arrendador_rut,arrendatario_rut` (or custom validator) | Validation error message: "Arrendador y arrendatario no pueden ser el mismo cliente." |
| Transaction rollback (any step fails) | Automatic via `DB::transaction()` — no partial state | Same as DB constraint: redirect back with generic error message. All DB changes rolled back atomically |
| API empty results | `ClienteSearchController` / `PropiedadPorArrendadorController` | Return `[]` — Alpine shows "No se encontraron resultados" |

## File Dependency Graph

```
AdministracionController
  ├── CrearAdministracionRequest (validation)
  ├── CrearAdministracionService (business logic)
  ├── Ciudad model (FK data for create view)
  └── views/administracion/create.blade.php
       └── views/administracion/partials/step-01 through step-09

CrearAdministracionService
  ├── Cliente model (firstOrCreate)
  ├── Propiedad model (firstOrCreate)
  ├── Unidad model (firstOrCreate)
  ├── Contrato model (create)
  ├── ParticipanteContrato model (create × 3)
  ├── Cobro model (create × N)
  ├── ParticipanteCobro model (create × 2N)
  ├── Servicio model (firstOrCreate × N)
  ├── config/cobro_roles.php (role mapping)
  └── DB facade (transaction)

ClienteSearchController
  ├── Cliente model (search)
  └── Request facade (query param)

PropiedadPorArrendadorController
  ├── Propiedad model (where propietario)
  └── Unidad model (eager load)

routes/web.php ──→ AdministracionController
routes/api.php ──→ ClienteSearchController, PropiedadPorArrendadorController
```

## Testing Strategy

No automated test infrastructure exists. Manual verification checklist:

| # | Scenario | Steps | Expected Result |
|---|----------|-------|-----------------|
| 1 | Happy path — full administracion | Fill all 9 steps, submit | Redirect to `contrato.show`, success flash, 1 Contrato, 3 ParticipanteContrato, 7+ Cobros, 2 ParticipanteCobro per Cobro |
| 2 | Existing cliente reuse | Enter RUT of existing cliente | Cliente NOT duplicated, reused via `firstOrCreate` |
| 3 | Corredor = arrendador (id=1) | Set arrendador to existing cliente id=1 | Egreso cobros skipped, only Ingreso cobros created |
| 4 | `administracion = false` | Toggle administracion to "No" | No Cobros created, no Servicios created, Contrato + Participantes only |
| 5 | `comision_inicial = null` | Leave comision inicial empty | No "Comision inicial" cobros created |
| 6 | `garantia = null` | Leave garantia empty | No "Garantía" cobros created |
| 7 | Transaction rollback | Trigger DB constraint (e.g., invalid Ciudad_id) | Zero records created, redirect back with error |
| 8 | Cliente autocomplete | Type "juan" in arrendador search | JSON results from `/api/clientes/search`, select populates hidden ID |
| 9 | Properties by owner | Select arrendador with properties | `/api/propiedades/por-arrendador/{id}` returns list |
| 10 | Validation errors | Submit empty form | 19 validation errors shown, form preserved with `old()` values |

## Migration / Rollout

No migration required. All new files. Rollback = delete 5 new PHP files + 10 Blade files + remove 4 route lines. Zero schema changes, zero data migration.

## Open Questions (ALL RESOLVED)

- [x] **Administración toggle**: Explicit checkbox (confirmed by user — matches legacy).
- [x] **`fecha_termino`**: Nullable (confirmed by user — SP allows NULL for open-ended contracts).
- [x] **Corredor / auth**: No auth system exists. Corredor is ALWAYS hardcoded to `Cliente_id = 1`. The `authorize()` method returns `true`. No `auth()->user()` references anywhere.
