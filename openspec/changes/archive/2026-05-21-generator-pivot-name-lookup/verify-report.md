## Verification Report (Post Extra-Brace Incident Fix)

**Change**: generator-pivot-name-lookup
**Version**: N/A (delta spec)
**Mode**: Standard
**Date**: 2026-05-21 (re-verification after extra-brace incident fix)

### Completeness
| Metric | Value |
|--------|-------|
| Tasks total | 15 |
| Tasks complete | 15 (implementation) |
| Tasks incomplete | 0 |

Note: All 15 implementation tasks were completed. However, the "extra braces" incident fix introduced a CRITICAL regression — the `buildPivotUpdateFields()` method was deleted from `StubRenderer.php`.

### Build & Tests Execution
**Build**: ✅ Passed (PHP syntax valid — no compile errors in generated views or controller)
```text
php -l app/Http/Controllers/Crud/CobroController.php → No syntax errors
php -l resources/views/cobro/create.blade.php → No syntax errors
php -l resources/views/cobro/edit.blade.php → No syntax errors
php -l resources/views/cobro/show.blade.php → No syntax errors
Brace balance: 50 open, 50 close — BALANCED
```

**Tests**: ❌ 15 failed / 63 passed / 0 skipped
```text
php artisan test --no-coverage

FAILURES RELATED TO THIS CHANGE (8 — missing buildPivotUpdateFields):
1. GeneratorPivotNameLookupTest::test_build_pivot_update_fields_uses_leading_backslash_for_pivot_model
2. GeneratorPivotNameLookupTest::test_build_pivot_update_fields_uses_leading_backslash_for_target_model
3. GeneratorPivotNameLookupTest::test_build_pivot_update_fields_has_firstorcreate_fallback
4. GeneratorPivotNameLookupTest::test_build_pivot_update_fields_checks_buscador_input_before_firstorcreate
5. BuscadorScopedRelationsTest::test_pivot_update_fields_uses_first_or_new_for_scoped_relation
6. BuscadorScopedRelationsTest::test_pivot_update_fields_empty_for_non_scoped_schema
7. BuscadorScopedRelationsTest::test_pivot_update_fields_checks_hidden_fk
8. GeneratorUniversalidadTest::test_build_pivot_update_fields_uses_config_namespace

All 8 fail with: ReflectionException: Method StubRenderer::buildPivotUpdateFields() does not exist

PRE-EXISTING FAILURES (7 — unrelated to this change):
- GeneratorUniversalidadTest: config keys missing (3 tests)
- GeneratorUniversalidadTest: custom filter titles from config (1 test)
- BuscadorScopedRelationsTest::both_deudor_and_acreedor_have_distinct_pivot_update_fields (1 — same root cause)
- ClienteConstraintMessagesTest::store with invalid rut shows readable message
- ExampleTest::the_application_returns_a_successful_response
```

**Coverage**: ➖ Not available (no coverage tool configured)

### Spec Compliance Matrix

| Requirement | Scenario | Test | Result |
|-------------|----------|------|--------|
| REQ-1: Select name uses scoped_fk_name for scoped relations | Create form select posts to deudor_Cliente_id | BuscadorScopedRelationsTest + grep of generated views | ✅ COMPLIANT |
| REQ-1: Select name uses scoped_fk_name for scoped relations | Edit form select posts to deudor_Cliente_id | GeneratorPivotNameLookupTest::test_build_edit_form_fields_uses_scoped_fk_name_for_select | ✅ COMPLIANT |
| REQ-1: Select name uses scoped_fk_name for scoped relations | Inline edit select posts to deudor_Cliente_id | GeneratorPivotNameLookupTest::test_render_relation_fk_row_adds_hidden_fk_for_scoped_relation | ✅ COMPLIANT |
| REQ-2: Validation conditional (sometimes\|nullable) for scoped FK | buildValidationRules emits sometimes\|nullable for hidden FK | BuscadorScopedRelationsTest::test_validation_rules_adds_hidden_fk_for_scoped_relation | ✅ COMPLIANT |
| REQ-2: Validation for buscador text input | buildValidationRules emits sometimes\|nullable\|string for buscador text | GeneratorPivotNameLookupTest::test_build_validation_rules_adds_buscador_text_rule | ✅ COMPLIANT |
| REQ-3: Absolute namespace for pivot model (store) | Store uses \App\Models\ParticipanteCobro | GeneratorPivotNameLookupTest::test_build_pivot_store_fields_uses_leading_backslash_for_pivot_model | ✅ COMPLIANT |
| REQ-3: Absolute namespace for pivot model (update) | Update uses \App\Models\ParticipanteCobro::firstOrNew | GeneratorPivotNameLookupTest::test_build_pivot_update_fields_uses_leading_backslash_for_pivot_model | ❌ FAILING — method missing |
| REQ-3: Absolute namespace for target model (store) | Store uses \App\Models\Cliente | GeneratorPivotNameLookupTest::test_build_pivot_store_fields_uses_leading_backslash_for_target_model | ✅ COMPLIANT |
| REQ-3: Absolute namespace for target model (update) | Update uses \App\Models\Cliente | GeneratorPivotNameLookupTest::test_build_pivot_update_fields_uses_leading_backslash_for_target_model | ❌ FAILING — method missing |
| REQ-4: Name resolution via firstOrCreate (store) | Store has firstOrCreate fallback for buscador text | GeneratorPivotNameLookupTest::test_build_pivot_store_fields_has_firstorcreate_fallback | ✅ COMPLIANT |
| REQ-4: Name resolution via firstOrCreate (update) | Update has firstOrCreate fallback for buscador text | GeneratorPivotNameLookupTest::test_build_pivot_update_fields_has_firstorcreate_fallback | ❌ FAILING — method missing |
| REQ-4: Priority ID first, then name (store) | Store checks hidden FK before buscador text | GeneratorPivotNameLookupTest::test_build_pivot_store_fields_checks_buscador_input_before_firstorcreate | ✅ COMPLIANT |
| REQ-4: Priority ID first, then name (update) | Update checks hidden FK before buscador text | GeneratorPivotNameLookupTest::test_build_pivot_update_fields_checks_buscador_input_before_firstorcreate | ❌ FAILING — method missing |
| REQ-5: Hidden FK input in inline edit | renderRelationFkRow includes hidden input with scoped key | GeneratorPivotNameLookupTest::test_render_relation_fk_row_adds_hidden_fk_for_scoped_relation | ✅ COMPLIANT |
| REQ-5: Hidden FK input in create form | buildCreateFormFields includes hidden input | BuscadorScopedRelationsTest::test_create_form_fields_adds_hidden_input_for_scoped | ✅ COMPLIANT |
| Incident fix 1: {{scoped_fk_name}} resolved in create views | No literal {{scoped_fk_name}} in generated cobro views | grep of resources/views/cobro — 0 matches | ✅ COMPLIANT |
| Incident fix 2: No extra braces in CobroController | Controller has balanced braces, no stray closing braces | php -l + brace balance check (50/50) | ✅ COMPLIANT |
| Regression: Regular FK unchanged | Edit form uses fk_column for non-scoped | GeneratorPivotNameLookupTest::test_build_edit_form_fields_regular_fk_unchanged | ✅ COMPLIANT |
| Regression: Regular FK unchanged | No hidden input for non-scoped inline edit | GeneratorPivotNameLookupTest::test_render_relation_fk_row_regular_fk_no_hidden_input | ✅ COMPLIANT |
| Regression: Regular FK unchanged | No firstOrCreate for non-scoped | GeneratorPivotNameLookupTest::test_build_pivot_store_fields_no_firstorcreate_for_regular_fk | ✅ COMPLIANT |
| Regression: Regular FK uses fk_column not scoped key | Edit form uses fk_column | GeneratorPivotNameLookupTest::test_build_edit_form_fields_regular_fk_uses_fk_column_not_scoped_key | ✅ COMPLIANT |

