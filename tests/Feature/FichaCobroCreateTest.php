<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Contrato;
use App\Models\ParticipanteCobro;
use App\Models\ParticipanteContrato;
use App\Models\Propiedad;
use App\Models\Unidad;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FichaCobroCreateTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Cliente::find(1)) {
            Cliente::create(['id' => 1, 'nombre' => 'Corredor Principal', 'fecha_creacion' => now()]);
        }
    }

    /**
     * Helper: create a client with active contracts and participants.
     */
    private function createClienteWithActiveContracts(): array
    {
        $arrendador = Cliente::create(['nombre' => 'Arrendador Feature', 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => 'Arrendatario Feature', 'fecha_creacion' => now()]);
        $cliente = Cliente::create(['nombre' => 'Cliente Feature', 'fecha_creacion' => now()]);

        $propiedad = Propiedad::create(['direccion' => 'Feature Address 123', 'propietario' => $arrendador->id]);
        $unidad = Unidad::create(['nombre' => 'Feature Unidad', 'Propiedad_id' => $propiedad->id]);
        $contrato = Contrato::create([
            'Unidad_id' => $unidad->id,
            'administracion' => true,
            'renta' => 500000,
        ]);

        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $cliente->id, 'rol' => 'Corredor']);

        return compact('cliente', 'propiedad', 'contrato', 'arrendador', 'arrendatario');
    }

    // =========================================================================
    // 1.2: Feature test — ficha modal GET renders deudor/acreedor selects
    // =========================================================================

    public function test_cliente_ficha_modal_renders_deudor_acreedor_selects_with_participants(): void
    {
        $data = $this->createClienteWithActiveContracts();

        $response = $this->get(route('fichacliente.show', $data['cliente']->id));

        $response->assertStatus(200);
        $html = $response->getContent();

        // The modal partial is inside vista-agregar-cobro
        $this->assertStringContainsString('id="vista-agregar-cobro"', $html);

        // debe contener selects de deudor/acreedor con "Seleccione"
        $this->assertStringContainsString('id="select-deudor"', $html);
        $this->assertStringContainsString('id="select-acreedor"', $html);

        // Should contain contract participant options
        // The participant names should be rendered as <option> elements
        // (via JS buildClienteOptions or server-side rendered)
        $this->assertStringContainsString('Arrendador Feature', $html);
        $this->assertStringContainsString('Arrendatario Feature', $html);
        $this->assertStringContainsString('Cliente Feature', $html);

        // The modal should include a "Seleccione" placeholder option
        $this->assertStringContainsString('— Seleccionar —', $html);
    }

    public function test_propiedad_ficha_modal_renders_deudor_acreedor_selects_with_participants(): void
    {
        $data = $this->createClienteWithActiveContracts();

        $response = $this->get(route('propiedad.ficha', $data['propiedad']->id));

        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('id="vista-agregar-cobro"', $html);

        // Should contain participant names rendered in the page
        $this->assertStringContainsString('Arrendador Feature', $html);
        $this->assertStringContainsString('Arrendatario Feature', $html);
    }

    // =========================================================================
    // 1.3: Feature test — POST /cobro with _ficha_context=1 rejects empty monto
    // =========================================================================

    public function test_store_rejects_empty_monto_with_ficha_context(): void
    {
        $data = $this->createClienteWithActiveContracts();

        $response = $this->post(route('cobro.store'), [
            '_ficha_context' => '1',
            'cliente_id' => $data['cliente']->id,
            'tipo' => 'Reparación',
            'monto' => '',
            'detalle' => 'Reparación de cocina',
            'deudor_Cliente_id' => $data['arrendatario']->id,
            'acreedor_Cliente_id' => $data['arrendador']->id,
            'Propiedad_id' => $data['propiedad']->id,
        ]);

        // Form POST validation failures redirect with session errors
        $response->assertStatus(302);
        $response->assertSessionHasErrors('monto');
    }

    // =========================================================================
    // 1.4: Feature test — POST /cobro with _ficha_context=1 rejects empty detalle
    // =========================================================================

    public function test_store_rejects_empty_detalle_with_ficha_context(): void
    {
        $data = $this->createClienteWithActiveContracts();

        $response = $this->post(route('cobro.store'), [
            '_ficha_context' => '1',
            'cliente_id' => $data['cliente']->id,
            'tipo' => 'Reparación',
            'monto' => 150000,
            'detalle' => '',
            'deudor_Cliente_id' => $data['arrendatario']->id,
            'acreedor_Cliente_id' => $data['arrendador']->id,
            'Propiedad_id' => $data['propiedad']->id,
        ]);

        // Form POST validation failures redirect with session errors
        $response->assertStatus(302);
        $response->assertSessionHasErrors('detalle');
    }

    // =========================================================================
    // 1.5: Feature test — POST /cobro with _ficha_context=1 rejects missing deudor
    // =========================================================================

    public function test_store_rejects_missing_deudor_with_ficha_context(): void
    {
        $data = $this->createClienteWithActiveContracts();

        $response = $this->post(route('cobro.store'), [
            '_ficha_context' => '1',
            'cliente_id' => $data['cliente']->id,
            'tipo' => 'Reparación',
            'monto' => 150000,
            'detalle' => 'Reparación de cocina',
            'deudor_Cliente_id' => '',
            'acreedor_Cliente_id' => $data['arrendador']->id,
            'Propiedad_id' => $data['propiedad']->id,
        ]);

        // Form POST validation failures redirect with session errors
        $response->assertStatus(302);
        $response->assertSessionHasErrors('deudor_Cliente_id');
    }

    // =========================================================================
    // 1.6: Feature test — POST /cobro omitting fecha_cobro/estado creates with defaults
    // =========================================================================

    public function test_store_omits_fecha_cobro_and_estado_creates_with_defaults(): void
    {
        $data = $this->createClienteWithActiveContracts();

        $response = $this->post(route('cobro.store'), [
            '_ficha_context' => '1',
            'cliente_id' => $data['cliente']->id,
            'tipo' => 'Reparación',
            'monto' => 150000,
            'detalle' => 'Reparación de cocina',
            'deudor_Cliente_id' => $data['arrendatario']->id,
            'acreedor_Cliente_id' => $data['arrendador']->id,
            'Propiedad_id' => $data['propiedad']->id,
            // Note: fecha_cobro and estado are intentionally OMITTED
        ]);

        $response->assertStatus(302); // Redirect after creation

        // Find the last created cobro
        $cobro = Cobro::latest()->first();
        $this->assertNotNull($cobro);

        // Verify defaults were applied
        $this->assertEquals('Pendiente', $cobro->estado);
        $this->assertNotNull($cobro->fecha_cobro);
        // fecha_cobro should be close to now (within 1 minute)
        $this->assertTrue(
            $cobro->fecha_cobro->diffInMinutes(now()) <= 1,
            'fecha_cobro should be set to approximately now()'
        );
    }
}
