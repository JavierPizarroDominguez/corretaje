<?php

namespace Tests\Feature\Models;

use App\Models\Cobro;
use App\Models\DescuentoGarantia;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class DescuentoGarantiaTest extends TestCase
{
    use DatabaseTransactions;

    private bool $createdDescuentoGarantiaTable = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('Descuento_Garantia')) {
            Schema::create('Descuento_Garantia', function (Blueprint $table): void {
                $table->unsignedBigInteger('Cobro_Devolucion_id');
                $table->unsignedBigInteger('Cobro_Descuento_id');
                $table->primary(['Cobro_Devolucion_id', 'Cobro_Descuento_id']);
            });

            $this->createdDescuentoGarantiaTable = true;
        }
    }

    protected function tearDown(): void
    {
        if ($this->createdDescuentoGarantiaTable) {
            Schema::dropIfExists('Descuento_Garantia');
        }

        parent::tearDown();
    }

    public function test_refund_cobro_reaches_linked_discount_cobros(): void
    {
        $refund = $this->createCobro('Devolución Garantía Arrendatario', 420000, 'Pendiente');
        $discount = $this->createCobro('Descuento Garantía: Aseo Final', 80000, 'Pagado');

        DescuentoGarantia::create([
            'Cobro_Devolucion_id' => $refund->id,
            'Cobro_Descuento_id' => $discount->id,
        ]);

        $link = $refund->descuentosGarantia()->first();

        $this->assertNotNull($link);
        $this->assertSame($discount->id, $link->descuento->id);
        $this->assertSame('Descuento Garantía: Aseo Final', $link->descuento->tipo);
    }

    public function test_discount_cobro_reaches_its_refund_cobro(): void
    {
        $refund = $this->createCobro('Devolución Garantía Arrendatario', 0, 'Pagado');
        $discount = $this->createCobro('Descuento Garantía: Reparación', 500000, 'Pagado');

        DescuentoGarantia::create([
            'Cobro_Devolucion_id' => $refund->id,
            'Cobro_Descuento_id' => $discount->id,
        ]);

        $link = $discount->devolucionGarantia;

        $this->assertNotNull($link);
        $this->assertSame($refund->id, $link->devolucion->id);
        $this->assertSame('Devolución Garantía Arrendatario', $link->devolucion->tipo);
    }

    private function createCobro(string $tipo, int $monto, string $estado): Cobro
    {
        return Cobro::create([
            'fecha_cobro' => now(),
            'estado' => $estado,
            'tipo' => $tipo,
            'monto' => $monto,
            'Contrato_id' => null,
            'Servicio_id' => null,
            'Propiedad_id' => null,
            'Unidad_id' => null,
        ]);
    }
}
