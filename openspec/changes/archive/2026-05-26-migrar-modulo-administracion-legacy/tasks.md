# Tasks: Migrar Módulo Administración Legacy

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~870 lines (5 PHP new + 10 Blade new + 2 route modified) |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Files | Notes |
|------|------|-------|-------|
| 1 | Backend core: Service + Form Request + Wizard Controller | `CrearAdministracionService.php`, `CrearAdministracionRequest.php`, `AdministracionController.php` | ~250 lines; base = `develop` |
| 2 | API controllers + Routes | `ClienteSearchController.php`, `PropiedadPorArrendadorController.php`, `web.php`, `api.php` | ~120 lines; base = `develop` |
| 3 | Wizard Blade views | `create.blade.php` + 9 partials | ~500 lines; base = `develop` |

---

## Phase 1: Backend Core (Work Unit 1)

- [ ] 1.1 Create `app/Services/CrearAdministracionService.php`
  - **File**: `app/Services/CrearAdministracionService.php`
  - **Class**: `CrearAdministracionService`
  - **Method**: `crearAdministracion(CrearAdministracionRequest $request): Contrato`
  - **Behavior**: Wraps 8 entity-creation steps in `DB::transaction()` — resolve/create Cliente (arrendador + arrendatario via `firstOrCreate` by rut or nombre), create/update Propiedad (firstOrCreate by direccion+propietario), create/update Unidad (firstOrCreate by Propiedad_id), create Contrato, create 3 ParticipanteContrato records (Arrendador, Arrendatario, Corredor=id=1), conditionally create Cobro×N + ParticipanteCobro×2N pairs (when administracion=true, skips Egreso when corredor=arrendador, skips null comision_inicial/garantia), conditionally create Servicio×N (when dia_pago set)
  - **Verification**: `php artisan tinker` — instantiate service, call `crearAdministracion()` with valid request, assert Contrato has 3 participante_contratos

- [ ] 1.2 Create `app/Http/Requests/CrearAdministracionRequest.php`
  - **File**: `app/Http/Requests/CrearAdministracionRequest.php`
  - **Class**: `CrearAdministracionRequest extends FormRequest`
  - **Rules**: 19 rules — arrendador_rut (nullable|string|max:12), arrendador_nombre (required|string|max:255), arrendador_email (nullable|email), arrendador_estado_civil (nullable|in:Soltero,Casado,Viudo,Divorcido); same pattern for arrendatario; propiedad_direccion (required|string|max:500), unidad_nombre (nullable|string); administracion (required|boolean), renta (required|integer|min:0), comision_mensual (nullable|integer|min:0), dia_pago (nullable|integer|between:1,28), comision_inicial (nullable|integer|min:0), garantia (nullable|integer|min:0); fecha_firma (nullable|date), fecha_inicio (required|date|after_or_equal:fecha_firma), fecha_termino (nullable|date|after:fecha_inicio); Ciudad_id (nullable|integer|exists:ciudad,id), url_pdf (nullable|url); servicio_Luz/Agua/Gas/'Gastos comunes' (nullable|boolean). `authorize()` returns `true`. Custom error messages in Spanish.
  - **Verification**: `php artisan route:list --name=administracion` shows no errors; submit invalid data, assert validation errors returned

- [ ] 1.3 Create `app/Http/Controllers/AdministracionController.php`
  - **File**: `app/Http/Controllers/AdministracionController.php`
  - **Class**: `AdministracionController extends Controller`
  - **Methods**: `create()` — loads `Ciudad::count()` and `Ciudad::orderBy('nombre')->get(['id','nombre'])` into view; `store(CrearAdministracionRequest, CrearAdministracionService)` — calls `$service->crearAdministracion($request)`, redirects `route('contrato.show', $contrato->id)` with success flash, catches `\Throwable` → logs + redirects back with error flash and `withInput()`
  - **Verification**: `GET /administracion/create` returns view; `POST /administracion` with valid data redirects to contrato.show

---

## Phase 2: API Controllers + Routes (Work Unit 2)

- [ ] 2.1 Create `app/Http/Controllers/Api/ClienteSearchController.php`
  - **File**: `app/Http/Controllers/Api/ClienteSearchController.php`
  - **Class**: `ClienteSearchController extends Controller`
  - **Method**: `search(Request $request): JsonResponse` — returns `[]` if `strlen($q) < 2`; otherwise `Cliente::where('nombre','like',"%{$q}%")->orWhere('rut','like',"%{$q}%")->orderBy('nombre')->limit(20)->get(['id','nombre','rut'])` mapped to `[{id, texto: "{$cliente->nombre} ({$cliente->rut})", tipo: 'cliente'}]`
  - **Verification**: `GET /api/clientes/search?q=ju` returns JSON array; `GET /api/clientes/search?q=a` (single char) returns `[]`

