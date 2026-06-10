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

class DashboardPendientesApiShapeTest extends TestCase
{
    use DatabaseTransactions;

    protected int $uniqueCounter = 0;

    /**
     * Create test property with cobr-os for integration testing.
     */
    protected function crearPropiedadConCobros(string $direccion, array $cobrosData): array
    {
        $this->uniqueCounter++;
        $seq = $this->uniqueCounter;

        $arrendador = Cliente::create(['nombre' => "Arrendador $seq", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario $seq", 'fecha_creacion' => now()]);
        $corredor = Cliente::create(['nombre' => "Corredor $seq", 'fecha_creacion' => now()]);

        $propiedad = Propiedad::create(['direccion' => $direccion, 'propietario' => $arrendador->id]);
        $unidad = Unidad::create(["Unidad $seq", 'Propiedad_id' => $propiedad->id]);

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
                'fecha_cobro' => $data['fecha_cobro'] ?? now(),
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

    public function test_response_envelope_has_required_fields(): void
    {
        $this->crearPropiedadConCobros('Env Test Prop', [
            ['tipo' => 'Ingreso Renta Arrendatario', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
        ]);

        $response = $this->getJson('/api/dashboard/pendientes');
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data',
            'total',
            'pagina',
            'por_pagina',
            'total_paginas',
        ]);
    }

    public function test_cobro_object_has_fecha_cobro_and_concepto(): void
    {
        $fechaCobro = '2025-05-15 10:00:00';

        $this->crearPropiedadConCobros('Concepto Test Prop', [
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

        $response = $this->getJson('/api/dashboard/pendientes');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $allCobros = array_merge(
            $data[0]['arrendador'] ?? [],
            $data[0]['arrendatario'] ?? [],
            $data[0]['corredor'] ?? []
        );
        $this->assertGreaterThanOrEqual(3, count($allCobros));

        foreach ($allCobros as $cobro) {
            $this->assertArrayHasKey('fecha_cobro', $cobro);
            $this->assertArrayHasKey('concepto', $cobro);
            $this->assertNotNull($cobro['concepto']);
            $this->assertNotEmpty($cobro['concepto']);
        }

        // Validate specific concepto values
        $conceptsByTipo = collect($allCobros)->keyBy('tipo');
        $this->assertEquals('Renta mayo 2025', $conceptsByTipo['Ingreso Renta Arrendatario']['concepto']);
        $this->assertEquals('Transferir renta mayo 2025', $conceptsByTipo['Egreso Renta Arrendador']['concepto']);
        $this->assertEquals('Luz mayo 2025', $conceptsByTipo['Luz']['concepto']);
    }

    public function test_response_includes_por_pagina_field(): void
    {
        $this->crearPropiedadConCobros('Page Field Test Prop', [
            ['tipo' => 'Ingreso Renta', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
        ]);

        $response = $this->getJson('/api/dashboard/pendientes');
        $response->assertStatus(200);

        $response->assertJsonStructure(['por_pagina']);
        $porPagina = $response->json('por_pagina');
        $this->assertIsInt($porPagina);
        $this->assertGreaterThan(0, $porPagina);
    }

    public function test_fecha_cobro_is_iso_string_when_cobro_has_fecha(): void
    {
        $fechaCobro = '2025-05-15 10:00:00';

        $this->crearPropiedadConCobros('Fecha Test Prop', [
            [
                'tipo' => 'Ingreso Renta Arrendatario',
                'monto' => 100000,
                'estado' => 'Pendiente',
                'fecha_cobro' => $fechaCobro,
                'deudor_rol' => 'Arrendatario',
                'acreedor_rol' => 'Corredor',
            ],
        ]);

        $response = $this->getJson('/api/dashboard/pendientes');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $allCobros = array_merge(
            $data[0]['arrendador'] ?? [],
            $data[0]['arrendatario'] ?? [],
            $data[0]['corredor'] ?? []
        );

        $this->assertGreaterThan(0, count($allCobros));
        $this->assertNotNull($allCobros[0]['fecha_cobro']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $allCobros[0]['fecha_cobro']);
        $this->assertEquals('Renta mayo 2025', $allCobros[0]['concepto']);
    }
}