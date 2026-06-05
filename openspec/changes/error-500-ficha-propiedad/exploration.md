## Exploration: Error 500 en /propiedad/ficha/1

### Current State

The route `GET /propiedad/ficha/{id}` is defined in `routes/web.php` line 50 and handled by `FichaPropiedadController@show` in `app/Http/Controllers/Vistas/FichaPropiedadController.php`. This controller and its views were created as part of a previous change (`vista-ficha-propiedad`).

The `show($id)` method:
1. Loads the Propiedad with relations (`cliente`, `unidad`, `servicios`)
2. Builds a `baseQuery($id)` that scopes Cobros by 3 paths (direct, contrato→unidad, servicio)
3. Queries pending cobros (`pendientes`) using `baseQuery`
4. Queries `Transaccion` records that have related Cobros scoped to this propiedad
5. Renders `view('propiedad', ...)` with all variables

### Root Cause

**Case-sensitivity mismatch in pivot table name** — the model uses lowercase `'transaccion_cobro'` but the actual table in the database is `Transaccion_Cobro` (PascalCase).

The error is logged at `storage/logs/laravel.log` line 7700 (most recent entry, dated `2026-06-05 17:58:01`):

```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'corretaje.transaccion_cobro' doesn't exist
```

**Stack trace points to `FichaPropiedadController.php(120)`**, which is the `->paginate(20, ['*'], 'transacciones_page')` call on the Transaccion query.

The chain of failure:
1. `Transaccion::query()->whereHas('cobros', ...)` at line 105-120
2. The `cobros()` relationship in `Transaccion` model (line 67):
   ```php
   return $this->belongsToMany(Cobro::class, 'transaccion_cobro', 'Transaccion_id', 'Cobro_id')
   ```
3. The table name is hardcoded as `'transaccion_cobro'` (all lowercase)
4. The SQL dump (`corretaje-bd.sql` line 323) defines the table as:
   ```sql
   CREATE TABLE IF NOT EXISTS `Transaccion_Cobro` (...)
   ```
5. On MySQL Linux (default `lower_case_table_names=0`), table names are case-sensitive
6. `whereHas` generates `INNER JOIN transaccion_cobro` → table `Transaccion_Cobro` exists but `transaccion_cobro` doesn't → `SQLSTATE[42S02]` → HTTP 500

**Why it fails only for this table**: All models define `protected $table` matching the dump's PascalCase (e.g., `$table = 'Transaccion'`, `$table = 'Cobro'`). The pivot table name is the only hardcoded lowercase string — it's passed as a literal to `belongsToMany` instead of matching the dump casing.

### Confirmed Working Parts

- The controller method loads `$propiedad` successfully (error is at line 120, after Propiedad loading)
- The `pendientes` query (lines 57-97) succeeds
- All view files exist:
  - `resources/views/propiedad.blade.php` (main template)
  - `resources/views/components/titulo-propiedad.blade.php`
  - `resources/views/components/pendientes-propiedad.blade.php`
  - `resources/views/components/transacciones-propiedad.blade.php`
  - `resources/views/propiedad/modal/show.blade.php`
- The `baseQuery($id)` builds correctly for Cobro (no missing joins there)
- The table `Transaccion_Cobro` already exists in the database (from the SQL dump)

### Affected Areas

| File | Impact |
|------|--------|
| `app/Models/Transaccion.php` | Line 67: `belongsToMany(..., 'transaccion_cobro', ...)` — wrong casing |
| `app/Models/Cobro.php` | Line 113: `belongsToMany(..., 'transaccion_cobro', ...)` — wrong casing |
| `app/Http/Controllers/Vistas/FichaPropiedadController.php` | Line 105-120: query fails downstream of the relationship |
| `resources/views/components/transacciones-propiedad.blade.php` | Would render fine once the relationship works |

### Approaches

1. **Fix the pivot table casing in both models** (recommended)
   - Change `'transaccion_cobro'` → `'Transaccion_Cobro'` in both `belongsToMany` calls
   - Pros: Fixes the actual problem, simplest change, no DB mutation needed
   - Cons: None — the table already exists and matches the dump convention
   - Effort: Very Low (~2 min)

2. **Wrap Transaccion query in try/catch and degrade gracefully**
   - Catch `QueryException` and set `$transacciones` to an empty paginator
   - Pros: Handles any future DB issues gracefully
   - Cons: Hides the real problem, technical debt
   - Effort: Low (~10 min)

3. **Approach 1 only** — fix the case and move on
   - Cleanest: no safety net needed since the table exists and works once the name matches
   - Effort: Very Low (~2 min)

4. **Approach 1 + 2 combined**
   - Fix the casing AND add a safety catch in the controller
   - Best resilience: root cause fix + graceful degradation for edge cases
   - Effort: Low (~12 min)

### Recommendation

**Approach 1** — simply correct the pivot table name in both models. The table already exists in the database (created by `corretaje-bd.sql`). No migration, no new tables, no try/catch needed. The root cause is purely a string casing mismatch.

### Risks

- **MySQL `lower_case_table_names`**: If set to `1` on the production server, the query would work even with lowercase. But fixing the casing to match the dump is the correct approach regardless — it aligns with how every other model in the project references its table.

### Ready for Proposal

**Yes** — root cause is clearly identified, fix requires changing two strings in two model files. Scope is minimal and safe.