**Compliance summary**: 17/21 scenarios compliant, 4 FAILING (all due to missing `buildPivotUpdateFields()` method)

### Correctness (Static Evidence)
| Requirement | Status | Notes |
|------------|--------|-------|
| Scoped FK select name (deudor_Cliente_id) | ✅ Implemented | Verified in create.blade.php, modal/create.blade.php, edit.blade.php, show.blade.php |
| Hidden FK input in create form | ✅ Implemented | buildCreateFormFields lines 452-454, 464, 469-473 |
| Hidden FK input in inline edit | ✅ Implemented | component-inline-relation-fk.stub {{hidden_fk_input}} placeholder |
| Validation sometimes\|nullable | ✅ Implemented | buildValidationRules + CobroController validation rules |
| Absolute namespace (store) | ✅ Implemented | buildPivotStoreFields — \App\Models\ParticipanteCobro, \App\Models\Cliente |
| firstOrCreate fallback (store) | ✅ Implemented | buildPivotStoreFields line 1087 |
| **buildPivotUpdateFields method** | ❌ **MISSING** | Method called on line 50 but not defined — deleted during "extra braces" incident fix |
| CobroController update scoped fields | ✅ Present | Lines 327-361 — correct firstOrNew with absolute namespace, name resolution |
| No extra braces in CobroController | ✅ Verified | 50 open, 50 close braces — perfectly balanced |
| No {{scoped_fk_name}} in generated views | ✅ Verified | grep found 0 matches |

### Coherence (Design)
| Decision | Followed? | Notes |
|----------|-----------|-------|
| Select name uses {relationName}_{scopedTargetFk} | ✅ Yes | Both create and edit form paths |
| Validation: sometimes\|nullable for scoped FK | ✅ Yes | Replaces old required rule |
| Absolute namespace with leading backslash (store) | ✅ Yes | Both pivot model and target model in store path |
| Absolute namespace with leading backslash (update) | ⚠️ Partial | Code exists in CobroController but generator method is missing |
| firstOrCreate for name resolution (store) | ✅ Yes | ID-first priority maintained |
| firstOrCreate for name resolution (update) | ⚠️ Partial | Code exists in CobroController but generator method is missing |
| Hidden FK input mirrors create form pattern | ✅ Yes | |

### Issues Found

**CRITICAL**:
1. **`buildPivotUpdateFields()` method DELETED from `StubRenderer.php`**: The method is called on line 50 (`$this->buildPivotUpdateFields($schema)`) but does NOT exist as a method definition. This was caused by the "extra braces" incident fix — when `buildPivotStoreFields()` was rewritten, `buildPivotUpdateFields()` was apparently deleted. Consequence:
   - 8 tests fail with `ReflectionException: Method does not exist`
   - The generator CANNOT regenerate controllers for any model with scoped relations — calling `renderController()` would crash
   - The existing `CobroController.php` works only because it was generated before the method was deleted

**WARNING**: None beyond the critical above.

**SUGGESTION**:
1. After restoring `buildPivotUpdateFields()`, add a smoke test that calls `renderController()` with a scoped-relation schema.
2. Consider a CI check that verifies all methods called in `renderController()` actually exist (static analysis).

### Verdict
**FAIL**
The `buildPivotUpdateFields()` method was accidentally deleted during the "extra braces" incident fix. The method is called on line 50 of `StubRenderer.php` but has no definition. This breaks the generator for any model with scoped relations. The existing `CobroController.php` is syntactically correct and functionally complete, but the generator cannot reproduce this output.
