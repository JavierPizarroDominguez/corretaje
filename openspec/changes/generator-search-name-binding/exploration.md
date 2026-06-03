## Exploration: Generator Search-Name Binding for FK Relations

### Current State

The code generator at `app/Generator/` already implements a two-mode input system for FK (belongsTo) relations in generated CRUD views:

1. **Buscador mode** (when related table has > `select_threshold` records, default 15): Renders a text input where the user types a name. The input `name` follows the convention `{display_field}-{relationName}` (e.g., `direccion-propiedad`, `nombre-unidad`). The JS `buscador()` component makes AJAX calls to `BuscadorController` and on select, sets `input.value = item.texto` (the display text, NOT the ID).

2. **Select mode** (when related table has <= threshold records): Renders a `<select>` that sends the FK ID directly via the `{fk_column}` name (e.g., `Propiedad_id`). It also includes a hidden buscador secondary input for the "Agregar nuevo" flow, which sends the text via the same `{display_field}-{relationName}` name.

**The controller store/update code already handles both paths correctly:**

- For **buscador-type** relations (`relationInputType === 'buscador'`): The `store-field-relation-buscador.stub` generates `firstOrCreate` logic that looks up the related model by display field and creates it if not found:
  ```php
  if (isset($data['direccion-propiedad'])) {
      $propiedad = Propiedad::firstOrCreate([
          'direccion' => trim($data['direccion-propiedad'])
      ]);
      $cobro->Propiedad_id = $propiedad->id;
  }
  ```

- For **select-type** relations (`relationInputType === 'select'`): Both paths are generated — the select path (FK ID) AND the buscador path (firstOrCreate):
  ```php
  if (isset($data['Propiedad_id']) && $data['Propiedad_id'] !== '__nuevo__') {
      $cobro->Propiedad_id = $data['Propiedad_id'];
  }
  if (isset($data['direccion-propiedad'])) {
      $propiedad = Propiedad::firstOrCreate([
          'direccion' => trim($data['direccion-propiedad'])
      ]);
      $cobro->Propiedad_id = $propiedad->id;
  }
  ```

**Key finding**: The `firstOrCreate` approach means the buscador input does NOT need the user to send an ID. The controller resolves the name to an ID by looking up (or creating) the related entity. This is intentional — the buscador sends the display text, and the controller converts it.

**However, there are real problems with this design:**

1. **Unintentional entity creation**: `firstOrCreate` will CREATE a new entity if the typed text doesn't match any existing record. If a user types "Calle Falsa 123" and selects a result, but the text is slightly different (extra space, typo), a NEW Propiedad record gets created instead of linking to the existing one. This is dangerous for production data.

2. **No ID passthrough from buscador**: The JS `buscador()` component's `onSelect` callback only sets `item.texto` (the display text) into the input field. It does NOT set a hidden ID field for the standard (non-scoped) buscador inputs. Compare:
   - **Scoped relations** (deudor, acreedor): DO set a hidden ID — `document.getElementById('input-create-deudor-id').value = item.id;`
   - **Standard relations** (propiedad, unidad): Only set the text — `document.getElementById('input-create-propiedad').value = item.texto;`

3. **Ambiguous matches**: `firstOrCreate` on `direccion` will match the FIRST property with that address. If multiple properties share the same address, the wrong one could be linked.

4. **Validation gap**: The buscador input is validated as `sometimes|nullable|string` — no check that the text actually corresponds to an existing record. A user could type gibberish and get a new entity created.

5. **display_field on `id`**: For Contrato and Servicio, the `display_field` is `id`, meaning `firstOrCreate` does `Contrato::firstOrCreate(['id' => trim($data['id-contrato'])])`. This is semantically wrong — you can't `firstOrCreate` by primary key; it should find by ID or fail, never create.

### Affected Areas

- `stubs/fragments/store-field-relation-buscador.stub` — Uses `firstOrCreate`; should offer a find-or-fail option
- `stubs/fragments/update-field-relation-buscador.stub` — Same issue
- `app/Generator/Rendering/StubRenderer.php` — `buildCreateBuscadorCalls()` / `buildEditBuscadorCalls()` — `onSelect` only sets `item.texto`, not `item.id` for standard FK relations
- `app/Generator/Rendering/StubRenderer.php` — `buildBuscadorCalls()` (show view inline editing) — same issue
- `public/js/buscador.js` — The `onSelect` callback is defined by the generated code, not by buscador.js itself
- `app/Generator/Introspection/ColumnMetadata.php` — No property to distinguish "should create new" vs "must find existing" for buscador inputs
- `app/Generator/Commands/FkInterviewer.php` — No question about whether buscador should allow creating new entities
- Generated controllers (e.g., `app/Http/Controllers/Crud/CobroController.php`) — Runtime behavior at stake

