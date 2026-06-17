# Exploration: fix-cobro-contexto

## Executive Summary

After creating a cobro from the cliente/propiedad ficha modal, the cobro's Deudor and Acreedor appear as "Desconocido" (or "Sin Deudor"/"Sin Acreedor") in the pendientes list and detail modal. The root cause is a three-phase controller flow that (1) creates ParticipanteCobro from user input, (2) **deletes them unconditionally** when `cliente_id` is present without `Contrato_id`, then (3) tries to auto-resolve them — but for manual tipos (Reparación/Devolución/Extra) the resolver returns `null` for both deudor/acreedor because they have no role mapping.

This manifests most commonly when a client has **multiple active contracts**: the AJAX resolver returns `contrato_id=null` (ambiguous), triggering the delete path, and Phase 3 cannot recreate the records. It also affects single-contract cases when the AJAX fails or the property owner has no matching contract.

---

## Files Investigated

| File | Role |
|------|------|
| `app/Http/Crud/CobroController.php` (lines 54-229) | Store method — 3-phase create flow |
| `app/Services/CobroRelationshipResolver.php` | Resolves contrato, deudor, acreedor from cliente+tipo |
| `app/Http/Api/CobroRelationshipController.php` | AJAX POST endpoint for resolver |
| `app/Models/Cobro.php` (lines 63-79) | `deudor()` / `acreedor()` hasOne relationships |
| `app/Models/ParticipanteCobro.php` (lines 53-56) | `getNombreAttribute()` accessor via `cliente` relationship |
| `resources/views/cobro/modal/create.blade.php` | The ficha-context create modal (hidden inputs, JS resolver) |
| `resources/views/cobro/modal/show.blade.php` | Detail modal — displays deudor/acreedor |
| `resources/views/components/pendientes.blade.php` | Cliente ficha — includes modal create with `fichaContext=true` |
| `resources/views/components/pendientes-propiedad.blade.php` | Propiedad ficha — includes modal create with `fichaContext=true` |
| `resources/views/layouts/app.blade.php` (lines 95-125) | `abrirModal()` — clones modal DOM, copies cliente_id |
| `app/Http/Vistas/FichaClienteController.php` (lines 314-323) | Builds `$participantOptions` from active contracts |
| `app/Http/Vistas/FichaPropiedadController.php` (lines 254-263) | Builds `$participantOptions` from active contracts |
| `config/cobro_roles.php` | Role map — manual tipos have `deudor_rol=null`, `acreedor_rol=null` |
| `tests/Unit/Services/CobroRelationshipResolverTest.php` | Existing tests — covers resolver but not controller path |

---

## Data Flow Analysis

### Phase 1: Cobro Creation + ParticipanteCobro (lines 102-171)

```
$cobro->save()                             (line 142)
→ Crea ParticipanteCobro Deudor            (lines 146-156)  — from $data['deudor_Cliente_id']
→ Crea ParticipanteCobro Acreedor          (lines 159-170)  — from $data['acreedor_Cliente_id']
```

These use the raw form input. In ficha context, the user selects deudor/acreedor from the participant `<select>` (populated server-side from `$participantOptions` or rebuilt client-side by the AJAX resolver). The `onchange` handler copies the selection into hidden `deudor_Cliente_id` / `acreedor_Cliente_id` inputs.

### Phase 2: Conditional Deletion (lines 176-178)

```php
if (!empty($data['cliente_id']) && empty($data['Contrato_id'])) {
    \App\Models\ParticipanteCobro::where('Cobro_id', $cobro->id)->delete();
}
```

**Triggers when:** `cliente_id` is present (always in ficha context) AND `Contrato_id` is empty/null.

`Contrato_id` comes from the hidden `input-create-contrato-id`, which is populated by the AJAX resolver. If the resolver returns `contrato_id=null`, this field is empty, and Phase 2 fires.

### Phase 3: Auto-Resolution (lines 184-215)

Same condition as Phase 2 — only fires when `cliente_id` present AND `Contrato_id` empty.

Calls `CobroRelationshipResolver::resolve($clienteId, $tipo, $propiedadId)` and creates ParticipanteCobro records from the resolver's `deudor_cliente_id` / `acreedor_cliente_id`.

---

## Root Cause A: Manual Tipos Have Null deudor/acreedor in Resolver

In `config/cobro_roles.php` (lines 118-138):

```php
'Reparación' => ['deudor_rol' => null, 'acreedor_rol' => null, ...],
'Extra'      => ['deudor_rol' => null, 'acreedor_rol' => null, ...],
'Devolución' => ['deudor_rol' => null, 'acreedor_rol' => null, ...],
```

