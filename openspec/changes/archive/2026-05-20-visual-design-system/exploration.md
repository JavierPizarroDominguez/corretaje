# Updated Exploration: Visual Design Rules

## Corrected Context

The previous exploration incorrectly attributed views to InfyOm. The ACTUAL stack is:

| Component | Technology | Role |
|-----------|-----------|------|
| **Model generation** | Reliese (`reliese/laravel`) | Generates Eloquent models from DB schema |
| **CRUD generation** | Custom generator (`app/Generator/`, command `php artisan gen:crud`) | Generates controllers, routes, ALL Blade views (create, edit, show, index, modals, components, filters) from stubs |
| **View templates** | `stubs/` directory (15+ `.stub` files) | Templates that the custom generator renders into Blade views |
| **Icons** | Tabler Icons (`@tabler/icons-webfont` 3.35.0 via CDN) | Icon system loaded in layout |
| **CSS framework** | Bootstrap 5.3 (CDN) + `public/assets/css/style.css` | Styling foundation |
| **Font** | Poppins (Google Fonts CDN) | Primary font |

**Key implication**: Since ALL views are generated from stubs, fixing the **stubs** fixes the root cause. Every generated view (cobro, cliente, contrato, transaccion, participante_cobro, participante_contrato) exhibits the same patterns because they all come from the same stubs. The sidebar (`resources/views/layouts/partials/sidebar.blade.php`) is the only hand-written view.

---

## Stub Analysis

### 1. `stubs/fragments/create-field.stub` — THE #1 offender
```blade
<div>
    <label>{{label}}</label>
    <input type="{{input_type}}" name="{{field}}" value="{{ old('{{field}}') }}">
    @error('{{field}}') <span>{{ $message }}</span> @enderror
</div>
```
**Problems**:
- ❌ **No `form-control`** on `<input>` — renders as browser-default unstyled input
- ❌ **No `form-label`** on `<label>` — no Bootstrap label styling
- ❌ **No `mb-3`** on wrapper — no vertical spacing
- ❌ **Error span has no class** — no `text-danger` styling
- **This stub is used for ALL simple fields** (text, number, date, datetime-local, email, etc.) in BOTH create views AND modal create views

