# Delta for Administracion Wizard

## ADDED Requirements

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

## MODIFIED Requirements

### Requirement: Atomic entity creation

The system MUST create all entities within a single `DB::transaction()`. On any exception, ALL database changes MUST be rolled back.

(Previously: Described atomic creation without specifying FK population on Cobro rows)

#### Scenario: Full creation succeeds

- GIVEN valid input, administracion=true, comision_inicial=100000, garantia=200000, corredor_es_arrendador=false
- WHEN POST /administracion succeeds
- THEN 2 Cliente, 1 Propiedad, 1 Unidad, 1 Contrato, 3 ParticipanteContrato, 6 Cobro, 12 ParticipanteCobro, 0-4 Servicio rows created
- AND every Cobro row has Propiedad_id and Unidad_id populated (not NULL)
- AND HTTP 302 redirect to success page

#### Scenario: Transaction rollback on failure

- GIVEN valid input that triggers a DB constraint violation mid-transaction
- WHEN POST /administracion is executed
- THEN transaction rolls back, no new rows in any table
- AND HTTP 500 is returned
