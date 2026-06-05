# Delta for Administracion Wizard

## ADDED Requirements

### Requirement: Searcher dropdown mobile overflow handling

The system MUST constrain searcher autocomplete dropdowns (arrendador, arrendatario) on mobile (≤991.98px) with max-height and overflow-y: scroll, and ensure z-index keeps dropdowns above action buttons without overlap.

#### Scenario: Searcher dropdown scrolls on mobile

- GIVEN viewport is 375px and user types in arrendador searcher
- WHEN dropdown shows 10+ results
- THEN dropdown has max-height and vertical scroll
- AND dropdown does not overlap action buttons below

#### Scenario: Searcher dropdown z-index correct on mobile

- GIVEN viewport is 375px
- WHEN searcher dropdown is open
- THEN dropdown renders above all action buttons (z-index > buttons)

### Requirement: No-properties text input fallback

When the selected arrendador has zero properties, the system MUST show a text input for "direccion" directly in step 3, bypassing the property select dropdown.

#### Scenario: Arrendador with no properties shows text input

- GIVEN user selects an arrendador who owns 0 properties
- WHEN step 3 renders
- THEN a text input for property address is shown (not a select dropdown)

#### Scenario: Arrendador with properties shows select dropdown

- GIVEN user selects an arrendador who owns 1+ properties
- WHEN step 3 renders
- THEN the property select dropdown is shown (not a text input)

### Requirement: Commission auto-initialization

When step 5 (Comision Inicial) becomes visible (administracion=true), the system MUST auto-set `comision_inicial` to `renta / 2` (integer, rounded down).

#### Scenario: Commission auto-filled from renta

- GIVEN user sets renta = 500000 and administracion = true
- WHEN step 5 becomes visible
- THEN comision_inicial field shows 250000

#### Scenario: Commission not set when administracion is false

- GIVEN administracion = false
- WHEN step 5 is hidden
- THEN comision_inicial is NOT auto-set

### Requirement: Guarantee auto-initialization

When step 7 (Garantia) becomes visible (administracion=true), the system MUST auto-set `garantia` to the `renta` value.

#### Scenario: Guarantee auto-filled from renta

- GIVEN user sets renta = 500000 and administracion = true
- WHEN step 7 becomes visible
- THEN garantia field shows 500000

#### Scenario: Guarantee not set when administracion is false

- GIVEN administracion = false
- WHEN step 7 is hidden
- THEN garantia is NOT auto-set

### Requirement: Summary panel below form steps

The system MUST render `#resumen-wrapper` below all form steps (after step 9), not inline with the wizard navigation.

#### Scenario: Summary appears after all steps

- GIVEN user is on any wizard step
- WHEN page renders
- THEN `#resumen-wrapper` is positioned below step 9 content

## MODIFIED Requirements

### Requirement: Wizard success redirect

The system MUST show a `flashModal` success message after wizard completion, then redirect to `/propiedad/ficha/{propiedad_id}` after a 3-second delay.
(Previously: Redirected to a generic success page without flashModal)

#### Scenario: FlashModal then redirect to property ficha

- GIVEN user successfully submits the wizard
- WHEN response is received
- THEN flashModal shows success message
- AND after 3 seconds, browser redirects to /propiedad/ficha/{propiedad_id}

#### Scenario: Redirect uses created propiedad ID

- GIVEN wizard created a new Propiedad with id=42
- WHEN success redirect occurs
- THEN redirect URL is /propiedad/ficha/42
