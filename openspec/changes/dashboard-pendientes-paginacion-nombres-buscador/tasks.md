# Tasks: Dashboard Pendientes — Pagination, Display Names, and Buscador Links

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~180–240 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Delivery strategy | ask-on-risk |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Full implementation | Single PR | Formatter + controllers + view + routes + tests |

---

## Phase 1: Foundation

- [x] 1.1 Create `app/Services/CobroConceptoFormatter.php` with static `format(string $tipo, ?Carbon $fechaCobro): string` — implement all tipo→concepto rules per spec table, use Carbon translatedFormat('F') for Spanish month

## Phase 2: Core Implementation

- [x] 2.1 Modify `app/Http/Controllers/Api/DashboardPendientesController.php` — add `fecha_cobro` (ISO string) and `concepto` via `CobroConceptoFormatter::format()` to each cobroData object

- [x] 2.2 Modify `resources/views/dashboard/index.blade.php` — change `POR_PAGINA` from 10 to 5; replace `${c.tipo}` with `${c.concepto}` in button/label rendering; change property link from `/propiedad/${item.id}` to `/propiedad/ficha/${item.id}`

- [x] 2.3 Modify `app/Http/Controllers/Api/DashboardBuscadorController.php` — for `propiedad` results change `url` to `/propiedad/ficha/{id}`; for `cliente` results change `url` to `/cliente/ficha/{id}`; leave other entity types unchanged

- [x] 2.4 Modify `routes/web.php` — add `Route::get('/propiedad/ficha/{id}', fn() => view('coming-soon'))` placeholder closure route

## Phase 3: Testing

- [x] 3.1 Write unit tests for `CobroConceptoFormatter::format()` covering all 7+ tipo cases in spec table (including null fecha_cobro fallback, unknown tipo fallback) — use PHPUnit data provider

- [x] 3.2 Write unit test for Buscador URL patterns — assert propiedad results contain `/propiedad/ficha/` and cliente results contain `/cliente/ficha/`

- [x] 3.3 Write integration test for `/api/dashboard/pendientes` — assert response envelope has `data`, `total`, `pagina`, `por_pagina`, `total_paginas` and each cobro object has `fecha_cobro` and `concepto` fields

## Dependencies

- Task 2.1 depends on Task 1.1
- Task 3.1 depends on Task 1.1
- Task 3.3 depends on Task 2.1
- All other tasks are independent
