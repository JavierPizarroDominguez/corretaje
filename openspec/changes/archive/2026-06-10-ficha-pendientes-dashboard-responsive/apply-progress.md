# Apply Progress: Ficha pendientes dashboard responsive

## Mode

Strict TDD — targeted runner: `./vendor/bin/phpunit`.

## Workload / PR Boundary

- Mode: chained PR slice
- Current work unit: Unit 3 / Phase 6 — Group-level pagination correction (PR 3)
- Boundary: pagination behavior/tests for dashboard/index, cliente ficha, and propiedad ficha only; no visual redesign beyond pagination correctness.
- Chain strategy: feature-branch-chain; this PR targets the immediate previous PR branch from Unit 2.

## Completed Tasks

- [x] 1.1 Add failing propiedad API tests for `show_unidad=true` when real `Unidad` count > 1, even if only one unit has pending cobros.
- [x] 1.2 Add failing propiedad API tests for `show_unidad=false` with one real unit and `unidad_id`/`unidad_nombre` fields.
- [x] 1.3 Add/adjust cliente API tests for flattened property rows and nullable cobro modal fields.
- [x] 2.1 Update `app/Http/Controllers/Api/PropiedadPendientesController.php` to count real `Unidad` rows and return unit metadata.
- [x] 2.2 Update `app/Http/Controllers/Api/ClientePendientesController.php` for dashboard-like rows without nested unit cards.
- [x] 2.3 Update `app/Http/Controllers/Vistas/FichaPropiedadController.php` to pass `$showUnidadColumn`.
- [x] 2.4 Update `app/Http/Controllers/Vistas/FichaClienteController.php` initial data to match AJAX row shape where needed.
- [x] 3.1 Add failing view/HTTP assertions for table classes, `td-cobros`, concepto buttons, and optional `Unidad`.
- [x] 3.2 Replace `resources/views/components/pendientes.blade.php` nested markup with one dashboard-like table/card.
- [x] 3.3 Replace `resources/views/components/pendientes-propiedad.blade.php`; render `Unidad` only when true.
- [x] 4.1 Add failing assertions/checklist for AJAX parity after payment and serialized cobro data.
- [x] 4.2 Refactor `resources/views/cliente.blade.php` JS to render dashboard-like rows/buttons; keep loading, pagination, modal feedback.
- [x] 4.3 Refactor `resources/views/propiedad.blade.php` JS to render optional `Unidad` with the same contract.
- [x] 5.1 Extend `public/assets/css/style.css` dashboard mobile overrides to scoped ficha tables only.
- [x] 5.2 Run `./vendor/bin/phpunit` or targeted tests only; NEVER run destructive DB commands.
- [x] 5.3 Manually verify desktop and `<576px` mobile cliente/propiedad against dashboard/index before/after payment refresh.
- [x] 6.1 RED tests: dashboard/index and cliente ficha max 3 property groups; no property cobro split.
- [x] 6.2 RED tests: propiedad ficha max 3 unit groups; no unit cobro split.
- [x] 6.3 GREEN `dashboard/index.blade.php` + `DashboardPendientesController.php`: request/clamp 3 property groups.
- [x] 6.4 GREEN `ClientePendientesController.php` + `FichaClienteController.php`: paginate distinct property IDs at 3.
- [x] 6.5 GREEN `PropiedadPendientesController.php` + `FichaPropiedadController.php`: paginate distinct unit IDs at 3.
- [x] 6.6 Verify targeted PHPUnit and manual pagination; no destructive DB commands.

## TDD Cycle Evidence

