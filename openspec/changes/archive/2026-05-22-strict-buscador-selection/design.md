# Design: Strict Buscador Selection

## Technical Approach

Enforce selection-only semantics across all buscador FK fields: the API returns `id` in every result, `onSelect` always sets a hidden FK input, validation requires FK ID when text is present, and controller code uses `findOrFail` instead of `firstOrCreate`. Changes span 5 stub templates, `StubRenderer.php` (3 methods), and `buscador.js`.

## Architecture Decisions

| Decision | Option A | Option B | Decision |
|----------|----------|----------|----------|
| Validation coupling | `required` on hidden FK | `required_with:{text_input}` on hidden FK | **B** — buscador is optional; `required_with` only fires when user typed something, allowing empty submissions to pass |
| Entity resolution | `firstOrCreate` by display name | `findOrFail` by FK ID | **B** — `firstOrCreate` creates incomplete records from arbitrary text; `findOrFail` guarantees a real entity, backed by `exists` validation |
| Hidden input scope | Only for scoped (pivot) relations | All buscador FK fields | **B** — direct FK fields (contrato, servicio, propiedad, unidad) had the same bug: no hidden input to carry the selected ID |
| Clear behavior | JS only clears on explicit button | Clear on input empty (backspace) AND button | **B** — users naturally backspace to deselect; both paths must clear the hidden FK to prevent stale submissions |
| Transition for existing files | Auto-migrate generated controllers | Document regeneration requirement | **B** — generated files are disposable; auto-migration is fragile against custom edits. Users re-run the generator after the fix |

## Data Flow

```
User types in buscador input
        │
        ▼
  buscador.js — debounce(200ms) → GET /buscador?q=...&{tipo}=1
        │
        ▼
  BuscadorController → buscador-block.stub
  Returns: [{ id, tipo, texto, url }, ...]
        │
        ▼
  User clicks result → onSelect(item) fires
        │
        ├── input.value = item.texto          (display)
        └── hidden.value = item.id            (FK ID)
        │
        ▼
  Form submits → Laravel validation
        │
        ├── text_input:    sometimes|nullable|string
        └── hidden_fk:     required_with:{text_input}|integer|exists:{table},id
        │
        ▼
  Controller (generated from stubs)
        │
        └── Model::findOrFail($data['fk_column'])  → assigns FK
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `stubs/fragments/buscador-block.stub` | Modify | Add `'id' => $item->id` to both direct and relation result arrays (lines 19-23, 35-39) |
| `stubs/fragments/create-field-fk-buscador.stub` | Modify | Add hidden `<input type="hidden" name="{{fk_column}}" id="input-create-{{field_id}}-id">` after the visible input |
| `stubs/fragments/create-buscador-call.stub` | Modify | Add `document.getElementById('input-create-{{field_id}}-id').value = item.id;` inside `onSelect` |
| `stubs/fragments/store-field-relation-buscador.stub` | Modify | Replace `firstOrCreate` with `findOrFail($data['{{fk_column}}'])` |
| `app/Generator/Rendering/StubRenderer.php:611-643` | Modify | `buildCreateBuscadorCalls()` — remove scoped guard (line 633), always emit hidden input assignment |
| `app/Generator/Rendering/StubRenderer.php:896-951` | Modify | `buildValidationRules()` — change hidden FK rule from `sometimes\|nullable\|integer\|exists` to `required_with:{buscador_input_name}\|integer\|exists` for ALL buscador fields |
| `app/Generator/Rendering/StubRenderer.php:1056-1120` | Modify | `buildPivotStoreFields()` — remove `elseif` branch with `firstOrCreate` fallback |
| `app/Generator/Rendering/StubRenderer.php:1128-1193` | Modify | `buildPivotUpdateFields()` — remove `elseif` branch with `firstOrCreate` fallback |
| `public/js/buscador.js` | Modify | Add hidden-input clearing logic when visible input becomes empty |

## Key Change Details

### buscador-block.stub — Add `id` to result arrays

**BEFORE** (line 19-23):
```php
$found->push([
    'tipo'  => '{{model_snake}}',
    'texto' => $this->resolveDisplay($item, $path, $field),
    'url'   => '/{{route_base}}/' . $item->id,
]);
```

**AFTER**:
```php
$found->push([
    'id'    => $item->id,
    'tipo'  => '{{model_snake}}',
    'texto' => $this->resolveDisplay($item, $path, $field),
    'url'   => '/{{route_base}}/' . $item->id,
]);
```

Same change for the relation branch (lines 35-39).

### create-field-fk-buscador.stub — Add hidden input

**BEFORE**: only the visible buscador input exists.

**AFTER** (insert after line 10, before the list div):
```blade
<input type="hidden"
       name="{{fk_column}}"
       id="input-create-{{field_id}}-id"
       value="{{ old('{{fk_column}}') }}">
