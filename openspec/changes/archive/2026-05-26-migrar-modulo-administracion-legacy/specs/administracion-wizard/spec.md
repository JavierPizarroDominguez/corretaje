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
| `dia_pago` | `required_if:administracion,1\|nullable\|integer\|min:1\|max:31` | Required when administracion is active, 1-31 |
| `comision_inicial` | `nullable\|integer\|min:0` | Must be non-negative integer |
| `cobro_arrendador` | `nullable\|boolean` | Must be true/false |
| `cobro_arrendatario` | `nullable\|boolean` | Must be true/false |
| `garantia` | `nullable\|integer\|min:0` | Must be non-negative integer |
| `comision_mensual` | `nullable\|integer\|min:0` | Must be non-negative integer |
| `dia_luz` | `nullable\|integer\|min:1\|max:31` | 1-31 if provided |
| `monto_luz` | `nullable\|integer\|min:0` | Non-negative if provided |
| `dia_agua` | `nullable\|integer\|min:1\|max:31` | 1-31 if provided |
| `monto_agua` | `nullable\|integer\|min:0` | Non-negative if provided |
| `dia_gas` | `nullable\|integer\|min:1\|max:31` | 1-31 if provided |
| `monto_gas` | `nullable\|integer\|min:0` | Non-negative if provided |
| `dia_gastos` | `nullable\|integer\|min:1\|max:31` | 1-31 if provided |
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

### Requirement: Atomic entity creation

The system MUST create all entities within a single `DB::transaction()`. On any exception, ALL database changes MUST be rolled back — zero partial rows remain.

#### Scenario: Full creation succeeds

- GIVEN valid input with all fields populated, administracion=true, comision_inicial=100000, garantia=200000, corredor_es_arrendador=false
- WHEN POST /administracion succeeds
- THEN exactly 2 new Cliente rows (arrendador, arrendatario), 1 Propiedad, 1 Unidad, 1 Contrato, 3 ParticipanteContrato (Arrendador, Arrendatario, Corredor), 6 Cobro rows (Comision inicial arrendador x1, Comision inicial arrendatario x1, Ingreso Renta Arrendatario x1, Egreso Renta Arrendador x1, Ingreso Garantia Arrendatario x1, Egreso Garantia Arrendador x1), 12 ParticipanteCobro rows (2 per Cobro), and 0-4 Servicio rows (one per servicio with dia_pago set) are created
- AND HTTP 302 redirect to success page

#### Scenario: Transaction rollback on failure

- GIVEN valid input that triggers a database constraint violation mid-transaction (e.g., foreign key violation on ParticipanteContrato)
- WHEN POST /administracion is executed
- THEN the transaction rolls back
- AND no new rows exist in Cliente, Propiedad, Unidad, Contrato, ParticipanteContrato, Cobro, ParticipanteCobro, or Servicio tables that were not present before the request
- AND HTTP 500 is returned

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
