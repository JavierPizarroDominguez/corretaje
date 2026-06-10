# Apply Progress: Ficha Cobro — Context-Aware Modal

**Status**: ✅ All 14 tasks complete
**Mode**: Strict TDD (RED → GREEN → REFACTOR)

## Completed Tasks

### Phase 1: RED — Write failing tests
- [x] 1.1 Unit test: `CobroRelationshipResolverTest` asserts `resolveManualTipo()` returns `participants` array
- [x] 1.2 Feature test: ficha modal GET renders `deudor`/`acreedor` `<select>` with "Seleccione" and contract-participant options
- [x] 1.3 Feature test: `POST /cobro` with `_ficha_context=1` rejects empty `monto`
- [x] 1.4 Feature test: `POST /cobro` with `_ficha_context=1` rejects empty `detalle`
- [x] 1.5 Feature test: `POST /cobro` with `_ficha_context=1` rejects missing `deudor_Cliente_id`
- [x] 1.6 Feature test: `POST /cobro` omitting `fecha_cobro`/`estado` creates record with `now()` and `Pendiente`

### Phase 2: GREEN — Backend services & controller
- [x] 2.1 `CobroRelationshipResolver::resolveManualTipo()`: add `participants` key
- [x] 2.2 `CobroController::store()`: merge defaults, enforce validations

### Phase 3: GREEN — Controllers & views
- [x] 3.1 `FichaClienteController`: derive `$participantOptions` from `$contratosVigentes`
- [x] 3.2 `FichaPropiedadController`: same for property's active contracts
- [x] 3.3 `pendientes.blade.php`: pass ficha context to modal
- [x] 3.4 `pendientes-propiedad.blade.php`: pass ficha context to modal
- [x] 3.5 `cobro/modal/create.blade.php`: branch on `$fichaContext`

### Phase 4: REFACTOR — Convention compliance
- [x] 4.1 `showElLoading`/`hideElLoading` used on all fetch calls (pre-existing ✓)
- [x] 4.2 `flashModal` used for errors — `showCobroError` helper added, no `alert()`/`confirm()`
- [x] 4.3 `_ficha_context` hidden input sent on ficha submit

## TDD Cycle Evidence

| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
|------|-----------|-------|------------|-----|-------|-------------|----------|
| 1.1 / 2.1 | `tests/Unit/Services/CobroRelationshipResolverTest.php` | Unit | ✅ 215/224 | ✅ Written | ✅ Passed | ✅ 3 cases | ✅ Helper extracted |
| 1.2 | `tests/Feature/FichaCobroCreateTest.php` | Feature | ✅ 215/224 | ✅ Written | ✅ Passed | ➖ 2 ficha views | ➖ None needed |
| 1.3 | `tests/Feature/FichaCobroCreateTest.php` | Feature | ✅ 215/224 | ✅ Written | ✅ Passed | ➖ Single | ✅ flashModal error added |
| 1.4 | `tests/Feature/FichaCobroCreateTest.php` | Feature | ✅ 215/224 | ✅ Written | ✅ Passed | ➖ Single | ➖ None needed |
| 1.5 | `tests/Feature/FichaCobroCreateTest.php` | Feature | ✅ 215/224 | ✅ Written | ✅ Passed | ➖ Single | ➖ None needed |
| 1.6 | `tests/Feature/FichaCobroCreateTest.php` | Feature | ✅ 215/224 | ✅ Written | ✅ Passed | ➖ Single | ➖ None needed |
| 2.2 | N/A (controller in feature tests) | Feature | ✅ 215/224 | ✅ Written | ✅ Passed | ✅ 3 validation cases | ➖ None needed |
| 3.1-3.5 | N/A (view rendering tested in feature) | Feature | ✅ 215/224 | N/A (view) | ✅ Tests pass | N/A | ✅ Convention compliance |

### Test Summary
- **Total tests written**: 9 (3 unit + 6 feature)
- **Total tests passing**: 9 of 9
- **Layers used**: Unit (3), Feature (6)
- **Pre-existing failures**: 19 (3 errors, 16 failures) — all pre-existing, unrelated
- **Regression check**: Full suite still has exactly same 19 pre-existing failures

## Files Changed

| File | Action | What Was Done |
|------|--------|---------------|
| `tests/Unit/Services/CobroRelationshipResolverTest.php` | Created | Unit tests for `participants` array in resolver response |
| `tests/Feature/FichaCobroCreateTest.php` | Created | Feature tests for ficha modal rendering + store validation |
| `app/Services/CobroRelationshipResolver.php` | Modified | Added `participants` key with `extractParticipantsFromContracts()` |
| `app/Http/Controllers/Crud/CobroController.php` | Modified | Ficha defaults merge + conditional required validation |
| `app/Http/Controllers/Vistas/FichaClienteController.php` | Modified | Compute `$participantOptions` from contratosVigentes |
| `app/Http/Controllers/Vistas/FichaPropiedadController.php` | Modified | Same for property's active contracts |
| `resources/views/components/pendientes.blade.php` | Modified | Pass ficha context to modal include |
| `resources/views/components/pendientes-propiedad.blade.php` | Modified | Pass ficha context to modal include |
| `resources/views/cobro/modal/create.blade.php` | Modified | Branch on `$fichaContext`: hide date/state, restrict tipos, CLP monto, participant selects, submit handler |

## Deviations from Design
None — implementation matches the design.md exactly.

## Issues Found
- Pre-existing test failures (19) are unrelated to this change.
- Form POST validation returns 302 (not 422) in Laravel — test assertions adjusted accordingly.

## Workload / PR Boundary
- Mode: size:exception (single PR)
- Estimated review budget: ~300-400 lines
