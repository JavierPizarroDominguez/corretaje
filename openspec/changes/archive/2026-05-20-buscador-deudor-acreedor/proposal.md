# Proposal: Buscador deudor/acreedor

## Intent

Two generator bugs: (1) buscador JS init missing from modal create views, (2) deudor/acreedor share same input name via `buscadorInputName()` convention. Root cause is in the generator — every entity with same-table FK relations hits the same collision.

## Scope

### In Scope

- Fix `renderModalCreate()` to emit buscador JS calls (Bug 1)
- Add `{{create_buscador_calls}}` to `modal-create.stub` (inline `<script>`, no `@push`)
- Fix `buscadorInputName()` disambiguation via `relationName` (Bug 2)
- Fix `CobroController` store logic (correct input names, `${cobro}->id` syntax error)
- Regenerate cobro views (modal/create, create, edit)

### Out of Scope

- Auditing other entities with same-table FK collisions (deferred)
- Refactoring `buscador.js`
- New buscador features

## Capabilities

### New Capabilities

None — pure bug fix.

### Modified Capabilities

None — no spec-level behavior changes.

## Approach

1. **StubRenderer.php**: Add `buildCreateBuscadorCalls()` in `renderModalCreate()` with new `{{create_buscador_calls}}` placeholder
2. **modal-create.stub**: Add placeholder wrapped in inline `<script>` (no `@push` — AJAX loaded)
3. **buscadorInputName()**: Use `{display_field}-{relationName}` when `relationName` differs from auto-derived name → `nombre-deudor` / `nombre-acreedor` instead of both `nombre-participante_cobro`
4. **CobroController**: Read new input names; fix `${cobro}->id` → `$cobro->id`
5. **Regenerate**: `gen:crud cobro` then verify output

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Generator/Rendering/StubRenderer.php` | Modified | `renderModalCreate()` + `buscadorInputName()` |
| `stubs/modal-create.stub` | Modified | `{{create_buscador_calls}}` placeholder |
| `app/Http/Controllers/Crud/CobroController.php` | Modified | Store logic + syntax fix |
| `resources/views/cobro/modal/create.blade.php` | Regenerated | New buscador inits + input names |
| `resources/views/cobro/create.blade.php` | Regenerated | New input names |
| `resources/views/cobro/edit.blade.php` | Regenerated | New input names |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Input name change breaks existing forms | High | Controller reads both old and new input names during transition |
| Regeneration overwrites manual patches | Medium | Diff before commit; keep generated files in git |
| Modal JS execution order (AJAX load) | Low | Inline `<script>` runs after DOM insertion; buscador.js uses delegation |

## Rollback Plan

1. **Git revert**: `git revert HEAD` restores all files
2. **Views broken**: `gen:crud cobro` regenerates fresh copies
3. **Generator broken**: revert `StubRenderer.php` + stub, manually patch cobro views as stopgap

## Dependencies

- None

## Success Criteria

- [ ] All buscador fields in cobro modal create work (deudor, acreedor, contrato, servicio, propiedad, unidad)
- [ ] Deudor and acreedor submit different values; controller stores both correctly
- [ ] Full-page create and edit still work after input name change
- [ ] Modal create uses inline `<script>` only — no `@push`
- [ ] `gen:crud cobro` regenerates without errors
