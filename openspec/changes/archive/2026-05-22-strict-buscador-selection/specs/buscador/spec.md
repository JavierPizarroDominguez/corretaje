# Delta for Buscador

## MODIFIED Requirements

### Requirement: API response MUST include primary key `id` for all entity types

The BuscadorController API response MUST include an `id` field in every result item, regardless of entity type. Each result item MUST contain at minimum: `id` (integer), `tipo` (string), `texto` (string), and `url` (string). This ensures `onSelect` callbacks can reliably set hidden form inputs to the selected record's primary key.

(Previously: `id` was omitted from result arrays, causing `onSelect` to receive `undefined`)

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

### Requirement: onSelect callbacks MUST set hidden input for all buscador fields

Every buscador `onSelect` callback in cobro modal create views MUST set both the display input (`item.texto`) and the hidden ID input (`item.id`). This applies to contrato, servicio, propiedad, unidad, deudor, and acreedor fields. The hidden input MUST have `id="input-create-{field}-id"` and MUST be set via `document.getElementById('input-create-{field}-id').value = item.id`.

(Previously: hidden input assignment was only emitted for scoped relations; direct FK fields like contrato, servicio, propiedad, unidad had no hidden-input assignment in their `onSelect`)

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

#### Scenario: Selecting deudor sets hidden Cliente_id

- GIVEN the user selects a Cliente from deudor search results
- WHEN `onSelect` fires with the selected item
- THEN the display input (`input-create-deudor`) shows `item.texto`
- AND a hidden input (`input-create-deudor-id`) is set to `item.id`
- AND the hidden input is included in the form submission

#### Scenario: Selecting acreedor sets hidden Cliente_id

- GIVEN the user selects a Cliente from acreedor search results
- WHEN `onSelect` fires with the selected item
- THEN the display input (`input-create-acreedor`) shows `item.texto`
- AND a hidden input (`input-create-acreedor-id`) is set to `item.id`
- AND the hidden input is included in the form submission

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

## ADDED Requirements

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
