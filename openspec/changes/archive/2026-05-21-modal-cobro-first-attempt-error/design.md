# Design: Modal cobro first-attempt error

## Technical Approach

Fix three interlocking bugs in the buscador search flow:
1. **BuscadorController** — Add `'id' => $item->id` to all result arrays; add query handlers for `contrato`, `servicio`, `propiedad`
2. **cobro/modal/create.blade.php** — Add hidden input setters to 4 `onSelect` callbacks (contrato, servicio, propiedad, unidad)

No new files, no migrations. Pure code fix following existing patterns.

## Architecture Decisions

### Decision: Add `id` to BuscadorController response

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Add `id` field to response | Minimal change; consistent API contract | **Chosen** |
| Extract ID from `item.url` in JS | Fragile parsing; different URL patterns per entity | Rejected |

**Rationale**: The API should provide the primary key directly. Parsing IDs from URL strings (`/cliente/5`) is error-prone and couples frontend to URL routing conventions.

### Decision: Search fields per entity type

| Entity | Search Field | Rationale |
|--------|-------------|-----------|
| contrato | `id` (LIKE) | No user-friendly text field; users search by contract number |
| servicio | `tipo` | Matches existing pattern (search by service type: "comision", "luz", etc.) |
| propiedad | `direccion` | Primary user-facing identifier for properties |

### Decision: Follow existing code patterns

The controller uses inline `if ($request->has('type'))` blocks with direct model queries. New handlers follow this exact pattern rather than refactoring to a strategy pattern — scope is limited to bug fix, not architecture overhaul.

## Data Flow

```
User types in buscador input
    │
    ▼
buscador.js → GET /buscador?q={query}&{tipo}=1
    │
    ▼
BuscadorController::index()
    ├── has('contrato') → Contrato::where('id', 'LIKE', "%q%")->limit(10)
    ├── has('servicio') → Servicio::where('tipo', 'LIKE', "%q%")->limit(10)
    ├── has('propiedad') → Propiedad::where('direccion', 'LIKE', "%q%")->limit(10)
    ├── has('unidad')   → Unidad::where('nombre', 'LIKE', "%q%")->limit(10)
    └── has('cliente')  → Cliente::where('nombre', 'LIKE', "%q%")->limit(10)
    │
    ▼
Response: [{ id, tipo, texto, url }, ...]
    │
    ▼
onSelect(item) → display.value = item.texto
               → hidden.value = item.id    ← THE FIX
    │
    ▼
Form POST → hidden input submits valid integer ID → validation passes
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Http/Controllers/BuscadorController.php` | Modify | Add `'id' => $item->id` to unidad/cliente result arrays; add 3 new query blocks for contrato, servicio, propiedad |
| `resources/views/cobro/modal/create.blade.php` | Modify | Add `document.getElementById('input-create-{entity}-id').value = item.id;` to contrato, servicio, propiedad, unidad onSelect callbacks |

## Interfaces / Contracts

### BuscadorController Response Format (all entity types)

```json
{
  "data": [
    {
      "id": 5,
      "tipo": "contrato",
      "texto": "5",
      "url": "/contrato/5"
    }
  ]
}
```

### New Handler Patterns

```php
// Contrato — search by id (numeric)
if ($request->has('contrato')) {
    $resultados_contrato = \App\Models\Contrato::query()
        ->where('id', 'LIKE', "%{$q}%")
        ->limit(10)
        ->get();
    foreach ($resultados_contrato as $item) {
        $resultados[] = [
            'id'    => $item->id,
            'tipo'  => 'contrato',
            'texto' => $this->getSearchText($item, ["id"]),
            'url'   => '/contrato/' . $item->id,
        ];
    }
}

// Servicio — search by tipo
if ($request->has('servicio')) {
    $resultados_servicio = \App\Models\Servicio::query()
        ->where('tipo', 'LIKE', "%{$q}%")
        ->limit(10)
        ->get();
    foreach ($resultados_servicio as $item) {
        $resultados[] = [
            'id'    => $item->id,
            'tipo'  => 'servicio',
            'texto' => $this->getSearchText($item, ["tipo"]),
            'url'   => '/servicio/' . $item->id,
        ];
    }
}

// Propiedad — search by direccion
if ($request->has('propiedad')) {
    $resultados_propiedad = \App\Models\Propiedad::query()
        ->where('direccion', 'LIKE', "%{$q}%")
        ->limit(10)
        ->get();
    foreach ($resultados_propiedad as $item) {
        $resultados[] = [
            'id'    => $item->id,
            'tipo'  => 'propiedad',
            'texto' => $this->getSearchText($item, ["direccion"]),
            'url'   => '/propiedad/' . $item->id,
        ];
    }
}
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | BuscadorController returns `id` for all entity types | PHPUnit feature test with mocked models |
| Unit | BuscadorController handles contrato/servicio/propiedad queries | PHPUnit feature test with query parameter assertions |
| Integration | onSelect callbacks set hidden inputs | Manual verification or browser test |
| Regression | Existing unidad/cliente searches still work | Re-run existing BuscadorScopedRelationsTest |

## Migration / Rollout

No migration required. Pure code change — deploy and verify.

## Open Questions

- [ ] Should `cobro/create.blade.php` (non-modal) also be fixed in this change? It has the same `item.id` pattern for deudor/acreedor. **Decision**: Out of scope per proposal; audit as follow-up.
