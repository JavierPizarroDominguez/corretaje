# Proposal: Terminar Contrato con Garantía y Renta Proporcional

## Intent

Separar el término de contrato de la devolución final de garantía: al confirmar, se termina el contrato, se crea una devolución pendiente editable por 30 días y se cobran rentas proporcionales hasta la fecha de término.

## Scope

### In Scope
- Modal de advertencia inicial en `resources/views/components/contratos.blade.php`.
- Cobro pendiente `Devolución Garantía Arrendatario` reabierto desde pendientes con el modal de devolución.
- Crear `Ingreso Proporcional Renta Arrendatario` y `Egreso Proporcional Renta Arrendador` como tipos válidos en DB, `app/Models/Cobro.php`/enum fuente, validaciones y `config/cobro_roles.php`.
- Finalización posterior: `Devolver garantía` marca cobro pagado y genera transacción.

### Out of Scope
- Ejecutar migraciones o comandos destructivos.
- Rediseñar cobros pendientes fuera de este flujo.

## Capabilities

### New Capabilities
- None

### Modified Capabilities
- `contract-termination-guarantee`: término atómico, devolución pendiente, plazo 30 días, rentas proporcionales.
- `cobro-payment`: pago/finalización especial para devolución de garantía.
- `ficha-pendientes-mobile`: pendientes deben enrutar devolución de garantía al modal correcto.

## Approach

Backend: `TerminarContratoService` debe ser idempotente por contrato: si ya tiene `fecha_termino` o cobros de término, no duplica. La transacción de devolución se mueve a un endpoint/servicio de finalización. Se agrega migración planificada para ampliar el ENUM MySQL de `Cobro.tipo` y actualizar el schema SQLite de tests; no se ejecuta en esta fase. La fuente de verdad del enum es `corretaje-bd.sql`, tabla `Cobro`, columna `tipo`.

Tipos nuevos planificados en `Cobro.tipo`: `Ingreso Proporcional Renta Arrendatario` y `Egreso Proporcional Renta Arrendador`.

Fórmula proporcional: contar primero los días del mes de `fecha_termino` (incluye febrero bisiesto y meses de 31). `valor_dia = renta_mensual / dias_del_mes`. `dias_proporcionales = días desde dia_pago hasta fecha_termino, incluyendo dia_pago y excluyendo fecha_termino`. Ejemplo: renta 300.000, mes 30 días => 10.000 diario; pago día 5 y término día 10 => días 5,6,7,8,9 = 5; monto 50.000.

```pseudo
dias_mes = daysInMonth(fecha_termino)
dia_inicio = clamp(dia_pago, 1, dias_mes)
dias = max(0, day(fecha_termino) - dia_inicio)
monto = round(contrato.renta / dias_mes * dias)
```

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Services/TerminarContratoService.php` | Modified | Pending refund, proportional cobros, idempotency. |
| `app/Http/Controllers/Api/*Pendientes*`, ficha controllers | Modified | Include refund metadata and plazo restante. |
| `resources/views/components/contratos.blade.php`, dashboard/cliente/propiedad pendientes | Modified | Warning flow, special click routing, loading/modal conventions. |
| `app/Models/Cobro.php`, `config/cobro_roles.php`, `CobroController` | Modified | Central cobro types and role mapping. |
| `database/migrations`, `database/schema/testing.sqlite.sql` | Modified | Add proportional tipo values. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| ENUM drift between DB/model/views | Med | Single Cobro type source + tests. |
| Duplicate cobros on repeated termination | Med | Lock contract and detect existing termination cobros. |
| Formula business mismatch | Med | Spec locks month-days divisor and termination-day exclusivity. |
| `dia_pago` invalid in shorter month | Med | Clamp to last valid day and cover February/leap-year/31-day cases in specs/tests. |

## Rollback Plan

Revert code and migration; if applied, remove only the two new ENUM values after confirming no cobros use them.

## Test Strategy

PHPUnit feature tests for termination idempotency, proportional monto/participants, pending refund finalization, pending API metadata, and Blade/modal behavior. Unit-test formula edge cases: same-day, month wrap, February/leap year.

## Success Criteria

- [ ] Termination creates one pending guarantee refund and two proportional pending cobros.
- [ ] Pending refund opens devolution modal with `Plazo restante`.
- [ ] `Devolver garantía` creates exactly one transaction and marks cobro paid.
- [ ] No native dialogs; fetch flows use local loading indicators.

## Review Workload Forecast

Likely over 400 changed lines due backend + duplicated UI + tests. Chained PRs recommended: Yes.
