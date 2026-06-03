# Exploration: Migrar Módulo Administración Legacy → Laravel

## Current State

### Legacy Flow (agregar-administracion.html)

The legacy module is a **multi-step wizard** (9 visual steps) that creates an entire "administración" (rental contract management setup) in a single transactional workflow. Here's the step-by-step breakdown:

**Step 0 — Arrendador (Landlord)**
- Text search with autocomplete against `buscador-cliente.php?q=` (queries `Cliente.nombre`)
- Selects existing client or enters new name
- Checkbox "La propiedad pertenece al corredor" → skips arrendador, sets `estado.arrendador = {texto: "Corredor"}`
- Stored as `{id: int|null, texto: string, tipo: "cliente"|"propiedad"}`

**Step 1 — Arrendatario (Tenant)**
- Same autocomplete search for tenant
- Stored same structure as arrendador

**Step 2 — Propiedad (Property)**
- If arrendador has an `id`, fetches properties via `propiedades-por-arrendador.php?id=` (queries `Propiedad WHERE propietario = id`)
- Dropdown of existing properties OR free-text "Agregar nueva propiedad"
- No `id` for new properties (created inline)

**Step 3 — Administración**
- Number input: `renta` (monthly rent amount)
- Number input: `diaPago` (day of month 1–31)
- Checkbox "Contrato sin administración" → skips steps 4, 5, 6 (comisión inicial, egreso, garantía)
- Sets `administracion = {texto: "Sin administración"|"Con administración"}`

**Step 4 — Comisión Inicial (Initial Commission)**
- Number input: `comision_inicial` amount
- Checkboxes: "Cobrar al Arrendador" / "Cobrar al Arrendatario"
- Or checkbox "No generar comisión inicial"
- Auto-unchecks both if both unchecked, marks "Sin comisión inicial"

**Step 5 — Egreso & Comisión Mensual**
- Number input: `egresoRenta` (amount to transfer to landlord)
- Auto-calculates `comision_mensual = renta - egresoRenta`
- Or checkbox "No generar comisión" → sets both to 0

**Step 6 — Garantía (Guarantee/Deposit)**
- Number input: `garantia` amount
- Or checkbox "No cobrar garantía"

**Step 7 — Servicios**
- Add services: Luz, Agua, Gas, Gastos Comunes
- Each has: tipo (dropdown), dia_pago (number), monto_fijo (optional checkbox + number)
- Can skip with "Seguir sin administrar servicios"

**Step 8 — Contrato**
- File upload for contract PDF OR "Generar contrato" button
- Final "Guardar Administración" button submits everything

### Legacy Controllers

| File | Method | What it does |
|------|--------|-------------|
| `buscador-cliente.php` | GET `?q=` | Searches `Cliente` by `nombre LIKE ?`, returns `{id, texto, tipo}` |
| `propiedades-por-arrendador.php` | GET `?id=` | Queries `Propiedad WHERE propietario = ?`, returns `{id, texto=direccion}` |
| `agregar-administracion.php` | POST JSON | **The key endpoint** — calls `sp_crear_contrato(19 params)` stored procedure |
| `pagar-cobro.php` | POST JSON | Creates Transaccion + Transaccion_Cobro + Origen/Destino for paying a cobro |

### How agregar-administracion.php Works

The single POST endpoint calls a MySQL stored procedure `sp_crear_contrato` with **19 positional parameters**:

```
CALL sp_crear_contrato(
  nombre_arrendador,      -- 1
  nombre_arrendatario,    -- 2
  direccion,              -- 3
  administracion,          -- 4 (0 or 1)
  renta,                  -- 5
  diaPago,                -- 6
  comision_inicial,       -- 7
  cobro_arrendador,       -- 8 (0 or 1)
  cobro_arrendatario,     -- 9 (0 or 1)
  dia_luz,               -- 10
  monto_luz,             -- 11
  dia_agua,              -- 12
  monto_agua,            -- 13
  dia_gas,               -- 14
  monto_gas,             -- 15
  dia_gastos_comunes,    -- 16
  monto_gastos_comunes,  -- 17
  garantia,              -- 18
  comision_mensual       -- 19
)
```

This SP handles **all entity creation inside the database** — it creates Cliente, Propiedad, Unidad, Contrato, ParticipanteContrato, Cobro, ParticipanteCobro, and Servicio records internally. The PHP does NO individual INSERTs.

**Critical observation**: The stored procedure uses `firstOrCreate` logic — if a Cliente with the same name exists, it reuses it. Same for Propiedad. This is important for the migration.

There is **NO explicit BEGIN TRANSACTION / COMMIT / ROLLBACK** in the PHP — the atomicity relies entirely on the stored procedure being a single CALL, which is implicitly committed at the end.

### Database Schema (from Models)

