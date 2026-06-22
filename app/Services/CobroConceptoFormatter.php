<?php

namespace App\Services;

use Carbon\Carbon;

class CobroConceptoFormatter
{
    /**
     * Compute a human-readable concepto from cobro tipo and fecha_cobro.
     *
     * @param string       $tipo        Raw cobro tipo from DB
     * @param Carbon|null  $fechaCobro  Cobro date (may be null)
     * @return string                   Display name
     */
    public static function format(string $tipo, ?Carbon $fechaCobro): string
    {
        // Tipos that never use fecha_cobro
        switch ($tipo) {
            case 'Comision inicial arrendador':
            case 'Comision inicial arrendatario':
                return 'Comisión inicial';
            case 'Ingreso Garantía Arrendatario':
                return 'Garantía';
            case 'Egreso Garantía Arrendador':
                return 'Transferir garantía';
        }

        // Date-dependent tipos require fecha_cobro to produce a formatted string
        if ($fechaCobro === null) {
            return $tipo;
        }

        $mesAno = $fechaCobro->locale('es')->translatedFormat('F Y');

        switch ($tipo) {
            case 'Ingreso Renta Arrendatario':
                return "Renta $mesAno";
            case 'Egreso Renta Arrendador':
                return "Transferir renta $mesAno";
            case 'Ingreso Proporcional Renta Arrendatario':
                return "Renta proporcional $mesAno";
            case 'Egreso Proporcional Renta Arrendador':
                return "Transferir renta proporcional $mesAno";
            case 'Luz':
            case 'Agua':
            case 'Gas':
            case 'Gastos comunes':
                return "$tipo $mesAno";
            default:
                return $tipo;
        }
    }
}
