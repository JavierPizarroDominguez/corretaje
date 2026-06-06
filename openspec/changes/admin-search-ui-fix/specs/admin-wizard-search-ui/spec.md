# Delta for Admin Wizard Search UI

## ADDED Requirements

### Requirement: Mobile No-Results Suppression for Wizard Buscadores

The system MUST NOT display the "No se encontraron resultados" message inside the buscador dropdown lists for the arrendador (step 1) and arrendatario (step 2) buscadores when the viewport width is ≤ 767px.

This prevents the absolutely-positioned dropdown from overlaying and blocking the "Añadir" button on mobile screens.

#### Scenario: Mobile user searches with no matching results (arrendador)

- GIVEN the user is on step 1 of the admin wizard on a mobile device (viewport ≤ 767px)
- WHEN the user types a query in `#input-arrendador` that returns zero results
- THEN the dropdown `#lista-arrendador` remains empty (no "No se encontraron resultados" message displayed)
- AND the "Añadir" button remains fully visible and clickable

#### Scenario: Mobile user searches with no matching results (arrendatario)

- GIVEN the user is on step 2 of the admin wizard on a mobile device (viewport ≤ 767px)
- WHEN the user types a query in `#input-arrendatario` that returns zero results
- THEN the dropdown `#lista-arrendatario` remains empty (no "No se encontraron resultados" message displayed)
- AND the "Añadir" button remains fully visible and clickable

#### Scenario: Desktop user searches with no matching results

- GIVEN the user is on step 1 or 2 of the admin wizard on a desktop device (viewport > 767px)
- WHEN the user types a query that returns zero results
- THEN the "No se encontraron resultados" message IS displayed normally in the dropdown
- AND no UI elements are blocked

#### Scenario: Other buscadores unaffected

- GIVEN the user is on any other view with a buscador (deudor, acreedor, propietario, etc.)
- WHEN the user searches with no results on mobile
- THEN the "No se encontraron resultados" message displays normally
- AND only `#lista-arrendador` and `#lista-arrendatario` are affected by this suppression

### Requirement: Single Instance of Wizard Navigation and Resumen

The system MUST render exactly ONE navigation block ("Anterior" button) and ONE resumen panel per page load in `administracion/create.blade.php`.

#### Scenario: Page renders with single navigation block

- GIVEN the user navigates to `administracion/create`
- WHEN the page is rendered
- THEN there is exactly ONE `<div>` containing the "Anterior" button with `x-show="step > 1"`
- AND there is exactly ONE `<div id="resumen-wrapper">` element

#### Scenario: Navigation works correctly after duplicate removal

- GIVEN the user is on step 3 or later of the wizard
- WHEN the user clicks "Anterior"
- THEN the wizard navigates to the previous step exactly once
- AND no duplicate navigation events occur

## MODIFIED Requirements

### Requirement: Wizard Layout Structure

The `create.blade.php` view MUST contain a single occurrence of each structural block: progress steps, step content containers, navigation, and resumen panel. Duplicate blocks of navigation (lines 155-161) and resumen panel (lines 163-214) MUST be removed.

(Previously: The file contained duplicate navigation and resumen blocks from lines 155-214, causing redundant DOM elements and potential Alpine.js state conflicts.)

#### Scenario: DOM contains no duplicate wizard blocks

- GIVEN the `create.blade.php` file is rendered
- WHEN the DOM is inspected
- THEN `#resumen-wrapper` appears exactly once
- AND the "Anterior" button container appears exactly once
- AND no duplicate `<tr data-key="...">` rows exist in the resumen table

## REMOVED Requirements

None.

---

## Affected Files

| File | Change | Lines |
|------|--------|-------|
| `resources/views/administracion/create.blade.php` | Remove duplicate nav + resumen blocks | Lines 155-214 (60 lines removed) |
| `public/assets/css/style.css` | Add scoped mobile CSS rule for wizard buscadores | ~5 lines added |

## Scope Guard (What NOT to Change)

- `buscador.js` — NO modifications to the global buscador function
- Other buscadores (deudor, acreedor, propietario, etc.) — NO changes to their no-results behavior
- Wizard step flow, validation, or Alpine.js logic — NO changes
- Desktop buscador behavior — NO changes
- Resumen panel content or styling — NO changes beyond removing the duplicate

## Acceptance Criteria

1. **Mobile (≤ 767px)**: Typing in arrendador/arrendatario buscador with no results does NOT show "No se encontraron resultados" and does NOT block the "Añadir" button
2. **Desktop (> 767px)**: "No se encontraron resultados" displays normally for all buscadores
3. **DOM inspection**: `create.blade.php` renders exactly one `#resumen-wrapper` and one "Anterior" button container
4. **Regression**: All other buscadores across the app continue showing "No se encontraron resultados" on mobile as before
5. **Size**: Total changes ≤ 65 lines (well under 400-line PR budget)
