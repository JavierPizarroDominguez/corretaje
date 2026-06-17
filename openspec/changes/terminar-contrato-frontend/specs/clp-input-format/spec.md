# Delta for CLP Input Format

## ADDED Requirements

### Requirement: Termination preview uses CLP formatting

The system MUST use the global CLP formatting utilities for termination preview monetary inputs, row amounts, totals, original guarantee, total discounts/charges, and amount returned to tenant.

#### Scenario: Monetary values display as CLP

- GIVEN the termination preview modal opens with guarantee `500000`
- WHEN the monetary summary is rendered
- THEN the guarantee displays as `$500.000`
- AND totals use the same CLP format

#### Scenario: Editable row amounts parse consistently

- GIVEN a user enters `$80.000` in an adjustment row
- WHEN totals recalculate
- THEN the calculation uses integer value `80000`
- AND the field remains displayable in CLP format

#### Scenario: Refund rows are labeled clearly

- GIVEN a user adds a refund or negative adjustment row
- WHEN totals recalculate
- THEN the row is treated as increasing tenant return
- AND the displayed totals make that sign effect clear without relying on a raw negative-only label
