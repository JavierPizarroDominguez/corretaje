# Tasks: Fix HTTP 500 on /propiedad/ficha/{id} — Pivot Table Casing

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 2 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-always |
| Chain strategy | size-exception |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: Low

## Phase 1: Fix pivot table casing in Transaccion.php

- [x] 1.1 Change `'transaccion_cobro'` to `'Transaccion_Cobro'` in `belongsToMany` at `app/Models/Transaccion.php:67`

## Phase 2: Fix pivot table casing in Cobro.php

- [x] 2.1 Change `'transaccion_cobro'` to `'Transaccion_Cobro'` in `belongsToMany` at `app/Models/Cobro.php:113`

## Phase 3: Verify the fix

- [x] 3.1 Load `GET /propiedad/ficha/1` and confirm HTTP 200
- [x] 3.2 Verify `Transaccion::cobros` and `Cobro::transaccions` relationships resolve correctly
- [x] 3.3 Run `php artisan test` — all tests pass
