## Exploration: terminar-contrato-devolucion-garantia

### Current State
`resources/views/components/contratos.blade.php` already renders a `Terminar contrato` preview modal for active contracts in property/client contract pages. It shows pending cobros, discount rows (`Aseo final`, `Reparación`, `Extra`), computes `Total descuentos` and `Monto a devolver`, and uses `flashModal`/local button spinners for existing cobro payment. It deliberately has no persistence endpoint yet.

Active contracts are selected by `fecha_termino IS NULL OR fecha_termino > now()` in `FichaPropiedadController::contratos()` and `FichaClienteController::contratos()`, so setting `fecha_termino = now()` will remove the contract from active administration views. Cobros use `Cobro` plus two `ParticipanteCobro` rows (`Deudor`/`Acreedor`). Paid cobros create `Transaccion` and `Transaccion_Cobro` via `PagarCobroController`, but termination must not blindly reuse that behavior because guarantee discounts are cobro records and the only guarantee-return transaction is the positive-amount refund transaction.

Transfer origin/destination is modeled through `Transaccion.Origen_Transaccion_id` and `Transaccion.Destino_Transaccion_id`. `PagarCobroController` derives those records from `Participante_Cobro`: origin is always the `Deudor` as `Origen_Transaccion(tipo='Cuenta Bancaria', Cliente_id=<deudor>)`; destination is either the `Acreedor` as `Destino_Transaccion(tipo='Cuenta Bancaria', Cliente_id=<acreedor>)` or a service destination when `Cobro.Servicio_id` exists. There is no current first-class “Garantía” origin type in the model or SQL dump: `Origen_Transaccion.tipo` is constrained to `Cuenta Bancaria` or `Saldo del Cliente`, while `Destino_Transaccion.tipo` is constrained to `Cuenta Bancaria` or `Empresa de Servicio`.

There are no domain migrations for existing tables in this repo; existing table names/models use DB-style casing (`Contrato`, `Cobro`, `Participante_Cobro`, `Transaccion_Cobro`). A new pivot table/model for `Descuento_Garantia` must be explicit about table name, keys, incrementing, and fillable fields to avoid Eloquent naming/casing conflicts.

Corrected domain rules supersede the prior transfer-origin assumption: the garantía itself is not a transaction and must not be modeled as `Origen_Transaccion`. The only transaction related to guarantee return is the `Devolución Garantía Arrendatario` transaction, and only when the refund amount is greater than `0`. If the refund amount is `0` and the refund cobro is marked `Pagado`, the workflow must not create a zero-valued `Transaccion` or `Transaccion_Cobro`. For guarantee-refund cobros, `Acreedor` is always the arrendador; validation remains required in both frontend and backend.

### Affected Areas
- `resources/views/components/contratos.blade.php` — add the final confirm action, collect discount rows, validate client-side, call backend with loading state and modal feedback.
- `routes/api.php` or custom routes in `routes/web.php` — add a non-generated termination endpoint.
- `app/Http/Controllers/Api/*` — new controller action for contract termination.
- `app/Http/Requests/*` — validate discount payload and reject sums above garantía.
- `app/Services/*` — best place for transaction-safe business workflow: terminate contract, create discount/refund cobros, create only the positive refund transaction, and maintain pivot links.
- `app/Http/Controllers/Api/PagarCobroController.php` — reference implementation for deriving transaction origin/destination from cobro participants; not ideal to overload for termination.
- `app/Models/OrigenTransaccion.php`, `app/Models/DestinoTransaccion.php`, `app/Models/Transaccion.php`, `app/Models/TransaccionCobro.php` — existing transfer model used by paid cobros.
- `config/cobro_roles.php`, `app/Services/CobroRelationshipResolver.php` — current role map conflicts with termination-discount requirements for `Reparación`/`Extra` and with “discount has no acreedor”.
- `app/Models/Contrato.php`, `app/Models/Cobro.php` — add explicit relationships for guarantee refund/discount links if needed.
- New `app/Models/DescuentoGarantia.php` + migration — pivot between the refund cobro and each discount cobro, if the workflow must preserve that explicit relationship.
- `tests/Feature/Api/*` and `tests/Feature/FichaContratosDisplayTest.php` — cover API workflow and UI contract.
- `openspec/specs/contract-termination-guarantee/spec.md` — existing spec covers preview only; needs delta for persistence.
- `C:\xampp\htdocs\src\corretaje-bd.sql` — source schema shows `Transaccion.monto > 0` and `Transaccion_Cobro.monto_pagado > 0`; the corrected `$0` refund rule aligns with this by not creating a transaction when the refund amount is `0`.

