# Tasks: Pendientes Dashboard Mobile Card Layout

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 1 (blade) + ~15 (CSS) = ~16 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Add `.table-card-mobile` class + dashboard CSS overrides | PR 1 | Single PR; desktop unchanged; mobile cards work |

## Phase 1: Implementation

- [x] 1.1 Add `table-card-mobile` class to `<table id="tabla-pendientes">` in `resources/views/dashboard/index.blade.php`
- [x] 1.2 Append dashboard-specific CSS overrides to `public/assets/css/style.css` after line 487: tfoot td display reset, button list spacing, pagination stacking

## Phase 2: Verification

- [ ] 2.1 Test mobile card layout (≤575.98px) in Chrome DevTools — verify `data-label` headers, button lists, pagination
- [ ] 2.2 Test desktop layout unchanged (≥576px) — table renders as normal columns
- [ ] 2.3 Test pagination functionality on mobile card mode
- [ ] 2.4 Test dynamically inserted rows via pagination — MutationObserver labels new cells
- [ ] 2.5 Verify no JS console errors during load and pagination