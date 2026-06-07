# CLP Input Format Specification

## Purpose

Reusable Chilean Peso (CLP) monetary formatting utility for input fields across the administracion wizard. Provides consistent `$xxx.xxx` display format (thousands separator, no decimals) with format-on-blur / strip-on-focus behavior.

## Requirements

### Requirement: CLP formatting utilities

The system MUST provide two reusable JavaScript functions accessible on `window`:

- `window.formatCLP(value)` — takes a numeric string or number, returns a CLP-formatted string (`$xxx.xxx`). Strips all non-digit characters, then inserts `.` as thousands separator from right to left. No decimal places.
- `window.stripCLP(formatted)` — takes a CLP-formatted string (e.g., `$500.000`), returns a plain digit string (`500000`). Removes all characters except digits.

#### Scenario: formatCLP formats a plain number

- GIVEN `value = 500000`
- WHEN `formatCLP(value)` is called
- THEN result is `"$500.000"`

#### Scenario: formatCLP formats a small number

- GIVEN `value = 1000`
- WHEN `formatCLP(value)` is called
- THEN result is `"$1.000"`

#### Scenario: formatCLP formats a single-digit number

- GIVEN `value = 5`
- WHEN `formatCLP(value)` is called
- THEN result is `"$5"`

#### Scenario: formatCLP handles zero

- GIVEN `value = 0`
- WHEN `formatCLP(value)` is called
- THEN result is `"$0"`

#### Scenario: formatCLP strips non-digits before formatting

- GIVEN `value = "$500.000"` (already formatted)
- WHEN `formatCLP(value)` is called
- THEN result is `"$500.000"` (idempotent)

#### Scenario: stripCLP removes formatting

- GIVEN `formatted = "$500.000"`
- WHEN `stripCLP(formatted)` is called
- THEN result is `"500000"`

#### Scenario: stripCLP handles plain digits

- GIVEN `formatted = "500000"`
- WHEN `stripCLP(formatted)` is called
- THEN result is `"500000"`

#### Scenario: stripCLP removes all non-digit characters

- GIVEN `formatted = "$1.234.567abc"`
- WHEN `stripCLP(formatted)` is called
- THEN result is `"1234567"`

### Requirement: Monetary inputs use CLP format

All monetary input fields in the administracion wizard MUST display values in CLP format (`$xxx.xxx`) on blur and accept raw input on focus. Affected inputs:

| Step | Input ID | Field Name |
|------|----------|------------|
| 4 | `rentaInput` | `renta` |
| 5 | `comisionMontoInput` | `comision_inicial` |
| 6 | `egresoRentaInput` | `egreso_renta` |
| 6 | `comisionMensualInput` | `comision_mensual` |
| 7 | `garantiaInput` | `garantia` |
| 8 | `servicioMontoInput` | `servicios.*.monto` |

(Previously: inputs were `type="number"` showing raw integers)

#### Scenario: Renta input formats on blur

- GIVEN user types `500000` in the renta input
- WHEN the input loses focus (blur)
- THEN the input displays `$500.000`

#### Scenario: Renta input strips on focus

- GIVEN renta input displays `$500.000`
- WHEN user clicks/focuses the input
- THEN the input displays `500000` (raw digits)

#### Scenario: Monetary inputs are text type with numeric inputmode

- GIVEN the administracion wizard page loads
- THEN all monetary inputs have `type="text"` and `inputmode="numeric"`
- AND they do NOT have `type="number"`

#### Scenario: Backend receives stripped values

- GIVEN user enters `$500.000` in renta and `$50.000` in comision_inicial
- WHEN form is submitted
- THEN the POST payload contains `renta=500000` and `comision_inicial=50000` (stripped digits)

### Requirement: Form submission strips all CLP-formatted fields

Before the administracion wizard form is submitted, the system MUST strip CLP formatting from all monetary input fields so the server receives plain integer values.

#### Scenario: Submit strips all formatted fields

- GIVEN user has filled multiple steps with CLP-formatted values: renta=`$500.000`, comision_inicial=`$100.000`, garantia=`$500.000`
- WHEN form submission event fires
- THEN all monetary input values are replaced with their stripped digit equivalents before the form POST

#### Scenario: Existing sanitizeNumericInput is replaced by CLP strip

- GIVEN the form has CLP-formatted inputs
- WHEN the form submit handler runs
- THEN `stripCLP()` is called on each monetary input (replacing the previous `sanitizeNumericInput` logic for these fields)

### Requirement: CLP format integrates with existing calculations

All existing JavaScript calculations that read monetary input values MUST use `stripCLP()` before parsing with `parseInt()`. This includes:

- `getRentaNumero()` — must strip CLP format before parsing
- Egreso/comision recalculation on input change
- Resumen panel display values

#### Scenario: getRentaNumero works with formatted value

- GIVEN renta input displays `$500.000`
- WHEN `getRentaNumero()` is called
- THEN result is `500000` (integer)

#### Scenario: Egreso recalculation works with formatted renta

- GIVEN renta displays `$600.000`, user is on step 6
- WHEN egreso input changes
- THEN comision_mensual is calculated from stripped renta value (600000)
- AND egreso = renta - comision_mensual

#### Scenario: Resumen displays CLP-formatted values

- GIVEN renta=`$500.000`, comision_inicial=`$100.000`
- WHEN `updateResumen()` is called
- THEN resumen shows `$500.000` for renta and `$100.000` for comision inicial
- AND formatting is consistent (no double-formatting like `$$500.000`)