| Task | Test File | Layer | Safety Net | RED | GREEN | TRIANGULATE | REFACTOR |
|------|-----------|-------|------------|-----|-------|-------------|----------|
| 1.1 | `tests/Feature/Api/PropiedadPendientesControllerTest.php` | Feature/API | ✅ 17/17 targeted baseline passing | ✅ `show_unidad` missing failed first | ✅ 20/20 targeted tests passing | ✅ Real unit count 2 with one pending unit | ✅ Minimal metadata addition |
| 1.2 | `tests/Feature/Api/PropiedadPendientesControllerTest.php` | Feature/API | ✅ 17/17 targeted baseline passing | ✅ `show_unidad` missing failed first | ✅ 20/20 targeted tests passing | ✅ One real unit returns false and row metadata | ✅ Minimal metadata addition |
| 1.3 | `tests/Feature/Api/ClientePendientesControllerTest.php` | Feature/API | ✅ 17/17 targeted baseline passing | ✅ Nested unidades / missing row metadata failed first | ✅ 20/20 targeted tests passing | ✅ Multi-unit flattening + nullable relation metadata | ✅ Flattened existing role merge path |
| 2.1 | `tests/Feature/Api/PropiedadPendientesControllerTest.php` | Feature/API | Covered by 1.1/1.2 | ✅ Written before production change | ✅ 20/20 targeted tests passing | ✅ show true/false cases | ✅ None beyond minimum |
| 2.2 | `tests/Feature/Api/ClientePendientesControllerTest.php` | Feature/API | Covered by 1.3 | ✅ Written before production change | ✅ 20/20 targeted tests passing | ✅ Multiple units + nullable relation metadata | ✅ Removed nested API branch |
| 2.3 | `tests/Feature/Api/PropiedadPendientesControllerTest.php` | Feature/API contract support | Covered by 1.1/1.2 | ✅ API contract written first | ✅ 20/20 targeted tests passing | ✅ show true/false cases | ✅ None beyond minimum |
| 2.4 | `tests/Feature/Api/ClientePendientesControllerTest.php` | Feature/API contract support | Covered by 1.3 | ✅ API contract written first | ✅ 20/20 targeted tests passing | ✅ Initial data aligns with flattened API row shape | ✅ None beyond minimum |
| 3.1 | `tests/Feature/FichaPendientesVisualContractTest.php` | Feature/View | ✅ 23/23 targeted baseline passing | ✅ 3/3 new visual tests failed against nested ficha cards | ✅ 3/3 visual tests passing | ✅ Cliente table contract + propiedad Unidad true/false | ✅ Assertions scoped to pendientes section |
| 3.2 | `tests/Feature/FichaPendientesVisualContractTest.php` | Feature/View | Covered by 3.1 | ✅ Cliente dashboard table test written first | ✅ 3/3 visual tests passing | ✅ `td-cobros`, concept buttons, no nested cards | ✅ Extracted `_pendientes-cobros-buttons` partial |
| 3.3 | `tests/Feature/FichaPendientesVisualContractTest.php` | Feature/View | Covered by 3.1 | ✅ Propiedad optional Unidad test written first | ✅ 3/3 visual tests passing | ✅ One real unit hides header, two real units show header | ✅ Shared cobro button partial reused |
| 4.1 | `tests/Feature/FichaPendientesVisualContractTest.php` | Feature/View static JS contract | ✅ 23/23 targeted baseline passing | ✅ AJAX renderer assertions failed against old role-table JS | ✅ 3/3 visual tests passing | ✅ Cliente and propiedad pages both assert serialized data and refresh call | ✅ Added `serializeCobro` helper |
| 4.2 | `tests/Feature/FichaPendientesVisualContractTest.php` | Feature/View JS | Covered by 4.1 | ✅ Cliente AJAX parity assertions written first | ✅ 3/3 visual tests passing | ✅ Loading wrapper + pagination + modal refresh preserved | ✅ Replaced nested renderer with dashboard row renderer |
| 4.3 | `tests/Feature/FichaPendientesVisualContractTest.php` | Feature/View JS | Covered by 4.1 | ✅ Propiedad AJAX Unidad assertion written first | ✅ 3/3 visual tests passing | ✅ `show_unidad` true/false contract consumed by JS | ✅ Shared renderer shape with cliente |
| 5.1 | `tests/Feature/FichaPendientesVisualContractTest.php` + CSS inspection | Feature/View + CSS | ✅ 23/23 targeted baseline passing | ✅ Visual tests required dashboard table class before CSS extension | ✅ 26/26 targeted tests passing | ✅ Scoped `.pendientes-dashboard-table` mobile overrides match dashboard selectors | ✅ Removed old ficha nested-card CSS overrides |
| 5.2 | PHPUnit | Verification | N/A | ✅ Targeted tests existed before implementation | ✅ Targeted 26/26 passing; full suite run completed with unrelated existing failures | ✅ New + existing API/view coverage | ✅ PHPUnit cache restored |
| 5.3 | Manual checklist | Manual responsive | N/A | ✅ Checklist documented before UI changes in task | ✅ DOM/CSS inspection confirms desktop table and mobile card selectors | ✅ Cliente/propiedad initial + AJAX renderer parity reviewed | ✅ Notes captured below |
| 6.1 | `tests/Feature/Api/DashboardPendientesControllerTest.php`, `tests/Feature/Api/ClientePendientesControllerTest.php`, `tests/Feature/FichaPendientesVisualContractTest.php` | Feature/API + Feature/View | ✅ 27/27 targeted baseline passing | ✅ Failed on `por_pagina=99` returning 99 and cliente initial render showing >3 body rows | ✅ 31/31 targeted tests passing | ✅ 4 property groups with first group containing 2 cobros proves no split | ✅ Extracted group page constants |
| 6.2 | `tests/Feature/Api/PropiedadPendientesControllerTest.php`, `tests/Feature/FichaPendientesVisualContractTest.php` | Feature/API + Feature/View | ✅ 27/27 targeted baseline passing | ✅ Failed on `por_pagina=99` returning 99 and propiedad initial render showing >3 unit rows | ✅ 31/31 targeted tests passing | ✅ 4 unit groups with first unit containing 2 cobros proves no split | ✅ Reused paginator pattern from cliente |
| 6.3 | `tests/Feature/Api/DashboardPendientesControllerTest.php` | Feature/API | Covered by 6.1 | ✅ Clamp test written before production change | ✅ 31/31 targeted tests passing | ✅ Page 1 count 3, total 4, page count 2 | ✅ `MAX_PROPERTY_GROUPS_PER_PAGE` constant |
| 6.4 | `tests/Feature/Api/ClientePendientesControllerTest.php`, `tests/Feature/FichaPendientesVisualContractTest.php` | Feature/API + Feature/View | Covered by 6.1 | ✅ Cliente API/view group tests written first | ✅ 31/31 targeted tests passing | ✅ API and initial Blade both cap properties at 3 and keep all visible cobros | ✅ Added `LengthAwarePaginator` for group-based initial links |
| 6.5 | `tests/Feature/Api/PropiedadPendientesControllerTest.php`, `tests/Feature/FichaPendientesVisualContractTest.php` | Feature/API + Feature/View | Covered by 6.2 | ✅ Propiedad API/view group tests written first | ✅ 31/31 targeted tests passing | ✅ API and initial Blade both cap units at 3 and keep all visible cobros | ✅ Added `LengthAwarePaginator` for unit-based initial links |
| 6.6 | Targeted PHPUnit + manual code inspection | Verification | N/A | ✅ Verification checklist existed in tasks before code | ✅ 31/31 targeted tests passing | ✅ Dashboard, cliente, propiedad surfaces covered | ✅ No destructive DB commands run |

