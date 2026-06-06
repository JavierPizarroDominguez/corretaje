# Tasks: Fix pivot table casing mismatches in Eloquent models

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~50 |
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
| 1 | Fix pivot casing in 4 models + expand tests | PR 1 | Single mechanical change, single PR |

## Phase 1: Model Fixes

- [x] 1.1 `app/Models/Cliente.php:99` — `telefono_cliente` → `Telefono_Cliente` in `telefonos()` belongsToMany
- [x] 1.2 `app/Models/Telefono.php:37` — `telefono_cliente` → `Telefono_Cliente` in `clientes()` belongsToMany
- [x] 1.3 `app/Models/Contrato.php:105` — `clausula_contrato` → `Clausula_Contrato` in `clausulas()` belongsToMany
- [x] 1.4 `app/Models/Clausula.php:41` — `clausula_contrato` → `Clausula_Contrato` in `contratos()` belongsToMany

## Phase 2: Test Coverage

- [x] 2.1 Add `test_cliente_telefonos_uses_correct_pivot_table_casing` — reflect Telefono_Cliente from Cliente::telefonos()
- [x] 2.2 Add `test_telefono_clientes_uses_correct_pivot_table_casing` — reflect Telefono_Cliente from Telefono::clientes()
- [x] 2.3 Add `test_contrato_clausulas_uses_correct_pivot_table_casing` — reflect Clausula_Contrato from Contrato::clausulas()
- [x] 2.4 Add `test_clausula_contratos_uses_correct_pivot_table_casing` — reflect Clausula_Contrato from Clausula::contratos()
- [x] 2.5 Add triangulation: Cliente + Telefono agree on Telefono_Cliente
- [x] 2.6 Add triangulation: Contrato + Clausula agree on Clausula_Contrato

## Phase 3: Verification

- [x] 3.1 Run `./vendor/bin/phpunit --filter=PivotTableCasing` — all 9 tests pass
- [x] 3.2 Run `./vendor/bin/phpunit` — full suite, no regressions from our change (56 pre-existing failures unrelated to pivot casing)
- [ ] 3.3 Smoke test: `GET /cliente/ficha/{id}` returns HTTP 200 (requires running server — manual)
