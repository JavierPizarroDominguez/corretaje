# Cobro Payment Specification

## Purpose

Register a payment against a cobro, transitioning it to `Pagado` estado. Creates a Transaccion and TransaccionCobro pivot. Reusable from dashboard pendientes and client detail view.

## Requirements

### Requirement: Payment endpoint contract

The system MUST provide `POST /api/cobro/pagar` accepting `{cobro_id: int, monto: int}`. On success, returns HTTP 200 with created Transaccion id and updated cobro estado.

#### Scenario: Successful payment of Pendiente cobro

- GIVEN a cobro with estado="Pendiente", monto=500000
- WHEN POST /api/cobro/pagar with `{cobro_id: <id>, monto: 500000}`
- THEN response is 200 with `{transaccion_id: N, cobro_estado: "Pagado"}`
- AND a Transaccion row is created with correct Origen/Destino
- AND a TransaccionCobro pivot links Transaccion to cobro with monto_pagado=500000
- AND cobro.estado is updated to "Pagado"

#### Scenario: Successful payment of Vencido cobro

- GIVEN a cobro with estado="Vencido", monto=300000
- WHEN POST /api/cobro/pagar with `{cobro_id: <id>, monto: 300000}`
- THEN response is 200, cobro.estado="Pagado", Transaccion + TransaccionCobro created

### Requirement: Payable estado validation

The system MUST reject payments for cobros not in `Pendiente` or `Vencido` estado. Returns HTTP 422 with error on `cobro_id`.

#### Scenario: Already-paid cobro rejected

- GIVEN a cobro with estado="Pagado"
- WHEN POST /api/cobro/pagar with that cobro_id
- THEN response is 422

#### Scenario: Anulado cobro rejected

- GIVEN a cobro with estado="Anulado"
- WHEN POST /api/cobro/pagar with that cobro_id
- THEN response is 422

### Requirement: Cobro existence validation

The system MUST return HTTP 404 if cobro_id does not exist.

#### Scenario: Non-existent cobro

- WHEN POST /api/cobro/pagar with `{cobro_id: 99999, monto: 100000}`
- THEN response is 404

### Requirement: Input validation

The system MUST validate `cobro_id` and `monto` as positive integers. Missing or invalid fields return HTTP 422.

#### Scenario: Missing or invalid monto

- WHEN POST /api/cobro/pagar with `{cobro_id: 5}` or `{cobro_id: 5, monto: 0}`
- THEN response is 422 with error on `monto`

#### Scenario: Non-integer values

- WHEN POST /api/cobro/pagar with `{cobro_id: "abc", monto: "xyz"}`
- THEN response is 422 with errors on both fields