- [ ] 2.2 Create `app/Http/Controllers/Api/PropiedadPorArrendadorController.php`
  - **File**: `app/Http/Controllers/Api/PropiedadPorArrendadorController.php`
  - **Class**: `PropiedadPorArrendadorController extends Controller`
  - **Method**: `index(int $arrendadorId): JsonResponse` — `Propiedad::where('propietario', $arrendadorId)->with('unidad')->get(['id','direccion'])` mapped to `[{id, direccion, unidad_id}]`
  - **Verification**: `GET /api/propiedades/por-arrendador/1` returns JSON array; empty array for owner with no properties

- [ ] 2.3 Modify `routes/web.php` — add wizard routes inside `[GEN:START:custom_routes]` block
  - **File**: `routes/web.php`
  - **Change**: Inside `[GEN:START:custom_routes]` after existing custom routes, add `// [GEN:START:administracion_routes]` block with `Route::get('/administracion/create', [AdministracionController::class, 'create'])->name('administracion.create');` and `Route::post('/administracion', [AdministracionController::class, 'store'])->name('administracion.store');`
  - **Verification**: `php artisan route:list --name=administracion` shows both routes

- [ ] 2.4 Modify `routes/api.php` — append API routes at end
  - **File**: `routes/api.php`
  - **Change**: Append `// [GEN:START:administracion_api_routes]` block with `Route::get('/clientes/search', [ClienteSearchController::class, 'search'])->name('api.clientes.search');` and `Route::get('/propiedades/por-arrendador/{id}', [PropiedadPorArrendadorController::class, 'index'])->name('api.propiedades.por-arrendador');`
  - **Verification**: `php artisan route:list --name=api.clientes` and `--name=api.propiedades` show routes

---

## Phase 3: Blade Views (Work Unit 3)

- [ ] 3.1 Create `resources/views/administracion/create.blade.php`
  - **File**: `resources/views/administracion/create.blade.php`
  - **Behavior**: Extends `layouts.app`. Container div with `x-data="administracionWizard()"`. Step indicator showing 9 steps (Arrendador, Arrendatario, Propiedad, Contrato, Fechas, Cobros, Servicios, Resumen, Corredor). `<form>` POSTing to `route('administracion.store')` with `@csrf`. Nine `x-show` partial includes. Navigation buttons (Anterior/Siguiente + Crear Administración on step 9). Alpine.js wizard function. Loads Alpine from CDN (`defer`). Includes inline `buscador()` setup for arrendador and arrendatario autocomplete fields using `#input-arrendador-search`/`#lista-arrendador` pattern with `tipo: 'cliente-api'`. Shows `session('success')` and `$errors` flash styling.
  - **Verification**: `GET /administracion/create` renders without errors; Alpine step navigation increments/decrements `currentStep`; form POSTs to correct route

- [ ] 3.2 Create `resources/views/administracion/partials/step-01-arrendador.blade.php`
  - **File**: `resources/views/administracion/partials/step-01-arrendador.blade.php`
  - **Fields**: `arrendador_rut` (text), `arrendador_nombre` (text with autocomplete `#input-arrendador-search` + hidden `#arrendador_id`), `arrendador_email` (email), `arrendador_estado_civil` (select: Soltero/Casado/Viudo/Divorcido). Uses Bootstrap form-floating pattern. All inputs use `value="{{ old('arrendador_nombre') }}"` pattern.
  - **Verification**: Each field preserves old value on validation error; autocomplete list `#lista-arrendador` appears on typing

- [ ] 3.3 Create `resources/views/administracion/partials/step-02-arrendatario.blade.php`
  - **File**: `resources/views/administracion/partials/step-02-arrendatario.blade.php`
  - **Fields**: Identical pattern to step-01 for `arrendatario_rut`, `arrendatario_nombre` (with autocomplete `#input-arrendatario-search` + `#arrendatario_id`), `arrendatario_email`, `arrendatario_estado_civil`
  - **Verification**: Same as step-01 with independent field IDs

- [ ] 3.4 Create `resources/views/administracion/partials/step-03-propiedad.blade.php`
  - **File**: `resources/views/administracion/partials/step-03-propiedad.blade.php`
  - **Fields**: `propiedad_direccion` (text, required), `unidad_nombre` (text, optional), `Ciudad_id` (select populated from `$ciudadOptions`). Uses `old()` for all values.
  - **Verification**: Ciudad select shows all cities from DB; form preserves values on error

- [ ] 3.5 Create `resources/views/administracion/partials/step-04-contrato.blade.php`
  - **File**: `resources/views/administracion/partials/step-04-contrato.blade.php`
  - **Fields**: `administracion` (select Sí/No, values 1/0), `renta` (number, required), `comision_mensual` (number, optional), `dia_pago` (number 1-28, optional). Alpine `x-model` bindings for conditional visibility in steps 6-7.
  - **Verification**: `x-model="administracion"` binds select value; numeric inputs validate as integer

- [ ] 3.6 Create `resources/views/administracion/partials/step-05-fechas.blade.php`
  - **File**: `resources/views/administracion/partials/step-05-fechas.blade.php`
  - **Fields**: `fecha_firma` (date, optional), `fecha_inicio` (date, required), `fecha_termino` (date, optional), `url_pdf` (url, optional). Uses Bootstrap date input styling.
  - **Verification**: Date pickers work; URL validates on submit

