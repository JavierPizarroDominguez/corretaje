# Buscador Specification

## Purpose

The buscador is a JS search/lookup component embedded in CRUD create/edit forms. It searches a target table by user input, displays results, and captures the selected record's FK value into a hidden form field.

## Requirements

### Requirement: Scoped pivot relation MUST search target model

When the buscador is generated for a scoped hasOne-through-pivot relation, the search endpoint MUST receive the target model's table name, not the pivot table. The target model is the pivot's `belongsTo` that does not point to the parent.

#### Scenario: Cobro deudor searches Cliente

- GIVEN a Cobro create modal with a deudor buscador (`tipo: 'cliente'`)
- WHEN the user types in the deudor search input
- THEN the buscador queries the `cliente` table
- AND displays matching Cliente records (nombre, rut, etc.)

### Requirement: onSelect MUST emit target FK callback

The generated `onSelect` callback for scoped relations MUST set a hidden input with the selected target model's PK value. This value is submitted with the form and used by the controller to stitch the pivot record. This requirement extends to all buscador fields including direct FK relations (contrato, servicio, propiedad, unidad) in addition to scoped pivot relations (deudor/acreedor).

#### Scenario: Selecting cliente sets Cliente_id

- GIVEN the user selects a Cliente from deudor search results
- WHEN `onSelect` fires with the selected item
- THEN the display input (`input-create-deudor`) shows `item.texto`
- AND a hidden input (`input-create-deudor-id`) is set to `item.id`
- AND the hidden input is included in the form submission

#### Scenario: Selecting cliente sets Cliente_id for acreedor

- GIVEN the user selects a Cliente from acreedor search results
- WHEN `onSelect` fires with the selected item
- THEN the display input (`input-create-acreedor`) shows `item.texto`
- AND a hidden input (`input-create-acreedor-id`) is set to `item.id`
- AND the hidden input is included in the form submission

### Requirement: Input names MUST be unique per relation

Buscador display and hidden input names for scoped relations MUST use `relationName` as discriminator, not the pivot model name. This prevents collisions when two relations share the same pivot table.

#### Scenario: Deudor and acreedor on same form

- GIVEN a Cobro form with both deudor and acreedor buscadores
- WHEN the form renders
- THEN deudor inputs use `nombre-deudor` / `input-create-deudor-id`
- AND acreedor inputs use `nombre-acreedor` / `input-create-acreedor-id`
- AND no input name collision occurs

### Requirement: API response MUST include primary key `id` for all entity types

The BuscadorController API response MUST include an `id` field in every result item, regardless of entity type. This ensures `onSelect` callbacks can reliably set hidden form inputs to the selected record's primary key.

#### Scenario: Cliente search result includes id

- GIVEN a search query for `?cliente=1&q=juan`
- WHEN BuscadorController returns matching Cliente records
- THEN each item in `data` contains `id` (integer), `tipo` ("cliente"), `texto`, and `url`

#### Scenario: Unidad search result includes id

- GIVEN a search query for `?unidad=1&q=sala`
- WHEN BuscadorController returns matching Unidad records
- THEN each item in `data` contains `id` (integer), `tipo` ("unidad"), `texto`, and `url`

#### Scenario: Contrato search result includes id

- GIVEN a search query for `?contrato=1&q=5`
- WHEN BuscadorController returns matching Contrato records
- THEN each item in `data` contains `id` (integer), `tipo` ("contrato"), `texto`, and `url`

#### Scenario: Servicio search result includes id

- GIVEN a search query for `?servicio=1&q=comision`
- WHEN BuscadorController returns matching Servicio records
- THEN each item in `data` contains `id` (integer), `tipo` ("servicio"), `texto`, and `url`

#### Scenario: Propiedad search result includes id

- GIVEN a search query for `?propiedad=1&q=avenida`
- WHEN BuscadorController returns matching Propiedad records
- THEN each item in `data` contains `id` (integer), `tipo` ("propiedad"), `texto`, and `url`

### Requirement: Controller MUST handle contrato, servicio, and propiedad search types

