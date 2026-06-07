# Administracion Wizard Specification

## Purpose

Multi-step wizard that creates a complete rental administration (Contrato + Cliente x2 + Propiedad + Unidad + ParticipanteContrato x3 + Cobro xN + ParticipanteCobro x2N + Servicio xN) in a single `DB::transaction()`, replacing the legacy `sp_crear_contrato` stored procedure.

## Requirements

### Requirement: Wizard form validation

The system MUST validate all 19 fields via `CrearAdministracionRequest` before entering the service layer. Validation failure returns HTTP 422 with field-specific error messages.

| Field | Rule | Error Message |
|-------|------|---------------|
| `arrendador` | `required_without:corredor_es_arrendador\|string\|max:100` | Required unless corredor is arrendador |
| `corredor_es_arrendador` | `nullable\|boolean` | Must be true/false |
| `arrendatario` | `required\|string\|max:100` | Required |
| `direccion` | `required\|string\|max:150` | Required |
| `administracion` | `required\|boolean` | Required |
| `renta` | `required_if:administracion,1\|nullable\|integer\|min:0` | Required when administracion is active |
| `dia_pago` | `required_if:administracion,1\|nullable\|integer\|between:1,28` | Required when administracion is active, 1-28 |
| `comision_inicial` | `nullable\|integer\|min:0` | Must be non-negative integer |
| `cobro_arrendador` | `nullable\|boolean` | Must be true/false |
| `cobro_arrendatario` | `nullable\|boolean` | Must be true/false |
| `garantia` | `nullable\|integer\|min:0` | Must be non-negative integer |
| `comision_mensual` | `nullable\|integer\|min:0` | Must be non-negative integer |
| `dia_luz` | `nullable\|integer\|between:1,28` | 1-28 if provided |
| `monto_luz` | `nullable\|integer\|min:0` | Non-negative if provided |
| `dia_agua` | `nullable\|integer\|between:1,28` | 1-28 if provided |
| `monto_agua` | `nullable\|integer\|min:0` | Non-negative if provided |
| `dia_gas` | `nullable\|integer\|between:1,28` | 1-28 if provided |
| `monto_gas` | `nullable\|integer\|min:0` | Non-negative if provided |
| `dia_gastos` | `nullable\|integer\|between:1,28` | 1-28 if provided |
| `monto_gastos` | `nullable\|integer\|min:0` | Non-negative if provided |

#### Scenario: Missing arrendador fails validation

- GIVEN `corredor_es_arrendador` is absent or false
- WHEN POST /administracion with `arrendador` empty
- THEN response is 422 with error on `arrendador`

#### Scenario: Missing renta when administracion=true fails

- GIVEN `administracion = 1`
- WHEN POST /administracion with `renta` absent
- THEN response is 422 with error on `renta`

#### Scenario: Negative comision_inicial fails

- WHEN POST /administracion with `comision_inicial = -500`
- THEN response is 422 with error on `comision_inicial`

#### Scenario: dia_pago=29 fails validation

- GIVEN `administracion = 1`
- WHEN POST /administracion with `dia_pago = 29`
- THEN response is 422 with error on `dia_pago`

#### Scenario: dia_pago=28 passes validation

- GIVEN `administracion = 1`
- WHEN POST /administracion with `dia_pago = 28`
- THEN validation passes for `dia_pago`

#### Scenario: servicio dia=30 fails validation

- GIVEN `servicios = [{tipo: 'Luz', dia: 30}]`
- WHEN POST /administracion
- THEN response is 422 with error on `servicios.0.dia`

### Requirement: Atomic entity creation

The system MUST create all entities within a single `DB::transaction()`. On any exception, ALL database changes MUST be rolled back — zero partial rows remain. Every Cobro row created in the transaction MUST have `Propiedad_id` and `Unidad_id` populated (not NULL).

#### Scenario: Full creation succeeds

- GIVEN valid input with all fields populated, administracion=true, comision_inicial=100000, garantia=200000, corredor_es_arrendador=false
- WHEN POST /administracion succeeds
- THEN exactly 2 new Cliente rows (arrendador, arrendatario), 1 Propiedad, 1 Unidad, 1 Contrato, 3 ParticipanteContrato (Arrendador, Arrendatario, Corredor), 6 Cobro rows (Comision inicial arrendador x1, Comision inicial arrendatario x1, Ingreso Renta Arrendatario x1, Egreso Renta Arrendador x1, Ingreso Garantia Arrendatario x1, Egreso Garantia Arrendador x1), 12 ParticipanteCobro rows (2 per Cobro), and 0-4 Servicio rows (one per servicio with dia_pago set) are created
- AND every Cobro row has Propiedad_id and Unidad_id populated (not NULL)
- AND HTTP 302 redirect to `propiedad.ficha` for the correct property

