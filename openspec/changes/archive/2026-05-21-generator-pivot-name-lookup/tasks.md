# Tasks: generator-pivot-name-lookup

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 60–90 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | All 5 fixes in StubRenderer + 2 stubs | PR 1 | Self-contained; tests included |

## Phase 1: Foundation — Namespace Fix (Bug #4)

- [x] 1.1 In `StubRenderer.php`, method `buildPivotStoreFields` (~line 1069): change `new {$pivotModel}()` to `new \\{$ns}{$pivotModelShort}()` (leading backslash for absolute namespace)
- [x] 1.2 In `StubRenderer.php`, method `buildPivotUpdateFields` (~line 1141): change `{$pivotModel}::firstOrNew` to `\\{$ns}{$pivotModelShort}::firstOrNew`

**Verification**: Regenerate CobroController, confirm no `ClassNotFoundError` for pivot model.

## Phase 2: Core Implementation — Validation + Name Resolution (Bugs #2, #5)

- [x] 2.1 In `StubRenderer.php`, method `buildValidationRules` (~line 897): change scoped hidden FK validation from `required` to `sometimes|nullable`; add `sometimes|nullable|string` rule for buscador text input
- [x] 2.2 In `StubRenderer.php`, method `buildPivotStoreFields`: add name resolution branch — if buscador text present but no hidden FK ID, call `firstOrCreate` on target model by `relationDisplayField`
- [x] 2.3 In `StubRenderer.php`, method `buildPivotUpdateFields`: mirror the name resolution branch from store fields

**Verification**: Assert `sometimes|nullable` appears in generated validation rules for scoped FK; assert `firstOrCreate` branch present in generated store/update methods.

## Phase 3: Integration — Stubs (Bugs #1, #3)

- [x] 3.1 In `stubs/fragments/create-field-fk-select.stub`: add `{{scoped_fk_name}}` placeholder; in select branch use `name="{{scoped_fk_name}}"` when scoped, `name="{{fk_column}}"` otherwise
- [x] 3.2 In `stubs/component-inline-relation-fk.stub`: add hidden FK input for scoped relations in inline edit form; add `onSelect` handler to populate hidden input with `item.id`; align select `name` to scoped FK key `{relationName}_{scopedTargetFk}`

**Verification**: Regenerate CobroController + views; inspect generated create view (select name = `deudor_Cliente_id`) and edit view (hidden input present).

## Phase 4: Testing

- [x] 4.1 Unit: Assert `sometimes|nullable|integer|exists:...` validation rules generated for scoped FK
- [x] 4.2 Unit: Assert leading backslash namespace (`\App\Models\ParticipanteCobro`) present in generated pivot instantiation
- [x] 4.3 Unit: Assert hidden input HTML present in generated edit form for scoped relations
- [x] 4.4 Unit: Assert select `name="deudor_Cliente_id"` (not `name="Cliente_id"`) in generated create form
- [ ] 4.5 Integration: Run generator, verify generated CobroController compiles without `ClassNotFoundError`
- [x] 4.6 Regression: Verify standard FK relations (non-scoped) produce unchanged output

## Phase 5: Cleanup

- [x] 5.1 Confirm no remaining `required` validation on scoped hidden FK inputs in generated code
- [x] 5.2 Confirm text-only buscador input is consumed by `firstOrCreate` branch (not silently ignored)