The resolver routes these to `resolveManualTipo()` (line 49-50), which calls `buildSingleContractResolution()` **without** a `$roleMap` parameter (line 146). Inside that method (line 218):

```php
if ($roleMap) {  // FALSE for manual tipos
    $deudorClienteId = $this->resolveParticipantByRole(...);
    $acreedorClienteId = $this->resolveParticipantByRole(...);
}
```

**Result: For ALL manual tipos, the resolver always returns `deudor_cliente_id=null`, `acreedor_cliente_id=null`** regardless of contract status.

### Impact on Phase 3

When Phase 3 runs for a manual tipo:

```php
// Line 198: creates Deudor only if deudor_cliente_id is NOT empty
if (!empty($resolved['data']['deudor_cliente_id'])) {  // null → FALSE, SKIPPED
    ...create...
}

// Line 207: creates Acreedor only if acreedor_cliente_id is NOT empty
if (!empty($resolved['data']['acreedor_cliente_id'])) {  // null → FALSE, SKIPPED
    ...create...
}
```

**No ParticipanteCobro records are created.** The cobro ends up without deudor or acreedor.

---

## Root Cause B: When Does Phase 2+3 Fire?

### Scenario 1 — 1 active contract (MOST COMMON) ✅ Works
- AJAX resolver returns `contrato_id = 123` (from `buildSingleContractResolution`, line 229)
- Hidden field `input-create-contrato-id` = 123
- Form submits with `Contrato_id = 123`
- Phase 2 condition: `cliente_id` present ✅, but `Contrato_id = 123` (not empty) ❌ → **NOT triggered**
- Phase 1 records survive → Deudor and Acreedor work correctly

### Scenario 2 — Multiple active contracts ❌ BROKEN
- AJAX resolver returns `contrato_id = null` (ambiguous, line 266)
- Hidden field `input-create-contrato-id` = ''
- Form submits with `Contrato_id = ''`
- Phase 2 condition: `cliente_id` present ✅, `Contrato_id` empty ✅ → **TRIGGERED**
- User-selected Deudor/Acreedor ParticipanteCobro records **DELETED**
- Phase 3 runs, creates NO records (as per Root Cause A)
- **Result: Cobro has no Deudor or Acreedor → "Desconocido"**

### Scenario 3 — 0 active contracts (Cliente ficha) ✅ Blocked
- AJAX resolver returns error: "No active contracts and no propiedad_id provided."
- JS shows modal error, user cannot proceed → blocked by design

### Scenario 4 — 0 active contracts (Propiedad ficha, owner mismatch)
- `modal-cliente-id` = propietario ID (from `$propiedad->cliente->id`)
- AJAX resolver falls back to propietario: `contrato_id=null`, `deudor_cliente_id=propietarioId`, participants=[]
- JS rebuilds participant selects (empty), can't set `deudorSelect.value=propietarioId` (no matching option)
- Ficha validation: `deudor_Cliente_id` and `acreedor_Cliente_id` both required → `acreedor_Cliente_id` is empty → validation fails
- **Blocked by validation** (but UX is broken — user sees empty selects)

### Scenario 5 — 1 active contract + AJAX failure (rare)
- AJAX call fails, `Contrato_id` stays empty
- Form submits with `Contrato_id = ''`
- Same as Scenario 2: Phase 2 triggers, deletions happen, Phase 3 creates nothing
- **BROKEN** — same outcome

---

## Root Cause C: Missing `participants` Key in 0-Contract Fallback

In `resolveManualTipo()` (lines 117-136), the 0-contracts-with-propiedad return has `'participants' => []`.

But wait — `resolveUtilityTipo()` (line 61-105) does NOT have a `'participants'` key in its return. This is not directly related to the bug but is an inconsistency.

More importantly: the ficha JS code at line 399 checks `result.data.participants` which is `[]` (truthy) for the 0-contracts fallback case, so it enters the block and clears the selects. Combined with the fact that `deudor_cliente_id` IS set (propietarioId) but has no matching option in the select, the user gets a stale hidden input value while the visible select shows "Seleccione".

---

## Edge Cases Matrix

| Cliente Contracts | Ficha Type | PropiedadId | AJAX contrato_id | Phase 2 triggers? | Result |
|---|---|---|---|---|---|
| 1 | Cliente | N/A | 123 | No | ✅ Works |
| Multiple | Cliente | N/A | null | **Yes** | ❌ **Desconocido** |
| 0 | Cliente | N/A | error | N/A | ✅ Blocked (error) |
| 1 | Propiedad | Yes | 123 | No | ✅ Works |
| Multiple | Propiedad | Yes | null | **Yes** | ❌ **Desconocido** |
| 0 | Propiedad | Yes | null | **Yes** | ⚠️ Deudor set, Acreedor blocked by validation |