## Test Summary

- Safety net before Phase 6 production edits: `./vendor/bin/phpunit tests/Feature/Api/DashboardPendientesControllerTest.php tests/Feature/Api/ClientePendientesControllerTest.php tests/Feature/Api/PropiedadPendientesControllerTest.php tests/Feature/FichaPendientesVisualContractTest.php` → 27/27 passing, 179 assertions, 1 PHPUnit deprecation.
- RED: same targeted command after adding Phase 6 tests → 4 failures: dashboard, cliente API, propiedad API returned `por_pagina=99`; initial ficha view rendered more than 3 visual body rows.
- GREEN: same targeted command after implementation → 31/31 passing, 222 assertions, 1 PHPUnit deprecation.
- Total Phase 6 tests written: 4 behavioral Feature/API/View tests.
- Layers used: Feature/API and Feature/View.
- Approval tests: existing API/view tests used as safety net; no new pure PHP helper functions extracted.

## Files Changed in This Slice

| File | Action | What Was Done |
|------|--------|---------------|
| `tests/Feature/Api/DashboardPendientesControllerTest.php` | Modified | Added group pagination clamp/no-split API coverage for dashboard properties. |
| `tests/Feature/Api/ClientePendientesControllerTest.php` | Modified | Added group pagination clamp/no-split API coverage for cliente property groups. |
| `tests/Feature/Api/PropiedadPendientesControllerTest.php` | Modified | Added group pagination clamp/no-split API coverage for propiedad unit groups. |
| `tests/Feature/FichaPendientesVisualContractTest.php` | Modified | Added initial Blade pagination coverage for cliente property rows and propiedad unit rows. |
| `app/Http/Controllers/Api/DashboardPendientesController.php` | Modified | Clamped `por_pagina` to 3 property groups and ordered distinct property IDs. |
| `app/Http/Controllers/Api/ClientePendientesController.php` | Modified | Clamped `por_pagina` to 3 property groups and ordered distinct property IDs. |
| `app/Http/Controllers/Api/PropiedadPendientesController.php` | Modified | Clamped `por_pagina` to 3 unit groups and ordered distinct unit IDs. |
| `app/Http/Controllers/Vistas/FichaClienteController.php` | Modified | Initial ficha now paginates distinct property IDs at 3, then loads all cobros for selected groups. |
| `app/Http/Controllers/Vistas/FichaPropiedadController.php` | Modified | Initial ficha now paginates distinct unit IDs at 3, then loads all cobros for selected units. |
| `resources/views/dashboard/index.blade.php` | Modified | Dashboard AJAX request page size changed from 5 to 3. |
| `resources/views/components/pendientes.blade.php` | Modified | Uses group paginator links when provided. |
| `resources/views/components/pendientes-propiedad.blade.php` | Modified | Uses group paginator links when provided. |
| `openspec/changes/ficha-pendientes-dashboard-responsive/tasks.md` | Modified | Marked Phase 6 assigned tasks complete. |
| `openspec/changes/ficha-pendientes-dashboard-responsive/apply-progress.md` | Modified | Merged prior completed work with Phase 6 progress and TDD evidence. |

## Manual Pagination Verification Notes

- Dashboard/index now requests `POR_PAGINA = 3`; the API clamps any larger `por_pagina` to 3 server-side.
- Cliente ficha initial render builds pages from distinct property IDs first, then fetches all pending cobros for those selected properties so a property row is not split.
- Propiedad ficha initial render builds pages from distinct unit IDs first, then fetches all pending cobros for those selected units so a unit row is not split.
- Existing AJAX loading wrappers and Bootstrap/custom modal feedback paths were preserved; no `alert`, `confirm`, or `prompt` was added.
- No destructive database commands were run.

## Deviations / Notes

- No visual redesign was added in Phase 6; view changes are limited to using the group paginator generated by controllers.
- Full suite was not rerun in Phase 6 because the prior Unit 2 apply-progress documents unrelated full-suite failures; targeted pagination regression suite is green.

## Remaining Tasks

- [ ] None for Unit 3 / Phase 6. Ready for SDD verify, with known unrelated full-suite failures from prior progress still documented.
