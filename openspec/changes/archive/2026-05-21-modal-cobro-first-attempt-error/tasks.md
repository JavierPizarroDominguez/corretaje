# Tasks: Modal cobro first-attempt error

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~50-70 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

## Phase 1: BuscadorController — Add `id` to existing responses

- [x] 1.1 Add `'id' => $item->id` to unidad result array in `BuscadorController::index()` (line ~27-31)
- [x] 1.2 Add `'id' => $item->id` to cliente result array in `BuscadorController::index()` (line ~41-45)

## Phase 2: BuscadorController — Add contrato, servicio, propiedad handlers

- [x] 2.1 Add `contrato` handler: query `Contrato::where('id', 'LIKE', "%{$q}%")` with `id`, `tipo`, `texto`, `url` response format
- [x] 2.2 Add `servicio` handler: query `Servicio::where('tipo', 'LIKE', "%{$q}%")` with `id`, `tipo`, `texto`, `url` response format
- [x] 2.3 Add `propiedad` handler: query `Propiedad::where('direccion', 'LIKE', "%{$q}%")` with `id`, `tipo`, `texto`, `url` response format

## Phase 3: Blade template — Fix onSelect callbacks

- [x] 3.1 Add `document.getElementById('input-create-contrato-id').value = item.id;` to contrato onSelect callback
- [x] 3.2 Add `document.getElementById('input-create-servicio-id').value = item.id;` to servicio onSelect callback
- [x] 3.3 Add `document.getElementById('input-create-propiedad-id').value = item.id;` to propiedad onSelect callback
- [x] 3.4 Add `document.getElementById('input-create-unidad-id').value = item.id;` to unidad onSelect callback

## Phase 4: Testing

- [ ] 4.1 Run existing `BuscadorScopedRelationsTest` — verify no regressions
- [ ] 4.2 Verify BuscadorController returns `id` field for all 5 entity types (manual or feature test)
- [ ] 4.3 Verify cobro modal create form submits successfully on first attempt with selected buscador values
