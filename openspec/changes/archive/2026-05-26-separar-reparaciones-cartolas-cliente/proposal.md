# Proposal: Separar Reparaciones, Cartolas y Contratos de la Ficha de Cliente

## Intent

Split the monolithic cliente ficha page into focused pages. Reparaciones + Cartola and Contratos each get their own dedicated page, linked from the main ficha page via navigation buttons. This reduces per-page query load from 8+ to ~3, improves navigation clarity, and makes each section independently accessible and paginable.

## Scope

### In Scope
- 2 new routes: `cliente/{id}/reparaciones` (reparaciones + cartola), `cliente/{id}/contratos`
- 2 new controller methods on `FichaClienteController`
- 1 private helper extracting `$baseQuery` to avoid duplication
- 2 new Blade views reusing existing `@include` components
- Modify `cliente.blade.php`: remove 3 inline includes, add 2 navigation buttons
- Slim `FichaClienteController::show()` by removing extracted queries

### Out of Scope
- Modifying component Blade files (reparaciones-propiedad, cartola, contratos)
- Moving pendientes or transacciones-propiedad (stay on ficha)
- Changing component data contracts
- UI/UX redesign of existing components
- Adding automated tests (no test infrastructure exists)

## Capabilities

### New Capabilities
- `cliente-reparaciones-page`: Dedicated page for client reparaciones + cartola with scoped queries and pagination
- `cliente-contratos-page`: Dedicated page for client contratos with scoped queries

### Modified Capabilities
- None (existing specs pivot-relation, crud-generator, buscador are unaffected)

## Approach

Add 2 custom routes in `routes/web.php` custom block, 2 new methods on `FichaClienteController`, and 2 new Blade views under `resources/views/cliente/`. Each new method runs only its subset of original `show()` queries. `$baseQuery` is extracted to a private helper method to avoid duplication across `show()`, `reparaciones()`, and `contratos()`. The main ficha page keeps pendientes, modal, and transacciones with navigation buttons linking to new pages.

Components are reused via `@include` with identical variable contracts. Named pagination params (`reparaciones_page`) become default `page` on their own pages — natural and safe.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `routes/web.php` | Modified | Add 2 custom routes |
| `app/Http/Controllers/Vistas/FichaClienteController.php` | Modified | Add reparaciones(), contratos(), private baseQuery helper; slim show() |
| `resources/views/cliente.blade.php` | Modified | Remove 3 includes, add 2 navigation buttons |
| `resources/views/cliente/reparaciones.blade.php` | New | Reparaciones + cartola page |
| `resources/views/cliente/contratos.blade.php` | New | Contratos page |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| No automated tests | High | Manual browser verification of each page |
| $baseQuery logic duplication | Med | Extract to private method on controller |
| Named pagination changes to default `page` | Low | Natural behavior, no functional impact |
| Broken links if routes change | Low | Use named routes (`route()`) everywhere |

## Rollback Plan

Remove 2 new routes, 2 new views, and restore the 3 removed `@include` lines in `cliente.blade.php`. Revert `show()` method to original queries. All changes are additive — rollback is clean deletion.

## Success Criteria

- [ ] Main ficha page loads with only pendientes, modal, and transacciones sections
- [ ] Navigation buttons link to reparaciones+cartola and contratos pages
- [ ] Reparaciones+cartola page renders identical data to current inline version
- [ ] Contratos page renders identical data to current inline version
- [ ] Pagination works correctly on new pages (using default `page` param)
- [ ] No queries from removed sections run on the main ficha page