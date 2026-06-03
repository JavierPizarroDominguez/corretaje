# Delta for Buscador

## ADDED Requirements

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
