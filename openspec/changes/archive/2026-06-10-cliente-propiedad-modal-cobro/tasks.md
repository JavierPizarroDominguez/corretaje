# Tasks: Ficha Cobro — Context-Aware Modal

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 300–400 |
| 400-line budget risk | Medium |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |

Decision needed before apply: Yes
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Medium

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | All ficha modal changes + tests | Single PR | Self-contained; 7 files + test coverage |

## Phase 1: RED — Write failing tests

- [x] 1.1 Unit test: `CobroRelationshipResolverTest` asserts `resolveManualTipo()` returns `participants` array with contract participant `{id, nombre, rol}`
- [x] 1.2 Feature test: ficha modal GET renders `deudor`/`acreedor` `<select>` with "Seleccione" placeholder and contract-participant options
- [x] 1.3 Feature test: `POST /cobro` with `_ficha_context=1` rejects empty `monto` (302 + session errors)
- [x] 1.4 Feature test: `POST /cobro` with `_ficha_context=1` rejects empty `detalle` (302 + session errors)
- [x] 1.5 Feature test: `POST /cobro` with `_ficha_context=1` rejects missing `deudor_Cliente_id` (302 + session errors)
- [x] 1.6 Feature test: `POST /cobro` omitting `fecha_cobro`/`estado` creates record with `now()` and `Pendiente`

## Phase 2: GREEN — Backend services & controller

- [x] 2.1 `CobroRelationshipResolver::resolveManualTipo()`: add `participants` key — return array from contract `participante_contratos` with unique clientes
- [x] 2.2 `CobroController::store()`: `$request->merge(['fecha_cobro' => now(), 'estado' => 'Pendiente'])`; enforce `monto` `required|integer`, `detalle` `required|string`, `deudor_Cliente_id`/`acreedor_Cliente_id` `required|integer|exists:clientes,id`

## Phase 3: GREEN — Controllers & views

- [x] 3.1 `FichaClienteController`: derive `$participantOptions` from `$contratosVigentes`→`participante_contratos`→unique `cliente` records; pass to view
- [x] 3.2 `FichaPropiedadController`: same derivation from property's active contracts; pass to view
- [x] 3.3 `pendientes.blade.php`: pass `fichaContext=true`, `participantOptions`, `propiedadOptions` (active-contract properties only) to modal include
- [x] 3.4 `pendientes-propiedad.blade.php`: pass `fichaContext=true`, `participantOptions`; lock `Propiedad_id` to current property as hidden input
- [x] 3.5 `cobro/modal/create.blade.php`: branch on `$fichaContext` — hide `fecha_cobro`/`estado`; restrict `tipo` to `Reparación`/`Devolución`/`Extra`; `handleCLPInput` on `monto`; render `deudor`/`acreedor` as `<select required>` from `$participantOptions` with `"Seleccione"` placeholder; submit handler calls `stripCLP(monto)` before POST

## Phase 4: REFACTOR — Convention compliance

- [x] 4.1 Verify all `fetch()` calls in modal use `showElLoading(modalBody)` / `hideElLoading(modalBody)` — pre-existing and preserved
- [x] 4.2 Verify all error paths use `flashModal` — no `alert()`/`confirm()`/`prompt()` — `showCobroError` helper added
- [x] 4.3 Verify `_ficha_context` hidden input sent on ficha submit for server-side enforcement
