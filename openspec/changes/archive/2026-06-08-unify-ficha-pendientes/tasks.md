# Tasks: Unify Ficha Pendientes with Index/Dashboard Style

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~680–720 |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 → PR 2 → PR 3 |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Dashboard `Incompleto` + API controllers + routes | PR 1 | Base: `main`; self-contained |
| 2 | Ficha controller switch replacement + grouped data | PR 2 | Base: PR 1 branch |
| 3 | Blade components (cliente ficha + propiedad ficha) + AJAX refresh | PR 3 | Base: PR 2 branch |

---

## Phase 1: Dashboard Incompleto + New API Controllers (PR 1)

- [ ] 1.1 **Add `Incompleto` to DashboardPendientesController**
  - File: `app/Http/Controllers/Api/DashboardPendientesController.php`
  - Change: Replace `$estadosPendientes = ['Pendiente', 'Vencido']` with `['Pendiente', 'Vencido', 'Incompleto']` (line 19)
  - Verify: Dashboard JS `renderCobros()` already maps `Incompleto → btn-info` (dashboard line 177)
  - Acceptance: Dashboard API response includes `Incompleto` cobros; no other changes needed
  - Effort: Small | Dependencies: None

- [ ] 1.2 **Create ClientePendientesController**
  - File: `app/Http/Controllers/Api/ClientePendientesController.php` (**new**)
  - Implements: `GET /api/cliente/{id}/pendientes`
  - Query: Same as `FichaClienteController::baseQuery()` + `whereIn('estado', ['pendiente', 'vencido', 'incompleto'])`
  - Grouping: Group by `propiedad_id`, then by `unidad` (if >1), then bucket by role (arrendador/arrendatario/corredor)
  - Uses: `CobroConceptoFormatter::format()` for `concepto` field
  - Response shape: `{ data: [...], total, pagina, por_pagina, total_paginas }` — mirrors `DashboardPendientesController`
  - Eager loads: `participante_cobros.cliente`, `contrato.participante_contratos`, `contrato.unidad`, `servicio`
  - Acceptance: `GET /api/cliente/{id}/pendientes?pagina=1&por_pagina=10` returns grouped JSON with correct shape
  - Effort: Medium | Dependencies: 1.1

- [ ] 1.3 **Create PropiedadPendientesController**
  - File: `app/Http/Controllers/Api/PropiedadPendientesController.php` (**new**)
  - Implements: `GET /api/propiedad/{id}/pendientes`
  - Query: Same as `FichaPropiedadController::baseQuery()` + `whereIn('estado', ['pendiente', 'vencido', 'incompleto'])`
  - Grouping: Group by `unidad_id`, then bucket by role
  - Uses: `CobroConceptoFormatter::format()` for `concepto` field
  - Response shape: Same as `ClientePendientesController`
  - Eager loads: Same as 1.2
  - Acceptance: `GET /api/propiedad/{id}/pendientes?pagina=1&por_pagina=10` returns grouped JSON with correct shape
  - Effort: Medium | Dependencies: 1.1

- [x] 1.4 **Add API routes**
  - File: `routes/api.php`
  - Add: `Route::get('/cliente/{id}/pendientes', [ClientePendientesController::class, 'index'])`
  - Add: `Route::get('/propiedad/{id}/pendientes', [PropiedadPendientesController::class, 'index'])`
  - Acceptance: Both routes are registered and respond with correct JSON
  - Effort: Small | Dependencies: 1.2, 1.3

---

## Phase 2: Ficha Controller Switch Replacement + Grouped Data (PR 2)

- [x] 2.1 **Replace switch in FichaClienteController with CobroConceptoFormatter**
  - File: `app/Http/Controllers/Vistas/FichaClienteController.php`
  - Remove: Lines 67–98 (the `foreach` with switch block for `concepto`)
  - Replace with: `CobroConceptoFormatter::format($cobro->tipo, $cobro->fecha_cobro)`
  - Add: After the concepto loop, add a second pass that groups `$pendientes` into `$groupedPendientes` array (propiedad → unidad (if >1) → role bucket)
  - Pass: Add `$groupedPendientes` to the `view()` compact call
  - Acceptance: Controller still provides `$pendientes` (paginator for `links()`) plus new `$groupedPendientes`; `concepto` is computed via formatter
  - Effort: Medium | Dependencies: 1.1 (formatter already exists)

