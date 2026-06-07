# Tasks: Ajustes Padding Cards Mobile

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 155–175 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-always |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Full implementation | PR 1 | CSS + 2 partials + 2 ficha views; self-contained frontend only |

## Phase 1: CSS Foundation

- [ ] 1.1 Add `.content` mobile padding rule at `@media (max-width: 575.98px)` in `public/assets/css/style.css` — `padding-left: 1rem; padding-right: 1rem;`
- [ ] 1.2 Add `.ficha-pendientes-mobile` card override block in `style.css` scoped to `@media (max-width: 575.98px)` — border, shadow, btn-cobro width/font styling (matching dashboard `#tabla-pendientes` patterns)

## Phase 2: Mobile Card Rendering in Partial Components

- [ ] 2.1 Restructure `resources/views/components/pendientes-propiedad.blade.php`: wrap existing `<table>` in `d-none d-sm-table-row` (desktop), add mobile-only `<div>` block with `d-sm-none` containing colored `btn-cobro` badges per `$cobro`, each carrying `data-cobro='@json([...])'` with id, concepto, tipo, estado (ucfirst), monto, fecha_cobro, deudor, deudor_id, acreedor, acreedor_id, servicio_id
- [ ] 2.2 Apply identical restructure to `resources/views/components/pendientes.blade.php` (cliente ficha) — same Bootstrap responsive classes, same badge structure, same JSON payload shape

## Phase 3: Cobro Detail Modal in Ficha Pages

- [ ] 3.1 Add `#modalCobro` markup to `resources/views/propiedad.blade.php` inside `@section('content')` — modal with header, body `#modal-body-cobro`, footer with `#btn-registrar`
- [ ] 3.2 Add `@push('scripts')` to `propiedad.blade.php` with: `registrarPago()` async function, click listener on `.btn-cobro` that parses `data-cobro`, populates modal body (tipo, deudor/acreedor links, formatted CLP monto via Intl.NumberFormat, formatted date), wires `#btn-registrar` to `registrarPago()`, shows bootstrap modal
- [ ] 3.3 Apply identical `#modalCobro` markup and `@push('scripts')` block to `resources/views/cliente.blade.php`

## Phase 4: Verification

- [ ] 4.1 Manual: Resize browser to ≤575.98px — verify `.content` has 1rem horizontal padding on ficha pages
- [ ] 4.2 Manual: Resize browser to ≥576px — verify 2-column table with "Revisar" button renders unchanged
- [ ] 4.3 Manual: On mobile ficha, tap colored badge — verify `#modalCobro` opens with correct tipo, deudor/acreedor links, formatted monto, formatted date
- [ ] 4.4 Manual: Tap "Registrar pago" in modal — verify navigation to cobro payment page
- [ ] 4.5 Manual: Visit dashboard — verify pendientes table unaffected

## Dependency Order

CSS (1) → Partial blade components (2) → Ficha views with modal (3) → Manual verification (4)

CSS must land first so mobile badge styling applies. Both partials (2.1, 2.2) are independent and can be done in parallel. Modal in ficha views (3.1–3.3) depends on understanding the badge JSON shape from Phase 2.