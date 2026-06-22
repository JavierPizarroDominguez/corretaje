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

    public function test_terminates_contract_with_pending_refund_and_proportional_cobros_only(): void
    {
        $scenario = $this->createActiveContractScenario(500000, 300000, 5);

        $response = $this->postJson("/api/contratos/{$scenario['contrato']->id}/terminar", [
            'descuentos' => [
                ['concepto' => 'Aseo Final', 'detalle' => 'Limpieza final', 'monto' => 50000],
                ['concepto' => 'Reparación', 'detalle' => 'Pintura muro', 'monto' => 30000],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'contrato_id' => $scenario['contrato']->id,
                'monto_devolucion' => 500000,
                'devolucion_estado' => 'Pendiente',
            ]);

        $this->assertSame('2026-06-20 15:30:00', $scenario['contrato']->fresh()->fecha_termino->format('Y-m-d H:i:s'));

        $refund = Cobro::where('Contrato_id', $scenario['contrato']->id)
            ->where('tipo', 'Devolución Garantía Arrendatario')
            ->first();

        $this->assertNotNull($refund);
        $this->assertSame('Pendiente', $refund->estado);
        $this->assertSame(500000, $refund->monto);
        $this->assertParticipant($refund, 'Deudor', $scenario['arrendador']->id, 500000);
        $this->assertParticipant($refund, 'Acreedor', $scenario['arrendatario']->id, 500000);

        $ingreso = Cobro::where('Contrato_id', $scenario['contrato']->id)
            ->where('tipo', 'Ingreso Proporcional Renta Arrendatario')
            ->first();
        $egreso = Cobro::where('Contrato_id', $scenario['contrato']->id)
            ->where('tipo', 'Egreso Proporcional Renta Arrendador')
            ->first();

        $this->assertNotNull($ingreso);
        $this->assertSame('Pendiente', $ingreso->estado);
        $this->assertSame(150000, $ingreso->monto);
        $this->assertParticipant($ingreso, 'Deudor', $scenario['arrendatario']->id, 150000);
        $this->assertParticipant($ingreso, 'Acreedor', $scenario['corredor']->id, 150000);

        $this->assertNotNull($egreso);
        $this->assertSame('Pendiente', $egreso->estado);
        $this->assertSame(150000, $egreso->monto);
        $this->assertParticipant($egreso, 'Deudor', $scenario['corredor']->id, 150000);
        $this->assertParticipant($egreso, 'Acreedor', $scenario['arrendador']->id, 150000);

        $this->assertSame(0, Cobro::where('Contrato_id', $scenario['contrato']->id)->whereIn('tipo', ['Aseo Final', 'Reparación'])->count());
        $this->assertSame(0, DescuentoGarantia::where('Cobro_Devolucion_id', $refund->id)->count());
        $this->assertSame(0, TransaccionCobro::where('Cobro_id', $refund->id)->count());
        $this->assertSame(0, Transaccion::count());
    }

    public function test_repeated_termination_is_idempotent_without_duplicate_cobros(): void
    {
        $scenario = $this->createActiveContractScenario(500000, 300000, 5);

        $first = $this->postJson("/api/contratos/{$scenario['contrato']->id}/terminar");
        $second = $this->postJson("/api/contratos/{$scenario['contrato']->id}/terminar");

        $first->assertStatus(200);
        $second->assertStatus(200)
            ->assertJsonPath('devolucion_cobro_id', $first->json('devolucion_cobro_id'))
            ->assertJsonPath('ingreso_proporcional_cobro_id', $first->json('ingreso_proporcional_cobro_id'))
            ->assertJsonPath('egreso_proporcional_cobro_id', $first->json('egreso_proporcional_cobro_id'));

        $this->assertSame(1, Cobro::where('Contrato_id', $scenario['contrato']->id)->where('tipo', 'Devolución Garantía Arrendatario')->count());
        $this->assertSame(1, Cobro::where('Contrato_id', $scenario['contrato']->id)->where('tipo', 'Ingreso Proporcional Renta Arrendatario')->count());
        $this->assertSame(1, Cobro::where('Contrato_id', $scenario['contrato']->id)->where('tipo', 'Egreso Proporcional Renta Arrendador')->count());
        $refund = Cobro::where('Contrato_id', $scenario['contrato']->id)->where('tipo', 'Devolución Garantía Arrendatario')->firstOrFail();
        $this->assertParticipant($refund, 'Deudor', $scenario['arrendador']->id, 500000);
        $this->assertParticipant($refund, 'Acreedor', $scenario['arrendatario']->id, 500000);
        $this->assertSame(0, DescuentoGarantia::count());
        $this->assertSame(0, Transaccion::count());
        $this->assertSame(0, TransaccionCobro::count());
    }

    public function test_discount_payload_is_ignored_at_termination(): void
    {
        $scenario = $this->createActiveContractScenario(500000);

        $response = $this->postJson("/api/contratos/{$scenario['contrato']->id}/terminar", [
            'descuentos' => [
                ['concepto' => 'Aseo Final', 'detalle' => 'Limpieza', 'monto' => 500001],
            ],
        ]);

        $response->assertStatus(200);

        $this->assertNotNull($scenario['contrato']->fresh()->fecha_termino);
        $this->assertSame(0, Cobro::where('Contrato_id', $scenario['contrato']->id)->where('tipo', 'Aseo Final')->count());
        $this->assertSame(0, DescuentoGarantia::count());
    }

    public function test_schema_dump_contains_proportional_cobro_types(): void
    {
        $schema = file_get_contents(base_path('corretaje-bd.sql'));

        $this->assertStringContainsString('Ingreso Proporcional Renta Arrendatario', $schema);
        $this->assertStringContainsString('Egreso Proporcional Renta Arrendador', $schema);
    }

    private function createActiveContractScenario(int $garantia, int $renta = 500000, int $diaPago = 1): array
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
            'renta' => $renta,
            'garantia' => $garantia,
            'dia_pago' => $diaPago,
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