#### Scenario: Transaction rollback on failure

- GIVEN valid input that triggers a database constraint violation mid-transaction (e.g., foreign key violation on ParticipanteContrato)
- WHEN POST /administracion is executed
- THEN the transaction rolls back
- AND no new rows exist in Cliente, Propiedad, Unidad, Contrato, ParticipanteContrato, Cobro, ParticipanteCobro, or Servicio tables that were not present before the request
- AND HTTP 500 is returned

### Requirement: Cobro FK population on creation

Every Cobro created by the wizard MUST have `Propiedad_id` and `Unidad_id` populated at application level, derived from the Propiedad and Unidad created/reused in the same transaction. This ensures dashboard grouping by property works for newly created cobros.

#### Scenario: Cobros created with FKs populated

- GIVEN valid administracion input with administracion=true, renta=500000
- WHEN POST /administracion succeeds
- THEN every Cobro row has Propiedad_id and Unidad_id matching the Propiedad and Unidad
- AND no Cobro row has NULL Propiedad_id or NULL Unidad_id

#### Scenario: FKs populated when entities are reused

- GIVEN Propiedad "Av. Italia 100" exists with id=10, Unidad id=20
- WHEN POST /administracion with `direccion = "Av. Italia 100"`, administracion=true
- THEN all created Cobro rows have Propiedad_id=10 and Unidad_id=20

### Requirement: Redirect after successful creation

After successfully creating an administracion, the system MUST redirect to `propiedad.ficha` using the property ID derived from `$contrato->unidad->Propiedad_id`. The Contrato model MUST have the `unidad` relationship eager-loaded before accessing `Propiedad_id` to avoid lazy-load failures.

#### Scenario: Redirect uses unidad->Propiedad_id

- GIVEN valid administracion input that creates a new Contrato linked to Unidad id=5
- AND Unidad id=5 has Propiedad_id=10
- WHEN POST /administracion succeeds
- THEN redirect goes to `propiedad.ficha` with `id=10`

#### Scenario: Eager-load prevents lazy-load crash

- GIVEN a Contrato is created successfully
- WHEN the redirect is computed
- THEN `$contrato->unidad` is already loaded (no additional query needed)
- AND `$contrato->unidad->Propiedad_id` returns the correct integer

### Requirement: Comisión mensual auto-initialization

When the user enters step 6 (Egreso) for the first time, the system MUST initialize `comision_mensual` to `Math.round(renta * 0.1)` (10% of renta, rounded). When the user changes the `renta` field AFTER step 6 has been visited, the system MUST recalculate `comision_mensual` and `egreso_renta` accordingly.

#### Scenario: Step 6 entry initializes comision to 10% of renta

- GIVEN user is on step 5, renta=500000
- WHEN user advances to step 6
- THEN `comision_mensual` input is set to 50000 (10% of 500000)
- AND `egreso_renta` input is set to 450000 (renta - comision)

#### Scenario: Renta change after step 6 visit recalculates

- GIVEN user has visited step 6, renta was 500000, comision_mensual was 50000
- WHEN user navigates back to step 4 and changes renta to 600000
- THEN `comision_mensual` is recalculated to 60000 (10% of 600000)
- AND `egreso_renta` is recalculated to 540000

#### Scenario: NoComisionMensual checkbox overrides auto-calc

- GIVEN user enters step 6, renta=500000
- WHEN user checks "No generar comisión"
- THEN `comision_mensual` is set to 0
- AND `egreso_renta` is set to renta (500000)

### Requirement: Corredor as arrendador edge case

When `corredor_es_arrendador = true`, the system MUST set arrendador_id to Cliente id=1 (the corredor), skip creating a new Cliente for arrendador, and skip all Egreso cobros (Egreso Renta Arrendador, Egreso Garantía Arrendador).

#### Scenario: Corredor is arrendador — no new Cliente, no Egreso cobros

