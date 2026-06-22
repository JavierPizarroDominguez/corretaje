## Exploration: terminar-contrato-garantia-proporcional

### Current State
`resources/views/components/contratos.blade.php` already renders a termination modal for active contracts and posts `descuentos` to `POST /api/contratos/{contrato}/terminar`. The modal is currently a combined preview/final action: it shows start date, today's termination date, original guarantee, pending cobros, discount rows, calculated refund, and a `Terminar contrato` button.

Backend termination is implemented in `app/Services/TerminarContratoService.php`: it locks the contract, validates discounts do not exceed guarantee, sets `fecha_termino = now()`, creates paid discount cobros, creates a `Devolución Garantía Arrendatario` cobro, links discounts via `Descuento_Garantia`, and currently creates a refund transaction immediately when refund amount is positive. Payment is handled separately by `POST /api/cobro/pagar`, which creates `Transaccion`, `Transaccion_Cobro`, and marks the cobro as `Pagado`.

Pending cobro buttons are duplicated across dashboard, client ficha, property ficha, and shared Blade partials. Their payloads currently include cobro identity, participants, amount, date, type, and formatted concept, but not an explicit guarantee-refund flag or contract metadata. Click handlers always open the generic cobro detail/payment modal.

Existing specs/tests cover the current termination/refund flow, generic payment, pending API shapes, and the termination modal preview. There is no existing implementation or test coverage for proportional rent cobros on termination, the 30-day guarantee-devolution deadline, or reopening the termination/devolution modal from pending views.

### Affected Areas
- `resources/views/components/contratos.blade.php` — split termination warning/acceptance from later guarantee-devolution modal behavior; update top card from `Garantía original` to `Plazo restante` when opened from a refund cobro; keep loading/modal conventions.
- `app/Services/TerminarContratoService.php` — change termination persistence so refund is pending only at termination, add proportional rent cobros, and avoid immediate transaction creation until `Devolver garantía`.
- `app/Http/Controllers/Api/TerminarContratoController.php` and `app/Http/Requests/TerminarContratoRequest.php` — likely need request contract changes if the first termination confirmation no longer submits discounts immediately.
- `app/Http/Controllers/Api/PagarCobroController.php` — can pay normal pending cobros today; guarantee refund may need a specialized finalize endpoint if discounts are edited before payment.
- `app/Http/Controllers/Api/DashboardPendientesController.php`, `ClientePendientesController.php`, `PropiedadPendientesController.php`, `Vistas/FichaClienteController.php`, `Vistas/FichaPropiedadController.php` — add enough payload to identify guarantee refund cobros and route clicks to the devolution modal.
- `resources/views/dashboard/index.blade.php`, `resources/views/cliente.blade.php`, `resources/views/propiedad.blade.php`, `resources/views/components/_pendientes-cobros-buttons.blade.php`, `resources/views/components/_pendientes-role-table.blade.php` — pending click handling and button data attributes are repeated and need consistent behavior.
- `app/Services/CobroConceptoFormatter.php` and `app/Http/Controllers/Crud/CobroController.php` — new types `Ingreso Proporcional Renta Arrendatario` and `Egreso Proporcional Renta Arrendador` need display/validation support.
- `tests/Feature/Api/TerminarContratoControllerTest.php`, `tests/Feature/Api/PagarCobroControllerTest.php`, `tests/Feature/FichaContratosDisplayTest.php`, pending API/controller tests — add coverage for warning flow, proportional cobros, refund pending behavior, 30-day remaining calculation, and special pending click behavior.

### Approaches
1. **Extend current termination modal/service** — keep the existing component and service, but make initial termination create only pending guarantee/refund/proportional cobros; reuse the existing devolution UI when a refund cobro is clicked.
   - Pros: smallest conceptual change, uses existing endpoint/modal patterns, easy to cover with current tests.
   - Cons: current modal mixes termination and refund-finalization concerns; dashboard/client/property duplicate JS still needs coordination.
   - Effort: Medium/High

2. **Introduce a dedicated guarantee-refund workflow endpoint** — termination endpoint only warns/terminates/creates pending cobros; a new endpoint finalizes `Devolución Garantía Arrendatario` after discounts and creates the transaction.
   - Pros: clearer idempotency boundary; aligns with “30 days to add discounts, then devolver garantía”.
   - Cons: requires new route/controller/request/tests and more UI wiring.
   - Effort: High

### Recommendation
Use approach 2: keep `POST /api/contratos/{contrato}/terminar` responsible for an atomic termination event and pending cobro creation, then add a dedicated guarantee-refund finalization path for `Devolver garantía`. This matches the requested lifecycle better than the current immediate refund transaction and gives a clean place to validate discounts, prevent double payment, and calculate `Plazo restante` from the original refund cobro/termination date.

### Risks
- Proportional formula is ambiguous: whether to charge days from `dia_pago` inclusive/exclusive, how to handle month wraparound, leap/month length, and whether both ingreso/egreso use full rent or egreso rent after commission.
- The requested display name says `Devolución de Garantía`, but existing code/spec/tests use `Devolución Garantía Arrendatario`; implementation needs a stable identifier to avoid string drift.
- Existing termination service creates a refund transaction immediately; this conflicts with “pending for 30 days” and will require spec/test updates.
- Idempotency is not handled today: a repeated termination request for an already terminated contract could create duplicate refund/discount cobros.
- Pending click behavior is duplicated in multiple views, so inconsistent changes are likely unless shared helpers or payload conventions are introduced.
- The source of the 30-day clock must be defined: `Contrato.fecha_termino`, `Cobro.fecha_cobro`, or a new persisted timestamp.
- Current pending grouping uses debtor role in the contract; guarantee refund and proportional egress/ingress must be checked so they appear under intended buckets.

### Ready for Proposal
Yes — but the proposal should explicitly call out three business clarifications before spec/design lock-in: exact proportional rent formula, whether `Devolución de Garantía` should replace or alias `Devolución Garantía Arrendatario`, and which date starts the 30-day countdown.
