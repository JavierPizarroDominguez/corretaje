<?php

namespace Tests\Unit\Services;

use App\Services\TerminarContratoService;
use Carbon\Carbon;
use Tests\TestCase;

class TerminarContratoServiceTest extends TestCase
{
    /**
     * @dataProvider proportionalRentProvider
     */
    public function test_calculates_proportional_rent_with_real_month_length_and_exclusive_end(
        int $renta,
        string $fechaTermino,
        int $diaPago,
        int $expected
    ): void {
        $amount = TerminarContratoService::calculateProportionalRent(
            $renta,
            Carbon::parse($fechaTermino),
            $diaPago
        );

        $this->assertSame($expected, $amount);
    }

    public static function proportionalRentProvider(): array
    {
        return [
            '30-day month counts payment day inclusive and termination day exclusive' => [300000, '2026-04-10', 5, 50000],
            '28-day February divisor' => [280000, '2026-02-15', 1, 140000],
            '29-day leap February divisor' => [290000, '2024-02-15', 1, 140000],
            '31-day month divisor' => [310000, '2026-07-31', 1, 300000],
            'dia_pago clamps to shorter month and yields zero before clamped day' => [300000, '2026-04-20', 31, 0],
            'termination on payment day yields zero days' => [300000, '2026-06-05', 5, 0],
        ];
    }
}
