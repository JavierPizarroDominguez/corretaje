# Proposal: Terminar contrato front-end

## Intent

Add a front-end-only contract termination review flow from cliente/propiedad contract views, and fix contract display that misreads scoped participant relations.

## Scope

### In Scope
- Add `Terminar contrato` per active contract in ficha de propiedad and ficha de cliente.
- Open a review modal showing inspection guidance, `contrato.garantia`, start date, today as termination date, pending cobros, and a pre-added `Aseo Final` row.
- Allow temporary add/edit/remove rows for `Reparación`, `Devolución`, `Extra`, etc.; calculate total discounts and guarantee return in the browser.
- Render the contract list/detail with correct participant, date, guarantee, property/unit, and cobro information.

### Out of Scope
- Persisting termination, updating `fecha_termino`, changing status, creating cobros, payments, accounting, or notifications.
- Calculating proportional utilities/services until billing-date/readings data is confirmed.
- Adding a termination API route unless later design proves server-rendered data is insufficient.

## Capabilities

### New Capabilities
- `contract-termination-preview`: Entry points, modal content, temporary expense rows, pending cobros, and guarantee-return calculations.
- `contract-display`: Correct participants and related data in ficha contract views and contract detail.

### Modified Capabilities
- `clp-input-format`: Termination modal monetary inputs/totals should reuse global CLP utilities.

## Approach

Use server-rendered modal data plus a client-side calculator. Controllers may eager-load read-only relationships, but termination remains non-persistent. Participant names/links must use `ParticipanteContrato->cliente`. Pending cobros include contract cobros in states `pendiente`, `vencido`, `incompleto` and title-case variants; from cliente context, scope to cobros where that cliente participates.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Http/Controllers/Vistas/FichaPropiedadController.php` | Modified | Eager-load contract participants/cobros. |
| `app/Http/Controllers/Vistas/FichaClienteController.php` | Modified | Same data, scoped to cliente context. |
| `app/Http/Controllers/Crud/ContratoController.php` | Modified | Load relationships for readable contract detail. |
| `resources/views/components/contratos.blade.php` | Modified | Button, modal template, participant display, calculator UI. |
| `resources/views/contrato/show.blade.php` | Modified | Correct readable contract information outside generated-owned sections. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Preview mistaken for real termination | Med | Copy must state review-only; no submit/terminate side effect. |
| Pending state casing mismatch | Med | Query both lower/title-case states. |
| Generated sections overwritten | Low | Avoid `[GEN:START/END]` blocks. |

## Rollback Plan

Revert Blade/controller changes. No database rollback is required because nothing persists.

## Dependencies

- Bootstrap modal pattern (`abrirModal`/`flashModal`) and global CLP utilities.
- No destructive database commands.

## Success Criteria

- [ ] Cliente/propiedad contract views show `Terminar contrato` per active contract.
- [ ] Modal displays required guidance, guarantee, dates, pending cobros, and default `Aseo Final` row.
- [ ] Expense row changes recalculate discount and return amount without persistence.
- [ ] Contract views display participants and related information correctly.