| Table | Key Columns | Relationships |
|-------|-------------|---------------|
| `cliente` | id, nombre, fecha_creacion, rut, email, ocupacion, Nacionalidad_id, estado_civil | hasMany Propiedad (as propietario), ParticipanteContrato, ParticipanteCobro |
| `propiedad` | id, direccion, propietario (FK→cliente.id) | hasOne Unidad, hasMany Cobro, Servicio |
| `unidad` | id, nombre, Propiedad_id | hasMany Contrato, Cobro |
| `contrato` | id, Unidad_id, administracion(bool), comision_inicial, garantia, renta, dia_pago, comision_mensual, fecha_firma, fecha_inicio, fecha_termino, url_pdf, Ciudad_id | hasMany ParticipanteContrato, Cobro, belongsTo Unidad |
| `participante_contrato` | Cliente_id, Contrato_id, rol, monto | belongsTo Cliente, Contrato |
| `cobro` | id, fecha_cobro, estado, tipo, monto, detalle, Contrato_id, Servicio_id, Propiedad_id, Unidad_id | hasMany ParticipanteCobro, belongsTo Contrato/Servicio/Propiedad/Unidad |
| `participante_cobro` | Cliente_id, Cobro_id, monto, rol | belongsTo Cliente, Cobro |
| `servicio` | id, tipo, dia_pago, Propiedad_id, estado, numero_cliente, Empresa_id, monto_fijo | belongsTo Propiedad, hasMany Cobro |

**Cobro tipo enum values** (from validation rules):
- Ingreso Renta Arrendatario, Egreso Renta Arrendador
- Comision inicial arrendador, Comision inicial arrendatario
- Comision Mensual
- Ingreso Garantía Arrendatario, Egreso Garantía Arrendador
- Devolución Garantía Arrendatario
- Aseo Final, Luz, Agua, Gas, Gastos comunes
- Reparación, Extra, Devolución

**ParticipanteContrato rol values**: arrendatario, arrendador, corredor, co-arrendatario, co-arrendador

**ParticipanteCobro rol values**: Deudor, Acreedor

## Laravel Gap Analysis

### What Already Exists in Laravel

| Component | Status | Location |
|-----------|--------|----------|
| **Cliente Model** | ✅ Complete | `app\Models\Cliente.php` |
| **Propiedad Model** | ✅ Complete | `app\Models\Propiedad.php` |
| **Unidad Model** | ✅ Complete | `app\Models\Unidad.php` |
| **Contrato Model** | ✅ Complete | `app\Models\Contrato.php` |
| **ParticipanteContrato Model** | ✅ Complete | `app\Models\ParticipanteContrato.php` |
| **Cobro Model** | ✅ Complete | `app\Models\Cobro.php` |
| **ParticipanteCobro Model** | ✅ Complete | `app\Models\ParticipanteCobro.php` |
| **Servicio Model** | ✅ Complete | `app\Models\Servicio.php` |
| **Cliente CRUD Controller** | ✅ Complete | `app\Http\Controllers\Crud\ClienteController.php` |
| **Contrato CRUD Controller** | ✅ Complete | `app\Http\Controllers\Crud\ContratoController.php` |
| **ParticipanteContrato CRUD** | ✅ Complete | `app\Http\Controllers\Crud\ParticipanteContratoController.php` |
| **Cobro CRUD Controller** | ✅ Complete | `app\Http\Controllers\Crud\CobroController.php` |
| **ParticipanteCobro CRUD** | ✅ Complete | `app\Http\Controllers\Crud\ParticipanteCobroController.php` |
| **Buscador Controller** | ✅ Complete | `app\Http\Controllers\BuscadorController.php` |
| **CobroRelationshipResolver** | ✅ Complete | `app\Services\CobroRelationshipResolver.php` |
| **Cobro role mapping** | ✅ Complete | `config\cobro_roles.php` |
| **CRUD Views** | ✅ Blade views exist | `resources\views\{cliente,cobro,contrato,participante_contrato,participante_cobro}\*` |
| **CRUD Routes** | ✅ generated.php | Full REST for all 7 entities |
| **API Routes** | ✅ api.php | Full apiResource for all entities |

### What's Missing in Laravel

| Component | Gap |
|-----------|-----|
| **Wizard Controller** | ❌ No multi-step "Agregar Administración" controller |
| **Wizard Views** | ❌ No step-by-step wizard Blade/LiveWire/Inertia views |
| **sp_crear_contrato equivalent** | ❌ No service that atomically creates all entities in one transaction |
| **Client Autocomplete API** | ⚠️ BuscadorController exists but doesn't return `tipo` field in the legacy format; needs adaptation or new endpoint |
| **Properties-by-Owner API** | ❌ No endpoint to fetch Propiedad list for a given cliente ID |
| **Transaction wrapper** | ❌ No DB::transaction wrapping for the multi-entity creation |
| **Servicio CRUD** | ⚠️ No dedicated CRUD controller/routes (model exists) |

### Key Difference: Legacy SP vs Laravel CRUD

