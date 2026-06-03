# Delta for CRUD Generator

## ADDED Requirements

### Requirement: ENUM values MUST preserve original database casing

The system MUST preserve the exact casing of MySQL ENUM values when generating `<option>` elements in create and show views. The `SchemaInspector::getColumnType()` method MUST NOT apply `strtolower()` to the raw `COLUMN_TYPE` string before parsing ENUM values.

#### Scenario: ENUM values retain original casing in create view

- GIVEN a MySQL table with an ENUM column defined as `ENUM('Ingreso', 'Renta', 'Arrendador')`
- WHEN the generator produces the create view for this table
- THEN the `<option>` elements MUST have `value="Ingreso"`, `value="Renta"`, `value="Arrendador"` (exact casing)
- AND the displayed text MUST match the value casing

#### Scenario: ENUM values retain original casing in show view

- GIVEN a MySQL table with an ENUM column defined as `ENUM('Activo', 'Inactivo', 'Pendiente')`
- WHEN the generator produces the show view for this table
- THEN any ENUM-based `<select>` or display elements MUST reflect the original casing

#### Scenario: Mixed-case ENUM values preserved

- GIVEN an ENUM column with mixed-case values like `ENUM('PDF', 'pdf', 'Pdf')`
- WHEN the generator processes this column
- THEN each value MUST be preserved exactly as defined: `'PDF'`, `'pdf'`, `'Pdf'`

### Requirement: Boolean TINYINT(1) detection MUST remain case-insensitive

The system MUST continue to detect `TINYINT(1)` columns as boolean fields regardless of the casing of the `COLUMN_TYPE` string returned by MySQL introspection. A targeted `strtolower()` MUST be applied only to the boolean detection comparison, not to the entire column type string.

#### Scenario: TINYINT(1) detected with uppercase type

- GIVEN a column with `COLUMN_TYPE` = `tinyint(1)`
- WHEN boolean detection runs
- THEN the column MUST be identified as a boolean field

#### Scenario: TINYINT(1) detected with mixed case type

- GIVEN a column with `COLUMN_TYPE` = `TINYINT(1)`
- WHEN boolean detection runs
- THEN the column MUST be identified as a boolean field

#### Scenario: Non-boolean TINYINT not misdetected

- GIVEN a column with `COLUMN_TYPE` = `tinyint(4)`
- WHEN boolean detection runs
- THEN the column MUST NOT be identified as a boolean field

## REMOVED Requirements

### Requirement: Global lowercase normalization of column types

(Reason: The `strtolower($row->COLUMN_TYPE)` on line 141 of `SchemaInspector.php` was destroying ENUM value casing. Replaced with targeted lowercase only for boolean detection.)

## Acceptance Criteria

| # | Criterion | Verification Method |
|---|-----------|-------------------|
| AC-1 | ENUM `<option>` values match database casing exactly | Inspect generated Blade view source |
| AC-2 | Boolean columns still render as checkboxes/toggles | Verify TINYINT(1) columns produce boolean UI |
| AC-3 | Non-ENUM selects (FK lookups) unaffected | Verify FK select output unchanged |
| AC-4 | All models with ENUM columns regenerate without errors | Run generator on each ENUM model |
| AC-5 | Laravel `in:` validation rules still pass | Submit form with ENUM values |

## Test Strategy

| Test Type | Scope | Method |
|-----------|-------|--------|
| Unit | `SchemaInspector::getColumnType()` | Assert ENUM values extracted with original casing |
| Unit | `SchemaInspector::getColumnType()` boolean path | Assert TINYINT(1) still detected as boolean |
| Integration | Generator end-to-end for ENUM model | Run generator, inspect output Blade files |
| Regression | Non-ENUM column types | Assert VARCHAR, INT, DATE columns produce identical output |
| Manual | Submit form with ENUM select | Verify validation passes with preserved casing |

## Out of Scope

- Non-ENUM column types (VARCHAR, INT, DATE, etc.) — no behavior change
- Select inputs driven by FK relations or buscador components — unaffected
- API resource responses — use Eloquent casts, not SchemaInspector
- Edit views — only create and show views contain the affected select generation
- Laravel validation rule casing — `in:` rule is case-insensitive by design
