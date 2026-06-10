# Design: Ficha Cobro — Context-Aware Modal Creation

## Technical Approach

Reuse the shared `cobro/modal/create.blade.php` with an explicit `$fichaContext` flag. Ficha controllers compute `$participantOptions` from active contracts and pass them to the view. The modal branches on `$fichaContext`: hides `fecha_cobro`/`estado`, restricts `tipo` to manual types, applies CLP formatting on `monto`, and populates `deudor`/`acreedor` selects from contract participants only. Server defaults for hidden fields and strengthened validation in `CobroController::store()` enforce the new requirements.

## Architecture Decisions

| Decision | Choice | Alternatives | Rationale |
|----------|--------|-------------|-----------|
| Ficha detection | `$fichaContext` bool passed to view | URL sniffing, JS flag | Minimal blast radius; generic CRUD paths completely untouched |
| Participant source | Controllers derive from `$contratosVigentes` | Resolver returns participants | Already loaded in ficha controllers; avoids N+1 on resolver response |
| Hidden field defaults | `$request->merge()` before validation | Separate validation paths | Single code path; safe defaults (now, Pendiente) are benign for generic create |
| CLP formatting | Existing `handleCLPInput` + `stripCLP` on submit | Custom formatting | Reuses tested utilities in `app.js`; no new JS surface |
| deudor/acreedor required | Always required server-side | Conditional on ficha flag | Both contexts render these selects; universal required is safer |

## Data Flow

```
Ficha page → "Agregar cobro"
  │
  ▼
Modal opens (fichaContext=true)
  ├── fecha_cobro/estado: not rendered
  ├── tipo: only Reparación/Extra/Devolución
  ├── monto: text + handleCLPInput, required
  ├── detalle: text, required
  ├── Propiedad_id: hidden (locked) or select (active contracts)
  ├── deudor/acreedor: <select> from $participantOptions, required
  │
  ▼
User selects tipo → resolveCobroRelationships()
  │
  ▼
POST /api/cobro/resolve-relationships → CobroRelationshipResolver
  │  resolveManualTipo() returns participants[]
  ▼
JS populates hidden fields + participant selects
  │
  ▼
User types monto ($xxx.xxx) + detalle → submits
  │  submit handler: stripCLP(monto)
  ▼
POST /cobro
  └── fecha_cobro: omitted → server defaults to now()
  └── estado: omitted → server defaults to Pendiente
  └── monto: "150000" (stripped)
  └── detalle, deudor_Cliente_id, acreedor_Cliente_id
  │
  ▼
CobroController::store()
  1. $request->merge(Defaults)
  2. Validate: monto required|integer, detalle required|string,
     deudor/acreedor required|integer|exists
  3. Create Cobro + ParticipanteCobro records
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Http/Controllers/Vistas/FichaClienteController.php` | Modify | Compute `$participantOptions` from `$contratosVigentes`→participante_contratos→unique clientes; pass to view + compact |
| `app/Http/Controllers/Vistas/FichaPropiedadController.php` | Modify | Same computation from `$contratosVigentes`; pass to view |
| `resources/views/components/pendientes.blade.php` | Modify | Pass `fichaContext=true`, `participantOptions`, `propiedadOptions` derived from active contracts only |
| `resources/views/components/pendientes-propiedad.blade.php` | Modify | Pass `fichaContext=true`, `participantOptions` from property contracts; lock `propiedadOptions` to single property |
| `resources/views/cobro/modal/create.blade.php` | Modify | Branch on `$fichaContext`: hide fecha/estado; filter tipos; CLP on monto; deudor/acreedor from `$participantOptions`; submit handler strips CLP + sends `_ficha_context` |
| `app/Http/Controllers/Crud/CobroController.php` | Modify | `$request->merge()` defaults; require monto/detalle; require deudor/acreedor |
| `app/Services/CobroRelationshipResolver.php` | Modify | `resolveManualTipo()` returns `participants[]` array with contract participant data for JS select rebuild |

## Interfaces / Contracts

### Resolver `participants` response shape (addition to existing response)

```php
// New key in resolveManualTipo() return data:
'participants' => [
    ['id' => 5, 'nombre' => 'Juan Pérez', 'rol' => 'Arrendador'],
    ['id' => 12, 'nombre' => 'María López', 'rol' => 'Arrendatario'],
],
```

JS `resolveCobroRelationships()` reads `result.data.participants` to rebuild deudor/acreedor select options in ficha context.

### `_ficha_context` hidden input

```html
<input type="hidden" name="_ficha_context" value="1">
```

Sent only from ficha modal submits. `CobroController::store()` uses it to enforce deudor/acreedor `required` (optional in generic context where the same fields are already present from rendered selects).

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | `CobroRelationshipResolver::resolveManualTipo()` returns `participants` | Mock active contracts, assert participants array contains contract clienteles |
| Unit | `CobroController::store()` merges defaults for missing fields | POST without fecha_cobro/estado, assert model created with now() + Pendiente |
| Feature | Ficha modal renders with contract-only participant options | `$this->get()` ficha page, assert HTML contains expected `<option>` values in deudor/acreedor selects |
| Feature | Store rejects empty monto/detalle (422) | POST with null monto, assert `validate` errors |
| Feature | Store rejects missing deudor (422) in ficha | POST with `_ficha_context=1`, null deudor, assert validation error |

## Migration / Rollout

No migration required. Existing cobros retain their `fecha_cobro` and `estado` values. New records from ficha context receive `now()`/`Pendiente` defaults.

## Open Questions

- [ ] `$participantOptions` should exclude the ficha's own cliente (the current user) from deudor/acreedor options? Spec says "contract participants" — the current cliente IS a contract participant, so included by default. Confirm during review.
- [ ] For generic (non-ficha) cobro create, `monto` and `detalle` become `required` too — is there any generic flow that submits without them?
