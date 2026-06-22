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

class GarantiaRefundControllerTest extends TestCase
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

    public function test_finalizes_positive_guarantee_refund_with_discount_links_and_one_transaction(): void
    {
        $scenario = $this->createTerminatedContractScenario(500000);

        $response = $this->postJson("/api/cobros/{$scenario['refund']->id}/devolver-garantia", [
            'descuentos' => [
                ['concepto' => 'Aseo Final', 'detalle' => 'Limpieza final', 'monto' => 50000],
                ['concepto' => 'Reparación', 'detalle' => 'Pintura muro', 'monto' => 30000],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'cobro_id' => $scenario['refund']->id,
                'cobro_estado' => 'Pagado',
                'monto_devolucion' => 420000,
            ]);

        $refund = $scenario['refund']->fresh();
        $this->assertSame('Pagado', $refund->estado);
        $this->assertSame(420000, $refund->monto);
        $this->assertSame(2, DescuentoGarantia::where('Cobro_Devolucion_id', $refund->id)->count());

        $discountCobros = Cobro::where('Contrato_id', $scenario['contrato']->id)
            ->whereIn('tipo', ['Aseo Final', 'Reparación'])
            ->orderBy('monto')
            ->get();

        $this->assertCount(2, $discountCobros);
        $this->assertSame([30000, 50000], $discountCobros->pluck('monto')->all());
        $this->assertSame(['Pagado', 'Pagado'], $discountCobros->pluck('estado')->all());

        $this->assertSame(1, TransaccionCobro::where('Cobro_id', $refund->id)->count());
        $pivot = TransaccionCobro::where('Cobro_id', $refund->id)->firstOrFail();
        $this->assertSame(420000, $pivot->monto_pagado);
        $this->assertSame(1, Transaccion::whereKey($pivot->Transaccion_id)->where('monto', 420000)->count());
    }

    public function test_finalizes_zero_guarantee_refund_without_transaction(): void
    {
        $scenario = $this->createTerminatedContractScenario(500000);

        $response = $this->postJson("/api/cobros/{$scenario['refund']->id}/devolver-garantia", [
            'descuentos' => [
                ['concepto' => 'Reparación', 'detalle' => 'Reposición total', 'monto' => 500000],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'cobro_id' => $scenario['refund']->id,
                'cobro_estado' => 'Pagado',
                'monto_devolucion' => 0,
                'transaccion_id' => null,
            ]);

        $this->assertDatabaseHas('cobro', ['id' => $scenario['refund']->id, 'estado' => 'Pagado', 'monto' => 0]);
        $this->assertSame(1, DescuentoGarantia::where('Cobro_Devolucion_id', $scenario['refund']->id)->count());
        $this->assertSame(0, TransaccionCobro::where('Cobro_id', $scenario['refund']->id)->count());
        $this->assertSame(0, Transaccion::count());
    }

    public function test_rejects_excessive_discounts_without_mutating_refund(): void
    {
        $scenario = $this->createTerminatedContractScenario(500000);

        $response = $this->postJson("/api/cobros/{$scenario['refund']->id}/devolver-garantia", [
            'descuentos' => [
                ['concepto' => 'Reparación', 'detalle' => 'Exceso', 'monto' => 500001],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['descuentos']);

        $this->assertDatabaseHas('cobro', ['id' => $scenario['refund']->id, 'estado' => 'Pendiente', 'monto' => 500000]);
        $this->assertSame(0, DescuentoGarantia::where('Cobro_Devolucion_id', $scenario['refund']->id)->count());
        $this->assertSame(0, TransaccionCobro::where('Cobro_id', $scenario['refund']->id)->count());
        $this->assertSame(0, Transaccion::count());
    }

    public function test_rejects_invalid_discount_concepts_before_finalization(): void
    {
        $scenario = $this->createTerminatedContractScenario(500000);

        $response = $this->postJson("/api/cobros/{$scenario['refund']->id}/devolver-garantia", [
            'descuentos' => [
                ['concepto' => 'Daños', 'detalle' => 'Concepto no permitido', 'monto' => 1000],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['descuentos.0.concepto']);

        $this->assertDatabaseHas('cobro', ['id' => $scenario['refund']->id, 'estado' => 'Pendiente', 'monto' => 500000]);
        $this->assertSame(0, DescuentoGarantia::where('Cobro_Devolucion_id', $scenario['refund']->id)->count());
        $this->assertSame(0, TransaccionCobro::where('Cobro_id', $scenario['refund']->id)->count());
        $this->assertSame(0, Transaccion::count());
    }

    public function test_rejects_duplicate_finalization_without_creating_new_rows(): void
    {
        $scenario = $this->createTerminatedContractScenario(500000);

        $first = $this->postJson("/api/cobros/{$scenario['refund']->id}/devolver-garantia", [
            'descuentos' => [
                ['concepto' => 'Aseo Final', 'detalle' => 'Limpieza', 'monto' => 50000],
            ],
        ]);
        $second = $this->postJson("/api/cobros/{$scenario['refund']->id}/devolver-garantia", [
            'descuentos' => [
                ['concepto' => 'Reparación', 'detalle' => 'Intento duplicado', 'monto' => 30000],
            ],
        ]);

        $first->assertStatus(200);
        $second->assertStatus(422)
            ->assertJsonValidationErrors(['cobro']);

        $this->assertDatabaseHas('cobro', ['id' => $scenario['refund']->id, 'estado' => 'Pagado', 'monto' => 450000]);
        $this->assertSame(1, DescuentoGarantia::where('Cobro_Devolucion_id', $scenario['refund']->id)->count());
        $this->assertSame(1, TransaccionCobro::where('Cobro_id', $scenario['refund']->id)->count());
        $this->assertSame(1, Transaccion::count());
        $this->assertSame(0, Cobro::where('Contrato_id', $scenario['contrato']->id)->where('tipo', 'Reparación')->count());
    }

    private function createTerminatedContractScenario(int $garantia): array
    {
        $seq = uniqid('refund-', true);
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

        $refund = Cobro::where('Contrato_id', $contrato->id)
            ->where('tipo', 'Devolución Garantía Arrendatario')
            ->firstOrFail();

        $this->assertParticipant($refund, 'Deudor', $arrendador->id, $garantia);
        $this->assertParticipant($refund, 'Acreedor', $arrendatario->id, $garantia);

        return compact('arrendador', 'arrendatario', 'corredor', 'propiedad', 'unidad', 'contrato', 'refund');
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
