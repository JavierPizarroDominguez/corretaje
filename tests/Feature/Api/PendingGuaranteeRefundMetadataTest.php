<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\ParticipanteContrato;
use App\Models\Propiedad;
use App\Models\Unidad;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PendingGuaranteeRefundMetadataTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-20 15:30:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_pending_payload_identifies_guarantee_refunds(): void
    {
        $scenario = $this->createTerminatedContractScenario(500000);

        $response = $this->getJson('/api/dashboard/pendientes');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.arrendador.0.is_guarantee_refund', true)
            ->assertJsonPath('data.0.arrendador.0.contrato_id', $scenario['contrato']->id)
            ->assertJsonPath('data.0.arrendador.0.fecha_termino', '2026-06-20T15:30:00+00:00')
            ->assertJsonPath('data.0.arrendador.0.plazo_restante_dias', 30)
            ->assertJsonPath('data.0.arrendador.0.base_monto_devolucion', 500000);
    }

    public function test_cliente_and_propiedad_pending_payloads_identify_guarantee_refunds(): void
    {
        $scenario = $this->createTerminatedContractScenario(500000);

        $clienteResponse = $this->getJson("/api/cliente/{$scenario['arrendador']->id}/pendientes");
        $propiedadResponse = $this->getJson("/api/propiedad/{$scenario['propiedad']->id}/pendientes");

        $clienteResponse->assertStatus(200)
            ->assertJsonPath('data.0.arrendador.0.is_guarantee_refund', true)
            ->assertJsonPath('data.0.arrendador.0.contrato_id', $scenario['contrato']->id)
            ->assertJsonPath('data.0.arrendador.0.plazo_restante_dias', 30)
            ->assertJsonPath('data.0.arrendador.0.base_monto_devolucion', 500000);

        $propiedadResponse->assertStatus(200)
            ->assertJsonPath('data.0.arrendador.0.is_guarantee_refund', true)
            ->assertJsonPath('data.0.arrendador.0.contrato_id', $scenario['contrato']->id)
            ->assertJsonPath('data.0.arrendador.0.plazo_restante_dias', 30)
            ->assertJsonPath('data.0.arrendador.0.base_monto_devolucion', 500000);
    }

    private function createTerminatedContractScenario(int $garantia): array
    {
        $seq = uniqid('pending-refund-', true);
        $arrendador = Cliente::create(['nombre' => "Arrendador {$seq}", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario {$seq}", 'fecha_creacion' => now()]);
        $corredor = Cliente::firstOrCreate(['id' => 1], ['nombre' => 'Corredor Principal', 'fecha_creacion' => now()]);
        $propiedad = Propiedad::create(['direccion' => "Propiedad {$seq}", 'propietario' => $arrendador->id]);
        $unidad = Unidad::create(['nombre' => "Unidad {$seq}", 'Propiedad_id' => $propiedad->id]);
        $contrato = Contrato::create([
            'Unidad_id' => $unidad->id,
            'administracion' => true,
            'renta' => 300000,
            'garantia' => $garantia,
            'dia_pago' => 5,
            'fecha_inicio' => '2025-01-01 00:00:00',
            'fecha_termino' => null,
        ]);

        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $corredor->id, 'rol' => 'Corredor']);

        $this->postJson("/api/contratos/{$contrato->id}/terminar")->assertStatus(200);

        return compact('arrendador', 'arrendatario', 'corredor', 'propiedad', 'unidad', 'contrato');
    }
}
