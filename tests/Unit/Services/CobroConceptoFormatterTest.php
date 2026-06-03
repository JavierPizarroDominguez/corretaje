<?php

namespace Tests\Unit\Services;

use App\Services\CobroConceptoFormatter;
use Carbon\Carbon;
use Tests\TestCase;

class CobroConceptoFormatterTest extends TestCase
{
    /**
     * @dataProvider formatoProvider
     */
    public function test_format_returns_correct_concepto(string $tipo, ?Carbon $fechaCobro, string $esperado): void
    {
        $resultado = CobroConceptoFormatter::format($tipo, $fechaCobro);
        $this->assertEquals($esperado, $resultado);
    }

    public static function formatoProvider(): array
    {
        $fecha = Carbon::parse('2025-05-15');

        return [
            // Renta — mes/año
            'Ingreso Renta Arrendatario con fecha' => [
                'Ingreso Renta Arrendatario',
                $fecha,
                'Cobrar renta mayo 2025',
            ],
            'Egreso Renta Arrendador con fecha' => [
                'Egreso Renta Arrendador',
                $fecha,
                'Transferir renta mayo 2025',
            ],
            // Comisiones — sin fecha
            'Comision inicial arrendador' => [
                'Comision inicial arrendador',
                null,
                'Comisión inicial',
            ],
            'Comision inicial arrendatario' => [
                'Comision inicial arrendatario',
                null,
                'Comisión inicial',
            ],
            // Garantías — sin fecha
            'Ingreso Garantía Arrendatario' => [
                'Ingreso Garantía Arrendatario',
                null,
                'Cobrar garantía',
            ],
            'Egreso Garantía Arrendador' => [
                'Egreso Garantía Arrendador',
                null,
                'Transferir garantía',
            ],
            // Utilidades — mes/año
            'Luz con fecha' => [
                'Luz',
                $fecha,
                'Luz mayo 2025',
            ],
            'Agua con fecha' => [
                'Agua',
                $fecha,
                'Agua mayo 2025',
            ],
            'Gas con fecha' => [
                'Gas',
                $fecha,
                'Gas mayo 2025',
            ],
            'Gastos comunes con fecha' => [
                'Gastos comunes',
                $fecha,
                'Gastos comunes mayo 2025',
            ],
            // Fallbacks
            'tipo desconocido con fecha devuelve raw tipo' => [
                'Extra Reparación',
                $fecha,
                'Extra Reparación',
            ],
            'tipo desconocido sin fecha devuelve raw tipo' => [
                'Extra Reparación',
                null,
                'Extra Reparación',
            ],
            // Null fecha → renta sin mes/año
            'Ingreso Renta Arrendatario sin fecha' => [
                'Ingreso Renta Arrendatario',
                null,
                'Ingreso Renta Arrendatario',
            ],
            'Egreso Renta Arrendador sin fecha' => [
                'Egreso Renta Arrendador',
                null,
                'Egreso Renta Arrendador',
            ],
            // Utilidades sin fecha
            'Luz sin fecha devuelve raw tipo' => [
                'Luz',
                null,
                'Luz',
            ],
            // Garantía sin fecha — aún produce concepto
            'Garantía Arrendatario sin fecha' => [
                'Ingreso Garantía Arrendatario',
                null,
                'Cobrar garantía',
            ],
        ];
    }
}