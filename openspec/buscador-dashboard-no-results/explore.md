# Exploration: buscador-dashboard-no-results

## Current State

The dashboard (`/`) shows a global search bar (`#searchInput`) meant to find clients and properties. The search is powered by `public/js/buscador.js`, a shared autocomplete module used across the entire app.

### How `buscador.js` works

The module always calls:
```
GET /buscador?q=<term>[&<tipo>=1]
```
Where `tipo` is the optional single-entity filter. The URL is **hardcoded** — there is no `url` config key the caller can override.

### How `BuscadorController` works

`/buscador` (handled by `App\Http\Controllers\BuscadorController`) returns results **only** when a known entity flag is present in the request:

| Request param | Entity searched | Field searched |
|---|---|---|
| `?unidad=1` | `Unidad` | `nombre` |
| `?cliente=1` | `Cliente` | `nombre` |
| `?nacionalidad=1` | `Nacionalidad` | `nombre` |
| `?ciudad=1` | `Ciudad` | `nombre` |

If **none** of those params are present, `$resultados` stays empty and the response is `{'data': []}`.

### How the dashboard calls `buscador`

```js
// dashboard/index.blade.php, lines 356–361
if (typeof buscador === 'function') {
    buscador({
        input: '#searchInput',
        list: '#autocomplete-list'
        // ← no `tipo` key
    });
}
```

`config.tipo` is `undefined`, so `params.append(config.tipo, '1')` is never executed. The fetch is `GET /buscador?q=<term>` with no entity flag → controller returns `[]` → "No se encontraron resultados."

### The ghost controller

There is already a purpose-built controller for this exact use case that is **completely disconnected**:

- **Controller**: `App\Http\Controllers\Api\DashboardBuscadorController`
- **Route**: `GET /api/dashboard/buscador` (registered in `routes/api.php`, line 22)
- **Behavior**: Searches `Propiedad.direccion` AND `Cliente.nombre`, returns combined results with priority ranking (starts-with before contains), max 10 results

This controller does exactly what the dashboard needs and already exists — it is just never called.

## Affected Areas

- `resources/views/dashboard/index.blade.php` — initializes `buscador()` without a URL or tipo; is the entry point of the bug
- `public/js/buscador.js` — hardcodes `/buscador` endpoint; has no `url` config option
- `app/Http/Controllers/Api/DashboardBuscadorController.php` — correct implementation, unused
- `app/Http/Controllers/BuscadorController.php` — generic entity controller, not designed for multi-entity dashboard search
- `routes/api.php` — `/api/dashboard/buscador` route exists but is never called

## Data Model Clarification

The dashboard searches two entity types:

| Entity | Model | Key field | Result URL |
|---|---|---|---|
| Property | `Propiedad` | `direccion` | `/propiedad/ficha/{id}` |
| Client | `Cliente` | `nombre` | `/cliente/ficha/{id}` |

`Unidad` is a sub-unit of `Propiedad` (a property has one Unidad, which has one contract). The dashboard talks about properties (`Propiedad`), not unidades. The correct model to search for "property by address" is `Propiedad.direccion`.

## Approaches

### Option A — Add a `url` config key to `buscador.js`

Extend `buscador.js` to accept an optional `url` config key. When present, it overrides `/buscador`. The dashboard then passes `url: '/api/dashboard/buscador'`.

```js
// buscador.js change: one line
const endpoint = config.url || '/buscador';
const res = await fetch(endpoint + '?' + params.toString());
```

```js
// dashboard/index.blade.php change
buscador({
    input: '#searchInput',
    list: '#autocomplete-list',
    url: '/api/dashboard/buscador'
});
```

- **Pros**: Minimal change (2 lines); zero risk to existing callers; leverages already-correct `DashboardBuscadorController`; no backend changes; clean separation (dashboard endpoint != generic buscador endpoint)
- **Cons**: Slightly non-obvious that the same widget can hit different endpoints (minor — well documented by the config key name)
- **Effort**: Low (< 10 lines total, 2 files)

