# Proposal: Ficha pendientes dashboard responsive

## Intent

Make pending cobros in cliente and propiedad fichas visually identical to the dashboard/index pendientes, using the dashboard as the source of truth. The current ficha rendering uses nested cards/tables and diverges after AJAX refresh.

## Scope

### In Scope
- Replace ficha pendientes presentation with dashboard-like responsive layout: desktop table, mobile cards.
- Align cliente and propiedad initial Blade render and AJAX refresh render.
- In propiedad ficha, replace the property/direction column with `Unidad` and show it only when the propiedad has more than one real unit.
- Preserve pagination by group: dashboard/index and cliente ficha paginate properties with a maximum of 3 properties per page; propiedad ficha paginates units with a maximum of 3 units per page.
- Preserve local loading indicators and modal payment feedback conventions.

### Out of Scope
- Changing cobro payment rules, estados, or unrelated pending-payment business logic.
- Large shared renderer refactor unless needed to prevent initial/AJAX drift.
- Changing dashboard/index visual design.

## Capabilities

### New Capabilities
- None

### Modified Capabilities
- `ficha-pendientes-mobile`: update ficha pendientes responsive contract so desktop and mobile match dashboard/index visuals instead of the older ficha-specific layout.

## Approach

Use the dashboard/index pendientes table/card pattern as the visual contract. Flatten ficha pending groups into dashboard-like rows with centered full-width cobro buttons showing `concepto`, keep dynamic role columns, and keep scoped AJAX refresh after payment. Keep pagination at the property/unit group level, never by individual cobro. Add reliable propiedad unit metadata/API signal based on total real units, not only units with pending cobros.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `resources/views/components/pendientes.blade.php` | Modified | Cliente initial pendientes shell/markup. |
| `resources/views/components/pendientes-propiedad.blade.php` | Modified | Propiedad initial markup with optional `Unidad`. |
| `resources/views/cliente.blade.php` | Modified | AJAX renderer matches dashboard visual output. |
| `resources/views/propiedad.blade.php` | Modified | AJAX renderer uses optional `Unidad` column. |
| `app/Http/Controllers/Api/*PendientesController.php` | Modified | Ensure metadata supports identical render and unit rule. |
| `public/assets/css/style.css` | Modified | Reuse/extend dashboard responsive card styling without drift. |
| `tests/Feature/Api/*Pendientes*Test.php` | Modified | Cover unit metadata and rendering data shape. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Initial Blade and AJAX render diverge | Med | Update both from same visual contract and test/verify post-payment refresh. |
| `Unidad` hidden incorrectly | Med | Base visibility on real propiedad unit count, not pending rows. |
| CSS affects unrelated tables | Low | Scope selectors to ficha/dashboard pendientes classes/IDs. |

## Rollback Plan

Revert modified Blade, JS, CSS, API metadata, and tests for this change. Payment endpoint and cobro business logic remain untouched, so rollback is UI/data-shape only.

## Dependencies

- Existing `showElLoading`/`hideElLoading`, Bootstrap modal flow, and dashboard/index pendientes visual pattern.

## Success Criteria

- [ ] Cliente and propiedad fichas match dashboard/index pendientes visually on desktop and mobile.
- [ ] Pagination shows at most 3 property groups per page for dashboard/index and cliente ficha, and at most 3 unit groups per page for propiedad ficha.
- [ ] Propiedad ficha shows `Unidad` only when the propiedad has more than one real unit.
- [ ] Paying a cobro refreshes the ficha without changing layout or feedback conventions.
- [ ] No unrelated cobro payment business behavior changes.
