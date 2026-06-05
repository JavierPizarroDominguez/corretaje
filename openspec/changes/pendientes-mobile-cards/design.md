# Design: Pendientes Dashboard Mobile Card Layout

## Technical Approach

Add `.table-card-mobile` class to `<table id="tabla-pendientes">` in `resources/views/dashboard/index.blade.php` (line 46). The existing `.table-card-mobile` CSS pattern in `public/assets/css/style.css` (lines 449–487) + `labelTable()` MutationObserver in `public/assets/js/app.js` (lines 117–143) already handle the card transformation and dynamic row labeling — no JS changes required. Add dashboard-specific CSS overrides for: (1) `<tfoot>` pagination visibility and layout in card mode, (2) cobro button list spacing within card cells, (3) `text-nowrap` class override (already handled by existing `white-space: normal !important` on td).

## Architecture Decisions

### Decision: Reuse existing `.table-card-mobile` pattern

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Add `.table-card-mobile` class to table | Reuses proven CSS + MutationObserver; zero JS changes; consistent with ficha tables | **Chosen** |
| Dual JS render path (table desktop / cards mobile) | Full control but duplicate logic, fragile viewport detection, harder to maintain | Rejected |
| CSS-only with custom dashboard card classes | More CSS to write; reinvents existing pattern | Rejected |

**Rationale**: The existing pattern already handles dynamic rows via MutationObserver. The dashboard table is JS-rendered by `cargarPendientes()`, which appends `<tr>` elements — the observer fires on `childList` changes and auto-assigns `data-label`. Adding one class is the minimal change.

### Decision: No JS changes to `cargarPendientes()` or `labelTable()`

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Keep JS as-is; rely on MutationObserver | Zero JS risk; observer already watches `.table-card-mobile` tables | **Chosen** |
| Add explicit `labelTable()` call after row rendering | Redundant — observer already handles it | Rejected |

**Rationale**: `app.js` lines 137–143 initialize the MutationObserver on all `.table-card-mobile` tables at DOMContentLoaded. Once the class is added to `#tabla-pendientes`, the observer watches `#body-pendientes` and labels new rows automatically.

### Decision: CSS-only `<tfoot>` pagination fix

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Override tfoot td display in card-mode media query | Keeps pagination in table; simple CSS | **Chosen** |
| Move pagination outside table on mobile | Cleaner separation but requires JS/Blade changes | Rejected |

**Rationale**: The existing `.table-card-mobile` CSS targets `tbody td` with `display: flex`. The `<tfoot>` cells inherit this and break pagination layout. A targeted override for `.table-card-mobile tfoot td` resets to `display: table-cell` and restores normal flow.

## Data Flow

No data flow changes. The change is purely presentational:

```
API /api/dashboard/pendientes
  └─→ cargarPendientes() builds <tr> rows
        └─→ MutationObserver detects new <td> elements
              └─→ labelTable() assigns data-label from <thead>
                    └─→ CSS @media (≤575.98px) renders as cards
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `resources/views/dashboard/index.blade.php` | Modify | Add `table-card-mobile` to `<table>` class on line 46: `class="table mb-0 text-nowrap table-hover table-card-mobile"` |
| `public/assets/css/style.css` | Modify | Append dashboard-specific overrides after line 487: tfoot display reset, button list spacing, pagination stacking |

## Interfaces / Contracts

No API or interface changes. CSS selectors added within existing `@media (max-width: 575.98px)` block:

```css
/* Dashboard pendientes card overrides */
.table-card-mobile#tabla-pendientes tfoot td {
  display: table-cell !important;
  flex-direction: unset !important;
  justify-content: unset !important;
  align-items: unset !important;
  padding: 8px 4px;
}
.table-card-mobile#tabla-pendientes tfoot td .pagination {
  flex-wrap: wrap;
  justify-content: center;
}
.table-card-mobile#tabla-pendientes tbody td .mb-1 {
  margin-bottom: 0.25rem;
}
.table-card-mobile#tabla-pendientes tbody td .btn-cobro {
  font-size: 0.8rem;
  padding: 0.25rem 0.5rem;
}
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Visual | Cards render at ≤575.98px with data-label headers | Manual test in Chrome DevTools device mode (iPhone SE, Pixel 5) |
| Visual | Desktop layout (≥576px) unchanged | Manual test at 768px+ viewport |
| Functional | Pagination visible and clickable in card mode | Manual test — prev/next/page buttons work |
| Functional | Cobro buttons clickable, open modal correctly | Manual test — tap button, modal opens with cobro data |
| Functional | Dynamic column visibility (hayCol) works in cards | Test with data that omits roles — cards show only present columns |
| Regression | No JS errors in console | DevTools console check during page load and pagination |

## Migration / Rollout

No migration required. This is a CSS-only visual change plus one class addition. Rollback: remove `table-card-mobile` from the table class and delete the dashboard CSS override block — table reverts to `table-responsive` horizontal scroll.

## Open Questions

- [ ] Should pagination info text ("Mostrando X–Y de Z propiedades") stack above pagination buttons on very narrow screens (≤360px), or is center-aligned pagination sufficient?
