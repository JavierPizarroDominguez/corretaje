# Proposal: Ficha Cobro — Context-Aware Modal

## Intent

Modify the shared cobro create modal so it works contextually from cliente and propiedad detail pages. `deudor` and `acreedor` become mandatory `<select>` elements constrained to active-contract participants. Server-side validation enforces required `monto`/`detalle` and supplies safe defaults for hidden fields (`fecha_cobro`, `estado`).

## Scope

### In Scope
- Context-aware cobro modal for both cliente and propiedad ficha pages
- `deudor`/`acreedor` as required `<select>` with "Seleccione" placeholder, options from contract participants only
- Hidden `fecha_cobro` (default: now) and `estado` (default: Pendiente) with backend-safe fallbacks
- CLP formatting on `monto` via existing `handleCLPInput`
- Server validation: required `monto`/`detalle`, required `deudor`/`acreedor`, property/contract integrity
- `CobroController::store()` hardening — fallback defaults before validation reads

### Out of Scope
- Dedicated ficha-only API endpoint (Approach 3 from exploration)
- Separate ficha-only modal partial (Approach 2)
- Payment/transaccion flows (handled by `cobro-payment` spec)
- Generic (non-ficha) cobro create — unaffected

## Capabilities

### New Capabilities
- `ficha-cobro-create`: Create cobros from cliente/propiedad detail pages with context-aware participant and property selection restricted to active contracts.

### Modified Capabilities
- None. This is a new workflow, not a change to existing spec behavior.

## Approach

**Approach 1** from exploration: context-aware reuse of existing shared modal. Pass context from ficha views (`locked_property_id`, `available_properties`, `current_contracts`, `allowed_participants`). Restrict `deudor`/`acreedor` options to contract participants via `CobroRelationshipResolver`. Hide date/status with backend-safe defaults in store(). Reuse existing CLP utilities and loading-indicator conventions.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `resources/views/cobro/modal/create.blade.php` | Modified | Branch ficha vs generic; deudor/acreedor as `<select>` with contract participants |
| `resources/views/components/pendientes.blade.php` | Modified | Pass ficha context (active contracts, participants) to modal |
| `resources/views/components/pendientes-propiedad.blade.php` | Modified | Lock property; pass contract participants |
| `app/Http/Controllers/Vistas/FichaClienteController.php` | Modified | Provide active-contract participant data |
| `app/Http/Controllers/Vistas/FichaPropiedadController.php` | Modified | Provide participant data for property's active contracts |
| `app/Services/CobroRelationshipResolver.php` | Modified | Resolve participants from contract roles for manual types |
| `app/Http/Controllers/Crud/CobroController.php` | Modified | Enforce required monto/detalle; safe defaults for hidden fields |
| `config/cobro_roles.php` | Possibly modified | Define participant constraints for manual types |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Hidden date/status missing backend defaults | High | Add fallback defaults in store() before validation |
| CLP formatting submitted raw breaks validation | Medium | Strip via `stripCLP()` in form submit handler |
| Client ficha shows all properties, not only active-contract ones | Medium | Derive options from `$contratosVigentes` only |
| Generic cobro create broken by ficha branching | Low | Use explicit `isFicha` flag; generic path unchanged |

## Rollback Plan

Revert changes to the eight files above. Generic cobro create resumes without regression. If store() validation was relaxed for ficha, restore original rules.

## Dependencies

- `formatCLP`/`stripCLP`/`handleCLPInput` — existing (clp-input-format spec, no changes)
- `showElLoading`/`hideElLoading` convention — existing (ui-loading-indicators spec, no changes)

## Success Criteria

- [ ] Cliente detail "Agregar Cobro" shows `deudor`/`acreedor` as `<select>` with "Seleccione" first option, options from active-contract participants, fields required
- [ ] Propiedad detail "Agregar Cobro" locks to current property, same participant behavior
- [ ] Store() rejects missing `monto`/`detalle`/`deudor`/`acreedor` (422)
- [ ] Store() defaults `fecha_cobro=now` and `estado=Pendiente` when hidden fields omitted
- [ ] All `fetch()` wrapped in `showElLoading`/`hideElLoading`
- [ ] All errors via flashModal, never `alert()`/`confirm()`
