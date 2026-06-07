# Delta for Administracion Wizard

## MODIFIED Requirements

### Requirement: Wizard form validation

The system MUST validate all fields via `CrearAdministracionRequest` before entering the service layer. Validation failure returns HTTP 422 with field-specific error messages.

| Field | Rule | Error Message |
|-------|------|---------------|
| `dia_pago` | `nullable\|integer\|between:1,28` | Required when administracion is active, 1-28 |
| `servicios.*.dia` | `nullable\|integer\|between:1,28` | 1-28 if provided |

(Previously: `dia_pago` and `servicios.*.dia` had `max:31` / `between:1,31`)

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

### Requirement: Atomic entity creation

The system MUST create all entities within a single `DB::transaction()`. On any exception, ALL database changes MUST be rolled back — zero partial rows remain. Every Cobro row created in the transaction MUST have `Propiedad_id` and `Unidad_id` populated (not NULL).

#### Scenario: Full creation succeeds

- GIVEN valid input with all fields populated, administracion=true, comision_inicial=100000, garantia=200000, corredor_es_arrendador=false
- WHEN POST /administracion succeeds
- THEN exactly 2 new Cliente rows (arrendador, arrendatario), 1 Propiedad, 1 Unidad, 1 Contrato, 3 ParticipanteContrato (Arrendador, Arrendatario, Corredor), 6 Cobro rows, 12 ParticipanteCobro rows, and 0-4 Servicio rows are created
- AND every Cobro row has Propiedad_id and Unidad_id populated (not NULL)
- AND HTTP 302 redirect to `propiedad.ficha` for the correct property

#### Scenario: Transaction rollback on failure

- GIVEN valid input that triggers a database constraint violation mid-transaction
- WHEN POST /administracion is executed
- THEN the transaction rolls back
- AND no new rows exist in any affected table
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

(Previously: redirect used `$contrato->propiedad_id` which does not exist as a column on the Contrato table, causing a crash)

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

(Previously: `comision_mensual` was initialized to `0` on step 6 entry, and changing renta did not recalculate comision_mensual)

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

Every Cobro created MUST have exactly 2 associated ParticipanteCobro rows: one Deudor and one Acreedor.

#### Scenario: Commission cobro has correct Deudor/Acreedor

- GIVEN `comision_inicial = 100000`, `cobro_arrendador = true`, corredor_es_arrendador=false
- WHEN POST /administracion succeeds
- THEN a Cobro with tipo "Comision inicial arrendador" exists
- AND it has exactly 2 ParticipanteCobro: Deudor=arrendador Cliente_id, Acreedor=Cliente id=1

### Requirement: Servicio creation is conditional on dia_pago

For each servicio type (luz, agua, gas, gastos), a Servicio row is created ONLY if the corresponding `dia_{tipo}` field is present and non-null.

#### Scenario: Only luz and gas have dia_pago — only 2 Servicios created

- GIVEN `dia_luz = 15`, `dia_gas = 20`, `dia_agua` is null, `dia_gastos` is null
- WHEN POST /administracion with administracion=true
- THEN exactly 2 Servicio rows are created: "Luz" (dia_pago=15) and "Gas" (dia_pago=20)
- AND no "Agua" or "Gastos Comunes" Servicio rows exist

### Requirement: Corredor as arrendador edge case

When `corredor_es_arrendador = true`, the system MUST set arrendador_id to Cliente id=1 (the corredor), skip creating a new Cliente for arrendador, and skip all Egreso cobros.

#### Scenario: Corredor is arrendador — no new Cliente, no Egreso cobros

- GIVEN `corredor_es_arrendador = true`, `arrendador` is empty, administracion=true, renta=500000, garantia=1000000
- WHEN POST /administracion succeeds
- THEN exactly 1 new Cliente (arrendatario only), 1 Propiedad, 1 Unidad, 1 Contrato, 3 ParticipanteContrato
- AND 4 Cobro rows: NO Egreso Renta Arrendador, NO Egreso Garantía Arrendador
- AND ParticipanteContrato for Arrendador references Cliente id=1

### Requirement: Administracion=false skips cobros and servicios

When `administracion = false`, the system MUST create only Cliente x2, Propiedad, Unidad, Contrato, and ParticipanteContrato x3. No Cobro, ParticipanteCobro, or Servicio rows are created.

#### Scenario: No administracion — Contrato only, no cobros

- GIVEN `administracion = false`, all other fields valid
- WHEN POST /administracion succeeds
- THEN 2 Cliente, 1 Propiedad, 1 Unidad, 1 Contrato, 3 ParticipanteContrato are created
- AND 0 Cobro rows, 0 ParticipanteCobro rows, 0 Servicio rows are created

### Requirement: Null comision_inicial skips commission cobros

When `comision_inicial` is null or absent, the system MUST NOT create any Cobro with tipo "Comision inicial arrendador" or "Comision inicial arrendatario".

#### Scenario: Null comision — no commission cobros

- GIVEN `comision_inicial` is null, administracion=true, renta=500000
- WHEN POST /administracion succeeds
- THEN no Cobro rows with tipo containing "Comision inicial" exist

### Requirement: Null garantia skips garantia cobros

When `garantia` is null or absent, the system MUST NOT create "Ingreso Garantía Arrendatario" or "Egreso Garantía Arrendador" cobros.

#### Scenario: Null garantia — no garantia cobros

- GIVEN `garantia` is null, administracion=true, renta=500000, corredor_es_arrendador=false
- WHEN POST /administracion succeeds
- THEN no Cobro rows with tipo containing "Garantía" exist

### Requirement: Existing entities are reused, not duplicated

The system MUST use `firstOrCreate` for Cliente (by nombre) and Propiedad (by direccion).

#### Scenario: Existing Cliente reused

- GIVEN a Cliente with nombre="Juan Perez" already exists with id=42
- WHEN POST /administracion with `arrendador = "Juan Perez"`
- THEN no new Cliente row is created for arrendador

#### Scenario: Existing Propiedad reused

- GIVEN a Propiedad with direccion="Av. Libertador 1234" already exists with id=10
- WHEN POST /administracion with `direccion = "Av. Libertador 1234"`
- THEN no new Propiedad row is created

### Requirement: Frontend dia_pago validation range

The wizard frontend MUST reject dia_pago values outside 1-28 in step 4 and step 8 service day inputs. The `dayOutOfRange` flag MUST be set when `dia < 1 || dia > 28`.

(Previously: `dayOutOfRange` was set when `dia < 1 || dia > 31`)

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

(Previously: no explicit range constraint on `dia_pago` beyond nullable integer)

#### Scenario: CRUD create rejects dia_pago=30

- WHEN POST /contrato with `dia_pago = 30`
- THEN validation fails

#### Scenario: CRUD create accepts dia_pago=15

- WHEN POST /contrato with `dia_pago = 15`
- THEN validation passes
