# Proposal: Buscador Dashboard No Results

## Intent

Fix the global search bar on the dashboard so it successfully finds properties (`Propiedades`) and clients (`Clientes`). The current implementation sends queries without an entity flag, resulting in an empty response from the generic search controller. 

## Scope

### In Scope
- Modify `public/js/buscador.js` to accept a custom `url` configuration key.
- Update `resources/views/dashboard/index.blade.php` to pass `url: '/api/dashboard/buscador'` to the autocomplete widget.

### Out of Scope
- Fixing the separate bug where `cobro` and `contrato` searches fail in their respective index views.
- Changing `App\Http\Controllers\BuscadorController` or other backend routes.

## Capabilities

### New Capabilities
- None

### Modified Capabilities
- `buscador`: The shared `buscador.js` module now supports a customizable endpoint `url` via its config object.

## Approach

Implement Option A from the exploration phase: Add a `url` configuration key to `buscador.js` that defaults to `/buscador`. Update the dashboard view to provide `/api/dashboard/buscador` as the `url` config. This allows the dashboard to utilize the existing, correct `DashboardBuscadorController` without affecting the 30+ usages of the `buscador()` function elsewhere.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `public/js/buscador.js` | Modified | Adds support for `config.url` to override the default endpoint. |
| `resources/views/dashboard/index.blade.php` | Modified | Passes `url: '/api/dashboard/buscador'` to the component. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Existing `buscador()` callers break | Low | The `url` property is optional and defaults to `/buscador`, maintaining exact backward compatibility. |
| Dashboard search route is inaccessible | Low | The route `/api/dashboard/buscador` is an existing GET route; no CSRF token issues expected. |

## Rollback Plan

Revert the 1-line changes in `public/js/buscador.js` and `resources/views/dashboard/index.blade.php` via standard git undo.

## Dependencies

- None

## Success Criteria

- [ ] Typing a client name or property address in the dashboard search bar displays matching results.
- [ ] Clicking on a result correctly navigates to the returned URL (e.g., `/propiedad/ficha/{id}`).
- [ ] Searching on other entity pages using `buscador()` continues to function normally.