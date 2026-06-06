# Delta for Administracion Wizard

## ADDED Requirements

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
