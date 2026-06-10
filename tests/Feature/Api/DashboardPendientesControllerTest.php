<?php

namespace Tests\Feature\Api;

use App\Models\Cobro;
use App\Models\Cliente;
use App\Models\ParticipanteCobro;
use App\Models\ParticipanteContrato;
use App\Models\Propiedad;
use App\Models\Unidad;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardPendientesControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected static bool $corredorSeeded = false;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure corredor Cliente id=1 exists for ParticipanteContrato lookups
        if (! Cliente::find(1)) {
            Cliente::create(['id' => 1, 'nombre' => 'Corredor Principal', 'fecha_creacion' => now()]);
        }
    }

    protected int $uniqueCounter = 0;

    /**
     * Helper: create a complete administracion chain for dashboard testing.
     */
    protected function crearPropiedadConCobros(string $direccion, array $cobrosData): array
    {
        $this->uniqueCounter++;
        $seq = $this->uniqueCounter;

        $arrendador = Cliente::create(['nombre' => "Arrendador $seq", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario $seq", 'fecha_creacion' => now()]);
        $corredor = Cliente::find(1);

        $propiedad = Propiedad::create(['direccion' => $direccion, 'propietario' => $arrendador->id]);
        $unidad = Unidad::create(["Unidad Dash $seq", 'Propiedad_id' => $propiedad->id]);

        $contrato = \App\Models\Contrato::create([
            'Unidad_id' => $unidad->id,
            'administracion' => true,
            'renta' => 500000,
        ]);

        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $corredor->id, 'rol' => 'Corredor']);

        $createdCobros = [];
        foreach ($cobrosData as $data) {
            $cobro = Cobro::create([
                'tipo' => $data['tipo'],
                'monto' => $data['monto'],
                'estado' => $data['estado'],
                'fecha_cobro' => now(),
                'Contrato_id' => $contrato->id,
                'Propiedad_id' => $propiedad->id,
                'Unidad_id' => $unidad->id,
            ]);

            $deudor = $data['deudor_rol'] === 'Arrendador' ? $arrendador : ($data['deudor_rol'] === 'Arrendatario' ? $arrendatario : $corredor);
            $acreedor = $data['acreedor_rol'] === 'Arrendador' ? $arrendador : ($data['acreedor_rol'] === 'Arrendatario' ? $arrendatario : $corredor);

            ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $deudor->id, 'rol' => 'Deudor', 'monto' => $data['monto']]);
            ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $acreedor->id, 'rol' => 'Acreedor', 'monto' => $data['monto']]);

            $createdCobros[] = $cobro;
        }

        return ['propiedad' => $propiedad, 'cobros' => $createdCobros];
    }

    public function test_includes_pendiente_vencido_and_incompleto(): void
    {
        $this->crearPropiedadConCobros('Dashboard Test 1', [
            ['tipo' => 'Ingreso Renta', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
            ['tipo' => 'Ingreso Renta', 'monto' => 200000, 'estado' => 'Vencido', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
            ['tipo' => 'Ingreso Renta', 'monto' => 300000, 'estado' => 'Pagado', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
            ['tipo' => 'Ingreso Renta', 'monto' => 400000, 'estado' => 'Anulado', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
            ['tipo' => 'Ingreso Renta', 'monto' => 500000, 'estado' => 'Incompleto', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
        ]);

        $response = $this->getJson('/api/dashboard/pendientes');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);

        $propiedadData = $data[0];
        $allCobros = array_merge($propiedadData['arrendador'] ?? [], $propiedadData['arrendatario'] ?? [], $propiedadData['corredor'] ?? []);

        $estados = array_column($allCobros, 'estado');
        $this->assertContains('Pendiente', $estados);
        $this->assertContains('Vencido', $estados);
        $this->assertNotContains('Pagado', $estados);
        $this->assertNotContains('Anulado', $estados);
        $this->assertContains('Incompleto', $estados);
    }

    public function test_paginates_by_property(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->crearPropiedadConCobros("Pagination Prop $i", [
                ['tipo' => 'Ingreso Renta', 'monto' => 100000 * $i, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
            ]);
        }

        $response = $this->getJson('/api/dashboard/pendientes?pagina=1&por_pagina=2');
        $response->assertStatus(200);

        $data = $response->json('data');
        $total = $response->json('total');
        $totalPaginas = $response->json('total_paginas');

        $this->assertCount(2, $data);
        $this->assertEquals(3, $total);
        $this->assertEquals(2, $totalPaginas);

        $response2 = $this->getJson('/api/dashboard/pendientes?pagina=2&por_pagina=2');
        $response2->assertStatus(200);
        $this->assertCount(1, $response2->json('data'));
    }

    public function test_clamps_dashboard_pagination_to_three_property_groups_without_splitting_cobros(): void
    {
        $multiCobroProp = $this->crearPropiedadConCobros('Dashboard Group Clamp Multi', [
            ['tipo' => 'Ingreso Renta', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
            ['tipo' => 'Egreso Renta', 'monto' => 200000, 'estado' => 'Vencido', 'deudor_rol' => 'Arrendador', 'acreedor_rol' => 'Corredor'],
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $this->crearPropiedadConCobros("Dashboard Group Clamp $i", [
                ['tipo' => 'Ingreso Renta', 'monto' => 100000 * $i, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
            ]);
        }

        $response = $this->getJson('/api/dashboard/pendientes?pagina=1&por_pagina=99');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertSame(3, $json['por_pagina']);
        $this->assertCount(3, $json['data']);
        $this->assertSame(4, $json['total']);
        $this->assertSame(2, $json['total_paginas']);

        $multiCobroRow = collect($json['data'])->firstWhere('id', $multiCobroProp['propiedad']->id);
        $this->assertNotNull($multiCobroRow, 'The first page must include the first property group.');

        $allCobros = array_merge($multiCobroRow['arrendador'], $multiCobroRow['arrendatario'], $multiCobroRow['corredor']);
        $this->assertCount(2, $allCobros, 'All cobros for a visible property group must remain on that page.');
        $this->assertEqualsCanonicalizing(
            array_column($multiCobroProp['cobros'], 'id'),
            array_column($allCobros, 'id')
        );
    }

    public function test_cobros_grouped_by_role_bucket(): void
    {
        // Deudor=arrendador → arrendador bucket
        // Deudor=arrendatario → arrendatario bucket
        // Deudor=corredor → corredor bucket
        $this->crearPropiedadConCobros('Bucket Test Prop', [
            ['tipo' => 'Egreso Renta', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendador', 'acreedor_rol' => 'Corredor'],
            ['tipo' => 'Ingreso Renta', 'monto' => 200000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
            ['tipo' => 'Comision inicial', 'monto' => 300000, 'estado' => 'Pendiente', 'deudor_rol' => 'Corredor', 'acreedor_rol' => 'Arrendador'],
        ]);

        $response = $this->getJson('/api/dashboard/pendientes');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);

        $propiedadData = $data[0];
        $this->assertCount(1, $propiedadData['arrendador'] ?? []);
        $this->assertCount(1, $propiedadData['arrendatario'] ?? []);
        $this->assertCount(1, $propiedadData['corredor'] ?? []);
    }

    public function test_returns_empty_when_all_cobros_have_null_propiedad_id(): void
    {
        $this->uniqueCounter++;
        $seq = $this->uniqueCounter;

        $deudor = Cliente::create(['nombre' => "Deudor Null $seq", 'fecha_creacion' => now()]);
        $acreedor = Cliente::create(['nombre' => "Acreedor Null $seq", 'fecha_creacion' => now()]);

        $cobro = Cobro::create([
            'tipo' => 'Ingreso Renta',
            'monto' => 100000,
            'estado' => 'Pendiente',
            'fecha_cobro' => now(),
            'Contrato_id' => null,
            'Propiedad_id' => null,
            'Unidad_id' => null,
        ]);

        ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $deudor->id, 'rol' => 'Deudor', 'monto' => 100000]);
        ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $acreedor->id, 'rol' => 'Acreedor', 'monto' => 100000]);

        $response = $this->getJson('/api/dashboard/pendientes');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEmpty($data);
    }
}
