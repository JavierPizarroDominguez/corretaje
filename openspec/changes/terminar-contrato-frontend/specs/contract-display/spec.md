# Contract Display Specification

## Purpose

Ensure ficha contract views and contract detail show current related participants, client, property, unit, dates, guarantee, and cobro information correctly.

## Requirements

### Requirement: Participant display uses current related clients

The system MUST display contract participants from their current related participant/client records, not from raw or mis-scoped relation objects.

#### Scenario: Property profile shows participant names

- GIVEN a property profile lists a contract with arrendador and arrendatario participants
- WHEN the contract card is rendered
- THEN each role shows the related client name and link

#### Scenario: Client profile keeps context participant correct

- GIVEN a client profile lists contracts for that client
- WHEN contract participants are rendered
- THEN the viewed client and other participants are shown from the contract participant/client relationships

#### Scenario: Missing participant relation

- GIVEN a contract participant relation is missing its client
- WHEN the view renders
- THEN the view shows a safe empty or unavailable state instead of breaking

### Requirement: Contract summary information is readable

The system MUST show readable contract start date, current end/termination date when present, guarantee, property, unit, and related cobro information in ficha lists and contract detail.

#### Scenario: Contract list summary

- GIVEN a ficha page lists an active contract
- WHEN the contract card is shown
- THEN it includes property or unit context, dates, guarantee, and participant information

#### Scenario: Contract detail summary

- GIVEN a user opens a contract detail page
- WHEN the detail renders
- THEN it shows participants, property/unit, dates, guarantee, and cobros in readable sections

#### Scenario: Generated-owned sections are not required for readability

- GIVEN the detail page includes generated scaffold sections
- WHEN custom readable contract information is added
- THEN the readable information remains outside generator-owned blocks

### Requirement: Pending cobro display is scoped

The system MUST identify pending contract cobros using pending, vencido, and incompleto states in supported casing; client-context views MUST only include cobros involving that client.

#### Scenario: Pending states are included

- GIVEN a contract has cobros with `pendiente`, `vencido`, `incompleto`, `Pendiente`, `Vencido`, or `Incompleto`
- WHEN pending cobros are displayed
- THEN those cobros are included

#### Scenario: Client context filters unrelated cobros

- GIVEN a client profile opens termination preview for a contract
- WHEN pending cobros are shown
- THEN only cobros where that client participates are included
