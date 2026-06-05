# Delta for Buscador

## MODIFIED Requirements

### Requirement: Buscador cliente result URLs point to ficha

The BuscadorController MUST return `url` field for cliente results as `/cliente/ficha/{id}` instead of `/cliente/{id}`. This ensures buscador links navigate to the client's ficha detail view.
(Previously: URL was `/cliente/{id}`)

#### Scenario: Cliente search result links to ficha

- GIVEN user searches for "Juan" in any buscador with tipo=cliente
- WHEN BuscadorController returns results
- THEN each cliente item has `url = "/cliente/ficha/{id}"`

#### Scenario: Clicking buscador cliente result navigates to ficha

- GIVEN buscador shows cliente "Juan Perez" with id=10
- WHEN user clicks the result
- THEN browser navigates to /cliente/ficha/10

#### Scenario: Non-cliente result URLs unchanged

- GIVEN user searches for a propiedad
- WHEN BuscadorController returns results
- THEN propiedad items have their original URL format (not affected)
