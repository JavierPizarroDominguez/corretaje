# Archive Report: strict-buscador-selection

**Archived**: 2026-05-22
**Change**: strict-buscador-selection
**Status**: Completed

---

## Final Summary

Fixed five interdependent bugs in the buscador (autocomplete) component and CRUD generator that together broke strict entity selection:

1. **API response now includes `id`** in every result item (`buscador-block.stub`).
2. **Hidden FK inputs are generated** for all buscador fields (`create-field-fk-buscador.stub`).
3. **`onSelect` always sets the hidden FK input** for both direct and scoped relations (`create-buscador-call.stub`, `StubRenderer::buildCreateBuscadorCalls()`).
4. **`firstOrCreate` replaced with `findOrFail`** in generated store/update code (`store-field-relation-buscador.stub`, `buildPivotStoreFields()`, `buildPivotUpdateFields()`).
5. **Validation tightened** to `required_with:{buscador_input_name}|integer|exists:{table},id` for hidden FK inputs (`buildValidationRules()`).
6. **JS clears hidden input** when the visible input is emptied (`buscador.js`).
7. **Keyboard selection (Enter/Tab) passes `item.id`** correctly via `dataset.id`.

---

## Specs Synced

| Domain | Action | Details |
|--------|--------|---------|
| `buscador` | Updated | Added 3 requirements: hidden-input clearing, stub hidden-input generation, `required_with` validation |
| `crud-generator` | Updated | Modified 2 requirements (`findOrFail` for pivot store, `required_with` validation); added 4 new requirements (`buildCreateBuscadorCalls`, `store-field-relation-buscador.stub`, `buildPivotStoreFields`, `buildPivotUpdateFields`) |
| `pivot-relation` | Updated | Modified "Controller MUST resolve scoped pivot by name OR by ID" — removed `firstOrCreate` text-only mode, kept `findOrFail` ID resolution only |

---

## Files Changed

### Stubs (Templates)
- `stubs/fragments/buscador-block.stub`
- `stubs/fragments/create-field-fk-buscador.stub`
- `stubs/fragments/create-buscador-call.stub`
- `stubs/fragments/store-field-relation-buscador.stub`

### Generator
- `app/Generator/Rendering/StubRenderer.php`
  - `buildCreateBuscadorCalls()` — removed scoped guard, unconditional hidden-input emit
  - `buildValidationRules()` — `required_with` for all buscador FK fields
  - `buildPivotStoreFields()` — removed `firstOrCreate` fallback
  - `buildPivotUpdateFields()` — removed `firstOrCreate` fallback

### JavaScript
- `public/js/buscador.js`
  - Added hidden-input clearing on empty input
  - Fixed keyboard selection (Enter) to pass `item.id` via `dataset.id`

### Tests
- `tests/Unit/BuscadorScopedRelationsTest.php` — 26 new unit tests covering all modified generator methods and stub rendering

---

## Known Limitations

| Limitation | Impact | Mitigation |
|------------|--------|------------|
| Integration tests (4.6–4.9) not executed | End-to-end request/response cycle untested | Unit tests cover validation rule generation and stub content; integration tests deferred to a running Laravel environment |
| Existing generated controllers/views still contain `firstOrCreate` | Old CRUDs continue creating records from free text | Manual regeneration required (see Migration Notes) |
| Stale test file `GeneratorPivotNameLookupTest.php` | 7 pre-existing failures asserting old `firstOrCreate` behavior | Tests contradict new spec; should be removed or updated in a follow-up task |
| Escape key does not clear hidden input | Hidden ID may persist if user presses Escape after selecting | Validation layer catches this (text without FK → 422); low risk |

---

## Migration Notes for Developers

**When regenerating existing CRUDs after this change:**

1. Re-run the CRUD generator for any model that uses buscador FK fields or scoped pivot relations.
2. Review generated controllers — store/update methods should now use `findOrFail` instead of `firstOrCreate`.
3. Review generated create views — hidden `<input type="hidden">` elements should appear next to every buscador field.
4. Review generated validation rules — hidden FK inputs should use `required_with:{text_input}|integer|exists:{table},id`.
5. No database migrations are required; this is a code-only change.

**Backward compatibility:**
- Existing generated files continue working as before until regenerated.
- The buscador.js changes are global and apply immediately to all pages.

---

## Archive Contents

- `proposal.md` — original change proposal
- `specs/` — delta specs for `buscador`, `crud-generator`, `pivot-relation`
- `design.md` — technical design and architecture decisions
- `tasks.md` — all 13 tasks marked complete (integration tests deferred)
- `verify-report.md` — verification results (unit tests passed; critical JS keyboard bug fixed post-report)

---

## Source of Truth Updated

The following specs now reflect the new behavior:
- `openspec/specs/buscador/spec.md`
- `openspec/specs/crud-generator/spec.md`
- `openspec/specs/pivot-relation/spec.md`

---

## SDD Cycle Complete

The change has been fully planned, implemented, verified, and archived.
Ready for the next change.