- GIVEN `corredor_es_arrendador = true`, `arrendador` is empty, administracion=true, renta=500000, garantia=1000000
- WHEN POST /administracion succeeds
- THEN exactly 1 new Cliente (arrendatario only), 1 Propiedad, 1 Unidad, 1 Contrato, 3 ParticipanteContrato (Arrendador=Cliente id=1, Arrendatario, Corredor=Cliente id=1)
- AND 4 Cobro rows: Ingreso Renta Arrendatario, Ingreso Garantia Arrendatario, Comision inicial arrendatario (if cobro_arrendatario=true), Comision inicial arrendador (if cobro_arrendador=true) — NO Egreso Renta Arrendador, NO Egreso Garantía Arrendador
- AND ParticipanteContrato for Arrendador references Cliente id=1

### Requirement: Administracion=false skips cobros and servicios

When `administracion = false`, the system MUST create only Cliente x2, Propiedad, Unidad, Contrato, and ParticipanteContrato x3. No Cobro, ParticipanteCobro, or Servicio rows are created.

#### Scenario: No administracion — Contrato only, no cobros

- GIVEN `administracion = false`, all other fields valid
- WHEN POST /administracion succeeds
- THEN 2 Cliente, 1 Propiedad, 1 Unidad, 1 Contrato, 3 ParticipanteContrato are created
- AND 0 Cobro rows, 0 ParticipanteCobro rows, 0 Servicio rows are created
- AND Contrato.administracion = 0

### Requirement: Null comision_inicial skips commission cobros

When `comision_inicial` is null or absent, the system MUST NOT create any Cobro with tipo "Comision inicial arrendador" or "Comision inicial arrendatario".

#### Scenario: Null comision — no commission cobros

- GIVEN `comision_inicial` is null, administracion=true, renta=500000
- WHEN POST /administracion succeeds
- THEN no Cobro rows with tipo containing "Comision inicial" exist
- AND all other expected cobros (Ingreso Renta, Egreso Renta, Garantia) are created normally

### Requirement: Null garantia skips garantia cobros

When `garantia` is null or absent, the system MUST NOT create "Ingreso Garantía Arrendatario" or "Egreso Garantía Arrendador" cobros.

#### Scenario: Null garantia — no garantia cobros

- GIVEN `garantia` is null, administracion=true, renta=500000, corredor_es_arrendador=false
- WHEN POST /administracion succeeds
- THEN no Cobro rows with tipo containing "Garantía" exist
- AND Ingreso Renta Arrendatario and Egreso Renta Arrendador cobros are created normally

### Requirement: Existing entities are reused, not duplicated

The system MUST use `firstOrCreate` for Cliente (by nombre) and Propiedad (by direccion). If a matching record exists, it is reused — no duplicate row is created.

#### Scenario: Existing Cliente reused

- GIVEN a Cliente with nombre="Juan Perez" already exists with id=42
- WHEN POST /administracion with `arrendador = "Juan Perez"`
- THEN no new Cliente row is created for arrendador
- AND ParticipanteContrato references Cliente id=42

#### Scenario: Existing Propiedad reused

- GIVEN a Propiedad with direccion="Av. Libertador 1234" already exists with id=10
- WHEN POST /administracion with `direccion = "Av. Libertador 1234"`
- THEN no new Propiedad row is created
- AND a new Unidad is created linked to Propiedad id=10

### Requirement: Frontend dia_pago validation range

The wizard frontend MUST reject dia_pago values outside 1-28 in step 4 and step 8 service day inputs. The `dayOutOfRange` flag MUST be set when `dia < 1 || dia > 28`.

#### Scenario: Frontend rejects dia_pago=29 in step 4

- GIVEN user is on step 4
- WHEN user enters `dia_pago = 29` and clicks "Añadir"
- THEN validation error is shown: "El día de pago debe estar entre 1 y 28"

#### Scenario: Frontend rejects service dia=31 in step 8

- GIVEN user is adding a service in step 8
- WHEN user enters `dia = 31` and confirms
- THEN the service is marked with `dayOutOfRange = true`
- AND form submission is blocked with error message

#### Scenario: Frontend accepts dia_pago=28

- GIVEN user is on step 4
- WHEN user enters `dia_pago = 28` and clicks "Añadir"
- THEN validation passes and user proceeds to next step

### Requirement: ContratoController dia_pago validation

The CRUD ContratoController MUST validate `dia_pago` as `between:1,28` in its `store` and `update` methods.

#### Scenario: CRUD create rejects dia_pago=30

- WHEN POST /contrato with `dia_pago = 30`
- THEN validation fails

