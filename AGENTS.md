# AGENTS.md — Development Conventions

## Loading Indicators Convention

All views that fetch server data via `fetch()` **MUST** use the loading indicator utilities to provide visual feedback to users.

### Required Utilities

Two functions are available in `public/assets/js/app.js`:

```js
// Show a Bootstrap spinner-border loading indicator inside a container
// For table bodies:  showElLoading(tbody, colspanNumber)
// For other containers (dropdowns, buttons, modals): showElLoading(container)
window.showElLoading(container, colspan)

// Remove the loading indicator
window.hideElLoading(container)
```

### Usage Rules

1. **Every `fetch()` call** in server-data views **MUST** be wrapped:
   ```js
   // BEFORE fetch
   showElLoading(targetContainer, colspan);

   // AFTER fetch (in both .then() and .catch())
   hideElLoading(targetContainer);
   ```

2. **For table `<tbody>` containers**: use `showElLoading(tbody, COLUMN_COUNT)`
   - The colspan should match the actual number of table columns
   - Example: `showElLoading(document.getElementById('body-pendientes'), 4)`

3. **For dropdown/autocomplete containers**: use `showElLoading(listElement)`
   - The spinner replaces the list content temporarily during fetch

4. **For buttons**: show spinner AND set `disabled` attribute
   ```js
   btn.disabled = true;
   showElLoading(btn);
   // ... fetch ...
   btn.disabled = false;
   hideElLoading(btn);
   ```

5. **For forms/modals**: disable all form fields AND show spinner on the modal body
   ```js
   form.querySelectorAll('input, select').forEach(el => el.disabled = true);
   showElLoading(modalBody);
   // ... fetch ...
   form.querySelectorAll('input, select').forEach(el => el.disabled = false);
   hideElLoading(modalBody);
   ```

### Index Table Placeholder Rows

All server-rendered index tables (`cobro/index`, `cliente/index`, `contrato/index`, etc.) **MUST** include a placeholder row that is removed on `DOMContentLoaded`:

```html
<tbody>
  <tr class="loading-placeholder">
    <td colspan="99" class="text-center py-4">
      <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
      Cargando...
    </td>
  </tr>
</tbody>
```

The `.loading-placeholder` rows are automatically removed by the `DOMContentLoaded` listener in `app.js`. No additional code needed in individual views.

### Page-Level Overlay (Initial Load Only)

For slow server-rendered pages, a **page-level overlay** can be added inside `<main>` in `layouts/app.blade.php`. This overlay:
- Is visible by default (covers `<main>` only, not sidebar/header)
- Uses 200ms debounce to prevent flicker on fast loads
- Is one-shot: removed permanently after `DOMContentLoaded`

See `resources/views/layouts/app.blade.php` for the current implementation.

### Files Using This Convention

| File | Pattern |
|------|---------|
| `public/assets/js/app.js` | `showElLoading`, `hideElLoading`, `.loading-placeholder` remover |
| `public/js/filtros.js` | `showElLoading(tableBody, 99)` / `hideElLoading(tableBody)` |
| `public/js/buscador.js` | `showElLoading(list)` / `hideElLoading(list)` |
| `resources/views/dashboard/index.blade.php` | `cargarPendientes`, `registrarPago`, buscador |
| `resources/views/administracion/create.blade.php` | `loadPropiedadesPorArrendador` |
| `resources/views/cobro/modal/create.blade.php` | `resolveCobroRelationships` |
| `resources/views/layouts/app.blade.php` | Page-level overlay |

## Database Protection Rule

**NEVER** run `php artisan migrate`, `migrate:fresh`, `migrate:reset`, `db:wipe`, or any command that drops or recreates database tables.

The project's MySQL database (`Corretaje` on port `3307`) is the single source of truth and contains real data. If the database needs to be restored:
1. Use the SQL dump at `C:\xampp\htdocs\src\corretaje-bd.sql`
2. Execute via: `Get-Content <path> -Raw | mysql -u root -P 3307 -p1234`

## Modal-Based User Feedback Convention

All user-facing messages, confirmations, and error displays **MUST** use the app's Bootstrap `flashModal` or custom modals. **Never** use native browser dialogs.

### Required Pattern

```javascript
// Show error in flashModal (Bootstrap modal)
function showWizardError(message) {
    var modalEl = document.getElementById('flashModal');
    var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    var header = document.getElementById('flashHeader');
    var title = document.getElementById('flashTitle');
    var body = document.getElementById('flashBody');
    if (header) {
        header.classList.remove('bg-success', 'text-white');
        header.classList.add('bg-danger', 'text-white');
    }
    if (title) title.innerText = 'Error';
    if (body) body.innerText = message;
    modal.show();
}

// Show confirmation (custom modal, NOT browser confirm)
// Create a confirmation modal in HTML and show/hide it programmatically
```

### Anti-Patterns (Strictly Forbidden)

- **Do NOT** use `alert()` — blocks the main thread, breaks UX flow, inconsistent with app design
- **Do NOT** use `confirm()` — blocks the main thread, cannot be styled, inconsistent with app design
- **Do NOT** use `prompt()` — same issues as alert/confirm

### Anti-Patterns (Avoid)

- **Do NOT** use inline `<div class="spinner-border...">` HTML strings in fetch callbacks — use `showElLoading`/`hideElLoading`
- **Do NOT** leave a `fetch()` without loading feedback — users need to know something is happening
- **Do NOT** stack multiple spinners — `showElLoading` clears existing indicators before adding new ones
- **Do NOT** use the page overlay for subsequent `fetch()` calls — use local spinners only
