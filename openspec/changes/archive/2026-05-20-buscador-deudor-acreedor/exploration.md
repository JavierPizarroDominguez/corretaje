## Exploration: Buscador doesn't work for Deudor and Acreedor in Cobro modal create

### Current State

The system has a custom generator that creates CRUD views, controllers, and models from database schema introspection + config (`config/generator.php`). It generates two kinds of "create" views:

1. **Full-page create** (`resources/views/cobro/create.blade.php`) — rendered via `renderCreateView()`, includes **both** HTML form fields AND `buscador()` JS initialization calls inside `@push('scripts')`
2. **Modal create** (`resources/views/cobro/modal/create.blade.php`) — rendered via `renderModalCreate()`, includes **only** HTML form fields, **no** JS buscador initialization

The `buscador()` JS function (in `public/js/buscador.js`) is a global event-driven autocomplete. It listens for input events on a specific input element, fetches results from `/buscador?q=...`, and shows a dropdown. On select, it calls the `onSelect` callback. It uses document-level event delegation (input, keydown, click) rather than attaching directly to elements, so it works even with dynamically loaded content (AJAX modals) — **as long as `buscador()` is called to register the config**.

For the **Cobro** entity, both `deudor` and `acreedor` are auto-detected scoped relations (hasOne + where('rol', 'Deudor'|'Acreedor')) on the `participante_cobro` pivot table. They are not direct FK columns on the `cobro` table.

### Bug Analysis

#### Bug 1 (PRIMARY): Missing buscador JS initialization in modal create view

The generator method `StubRenderer::renderModalCreate()` (line 235) calls `buildCreateFormFields()` to generate the HTML fields but **never calls `buildCreateBuscadorCalls()`** to generate the corresponding JS `buscador()` calls.

```php
// renderModalCreate() — lines 235-243
public function renderModalCreate(TableSchema $schema): string
{
    $stub          = $this->loadStub('modal-create.stub');
    $simpleFragment = $this->loadStub('fragments/create-field.stub');
    $fields        = $this->buildCreateFormFields($schema, $simpleFragment);
    $stub = str_replace('{{create_fields}}', implode("\n", $fields), $stub);
    $stub = $this->replaceGlobal($stub, $schema);
    return $stub;
    // ^^^ NO buildCreateBuscadorCalls()!
}
```

Compare with `renderCreateView()`:
```php
// renderCreateView() — lines 362-375
public function renderCreateView(TableSchema $schema): string
{
    $stub          = $this->loadStub('view-create.stub');
    $simpleFragment  = $this->loadStub('fragments/create-field.stub');
    $fields          = $this->buildCreateFormFields($schema, $simpleFragment);
    $buscadorCalls   = $this->buildCreateBuscadorCalls($schema);  // ← THIS IS MISSING
    ...
}
```

Additionally, the `modal-create.stub` template has **no placeholder** for buscador calls (no `{{create_buscador_calls}}` or `{{buscador_calls}}`), while `view-create.stub` does.

**Impact**: No buscador works in the modal create view — not just deudor/acreedor, but ALL FK fields (contrato, servicio, propiedad, unidad). The grep confirms zero `buscador()` calls in `cobro/modal/create.blade.php`.

#### Bug 2: Colliding input names for deudor and acreedor

The `buscadorInputName()` method generates input names using the convention `{display_field}-{referenced_table}`:

```php
private function buscadorInputName(ColumnMetadata $col): string
{
    $sqlField        = $this->displayFieldToSqlColumn($col->relationDisplayField ?? 'id');
    $referencedTable = $col->referencedTable ?? $col->relatedRoute ?? 'rel';
    return "{$sqlField}-{$referencedTable}";
}
```

For scoped relations (auto-detected in `buildScopedColumn()`), both `deudor` and `acreedor` share:
- `relationDisplayField` = 'nombre' (hardcoded default)
- `referencedTable` = 'participante_cobro' (same table)
- Result: BOTH get `name="nombre-participante_cobro"`

This is confirmed in the blade view — lines 215 and 249:
```html
<input id="input-create-deudor" name="nombre-participante_cobro" ...>
<input id="input-create-acreedor" name="nombre-participante_cobro" ...>
```

**Impact**: When the form is submitted with DIFFERENT values in each field, PHP overwrites the first value with the second (since both have the same name). Only the second field's value survives.

#### Bug 3: Controller store logic broken for duplicate input names

