# Spec: UI Loading Indicators

## Purpose

Reusable loading indicator pattern for all views that fetch server data — async `fetch()` calls and server-rendered slow pages.

---

## Requirement: Reusable JS loading utility

`showElLoading(el, colspan)` and `hideElLoading(el)` SHALL exist in `public/assets/js/app.js` as the single source of truth for spinners. `showElLoading` injects a Bootstrap `spinner-border`; `colspan` is optional for table rows.

#### Scenario: Spinner in table tbody

- GIVEN `<tbody id="t">`
- WHEN `showElLoading(t, 6)` is called
- THEN a `<tr>` with `spinner-border` spanning 6 columns is appended

#### Scenario: Hide removes spinner

- GIVEN tbody has a loading row
- WHEN `hideElLoading(tbody)` is called
- THEN the spinner row is removed

---

## Requirement: Page-level loading overlay (initial page load only)

`layouts/app.blade.php` MUST render a loading overlay during initial page load, hidden on `DOMContentLoaded`, with 200ms debounce to prevent flicker. This overlay applies ONLY to server-rendered pages that take time to generate HTML. It SHALL NOT block the page during subsequent `fetch()` calls.

#### Scenario: Overlay on slow initial load

- GIVEN Laravel takes >200ms to generate and send the HTML response
- THEN overlay with spinner is visible until `DOMContentLoaded` fires
- AND once hidden, it never reappears for that page session

#### Scenario: No flicker on fast load

- GIVEN cached or fast page loads in <200ms
- THEN overlay never becomes visible

#### Scenario: Fetch after DOMContentLoaded does not trigger overlay

- GIVEN `DOMContentLoaded` has already fired and overlay is hidden
- WHEN a `fetch()` begins (e.g., dashboard pendientes table)
- THEN the overlay MUST remain hidden
- AND only a local spinner inside the affected container is shown

---

## Requirement: Table placeholder rows

All server-rendered index tables MUST include a `<tr class="loading-placeholder">` with "Cargando..." in `<tbody>`, removed on `DOMContentLoaded`.

#### Scenario: Placeholder removed on DOM ready

- WHEN `DOMContentLoaded` fires
- THEN all `.loading-placeholder` rows are removed

---

## Requirement: Convention documented in AGENTS.md

`AGENTS.md` at project root MUST state: loading indicators are mandatory for all server-data views; use `showElLoading`/`hideElLoading`; every `fetch()` must wrap with these utilities.

---

## Requirement: filtros.js refactored

`public/js/filtros.js` MUST use `showElLoading`/`hideElLoading` from `app.js` — no inline spinner code SHALL remain.
