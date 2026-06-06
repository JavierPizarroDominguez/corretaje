# Design: Fix pivot table casing mismatches in Eloquent models

## Technical Approach

Mechanical string replacement: change 4 lowercase pivot table names in `belongsToMany()` calls to match the actual MySQL table casing. The database schema uses PascalCase (`Telefono_Cliente`, `Clausula_Contrato`); the model code passes lowercase (`telefono_cliente`, `clausula_contrato`). MySQL on Linux with `lower_case_table_names=0` rejects the lowercase form with SQLSTATE[42S02].

Each fix is a single string in the second argument of `belongsToMany()`. No logic changes, no new classes, no migrations.

## Architecture Decisions

| Option | Tradeoff | Decision |
|--------|----------|----------|
| **Change model strings** (4 edits, 0 risk) | Must keep model and DB in sync manually | ✅ **Chosen** — minimal footprint, follows Laravel convention |
| Add `$table` attribute or override on pivot model | Over-engineered for 2 simple pivot tables | ❌ Rejected — no custom pivot logic needed |
| Set `lower_case_table_names=1` on MySQL | Requires server config change, affects all queries, can't change after init | ❌ Rejected — DB is authoritative, don't patch the server |
| Snake_case DB table rename | Renames real tables, breaks other references | ❌ Rejected — risk outweighs benefit |

**Rationale for chosen approach**: The database schema is the source of truth. Eloquent's `belongsToMany()` accepts a string table name — pass the correct casing. This is exactly what commit `1322bc8` did for `Transaccion_Cobro`, establishing the proven pattern.

## Data Flow

```
HTTP GET /cliente/ficha/{id}
  → ClienteController@ficha($id)
    → $cliente->load('telefonos')          // lazy eager load
      → Eloquent JOIN telefono_cliente     ← lowercase → CRASH (42S02)
      → Eloquent JOIN Telefono_Cliente     ← PascalCase → OK
```

The fix affects the pivot table name string that Eloquent interpolates into the JOIN query. No other code paths touch these strings.

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Models/Cliente.php` | Modify | L99: `'telefono_cliente'` → `'Telefono_Cliente'` |
| `app/Models/Telefono.php` | Modify | L37: `'telefono_cliente'` → `'Telefono_Cliente'` |
| `app/Models/Contrato.php` | Modify | L105: `'clausula_contrato'` → `'Clausula_Contrato'` |
| `app/Models/Clausula.php` | Modify | L41: `'clausula_contrato'` → `'Clausula_Contrato'` |
| `tests/Unit/PivotTableCasingTest.php` | Modify | Add 4 test methods for new pivot relationships |

## Interfaces / Contracts

No new interfaces. The `BelongsToMany` relation contract is unchanged — only the internal `$table` property value changes. Both sides of each relationship must reference the same pivot table name to avoid silent mismatches.

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit (reflection) | `Cliente::telefonos()` uses `Telefono_Cliente` | Reflection on `BelongsToMany::$table` |
| Unit (reflection) | `Telefono::clientes()` uses `Telefono_Cliente` | Same reflection pattern |
| Unit (reflection) | `Contrato::clausulas()` uses `Clausula_Contrato` | Same reflection pattern |
| Unit (reflection) | `Clausula::contratos()` uses `Clausula_Contrato` | Same reflection pattern |
| Triangulation | Both sides of each pair agree on pivot name | Cross-assert within test method |
| Integration | Full test suite passes with no regressions | `./vendor/bin/phpunit` |

Test pattern mirrors `test_transaccion_cobros_uses_correct_pivot_table_casing()`: instantiate model, call relationship, reflect `$table` property, assert against the PascalCase string.

## Migration / Rollout

No migration required. Zero-downtime: the fix is purely code-side. Deploy the changed models and the next JOIN query uses the correct casing.

**Rollback**: Revert the 4 string changes. The HTTP 500 returns but no data corruption occurs.

**Verification**: `php artisan test --filter=PivotTableCasing` must pass. Manual smoke test: `GET /cliente/ficha/{id}` returns 200.

## Open Questions

None. The fix is mechanical, the pattern is proven (commit `1322bc8`), and all affected locations are identified.

### Coverage Confirmation

All `belongsToMany()` calls in `app/Models/` verified:

| Model | Pivot Table | Status |
|-------|-------------|--------|
| `Cobro` / `Transaccion` | `Transaccion_Cobro` | ✅ Fixed in 1322bc8 |
| `Cliente` / `Telefono` | `telefono_cliente` → `Telefono_Cliente` | 🔧 **This fix** |
| `Contrato` / `Clausula` | `clausula_contrato` → `Clausula_Contrato` | 🔧 **This fix** |
