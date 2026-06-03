# Design: Buscador deudor/acreedor — Generator fix

## Technical Approach

Fix the generator at source: change `buscadorInputName()` to use `relationName` (unique per FK field) instead of `referencedTable` (shared for same-table scoped relations); add buscador JS calls to `renderModalCreate()`; patch `CobroController` for dual-input backward compat and syntax error; regenerate cobro views.

## Architecture Decisions

| Decision | Options | Choice | Rationale |
|----------|---------|--------|-----------|
| Input name discriminator | `relationName` vs `referencedTable` | `relationName` | Already used as `fieldId` in `buildCreateFormFields()` — verified available in `ColumnMetadata` (set in `buildScopedColumn()` line 274: `relationName: $key`). Gives `nombre-deudor` / `nombre-acreedor` — unique per field, no collisions. |
| JS placement in modal | Inline `<script>` vs `@push` | Inline `<script>` | Modal loaded via AJAX — `@push('scripts')` only runs on full page render. Inline `<script>` executes when the HTML is inserted into the DOM. `buscador.js` already uses document-level event delegation, so no timing issue. |
| Backward compatibility | Dual-read vs migration script | Dual-read in controller | No DB migration needed. Controller reads new name first, falls back to old name. Old forms in open browser tabs still work (both values share the old bug until refresh — acceptable transition risk). |
| Scoped store syntax fix | Inline correction | `$cobro->id` | `${cobro}->id` is PHP variable-variable syntax — always wrong. Simply change to `$cobro->id` (the Cobro object is in scope after `$cobro->save()`). |

## Data Flow

```
Browser form (modal create via AJAX)
│
│  POST /cobro
│  name="nombre-deudor" ──→ $data['nombre-deudor']
│  name="nombre-acreedor" ──→ $data['nombre-acreedor']
│
▼
CobroController::store()
│
├─ validation rules include both new + old names
│
├─ store_fields block:
│   deudor  → $data['nombre-deudor'] ?? $data['nombre-participante_cobro']
│   acreedor → $data['nombre-acreedor'] ?? $data['nombre-participante_cobro']
│   (read new, fall back to old — both values survive)
│
├─ scoped_store_fields block:
│   Fixes: $cobro->id instead of ${cobro}->id
│   (still reads $data['nombre-participante_cobro'] — will be fixed to
│    new names in a follow-up when scoped store logic is audited)
│
▼
buscador.js init (inline <script> in modal):
  buscador({ input: '#input-create-deudor', list: '#listaCreateDeudor', tipo: 'participante_cobro', ... })
  buscador({ input: '#input-create-acreedor', list: '#listaCreateAcreedor', tipo: 'participante_cobro', ... })
  (tipo still uses referencedTable for search endpoint — correct)
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Generator/Rendering/StubRenderer.php` | Modify | `renderModalCreate()`: add `buildCreateBuscadorCalls()` + `str_replace('{{create_buscador_calls}}')`. `buscadorInputName()`: change discriminator from `referencedTable` to `relationName` with fallback. |
| `stubs/modal-create.stub` | Modify | Add `<script>\n{{create_buscador_calls}}\n</script>` before `</form>`. |
| `app/Http/Controllers/Crud/CobroController.php` | Modify | Dual-read in `store()` + `update()`: new names with old fallback. Fix `${cobro}->id` → `$cobro->id`. Remove duplicate `$data['nombre-participante_cobro']` validation rule. |
| `resources/views/cobro/modal/create.blade.php` | Regenerated | Fixed input names + inline buscador `<script>`. |
| `resources/views/cobro/create.blade.php` | Regenerated | New input names. |
| `resources/views/cobro/edit.blade.php` | Regenerated | New input names. |

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | `buscadorInputName()` with scoped column | Verify `relationName` is preferred over `referencedTable`; test fallback when null |
| Unit | `renderModalCreate()` output | Verify `{{create_buscador_calls}}` placeholder is replaced; verify inline `<script>` tag in output |
| Manual | Form submit deudor + acreedor | Open browser, fill both buscador fields, submit — verify both stored correctly |
| Manual | Old form backward compat | Keep old form open, submit — verify it still works (both get same value, same as before) |
| Regeneration | `php artisan gen:crud cobro --only=views,controller` | Verify no errors, diff output matches expectations |

## Open Questions

- [ ] The `scoped_store_fields` block (lines 136-160) has deeper architectural issues beyond the syntax error — it creates *new* ParticipanteCobro pivot records instead of linking to the one selected by buscador. This was pre-existing and out of scope, but worth noting for a future change.
- [ ] `update()` method has the same `nombre-participante_cobro` duplicate + missing new names — fix it identically to `store()`.
- [ ] The `scoped_store_fields` still reads `$data['nombre-participante_cobro']` — should it also be updated to new names? Deferred until scoped store logic is refactored.