### 2. `stubs/fragments/edit-field.stub` — Same problem, for edit views
```blade
<div>
    <label>{{label}}</label>
    <input type="{{input_type}}" name="{{field}}" value="{{ old('{{field}}', ${{model}}->{{field}}) }}">
    @error('{{field}}') <span>{{ $message }}</span> @enderror
</div>
```
**Problems**: Same as create-field.stub — no `form-control`, `form-label`, `mb-3`.
**Additional issue**: In the edit view generator (`StubRenderer::buildEditFormFields`), enum and boolean columns FALL THROUGH to the simple field branch, producing `<input type="select">` (invalid HTML — `<input type="select">` doesn't exist). This is a generator logic bug, not just a styling issue.

### 3. `stubs/view-create.stub` — Full-page create
```blade
@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Nuevo {{model_title}}</h2>
    <form method="POST" action="/{{route_base}}">
        @csrf
{{create_fields}}
        <button type="submit">Guardar</button>
        <a href="/{{route_base}}">Cancelar</a>
    </form>
</div>
@endsection
```
**Problems**:
- ❌ `<button type="submit">Guardar</button>` — **NO `btn btn-primary` classes** — renders as unstyled browser button
- ❌ `<a href="...">Cancelar</a>` — **No `btn` classes** — renders as plain text link

### 4. `stubs/view-edit.stub` — Full-page edit
```blade
<div class="container">
    <h2>Editar {{model_title}}</h2>
    <form method="POST" action="/{{route_base}}/{{pk_blade_segments}}">
        @csrf
        @method('PUT')
{{edit_fields}}
        <button type="submit">Guardar</button>
        <a href="/{{route_base}}/{{pk_blade_segments}}">Cancelar</a>
    </form>
</div>
```
**Problems**: Same as create — button and cancel link are unstyled.

### 5. `stubs/modal-create.stub` — Modal wrapper
```blade
<form method="POST" action="/{{route_base}}" id="form-modal-create-{{model}}">
    @csrf
{{create_fields}}
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
    </div>
</form>
```
**Analysis**: ✅ Submit button ALREADY has `btn btn-primary btn-sm`. The wrapper structure is minimal but functional. No cancel button (modal closes via `data-bs-dismiss`). The fields rendered inside `{{create_fields}}` come from `create-field.stub` so they share the same styling issues.

### 6. `stubs/view-index.stub` — Index view
```blade
<button type="button" class="btn btn-outline-secondary btn-sm" ...>
    <i class="bi bi-funnel"></i> Filtrar
</button>
<a href="/{{route_base}}/create" class="btn btn-primary">Agregar</a>
```
**Analysis**:
- ✅ "Agregar" uses `btn btn-primary` (no `btn-sm`) — good for primary CTA
- ❌ Filter button uses `<i class="bi bi-funnel">` — Bootstrap Icons icon, but Bootstrap Icons CDN is NOT loaded. Only Tabler Icons CDN is loaded. **This icon will not render.**
- ✅ Table actions use consistent `btn btn-sm btn-outline-primary` / `btn btn-sm btn-outline-danger`

### 7. `stubs/view-filter.stub` — Filter panel
```blade
<i class="bi bi-funnel"></i> Aplicar filtros
<i class="bi bi-x-circle"></i> Limpiar
```
**Problems**: Same Bootstrap Icons issue — `bi bi-funnel` and `bi bi-x-circle` won't render.

### 8. Already-well-styled fragments (no changes needed)

| Stub | Classes used | Status |
|------|-------------|--------|
| `create-field-fk-select.stub` | `mb-3`, `form-label`, `form-control`, `form-select` | ✅ Good |
| `create-field-fk-buscador.stub` | `mb-3`, `form-label`, `form-control` | ✅ Good |
| `create-field-enum.stub` | `mb-3`, `form-label`, `form-select` | ✅ Good |
| `create-field-boolean.stub` | `mb-3`, `form-label`, `form-select` | ✅ Good |

---

## Generated View Validation (confirmed against cobro/create.blade.php, cobro/edit.blade.php, cobro/modal/create.blade.php)

The generated views confirm EXACTLY the stub analysis:

**`cobro/create.blade.php`** (325 lines):
- Lines 9-13: `<div><label>Fecha Cobro</label><input type="datetime-local" ...>` — **no classes** ← from `create-field.stub`
- Lines 14-25: `<div class="mb-3"><label class="form-label">Estado</label><select class="form-select">...` ✅ from `create-field-enum.stub`
- Lines 49-53: `<div><label>Monto</label><input type="number" ...>` — **no classes**
- Lines 54-58: `<div><label>Detalle</label><input type="text" ...>` — **no classes**
- Lines 59-228: All FK fields have `mb-3`, `form-label`, `form-select`/`form-control` ✅
- Lines 263-264: `<button type="submit">Guardar</button>` — **no btn classes** ❌
- Lines 263-264: `<a href="/cobro">Cancelar</a>` — **raw anchor** ❌

**`cobro/edit.blade.php`** (301 lines):
- Lines 10-13: `<div><label>Fecha Cobro</label><input type="datetime-local" ...>` — **no classes**
- Lines 15-18: `<div><label>Estado</label><input type="select" ...>` — **no classes AND wrong HTML** (`<input type="select">` is invalid)
- Lines 239-240: `<button type="submit">Guardar</button>` — **no btn classes** ❌
- Lines 239-240: `<a href="/cobro/{{ $cobro->id }}">Cancelar</a>` — **raw anchor** ❌

**`cobro/modal/create.blade.php`** (276 lines):
- Lines 19-22: `<div><label>Fecha Cobro</label><input type="datetime-local" ...>` — **no classes**
- Lines 59-63: `<div><label>Monto</label><input type="number" ...>` — **no classes**
- Lines 273-275: `<button type="submit" class="btn btn-primary btn-sm">Guardar</button>` ✅ (good)

---

## Sidebar Analysis (`resources/views/layouts/partials/sidebar.blade.php`)

```blade
<aside id="sidebar" class="sidebar">
    <div class="d-flex align-items-center gap-2 px-3 py-3">
        <span class="brand-icon"><i class="ti ti-building-skyscraper"></i></span>
        <span class="brand-text">InApp</span>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link active" href="#">Inicio</a></li>
        <li class="nav-item"><a class="nav-link active" href="#">Agregar administración</a></li>
    </ul>
</aside>
```
**Problems**:
- ❌ **No icons on nav links** — CSS already has `.sidebar .nav-link .ti` with `gap: 12px` and `font-size: 18px`, but the icon `<i>` elements are missing
- Both links have `href="#"` (placeholder) and `class="nav-link active"` (both marked active)
- Brand icon exists correctly (`ti ti-building-skyscraper`)
- ✅ CSS already supports the full sidebar system: collapsed state (60px), mobile overlay, border-right, proper z-index

---

## CSS Analysis (`public/assets/css/style.css`, 489 lines)

**What's excellent**:
- Complete gray scale CSS variables (gray-50 through gray-950)
- Semantic color system mapped to Bootstrap variables (`--bs-primary: #E66239`)
- Orange primary (user said "no blue" — already correct)
- Avatar system with status indicators (online, offline, away, busy)
- Button icon sizes (`.btn-icon` with xs, sm, lg variants)
- Icon shape system (`.icon-xxs` through `.icon-xxxl`)
- Responsive table-card mode for mobile
- Custom spacer utilities (mb-6 through mb-11)
- Poppins font applied as `--bs-body-font-family`
- Overlay with backdrop-filter blur

**What's missing**:
| Feature | Currently | Needed |
|---------|-----------|--------|
| Box shadow on cards | None | `.card { box-shadow: ... }`, `.modal-content { box-shadow: ... }` |
| Box shadow on sidebar | None | Subtle shadow or keep border-only |
| Input focus ring styling | Bootstrap default | Custom focus ring matching orange primary |
| Button hover/active polish | Bootstrap default | Smoother transitions, subtle lift |
| Card container for forms | None — forms are bare `<div class="container">` | `.card` wrapper around forms |
| Section spacing | Nothing explicit | Consistent form section dividers |
| `.btn` custom variants | Only `.btn-icon` | Could add `.btn-soft-primary`, etc. |
| `.table` row hover | Bootstrap default | Already has `.table-hover` in views |
| `.badge` styling | Bootstrap default | Custom badge for status colors |

---

## Visual Design Rules (Proposed)

### 1. Color & Depth

Add a subtle elevation system using CSS variables:

```css
:root {
  /* Card/container shadows */
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  --shadow-md: 0 4px 6px rgba(0,0,0,0.05), 0 2px 4px rgba(0,0,0,0.04);
  --shadow-lg: 0 10px 15px rgba(0,0,0,0.05), 0 4px 6px rgba(0,0,0,0.04);
  --shadow-sidebar: none; /* Keep sidebar flat with border, or use shadow if preferred */
}
```

**Current color palette is OK** — orange primary (`#E66239`), neutral grays, no blue. Just needs depth.

### 2. Input Uniformity

**All input fields MUST have**:
- `form-control` class on `<input>`
- `form-label` class on `<label>`
- `mb-3` on wrapper `<div>`
- Error spans with `text-danger` class

**Affected stubs**:
| Stub | Current state | Fix |
|------|-------------|-----|
| `stubs/fragments/create-field.stub` | No classes | Add `form-control`, `form-label`, `mb-3`, `text-danger` |
| `stubs/fragments/edit-field.stub` | No classes | Add `form-control`, `form-label`, `mb-3`, `text-danger` |

**CSS addition**: Custom focus ring matching orange brand:
```css
.form-control:focus, .form-select:focus {
  border-color: rgba(230, 98, 57, 0.5);
  box-shadow: 0 0 0 0.2rem rgba(230, 98, 57, 0.15);
}
```

### 3. Button System

Define a clear button hierarchy:

| Role | Class | Size | Where Used |
|------|-------|------|-----------|
| Primary CTA | `btn btn-primary` | Default (no btn-sm) | Index "Agregar", full-page "Guardar" |
| Secondary action | `btn btn-outline-secondary` | `btn-sm` | Cancel, Volver |
| Primary compact | `btn btn-primary btn-sm` | Small | Modal submit, Filter apply |
| View/Review | `btn btn-sm btn-outline-primary` | Small | Table "Revisar" |
| Danger | `btn btn-sm btn-outline-danger` | Small | Delete actions |
| Filter toggle | `btn btn-outline-secondary btn-sm` | Small | Filter toggle button |

**Affected stubs**:
| Stub | Current | Fix |
|------|---------|-----|
| `stubs/view-create.stub` | `<button type="submit">Guardar</button>` | `<button type="submit" class="btn btn-primary">Guardar</button>` |
| | `<a href="/{{route_base}}">Cancelar</a>` | `<a href="/{{route_base}}" class="btn btn-outline-secondary btn-sm">Cancelar</a>` |
| `stubs/view-edit.stub` | Same | Same |
| `stubs/view-index.stub` | `btn btn-primary` (no btn-sm) | Keep as-is — correct for primary CTA |
| `stubs/modal-create.stub` | `btn btn-primary btn-sm` | ✅ Already correct |

**CSS additions**:
```css
.btn {
  transition: all 0.15s ease;
}
.btn-primary {
  box-shadow: 0 1px 2px rgba(230, 98, 57, 0.2);
}
.btn-primary:hover {
  box-shadow: 0 2px 4px rgba(230, 98, 57, 0.3);
  transform: translateY(-1px);
}
```

### 4. Sidebar Icons

Add Tabler icons to each nav link. CSS already supports the pattern:

```blade
<li class="nav-item">
    <a class="nav-link" href="#">
        <i class="ti ti-home"></i>
        <span class="nav-text">Inicio</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link" href="#">
        <i class="ti ti-building-plus"></i>
        <span class="nav-text">Agregar administración</span>
    </a>
</li>
```

- Fix `class="nav-link active"` — only ONE link should be active at a time (remove duplicate `active`)
- CSS already has `.sidebar .nav-link .ti` with proper sizing and gap

### 5. Icon Fix — Bootstrap Icons → Tabler Icons

The filter views use `bi bi-funnel` and `bi bi-x-circle` (Bootstrap Icons), but Bootstrap Icons CDN is NOT loaded. These icons are invisible.

Replace with Tabler equivalents:
| Current | Tabler replacement |
|---------|-------------------|
| `bi bi-funnel` | `ti ti-filter` |
| `bi bi-x-circle` | `ti ti-x` |

**Affected stubs**:
| Stub | Lines |
|------|-------|
| `stubs/view-index.stub` | `btn-toggle-filter` icon |
| `stubs/view-filter.stub` | Aplicar filtros + Limpiar buttons |

**Alternative**: Add Bootstrap Icons CDN (`bootstrap-icons` npm or CDN). But mixing two icon sets is suboptimal — prefer Tabler-only consistency.

### 6. Form Card Wrapper

Full-page create and edit views have bare `<div class="container">` with no card. Adding a card wrapper creates visual structure:

**In `stubs/view-create.stub` and `stubs/view-edit.stub`**:
```blade
<div class="container">
    <h2>Nuevo {{model_title}}</h2>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="/{{route_base}}">
                @csrf
                {{create_fields}}
                <div class="d-flex gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="/{{route_base}}" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
```

This gives:
- Visual separation from the page background
- Subtle shadow from Bootstrap's `.card` + custom CSS
- Form action buttons separated by a border-top
- Consistent with the component views pattern (which already use `card mb-4`)

### 7. Spacing System

Current spacing between form fields: **zero** for simple fields (no `mb-3`), `mb-3` for FK/enum/boolean fields. Fixing `create-field.stub` and `edit-field.stub` to include `mb-3` fixes this globally.

Additional spacing rules:
- Form action area: `mt-4 pt-3 border-top` (separator before buttons)
- Index page heading: `d-flex justify-content-between align-items-center mb-3` already correct
- Section headings: `<h2>` already correct
- No additional margins needed beyond Bootstrap's defaults + the `mb-3` fix

### 8. Responsive

**Current responsive behavior**:
- Sidebar: collapses to off-canvas on mobile (≤992px) — ✅ good
- Tables: `table-card-mobile` class converts to card layout on mobile (<576px) — ✅ good
- Form controls: iOS zoom prevention via `font-size: 16px` on mobile — ✅ good
- Pagination: smaller links on mobile — ✅ good

**Only gap**: The form fields in modals may overflow on very small screens. The `modal-dialog modal-lg` handles this reasonably, but the FK buscador fields have `position-relative` + `position-absolute w-100` dropdowns that need `z-index` management inside modals.

---

## Affected Files

### Stubs (fix these and ALL future generated views are correct)

| File | Changes |
|------|---------|
| `stubs/fragments/create-field.stub` | Add `form-control`, `form-label`, `mb-3`, `text-danger` on error span |
| `stubs/fragments/edit-field.stub` | Same + note: `htmlInputType` for enum fields renders as `type="select"` which is invalid HTML — may need generator logic fix |
| `stubs/view-create.stub` | Add `btn btn-primary` to submit; style cancel as `btn btn-outline-secondary btn-sm`; add card wrapper |
| `stubs/view-edit.stub` | Same as create |
| `stubs/view-index.stub` | Replace `bi bi-funnel` with `ti ti-filter` |
| `stubs/view-filter.stub` | Replace `bi bi-funnel` with `ti ti-filter`, `bi bi-x-circle` with `ti ti-x` |
| `stubs/component-inline-field.stub` | Add `form-control` to inline edit input, `btn btn-sm btn-outline-secondary` to edit button, `btn btn-sm btn-primary` to modify button |
| `stubs/modal-create.stub` | ✅ Already correct for buttons — no changes needed for submit |

### Hand-written Views (not generated from stubs)

| File | Changes |
|------|---------|
| `resources/views/layouts/partials/sidebar.blade.php` | Add Tabler icons (`ti ti-home`, `ti ti-building-plus`), fix duplicate `active` class, update mock `href`s |
| `resources/views/layouts/app.blade.php` | No changes needed — layout structure is sound |

### CSS

| File | Changes |
|------|---------|
| `public/assets/css/style.css` | Add shadow system (3 levels), card depth, button transitions & hover effects, custom focus ring for orange primary, form section spacing utilities |

---

## Approaches

| Approach | Description | Pros | Cons | Effort |
|----------|------------|------|------|--------|
| **A: Fix stubs only** | Fix the 5-6 problem stubs + CSS + sidebar. Do NOT touch existing generated views. | Minimal blast radius; fixes ALL future generations; preserves hand-edits; low risk. CSS immediately improves existing views visually. | Existing views still have bare inputs and buttons (no `form-control`, no `btn`). CSS cannot fix unstyled inputs — missing `form-control` means no Bootstrap styling at all. | **Medium** (6 stubs + CSS + sidebar) |
| **B: Fix stubs + regenerate ALL views** | Fix stubs, then run `php artisan gen:crud` for all 6 models. FileWriter safely merges (preserves hand-edits via `[GEN:START/END]` markers + checksum detection). | Complete fix — ALL views become consistent. Fastest execution for existing views. Safe due to FileWriter's partial merge logic. | Requires working database for schema introspection. Regeneration might expose existing generator bugs (like `<input type="select">`). If any view has hand-edits outside markers, they get overwritten (FileWriter warns first). | **Medium-High** (stubs + CSS + sidebar + regeneration of 6 models) |
| **C: Fix stubs + manual patching** | Fix stubs AND manually add classes to existing views using search-and-replace. | Full control; no regeneration risk; fixes everything. | Tedious (30+ files); repetitive work could be error-prone; stubs and manual fixes could diverge. | **High** (6 stubs + 30+ views + CSS + sidebar) |
| **D: CSS-only** | Add shadows, depth, focus rings, transitions to CSS. Do NOT touch stubs or views. | Zero risk; instant global improvement. | Cannot fix missing `form-control` on inputs — inputs remain unstyled. Cannot fix `btn` classes on submit buttons. Cannot fix sidebar icons. Poor ROI. | **Low** (CSS only, ~200 lines) |

---

## Recommendation

**Approach A: Fix stubs + CSS + sidebar** — with the caveat that existing generated views will still show raw inputs (no `form-control`) until the stubs are regenerated against them.

**Why**:
1. The stubs are the **source of truth** for ALL generated views. Fixing them is investing in the future — every time `gen:crud` runs, the output will be correct.
2. CSS depth/shadows apply to ALL views immediately, giving visual improvement even on currently-broken views.
3. The sidebar is hand-written and easy to fix in isolation.
4. Existing generated views can be regenerated later (Approach B) as a follow-up — or the user can choose to do it immediately by running `gen:crud` after stubs are fixed.
5. Approach C (manual patching of 30+ files) is not worth the effort when the same result comes from a `gen:crud` command.
6. The FileWriter's safe regeneration (checksums + partial merge) makes Approach B a safe, low-risk option as a follow-up step.

**If the user wants an immediate fix for existing views**, combine A + B: fix stubs → run `gen:crud` for all models → verify. The FileWriter will:
- Detect hand-edits (checksum mismatch) → ask before overwrite
- If hand-edited, do partial merge (replaces only `[GEN:START/END]` blocks)
- If no hand-edits, overwrite completely

**Deferred decision**: Whether to regenerate existing views or leave them is a user choice. The exploration recommends asking the user.

### Specific Plan

1. **Stubs** (6 files):
   - `stubs/fragments/create-field.stub`: Add `form-control`, `form-label`, `mb-3`, `text-danger`
   - `stubs/fragments/edit-field.stub`: Same
   - `stubs/view-create.stub`: Card wrapper, styled buttons
   - `stubs/view-edit.stub`: Card wrapper, styled buttons
   - `stubs/view-index.stub`: Replace `bi bi-funnel` → `ti ti-filter`
   - `stubs/view-filter.stub`: Replace `bi bi-funnel` → `ti ti-filter`, `bi bi-x-circle` → `ti ti-x`

2. **CSS** (`public/assets/css/style.css`, ~100-150 new lines):
   - Shadow system variables (`--shadow-sm`, `--shadow-md`, `--shadow-lg`)
   - Card depth: `.card { box-shadow: var(--shadow-sm); }`
   - Button transitions & hover effects
   - Custom orange focus ring for form controls

3. **Sidebar** (1 file):
   - Add `<i class="ti ti-home"></i>` and `<i class="ti ti-building-plus"></i>` with `<span class="nav-text">`
   - Fix duplicate `active` class

4. **Optionally**: Run `gen:crud` for all models to propagate stub fixes to existing views

---

## Risks

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|-----------|
| **Generator overwrites hand-edits on regeneration** | Low | Medium | FileWriter checks checksums and warns; partial merge preserves marker-delimited content. Manual confirmation required before overwrite. |
| **CDN version changes break styling** | Low | High | Bootstrap 5.3.3 and Tabler Icons 3.35.0 are specific versions — they won't auto-update. Only a deliberate CDN URL change breaks it. |
| **`<input type="select">` bug in edit view generator** | Confirmed (cobro/edit) | Medium | This is a pre-existing generator bug (enum fields falling through to simple field branch). Not caused by our changes, but could be addressed as a separate fix in the generator logic. |
| **Scope creep — more files than anticipated** | Low | Low | Only ~6 stubs + 1 CSS + 1 sidebar. Even with regeneration, only 6 models' CRUD views. Total file count under 20. |
| **Regeneration requires database connection** | Medium | Medium | The generator introspects the database schema. Without a working DB, regeneration cannot run. Manual patching becomes the fallback. |

---

## Ready for Proposal

**Yes** — the exploration is complete with corrected context. The findings clearly show:

1. The root cause is in the stubs, not InfyOm
2. Fixing 6 stubs + CSS + sidebar covers ALL visual design issues
3. Existing generated views will need regeneration to see the stub fixes (user decision)
4. The `<input type="select">` bug in edit views is a pre-existing generator issue

The orchestrator should present this to the user with:
1. The corrected context (stubs vs InfyOm)
2. The recommended approach (fix stubs + CSS + sidebar, optionally regenerate)
3. Confirmation that "no blue" is already respected
4. Ask: "Do you want to regenerate existing views after fixing stubs, or only fix stubs for future generations?"
5. Mention the `<input type="select">` bug in edit views as a separate issue
