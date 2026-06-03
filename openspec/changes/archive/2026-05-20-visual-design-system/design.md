# Design: Visual Design System

## Technical Approach

Six areas of change: CSS tokens → input stubs → button stubs → sidebar → icon fixes → spacing. All changes are independent — no ordering dependency between CSS, stubs, and sidebar. Existing generated views benefit from CSS immediately but won't get stub fixes until regenerated (deferred — user decision).

## Architecture Decisions

### 1. CSS Design Tokens

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Bootstrap defaults only | Zero custom CSS, but flat look | ❌ Rejected |
| 3 shadow vars in `:root` | Consistent elevation, ~20 lines | ✅ **Adopt** |

```css
:root {
  --shadow-sm: 0 1px 2px rgba(0,0,0,0.04), 0 1px 3px rgba(0,0,0,0.06);
  --shadow-md: 0 4px 6px rgba(0,0,0,0.04), 0 2px 4px rgba(0,0,0,0.05);
  --shadow-lg: 0 10px 15px rgba(0,0,0,0.04), 0 4px 6px rgba(0,0,0,0.05);
}
.card { box-shadow: var(--shadow-sm); }
.modal-content { box-shadow: var(--shadow-lg); }
```

Border-radius: keep Bootstrap defaults (`.card` already has `border-radius`). No new scale.

### 2. Input System

| Stub | Current | New classes |
|------|---------|-------------|
| `create-field.stub` | bare `<label>` + `<input>` | `mb-3` on div, `form-label` on label, `form-control` on input, `text-danger` on error |
| `edit-field.stub` | Same | Same |

Concrete change for both:
```blade
<div class="mb-3">
    <label class="form-label">{{label}}</label>
    <input type="{{input_type}}" name="{{field}}" class="form-control"
           value="{{ old('{{field}}') }}">
    @error('{{field}}') <span class="text-danger">{{ $message }}</span> @enderror
</div>
```
(Edit variant keeps `, ${{model}}->{{field}}` in old value for pre-fill.)

Width: Bootstrap grid handles it — no explicit width on inputs.

### 3. Button Hierarchy

| Context | Role | Class | Size |
|---------|------|-------|------|
| Full-page submit | Primary action | `btn btn-primary` | default |
| Full-page cancel | Secondary | `btn btn-outline-secondary` | `btn-sm` |
| Modal submit | Primary compact | `btn btn-primary btn-sm` | ✅ Correct |
| Index "Agregar" | Primary CTA | `btn btn-primary` | ✅ Correct |
| Table actions | View/Edit | `btn btn-sm btn-outline-primary` | ✅ Correct |
| Table delete | Danger | `btn btn-sm btn-outline-danger` | ✅ Correct |

Changes in `view-create.stub` and `view-edit.stub`:
```blade
<button type="submit" class="btn btn-primary">Guardar</button>
<a href="/{{route_base}}" class="btn btn-outline-secondary btn-sm">Cancelar</a>
```

### 4. Sidebar Icons

| Link | Tabler Icon |
|------|-------------|
| Inicio | `ti ti-home` |
| Agregar administración | `ti ti-building-plus` |

HTML pattern:
```blade
<li class="nav-item">
    <a class="nav-link" href="#">
        <i class="ti ti-home"></i>
        <span class="nav-text">Inicio</span>
    </a>
</li>
```

Fix: only "Inicio" has `active` class (remove duplicate from "Agregar administración").

### 5. Spacing System

| Context | Rule | Reasoning |
|---------|------|-----------|
| Between form fields | `mb-3` per field wrapper | Matches FK/enum/boolean stubs — universal consistency |
| Form action buttons | `mt-4 pt-3 border-top` | Visual separator before actions |
| Content padding | Bootstrap `.container` padding | Already correct |
| Section headings | Default `h2` margin | Already correct |

### 6. Depth / Visual Hierarchy

| Element | Treatment | CSS |
|---------|-----------|-----|
| Cards (form wrappers) | `.card` with `--shadow-sm` | `.card { box-shadow: var(--shadow-sm); }` |
| Modals | `.modal-content` with `--shadow-lg` | `.modal-content { box-shadow: var(--shadow-lg); }` |
| Input focus | Orange glow | `border-color: rgba(230,98,57,0.5); box-shadow: 0 0 0 0.2rem rgba(230,98,57,0.15);` |
| Button hover | Lift + enhanced shadow | `.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(230,98,57,0.3); }` |

## File Changes

| File | Action | Changes |
|------|--------|---------|
| `stubs/fragments/create-field.stub` | Modify | Add `mb-3`, `form-label`, `form-control`, `text-danger` |
| `stubs/fragments/edit-field.stub` | Modify | Same 4 classes |
| `stubs/view-create.stub` | Modify | Card wrapper, `btn btn-primary` on submit, `btn btn-outline-secondary btn-sm` on cancel |
| `stubs/view-edit.stub` | Modify | Same as create |
| `stubs/view-index.stub` | Modify | `bi bi-funnel` → `ti ti-filter` |
| `stubs/view-filter.stub` | Modify | `bi bi-funnel` → `ti ti-filter`, `bi bi-x-circle` → `ti ti-x` |
| `public/assets/css/style.css` | Modify | Add shadow vars (3), `.card`/`.modal-content` shadows, focus ring, button transitions + hover lift |
| `resources/views/layouts/partials/sidebar.blade.php` | Modify | Add Tabler icons, `<span class="nav-text">`, fix duplicate `active` |

## Testing Strategy

| Layer | What | How |
|-------|------|-----|
| Manual visual | Stubs produce valid HTML with classes | Load `cobro/create.blade.php`, inspect inputs/buttons in dev tools |
| Manual visual | CSS depth renders | Check card shadow, input focus glow, button hover in browser |
| Manual visual | Sidebar icons | Open sidebar, verify `ti ti-home` and `ti ti-building-plus` render |
| Manual visual | Icon replacements | Open index + filter views, verify `ti ti-filter` and `ti ti-x` render |

No automated test infrastructure exists. Visual regression would need Cypress/Playwright setup separately.

## Open Questions

- [ ] Sidebar nav links use `href="#"` (placeholder) — will actual routes be added, or keep placeholder?
- [ ] `<input type="select">` bug in edit view generator (enum fields fall through to simple field branch) — address in this change or defer as separate issue?
