# Proposal: ENUM Case Preservation

## Intent

The generator destroys original ENUM casing from MySQL when rendering `<option>` values. Database values like `"Ingreso"`, `"Renta"`, `"Arrendador"` become `<option value="ingreso">` — breaking UX consistency and any case-sensitive downstream logic.

## Scope

### In Scope
- Remove `strtolower` from `SchemaInspector.php` line 141
- Add targeted `strtolower` for boolean detection on line 148
- Regenerate all `create` and `show` views for models with ENUM columns

### Out of Scope
- Non-ENUM column types (VARCHAR, INT, etc.)
- Select inputs not driven by ENUM introspection
- API resource responses (already use Eloquent casts, not affected)
- Validation rule casing (Laravel `in:` rule is case-insensitive)

## Capabilities

### New Capabilities
- `enum-case-preservation`: ENUM values in generated views preserve their original database casing

### Modified Capabilities
- None

## Approach

One-line code fix + targeted guard + regeneration:

1. **Line 141**: Change `$colType = strtolower($row->COLUMN_TYPE)` → `$colType = $row->COLUMN_TYPE`
2. **Line 148**: Change `str_contains($colType, '(1)')` → `str_contains(strtolower($colType), '(1)')` to keep boolean detection working
3. **Regenerate**: Re-run the generator for all models with ENUM columns to update `<option>` values and Blade comparison expressions

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Generator/Introspection/SchemaInspector.php` | Modified | Remove strtolower from COLUMN_TYPE, add it locally for boolean check |
| Generated Blade views (create/show) | Modified | Option values and Blade comparisons will reflect correct casing |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Boolean detection breaks for TINYINT(1) | Low | Targeted strtolower added on line 148 |
| Blade comparisons use wrong case | Low | Full regeneration fixes all template output |
| Validation `in:` rules break | None | Laravel `in:` rule is case-insensitive |

## Rollback Plan

Revert line 141 to `strtolower($row->COLUMN_TYPE)`, remove the added `strtolower` on line 148, and regenerate views. Single commit revert.

## Dependencies

- None

## Success Criteria

- [ ] ENUM `<option>` values match original database casing (e.g., `Ingreso` not `ingreso`)
- [ ] Boolean TINYINT(1) detection still works correctly
- [ ] All ENUM models' create/show views regenerated without errors
