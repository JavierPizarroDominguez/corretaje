# Delta for Ficha Pendientes Mobile

## ADDED Requirements

### Requirement: Termination Modal Pending Table Parity

The system MUST render `Terminar Contrato` pending cobros using the ficha/index pending-payment table contract for desktop and mobile. The table SHALL use the same responsive card behavior and role-button presentation as ficha/index pending cobros.

#### Scenario: Desktop termination modal uses ficha/index format

- GIVEN a contract has pending cobros
- WHEN `Terminar Contrato` opens on a desktop viewport
- THEN pending cobros appear in the ficha/index-style pending table
- AND role cells show centered cobro buttons.

#### Scenario: Mobile termination modal uses ficha/index cards

- GIVEN a contract has pending cobros
- WHEN `Terminar Contrato` opens on a mobile viewport
- THEN pending rows appear as mobile cards
- AND cobro buttons remain full-width, centered, and readable.

#### Scenario: Empty pending state remains clear

- GIVEN a contract has no pending cobros
- WHEN `Terminar Contrato` opens
- THEN the pending-cobros section clearly communicates that no pending cobros exist.