#### Scenario: CRUD create accepts dia_pago=15

- WHEN POST /contrato with `dia_pago = 15`
- THEN validation passes

### Requirement: Servicio creation is conditional on dia_pago

For each servicio type (luz, agua, gas, gastos), a Servicio row is created ONLY if the corresponding `dia_{tipo}` field is present and non-null.

#### Scenario: Only luz and gas have dia_pago — only 2 Servicios created

- GIVEN `dia_luz = 15`, `dia_gas = 20`, `dia_agua` is null, `dia_gastos` is null
- WHEN POST /administracion with administracion=true
- THEN exactly 2 Servicio rows are created: "Luz" (dia_pago=15) and "Gas" (dia_pago=20)
- AND no "Agua" or "Gastos Comunes" Servicio rows exist

### Requirement: Wizard renders 9 steps with conditional visibility

The wizard view at GET /administracion/create MUST render 9 steps: (1) Arrendador, (2) Arrendatario, (3) Propiedad, (4) Administracion settings, (5) Comision Inicial, (6) Egreso, (7) Garantia, (8) Servicios, (9) Contrato details. Steps 5-7 MUST be conditionally shown/hidden based on administracion toggle.

#### Scenario: Administracion toggle shows/hides conditional steps

- GIVEN user is on step 4 (Administracion settings)
- WHEN user toggles administracion to false
- THEN steps 5 (Comision Inicial), 6 (Egreso), and 7 (Garantia) are hidden
- AND step 8 (Servicios) is hidden
- AND step 9 (Contrato) still shows renta and dia_pago as optional

### Requirement: Cliente autocomplete API

The system MUST provide GET /api/clientes/search?q={query} returning JSON with format `{data: [{id, texto, tipo}]}` where texto is the cliente nombre and tipo is "cliente".

#### Scenario: Search returns matching clientes

- GIVEN 3 clientes exist: "Juan Perez", "Maria Lopez", "Carlos Ruiz"
- WHEN GET /api/clientes/search?q=juan
- THEN response is 200 with `{data: [{id: N, texto: "Juan Perez", tipo: "cliente"}]}`
- AND "Maria Lopez" and "Carlos Ruiz" are NOT in results

#### Scenario: Empty query returns empty array

- WHEN GET /api/clientes/search?q=
- THEN response is 200 with `{data: []}`

### Requirement: Properties-by-owner API

The system MUST provide GET /api/propiedades/por-arrendador/{id} returning JSON with format `{data: [{id, texto}]}` where texto is the propiedad direccion.

#### Scenario: Returns properties for given arrendador

- GIVEN arrendador id=5 owns 2 propiedades: "Av. Italia 100" and "Calle 18 555"
- WHEN GET /api/propiedades/por-arrendador/5
- THEN response is 200 with `{data: [{id: X, texto: "Av. Italia 100"}, {id: Y, texto: "Calle 18 555"}]}`

#### Scenario: Arrendador with no properties returns empty

- GIVEN arrendador id=99 exists but owns 0 propiedades
- WHEN GET /api/propiedades/por-arrendador/99
- THEN response is 200 with `{data: []}`

### Requirement: Cobro and ParticipanteCobro pairing

Every Cobro created MUST have exactly 2 associated ParticipanteCobro rows: one Deudor and one Acreedor. The Deudor/Acreedor assignment follows this table:

| Cobro tipo | Deudor | Acreedor |
|---|---|---|
| Comision inicial arrendador | Arrendador | Corredor (id=1) |
| Comision inicial arrendatario | Arrendatario | Corredor (id=1) |
| Ingreso Renta Arrendatario | Arrendatario | Corredor (id=1) |
| Egreso Renta Arrendador | Corredor (id=1) | Arrendador |
| Ingreso Garantía Arrendatario | Arrendatario | Corredor (id=1) |
| Egreso Garantía Arrendador | Corredor (id=1) | Arrendador |

#### Scenario: Commission cobro has correct Deudor/Acreedor

- GIVEN `comision_inicial = 100000`, `cobro_arrendador = true`, corredor_es_arrendador=false
- WHEN POST /administracion succeeds
- THEN a Cobro with tipo "Comision inicial arrendador" exists
- AND it has exactly 2 ParticipanteCobro: Deudor=arrendador Cliente_id, Acreedor=Cliente id=1

---

## Delta: Loading Indicators (Archived 2026-05-26)

### Requirement: Propiedad select spinner during loadPropiedadesPorArrendador