### Approaches

1. **Add hidden ID input + find-or-fail in controller (Recommended)**
   - Add a hidden `<input type="hidden" name="{fk_column}" id="input-create-{fieldId}-id">` for all buscador FK fields (same pattern already used for scoped relations like deudor)
   - In JS `onSelect`, set both the text display AND the hidden ID: `item.id` into hidden, `item.texto` into visible
   - In controller stub, change from `firstOrCreate` to `findOrFail` when hidden ID is present; fall back to `firstOrCreate` only when the text input has value but no hidden ID (i.e., user typed a new entity name)
   - Pros:
     - Fixes the ambiguity problem — selected entities are matched by ID, not text
     - Prevents accidental entity creation from typos
     - Reuses the existing pattern from scoped relations
     - Backward compatible — existing generated code can be regenerated
   - Cons:
     - Requires regenerating all CRUD controllers for existing entities
     - Slightly more complex form (extra hidden input per FK)
   - Effort: Medium

2. **Change firstOrCreate to firstOrFail in stubs**
   - Simply replace `firstOrCreate` with `where(...)->firstOrFail()` in the stubs
   - Pros: Minimal change, prevents accidental creation
   - Cons: Breaks the "add new entity from the buscador" flow; user can't type a new name to create a new entity inline; select-mode "Agregar" flow would break since it relies on `firstOrCreate`
   - Effort: Low

3. **Make firstOrCreate vs findOrFail configurable per relation**
   - Add a `allow_create` boolean to ColumnMetadata / config
   - FkInterviewer asks whether the buscador should allow creating new entities
   - Stub generates `firstOrCreate` or `findOrFail` accordingly
   - Pros: Flexible per-relation control
   - Cons: More complex generator; more questions during `gen:crud`; most relations probably don't want auto-creation
   - Effort: Medium-High

### Recommendation

**Approach 1** — Add hidden ID input + find-or-fail with firstOrCreate fallback.

This is the right fix because:
- The pattern already exists for scoped relations (deudor, acreedor). The hidden input + `onSelect` setting `item.id` is proven in the same codebase.
- It correctly separates "I selected an existing entity" (ID present → findOrFail) from "I'm typing a new entity name" (no ID → firstOrCreate).
- It doesn't break the existing "Agregar nuevo" flow for select-mode fields.

The implementation would touch:
1. `StubRenderer::buildCreateBuscadorCalls()` and `buildEditBuscadorCalls()` — add `item.id` to hidden input in `onSelect`
2. `StubRenderer::buildBuscadorCalls()` (show view) — same
3. `create-field-fk-select.stub` and `create-field-fk-buscador.stub` — add hidden input for the FK column
4. `store-field-relation-buscador.stub` and `update-field-relation-buscador.stub` — prefer ID when available, fall back to `firstOrCreate` for text
5. Validation: when buscador input is present alongside a hidden FK ID, validate the FK ID as `integer|exists:table,id`

### Risks

- **Data integrity regression**: If the hidden ID input is not properly populated (JS fails, user types manually without selecting), `firstOrCreate` will still create entities. Need to ensure the fallback is intentional.
- **display_field = id** (Contrato, Servicio): `firstOrCreate(['id' => ...])` is semantically broken. When display_field is `id`, the buscador should not use `firstOrCreate` at all — it should use `findOrFail`. This needs special handling.
- **Existing generated code**: After the generator is fixed, existing controllers must be regenerated. Any manual edits between `[GEN:START/END]` markers will be preserved, but code outside markers could be overwritten.
- **Buscador JS caching**: If the buscador.js is cached in browsers, the old `onSelect` behavior (text-only) would persist. However, since `onSelect` is inline in the Blade view (not in buscador.js), this is not an issue — regenerating views is sufficient.

### Ready for Proposal
Yes — the problem is well-understood, the pattern exists in the codebase (scoped relations), and the fix is straightforward. The next step should be an SDD proposal covering the stub changes, renderer updates, and validation adjustments.
