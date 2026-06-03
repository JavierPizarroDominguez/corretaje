## Exploration: ENUM Case Preservation in Generated Views

### Current State

The code generator produces `<select>` inputs for ENUM database columns with option values in **lowercase**, even when the database ENUM definition uses mixed case (e.g., `"Ingreso"`, `"Renta"`, `"Arrendador"`). This breaks creation and modification workflows because MySQL ENUM comparisons are case-sensitive by default (depending on collation), and the application logic may depend on the exact case.

**Root Cause** — a single line in `SchemaInspector.php`:

```php
// Line 141 — SchemaInspector::buildColumnMetadata()
$colType = strtolower($row->COLUMN_TYPE); // 'tinyint(1)', 'enum(...)'
```

This line lowercases the entire `COLUMN_TYPE` string (e.g., `enum('Ingreso','Renta','Arrendador')` → `enum('ingreso','renta','arrendador')`). Then on line 149:

```php
$enumValues = $this->parseEnumValues($colType);
```

The `parseEnumValues()` method extracts quoted values from the already-lowered string, producing `['ingreso', 'renta', 'arrendador']` instead of `['Ingreso', 'Renta', 'Arrendador']`.

**Why `strtolower` is there**: The `COLUMN_TYPE` value is used for two purposes:
1. **Type detection** — checking `str_contains($colType, '(1)')` for boolean detection (line 148). This only needs `DATA_TYPE` to be lowercase, not the full `COLUMN_TYPE`.
2. **ENUM parsing** — extracting enum values, which MUST preserve original case.

The `DATA_TYPE` (e.g., `enum`, `tinyint`, `varchar`) is already normalized to lowercase by MySQL's `information_schema`, so `strtolower` on line 140 (`$sqlType`) is correct. But `strtolower` on `COLUMN_TYPE` (line 141) destroys the case of ENUM values.

### Affected Areas

- **`app/Generator/Introspection/SchemaInspector.php` line 141** — The bug origin: `strtolower($row->COLUMN_TYPE)` destroys ENUM value casing.
- **`app/Generator/Rendering/StubRenderer.php`** — Multiple methods consume `$col->enumValues` and render it into HTML/PHP:
  - `buildCreateFormFields()` (line ~483–496) — create view enum options
  - `buildEditFormFields()` (line ~565–579) — edit view enum options
  - `renderEnumRow()` (line ~736–751) — component inline enum options
  - `buildValidationRules()` (line ~988) — generates `in:ingreso,renta` instead of `in:Ingreso,Renta`
  - `renderFilterEnumField()` (line ~1939–1960) — filter checkbox values
- **`stubs/component-inline-enum-option.stub`** — Uses `{{enum_value}}` and `{{enum_label}}` (no hardcoded lowercase, correct)
- **`stubs/fragments/create-field-enum-option.stub`** — Uses `{{enum_value}}` and `{{enum_label}}` (no hardcoded lowercase, correct)
- **`stubs/fragments/edit-field-enum-option.stub`** — Uses `{{enum_value}}` and `{{enum_label}}` (no hardcoded lowercase, correct)
- **`stubs/fragments/filter-enum-checkbox.stub`** — Uses `{{enum_value}}` and `{{enum_label}}` (no hardcoded lowercase, correct)
- **All generated Blade views** that contain `<option>` or `<input>` elements with ENUM values — these are the OUTPUT that's broken and will need regeneration after the fix.

### Knock-on Effects

The bug propagates to **three** distinct areas in generated code:

1. **HTML `<option value="...">` and `<input value="...">`** — The `value` attribute becomes lowercase, so form submissions send lowercase values.
2. **Blade comparison expressions** — `{{ $model->field === 'ingreso' ? 'selected' : '' }}` will never match if the database holds `'Ingreso'`.
3. **Laravel validation rules** — `in:ingreso,renta` will reject `'Ingreso'` (case-sensitive validation), causing 422 errors on form submissions even if the frontend somehow sent the correct case.

### Approaches

1. **Remove `strtolower` from `COLUMN_TYPE`, keep it only for `DATA_TYPE`** — Fix the root cause
   - Pros: Single-line fix; preserves all original casing; no stub changes needed; minimal risk
   - Cons: None significant. The `str_contains($colType, '(1)')` check for booleans still works because `strtolower('tinyint(1)')` is redundant — MySQL already returns `tinyint(1)` in lowercase.
   - Effort: **Low**

2. **Parse ENUM values before lowercasing `COLUMN_TYPE`** — Keep `strtolower` for type detection but parse enum values from raw `COLUMN_TYPE`
   - Pros: Most targeted fix; no changes to `strtolower` behavior for other uses
   - Cons: Slightly more code change; need a new variable for the raw column type
   - Effort: **Low**

3. **Add a case-preserving parse step and normalize only for comparison** — Parse from raw, store original case, optionally add a `enumValuesLower` property
   - Pros: Gives flexibility for both display and comparison use cases
   - Cons: Over-engineered for the current need; adds complexity to ColumnMetadata
   - Effort: **Medium**

### Recommendation

**Approach 1** — Replace line 141 with a targeted solution:

```php
// Before (bug):
$colType = strtolower($row->COLUMN_TYPE);

// After (fix):
$colType = $row->COLUMN_TYPE;
```

Then update line 148 to ensure the boolean check still works:
```php
// Before:
$isBoolean = ($sqlType === 'tinyint' && str_contains($colType, '(1)'));

// After (explicit lowercase just for the check):
$isBoolean = ($sqlType === 'tinyint' && str_contains(strtolower($colType), '(1)'));
```

The `parseEnumValues()` method already uses `str_starts_with` which is case-insensitive for our needs (MySQL always returns `enum(` in lowercase), so no change needed there. The regex `/'([^']+)'/ ` will now capture the original-cased values.

After the code fix, all views will need to be **regenerated** (re-run the generator) to produce correct output. The fix itself is safe — no runtime behavior changes for existing generated views until they are regenerated.

### Risks

- **Regeneration required**: Existing generated views won't automatically fix themselves. The code fix only affects future generation. Already-rendered views must be regenerated. This is a minor operational concern, not a code risk.
- **`str_contains` on `$colType`**: There's one `str_contains($colType, '(1)')` call on line 148 for boolean detection. If `COLUMN_TYPE` is no longer lowercased, and MySQL ever returned `TinyInt(1)` (it doesn't, but hypothetically), this check could fail. The fix adds `strtolower()` only for that specific check, making it defensive.
- **Validation rules**: The `in:ingreso,renta` rule in generated controllers will also be wrong. After the fix, regeneration produces `in:Ingreso,Renta` which correctly matches the database values. No structural change needed — same root cause, same fix.

### Ready for Proposal

**Yes** — The analysis is complete. The root cause is a single `strtolower()` call that should be removed from `COLUMN_TYPE` processing (keeping it only for `DATA_TYPE`). The orchestrator should proceed to `sdd-propose` with a change to fix `SchemaInspector.php` and regenerate affected views.