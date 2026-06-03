# Proposal: Migrar Módulo Administración Legacy

## Intent

Replace the legacy `agregar-administracion.html` → `agregar-administracion.php` → `sp_crear_contrato` flow with a native Laravel wizard that atomically creates all entities (Cliente × 2, Propiedad, Unidad, Contrato, ParticipanteContrato × 3, Cobro × N, ParticipanteCobro × 2N, Servicio × N) in a single `DB::transaction()`.

## Scope

### In Scope

- **`app/Services/CrearAdministracionService.php`** — NEW. Core business logic replacing `sp_crear_contrato`. Wraps all entity creation in `DB::transaction()`.
- **`app/Http/Requests/CrearAdministracionRequest.php`** — NEW. Form request with exact validation rules for all 19 SP parameters.
- **`app/Http/Controllers/AdministracionController.php`** — NEW. Wizard controller (create + store).
- **`app/Http/Controllers/Api/ClienteSearchController.php`** — NEW. Autocomplete API matching legacy `buscador-cliente.php` response format `{id, texto, tipo}`.
- **`app/Http/Controllers/Api/PropiedadPorArrendadorController.php`** — NEW. API listing properties by owner.
- **`routes/web.php`** — MODIFIED. Add admin wizard routes inside `[GEN:START/END:custom_routes]`.
- **`routes/api.php`** — MODIFIED. Add autocomplete + property-by-owner endpoints.
- **`resources/views/administracion/create.blade.php`** — NEW. Multi-step wizard view (single-page with Alpine.js state).
- **`resources/views/administracion/partials/`** — NEW. Step partials (arrendador, arrendatario, propiedad, administracion, comision-inicial, egreso, garantia, servicios, contrato).
- **`config/cobro_roles.php`** — NOT MODIFIED. Reused as-is for cobro tipo → role mapping.

### Out of Scope

- Edit/update flow for existing administraciones (future change)
- Delete flow for administraciones
- PDF contract generation/upload (legacy Step 8)
- Migration of `pagar-cobro.php` (separate change)
- Refactoring existing CRUD controllers
- Adding CI pipeline or automated tests (deferred)

## Capabilities

### New Capabilities

- `administracion-wizard`: Multi-step form + service that creates a complete rental administration (Contrato + all related entities) in a single transactional operation, replacing the legacy `sp_crear_contrato` stored procedure.

### Modified Capabilities

- `buscador`: The existing buscador spec covers search within CRUD forms. The new `ClienteSearchController` adds a dedicated JSON endpoint (`GET /api/clientes/search?q=`) returning `{id, texto, tipo}` — this extends the buscador capability to support the wizard's autocomplete needs.

## Approach

### Service: CrearAdministracionService

```php
class CrearAdministracionService
{
    public function crearAdministracion(CrearAdministracionRequest $request): Contrato
    // Wraps DB::transaction() around all SP steps below
}
```

**Transaction boundary**: `DB::transaction()` starts at method entry, wraps ALL steps 1–8, commits on return. Rollback on any `Exception`.

### Step-by-step SP → Laravel Mapping

**Step 1: Resolve/create Arrendador**
```php
$v_arrendador = Cliente::firstOrCreate(['nombre' => $validated['arrendador']]);
$v_arrendador_id = $v_arrendador->id;
```
Edge case: If `arrendador` is null/empty → SP inserts NULL name → Guard: if empty, skip and set `$v_arrendador_id = 1` (corredor default).

**Step 2: Resolve/create Arrendatario**
```php
$v_arrendatario = Cliente::firstOrCreate(['nombre' => $validated['arrendatario']]);
$v_arrendatario_id = $v_arrendatario->id;
```

**Step 3: Resolve/create Propiedad + Unidad**
```php
$v_propiedad = Propiedad::firstOrCreate(
    ['direccion' => $validated['direccion']],
    ['propietario' => $v_arrendador_id]
);
$v_unidad = Unidad::firstOrCreate(
    ['Propiedad_id' => $v_propiedad->id],
    ['nombre' => 'Unidad principal', 'Propiedad_id' => $v_propiedad->id]
);
```

**Step 4: Create Contrato**
```php
$contrato = Contrato::create([
    'Unidad_id'          => $v_unidad->id,
    'administracion'     => $validated['administracion'],
    'comision_inicial'  => $validated['comision_inicial'] ?? null,
    'garantia'          => $validated['garantia'] ?? null,
    'renta'             => $validated['renta'],
    'dia_pago'          => $validated['dia_pago'],
    'comision_mensual'  => $validated['comision_mensual'] ?? 0,
]);
```

**Step 5: Create 3 ParticipanteContrato**
```php
ParticipanteContrato::create(['Cliente_id' => $v_arrendador_id, 'Contrato_id' => $contrato->id, 'rol' => 'Arrendador']);
ParticipanteContrato::create(['Cliente_id' => $v_arrendatario_id, 'Contrato_id' => $contrato->id, 'rol' => 'Arrendatario']);
ParticipanteContrato::create(['Cliente_id' => 1, 'Contrato_id' => $contrato->id, 'rol' => 'Corredor']);
```