```

### create-buscador-call.stub — Always set hidden ID

**BEFORE**:
```js
onSelect: function(item) {
    document.getElementById('input-create-{{field_id}}').value = item.texto;
}
```

**AFTER**:
```js
onSelect: function(item) {
    document.getElementById('input-create-{{field_id}}').value = item.texto;
    document.getElementById('input-create-{{field_id}}-id').value = item.id;
}
```

### StubRenderer::buildCreateBuscadorCalls() — Remove scoped guard

**BEFORE** (lines 632-635):
```php
// Para relaciones scoped con tabla pivote, setear el hidden FK
if ($col->pivotModel !== null && $col->scopedTargetFk !== null) {
    $call .= "            document.getElementById('input-create-{$fieldId}-id').value = item.id;\n";
}
```

**AFTER**: remove the `if` guard, emit the line unconditionally for all buscador fields.

### StubRenderer::buildValidationRules() — Use `required_with`

**BEFORE** (scoped branch, lines 914-919):
```php
$hiddenRules = 'sometimes|nullable|integer|exists:' . $col->referencedTable . ',id';
```

**AFTER** (applied to ALL buscador fields, not just scoped):
```php
$hiddenFkName = $col->pivotModel !== null && $col->scopedTargetFk !== null
    ? $col->relationName . '_' . $col->scopedTargetFk
    : $col->name;
$hiddenRules = 'required_with:' . $buscadorName . '|integer|exists:' . $col->referencedTable . ',id';
```

The `else` branch (line 920-926) that validates `$col->name` for non-scoped FK fields is removed — the hidden input now covers this.

### store-field-relation-buscador.stub — findOrFail

**BEFORE**:
```php
if (isset($data['{{buscador_input_name}}'])) {
    ${{related_var}} = {{RelatedModel}}::firstOrCreate([
        '{{display_field}}' => trim($data['{{buscador_input_name}}'])
    ]);
    ${{model}}->{{fk_column}} = ${{related_var}}->{{related_pk}};
}
```

**AFTER**:
```php
if (!empty($data['{{fk_column}}'])) {
    ${{related_var}} = {{RelatedModel}}::findOrFail($data['{{fk_column}}']);
    ${{model}}->{{fk_column}} = ${{related_var}}->{{related_pk}};
}
```

### StubRenderer::buildPivotStoreFields() — Remove firstOrCreate fallback

**BEFORE** (lines 1091-1098): resolves by hidden FK ID OR falls back to `firstOrCreate` by display name.

**AFTER**: only the `if (!empty($data['{$hiddenFkName}']))` branch remains. The `elseif` with `firstOrCreate` is deleted entirely.

Same pattern for `buildPivotUpdateFields()` (lines 1163-1169).

### buscador.js — Clear hidden input on empty

Add inside the `onInput` handler, after `list.innerHTML = ''` when `q.length < 1` (line 29-31):

**BEFORE**:
```js
if (q.length < 1) {
    list.innerHTML = '';
    s.resultItems = [];
    return;
}
```

**AFTER**:
```js
if (q.length < 1) {
    list.innerHTML = '';
    s.resultItems = [];
    var hiddenId = inputId + '-id';
    var hidden = document.getElementById(hiddenId);
    if (hidden) hidden.value = '';
    return;
}
```

## Interfaces / Contracts

### Buscador API response (modified)
```json
{
  "data": [
    { "id": 5, "tipo": "contrato", "texto": "Contrato 123", "url": "/contratos/5" }
  ]
}
```

The `id` field (integer) is now mandatory in every result item.

### Hidden input naming convention
- Direct FK: `id="input-create-{column}-id"`, `name="{column}"`
- Scoped relation: `id="input-create-{relationName}-id"`, `name="{relationName}_{scopedTargetFk}"`

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit — StubRenderer | `buildCreateBuscadorCalls()` emits hidden input for direct FK and scoped | PHPUnit: assert output contains `getElementById('input-create-contrato_id-id')` |
| Unit — StubRenderer | `buildValidationRules()` generates `required_with` for buscador FK | PHPUnit: assert rule string contains `required_with:nombre-contrato` |
| Unit — StubRenderer | `buildPivotStoreFields()` has no `firstOrCreate` | PHPUnit: assert output does NOT contain `firstOrCreate` |
| Unit — StubRenderer | `buildPivotUpdateFields()` has no `firstOrCreate` | PHPUnit: assert output does NOT contain `firstOrCreate` |
| Unit — StubRenderer | `store-field-relation-buscador.stub` uses `findOrFail` | PHPUnit: render stub and assert `findOrFail` present, `firstOrCreate` absent |
| Integration | Submit buscador text without FK ID → 422 | Feature test: POST with text field set, FK empty, assert validation error |
| Integration | Submit buscador text with valid FK ID → 200 | Feature test: POST with both fields, assert record created |
| Integration | Submit empty buscador → 200 (optional field) | Feature test: POST with neither field, assert success |
| JS | Clearing visible input clears hidden | Browser test or Playwright: type, select, backspace, assert hidden.value === '' |

## Migration / Rollout

No migration required. Existing generated controllers/views continue working with `firstOrCreate` until manually regenerated. After this change is merged, users must re-run the CRUD generator to get the fixed output. Document this in the changelog.

## Open Questions

- [ ] Should `buscador.js` also clear the hidden input on the `Escape` key handler? Currently Escape only closes the dropdown. If the user pressed Escape after typing but before selecting, the visible input still has text but no selection was made — this could submit stale text. Decision: **defer** — the validation layer catches this case (text without FK ID → 422), so no immediate risk.
