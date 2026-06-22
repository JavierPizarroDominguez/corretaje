<?php

namespace Tests\Feature\Api;

use App\Models\Cobro;
use App\Models\Cliente;
use App\Models\ParticipanteCobro;
use App\Models\Transaccion;
use App\Models\TransaccionCobro;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PagarCobroControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Helper: create a minimal Cobro with participants for payment testing.
     */
    protected function crearCobroPagable(string $estado = 'Pendiente', int $monto = 500000): Cobro
    {
        $deudor = Cliente::create(['nombre' => 'Deudor Test', 'fecha_creacion' => now()]);
        $acreedor = Cliente::create(['nombre' => 'Acreedor Test', 'fecha_creacion' => now()]);

        $cobro = Cobro::create([
            'tipo' => 'Ingreso Renta Arrendatario',
            'monto' => $monto,
            'estado' => $estado,
            'fecha_cobro' => now(),
            'Contrato_id' => null,
            'Propiedad_id' => null,
            'Unidad_id' => null,
        ]);

        ParticipanteCobro::create([
            'Cobro_id' => $cobro->id,
            'Cliente_id' => $deudor->id,
            'rol' => 'Deudor',
            'monto' => $monto,
        ]);

        ParticipanteCobro::create([
            'Cobro_id' => $cobro->id,
            'Cliente_id' => $acreedor->id,
            'rol' => 'Acreedor',
            'monto' => $monto,
        ]);

        return $cobro;
    }

    public function test_pays_pendiente_cobro_successfully(): void
    {
        $cobro = $this->crearCobroPagable('Pendiente', 500000);

        $response = $this->postJson('/api/cobro/pagar', ['cobro_id' => $cobro->id]);

        $response->assertStatus(200)
            ->assertJsonStructure(['transaccion_id', 'cobro_estado'])
            ->assertJson(['cobro_estado' => 'Pagado']);

        // Verify Transaccion created
        $transaccionId = $response->json('transaccion_id');
        $this->assertNotNull($transaccionId);
        $this->assertDatabaseHas('transaccion', ['id' => $transaccionId]);

        // Verify TransaccionCobro pivot created
        $this->assertDatabaseHas('transaccion_cobro', [
            'Transaccion_id' => $transaccionId,
            'Cobro_id' => $cobro->id,
        ]);

        // Verify Cobro estado updated
        $this->assertDatabaseHas('cobro', ['id' => $cobro->id, 'estado' => 'Pagado']);
    }

    public function test_rejects_guarantee_refund_in_generic_payment_flow(): void
    {
        $cobro = $this->crearCobroPagable('Pendiente', 500000);
        $cobro->tipo = 'Devolución Garantía Arrendatario';
        $cobro->save();

        $response = $this->postJson('/api/cobro/pagar', ['cobro_id' => $cobro->id]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cobro_id']);

        $this->assertDatabaseHas('cobro', ['id' => $cobro->id, 'estado' => 'Pendiente']);
        $this->assertSame(0, TransaccionCobro::where('Cobro_id', $cobro->id)->count());
        $this->assertSame(0, Transaccion::count());
    }

    public function test_pays_vencido_cobro_successfully(): void
    {
        $cobro = $this->crearCobroPagable('Vencido', 300000);

        $response = $this->postJson('/api/cobro/pagar', ['cobro_id' => $cobro->id]);

        $response->assertStatus(200)
            ->assertJson(['cobro_estado' => 'Pagado']);

        // Verify Cobro estado updated
        $this->assertDatabaseHas('cobro', ['id' => $cobro->id, 'estado' => 'Pagado']);
    }

    public function test_rejects_already_paid_cobro(): void
    {
        $cobro = $this->crearCobroPagable('Pagado', 500000);

        $response = $this->postJson('/api/cobro/pagar', ['cobro_id' => $cobro->id]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cobro_id']);
    }

    public function test_rejects_anulado_cobro(): void
    {
        $cobro = $this->crearCobroPagable('Anulado', 500000);

        $response = $this->postJson('/api/cobro/pagar', ['cobro_id' => $cobro->id]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cobro_id']);
    }

    public function test_returns_404_for_nonexistent_cobro(): void
    {
        $response = $this->postJson('/api/cobro/pagar', ['cobro_id' => 99999]);

        $response->assertStatus(404);
    }

    public function test_validates_missing_cobro_id(): void
    {
        $response = $this->postJson('/api/cobro/pagar', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cobro_id']);
    }

    public function test_validates_non_integer_values(): void
    {
        $response = $this->postJson('/api/cobro/pagar', ['cobro_id' => 'abc']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cobro_id']);
    }
}
