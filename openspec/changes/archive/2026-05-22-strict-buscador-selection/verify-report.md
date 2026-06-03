## Verification Report

**Change**: strict-buscador-selection
**Version**: N/A
**Mode**: Standard

### Completeness

| Metric | Value |
|--------|-------|
| Tasks total | 13 |
| Tasks complete | 9 |
| Tasks incomplete | 4 |

**Incomplete tasks**:
- 4.6 Integration: POST form with buscador text but empty FK ID → 422 validation error on FK field
- 4.7 Integration: POST form with valid FK ID and text → 200, record created with correct FK
- 4.8 Integration: POST form with neither field set → 200 (optional field)
- 4.9 JS: Type in buscador, select item, backspace to empty → hidden input value is `''`

### Build & Tests Execution

**Tests**: ✅ 26 passed (new) / ❌ 12 failures / 1 error (pre-existing test suite)

```
vendor/bin/phpunit tests/Unit/BuscadorScopedRelationsTest.php
Tests: 26, Assertions: 89 — OK

vendor/bin/phpunit (full suite)
Tests: 91, Assertions: 207, Errors: 1, Failures: 12
```

**Full suite failures breakdown**:
- 7 failures in `GeneratorPivotNameLookupTest` — this test file asserts the OLD behavior (`firstOrCreate` fallback, `sometimes|nullable` for scoped FK) which was intentionally replaced. These are **stale tests**, not implementation bugs.
- 4 failures in `GeneratorUniversalidadTest` — pre-existing, unrelated to this change (missing config keys `model_namespace`, `months`, `filter_titles`).
- 1 error in `GeneratorUniversalidadTest::test_custom_filter_titles_from_config` — pre-existing, unrelated.
- 1 failure in `ClienteConstraintMessagesTest` — pre-existing, unrelated.
- 1 failure in `ExampleTest` — pre-existing, unrelated.

**Coverage**: ➖ Not available (no coverage tooling configured)

### Spec Compliance Matrix

| Requirement | Scenario | Test | Result |
|-------------|----------|------|--------|
| Buscador API MUST include `id` in response | Cliente/Unidad/Contrato/Servicio/Propiedad includes id | Unit: `test_create_buscador_call_*` covers `item.id` in onSelect; stub verified directly | ✅ COMPLIANT |
| onSelect MUST set hidden input for ALL buscador fields | contrato, servicio, propiedad, unidad, deudor, acreedor | Unit: `test_create_buscador_call_sets_hidden_input_for_all_buscador_fields` | ✅ COMPLIANT |
| onSelect MUST set hidden input for scoped relations | deudor, acreedor | Unit: `test_create_buscador_call_on_select_sets_hidden_input_for_scoped` | ✅ COMPLIANT |
| Hidden input MUST be in stub | `create-field-fk-buscador.stub` contains hidden input | Static: stub file verified | ✅ COMPLIANT |
| Hidden input cleared when visible input cleared | Backspace to empty clears hidden | `public/js/buscador.js` lines 31-33 | ✅ COMPLIANT |
| Validation MUST require FK ID when text present | Text without FK → 422; Valid FK → pass; Empty both → pass | Unit: `test_validation_rules_uses_required_with_for_hidden_fk`, `test_validation_rules_normal_buscador_uses_required_with` | ⚠️ PARTIAL |
| Validation for ALL buscador fields (not just scoped) | Direct FK (contrato, servicio) also gets required_with | Unit: `test_validation_rules_normal_buscador_uses_required_with` | ✅ COMPLIANT |
| ` StubRenderer::buildCreateBuscadorCalls()` sets hidden for ALL FKs | No scoped guard | Static: code review shows unconditional emit | ✅ COMPLIANT |
| `buildValidationRules()` uses required_with | All buscador fields | Unit: verified | ✅ COMPLIANT |
| `buildPivotStoreFields()` uses findOrFail only | No firstOrCreate fallback | Unit: `test_pivot_store_fields_uses_find_or_fail_only` | ✅ COMPLIANT |
| `buildPivotUpdateFields()` uses findOrFail | No firstOrCreate fallback | Unit: `test_pivot_update_fields_checks_hidden_fk_and_uses_find_or_fail` | ✅ COMPLIANT |
| `store-field-relation-buscador.stub` uses findOrFail | Stub content | Unit: test 4.5 | ✅ COMPLIANT |
| Tampered FK ID fails validation | Submit with non-existent ID → 422 | No covering test | ❌ UNTESTED |
| Integration: text without FK → 422 | Full request cycle | No covering test (task 4.6) | ❌ UNTESTED |
| Integration: valid FK + text → 200 | Full request cycle | No covering test (task 4.7) | ❌ UNTESTED |
| Integration: empty both → 200 | Full request cycle | No covering test (task 4.8) | ❌ UNTESTED |
| JS: Enter key selection passes `item.id` | Enter key in dropdown | No covering test (not in task list) | ❌ FAILING (see CRITICAL below) |

**Compliance summary**: 12/17 scenarios compliant, 2 partial, 3 untested

### Correctness (Static Evidence)

