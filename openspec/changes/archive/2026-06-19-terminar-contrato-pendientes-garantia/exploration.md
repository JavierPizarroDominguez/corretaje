# Exploration: Terminar contrato pending-payment UI and guarantee return calculation

Full user request: En `resources/views/components/contratos.blade.php`, en el modal de Terminar Contrato, reemplazar la tabla actual de cobros pendientes por el formato de pendientes de fichas e index, reutilizando componente completo si se puede. Debe capturar lógica desktop/mobile, modal de detalle del cobro con opción a pagar, agregar `Extra` a conceptos, calcular Total descuentos como sumatoria de conceptos agregados y monto a devolver como garantía - total descuentos, permitir quitar todos los conceptos y confirmar con: `¡Atención! se devolverá la garantía en su totalidad al arrendatario. ¿Está seguro que no hay reparaciones o aseo que pagar?`

### Current State

- `resources/views/components/contratos.blade.php` renders active contracts and a hidden `vista-terminar-contrato-{id}` copied into `#modalPrincipal` by `abrirModal(...)`.
- The Terminar Contrato modal currently shows pending cobros as a simple table (`Cobros pendientes`, lines 121-138) with rows using `.terminacion-row data-sign="charge" data-amount=...`.
- Its JS `recalculate(preview)` sums **all** `.terminacion-row` elements, so pending cobros currently affect `Total descuentos`; this conflicts with the requested model.
- `recalculate()` writes to `.terminacion-devoluciones`, but the current markup has no such element, so opening/recalculating can throw unless this is guarded or removed.
- The adjustment concept select is local to `components/contratos.blade.php` and currently has `Aseo final` and `Reparación`; `Extra` must be added there. Existing app cobro types already support `Extra` in `CobroController`, `config/cobro_roles.php`, ficha create modal, and ficha controllers.
- Removal is currently blocked when only one `.terminacion-ajuste` remains. The new UX requires allowing zero concept rows and showing a custom Bootstrap confirmation before removing the last/all concepts.

Existing pending-payment UI:

- Dashboard/index: `resources/views/dashboard/index.blade.php` renders `#tabla-pendientes` with `.table-card-mobile`, role columns (`arrendador`, `arrendatario`, `corredor`), full-width `.btn-cobro` buttons, AJAX refresh, and `#modalCobro` detail/payment modal.
- Fichas: `resources/views/components/pendientes.blade.php` and `resources/views/components/pendientes-propiedad.blade.php` render the dashboard-like table contract for initial page load. Their AJAX renderers live in `resources/views/cliente.blade.php` and `resources/views/propiedad.blade.php`.
- Button partial: `resources/views/components/_pendientes-cobros-buttons.blade.php` is reusable for role-cell buttons if cobros are shaped as arrays with `estado`, `concepto`, `tipo`, `monto`, participant IDs/names, `servicio_id`, and `fecha_cobro`.
- Mobile behavior is mostly CSS-driven through `.table-card-mobile` labels in `public/assets/js/app.js` and styles in `public/assets/css/style.css`; the dashboard/ficha-specific mobile card style requires `.pendientes-dashboard-table` or `#tabla-pendientes`.
- `resources/views/components/_pendientes-role-table.blade.php` has explicit desktop/mobile split, but current ficha/index contract has moved to one `.table-card-mobile pendientes-dashboard-table` table, which is the better source to mirror.

Cobro detail/payment modal:

- Dashboard, cliente ficha, and propiedad ficha each define their own `#modalCobro`, click handler for `.btn-cobro`, `registrarPago(cobro)`, and Bootstrap feedback modal. This behavior is duplicated, not componentized as a reusable Blade/JS module.
- `resources/views/cobro/modal/show.blade.php` is a separate CRUD detail modal with `Registrar Pago` as a link to `/cobro/{id}`, but it is not the same as the dashboard/ficha AJAX payment modal.
- To reuse the exact payment behavior inside the Terminar Contrato modal, the contracts page must include a `#modalCobro` compatible modal and the `.btn-cobro` click/payment JS, or extract that duplicated behavior into a shared partial/script first.

Data flow constraints:

- Contract pages are `resources/views/propiedad/contratos.blade.php` and `resources/views/cliente/contratos.blade.php`; both include only `components.contratos`.
- Controllers load `contratosVigentes` with pending `cobros`:
  - `FichaPropiedadController::contratos()` loads all pending cobros for contracts on the property.
  - `FichaClienteController::contratos()` filters contract cobros to those with a `participante_cobros.Cliente_id` equal to the cliente context.
- Contract cobros are Eloquent models, while pending UI partials expect array-shaped cobros. The implementation must map each `$cobro` to the same data shape used by `DashboardPendientesController`, `ClientePendientesController`, and `PropiedadPendientesController`, including `concepto` from `App\Services\CobroConceptoFormatter`.
- Role bucketing should follow the existing pending API logic: determine the deudor participant, match it against `contrato->participante_contratos`, then place the cobro under `arrendador`, `arrendatario`, `corredor`, defaulting to `arrendador` if unmatched.