- [x] 2.2 **Replace switch in FichaPropiedadController with CobroConceptoFormatter**
  - File: `app/Http/Controllers/Vistas/FichaPropiedadController.php`
  - Remove: Lines 66–97 (the `foreach` with switch block for `concepto`)
  - Replace with: `CobroConceptoFormatter::format($cobro->tipo, $cobro->fecha_cobro)`
  - Add: After the concepto loop, add grouping pass into `$groupedPendientes` array (unidad → role bucket)
  - Pass: Add `$groupedPendientes` to the `view()` compact call
  - Acceptance: Controller still provides `$pendientes` plus new `$groupedPendientes`; `concepto` is computed via formatter
  - Effort: Medium | Dependencies: 2.1

- [x] 2.3 **Update cliente.blade.php: replace location.reload with AJAX refresh**
  - File: `resources/views/cliente.blade.php`
  - Remove: `location.reload()` in `registrarPago()` success block
  - Add: `cargarFichaPendientes(paginaActual)` call after modal hide
  - Add: JS functions `cargarFichaPendientes(pagina)`, `renderFichaPendientes(json)`, `renderFichaCobros(lista)`, `renderFichaPaginacion(pagina, totalPaginas)`
  - Note: `@push('styles')` deferred to PR 3 (Blade components are not modified in this PR per task constraint)
  - Acceptance: After payment success, the pendientes section re-renders via AJAX without full page reload
  - Effort: Medium | Dependencies: 1.2, 2.1

- [x] 2.4 **Update propiedad.blade.php: replace location.reload with AJAX refresh**
  - File: `resources/views/propiedad.blade.php`
  - Same changes as 2.3 but targeting `/api/propiedad/{id}/pendientes`
  - Acceptance: Same as 2.3
  - Effort: Medium | Dependencies: 1.3, 2.2

---

## Phase 3: Blade Components + Empty States + Tests (PR 3)

- [x] 3.1 **Restructure pendientes.blade.php (Cliente Ficha)**
  - File: `resources/views/components/pendientes.blade.php`
  - Receives: `$groupedPendientes` + `$pendientes` (paginator) + `$cliente`
  - Desktop (≥576px): Table with `Dirección` column + dynamic role columns (arrendador/arrendatario/corredor — show only columns with data)
  - Mobile (<576px): Propiedad cards → nested unidad sub-cards (if `unidad_count > 1`, with `border-left: 3px solid var(--bs-border-color); padding-left: 12px`) → colored badge buttons
  - Empty state: Replace `alert alert-light border` with animated SVG (from dashboard, lines 36–44) + `fadeInUp`/`bounceIn`/`pulse` animations
  - Loading placeholder: `<tbody>` includes `<tr class="loading-placeholder">` (removed by `DOMContentLoaded` in `app.js`)
  - Estado colors: `Pendiente → btn-warning`, `Vencido → btn-danger`, `Incompleto → btn-info` via CSS classes
  - Pagination: `$pendientes->links()` preserved
  - Acceptance: Matches FR-1.1 through FR-1.9 and AC-1, AC-8, AC-9
  - Effort: Large | Dependencies: 2.1, 2.3

- [x] 3.2 **Restructure pendientes-propiedad.blade.php (Propiedad Ficha)**
  - File: `resources/views/components/pendientes-propiedad.blade.php`
  - Receives: `$groupedPendientes` + `$pendientes` (paginator) + `$propiedad`
  - Desktop (≥576px): Table with `Unidad` column + dynamic role columns
  - Mobile (<576px): Unidad cards → colored badge buttons
  - Same empty state SVG and loading placeholder pattern as 3.1
  - Same estado CSS class pattern
  - Acceptance: Matches FR-2.1 through FR-2.7 and AC-2, AC-8, AC-9
  - Effort: Large | Dependencies: 2.2, 2.4

- [x] 3.3 **Create ClientePendientesControllerTest**
  - File: `tests/Feature/Api/ClientePendientesControllerTest.php` (**new**)
  - Test: Groups by propiedad (create client with 2 propiedades, verify response structure)
  - Test: Nests unidades when >1 (create propiedad with 3 unidades, verify `unidades` array)
  - Test: Flat when single unidad (verify no `unidades` nesting when only 1 unidad)
  - Test: Pagination (`?pagina=2&por_pagina=10` works)
  - Test: `concepto` field matches `CobroConceptoFormatter` output
  - Test: All three estados included
  - Acceptance: All tests pass
  - Effort: Medium | Dependencies: 1.2

- [x] 3.4 **Create PropiedadPendientesControllerTest**
  - File: `tests/Feature/Api/PropiedadPendientesControllerTest.php` (**new**)
  - Test: Groups by unidad
  - Test: Pagination works
  - Test: `concepto` field matches formatter
  - Test: All three estados included
  - Acceptance: All tests pass
  - Effort: Medium | Dependencies: 1.3

