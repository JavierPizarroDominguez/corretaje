<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Contrato;
use App\Models\DestinoTransaccion;
use App\Models\OrigenTransaccion;
use App\Models\ParticipanteCobro;
use App\Models\ParticipanteContrato;
use App\Models\Propiedad;
use App\Models\Transaccion;
use App\Models\TransaccionCobro;
use App\Models\Unidad;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FichaPendientesVisualContractTest extends TestCase
{
    use DatabaseTransactions;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Cliente::find(1)) {
            Cliente::create(['id' => 1, 'nombre' => 'Corredor Principal', 'fecha_creacion' => now()]);
        }
    }

    public function test_cliente_ficha_initial_pendientes_use_dashboard_table_contract(): void
    {
        $data = $this->createPendingCobroScenario('Cliente Visual Prop');

        $response = $this->get(route('fichacliente.show', $data['target']->id));

        $response->assertStatus(200);
        $section = $this->pendientesSection($response->getContent());

        $this->assertStringContainsString('class="card" id="ficha-pendientes-container"', $section);
        $this->assertStringContainsString('class="table mb-0 text-nowrap table-hover table-card-mobile pendientes-dashboard-table ficha-pendientes-table"', $section);
        $this->assertStringContainsString('<th><b>Dirección</b></th>', $section);
        $this->assertStringContainsString('class="td-cobros"', $section);
        $this->assertStringContainsString('class="btn btn-sm btn-warning w-100 text-center btn-cobro"', $section);
        $this->assertStringContainsString('Renta mayo 2025', $section);
        $this->assertStringContainsString('data-cobro=', $section);
        $this->assertStringNotContainsString('ficha-pendientes-card', $section);
        $this->assertStringNotContainsString('unidad-nested', $section);
    }

    public function test_propiedad_ficha_initial_pendientes_show_unidad_only_for_multiple_real_units(): void
    {
        $single = $this->createPendingCobroScenario('Single Unidad Visual Prop', unidadCount: 1);
        $multiple = $this->createPendingCobroScenario('Multi Unidad Visual Prop', unidadCount: 2);

        $singleResponse = $this->get(route('propiedad.ficha', $single['propiedad']->id));
        $multipleResponse = $this->get(route('propiedad.ficha', $multiple['propiedad']->id));

        $singleResponse->assertStatus(200);
        $multipleResponse->assertStatus(200);

        $singleSection = $this->pendientesSection($singleResponse->getContent());
        $multipleSection = $this->pendientesSection($multipleResponse->getContent());

        $this->assertStringContainsString('pendientes-dashboard-table ficha-pendientes-table', $multipleSection);
        $this->assertStringContainsString('<th><b>Unidad</b></th>', $multipleSection);
        $this->assertStringContainsString('Unidad Visual 1', $multipleSection);
        $this->assertStringContainsString('class="td-cobros"', $multipleSection);
        $this->assertStringContainsString('Renta mayo 2025', $multipleSection);

        $this->assertStringContainsString('pendientes-dashboard-table ficha-pendientes-table', $singleSection);
        $this->assertStringNotContainsString('<th><b>Unidad</b></th>', $singleSection);
    }

    public function test_ficha_ajax_renderers_keep_dashboard_contract_and_serialized_cobro_data(): void
    {
        $data = $this->createPendingCobroScenario('Ajax Visual Prop');

        $clienteResponse = $this->get(route('fichacliente.show', $data['target']->id));
        $propiedadResponse = $this->get(route('propiedad.ficha', $data['propiedad']->id));

        $clienteResponse->assertStatus(200);
        $propiedadResponse->assertStatus(200);

        foreach ([$clienteResponse->getContent(), $propiedadResponse->getContent()] as $content) {
            $this->assertStringContainsString('function renderCobros(lista = [])', $content);
            $this->assertStringContainsString('class="btn btn-sm btn-${color} w-100 text-center btn-cobro"', $content);
            $this->assertStringContainsString('data-cobro=\'${serializeCobro(c)}\'', $content);
            $this->assertStringContainsString('td class="td-cobros"', $content);
            $this->assertStringContainsString('await cargarFichaPendientes(paginaActual);', $content);
            $this->assertStringContainsString('window.showElLoading', $content);
            $this->assertStringContainsString('window.hideElLoading', $content);
        }

        $this->assertStringContainsString('const showUnidadColumn = Boolean(json.show_unidad);', $propiedadResponse->getContent());
    }

    public function test_initial_cliente_and_propiedad_fichas_paginate_visual_groups_at_three_without_splitting_cobros(): void
    {
        $clienteData = $this->createClienteWithPropertyGroups(4, firstGroupCobros: 2);
        $propiedadData = $this->createPropiedadWithUnitGroups(4, firstGroupCobros: 2);

        $clienteResponse = $this->get(route('fichacliente.show', $clienteData['target']->id));
        $propiedadResponse = $this->get(route('propiedad.ficha', $propiedadData['propiedad']->id));

        $clienteResponse->assertStatus(200);
        $propiedadResponse->assertStatus(200);

        $clienteSection = $this->pendientesSection($clienteResponse->getContent());
        $propiedadSection = $this->pendientesSection($propiedadResponse->getContent());

        $this->assertSame(3, substr_count($this->pendientesTableBody($clienteSection), '<tr>'), 'Cliente ficha must render max 3 property rows on the first page.');
        $this->assertStringContainsString($clienteData['first_group_cobro_labels'][0], $clienteSection);
        $this->assertStringContainsString($clienteData['first_group_cobro_labels'][1], $clienteSection);
        $this->assertStringNotContainsString($clienteData['fourth_group_label'], $clienteSection);

        $this->assertSame(3, substr_count($this->pendientesTableBody($propiedadSection), '<tr>'), 'Propiedad ficha must render max 3 unit rows on the first page.');
        $this->assertStringContainsString($propiedadData['first_unit_name'], $propiedadSection);
        $this->assertStringContainsString($propiedadData['first_group_cobro_labels'][0], $propiedadSection);
        $this->assertStringContainsString($propiedadData['first_group_cobro_labels'][1], $propiedadSection);
        $this->assertStringNotContainsString($propiedadData['fourth_unit_name'], $propiedadSection);
    }

    public function test_transacciones_render_only_inside_historial_movimientos_for_cliente_and_propiedad_fichas(): void
    {
        $data = $this->createPendingCobroScenario('Movimientos Transaccion Prop');
        $this->createTransactionForCobro($data['target'], $data['cobro']);

        $clienteFicha = $this->get(route('fichacliente.show', $data['target']->id));
        $propiedadFicha = $this->get(route('propiedad.ficha', $data['propiedad']->id));
        $clienteMovimientos = $this->get(route('cliente.reparaciones', $data['target']->id));
        $propiedadMovimientos = $this->get(route('propiedad.reparaciones', $data['propiedad']->id));

        $clienteFicha->assertStatus(200);
        $propiedadFicha->assertStatus(200);
        $clienteMovimientos->assertStatus(200);
        $propiedadMovimientos->assertStatus(200);

        foreach ([$clienteFicha, $propiedadFicha] as $response) {
            $response->assertSee('Historial de movimientos');
            $response->assertDontSee('Historial de transacciones');
            $response->assertDontSee('$123.456');
        }

        foreach ([$clienteMovimientos, $propiedadMovimientos] as $response) {
            $content = $response->getContent();
            $cartolaPosition = strpos($content, 'Cartola Unidad');
            $transaccionesPosition = strpos($content, 'Historial de transacciones');

            $response->assertSee('Historial de transacciones');
            $response->assertSee('Cartola Unidad');
            $response->assertSee('$123.456');
            $response->assertSee('Ingreso Renta Arrendatario');
            $response->assertDontSee('Reparaciones y gastos extras');
            $this->assertNotFalse($cartolaPosition, 'Cartola must render on movement pages.');
            $this->assertNotFalse($transaccionesPosition, 'Transaction history must render on movement pages.');
            $this->assertLessThan($transaccionesPosition, $cartolaPosition, 'Cartola must appear before transaction history.');
        }
    }

    private function createPendingCobroScenario(string $direccion, int $unidadCount = 1): array
    {
        $this->sequence++;
        $seq = $this->sequence;

        $arrendador = Cliente::create(['nombre' => "Arrendador Visual {$seq}", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario Visual {$seq}", 'fecha_creacion' => now()]);
        $target = Cliente::create(['nombre' => "Cliente Visual {$seq}", 'fecha_creacion' => now()]);
        $corredor = Cliente::find(1);

        $propiedad = Propiedad::create(['direccion' => "{$direccion} {$seq}", 'propietario' => $arrendador->id]);
        $unidades = [];
        for ($i = 1; $i <= $unidadCount; $i++) {
            $unidades[] = Unidad::create(['nombre' => "Unidad Visual {$i}", 'Propiedad_id' => $propiedad->id]);
        }

        $contrato = Contrato::create(['Unidad_id' => $unidades[0]->id, 'administracion' => true, 'renta' => 500000]);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $corredor->id, 'rol' => 'Corredor']);

        $cobro = Cobro::create([
            'tipo' => 'Ingreso Renta Arrendatario',
            'monto' => 100000,
            'estado' => 'pendiente',
            'fecha_cobro' => '2025-05-15 10:00:00',
            'Contrato_id' => $contrato->id,
            'Propiedad_id' => $propiedad->id,
            'Unidad_id' => $unidades[0]->id,
        ]);
        ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $target->id, 'rol' => 'Deudor', 'monto' => 100000]);
        ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $corredor->id, 'rol' => 'Acreedor', 'monto' => 100000]);

        return compact('target', 'propiedad', 'unidades', 'cobro');
    }

    private function createTransactionForCobro(Cliente $cliente, Cobro $cobro): Transaccion
    {
        $origen = OrigenTransaccion::create([
            'tipo' => 'Cuenta Bancaria',
            'Cliente_id' => $cliente->id,
        ]);
        $destino = DestinoTransaccion::create([
            'tipo' => 'Cuenta Bancaria',
            'Cliente_id' => $cliente->id,
        ]);
        $transaccion = Transaccion::create([
            'monto' => 123456,
            'fecha' => '2025-06-20 10:00:00',
            'Destino_Transaccion_id' => $destino->id,
            'Origen_Transaccion_id' => $origen->id,
        ]);

        TransaccionCobro::create([
            'Transaccion_id' => $transaccion->id,
            'Cobro_id' => $cobro->id,
            'monto_pagado' => 123456,
        ]);

        return $transaccion;
    }

    private function pendientesSection(string $html): string
    {
        $start = strpos($html, 'id="pendientes-section"');
        $end = strpos($html, 'id="vista-agregar-cobro"');

        $this->assertNotFalse($start, 'The pendientes section must be present.');
        $this->assertNotFalse($end, 'The hidden add-cobro modal marker must be present.');

        return substr($html, $start, $end - $start);
    }

    private function pendientesTableBody(string $section): string
    {
        $start = strpos($section, '<tbody id="body-ficha-pendientes">');
        $end = strpos($section, '</tbody>', $start ?: 0);

        $this->assertNotFalse($start, 'The pendientes table body must be present.');
        $this->assertNotFalse($end, 'The pendientes table body must close.');

        return substr($section, $start, $end - $start);
    }

    private function createClienteWithPropertyGroups(int $propertyCount, int $firstGroupCobros): array
    {
        $this->sequence++;
        $seq = $this->sequence;
        $arrendador = Cliente::create(['nombre' => "Arrendador Cliente Page {$seq}", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario Cliente Page {$seq}", 'fecha_creacion' => now()]);
        $target = Cliente::create(['nombre' => "Cliente Page {$seq}", 'fecha_creacion' => now()]);
        $corredor = Cliente::find(1);
        $firstGroupLabels = [];
        $fourthGroupLabel = '';

        for ($i = 1; $i <= $propertyCount; $i++) {
            $propiedad = Propiedad::create(['direccion' => "Cliente Page Prop {$i} {$seq}", 'propietario' => $arrendador->id]);
            $unidad = Unidad::create(['nombre' => "Cliente Page Unidad {$i} {$seq}", 'Propiedad_id' => $propiedad->id]);
            $contrato = Contrato::create(['Unidad_id' => $unidad->id, 'administracion' => true, 'renta' => 500000]);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $corredor->id, 'rol' => 'Corredor']);

            $cobroCount = $i === 1 ? $firstGroupCobros : 1;
            for ($c = 1; $c <= $cobroCount; $c++) {
                $date = sprintf('2025-%02d-15 10:00:00', $i + $c);
                $tipo = $c === 1 ? 'Ingreso Renta Arrendatario' : 'Egreso Renta Arrendador';
                $cobro = Cobro::create([
                    'tipo' => $tipo,
                    'monto' => 100000 * ($i + $c),
                    'estado' => 'pendiente',
                    'fecha_cobro' => $date,
                    'Contrato_id' => $contrato->id,
                    'Propiedad_id' => $propiedad->id,
                    'Unidad_id' => $unidad->id,
                ]);
                ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $target->id, 'rol' => 'Deudor', 'monto' => $cobro->monto]);
                ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $corredor->id, 'rol' => 'Acreedor', 'monto' => $cobro->monto]);

                $label = $tipo === 'Ingreso Renta Arrendatario'
                    ? sprintf('Renta %s 2025', now()->month($i + $c)->translatedFormat('F'))
                    : sprintf('Transferir renta %s 2025', now()->month($i + $c)->translatedFormat('F'));
                if ($i === 1) {
                    $firstGroupLabels[] = $label;
                }
                if ($i === 4) {
                    $fourthGroupLabel = $label;
                }
            }
        }

        return ['target' => $target, 'first_group_cobro_labels' => $firstGroupLabels, 'fourth_group_label' => $fourthGroupLabel];
    }

    private function createPropiedadWithUnitGroups(int $unitCount, int $firstGroupCobros): array
    {
        $this->sequence++;
        $seq = $this->sequence;
        $arrendador = Cliente::create(['nombre' => "Arrendador Unidad Page {$seq}", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario Unidad Page {$seq}", 'fecha_creacion' => now()]);
        $corredor = Cliente::find(1);
        $propiedad = Propiedad::create(['direccion' => "Propiedad Unit Page {$seq}", 'propietario' => $arrendador->id]);
        $firstGroupLabels = [];
        $firstUnitName = '';
        $fourthUnitName = '';

        for ($i = 1; $i <= $unitCount; $i++) {
            $unidad = Unidad::create(['nombre' => "Unidad Page {$i} {$seq}", 'Propiedad_id' => $propiedad->id]);
            if ($i === 1) {
                $firstUnitName = $unidad->nombre;
            }
            if ($i === 4) {
                $fourthUnitName = $unidad->nombre;
            }

            $contrato = Contrato::create(['Unidad_id' => $unidad->id, 'administracion' => true, 'renta' => 500000]);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
            ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $corredor->id, 'rol' => 'Corredor']);

            $cobroCount = $i === 1 ? $firstGroupCobros : 1;
            for ($c = 1; $c <= $cobroCount; $c++) {
                $date = sprintf('2025-%02d-15 10:00:00', $i + $c);
                $tipo = $c === 1 ? 'Ingreso Renta Arrendatario' : 'Egreso Renta Arrendador';
                $cobro = Cobro::create([
                    'tipo' => $tipo,
                    'monto' => 100000 * ($i + $c),
                    'estado' => 'pendiente',
                    'fecha_cobro' => $date,
                    'Contrato_id' => $contrato->id,
                    'Propiedad_id' => $propiedad->id,
                    'Unidad_id' => $unidad->id,
                ]);
                ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Deudor', 'monto' => $cobro->monto]);
                ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $corredor->id, 'rol' => 'Acreedor', 'monto' => $cobro->monto]);

                if ($i === 1) {
                    $firstGroupLabels[] = $tipo === 'Ingreso Renta Arrendatario'
                        ? sprintf('Renta %s 2025', now()->month($i + $c)->translatedFormat('F'))
                        : sprintf('Transferir renta %s 2025', now()->month($i + $c)->translatedFormat('F'));
                }
            }
        }

        return [
            'propiedad' => $propiedad,
            'first_unit_name' => $firstUnitName,
            'fourth_unit_name' => $fourthUnitName,
            'first_group_cobro_labels' => $firstGroupLabels,
        ];
    }
}