BuscadorController MUST accept `contrato`, `servicio`, and `propiedad` query parameters and return search results for each entity type, consistent with existing `unidad` and `cliente` handlers.

#### Scenario: Search contrato by numeric id

- GIVEN a request with `?contrato=1&q=5`
- WHEN BuscadorController processes the request
- THEN it queries the `contrato` table where `id` LIKE `%5%`
- AND returns up to 10 matching records

#### Scenario: Search servicio by tipo field

- GIVEN a request with `?servicio=1&q=comision`
- WHEN BuscadorController processes the request
- THEN it queries the `servicio` table where `tipo` LIKE `%comision%`
- AND returns up to 10 matching records

#### Scenario: Search propiedad by direccion field

- GIVEN a request with `?propiedad=1&q=avenida`
- WHEN BuscadorController processes the request
- THEN it queries the `propiedad` table where `direccion` LIKE `%avenida%`
- AND returns up to 10 matching records

#### Scenario: Empty query returns empty results for all types

- GIVEN a request with `?contrato=1&q=`
- WHEN BuscadorController processes the request
- THEN it returns `{"data": []}` without executing any database query

### Requirement: onSelect callbacks MUST set hidden input for all buscador fields

Every buscador `onSelect` callback in cobro modal create views MUST set both the display input (`item.texto`) and the hidden ID input (`item.id`). This applies to contrato, servicio, propiedad, unidad, deudor, and acreedor fields.

#### Scenario: Selecting contrato sets hidden Contrato_id

- GIVEN the user selects a Contrato from the cobro modal buscador
- WHEN the `onSelect` callback fires
- THEN `input-create-contrato` displays `item.texto`
- AND `input-create-contrato-id` is set to `item.id`

#### Scenario: Selecting servicio sets hidden Servicio_id

- GIVEN the user selects a Servicio from the cobro modal buscador
- WHEN the `onSelect` callback fires
- THEN `input-create-servicio` displays `item.texto`
- AND `input-create-servicio-id` is set to `item.id`

#### Scenario: Selecting propiedad sets hidden Propiedad_id

- GIVEN the user selects a Propiedad from the cobro modal buscador
- WHEN the `onSelect` callback fires
- THEN `input-create-propiedad` displays `item.texto`
- AND `input-create-propiedad-id` is set to `item.id`

#### Scenario: Selecting unidad sets hidden Unidad_id

- GIVEN the user selects a Unidad from the cobro modal buscador
- WHEN the `onSelect` callback fires
- THEN `input-create-unidad` displays `item.texto`
- AND `input-create-unidad-id` is set to `item.id`

### Requirement: Hidden input MUST be cleared when visible input is cleared

When the user clears the visible buscador text input (by backspacing to empty or clicking the clear button), the associated hidden FK input MUST be set to an empty string. This ensures no stale FK ID is submitted when the user removes their selection.

#### Scenario: Backspacing to empty clears hidden input

- GIVEN the user has selected a record and the hidden input contains an integer ID
- WHEN the user backspaces until the visible input is empty
- THEN the hidden input value MUST be set to `""`

#### Scenario: Clear button clears hidden input

- GIVEN the user has selected a record
- WHEN the user clicks the buscador clear button (if present)
- THEN both the visible input and the hidden FK input MUST be cleared

### Requirement: Buscador stub MUST generate hidden FK input

The `create-field-fk-buscador.stub` MUST include a hidden `<input type="hidden">` element with `name="{{fk_column}}"` and `id="input-create-{{field_id}}-id"`. This hidden input MUST be placed within the same form group as the visible buscador input so it is submitted with the form.

#### Scenario: Hidden input generated for contrato field

- GIVEN a field `contrato_id` with `isBuscador = true`
- WHEN the buscador stub is rendered
- THEN the output MUST contain `<input type="hidden" name="contrato_id" id="input-create-contrato_id-id">`

#### Scenario: Hidden input generated for scoped deudor field

