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
 * Tests for GET /api/cliente/{id}/pendientes
 * Verifies grouped-by-propiedad response structure with role bucketing.
 */
class ClientePendientesControllerTest extends TestCase
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
     * Helper: create a client with a propiedad/unidad/contrato and cobros.
     *
     * @param string $direccion
     * @param array $cobrosData Each item: [tipo, monto, estado, fecha_cobro, deudor_rol, acreedor_rol, deudor_override_id, acreedor_override_id]
     *   deudor_override_id / acreedor_override_id: if set, use that client_id instead of the default targetCliente
     */
    protected function crearClienteConPropiedadYCobros(string $direccion, array $cobrosData): array
    {
        $this->uniqueCounter++;
        $seq = $this->uniqueCounter;

        $arrendador = Cliente::create(['nombre' => "Arrendador $seq", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario $seq", 'fecha_creacion' => now()]);
        $corredor = Cliente::find(1);

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

        // Create the target client and link them via ParticipanteCobro
        $targetCliente = Cliente::create(['nombre' => "Cliente Target $seq", 'fecha_creacion' => now()]);

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

            $deudorClient = match ($data['deudor_rol'] ?? 'Arrendador') {
                'Arrendador' => $arrendador,
                'Arrendatario' => $arrendatario,
                'Corredor' => $corredor,
            };
            $acreedorClient = match ($data['acreedor_rol'] ?? 'Corredor') {
                'Arrendador' => $arrendador,
                'Arrendatario' => $arrendatario,
                'Corredor' => $corredor,
            };

            // Override deudor/acreedor if specified (for bucket testing)
            $deudorId = $data['deudor_override_id'] ?? $targetCliente->id;
            $acreedorId = $data['acreedor_override_id'] ?? $acreedorClient->id;

            ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $deudorId, 'rol' => 'Deudor', 'monto' => $data['monto']]);
            ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $acreedorId, 'rol' => 'Acreedor', 'monto' => $data['monto']]);

            $createdCobros[] = $cobro;
        }

        return ['cliente' => $targetCliente, 'propiedad' => $propiedad, 'cobros' => $createdCobros];
    }

    public function test_returns_grouped_by_propiedad_structure(): void
    {
        $data = $this->crearClienteConPropiedadYCobros('Cliente Test Prop 1', [
            ['tipo' => 'Ingreso Renta Arrendatario', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
        ]);

        $response = $this->getJson("/api/cliente/{$data['cliente']->id}/pendientes");

        $response->assertStatus(200);
        $json = $response->json();

        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'direccion', 'unidad_count', 'arrendador', 'arrendatario', 'corredor'],
            ],
            'total',
            'pagina',
            'por_pagina',
            'total_paginas',
        ]);

        $this->assertEquals(1, $json['total']);
        $this->assertCount(1, $json['data']);
    }

    public function test_single_unidad_has_flat_structure_with_no_unidades_array(): void
    {
        $data = $this->crearClienteConPropiedadYCobros('Single Unidad Prop', [
            ['tipo' => 'Ingreso Renta Arrendatario', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
        ]);

        $response = $this->getJson("/api/cliente/{$data['cliente']->id}/pendientes");
        $response->assertStatus(200);

        $json = $response->json();
        $propiedadData = $json['data'][0];

        $this->assertEquals(1, $propiedadData['unidad_count']);
        $this->assertEmpty($propiedadData['unidades']);
        $this->assertNotEmpty($propiedadData['arrendador'] + $propiedadData['arrendatario'] + $propiedadData['corredor']);
    }

    public function test_multiple_unidades_are_flattened_into_property_role_buckets_without_nested_cards(): void
    {
        $this->uniqueCounter++;
        $seq = $this->uniqueCounter;

        $arrendador = Cliente::create(['nombre' => "Arrendador Multi $seq", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario Multi $seq", 'fecha_creacion' => now()]);
        $corredor = Cliente::find(1);
        $targetCliente = Cliente::create(['nombre' => "Cliente Multi $seq", 'fecha_creacion' => now()]);

        $propiedad = Propiedad::create(['direccion' => "Multi Unidad Prop $seq", 'propietario' => $arrendador->id]);
        $unidad1 = Unidad::create(["Unidad A $seq", 'Propiedad_id' => $propiedad->id]);
        $unidad2 = Unidad::create(["Unidad B $seq", 'Propiedad_id' => $propiedad->id]);

        $contrato1 = \App\Models\Contrato::create(['Unidad_id' => $unidad1->id, 'administracion' => true, 'renta' => 500000]);
        $contrato2 = \App\Models\Contrato::create(['Unidad_id' => $unidad2->id, 'administracion' => true, 'renta' => 500000]);

        foreach ([[$arrendador, $arrendatario, $corredor, $contrato1], [$arrendador, $arrendatario, $corredor, $contrato2]] as [$a, $at, $c, $ctr]) {
            ParticipanteContrato::create(['Contrato_id' => $ctr->id, 'Cliente_id' => $a->id, 'rol' => 'Arrendador']);
            ParticipanteContrato::create(['Contrato_id' => $ctr->id, 'Cliente_id' => $at->id, 'rol' => 'Arrendatario']);
            ParticipanteContrato::create(['Contrato_id' => $ctr->id, 'Cliente_id' => $c->id, 'rol' => 'Corredor']);
        }

        // Create cobro on unidad1
        $cobro1 = Cobro::create([
            'tipo' => 'Ingreso Renta Arrendatario',
            'monto' => 100000,
            'estado' => 'Pendiente',
            'fecha_cobro' => now(),
            'Contrato_id' => $contrato1->id,
            'Propiedad_id' => $propiedad->id,
            'Unidad_id' => $unidad1->id,
        ]);
        ParticipanteCobro::create(['Cobro_id' => $cobro1->id, 'Cliente_id' => $targetCliente->id, 'rol' => 'Deudor', 'monto' => 100000]);
        ParticipanteCobro::create(['Cobro_id' => $cobro1->id, 'Cliente_id' => $corredor->id, 'rol' => 'Acreedor', 'monto' => 100000]);

        // Create cobro on unidad2
        $cobro2 = Cobro::create([
            'tipo' => 'Egreso Renta Arrendador',
            'monto' => 200000,
            'estado' => 'Pendiente',
            'fecha_cobro' => now(),
            'Contrato_id' => $contrato2->id,
            'Propiedad_id' => $propiedad->id,
            'Unidad_id' => $unidad2->id,
        ]);
        ParticipanteCobro::create(['Cobro_id' => $cobro2->id, 'Cliente_id' => $targetCliente->id, 'rol' => 'Deudor', 'monto' => 200000]);
        ParticipanteCobro::create(['Cobro_id' => $cobro2->id, 'Cliente_id' => $corredor->id, 'rol' => 'Acreedor', 'monto' => 200000]);

        $response = $this->getJson("/api/cliente/{$targetCliente->id}/pendientes");
        $response->assertStatus(200);

        $json = $response->json();
        $propData = $json['data'][0];

        // With cobros on 2 unidades, dashboard-like rows stay flat at propiedad level.
        $this->assertEquals(2, $propData['unidad_count']);
        $this->assertEmpty($propData['unidades']);

        $allCobros = array_merge($propData['arrendador'], $propData['arrendatario'], $propData['corredor']);
        $this->assertCount(2, $allCobros);
        $this->assertEqualsCanonicalizing([$unidad1->id, $unidad2->id], array_column($allCobros, 'unidad_id'));
        $this->assertEqualsCanonicalizing([$unidad1->nombre, $unidad2->nombre], array_column($allCobros, 'unidad_nombre'));
    }

    public function test_cobro_data_keeps_nullable_modal_fields(): void
    {
        $this->uniqueCounter++;
        $seq = $this->uniqueCounter;

        $arrendador = Cliente::create(['nombre' => "Arrendador Nullable $seq", 'fecha_creacion' => now()]);
        $targetCliente = Cliente::create(['nombre' => "Cliente Nullable $seq", 'fecha_creacion' => now()]);
        $propiedad = Propiedad::create(['direccion' => "Nullable Prop $seq", 'propietario' => $arrendador->id]);
        $unidad = Unidad::create(["Unidad Nullable $seq", 'Propiedad_id' => $propiedad->id]);
        $contrato = \App\Models\Contrato::create(['Unidad_id' => $unidad->id, 'administracion' => true, 'renta' => 500000]);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $targetCliente->id, 'rol' => 'Arrendatario']);

        $cobro = Cobro::create([
            'tipo' => 'Ingreso Renta Arrendatario',
            'monto' => 100000,
            'estado' => 'Pendiente',
            'fecha_cobro' => now(),
            'Contrato_id' => $contrato->id,
            'Propiedad_id' => $propiedad->id,
            'Unidad_id' => $unidad->id,
            'Servicio_id' => null,
        ]);
        ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $targetCliente->id, 'rol' => 'Deudor', 'monto' => 100000]);

        $response = $this->getJson("/api/cliente/{$targetCliente->id}/pendientes");
        $response->assertStatus(200);

        $propData = $response->json('data.0');
        $allCobros = array_merge($propData['arrendador'], $propData['arrendatario'], $propData['corredor']);
        $this->assertCount(1, $allCobros);

        $cobroData = $allCobros[0];
        $this->assertSame($targetCliente->id, $cobroData['deudor_id']);
        $this->assertNull($cobroData['acreedor_id']);
        $this->assertNull($cobroData['servicio_id']);
        $this->assertNotNull($cobroData['fecha_cobro']);
        $this->assertEquals($unidad->id, $cobroData['unidad_id']);
        $this->assertEquals($unidad->nombre, $cobroData['unidad_nombre']);
    }

    public function test_cobros_bucketed_by_deudor_role(): void
    {
        // The helper creates cobr-os where targetCliente is a participante_cobro.
        // By specifying deudor_override_id we control who the deudor is,
        // which determines the bucket via their contrato rol.
        $data = $this->crearClienteConPropiedadYCobros('Bucket Test Prop', [
            // deudor=arrendador → arrendador bucket
            [
                'tipo' => 'Egreso Renta Arrendador',
                'monto' => 100000,
                'estado' => 'Pendiente',
                'deudor_rol' => 'Arrendador',
                'acreedor_rol' => 'Corredor',
                'deudor_override_id' => null, // use default (targetCliente) — overridden below
                'acreedor_override_id' => null,
            ],
        ]);

        // Redo with explicit overrides: deudor=arrendador → arrendador bucket
        $this->uniqueCounter++;
        $seq = $this->uniqueCounter;
        $arrendador = Cliente::create(['nombre' => "Arrendador Bkt $seq", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario Bkt $seq", 'fecha_creacion' => now()]);
        $corredor = Cliente::find(1);
        $targetCliente = Cliente::create(['nombre' => "Cliente Bkt $seq", 'fecha_creacion' => now()]);
        $propiedad = Propiedad::create(['direccion' => "Bucket Prop $seq", 'propietario' => $arrendador->id]);
        $unidad = Unidad::create(["Unidad Bkt $seq", 'Propiedad_id' => $propiedad->id]);
        $contrato = \App\Models\Contrato::create(['Unidad_id' => $unidad->id, 'administracion' => true, 'renta' => 500000]);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $corredor->id, 'rol' => 'Corredor']);

        // Create 3 cobr-os with targetCliente as acreedor but different deudors
        $c1 = Cobro::create(['tipo' => 'Egreso Renta Arrendador', 'monto' => 100000, 'estado' => 'Pendiente', 'fecha_cobro' => now(), 'Contrato_id' => $contrato->id, 'Propiedad_id' => $propiedad->id, 'Unidad_id' => $unidad->id]);
        ParticipanteCobro::create(['Cobro_id' => $c1->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Deudor', 'monto' => 100000]);
        ParticipanteCobro::create(['Cobro_id' => $c1->id, 'Cliente_id' => $targetCliente->id, 'rol' => 'Acreedor', 'monto' => 100000]);

        $c2 = Cobro::create(['tipo' => 'Ingreso Renta Arrendatario', 'monto' => 200000, 'estado' => 'Pendiente', 'fecha_cobro' => now(), 'Contrato_id' => $contrato->id, 'Propiedad_id' => $propiedad->id, 'Unidad_id' => $unidad->id]);
        ParticipanteCobro::create(['Cobro_id' => $c2->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Deudor', 'monto' => 200000]);
        ParticipanteCobro::create(['Cobro_id' => $c2->id, 'Cliente_id' => $targetCliente->id, 'rol' => 'Acreedor', 'monto' => 200000]);

        $c3 = Cobro::create(['tipo' => 'Comision inicial arrendador', 'monto' => 300000, 'estado' => 'Pendiente', 'fecha_cobro' => now(), 'Contrato_id' => $contrato->id, 'Propiedad_id' => $propiedad->id, 'Unidad_id' => $unidad->id]);
        ParticipanteCobro::create(['Cobro_id' => $c3->id, 'Cliente_id' => $corredor->id, 'rol' => 'Deudor', 'monto' => 300000]);
        ParticipanteCobro::create(['Cobro_id' => $c3->id, 'Cliente_id' => $targetCliente->id, 'rol' => 'Acreedor', 'monto' => 300000]);

        $response = $this->getJson("/api/cliente/{$targetCliente->id}/pendientes");
        $response->assertStatus(200);

        $json = $response->json();
        $propData = $json['data'][0];

        $this->assertCount(1, $propData['arrendador']);
        $this->assertCount(1, $propData['arrendatario']);
        $this->assertCount(1, $propData['corredor']);
    }

    public function test_incluye_tres_estados(): void
    {
        $data = $this->crearClienteConPropiedadYCobros('Tres Estados Prop', [
            ['tipo' => 'Ingreso Renta Arrendatario', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
            ['tipo' => 'Egreso Renta Arrendador', 'monto' => 200000, 'estado' => 'Vencido', 'deudor_rol' => 'Arrendador', 'acreedor_rol' => 'Corredor'],
            ['tipo' => 'Comision inicial arrendador', 'monto' => 300000, 'estado' => 'Incompleto', 'deudor_rol' => 'Corredor', 'acreedor_rol' => 'Arrendador'],
        ]);

        $response = $this->getJson("/api/cliente/{$data['cliente']->id}/pendientes");
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

        $data = $this->crearClienteConPropiedadYCobros('Concepto Test Prop', [
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

        $response = $this->getJson("/api/cliente/{$data['cliente']->id}/pendientes");
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
        $seq = $this->uniqueCounter + 100;
        $arrendador = Cliente::create(['nombre' => "Arrendador P $seq", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario P $seq", 'fecha_creacion' => now()]);
        $corredor = Cliente::find(1);
        $targetCliente = Cliente::create(['nombre' => "Cliente P $seq", 'fecha_creacion' => now()]);

        for ($i = 1; $i <= 3; $i++) {
            $propiedad = Propiedad::create(['direccion' => "Pag Prop $i $seq", 'propietario' => $arrendador->id]);
            $unidad = Unidad::create(["Unidad P$i $seq", 'Propiedad_id' => $propiedad->id]);
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
            ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $targetCliente->id, 'rol' => 'Deudor', 'monto' => 100000 * $i]);
            ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $corredor->id, 'rol' => 'Acreedor', 'monto' => 100000 * $i]);
        }

        $response = $this->getJson("/api/cliente/{$targetCliente->id}/pendientes?pagina=1&por_pagina=2");
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertCount(2, $json['data']);
        $this->assertEquals(3, $json['total']);
        $this->assertEquals(2, $json['total_paginas']);

        $response2 = $this->getJson("/api/cliente/{$targetCliente->id}/pendientes?pagina=2&por_pagina=2");
        $response2->assertStatus(200);
        $this->assertCount(1, $response2->json('data'));
    }

    public function test_clamps_cliente_pagination_to_three_property_groups_without_splitting_cobros(): void
    {
        $seq = $this->uniqueCounter + 200;
        $arrendador = Cliente::create(['nombre' => "Arrendador Clamp $seq", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario Clamp $seq", 'fecha_creacion' => now()]);
        $corredor = Cliente::find(1);
        $targetCliente = Cliente::create(['nombre' => "Cliente Clamp $seq", 'fecha_creacion' => now()]);
        $multiCobroPropertyId = null;
        $multiCobroIds = [];

        for ($i = 1; $i <= 4; $i++) {
            $propiedad = Propiedad::create(['direccion' => "Cliente Clamp Prop $i $seq", 'propietario' => $arrendador->id]);
            $unidad = Unidad::create(["Cliente Clamp Unidad $i $seq", 'Propiedad_id' => $propiedad->id]);
            $contrato = \App\Models\Contrato::create(['Unidad_id' => $unidad->id, 'administracion' => true, 'renta' => 500000]);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $corredor->id, 'rol' => 'Corredor']);

            $cobrosForProperty = $i === 1 ? 2 : 1;
            for ($c = 1; $c <= $cobrosForProperty; $c++) {
                $cobro = Cobro::create([
                    'tipo' => $c === 1 ? 'Ingreso Renta Arrendatario' : 'Egreso Renta Arrendador',
                    'monto' => 100000 * ($i + $c),
                    'estado' => $c === 1 ? 'Pendiente' : 'Vencido',
                    'fecha_cobro' => now(),
                    'Contrato_id' => $contrato->id,
                    'Propiedad_id' => $propiedad->id,
                    'Unidad_id' => $unidad->id,
                ]);
                ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $targetCliente->id, 'rol' => 'Deudor', 'monto' => $cobro->monto]);
                ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $corredor->id, 'rol' => 'Acreedor', 'monto' => $cobro->monto]);

                if ($i === 1) {
                    $multiCobroPropertyId = $propiedad->id;
                    $multiCobroIds[] = $cobro->id;
                }
            }
        }

        $response = $this->getJson("/api/cliente/{$targetCliente->id}/pendientes?pagina=1&por_pagina=99");

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertSame(3, $json['por_pagina']);
        $this->assertCount(3, $json['data']);
        $this->assertSame(4, $json['total']);
        $this->assertSame(2, $json['total_paginas']);

        $multiCobroRow = collect($json['data'])->firstWhere('id', $multiCobroPropertyId);
        $this->assertNotNull($multiCobroRow, 'The first page must include the first property group.');

        $allCobros = array_merge($multiCobroRow['arrendador'], $multiCobroRow['arrendatario'], $multiCobroRow['corredor']);
        $this->assertCount(2, $allCobros, 'All cobros for a visible property group must remain on that page.');
        $this->assertEqualsCanonicalizing($multiCobroIds, array_column($allCobros, 'id'));
    }

    public function test_response_envelope_has_required_fields(): void
    {
        $data = $this->crearClienteConPropiedadYCobros('Env Test Prop', [
            ['tipo' => 'Ingreso Renta Arrendatario', 'monto' => 100000, 'estado' => 'Pendiente', 'deudor_rol' => 'Arrendatario', 'acreedor_rol' => 'Corredor'],
        ]);

        $response = $this->getJson("/api/cliente/{$data['cliente']->id}/pendientes");
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
        $data = $this->crearClienteConPropiedadYCobros('Fecha Test Prop', [
            [
                'tipo' => 'Ingreso Renta Arrendatario',
                'monto' => 100000,
                'estado' => 'Pendiente',
                'fecha_cobro' => '2025-05-15 10:00:00',
                'deudor_rol' => 'Arrendatario',
                'acreedor_rol' => 'Corredor',
            ],
        ]);

        $response = $this->getJson("/api/cliente/{$data['cliente']->id}/pendientes");
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
