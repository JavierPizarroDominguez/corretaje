## Exploration: Cliente/propiedad modal cobro

### Current State
The same `cobro.modal.create` partial is reused from both ficha contexts. It currently renders visible `fecha_cobro`, `estado`, `tipo`, `monto`, `detalle`, `Propiedad_id`, deudor and acreedor controls, then uses `resolveCobroRelationships()` to infer `Contrato_id`, `Unidad_id`, `Servicio_id`, deudor and acreedor from `cliente_id`, `tipo`, and optional `propiedad_id`.

Property ficha already passes a single current property via `components.pendientes-propiedad`, including `modal-propiedad-id`, so the modal can be locked to that property. Client ficha builds property options from owned properties plus contract properties, but it does not distinguish exactly one active contract versus multiple active contracts at the modal UI level.

Important backend constraint: `CobroController::store()` validates `fecha_cobro` and `estado` as `sometimes|required`, but then unconditionally reads `$data['fecha_cobro']` and `$data['estado']`. If the fields are merely removed/disabled, store will fail; hidden defaults or backend defaults are required. `monto` and `detalle` are currently nullable, so making them mandatory needs server-side validation too, not only HTML `required`.

`public/assets/js/app.js` already exposes `formatCLP`, `stripCLP`, and `handleCLPInput`, matching the requested `$xxx.xxx` typing behavior. Any fetch work must keep using `showElLoading`/`hideElLoading`; the current relationship fetch already does so.

### Affected Areas
- `resources/views/cobro/modal/create.blade.php` — main modal fields, type options, CLP amount behavior, hidden defaults, participant/property select behavior, relationship fetch handling.
- `resources/views/components/pendientes.blade.php` — client ficha context decides whether to pass one property or multiple active-contract properties into the modal.
- `resources/views/components/pendientes-propiedad.blade.php` — property ficha context already has a single property; needs explicit locked-context behavior.
- `app/Http/Controllers/Vistas/FichaClienteController.php` — already computes `$contratosVigentes`; should provide active-contract-derived property/participant context for the modal.
- `app/Http/Controllers/Vistas/FichaPropiedadController.php` — already computes property active contracts; should provide participant context for selected/current contract.
- `app/Services/CobroRelationshipResolver.php` — resolves active contracts and options, but manual types (`Reparación`, `Extra`, `Devolución`) currently do not receive a role map, so participants are not auto-filled from contract roles.
- `app/Http/Controllers/Crud/CobroController.php` — must enforce required `monto`/`detalle`, default current date and `Pendiente` safely, and protect deudor/acreedor from arbitrary clients.
- `config/cobro_roles.php` — manual types are mapped with null roles today; if participants must be contract-only, the design must define defaults or require explicit participant selection from contract participants.
- `public/assets/js/app.js` — reusable CLP utilities already exist; likely no change needed.
- `tests/Feature` / `tests/Unit` — add coverage around modal creation/store rules and relationship resolution; existing tests cover pending APIs but not this modal contract.

### Approaches
1. **Context-aware reuse of existing modal** — Keep one `cobro.modal.create`, but pass a small context from ficha views: `locked_property_id`, `available_properties`, `current_contracts`, and `allowed_participants`.
   - Pros: lowest duplication, keeps current relationship resolver and loading convention, aligns with existing shared modal pattern.
   - Cons: the partial is already dense and inline-JS-heavy; requires careful branching to avoid breaking generic cobro create flows.
   - Effort: Medium

2. **Dedicated ficha-only cobro modal** — Create a separate modal partial for client/property fichas with only the requested fields and context behavior.
   - Pros: simpler UI contract for this specific workflow, avoids risking generated CRUD create behavior.
   - Cons: duplicates store/relationship assumptions; future cobro modal fixes may need to be applied twice.
   - Effort: Medium

3. **Backend-first endpoint for ficha cobros** — Add a dedicated create action/API that receives only `{context, tipo, monto, detalle, property/contract choice, participants}` and applies all defaults/server constraints.
   - Pros: strongest domain safety; avoids relying on hidden fields for date/status/contract defaults.
   - Cons: larger scope; more route/controller/test work than the requested UI polish likely needs.
   - Effort: High

### Recommendation
Use **Approach 1** with strict server-side safeguards. Keep the shared modal, but make ficha contexts explicit: hidden `fecha_cobro=now` and `estado=Pendiente`, visible type options restricted to `Reparación`, `Devolución`, and `Extra`, amount as `type="text" inputmode="numeric"` using `handleCLPInput`, and required `monto`/`detalle` validation in `CobroController::store()`.

For property ficha, always submit the current property through a hidden `Propiedad_id`. For client ficha, derive active contracts from `$contratosVigentes`: if exactly one, lock to its property; if more than one, show a select containing only those active-contract properties. In either case, deudor and acreedor selects should be rebuilt from the selected/current contract participants only.

Before implementation, the proposal/spec should settle one domain detail: for manual cobro types, does the system preselect deudor/acreedor by default, or only constrain the select options to contract participants and require the user to choose? The user only said they “can only be participants”, not which participant should default.

### Risks
- Hiding date/status without backend defaults will break `CobroController::store()` because it currently reads those keys unconditionally.
- Client ficha property selection must be based on active contracts, not all `$cliente->propiedades`, or it will violate the “contrato vigente” rule.
- Existing resolver treats manual types as null-role types; it may not produce participant defaults without a contract-specific manual-type path.
- The amount field must strip CLP formatting before submit, or integer validation will reject values like `$123.456`.
- The current deudor/acreedor options include all `$clienteOptions` plus Corredor; leaving that unchanged violates the contract-participant restriction.

### Ready for Proposal
Yes — ready for `sdd-propose`. The proposal should scope this as a ficha modal behavior change with server validation hardening, and should explicitly ask/record the defaulting rule for deudor/acreedor on manual cobro types if the product owner wants automatic defaults instead of required constrained selection.
