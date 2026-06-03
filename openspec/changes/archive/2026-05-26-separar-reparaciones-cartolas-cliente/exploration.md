## Exploration: Separar Reparaciones, Cartolas y Contratos de la Ficha de Cliente

### Current State

The "Ficha del Cliente" page (`resources/views/cliente.blade.php`) is a monolithic page served by `FichaClienteController::show($id)` at route `cliente/ficha/{id}`. It loads ALL client data in a single request and renders everything inline:

**cliente.blade.php includes:**
1. `components.pendientes` — pending transactions with "Agregar cobro" modal (line 4)
2. `components.reparaciones-propiedad` — reparaciones table with pagination (line 5)
3. `components.cartola` — financial ledger by unit/year/month (line 6)
4. `cliente.modal.show` — client detail edit modal (line 7)
5. `components.contratos` — active contracts cards (line 8)
6. `components.transacciones-propiedad` — transaction history table (line 9)

**FichaClienteController::show** does ALL of the following in one method:
- Loads cliente with 8 eager-loaded relationships
- Builds a base Cobro query filtered by cliente ID
- Queries pendientes (paginated, `pendientes_page`)
- Builds cartola structure (nested array: unit → year → month → column)
- Calculates `$columnasCartola` dynamically from actual data
- Queries reparaciones (paginated, `reparaciones_page`)
- Queries transacciones (paginated, `transacciones_page`)
- Queries contratosVigentes (collection, not paginated)
- Calculates tiposCobroDisponibles
- Prepares multiple dropdown option collections (clienteOptions, contratoOptions, servicioOptions, etc.)
- Passes **25+ variables** to the view

**Data dependencies per component:**
| Component | Variables needed | Source query |
|-----------|-----------------|-------------|
| pendientes | $pendientes, $cliente, $clienteOptions, $tiposCobroDisponibles | Clone of baseQuery |
| reparaciones-propiedad | $reparaciones | Clone of baseQuery, tipo filter |
| cartola | $cartola, $columnasCartola | Clone of baseQuery, custom grouping |
| contratos | $contratosVigentes | Separate Contrato query |
| transacciones-propiedad | $transacciones | Separate Transaccion query |
| cliente.modal.show | $cliente | Cliente eager load |

**No existing separate routes** for cliente-specific reparaciones, cartolas, or contratos. The generic CRUD routes (`contrato.index`, `cobro.index`) exist but are entity-wide, not scoped to a client.

### Affected Areas

- `resources/views/cliente.blade.php` — Remove inline includes, add navigation buttons
- `app/Http/Controllers/Vistas/FichaClienteController.php` — Split or refactor the massive `show()` method
- `routes/generated.php` — Add new routes (inside `[GEN:START:custom_routes]` block in `web.php` or in `generated.php` outside GEN blocks)
- NEW: `resources/views/cliente/reparaciones.blade.php` — New page for reparaciones
- NEW: `resources/views/cliente/cartola.blade.php` — New page for cartola
- NEW: `resources/views/cliente/contratos.blade.php` — New page for contratos
- `resources/views/components/reparaciones-propiedad.blade.php` — Reused as-is or adapted
- `resources/views/components/cartola.blade.php` — Reused as-is or adapted
- `resources/views/components/contratos.blade.php` — Reused as-is or adapted

### Approaches

1. **New Routes + Dedicated Blade Pages (Recommended)**
   - Create 3 new routes: `cliente/{id}/reparaciones`, `cliente/{id}/cartola`, `cliente/{id}/contratos`
   - Create 3 new controller methods (new controller `ClienteSeccionesController` or add to `FichaClienteController`)
   - Each method runs only its specific query and passes minimal data
   - Each new Blade page extends `layouts.app`, includes the existing component, and adds a "Volver" button
   - `cliente.blade.php` removes the 3 includes and adds `<a>` buttons linking to the new pages
   - The existing `@include` components remain unchanged — they receive the same variable names

   - Pros: Clean separation, each page loads faster (less data), follows existing Laravel pattern, components are reusable as-is, pagination works per-section without conflicting page params
   - Cons: More files to maintain, 3 new routes + 3 new views + 3 new controller methods
   - Effort: Medium

