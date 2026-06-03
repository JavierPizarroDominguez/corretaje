# Tasks: Visual Design System

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~276 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | size-exception |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: Low

## Phase 1: Generator Bug Fix — Enum/Boolean in Edit Fields

- [x] 1.1 Create `stubs/fragments/edit-field-enum.stub` — `<select>` with edit pre-fill (`old('{{field}}', ${{model}}->{{field}})`)
- [x] 1.2 Create `stubs/fragments/edit-field-enum-option.stub` — enum `<option>` with edit pre-fill
- [x] 1.3 Create `stubs/fragments/edit-field-boolean.stub` — `<select>` Sí/No with edit pre-fill
- [x] 1.4 Fix `app/Generator/Rendering/StubRenderer.php` `buildEditFormFields()` — add enum and boolean branches (mirror `buildCreateFormFields`) so enum/boolean columns render `<select>` instead of `<input type="select">`

## Phase 2: Stub Visual Fixes

- [x] 2.1 Fix `stubs/fragments/create-field.stub` — add `mb-3` on div, `form-label` on label, `form-control` on input, `text-danger` on error span
- [x] 2.2 Fix `stubs/fragments/edit-field.stub` — same 4 classes as create-field
- [x] 2.3 Fix `stubs/view-create.stub` — wrap form in `.card > .card-body`, style submit as `btn btn-primary`, cancel as `btn btn-outline-secondary btn-sm`, add `mt-4 pt-3 border-top` separator before buttons
- [x] 2.4 Fix `stubs/view-edit.stub` — same card + button treatment as view-create
- [x] 2.5 Fix `stubs/view-index.stub` — replace `bi bi-funnel` with `ti ti-filter`
- [x] 2.6 Fix `stubs/view-filter.stub` — replace `bi bi-funnel` → `ti ti-filter`, `bi bi-x-circle` → `ti ti-x`

## Phase 3: CSS Depth System

- [x] 3.1 Add `--shadow-sm/md/lg` CSS variables in `:root` block
- [x] 3.2 Add `.card { box-shadow: var(--shadow-sm); }` and `.modal-content { box-shadow: var(--shadow-lg); }`
- [x] 3.3 Add custom orange focus ring: `.form-control:focus, .form-select:focus` with `border-color` and `box-shadow` matching `--bs-primary`
- [x] 3.4 Add button transitions (`.btn { transition: all 0.15s ease; }`) and hover lift (`.btn-primary:hover` with `translateY(-1px)` + enhanced shadow)

## Phase 4: Sidebar Icons & Active Class

- [x] 4.1 Fix `resources/views/layouts/partials/sidebar.blade.php` — add `<i class="ti ti-home">` to "Inicio" and `<i class="ti ti-building-plus">` to "Agregar administración" with `<span class="nav-text">` wrappers
- [x] 4.2 Fix duplicate `active` class — only "Inicio" should have `active`; remove from "Agregar administración"
