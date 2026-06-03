# Delta for Buscador

## ADDED Requirements

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

## MODIFIED Requirements

### Requirement: onSelect MUST emit target FK callback

The generated `onSelect` callback for scoped relations MUST set a hidden input with the selected target model's PK value. This value is submitted with the form and used by the controller to stitch the pivot record.

(Previously: Only covered scoped pivot relations (deudor/acreedor); now extends to all buscador fields including direct FK relations like contrato, servicio, propiedad, unidad.)

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