### Option B — Add `?all=1` default fallback to `BuscadorController`

Add a default block to `BuscadorController` that runs when no entity flag is present, searching `Propiedad` + `Cliente`:

```php
if (empty($request->query()) || (!$request->has('unidad') && !$request->has('cliente') && ...)) {
    // search all entities
}
```

- **Pros**: No frontend change; single controller handles all cases
- **Cons**: Changes the shared generic controller behavior (risk of side effects); dashboard's "all entities" concept bleeds into the generic controller; `DashboardBuscadorController` becomes dead code; the generic controller already has a clean contract (explicit entity flags) — a fallback breaks that clarity
- **Effort**: Low-Medium (controller change + possible route conflict review)

### Option C — Pass multiple tipos in the dashboard `buscador` config

Current buscador.js supports only one `tipo`. To pass `tipo: ['unidad', 'cliente']` would require changing `buscador.js` to loop/append multiple params.

- **Pros**: Stays within the generic `/buscador` controller
- **Cons**: More invasive change to `buscador.js` (all callers are affected); `Propiedad` isn't a supported type in `BuscadorController` — the dashboard searches `Propiedad.direccion`, not `Unidad.nombre`; requires adding `propiedad` support to `BuscadorController` too; more lines, more risk
- **Effort**: Medium

## Recommendation

**Option A** — Add `url` config key to `buscador.js` and point the dashboard at `/api/dashboard/buscador`.

Reasons:
1. `DashboardBuscadorController` already exists, is already correct (priority ranking, right fields, right URLs), and is already routed. The bug is just a missing wire.
2. The change is surgical: 1 line in `buscador.js`, 1 line in `dashboard/index.blade.php`.
3. Zero risk to the 30+ existing `buscador()` calls across other views — they don't pass `url`, so they continue hitting `/buscador` unchanged.
4. The generic and dashboard endpoints remain conceptually separate — correct architecture.

## Risks

- **Dead-code confusion** (`DashboardBuscadorController` exists but looks unused): resolved by this fix — it becomes the active controller.
- **`/api` middleware**: `routes/api.php` in Laravel 10 applies the `api` middleware group by default. If the dashboard is behind `web` middleware and CSRF is required, the `GET /api/dashboard/buscador` route should work fine since it's a GET request — no CSRF token needed.
- **CORS**: Not applicable — both routes are on the same domain.
- **`/propiedad/ficha/{id}` and `/cliente/ficha/{id}` routes**: Both exist in `routes/web.php` (currently showing a `coming-soon` view). The buscador will navigate to them on click — acceptable for now, consistent with existing behavior.

## Additional Gotchas Found

1. **`cobro.index`, `cliente.index`, `contrato.index` pass `tipo: 'cobro'|'cliente'|'contrato'`** — but `BuscadorController` only handles `unidad`, `cliente`, `nacionalidad`, `ciudad`. `cobro` and `contrato` are silently ignored (no results for those tipos). This is a **separate pre-existing bug** — out of scope for this change.
2. **`buscador.js` line 39**: `params.append(config.tipo, '1')` — if `config.tipo` is `undefined`, `URLSearchParams` will actually append `undefined=1` to the query string (some browsers normalize undefined to the string `"undefined"`). The controller does not have a `?undefined` block so it harmlessly returns `[]`. Confirmed: this is the exact failure path.
3. **`DashboardBuscadorController` deduplication**: The controller correctly deduplicates starts-with vs contains matches using `collect($resultados)->contains('id', $item->id)`. This is already solid logic.
4. **Search scope for the dashboard**: The dashboard description says "Buscar cliente o propiedad" — `DashboardBuscadorController` searches exactly `Propiedad.direccion` and `Cliente.nombre`. This is the correct scope.

## Ready for Proposal

**Yes.** The root cause is confirmed, the fix is a 2-line change (+ removing the non-obvious `if (config.tipo)` guard that silently drops `undefined`), and the correct backend already exists. Recommend proceeding directly to `sdd-propose`.
