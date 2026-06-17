# Design: Terminar contrato front-end preview

## Technical Approach

Keep termination as a server-rendered, front-end-only preview. Ficha controllers will eager-load active contracts with participants and pending cobros; Blade will render a `Terminar contrato` button and hidden modal source per contract; JavaScript inside the shared contract component will manage temporary adjustment rows and CLP totals. No termination route, database write, cobro creation, payment, or accounting side effect is introduced.

## Architecture Decisions

| Decision | Choice | Alternatives considered | Rationale |
|---|---|---|---|
| Modal data | Render contract/participant/pending-cobro data in Blade | Read-only JSON endpoint | Avoids new API surface and fetch/loading requirements for a preview-only flow. |
| Modal host | Reuse Bootstrap `abrirModal()` / `#modalPrincipal` | Native dialogs or bespoke modal shell | Matches app UX and forbids `alert`, `confirm`, `prompt`. |
| Participant display | Use `ParticipanteContrato->cliente` for `arrendador`, `arrendatario`, `corredor` | Treat scoped relations as `Cliente` | `Contrato::arrendador()` returns `ParticipanteContrato`, so links/names must dereference `cliente`. |
| Pending states | Query `pendiente`, `vencido`, `incompleto` and title-case variants | Normalize DB values in this change | Front-end feature must not mutate production data; casing is inconsistent in existing code paths. |

## Data Flow

```text
Ficha route -> controller eager-loads active contracts
            -> Blade card renders summary + hidden modal source
            -> abrirModal() clones selected source into Bootstrap modal
            -> JS initializes preview state, rows, CLP formatting, totals
            -> close modal: no server request, no persistence
```

## File Changes

| File | Action | Description |
|---|---|---|
| `app/Http/Controllers/Vistas/FichaPropiedadController.php` | Modify | In `contratos($id)` and existing ficha active-contract query, eager-load `unidad.propiedad`, `participante_contratos.cliente`, scoped participant relations with `cliente`, and `cobros.participante_cobros.cliente`; filter pending cobros by contract and supported states. |
| `app/Http/Controllers/Vistas/FichaClienteController.php` | Modify | Same eager-loading, plus client-context pending cobros constrained by `participante_cobros.Cliente_id = $id`. |
| `app/Http/Controllers/Crud/ContratoController.php` | Modify | In `show($id)`, eager-load readable relationships only; do not touch generated validation/store/update sections. |
| `resources/views/components/contratos.blade.php` | Modify | Main UI: corrected participant names/links, readable date/CLP display, button, hidden modal template, pending cobro table/empty state, default `Aseo Final`, row add/edit/remove UI, totals. |
| `resources/views/cliente/contratos.blade.php` | Modify | Pass context data such as `clienteContextId` to shared component. |
| `resources/views/propiedad/contratos.blade.php` | Modify | Pass property context to shared component if needed. |
| `resources/views/contrato/show.blade.php` | Modify | Add readable summary/cobros outside `[GEN:START/END]` component table. |

## Interfaces / Contracts

- Pending-state constant should be local/private in controllers or duplicated minimally: `['pendiente','vencido','incompleto','Pendiente','Vencido','Incompleto']`.
- Each modal source stores machine values as `data-*`: `data-garantia`, `data-contrato-id`, and row amount values as integer pesos.
- Adjustment rows have `{type, description, amount, sign}` where charges reduce return and refunds increase return. Totals: `netCharges = charges - refunds`; `returnAmount = garantia - netCharges`.
- Use global `window.formatCLP`, `window.stripCLP`, `window.handleCLPInput` for display and parsing.

## Blade / JS Design

- Render one hidden `div#vista-terminar-contrato-{id}` per contract, then button calls `abrirModal({ titulo: 'Vista previa de término de contrato', vista: 'vista-terminar-contrato-{id}', size: 'modal-xl' })` and initializes after clone.
- Because `abrirModal()` clones nodes, bind modal events with delegated listeners on `#modalPrincipalBody` or initialize after opening; do not rely on listeners attached to hidden source children.
- Default modal copy must say it is a preview and does not terminate the contract. Footer should use `Cerrar`, not submit/terminate wording.
- Inputs recalculate on `input`/`change`; remove buttons delete only temporary rows. Empty pending cobros displays an explicit “no hay cobros pendientes” message.

## Testing Strategy

| Layer | What to Test | Approach |
|---|---|---|
| Feature | Ficha contract pages render action, modal source, participants via `cliente`, pending cobros with state variants/client scope | Laravel feature tests with SQLite fixtures/factories or direct model setup; no destructive migrations. |
| Feature | `contrato.show` has readable custom summary outside generated block | Assert response contains participants, property/unit, guarantee, cobros. |
| Static/contract | No native dialogs; no termination route/write call introduced | Grep assertions or review checklist; existing PHPUnit runner `./vendor/bin/phpunit`. |
| Manual | JS row add/edit/remove, CLP parsing, total recalculation, Bootstrap accessibility | Browser verification on cliente/propiedad profiles; inspect no network request occurs. |

## Migration / Rollout

No migration required. Rollback is reverting Blade/controller changes.

## Risks / Tradeoffs

- Rendering hidden modal markup per contract increases HTML size but avoids fetch complexity.
- Existing pending-cobro casing is inconsistent; include both casings until data normalization is scoped separately.
- Generated `[GEN:START/END]` sections may be overwritten; custom readable contract detail must sit outside those markers.

## Open Questions

- None blocking.
