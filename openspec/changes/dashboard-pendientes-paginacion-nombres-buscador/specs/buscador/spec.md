# Delta for Buscador

## MODIFIED Requirements

### Requirement: API response URL format for property and client search results

BuscadorController API response items for `propiedad` and `cliente` entity types SHALL use the `/ficha/` URL pattern: `/propiedad/ficha/{id}` and `/cliente/ficha/{id}` respectively. Other entity types (`contrato`, `servicio`, `unidad`) retain their existing URL format.

(Previously: URLs used `/{entity}/{id}` pattern for all entity types)

#### Scenario: Propiedad search result URL uses ficha pattern

- GIVEN a search query for `?propiedad=1&q=avenida`
- WHEN BuscadorController returns matching Propiedad records
- THEN each item's `url` field is `/propiedad/ficha/{id}`

#### Scenario: Cliente search result URL uses ficha pattern

- GIVEN a search query for `?cliente=1&q=juan`
- WHEN BuscadorController returns matching Cliente records
- THEN each item's `url` field is `/cliente/ficha/{id}`

#### Scenario: Other entity types retain existing URL format

- GIVEN a search query for `?contrato=1&q=5`
- WHEN BuscadorController returns matching Contrato records
- THEN each item's `url` field remains `/contrato/{id}` (unchanged)

#### Scenario: Buscador.js uses backend-provided URL unchanged

- GIVEN the buscador component receives search results with `item.url`
- WHEN the user selects a result
- THEN the navigation uses `item.url` as-is (no client-side URL transformation)