- GIVEN a scoped relation `deudor` with `fk_column = 'Cliente_id'`
- WHEN the buscador stub is rendered
- THEN the output MUST contain `<input type="hidden" name="deudor_Cliente_id" id="input-create-deudor-id">`

### Requirement: Validation MUST require FK ID when buscador text is submitted

When a buscador text input has a non-empty value in the submitted form data, the corresponding hidden FK input MUST contain a valid integer ID that exists in the target table. The validation rules MUST be: hidden FK input = `required_with:{buscador_input_name}|integer|exists:{table},id`; text input = `sometimes|nullable|string`.

#### Scenario: Text without FK ID fails validation

- GIVEN a form submission with `nombre-contrato = "Contrato 123"` and `contrato_id` empty or missing
- WHEN validation runs
- THEN the request MUST fail with a 422 error on `contrato_id`

#### Scenario: Text with valid FK ID passes validation

- GIVEN a form submission with `nombre-contrato = "Contrato 123"` and `contrato_id = 5`
- AND a record with `id = 5` exists in the `contrato` table
- WHEN validation runs
- THEN the request MUST pass validation for both fields

#### Scenario: Empty text with empty FK ID passes validation

- GIVEN a form submission with both `nombre-contrato` and `contrato_id` empty or absent
- WHEN validation runs
- THEN the request MUST pass validation (buscador field is optional)

#### Scenario: Tampered FK ID fails validation

- GIVEN a form submission with `nombre-contrato = "X"` and `contrato_id = 99999`
- AND no record with `id = 99999` exists in the `contrato` table
- WHEN validation runs
- THEN the request MUST fail with a 422 error on `contrato_id` (fails `exists` rule)

### Requirement: Dedicated Cliente search API endpoint

The system MUST provide a dedicated JSON endpoint `GET /api/clientes/search?q={query}` that returns matching Cliente records in `{data: [{id, texto, tipo}]}` format, independent of the existing BuscadorController. This endpoint supports the administracion wizard's autocomplete needs.

#### Scenario: ClienteSearchController returns matching clientes

- GIVEN clientes "Juan Perez" (id=10) and "Juan Gonzalez" (id=20) exist
- WHEN GET /api/clientes/search?q=juan
- THEN response is 200 with `{data: [{id: 10, texto: "Juan Perez", tipo: "cliente"}, {id: 20, texto: "Juan Gonzalez", tipo: "cliente"}]}`

#### Scenario: ClienteSearchController returns empty on no match

- WHEN GET /api/clientes/search?q=zzzzzz
- THEN response is 200 with `{data: []}`

#### Scenario: ClienteSearchController returns empty on empty query

- WHEN GET /api/clientes/search?q=
- THEN response is 200 with `{data: []}` and no database query is executed

### Requirement: Properties-by-owner API endpoint

The system MUST provide `GET /api/propiedades/por-arrendador/{id}` returning `{data: [{id, texto}]}` where texto is the propiedad direccion, filtered by the arrendador (propietario) FK.

#### Scenario: PropiedadPorArrendadorController returns owned properties

- GIVEN arrendador id=5 owns propiedades with id=100 (direccion="Av. Italia 100") and id=101 (direccion="Calle 18 555")
- WHEN GET /api/propiedades/por-arrendador/5
- THEN response is 200 with `{data: [{id: 100, texto: "Av. Italia 100"}, {id: 101, texto: "Calle 18 555"}]}`

#### Scenario: Non-existent arrendador returns empty

- WHEN GET /api/propiedades/por-arrendador/99999
- THEN response is 200 with `{data: []}`

---

## Delta: Loading Indicators (Archived 2026-05-26)

### Requirement: Autocomplete MUST show spinner during fetch

Buscador dropdown MUST display `spinner-border` while search `fetch()` is in flight — shown before fetch, hidden on completion.

#### Scenario: Spinner during search, hidden on results or error

- GIVEN user types in buscador input
- WHEN fetch begins, THEN dropdown shows spinner
- WHEN fetch succeeds, THEN spinner removed, results rendered
- WHEN fetch fails, THEN spinner removed, error shown