### Affected Areas

- `resources/views/components/contratos.blade.php` — main modal markup, pending cobro rendering, adjustment concept rows, calculations, remove-all confirmation.
- `resources/views/propiedad/contratos.blade.php` and `resources/views/cliente/contratos.blade.php` — may need the shared `#modalCobro` markup/script if payment modal behavior is reused on contracts pages.
- `resources/views/components/_pendientes-cobros-buttons.blade.php` — reusable button renderer for role columns; likely no change needed.
- `resources/views/components/pendientes*.blade.php`, `resources/views/dashboard/index.blade.php`, `resources/views/cliente.blade.php`, `resources/views/propiedad.blade.php` — source patterns to mirror or extract from.
- `public/assets/css/style.css` and `public/assets/js/app.js` — already provide mobile card styling and data labels for `.table-card-mobile`; likely no change unless modal-cloned dynamic tables need explicit relabeling.
- `tests/Feature/FichaContratosDisplayTest.php` — current contract-modal assertions are stale relative to current `components/contratos.blade.php` and should be updated for new UI/calculation contract.
- Potential new/updated feature tests — assert `Extra`, pending UI classes/buttons/data-cobro, zero concepts confirmation strings, no native dialogs, and calculation JS selectors.

### Approaches

1. **Mirror ficha/index table in `components.contratos` and reuse button partial** — Build a grouped array per contract in Blade/PHP, render one `.table-card-mobile pendientes-dashboard-table ficha-pendientes-table` table, and include `_pendientes-cobros-buttons` per role column.
   - Pros: lowest scope; reuses existing CSS/mobile behavior and button partial; keeps change local.
   - Cons: still duplicates modal payment JS unless extracted separately; grouping logic in Blade can get bulky.
   - Effort: Medium.

2. **Extract shared pending-payment modal/table primitives first** — Create shared Blade/JS for pending table, cobro detail modal, and payment handler, then use it in dashboard/fichas/contracts.
   - Pros: best long-term reuse and removes duplication.
   - Cons: higher regression risk and likely exceeds the requested change/review budget because dashboard and ficha pages would be touched.
   - Effort: High.

3. **Use existing `_pendientes-role-table` explicit desktop/mobile component** — Adapt contract cobros to that component.
   - Pros: already has separate desktop/mobile logic.
   - Cons: not the current ficha/index contract per spec; visual behavior may diverge from dashboard/ficha `.pendientes-dashboard-table` standard.
   - Effort: Medium.

### Recommendation

Use Approach 1 for this change: in `components.contratos`, adapt each contract's pending cobros to the ficha/index data shape and render the same `.table-card-mobile pendientes-dashboard-table` structure with `_pendientes-cobros-buttons`. Add a local/shared `#modalCobro` and payment handler only for contract pages, unless a later refactor explicitly extracts the duplicated dashboard/ficha behavior. For calculations, remove pending cobros from `.terminacion-row`, make adjustment rows the only inputs to `Total descuentos`, and compute `Monto a devolver al arrendatario = garantía - total descuentos`.

Implementation notes for proposal/design:

- Concept rows should be charges only; the current `value="refund"` for `Reparación` is semantically wrong for “descuentos”. Prefer concept values/names such as `Aseo Final`, `Reparación`, `Extra` and sum all `.terminacion-ajuste .terminacion-amount`.
- Allow zero `.terminacion-ajuste` rows. When removing the last row, show a custom Bootstrap confirmation modal with the required message; do not use `confirm()`.
- If zero concepts remain, show the attention text/confirmation result and calculate descuentos as `$0`, refund as full garantía.
- Payment success should refresh or remove paid cobro from the modal if possible. If no AJAX endpoint exists for contract pending groups, simplest safe behavior is to hide payment modal and reload the current page or update the clicked button state; this needs design choice.

### Risks

- `#modalCobro` IDs and global `.btn-cobro` click handlers can conflict if contract pages later include ficha/dashboard scripts; keep handlers idempotent and scoped enough.
- `abrirModal()` clones hidden content into `#modalPrincipalBody`; event delegation works, but any MutationObserver labels from `app.js` only attach to tables present on DOMContentLoaded. Dynamic/cloned tables may need explicit label application or a local label function like fichas use.
- Existing tests for contract termination preview appear out of sync with current markup; test updates must be deliberate, not blindly preserve stale assertions.
- Payment from inside an already-open Terminar Contrato modal introduces stacked modal behavior (`#modalPrincipal` + `#modalCobro`); Bootstrap focus/backdrop behavior needs manual verification.
- Grouping pending cobros in Blade duplicates API logic unless extracted to a helper/service.

### Ready for Proposal

Yes — requirements are clear enough. Proposal should keep scope to the Terminar Contrato modal, reuse the existing pending button/table contract, and defer broad dashboard/ficha JS extraction unless the user explicitly accepts the larger refactor.