**Step 6: Comisión Inicial Cobros** (conditional)
```php
if ($validated['comision_inicial'] !== null && $v_arrendador_id !== 1) {
    if ($validated['cobro_arrendador']) {
        $cobro = Cobro::create(['tipo' => 'Comision inicial arrendador', 'monto' => $validated['comision_inicial'], 'estado' => 'Pendiente', 'fecha_cobro' => now(), 'Contrato_id' => $contrato->id]);
        ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $v_arrendador_id, 'rol' => 'Deudor']);
        ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => 1, 'rol' => 'Acreedor']);
    }
    if ($validated['cobro_arrendatario']) {
        $cobro = Cobro::create(['tipo' => 'Comision inicial arrendatario', 'monto' => $validated['comision_inicial'], 'estado' => 'Pendiente', 'fecha_cobro' => now(), 'Contrato_id' => $contrato->id]);
        ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $v_arrendatario_id, 'rol' => 'Deudor']);
        ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => 1, 'rol' => 'Acreedor']);
    }
}
```

**Step 7: Administración Cobros** (if `administracion = true`)
```php
if ($validated['administracion']) {
    // Ingreso Renta Arrendatario
    $cobro = Cobro::create(['tipo' => 'Ingreso Renta Arrendatario', 'monto' => $validated['renta'], 'estado' => 'Pendiente', 'fecha_cobro' => now(), 'Contrato_id' => $contrato->id]);
    ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $v_arrendatario_id, 'rol' => 'Deudor']);
    ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => 1, 'rol' => 'Acreedor']);

    // Egreso Renta Arrendador (skip if arrendador is corredor)
    if ($v_arrendador_id !== 1) {
        $cobro = Cobro::create(['tipo' => 'Egreso Renta Arrendador', 'monto' => $validated['renta'], 'estado' => 'Pendiente', 'fecha_cobro' => now(), 'Contrato_id' => $contrato->id]);
        ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => 1, 'rol' => 'Deudor']);
        ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $v_arrendador_id, 'rol' => 'Acreedor']);
    }

    // Garantía (if provided)
    if ($validated['garantia'] !== null) {
        $cobro = Cobro::create(['tipo' => 'Ingreso Garantía Arrendatario', 'monto' => $validated['garantia'], 'estado' => 'Pendiente', 'fecha_cobro' => now(), 'Contrato_id' => $contrato->id]);
        ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $v_arrendatario_id, 'rol' => 'Deudor']);
        ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => 1, 'rol' => 'Acreedor']);
        if ($v_arrendador_id !== 1) {
            $cobro = Cobro::create(['tipo' => 'Egreso Garantía Arrendador', 'monto' => $validated['garantia'], 'estado' => 'Pendiente', 'fecha_cobro' => now(), 'Contrato_id' => $contrato->id]);
            ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => 1, 'rol' => 'Deudor']);
            ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $v_arrendador_id, 'rol' => 'Acreedor']);
        }
    }

    // Servicios (Luz, Agua, Gas, Gastos Comunes)
    foreach (['luz', 'agua', 'gas', 'gastos'] as $tipo) {
        if ($validated["dia_{$tipo}"] !== null) {
            Servicio::create([
                'tipo' => ucfirst($tipo) . ($tipo === 'gastos' ? ' Comunes' : ''),
                'dia_pago' => $validated["dia_{$tipo}"],
                'Propiedad_id' => $v_propiedad->id,
                'monto_fijo' => $validated["monto_{$tipo}"] ?? null,
                'estado' => 'Activo',
            ]);
        }
    }
}
```

### Validation Rules (CrearAdministracionRequest)

```php
public function rules(): array
{
    return [
        'arrendador'          => 'required_without:corredor_es_arrendador|string|max:100',
        'corredor_es_arrendador' => 'nullable|boolean',
        'arrendatario'       => 'required|string|max:100',
        'direccion'           => 'required|string|max:150',
        'administracion'     => 'required|boolean',
        'renta'              => 'required_if:administracion,1|nullable|integer|min:0',
        'dia_pago'           => 'required_if:administracion,1|nullable|integer|min:1|max:31',
        'comision_inicial'   => 'nullable|integer|min:0',
        'cobro_arrendador'   => 'nullable|boolean',
        'cobro_arrendatario' => 'nullable|boolean',
        'garantia'           => 'nullable|integer|min:0',
        'comision_mensual'   => 'nullable|integer|min:0',
        'dia_luz'            => 'nullable|integer|min:1|max:31',
        'monto_luz'          => 'nullable|integer|min:0',
        'dia_agua'           => 'nullable|integer|min:1|max:31',
        'monto_agua'         => 'nullable|integer|min:0',
        'dia_gas'            => 'nullable|integer|min:1|max:31',
        'monto_gas'          => 'nullable|integer|min:0',
        'dia_gastos'         => 'nullable|integer|min:1|max:31',
        'monto_gastos'       => 'nullable|integer|min:0',
    ];
}
```

