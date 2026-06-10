# Tasks: Ficha pendientes dashboard responsive

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 550-800 |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 API/tests → PR 2 UI/tests → PR 3 pagination |
| Delivery strategy | ask-on-risk |
| Chain strategy | feature-branch-chain |

Decision needed before apply: No
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Stabilize API/unit metadata | PR 1 | Base = feature/tracker branch. |
| 2 | Match ficha UI to dashboard | PR 2 | Base = PR 1 branch. |
| 3 | Enforce group pagination | PR 3 | Base = PR 2 branch; chain-approved. |

## Phase 1: RED — Data Contract Tests

- [x] 1.1 RED propiedad API: `show_unidad=true` when real `Unidad` count > 1, even with one pending unit.
- [x] 1.2 RED propiedad API: `show_unidad=false` with one real unit and unit metadata fields.
- [x] 1.3 RED cliente API: flattened property rows and nullable cobro modal fields.

## Phase 2: GREEN — API and Controller Data

- [x] 2.1 Update `app/Http/Controllers/Api/PropiedadPendientesController.php` for real-unit count metadata.
- [x] 2.2 Update `app/Http/Controllers/Api/ClientePendientesController.php` for dashboard-like rows.
- [x] 2.3 Update `app/Http/Controllers/Vistas/FichaPropiedadController.php` to pass `$showUnidadColumn`.
- [x] 2.4 Update `app/Http/Controllers/Vistas/FichaClienteController.php` initial data for AJAX parity.

## Phase 3: RED/GREEN — Initial Blade Visual Contract

- [x] 3.1 RED view assertions: table classes, `td-cobros`, concepto buttons, optional `Unidad`.
- [x] 3.2 Replace `resources/views/components/pendientes.blade.php` with dashboard-like table/card.
- [x] 3.3 Replace `resources/views/components/pendientes-propiedad.blade.php`; conditional `Unidad`.

## Phase 4: RED/GREEN — AJAX Renderer Parity

- [x] 4.1 RED AJAX parity after payment and serialized cobro data.
- [x] 4.2 Refactor `resources/views/cliente.blade.php` JS for dashboard-like rows/buttons.
- [x] 4.3 Refactor `resources/views/propiedad.blade.php` JS for optional `Unidad`.

## Phase 5: REFACTOR and Verification

- [x] 5.1 Extend `public/assets/css/style.css` dashboard mobile overrides to ficha tables.
- [x] 5.2 Run targeted PHPUnit; NEVER run destructive DB commands.
- [x] 5.3 Manually verify desktop and `<576px` mobile before/after payment refresh.

## Phase 6: RED/GREEN — Group Pagination Correction

- [x] 6.1 RED tests: dashboard/index and cliente ficha max 3 property groups; no property cobro split.
- [x] 6.2 RED tests: propiedad ficha max 3 unit groups; no unit cobro split.
- [x] 6.3 GREEN `dashboard/index.blade.php` + `DashboardPendientesController.php`: request/clamp 3 property groups.
- [x] 6.4 GREEN `ClientePendientesController.php` + `FichaClienteController.php`: paginate distinct property IDs at 3.
- [x] 6.5 GREEN `PropiedadPendientesController.php` + `FichaPropiedadController.php`: paginate distinct unit IDs at 3.
- [x] 6.6 Verify targeted PHPUnit and manual pagination; no destructive DB commands.
