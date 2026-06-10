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

/**
 * Tests for GET /api/propiedad/{id}/pendientes
 * Verifies grouped-by-unidad response structure with role bucketing.
 */
class PropiedadPendientesControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected static bool $corredorSeeded = false;
    protected int $uniqueCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Cliente::find(1)) {
            Cliente::create(['id' => 1, 'nombre' => 'Corredor Principal', 'fecha_creacion' => now()]);
        }
    }

    /**
     * Helper: create a propiedad with unidad/contrato and cobros.
     * The propiedad can have multiple unidades.
     */
    protected function crearPropiedadConCobros(string $direccion, array $cobrosData, int $unidadCount = 1): array
    {
        $this->uniqueCounter++;
        $seq = $this->uniqueCounter;

        $arrendador = Cliente::create(['nombre' => "Arrendador $seq", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario $seq", 'fecha_creacion' => now()]);
        $corredor = Cliente::find(1);

        $propiedad = Propiedad::create(['direccion' => $direccion, 'propietario' => $arrendador->id]);

        $unidades = [];
        $contratos = [];

        for ($i = 1; $i <= $unidadCount; $i++) {
            $unidad = Unidad::create(["Unidad $i $seq", 'Propiedad_id' => $propiedad->id]);
            $unidades[] = $unidad;

            $contrato = \App\Models\Contrato::create([
                'Unidad_id' => $unidad->id,
                'administracion' => true,
                'renta' => 500000,
            ]);
            $contratos[] = $contrato;

            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $corredor->id, 'rol' => 'Corredor']);
        }

        $createdCobros = [];
        foreach ($cobrosData as $index => $data) {
            $unidadIndex = $data['unidad_index'] ?? 0;
            $cobro = Cobro::create([
                'tipo' => $data['tipo'],
                'monto' => $data['monto'],
                'estado' => $data['estado'],
                'fecha_cobro' => $data['fecha_cobro'] ?? now(),
                'Contrato_id' => $contratos[$unidadIndex]->id,
                'Propiedad_id' => $propiedad->id,
                'Unidad_id' => $unidades[$unidadIndex]->id,
            ]);

            $deudor = $data['deudor_rol'] === 'Arrendador' ? $arrendador
                : ($data['deudor_rol'] === 'Arrendatario' ? $arrendatario : $corredor);
            $acreedor = $data['acreedor_rol'] === 'Arrendador' ? $arrendador
                : ($data['acreedor_rol'] === 'Arrendatario' ? $arrendatario : $corredor);

            ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $deudor->id, 'rol' => 'Deudor', 'monto' => $data['monto']]);
            ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $acreedor->id, 'rol' => 'Acreedor', 'monto' => $data['monto']]);

            $createdCobros[] = $cobro;
        }

        return ['propiedad' => $propiedad, 'unidades' => $unidades, 'cobros' => $createdCobros];
    }

    public function test_returns_grouped_by_unidad_structure(): void
    {
        $data = $this->crearPropiedadConCobros('Prop Test Unidad', [
            ['tipo' => 'Ingreso Renta Arrendatario', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
        ]);

        $response = $this->getJson("/api/propiedad/{$data['propiedad']->id}/pendientes");

        $response->assertStatus(200);
        $json = $response->json();

        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'direccion', 'arrendador', 'arrendatario', 'corredor'],
            ],
            'total',
            'pagina',
            'por_pagina',
            'total_paginas',
        ]);

        $this->assertEquals(1, $json['total']);
        $this->assertCount(1, $json['data']);
    }

    public function test_show_unidad_true_counts_real_unidades_even_when_only_one_has_pending_cobros(): void
    {
        $data = $this->crearPropiedadConCobros('Real Unidad Count Prop', [
            ['tipo' => 'Ingreso Renta Arrendatario', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor', 'unidad_index' => 0],
        ], 2);

        $response = $this->getJson("/api/propiedad/{$data['propiedad']->id}/pendientes");
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertTrue($json['show_unidad']);
        $this->assertEquals(2, $json['unidad_count']);
        $this->assertEquals(1, $json['total']);
        $this->assertEquals($data['unidades'][0]->id, $json['data'][0]['unidad_id']);
        $this->assertEquals($data['unidades'][0]->nombre, $json['data'][0]['unidad_nombre']);
    }

    public function test_show_unidad_false_for_one_real_unidad_and_returns_unit_metadata(): void
    {
        $data = $this->crearPropiedadConCobros('Single Real Unidad Prop', [
            ['tipo' => 'Ingreso Renta Arrendatario', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
        ]);

        $response = $this->getJson("/api/propiedad/{$data['propiedad']->id}/pendientes");
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertFalse($json['show_unidad']);
        $this->assertEquals(1, $json['unidad_count']);
        $this->assertEquals($data['unidades'][0]->id, $json['data'][0]['unidad_id']);
        $this->assertEquals($data['unidades'][0]->nombre, $json['data'][0]['unidad_nombre']);
    }

    public function test_multiple_unidades_returned_as_separate_items(): void
    {
        $data = $this->crearPropiedadConCobros('Multi Unidad Test', [
            ['tipo' => 'Ingreso Renta Arrendatario', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor', 'unidad_index' => 0],
            ['tipo' => 'Egreso Renta Arrendador', 'monto' => 200000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendador', 'acreedor_rol' => 'Corredor', 'unidad_index' => 1],
        ], 2);

        $response = $this->getJson("/api/propiedad/{$data['propiedad']->id}/pendientes");
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertCount(2, $json['data']);
        $this->assertEquals(2, $json['total']);
    }

    public function test_cobros_bucketed_by_deudor_role(): void
    {
        $data = $this->crearPropiedadConCobros('Bucket Test Prop', [
            ['tipo' => 'Egreso Renta Arrendador', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendador', 'acreedor_rol' => 'Corredor'],
            ['tipo' => 'Ingreso Renta Arrendatario', 'monto' => 200000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
            ['tipo' => 'Comision inicial arrendador', 'monto' => 300000, 'estado' => 'Pendiente', 'deudor_rol' => 'Corredor', 'acreedor_rol' => 'Arrendador'],
        ]);

        $response = $this->getJson("/api/propiedad/{$data['propiedad']->id}/pendientes");
        $response->assertStatus(200);

        $json = $response->json();
        $allCobros = array_merge(
            $json['data'][0]['arrendador'] ?? [],
            $json['data'][0]['arrendatario'] ?? [],
            $json['data'][0]['corredor'] ?? []
        );

        $this->assertCount(1, $json['data'][0]['arrendador']);
        $this->assertCount(1, $json['data'][0]['arrendatario']);
        $this->assertCount(1, $json['data'][0]['corredor']);
    }

    public function test_incluye_tres_estados(): void
    {
        $data = $this->crearPropiedadConCobros('Tres Estados Prop', [
            ['tipo' => 'Ingreso Renta Arrendatario', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
            ['tipo' => 'Egreso Renta Arrendador', 'monto' => 200000, 'estado' => 'Vencido', 'deudor_rol' => 'Arrendador', 'acreedor_rol' => 'Corredor'],
            ['tipo' => 'Comision inicial arrendador', 'monto' => 300000, 'estado' => 'Incompleto', 'deudor_rol' => 'Corredor', 'acreedor_rol' => 'Arrendador'],
        ]);

        $response = $this->getJson("/api/propiedad/{$data['propiedad']->id}/pendientes");
        $response->assertStatus(200);

        $json = $response->json();
        $allCobros = array_merge(
            $json['data'][0]['arrendador'] ?? [],
            $json['data'][0]['arrendatario'] ?? [],
            $json['data'][0]['corredor'] ?? []
        );

        $estados = array_column($allCobros, 'estado');
        $this->assertContains('Pendiente', $estados);
        $this->assertContains('Vencido', $estados);
        $this->assertContains('Incompleto', $estados);
    }

    public function test_concepto_matches_CobroConceptoFormatter_output(): void
    {
        $fechaCobro = '2025-05-15 10:00:00';

        $data = $this->crearPropiedadConCobros('Concepto Test Prop', [
            [
                'tipo' => 'Ingreso Renta Arrendatario',
                'monto' => 100000,
                'estado' => 'Pendiente',
                'fecha_cobro' => $fechaCobro,
                'deudor_rol' => 'Arrendatario',
                'acreedor_rol' => 'Corredor',
            ],
            [
                'tipo' => 'Egreso Renta Arrendador',
                'monto' => 100000,
                'estado' => 'Pendiente',
                'fecha_cobro' => $fechaCobro,
                'deudor_rol' => 'Arrendador',
                'acreedor_rol' => 'Corredor',
            ],
            [
                'tipo' => 'Luz',
                'monto' => 50000,
                'estado' => 'Vencido',
                'fecha_cobro' => $fechaCobro,
                'deudor_rol' => 'Arrendatario',
                'acreedor_rol' => 'Corredor',
            ],
        ]);

        $response = $this->getJson("/api/propiedad/{$data['propiedad']->id}/pendientes");
        $response->assertStatus(200);

        $json = $response->json();
        $allCobros = array_merge(
            $json['data'][0]['arrendador'] ?? [],
            $json['data'][0]['arrendatario'] ?? [],
            $json['data'][0]['corredor'] ?? []
        );

        $conceptsByTipo = collect($allCobros)->keyBy('tipo');
        $this->assertEquals('Renta mayo 2025', $conceptsByTipo['Ingreso Renta Arrendatario']['concepto']);
        $this->assertEquals('Transferir renta mayo 2025', $conceptsByTipo['Egreso Renta Arrendador']['concepto']);
        $this->assertEquals('Luz mayo 2025', $conceptsByTipo['Luz']['concepto']);
    }

    public function test_pagination_works(): void
    {
        $this->uniqueCounter++;
        $seq = $this->uniqueCounter;

        $arrendador = Cliente::create(['nombre' => "Arrendador PP $seq", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario PP $seq", 'fecha_creacion' => now()]);
        $corredor = Cliente::find(1);
        $propiedad = Propiedad::create(['direccion' => "Pag Prop $seq", 'propietario' => $arrendador->id]);

        for ($i = 1; $i <= 3; $i++) {
            $unidad = Unidad::create(["Pag Unidad $i $seq", 'Propiedad_id' => $propiedad->id]);
            $contrato = \App\Models\Contrato::create(['Unidad_id' => $unidad->id, 'administracion' => true, 'renta' => 500000]);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $corredor->id, 'rol' => 'Corredor']);

            $cobro = Cobro::create([
                'tipo' => 'Ingreso Renta Arrendatario',
                'monto' => 100000 * $i,
                'estado' => 'Pendiente',
                'fecha_cobro' => now(),
                'Contrato_id' => $contrato->id,
                'Propiedad_id' => $propiedad->id,
                'Unidad_id' => $unidad->id,
            ]);
            ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Deudor', 'monto' => 100000 * $i]);
            ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $corredor->id, 'rol' => 'Acreedor', 'monto' => 100000 * $i]);
        }

        $response = $this->getJson("/api/propiedad/{$propiedad->id}/pendientes?pagina=1&por_pagina=2");
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertCount(2, $json['data']);
        $this->assertEquals(3, $json['total']);
        $this->assertEquals(2, $json['total_paginas']);

        $response2 = $this->getJson("/api/propiedad/{$propiedad->id}/pendientes?pagina=2&por_pagina=2");
        $response2->assertStatus(200);
        $this->assertCount(1, $response2->json('data'));
    }

    public function test_clamps_propiedad_pagination_to_three_unit_groups_without_splitting_cobros(): void
    {
        $this->uniqueCounter++;
        $seq = $this->uniqueCounter;

        $arrendador = Cliente::create(['nombre' => "Arrendador Unit Clamp $seq", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario Unit Clamp $seq", 'fecha_creacion' => now()]);
        $corredor = Cliente::find(1);
        $propiedad = Propiedad::create(['direccion' => "Unit Clamp Prop $seq", 'propietario' => $arrendador->id]);
        $multiCobroUnitId = null;
        $multiCobroIds = [];

        for ($i = 1; $i <= 4; $i++) {
            $unidad = Unidad::create(["Unit Clamp Unidad $i $seq", 'Propiedad_id' => $propiedad->id]);
            $contrato = \App\Models\Contrato::create(['Unidad_id' => $unidad->id, 'administracion' => true, 'renta' => 500000]);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $corredor->id, 'rol' => 'Corredor']);

            $cobrosForUnit = $i === 1 ? 2 : 1;
            for ($c = 1; $c <= $cobrosForUnit; $c++) {
                $cobro = Cobro::create([
                    'tipo' => $c === 1 ? 'Ingreso Renta Arrendatario' : 'Egreso Renta Arrendador',
                    'monto' => 100000 * ($i + $c),
                    'estado' => $c === 1 ? 'Pendiente' : 'Vencido',
                    'fecha_cobro' => now(),
                    'Contrato_id' => $contrato->id,
                    'Propiedad_id' => $propiedad->id,
                    'Unidad_id' => $unidad->id,
                ]);
                ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Deudor', 'monto' => $cobro->monto]);
                ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $corredor->id, 'rol' => 'Acreedor', 'monto' => $cobro->monto]);

                if ($i === 1) {
                    $multiCobroUnitId = $unidad->id;
                    $multiCobroIds[] = $cobro->id;
                }
            }
        }

        $response = $this->getJson("/api/propiedad/{$propiedad->id}/pendientes?pagina=1&por_pagina=99");

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertSame(3, $json['por_pagina']);
        $this->assertCount(3, $json['data']);
        $this->assertSame(4, $json['total']);
        $this->assertSame(2, $json['total_paginas']);

        $multiCobroRow = collect($json['data'])->firstWhere('unidad_id', $multiCobroUnitId);
        $this->assertNotNull($multiCobroRow, 'The first page must include the first unit group.');

        $allCobros = array_merge($multiCobroRow['arrendador'], $multiCobroRow['arrendatario'], $multiCobroRow['corredor']);
        $this->assertCount(2, $allCobros, 'All cobros for a visible unit group must remain on that page.');
        $this->assertEqualsCanonicalizing($multiCobroIds, array_column($allCobros, 'id'));
    }

    public function test_response_envelope_has_required_fields(): void
    {
        $data = $this->crearPropiedadConCobros('Env Test Prop', [
            ['tipo' => 'Ingreso Renta Arrendatario', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
        ]);

        $response = $this->getJson("/api/propiedad/{$data['propiedad']->id}/pendientes");
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data',
            'total',
            'pagina',
            'por_pagina',
            'total_paginas',
        ]);
    }

    public function test_fecha_cobro_is_iso_string(): void
    {
        $data = $this->crearPropiedadConCobros('Fecha Test Prop', [
            [
                'tipo' => 'Ingreso Renta Arrendatario',
                'monto' => 100000,
                'estado' => 'Pendiente',
                'fecha_cobro' => '2025-05-15 10:00:00',
                'deudor_rol' => 'Arrendatario',
                'acreedor_rol' => 'Corredor',
            ],
        ]);

        $response = $this->getJson("/api/propiedad/{$data['propiedad']->id}/pendientes");
        $response->assertStatus(200);

        $json = $response->json();
        $allCobros = array_merge(
            $json['data'][0]['arrendador'] ?? [],
            $json['data'][0]['arrendatario'] ?? [],
            $json['data'][0]['corredor'] ?? []
        );

        $this->assertNotEmpty($allCobros);
        $this->assertNotNull($allCobros[0]['fecha_cobro']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $allCobros[0]['fecha_cobro']);
    }
}
