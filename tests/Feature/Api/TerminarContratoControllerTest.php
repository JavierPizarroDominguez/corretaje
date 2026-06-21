<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Contrato;
use App\Models\DescuentoGarantia;
use App\Models\ParticipanteCobro;
use App\Models\ParticipanteContrato;
use App\Models\Propiedad;
use App\Models\Transaccion;
use App\Models\TransaccionCobro;
use App\Models\Unidad;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TerminarContratoControllerTest extends TestCase
{
    use DatabaseTransactions;

    private bool $createdDescuentoGarantiaTable = false;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-20 15:30:00'));

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

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_terminates_contract_with_positive_refund_discount_links_and_transaction(): void
    {
        $scenario = $this->createActiveContractScenario(500000);

        $response = $this->postJson("/api/contratos/{$scenario['contrato']->id}/terminar", [
            'descuentos' => [
                ['concepto' => 'Aseo Final', 'detalle' => 'Limpieza final', 'monto' => 50000],
                ['concepto' => 'Reparación', 'detalle' => 'Pintura muro', 'monto' => 30000],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'contrato_id' => $scenario['contrato']->id,
                'total_descuentos' => 80000,
                'monto_devolucion' => 420000,
                'devolucion_estado' => 'Pendiente',
            ]);

        $this->assertSame('2026-06-20 15:30:00', $scenario['contrato']->fresh()->fecha_termino->format('Y-m-d H:i:s'));

        $discountCobros = Cobro::where('Contrato_id', $scenario['contrato']->id)
            ->where('estado', 'Pagado')
            ->whereIn('tipo', ['Aseo Final', 'Reparación'])
            ->orderBy('monto')
            ->get();

        $this->assertCount(2, $discountCobros);
        $this->assertSame([30000, 50000], $discountCobros->pluck('monto')->all());
        $this->assertSame('Pintura muro', $discountCobros->firstWhere('tipo', 'Reparación')->detalle);

        $refund = Cobro::where('Contrato_id', $scenario['contrato']->id)
            ->where('tipo', 'Devolución Garantía Arrendatario')
            ->first();

        $this->assertNotNull($refund);
        $this->assertSame('Pendiente', $refund->estado);
        $this->assertSame(420000, $refund->monto);

        $this->assertSame(2, DescuentoGarantia::where('Cobro_Devolucion_id', $refund->id)->count());
        $this->assertSame(1, TransaccionCobro::where('Cobro_id', $refund->id)->count());
        $this->assertSame(1, Transaccion::where('monto', 420000)->count());
    }

    public function test_full_discount_creates_zero_paid_refund_without_transaction_rows(): void
    {
        $scenario = $this->createActiveContractScenario(500000);

        $response = $this->postJson("/api/contratos/{$scenario['contrato']->id}/terminar", [
            'descuentos' => [
                ['concepto' => 'Extra', 'detalle' => 'Reposición completa', 'monto' => 500000],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'total_descuentos' => 500000,
                'monto_devolucion' => 0,
                'devolucion_estado' => 'Pagado',
            ]);

        $refund = Cobro::where('Contrato_id', $scenario['contrato']->id)
            ->where('tipo', 'Devolución Garantía Arrendatario')
            ->first();

        $this->assertNotNull($refund);
        $this->assertSame('Pagado', $refund->estado);
        $this->assertSame(0, $refund->monto);
        $this->assertSame(1, DescuentoGarantia::where('Cobro_Devolucion_id', $refund->id)->count());
        $this->assertSame(0, Transaccion::count());
        $this->assertSame(0, TransaccionCobro::count());
    }

    public function test_excessive_discounts_are_rejected_without_partial_writes(): void
    {
        $scenario = $this->createActiveContractScenario(500000);
        $cobroCount = Cobro::count();
        $transactionCount = Transaccion::count();
        $transactionCobroCount = TransaccionCobro::count();
        $discountLinkCount = DescuentoGarantia::count();

        $response = $this->postJson("/api/contratos/{$scenario['contrato']->id}/terminar", [
            'descuentos' => [
                ['concepto' => 'Aseo Final', 'detalle' => 'Limpieza', 'monto' => 500001],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['descuentos']);

        $this->assertNull($scenario['contrato']->fresh()->fecha_termino);
        $this->assertSame($cobroCount, Cobro::count());
        $this->assertSame($transactionCount, Transaccion::count());
        $this->assertSame($transactionCobroCount, TransaccionCobro::count());
        $this->assertSame($discountLinkCount, DescuentoGarantia::count());
    }

    public function test_rejects_unknown_discount_concepts_and_non_integer_amounts(): void
    {
        $scenario = $this->createActiveContractScenario(500000);

        $response = $this->postJson("/api/contratos/{$scenario['contrato']->id}/terminar", [
            'descuentos' => [
                ['concepto' => 'Garantía', 'detalle' => 'Concepto inválido', 'monto' => 10000],
                ['concepto' => 'Aseo Final', 'detalle' => 'Monto decimal', 'monto' => 10000.50],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['descuentos.0.concepto', 'descuentos.1.monto']);

        $this->assertNull($scenario['contrato']->fresh()->fecha_termino);
        $this->assertSame(0, Cobro::where('Contrato_id', $scenario['contrato']->id)->count());
    }

    public function test_uses_contract_participants_for_discount_and_refund_cobros(): void
    {
        $scenario = $this->createActiveContractScenario(500000);

        $response = $this->postJson("/api/contratos/{$scenario['contrato']->id}/terminar", [
            'descuentos' => [
                ['concepto' => 'Aseo Final', 'detalle' => 'Limpieza final', 'monto' => 80000],
            ],
        ]);

        $response->assertStatus(200);

        $discount = Cobro::where('Contrato_id', $scenario['contrato']->id)
            ->where('tipo', 'Aseo Final')
            ->first();
        $refund = Cobro::where('Contrato_id', $scenario['contrato']->id)
            ->where('tipo', 'Devolución Garantía Arrendatario')
            ->first();

        $this->assertParticipant($discount, 'Deudor', $scenario['arrendatario']->id, 80000);
        $this->assertParticipant($discount, 'Acreedor', $scenario['arrendador']->id, 80000);
        $this->assertParticipant($refund, 'Deudor', $scenario['arrendador']->id, 420000);
        $this->assertParticipant($refund, 'Acreedor', $scenario['arrendatario']->id, 420000);
    }

    private function createActiveContractScenario(int $garantia): array
    {
        $seq = uniqid('terminar-', true);
        $arrendador = Cliente::create(['nombre' => "Arrendador {$seq}", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario {$seq}", 'fecha_creacion' => now()]);
        $corredor = Cliente::firstOrCreate(['id' => 1], ['nombre' => 'Corredor Principal', 'fecha_creacion' => now()]);
        $propiedad = Propiedad::create(['direccion' => "Propiedad {$seq}", 'propietario' => $arrendador->id]);
        $unidad = Unidad::create(['nombre' => "Unidad {$seq}", 'Propiedad_id' => $propiedad->id]);
        $contrato = Contrato::create([
            'Unidad_id' => $unidad->id,
            'administracion' => true,
            'renta' => 500000,
            'garantia' => $garantia,
            'fecha_inicio' => '2025-01-01 00:00:00',
            'fecha_termino' => null,
        ]);

        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $corredor->id, 'rol' => 'Corredor']);

        return compact('arrendador', 'arrendatario', 'corredor', 'propiedad', 'unidad', 'contrato');
    }

    private function assertParticipant(Cobro $cobro, string $rol, int $clienteId, int $monto): void
    {
        $participant = ParticipanteCobro::where('Cobro_id', $cobro->id)
            ->where('rol', $rol)
            ->first();

        $this->assertNotNull($participant);
        $this->assertSame($clienteId, $participant->Cliente_id);
        $this->assertSame($monto, $participant->monto);
    }
}