- [ ] 3.7 Create `resources/views/administracion/partials/step-06-cobros-iniciales.blade.php`
  - **File**: `resources/views/administracion/partials/step-06-cobros-iniciales.blade.php`
  - **Fields**: `comision_inicial` (number, optional), `garantia` (number, optional). Fieldset or div wrapper with `x-show="administracion === '1'"` so fields only appear when administración is enabled.
  - **Verification**: Fields hidden when administracion=false; visible when true

- [ ] 3.8 Create `resources/views/administracion/partials/step-07-servicios.blade.php`
  - **File**: `resources/views/administracion/partials/step-07-servicios.blade.php`
  - **Fields**: Checkboxes for `servicio_Luz`, `servicio_Agua`, `servicio_Gas`, `servicio_Gastos comunes`. Wrapper with `x-show="administracion === '1' && diaPago"` so section only appears when both administracion=true AND dia_pago is filled.
  - **Verification**: All 4 checkboxes render; wrapper respects conditional Alpine expression

- [ ] 3.9 Create `resources/views/administracion/partials/step-08-resumen.blade.php`
  - **File**: `resources/views/administracion/partials/step-08-resumen.blade.php`
  - **Behavior**: Read-only summary showing all entered form values. Uses Alpine `$data` to bind and display `currentStep === 8` visibility. Shows placeholder text for empty fields. Layout matches Bootstrap card/table style.
  - **Verification**: On reaching step 8, all previously entered values are displayed read-only

- [ ] 3.10 Create `resources/views/administracion/partials/step-09-corredor.blade.php`
  - **File**: `resources/views/administracion/partials/step-09-corredor.blade.php`
  - **Behavior**: Static display showing "Corredor: [Nombre from Cliente id=1]". No form inputs. Hidden field not needed — corredor_id is hardcoded to 1 in service. Shows info alert with corredor name.
  - **Verification**: Step 9 shows corredor info; no hidden ID field submitted

---

## Phase 4: Integration Verification

- [ ] 4.1 Manual browser verification — happy path
  - **Scenario**: Fill all 9 wizard steps with valid data, submit
  - **Expected**: Redirect to `contrato.show` with success flash; DB contains 1 Contrato, 3 ParticipanteContrato, correct Cobros (Ingreso Renta Arrendatario always, Egreso Renta Arrendador when arrendador≠id=1, comision inicial pairs when filled, garantia pairs when filled), correct ParticipanteCobro records
  - **Verification**: `SELECT * FROM contrato` + joined tables shows correct data

- [ ] 4.2 Edge case: `administracion = false`
  - **Scenario**: Step 4 set to "No"
  - **Expected**: No Cobro or Servicio records created; only Contrato + 3 ParticipanteContrato exist
  - **Verification**: `SELECT COUNT(*) FROM cobro WHERE Contrato_id=X` returns 0

- [ ] 4.3 Edge case: existing Cliente reuse
  - **Scenario**: Enter RUT that matches an existing Cliente in step 1
  - **Expected**: Cliente record NOT duplicated; `firstOrCreate` reused existing record
  - **Verification**: `SELECT COUNT(*) FROM cliente WHERE rut='{entered_rut}'` returns 1

- [ ] 4.4 Edge case: corredor = arrendador (id=1)
  - **Scenario**: Set arrendador to existing Cliente with id=1
  - **Expected**: Egreso Renta Arrendador and Egreso Garantía Arrendador cobros are NOT created
  - **Verification**: `SELECT tipo FROM cobro WHERE Contrato_id=X` contains no "Egreso" tipos

- [ ] 4.5 Transaction rollback verification
  - **Scenario**: Submit with invalid `Ciudad_id` (non-existent FK)
  - **Expected**: No Contrato or related records created; user redirected back with error
  - **Verification**: `SELECT COUNT(*) FROM contrato` unchanged; error flash visible

---

## Implementation Order

1. **Tasks 1.1 → 1.3** (Backend core) — build first, no dependencies
2. **Tasks 2.1 → 2.4** (API + Routes) — depends on models used in service (already exist), can parallelize
3. **Tasks 3.1 → 3.10** (Blade views) — depends on routes being registered and controller `create()` method signature
4. **Tasks 4.1 → 4.5** (Verification) — runs after all code is deployed

---

## Dependencies Graph

```
1.1 (Service)          ← no deps
1.2 (Form Request)     ← no deps
1.3 (Controller)       ← 1.1, 1.2
2.1 (ClienteSearch)    ← no deps (uses existing Cliente model)
2.2 (PropiedadApi)     ← no deps (uses existing models)
2.3 (web routes)       ← 1.3
2.4 (api routes)       ← 2.1, 2.2
3.1 (main blade)       ← 2.3 (route registered), 1.3 (create() method)
3.2–3.10 (partials)   ← 3.1
4.1–4.5 (verification) ← all above
```
