## Exploration: Terminar contrato front-end

### Current State

Contract lists are exposed from ficha pages through custom routes:

- `GET /propiedad/{id}/contratos` → `FichaPropiedadController::contratos()` → `resources/views/propiedad/contratos.blade.php`
- `GET /cliente/{id}/contratos` → `FichaClienteController::contratos()` → `resources/views/cliente/contratos.blade.php`
- Both pages include `resources/views/components/contratos.blade.php` with `$contratosVigentes`.

`Contrato` already has the fields needed for the review modal (`garantia`, `fecha_inicio`, `fecha_termino`) and has many `cobros`. `Cobro` exposes `estado`, `tipo`, `monto`, `detalle`, `fecha_cobro`, and participants through `participante_cobros`. Pending charge logic already exists in ficha controllers and APIs using states `pendiente`, `vencido`, `incompleto` / `Pendiente`, `Vencido`, `Incompleto`, but the contract-list actions do not currently load pending cobros per contract.

The reusable UI modal pattern is `abrirModal()` in `resources/views/layouts/app.blade.php`, with hidden source markup cloned into `#modalPrincipal`. User messages use `flashModal`. Fetch-based views must wrap requests with `showElLoading()` / `hideElLoading()`; a front-end-only calculator can avoid fetch entirely if all data is rendered into the page.

The current contract view is incomplete/broken at a high level: `components.contratos` treats `arrendador` / `arrendatario` as if they were `Cliente` records, but `Contrato::arrendador()` and `Contrato::arrendatario()` return `ParticipanteContrato`, so links/names should go through `->cliente`. The generated `contrato.show` page also shows raw generated editable fields only, does not present participants/cobros clearly, and the controller does not eager-load the relationships needed for a complete readable detail.

### Affected Areas

- `routes/web.php` — custom ficha routes for cliente/propiedad contract lists already exist; no termination route should be added yet unless later phases need read-only data.
- `routes/generated.php` — generated CRUD route `contrato.show` exists; avoid editing generated route blocks.
- `app/Http/Controllers/Vistas/FichaPropiedadController.php` — `contratos($id)` loads active contracts for a property; likely needs eager-loading of `cobros.participante_cobros.cliente`, participants, and maybe services/property/unit for read-only modal data.
- `app/Http/Controllers/Vistas/FichaClienteController.php` — `contratos($id)` loads active contracts for a client; same read-only data issue, scoped to client participation.
- `app/Http/Controllers/Crud/ContratoController.php` — `show($id)` currently loads `Contrato::findOrFail($id)` plus FK options; likely needs relationship eager-loading or a cleaned-up Blade detail if fixing the contract detail page is in scope.
- `app/Models/Contrato.php` — relevant relationships: `unidad`, `cobros`, `participante_contratos`, `arrendador`, `arrendatario`, `corredor`; note scoped participant relations return pivot models.
- `app/Models/Cobro.php` / `app/Models/ParticipanteCobro.php` — source for pending charges and modal rows.
- `resources/views/components/contratos.blade.php` — main reusable card list; best place for the “Terminar contrato” button, modal source markup, correct participant display, warranty/date display, and read-only pending-cobro list.
- `resources/views/cliente/contratos.blade.php` / `resources/views/propiedad/contratos.blade.php` — wrappers around the shared component; may need context flags/return links only.
- `resources/views/contrato/show.blade.php` — generated contract detail; likely needs high-level display correction outside generated markers if possible.
- `resources/views/layouts/app.blade.php` — existing `abrirModal`, `modalPrincipal`, and `flashModal` patterns to reuse.
- `resources/views/cobro/modal/create.blade.php` — useful reference for modal, CLP amount handling, participant selection, and loading/error conventions, but should not be coupled to the termination preview unless creating real cobros becomes in scope later.

### Approaches

1. **Server-rendered modal data, client-side calculator** — Load each active contract with participants and pending cobros, render a hidden modal template per contract, and use JS only to add/remove temporary expense rows and update totals.
   - Pros: stays front-end-only for the termination workflow, no fetch/loading burden, reuses `abrirModal`, easiest to reason about.
   - Cons: controller queries need read-only eager-loading; pages with many contracts may render more hidden HTML.
   - Effort: Medium

2. **Read-only JSON endpoint for termination preview** — Add a route that returns contract, warranty, dates, pending cobros, and services; button fetches data and renders the modal.
   - Pros: lighter initial page and cleaner separation of data from markup.
   - Cons: adds backend/API surface even though the user asked for front-end-only behavior; every fetch must implement local loading/error handling.
   - Effort: Medium

3. **Reuse cobro creation modal** — Adapt `cobro.modal.create` for termination expenses.
   - Pros: reuses existing cobro type/participant patterns.
   - Cons: misleading because termination expenses are only temporary preview rows now, while `cobro.modal.create` persists real cobros; high coupling and UX risk.
   - Effort: Medium/High

### Recommendation

Use approach 1. Keep termination as a non-persistent review/calculator modal: show inspection guidance, original `contrato.garantia`, `fecha_inicio`, termination date as today, existing pending cobros, a pre-added editable “Aseo Final” row, add/remove rows for `Reparación`, `Devolución`, `Extra`, etc., and calculate discount plus return amount in JavaScript. Do not update `fecha_termino`, do not create cobros, do not add a termination workflow route, and do not trigger payment/accounting side effects in this change.

For pending cobros, later phases should define the exact inclusion rule. The safest initial rule is cobros for the contract whose `estado` is in pending states and, when launched from client context, where the client participates in the cobro.

### Risks

- Role display bug: `arrendador` / `arrendatario` must dereference `ParticipanteContrato->cliente`; otherwise the contract cards show wrong/blank client information.
- State casing is inconsistent across code paths (`pendiente` lower-case in ficha controllers, `Pendiente` upper-case in APIs/validation); pending-cobro queries should handle both or confirm DB casing.
- “Gastos de servicios proporcionales” cannot be automatically calculated accurately from current explored code without knowing service billing dates/readings/last invoices; initially it should be explanatory/placeholder unless data model support is confirmed.
- Front-end-only means modal rows are preview-only; labels/buttons must avoid implying that contract termination or cobro creation has been executed.
- Generated files contain `[GEN:START/END]` blocks; changes to generated `contrato.show` should avoid generator-owned sections unless intentionally regenerating.

### Ready for Proposal

Yes. The proposal should scope this as a UI-only termination review modal plus contract view display fix, with read-only controller data allowed but no persistence, no destructive DB actions, and no termination workflow side effects.