### Routes

```
GET  /administracion/create           → AdministracionController@create   → name: administracion.create
POST /administracion                   → AdministracionController@store    → name: administracion.store
GET  /api/clientes/search?q={query}   → ClienteSearchController@search   → name: api.clientes.search
GET  /api/propiedades/por-arrendador/{id} → PropiedadPorArrendadorController@index → name: api.propiedades.por-arrendador
```

### Controller Method Signatures

```php
// AdministracionController
public function create(): Illuminate\View\View
public function store(CrearAdministracionRequest $request): Illuminate\Http\RedirectResponse

// ClienteSearchController
public function search(Request $request): Illuminate\Http\JsonResponse

// PropiedadPorArrendadorController
public function index(int $id): Illuminate\Http\JsonResponse
```

### Edge Cases (SP → Laravel)

| SP Edge Case | Laravel Handling |
|---|---|
| `v_arrendador_id = 1` (corredor is landlord) → Skip Egreso Renta + Egreso Garantía | `if ($v_arrendador_id !== 1)` guard before creating "Egreso Renta Arrendador" and "Egreso Garantía Arrendador" cobros |
| `p_arrendador` is NULL → SP inserts with nombre=NULL | Reject at validation: `arrendador` is `required_without:corredor_es_arrendador`. When `corredor_es_arrendador=true`, set `$v_arrendador_id = 1` and skip step 1 creation |
| `p_comision_inicial` is NULL → Skip all commission cobros | `if ($validated['comision_inicial'] !== null)` guard on entire step 6 |
| `p_administracion = 0` → Skip all admin cobros + servicios | `if ($validated['administracion'])` guard on entire step 7 |
| `p_garantia` is NULL → Skip garantía cobros | `if ($validated['garantia'] !== null)` guard on garantía subsection |
| Existing Cliente/Propiedad by name → `firstOrCreate` reuses | `Cliente::firstOrCreate(['nombre' => ...])` and `Propiedad::firstOrCreate(['direccion' => ...], ['propietario' => ...])` |
| No dia_pago for servicio → no Servicio record created | Only create Servicio when `dia_{tipo}` is present in request |

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Services/CrearAdministracionService.php` | New | Core business logic replacing sp_crear_contrato |
| `app/Http/Requests/CrearAdministracionRequest.php` | New | Form request validation for all wizard fields |
| `app/Http/Controllers/AdministracionController.php` | New | Wizard controller (create + store) |
| `app/Http/Controllers/Api/ClienteSearchController.php` | New | Autocomplete JSON endpoint |
| `app/Http/Controllers/Api/PropiedadPorArrendadorController.php` | New | Properties by owner JSON endpoint |
| `routes/web.php` | Modified | 2 new routes in custom_routes block |
| `routes/api.php` | Modified | 2 new API routes |
| `resources/views/administracion/create.blade.php` | New | Wizard view |
| `resources/views/administracion/partials/*.blade.php` | New | 9 step partials |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| `firstOrCreate` race condition on duplicate Cliente/Propiedad names | Medium | Wrapped in `DB::transaction()` with `lockForUpdate()` on parent query |
| SP logic differs from reverse-engineered understanding | Medium | Verification test: create same inputs via SP and service, compare all entity counts and field values |
| Wizard UX complexity (9 steps, conditional flows) | High | Alpine.js local state; partials per step; progressive disclosure |
| Corredor edge case (arrendador_id = 1) skips cobros incorrectly | Medium | Explicit conditional blocks mirror SP exactly; covered by acceptance criteria |

## Rollback Plan

All new files — delete them and remove the 4 route entries from `web.php` and `api.php`. No existing models, controllers, or migrations are modified. Zero data migration, zero schema changes.

## Dependencies

- `config/cobro_roles.php` (-existing, unchanged) — used by service for cobro tipo resolution
- Corredor `Cliente` with `id = 1` must exist in database (seeded or verified)

## Success Criteria

- [ ] Creating an administración with all fields produces exactly the same entities as `CALL sp_crear_contrato(...)` with equivalent inputs (verified by manual comparison)
- [ ] `corredor_es_arrendador = true` sets arrendador to Cliente id=1 and skips Egreso cobros
- [ ] `administracion = false` creates Contrato + ParticipanteContrato only (no Cobro, no Servicio)
- [ ] `comision_inicial = null` skips all Comisión Inicial cobros
- [ ] `garantia = null` skips Garantía cobros
- [ ] Existing Cliente/Propiedad names are reused (not duplicated)
- [ ] `DB::transaction()` rolls back ALL creations if any step fails
- [ ] Wizard renders all 9 steps with conditional show/hide matching legacy behavior
- [ ] Cliente autocomplete returns `{id, texto, tipo}` format
- [ ] Properties-by-owner endpoint returns `{id, texto=direccion}[]` format