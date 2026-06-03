# Proposal: Modal cobro first-attempt error

## Intent

Fix the 422 validation error when creating a cobro from the modal on first attempt. The BuscadorController API response omits the `id` field, so JS `onSelect` callbacks set hidden inputs to `"undefined"`, failing `integer|exists:cliente,id` validation. Two secondary bugs compound this: missing `onSelect` hidden-input assignments for contrato/servicio/propiedad/unidad, and the controller only handles `unidad` + `cliente` — contrato, servicio, propiedad return empty results.

## Scope

### In Scope
- Add `id` field to BuscadorController API response for all entity types
- Fix all 6 `onSelect` callbacks in `cobro/modal/create.blade.php` to set hidden input IDs
- Add contrato, servicio, propiedad handlers to BuscadorController

### Out of Scope
- Buscador JS component rewrite (`public/js/buscador.js`)
- Other modal forms beyond cobro create
- UI/UX changes to the buscador dropdown

## Capabilities

### New Capabilities
None

### Modified Capabilities
- `buscador`: API response MUST include `id` field; controller MUST handle all entity types (contrato, servicio, propiedad, unidad, cliente); `onSelect` callbacks MUST set hidden input values for all FK fields

## Approach

1. **BuscadorController**: Add `'id' => $item->id` to each result array. Add three new query blocks for `contrato` (search by id), `servicio` (search by tipo), and `propiedad` (search by direccion).
2. **Blade template**: Add `document.getElementById('input-create-{entity}-id').value = item.id;` to the 4 `onSelect` callbacks currently missing it (contrato, servicio, propiedad, unidad).

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Http/Controllers/BuscadorController.php` | Modified | Add `id` to response; add contrato/servicio/propiedad handlers |
| `resources/views/cobro/modal/create.blade.php` | Modified | Fix 4 onSelect callbacks to set hidden input IDs |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Contrato search by `id` is numeric — LIKE may match too broadly | Low | Limit to 10 results; exact match surfaced first by DB order |
| Other blade templates have same broken onSelect pattern | Med | Fix cobro now; audit other templates as follow-up |

## Rollback Plan

Revert the two files: `BuscadorController.php` and `cobro/modal/create.blade.php`. No schema migrations involved — pure code revert.

## Dependencies

None

## Success Criteria

- [ ] Selecting a deudor/acreedor in cobro modal sets the hidden input to a valid integer ID
- [ ] Creating a cobro via modal succeeds on first attempt (no 422)
- [ ] Selecting contrato/servicio/propiedad/unidad in cobro modal sets the hidden input to a valid integer ID
- [ ] Buscador returns results for all 5 entity types