Select MUST show `spinner-border` (not plain text) while `GET /api/propiedades/por-arrendador/{id}` fetches. Disabled during loading.

#### Scenario: Spinner lifecycle

- GIVEN user selects arrendador, fetch begins
- THEN select shows spinner, disabled
- WHEN API returns properties, THEN spinner removed, options populated
- WHEN API returns empty, THEN spinner removed, "Sin propiedades" shown
- WHEN fetch fails, THEN spinner removed, error displayed

---

## Delta: Property Select Fallback Text Input (Archived 2026-06-05)

### Requirement: Property input fallback for new or empty arrendador

When the arrendador is new (not yet in the database, no `hidden-arrendador-id`) or when an existing arrendador has zero properties, the system MUST show the text input (`#nuevaPropiedadInput`) directly in Step 3 instead of an empty `<select>` dropdown. This avoids presenting the user with a useless select containing only "Seleccionar propiedad..." and "Agregar nueva propiedad".

#### Scenario: New arrendador — text input shown immediately

- GIVEN user typed a new arrendador name in Step 1 (not selected from buscador, `hidden-arrendador-id` is empty)
- WHEN user clicks "Añadir" to proceed to Step 3
- THEN `#propiedadSelect` is hidden and `#nuevaPropiedadInput` is shown with focus
- AND the user can type the property address directly

#### Scenario: Existing arrendador with properties — select shown as before

- GIVEN user selected an existing arrendador from buscador (`hidden-arrendador-id` is set)
- WHEN the API returns properties for that arrendador
- THEN `#propiedadSelect` is shown with populated options as before
- AND `#nuevaPropiedadInput` remains hidden

#### Scenario: Existing arrendador with no properties — text input shown (existing behavior preserved)

- GIVEN user selected an existing arrendador from buscador
- WHEN the API returns an empty array (`data.length === 0`)
- THEN `#propiedadSelect` is hidden and `#nuevaPropiedadInput` is shown
- AND this behavior is unchanged from current implementation

#### Scenario: API fetch failure — text input shown (existing behavior preserved)

- GIVEN user selected an existing arrendador from buscador
- WHEN the API call to `/api/propiedades/por-arrendador/{id}` fails (network error)
- THEN `#propiedadSelect` is hidden and `#nuevaPropiedadInput` is shown
- AND this behavior is unchanged from current implementation

### Requirement: Step 3 state reset on arrendador change

When the user navigates back to Step 1 and changes the arrendador (either selecting an existing one or clearing to type a new name), the system MUST reset Step 3's property selection state to avoid showing stale data from the previous arrendador.

#### Scenario: User switches from new arrendador to existing arrendador

- GIVEN Step 3 shows text input (previous arrendador was new)
- WHEN user goes back to Step 1 and selects an existing arrendador from buscador
- THEN `loadPropiedadesPorArrendador(id)` is called and Step 3 state is reset
- AND the select or input is shown based on the API response

#### Scenario: User clears arrendador input

- GIVEN an existing arrendador was selected and Step 3 shows a populated select
- WHEN user clears the arrendador input field in Step 1
- THEN `hidden-arrendador-id` is cleared and Step 3 select options are reset to defaults
- AND when user proceeds to Step 3, the text input is shown (new arrendador path)

### Requirement: validateStep(3) compatibility with text input mode

The system MUST validate Step 3 correctly when the text input is shown instead of the select. The existing validation logic at `validateStep(3)` already handles this case by checking both `sel.value` and `inp.value.trim()`.

#### Scenario: Text input has content — validation passes

- GIVEN `#propiedadSelect` is hidden and `#nuevaPropiedadInput` has a non-empty value
- WHEN `validateStep(3)` is called
- THEN validation passes and the user proceeds to Step 4

#### Scenario: Text input is empty — validation fails

- GIVEN `#propiedadSelect` is hidden and `#nuevaPropiedadInput` is empty
- WHEN `validateStep(3)` is called
- THEN validation fails with error "La direccion de la propiedad es obligatoria."

### Requirement: Resumen displays typed address when text input is active

The `updateResumen()` function MUST correctly display the typed property address in the resumen panel when the text input is shown instead of the select.

#### Scenario: Resumen shows typed address

- GIVEN user typed "Av. Libertador 2500" in the text input
- WHEN `updateResumen()` is called
- THEN the "Propiedad" row in the resumen shows "Av. Libertador 2500"