### Approaches
1. **Dedicated termination service and endpoint** — `POST /api/contratos/{contrato}/terminar` validates discounts, then a service runs one DB transaction.
   - Pros: atomic, testable, keeps complex business flow out of Blade/controller, can reuse payment-resolution logic for positive refund transactions.
   - Cons: requires several new files and likely a migration/model for the discount/refund pivot.
   - Effort: Medium/High

2. **Extend existing payment endpoint** — overload `/api/cobro/pagar` or add special flags to create discount/refund cobros.
   - Pros: reuses some transaction creation code.
   - Cons: mixes paying existing cobros with terminating contracts; harder validation and rollback; high risk of confusing API contract already specified by `cobro-payment`.
   - Effort: Medium

3. **Add a shared payment/transfer helper used by termination only** — extract the origin/destination creation logic into a small service method for the positive-amount refund transaction.
   - Pros: avoids overloading the public payment endpoint while keeping transaction creation consistent; gives one place to skip transaction creation when the refund amount is `0`.
   - Cons: must be explicit that the helper creates no transaction for zero-amount paid refunds and does not treat garantía as a transfer origin.
   - Effort: Medium

### Recommendation
Use a dedicated termination endpoint backed by a `TerminarContratoService`, plus a small internal helper for creating the positive-amount refund transaction consistently. The service should wrap all writes in `DB::transaction()`, lock or reload the contract, validate `sum(descuentos) <= garantia` both client-side and backend-side, set `fecha_termino = now()`, create one cobro record per discount without creating transfer transactions for those discounts, create the refund cobro (`Devolución Garantía Arrendatario`), and link each discount cobro to the refund cobro through `Descuento_Garantia` if that relationship is required for audit/reporting.

Apply the corrected participant model: the refund cobro's `Acreedor` is always the arrendador, and the garantía is not a participant, not a transaction, and not a transfer origin. The only guarantee-return transaction is created for `Devolución Garantía Arrendatario` when `monto > 0`. When the calculated refund amount is `0`, the refund cobro may be marked `Pagado`, but no `Transaccion` and no `Transaccion_Cobro` should be created.

For the `$0` refund case, the existing SQL constraints (`Transaccion.monto > 0`, `Transaccion_Cobro.monto_pagado > 0`) are no longer a schema conflict because no transaction row should be inserted. The implementation still needs backend validation to prevent accidentally routing a zero-valued refund through generic payment logic such as `PagarCobroController`, which currently always creates transaction rows from cobro participants.

### Risks
- ~~**Guarantee-origin mismatch**~~ Obsolete: the garantía is not a transaction origin and must not be modeled in transfer-origin tables.
- **Participant/transaction semantics:** existing payment logic assumes participant-derived origin/destination and always creates a transaction; termination must only create the refund transaction when `monto > 0` and must use arrendador as the refund cobro `Acreedor`.
- ~~**Zero-amount paid refund transaction**~~ Obsolete: a `$0` paid refund must not create `Transaccion`/`Transaccion_Cobro`; the remaining risk is accidentally reusing generic payment logic that would attempt one.
- **Eloquent/table casing:** add explicit `$table = 'Descuento_Garantia'`, non-incrementing/composite handling, and relationship names to avoid default plural/snake guesses.
- **No destructive DB commands:** migration can be written/tested, but apply/verify must not run `php artisan migrate` against real MySQL.
- **UI conventions:** the new fetch must use `showElLoading`/`hideElLoading`, disable the confirm button, and use `flashModal`/custom modal only.

### Ready for Proposal
Yes — proceed to proposal/spec. The corrected rules are clear: garantía is not a transaction origin; only positive refund amounts create a guarantee-return transaction; `$0` paid refunds create no transaction; refund cobro `Acreedor` is always the arrendador; validation remains frontend and backend.