---

## Root Cause Summary (Exact Lines)

| Issue | File | Lines | Severity |
|-------|------|-------|----------|
| Phase 2 deletes all ParticipanteCobro when contrato_id is null | `CobroController.php` | 176-178 | **Critical** |
| Phase 3 auto-resolve creates no records for manual tipos | `CobroController.php` | 198-213 | **Critical** |
| Manual tipos resolver returns null deudor/acreedor | `CobroRelationshipResolver.php` | 111-151, 210-245 | **Root cause** |
| Missing role mapping for manual tipos | `config/cobro_roles.php` | 118-138 | **Design decision** |
| Multiple contracts return contrato_id=null with no UI disambiguation in ficha | `CobroRelationshipResolver.php` | 250-281 + `create.blade.php` 116 (d-none) | **Medium** |
| Participant selects cleared on 0-contracts fallback, stale hidden value | `cobro/modal/create.blade.php` | 399-411 | Low |

---

## Approach Comparison

### Approach A: Skip delete+auto-resolve when user provided participants
- Modify Phase 2 condition: only delete if `deudor_Cliente_id` AND `acreedor_Cliente_id` are BOTH absent (meaning no user intent)
- Phase 3 condition same guard
- **Pros**: Minimal change, preserves user intent, no resolver changes needed
- **Cons**: Assumes user-provided deudor/acreedor are correct
- **Effort**: Low (change 2 lines)

### Approach B: Fix resolver to return deudor/acreedor for manual tipos
- In `resolveManualTipo()`, when 1 contract is found, return the Arrendatario as deudor and Arrendador as acreedor (sensible defaults)
- **Pros**: Auto-resolve works correctly for manual tipos
- **Cons**: Changes resolver semantics, might not match user intent (manual repairs could be between any parties)
- **Effort**: Medium (resolver change + tests)

### Approach C: Multiple contracts — show property select in ficha context
- When `result.data.multiple` is true, show the hidden `create-propiedad-wrapper` so user can pick a contract
- Re-trigger resolve when property changes → get single contract → contrato_id set
- **Pros**: Fixes the root disambiguation problem
- **Cons**: UX change, only fixes multiple-contracts case
- **Effort**: Medium (JS changes)

### Approach D: Combined — Approach A + Approach C
- Skip delete when user provided participants (A)
- Show property select for multiple contracts (C)
- **Pros**: Covers both the multiple-contracts bug AND the edge case where Phase 2 shouldn't destroy user intent
- **Effort**: Medium

---

## Recommendation

**Approach D** — combined fix:

1. **Guard Phase 2/3** (low risk, immediate safety): Add an additional condition to the delete and auto-resolve paths so they're skipped when the user has explicitly submitted `deudor_Cliente_id` or `acreedor_Cliente_id`:
   ```php
   $hasUserParticipants = !empty($data['deudor_Cliente_id']) || !empty($data['acreedor_Cliente_id']);
   if (!empty($data['cliente_id']) && empty($data['Contrato_id']) && !$hasUserParticipants) { ... }
   ```

2. **Multiple contracts UI** (medium effort): When the resolver returns `multiple=true`, show the property select in ficha context so users can disambiguate. This gives the resolver enough info to return a single contract with `contrato_id`.

3. **Add controller tests** for all 5 scenarios (1 contract, multiple contracts, 0 contracts, AJAX failure, ficha contexts).

---

## Risks

- **Risk**: Guarding Phase 2/3 with `$hasUserParticipants` could mask cases where the auto-resolver should have been used but the form had stale hidden values. Mitigation: the ficha validation already requires both deudor/acreedor, so if they're present they're intentional.
- **Risk**: Showing the property select in ficha context adds UI complexity to a currently clean modal. Mitigation: only show when `multiple=true`, keep it hidden otherwise.
- **Risk**: The resolver's participants array for the 0-contracts fallback (`'participants' => []`) causes unnecessary select rebuilds. Mitigation: either omit the key (so JS falsy check skips it) or add explicit null check in JS.

---

## Ready for Proposal

**Yes.**

The exploration found the definitive root causes with precise line numbers. The recommended fix is well-scoped and directly addresses the bug without changing the resolver's domain logic for manual tipos. Next step: `sdd-propose` to define the change scope and approach formally.