2. **Tab-Based UI (Single Page, Tab Navigation)**
   - Keep everything in `cliente.blade.php` but use Bootstrap tabs/nav-pills to separate sections
   - Use lazy loading (AJAX or Livewire) to load heavy sections only when tab is activated
   - No new routes needed for the tab content itself

   - Pros: No page navigation, "single page app" feel, fewer routes
   - Cons: All data still loads on initial request if not lazy-loaded, tab switching complexity, pagination conflicts across tabs, requires JavaScript for lazy loading, Bootstrap tabs don't match the project's current navigation style
   - Effort: Medium-High (requires JS for lazy loading, or all data still loads at once)

3. **Hybrid: Simplified Ficha + Section Routes (Slim middle ground)**
   - Keep pendientes + transacciones on the ficha page (they're smaller and contextually essential)
   - Move reparaciones + cartola to one combined page (they share the same baseQuery)
   - Move contratos to its own page
   - Result: 2 new routes instead of 3

   - Pros: Fewer new pages, groups related data (reparaciones + cartola are both financial), keeps the most contextually relevant info on the main ficha
   - Cons: reparaciones and cartola on the same page could still be heavy, less clean separation than Approach 1
   - Effort: Medium

### Recommendation

**Approach 1: New Routes + Dedicated Blade Pages**

Reasons:
1. The project already follows the Laravel resource route pattern (`/cliente/ficha/{id}`, `/contrato/{id}/edit`). Adding `cliente/{id}/reparaciones` etc. is consistent.
2. Each component currently receives its own isolated variable set — decoupling is natural, not forced.
3. Pagination already uses custom page names (`reparaciones_page`, `pendientes_page`, `transacciones_page`) which will work cleanly on separate pages.
4. The existing `@include` components (`reparaciones-propiedad`, `cartola`, `contratos`) can be reused as-is since they already accept isolated variable sets.
5. The massive `FichaClienteController::show()` method (336 lines!) would benefit from splitting into focused methods, improving readability and testability.

### Risks

- **Paginated data**: Each paginated section uses named page parameters (`reparaciones_page`, etc.). On separate pages, the default `page` query param works fine — no conflict risk. However, if any JavaScript depends on these specific param names, it must be updated.
- **`$tiposCobroDisponibles` and option collections**: Currently computed in FichaClienteController for the pendientes "Agregar cobro" modal. If pendientes stays on the ficha, these remain there. The new pages don't need them.
- **`$baseQuery` reuse**: The controller clones a baseQuery for multiple purposes. Splitting into separate methods means each method builds its own query — slightly duplicated query logic but clearer ownership. Could extract a private helper method.
- **SEO/Navigation**: Users arriving at `/cliente/ficha/5` need clear buttons to reach the new pages. Back-navigation ("Volver a ficha") must be obvious.
- **No automated tests**: The project has no E2E tests per sdd-init context, so visual/manual verification is required.

### Ready for Proposal

Yes — the exploration is complete. The next phase should propose the specific routes, controller structure, view changes, and navigation buttons. Key decisions for the user:
1. Should reparaciones + cartola be on the **same page** (they share data) or **separate pages**?
2. Should the new routes be in `FichaClienteController` or a new dedicated controller?
3. Should the ficha page still show pendientes + transacciones inline, or move those too?

### Key Files

- `resources/views/cliente.blade.php` — Main view to simplify
- `app/Http/Controllers/Vistas/FichaClienteController.php` — Controller to refactor/split
- `resources/views/components/reparaciones-propiedad.blade.php` — Component to reuse
- `resources/views/components/cartola.blade.php` — Component to reuse
- `resources/views/components/contratos.blade.php` — Component to reuse
- `resources/views/layouts/app.blade.php` — Layout to extend for new pages
- `routes/generated.php` — Routes to add (inside GEN:START:custom_routes or web.php custom block)
- `resources/views/components/pendientes.blade.php` — Stays on ficha (unaffected but relevant)
- `resources/views/components/transacciones-propiedad.blade.php` — Stays on ficha (unaffected but relevant)