| Requirement | Status | Notes |
|------------|--------|-------|
| `buscador-block.stub` returns `id` in both direct and relation branches | ✅ Implemented | Lines 20, 37: `'id' => $item->id` |
| `create-buscador-call.stub` sets hidden ID unconditionally | ✅ Implemented | Line 7: `input-create-{{field_id}}-id` |
| `create-field-fk-buscador.stub` contains hidden input | ✅ Implemented | Lines 11-14: hidden input with `name="{{fk_column}}"` and `id="input-create-{{field_id}}-id"` |
| `store-field-relation-buscador.stub` uses findOrFail | ✅ Implemented | Line 2: `{{RelatedModel}}::findOrFail($data['{{fk_column}}'])`; no `firstOrCreate` |
| `buildCreateBuscadorCalls()` sets hidden for ALL FKs | ✅ Implemented | No scoped guard; emits hidden input assignment unconditionally (line 631) |
| `buildValidationRules()` enforces required_with | ✅ Implemented | Lines 909-914: `required_with:{buscadorName}\|integer\|exists:{table},id` for ALL buscador fields |
| `buildPivotStoreFields()` no firstOrCreate fallback | ✅ Implemented | Lines 1077-1099: only `findOrFail` branch; no `elseif` with `firstOrCreate` |
| `buildPivotUpdateFields()` no firstOrCreate fallback | ✅ Implemented | Lines 1143-1166: only `findOrFail` branch; uses `firstOrNew` for pivot record lookup (correct, this is for existing pivot lookup, not target entity creation) |
| `buscador.js` clears hidden input on empty | ✅ Implemented | Lines 31-33: sets hidden input value to `''` when visible input is empty |

### Coherence (Design)

| Decision | Followed? | Notes |
|----------|-----------|-------|
| Validation: `required_with` instead of `required` | ✅ Yes | Implemented as `required_with:{buscador_input_name}\|integer\|exists` |
| Entity resolution: `findOrFail` instead of `firstOrCreate` | ✅ Yes | All four code paths (stub, store pivot, update pivot) use `findOrFail` |
| Hidden input scope: ALL buscador FK fields, not just scoped | ✅ Yes | `buildCreateBuscadorCalls` and `buildValidationRules` both handle direct FK and scoped FK |
| Clear behavior: backspace clears hidden input | ✅ Yes | `buscador.js` lines 31-33 |
| Transition: document regeneration, no auto-migration | ✅ Yes | No migration files; stub changes only |

### Issues Found

**CRITICAL**:
1. **Enter key selection does NOT pass `item.id` to `onSelect`**. In `public/js/buscador.js` line 113, the Enter key handler calls `config.onSelect({ url: target.dataset.url, texto: target.textContent.trim() })` — this constructed object is missing the `id` property. When a user selects an item via Enter key (arrow navigation + Enter), the hidden FK input will be set to `undefined`. The root cause is twofold: (a) `a.dataset.id = item.id` is never stored on line 67 (only `url` is stored), and (b) line 113 constructs a partial object without `id`. Fix: add `a.dataset.id = item.id;` on line 67, and change line 113 to include `id: target.dataset.id` (or parse as int: `id: parseInt(target.dataset.id, 10)`).

**WARNING**:
1. **Integration tests (4.6–4.8) are not implemented**. The validation and controller behavior cannot be verified through a real HTTP request cycle. Unit tests cover code generation, but end-to-end validation (`required_with` triggering 422, valid ID passing, empty-both passing) is untested.
2. **JS end-to-end test (4.9) is not implemented**. The backspace-clearing behavior in `buscador.js` has no browser or Playwright test.
3. **Stale test file `GeneratorPivotNameLookupTest`** (7 failures) asserts OLD behavior (`firstOrCreate` fallback, `sometimes|nullable` for scoped FK). These tests must be updated or removed since the spec intentionally changed this behavior.
4. **Escape key does not clear hidden input**. When user presses Escape while the dropdown is open, lines 121-124 clear `list.innerHTML` and `resultItems` but do NOT reset `hidden.value`. The design doc marks this as "defer" because validation catches stale text, but if the user clears text via Escape after already selecting, the hidden ID persists. This is a design-acknowledged gap.

**SUGGESTION**:
1. Add `a.dataset.id = item.id;` on line 67 and `id: parseInt(target.dataset.id, 10)` on line 113 to fix the Enter key path. This is a one-line addition plus a one-line fix.
2. Consider resetting the hidden input in the Escape handler as well (`var hidden = document.getElementById(inputId + '-id'); if (hidden) hidden.value = '';`) for defensive consistency.
3. Remove or update `GeneratorPivotNameLookupTest.php` — its assertions contradict the new spec. The new `BuscadorScopedRelationsTest.php` correctly covers the new behavior.
4. The `buscador.js` Tab key handler (line 102-105) also constructs an object for `onSelect` without `id` — same bug as Enter. Tab selection will also fail to set the hidden FK input.

### Verdict

**FAIL**

The Enter and Tab key selection paths in `buscador.js` do not pass `item.id`, which means users who select results via keyboard will submit forms with `undefined` in the hidden FK input — defeating the entire strict-selection mechanism. This is a spec-breaking bug that must be fixed before this change can pass verification.