In `CobroController::store()` (lines 121-132):
```php
if (isset($data['nombre-participante_cobro'])) {
    // ... sets $cobro->deudor = $participanteCobro->id
}
if (isset($data['nombre-participante_cobro'])) {
    // ... sets $cobro->acreedor = $participanteCobro->id  // OVERWRITES deudor!
}
```

Both blocks read from the **same** `$data['nombre-participante_cobro']`, so both deudor and acreedor get the same value (or the second block runs with the last-submitted value).

Also, the `scoped_store_fields` block (lines 136-159) has a syntax error: `${cobro}->id` is invalid PHP variable interpolation inside double-quoted string. It should be `$cobro->id`.

### Affected Areas

- **`app/Generator/Rendering/StubRenderer.php`** — `renderModalCreate()` missing `buildCreateBuscadorCalls()`; `buscadorInputName()` uses convention that collides for same-table FKs
- **`stubs/modal-create.stub`** — Missing placeholder for buscador JS calls (`{{buscador_calls}}` or `{{create_buscador_calls}}`)
- **`resources/views/cobro/modal/create.blade.php`** — Generated view with no buscador init AND colliding input names
- **`app/Http/Controllers/Crud/CobroController.php`** — Store logic broken: both deudor/acreedor read same input, syntax error in scoped_store_fields
- **`resources/views/cobro/create.blade.php`** — Reference: working version with correct buscador init (for comparison)
- **`resources/views/cobro/edit.blade.php`** — Same colliding input name issue persists
- **`resources/views/cobro/modal/show.blade.php`** — Same colliding input name issue persists

### Approaches

1. **Fix both generator and generated views** — Add buscador calls to `renderModalCreate()`, fix input name convention to use `relationName` instead of (or in addition to) `referencedTable`, and regenerate the cobro views/controller.

   - Pros: Fixes root cause for all current and future entities; clean, systematic solution
   - Cons: Regeneration of existing views could have side effects; the input name change is a breaking change for existing forms
   - Effort: High

2. **Fix only the generated cobro view files (manual patch)** — Manually add `buscador()` calls to `cobro/modal/create.blade.php`, change input names to `nombre-deudor` and `nombre-acreedor`, and fix the controller store logic.

   - Pros: Fast, targeted fix; no risk of breaking other generated entities
   - Cons: Fix will be overwritten if views are regenerated; doesn't fix the generator for future entities
   - Effort: Low

3. **Fix the generator's modal render + input name convention** — Fix `renderModalCreate()` to include buscador calls, add placeholder to stub, and change `buscadorInputName()` to use `relationName` (e.g., `nombre-deudor`, `nombre-acreedor`) when the relation has a unique name. Then regenerate only cobro files.

   - Pros: Fixes root cause for generator AND current entity; preserves regeneration capability
   - Cons: Medium effort; need to handle migration for existing forms
   - Effort: Medium

### Recommendation

**Approach 3** — fix the generator properly. The two issues (missing buscador calls in modal create + colliding input names) are both generator defects that will affect other entities if they have multiple FKs to the same table. The fix is:

1. In `StubRenderer::renderModalCreate()`: call `buildCreateBuscadorCalls()` and add the result to the stub with a new placeholder
2. In `modal-create.stub`: add a `{{create_buscador_calls}}` placeholder (or wrap it in a section that the including view can push)
3. In `buscadorInputName()`: when the column has a `relationName` that differs from the auto-generated name, use `{display_field}-{relationName}` instead of `{display_field}-{referenced_table}`. This way deudor gets `nombre-deudor` and acreedor gets `nombre-acreedor`.
4. Regenerate cobro files (or manually apply fixes to existing files)

For the modal create specifically, since it's loaded via AJAX, the `buscador()` calls should be placed inline in a `<script>` tag within the modal content (not using `@push('scripts')` which won't work with AJAX).

### Risks

- Input name change is a **breaking change** — existing form submissions with `nombre-participante_cobro` will fail validation. Need to handle old input name in controller or deploy as part of a coordinated update.
- Modal create is loaded via AJAX — `@push('scripts')` won't work, so buscador calls must be inline `<script>` tags
- Other entities may have the same pattern (multiple FKs to same table) — need to audit
- Regenerating cobro views will overwrite any manual fixes — need a strategy for this

### Ready for Proposal
Yes — the root cause is well understood. The orchestrator should proceed with `sdd-propose` to define the fix scope, rollback plan, and delivery strategy. Key decision needed: whether to rename input names (breaking change) or use a dual-name approach (support both old and new names during transition).