- [x] 3.5 **Modify DashboardPendientesControllerTest**
  - File: `tests/Feature/Api/DashboardPendientesControllerTest.php` (existing — **modify**)
  - Change: `test_only_pendiente_and_vencido_in_results` → `test_includes_pendiente_vencido_and_incompleto` (asserts Incompleto IS included)
  - Note: Also fixed pre-existing bug in `test_cobros_grouped_by_role_bucket` (wrong deudor_rol test data)
  - Acceptance: Test confirms `Incompleto` cobros appear in dashboard API
  - Effort: Small | Dependencies: 1.1

---

## Phase 4: Integration Verification (PR 3, post-blade)

- [ ] 4.1 **Manual verification: Cliente ficha grouped display on desktop**
  - Navigate to `/cliente/ficha/{id}` with a client that has pendientes
  - Verify: Propiedad cards with address header linking to `/propiedad/ficha/{id}`
  - Verify: Cobros bucketed into arrendador/arrendatario/corredor columns
  - Verify: Dynamic column visibility (only show columns with data)
  - Acceptance: Matches AC-1

- [ ] 4.2 **Manual verification: Cliente ficha mobile — nested unidad cards**
  - Resize browser to ≤576px
  - Verify: `border-left: 3px solid var(--bs-border-color); padding-left: 12px` on unidad sub-cards
  - Verify: Colored badge buttons for each cobro
  - Acceptance: Matches FR-1.4, SC-2

- [ ] 4.3 **Manual verification: Propiedad ficha grouped by unidad**
  - Navigate to `/propiedad/ficha/{id}`
  - Verify: Unidad cards with nombre header
  - Verify: Cobros bucketed into role columns
  - Acceptance: Matches AC-2, SC-3

- [ ] 4.4 **Manual verification: Dashboard shows Incompleto with blue badge**
  - Load dashboard `/`
  - Verify: `Incompleto` cobros appear with `btn-info` (blue) badges
  - Verify: Counter includes propiedades with only `Incompleto` cobros
  - Acceptance: Matches FR-3.1, FR-3.2, SC-4

- [ ] 4.5 **Manual verification: Payment refreshes via AJAX (no reload)**
  - Open `#modalCobro` on cliente or propiedad ficha
  - Click "Registrar pago"
  - Verify: No full page reload (check Network tab)
  - Verify: Loading spinner appears during fetch
  - Verify: Pendientes section re-renders with updated list
  - Verify: Paid cobro disappears from list
  - Acceptance: Matches FR-7.1–FR-7.4, SC-6

- [ ] 4.6 **Manual verification: Empty state animated SVG**
  - Find or create a client/propiedad with zero pendientes
  - Verify: Animated SVG shows (fadeInUp, bounceIn, pulse)
  - Verify: Text: "No hay transacciones pendientes por el momento."
  - Acceptance: Matches FR-8.1–FR-8.4

- [ ] 4.7 **Manual verification: Existing modals still work**
  - Click "Revisar" → verify `#modalPrincipal` opens
  - Click mobile badge → verify `#modalCobro` opens
  - Verify: "Agregar cobro" button still works
  - Acceptance: Matches AC-10

- [ ] 4.8 **Manual verification: Pagination boundary after payment**
  - Go to page 2 of pendientes
  - Pay the last cobro on page 2
  - Verify: Refresh adjusts to page 1 if current page is now empty
  - Acceptance: Matches EC-6

---

## Implementation Order

1. **PR 1** (base → main): Tasks 1.1 → 1.4 — Dashboard `Incompleto` + new API controllers + routes. Self-contained foundation.
2. **PR 2** (PR 1 base): Tasks 2.1 → 2.4 — Replace switch blocks in ficha controllers with `CobroConceptoFormatter`, add grouped data structure, update cliente/propiedad blade with AJAX refresh scaffold.
3. **PR 3** (PR 2 base): Tasks 3.1 → 3.5 + 4.1 → 4.8 — Restructure Blade components (full grouped display), write tests, run manual verification.

**Rationale for split:**
- PR 1 is purely additive (new files + 1-line change). Safe to merge first.
- PR 2 modifies controllers (switch removal is deletion + formatter call) and blade JS (replaces `location.reload`). The Blade components still receive the old `$pendientes` format until PR 3, so no breakage.
- PR 3 swaps the Blade components to use `$groupedPendientes` and wires everything together. All three phases are independently testable.

---

## Summary

| Phase | Tasks | Focus |
|-------|-------|-------|
| Phase 1 | 1.1–1.4 | Dashboard Incompleto + new API controllers + routes |
| Phase 2 | 2.1–2.4 | Controller switch replacement + grouped data + AJAX refresh scaffold |
| Phase 3 | 3.1–3.5 + 4.1–4.8 | Blade component restructure + tests + manual verification |
| **Total** | **14 tasks** | |