The legacy module achieves atomicity via `sp_crear_contrato` — a single DB call that creates ALL entities (Cliente × 2, Propiedad, Unidad, Contrato, ParticipanteContrato × 2+, Cobro × N, ParticipanteCobro × 2N, Servicio × N) in one shot.

The Laravel app currently only has individual CRUD controllers. Creating an "administración" would require **multiple separate CRUD calls** or a **new unified service** that wraps everything in `DB::transaction()`.

## Affected Areas

- `C:\xampp\htdocs\src\agregar-administracion.html` — source of truth for the wizard UI/steps
- `C:\xampp\htdocs\src\controlador\agregar-administracion.php` — source of truth for the business logic (SP params)
- `C:\xampp\htdocs\src\controlador\buscador-cliente.php` — client search logic
- `C:\xampp\htdocs\src\controlador\propiedades-por-arrendador.php` — property listing logic
- `app\Models\{Cliente,Propiedad,Unidad,Contrato,ParticipanteContrato,Cobro,ParticipanteCobro,Servicio}.php` — existing models (no changes needed)
- `app\Http\Controllers\Crud\*.php` — existing CRUD controllers (no changes needed, but serve as reference)
- `app\Services\CobroRelationshipResolver.php` — reusable for cobro participant resolution
- `config\cobro_roles.php` — reusable cobro tipo → role mapping
- `routes\web.php` — needs new route group for wizard
- `resources\views\*` — new wizard views needed

## Approaches

### 1. Full Laravel Controller + Service + Blade Wizard

Create a new `AdministracionController` with a multi-step form (wizard), a `CrearAdministracionService` that wraps the multi-entity creation in `DB::transaction()`, and Blade views that reproduce the step flow.

- **Pros**: Full Laravel conventions, IDE-friendly, reusable service, easy to test
- **Cons**: Full-page reloads between steps (or complex JS), new Blade templates needed, more code
- **Effort**: Medium-High

### 2. Laravel API + Inertia/Vue SPA Wizard

Create the same controller/service backend but build the frontend with Inertia.js + Vue (or Alpine.js), mimicking the legacy's AJAX-driven wizard. The API endpoint receives the entire payload at once (like the legacy does) and the service handles everything in one transaction.

- **Pros**: Modern UX, single-page wizard like the legacy, clean separation
- **Cons**: Requires adding Inertia/Vue setup if not present, frontend skill required
- **Effort**: Medium-High

### 3. Laravel API + Legacy HTML Conversation (Staged Migration)

Keep the legacy frontend temporarily, but rewrite the backend to point to a new Laravel API endpoint that uses Eloquent instead of the stored procedure. This is a minimal backend-first approach.

- **Pros**: Smallest initial change, proves Eloquent logic works, frontend can be migrated later
- **Cons**: Still relies on legacy HTML, two codebases coexist longer
- **Effort**: Low-Medium

## Recommendation

**Approach 1 (Full Laravel Controller + Service + Blade Wizard)** is recommended, with the service as the FIRST deliverable. Here's why:

1. The `CrearAdministracionService` encapsulates all the business logic and can be unit-tested independently
2. The service replaces `sp_crear_contrato` with pure Eloquent in `DB::transaction()`
3. Blade wizard views can match the legacy UX step-by-step
4. The existing CRUD controllers and models remain untouched
5. `CobroRelationshipResolver` and `cobro_roles.php` are directly reusable for the cobro participant logic

The implementation should be split into:
- **Phase 1**: `CrearAdministracionService` — the core business logic (creates all entities atomically)
- **Phase 2**: `AdministracionController` + routes — the wizard endpoints
- **Phase 3**: Blade/Alpine.js wizard views — the multi-step UI
- **Phase 4**: Integration tests — verify all 7 entities are created correctly

## Risks

- **Stored procedure logic reverse-engineering**: `sp_crear_contrato` does `firstOrCreate` logic for clients and properties by name. The Laravel service must replicate this exactly (especially the "if name exists, reuse" behavior). We DON'T have the SP source code — it's only referenced by CALL, we need to extract it from the database.
- **Cobro tipo mapping**: The wizard creates multiple cobros with specific tipos. The mapping from wizard fields to cobro.tipo values and participante_cobro roles is complex and must match `config/cobro_roles.php` exactly.
- **"Sin administración" flow**: When this checkbox is checked, it skips comisión inicial, egreso, garantía, and servicios — this conditional logic must be handled in both the service and the wizard UI.
- **Servicio creation**: The legacy SP creates Servicio records with dia_pago and monto_fijo. The Laravel app's Servicio model already supports these fields, but no dedicated CRUD exists for them.
- **Corredor role**: The "La propiedad pertenece al corredor" checkbox has no equivalent in the current Laravel app — it requires special handling in ParticipanteContrato (rol='Corredor').

## Ready for Proposal

Yes — the exploration is complete. The next step is `sdd-propose` to define the change scope, approach, and rollback plan.