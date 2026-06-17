<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Contrato;
use App\Models\ParticipanteCobro;
use App\Models\ParticipanteContrato;
use App\Models\Propiedad;
use App\Models\Unidad;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FichaContratosDisplayTest extends TestCase
{
    use DatabaseTransactions;

    public function test_propiedad_contratos_displays_participant_clients_and_supported_pending_states(): void
    {
        $data = $this->createActiveContractScenario('Propiedad Contratos Display', pendingState: 'Vencido');

        $response = $this->get(route('propiedad.contratos', $data['propiedad']->id));

        $response->assertStatus(200);
        $response->assertSee($data['propiedad']->direccion);
        $response->assertSee($data['unidad']->nombre);
        $response->assertSee($data['arrendador']->nombre);
        $response->assertSee($data['arrendatario']->nombre);
        $response->assertSee(route('fichacliente.show', $data['arrendador']->id), false);
        $response->assertSee(route('fichacliente.show', $data['arrendatario']->id), false);
        $response->assertSee('Cobros pendientes');
        $response->assertSee('Vencido');
        $response->assertSee('Renta pendiente propiedad');
    }

    public function test_cliente_contratos_displays_context_participants_and_only_client_pending_cobros(): void
    {
        $data = $this->createActiveContractScenario('Cliente Contratos Display', pendingState: 'Incompleto');
        $unrelated = Cliente::create(['nombre' => 'Cliente no relacionado', 'fecha_creacion' => now()]);
        $unrelatedCobro = Cobro::create([
            'tipo' => 'Cobro no relacionado',
            'monto' => 90000,
            'estado' => 'pendiente',
            'fecha_cobro' => '2026-02-15 10:00:00',
            'Contrato_id' => $data['contrato']->id,
            'Propiedad_id' => $data['propiedad']->id,
            'Unidad_id' => $data['unidad']->id,
        ]);
        ParticipanteCobro::create(['Cobro_id' => $unrelatedCobro->id, 'Cliente_id' => $unrelated->id, 'rol' => 'Deudor', 'monto' => 90000]);

        $response = $this->get(route('cliente.contratos', $data['arrendatario']->id));

        $response->assertStatus(200);
        $response->assertSee($data['arrendador']->nombre);
        $response->assertSee($data['arrendatario']->nombre);
        $response->assertSee($data['propiedad']->direccion);
        $response->assertSee($data['unidad']->nombre);
        $response->assertSee('Incompleto');
        $response->assertSee('Renta pendiente propiedad');
        $response->assertDontSee('Cobro no relacionado');
    }

    public function test_propiedad_contratos_renders_termination_preview_modal_with_pending_and_default_rows(): void
    {
        Carbon::setTestNow('2026-06-16 09:00:00');
        $data = $this->createActiveContractScenario('Propiedad Termino Preview', pendingState: 'pendiente');

        $response = $this->get(route('propiedad.contratos', $data['propiedad']->id));

        $response->assertStatus(200);
        $response->assertSee('Terminar contrato');
        $response->assertSee("vista-terminar-contrato-{$data['contrato']->id}", false);
        $response->assertSee('Vista previa de término de contrato');
        $response->assertSee('Esta vista previa no termina el contrato ni guarda cambios');
        $response->assertSee('Inspeccioná la propiedad antes de confirmar cualquier devolución');
        $response->assertSee('servicios y gastos comunes proporcionales son avisos automáticos de esta vista previa');
        $response->assertSee('Garantía original');
        $response->assertSee('$500.000');
        $response->assertSee('Fecha de inicio');
        $response->assertSee('01-01-2025');
        $response->assertSee('Fecha de término');
        $response->assertSee('16-06-2026');
        $response->assertSee('Renta pendiente propiedad');
        $response->assertSee('Aseo Final');
        $response->assertSee('Agregar ajuste');
        $response->assertSee('Total descuentos/cargos');
        $response->assertSee('Monto a devolver al arrendatario');
    }

    public function test_cliente_contratos_termination_preview_modal_keeps_empty_pending_state_and_no_persistence_surface(): void
    {
        Carbon::setTestNow('2026-06-16 09:00:00');
        $data = $this->createActiveContractScenario('Cliente Termino Preview', pendingState: 'pagado');

        $response = $this->get(route('cliente.contratos', $data['arrendatario']->id));
        $component = file_get_contents(resource_path('views/components/contratos.blade.php'));
        $webRoutes = file_get_contents(base_path('routes/web.php'));
        $generatedRoutes = file_get_contents(base_path('routes/generated.php'));

        $response->assertStatus(200);
        $response->assertSee('Terminar contrato');
        $response->assertSee('No hay cobros pendientes para este contrato.');
        $response->assertSee('Aseo Final');
        $response->assertDontSee('pagado');
        $this->assertStringNotContainsString('alert(', $component);
        $this->assertStringNotContainsString('confirm(', $component);
        $this->assertStringNotContainsString('prompt(', $component);
        $this->assertDoesNotMatchRegularExpression('/Route::(post|put|patch|delete)\([^\n]*(termin|finaliz)/i', $webRoutes . "\n" . $generatedRoutes);
    }

    public function test_contrato_show_displays_readable_summary_participants_property_dates_guarantee_and_cobros(): void
    {
        $data = $this->createActiveContractScenario('Contrato Show Readable', pendingState: 'pendiente');

        $response = $this->get(route('contrato.show', $data['contrato']->id));

        $response->assertStatus(200);
        $response->assertSee('Resumen legible del contrato');
        $response->assertSee('Participantes del contrato');
        $response->assertSee('Arrendador');
        $response->assertSee($data['arrendador']->nombre);
        $response->assertSee('Arrendatario');
        $response->assertSee($data['arrendatario']->nombre);
        $response->assertSee('Corredor');
        $response->assertSee($data['corredor']->nombre);
        $response->assertSee('Propiedad y unidad');
        $response->assertSee($data['propiedad']->direccion);
        $response->assertSee($data['unidad']->nombre);
        $response->assertSee('Fecha de inicio');
        $response->assertSee('01-01-2025');
        $response->assertSee('Fecha de término');
        $response->assertSee('Sin fecha de término');
        $response->assertSee('Garantía');
        $response->assertSee('$500.000');
        $response->assertSee('Cobros del contrato');
        $response->assertSee('Renta pendiente propiedad');
        $response->assertSee('pendiente');
        $response->assertSee('$120.000');
    }

    public function test_contrato_show_uses_safe_empty_states_for_missing_participants_and_cobros(): void
    {
        $propietario = Cliente::create(['nombre' => 'Propietario sin contrato participante', 'fecha_creacion' => now()]);
        $propiedad = Propiedad::create([
            'direccion' => 'Contrato sin relaciones completas',
            'propietario' => $propietario->id,
        ]);
        $unidad = Unidad::create(['nombre' => 'Unidad sin participantes', 'Propiedad_id' => $propiedad->id]);
        $contrato = Contrato::create([
            'Unidad_id' => $unidad->id,
            'administracion' => false,
            'garantia' => null,
            'fecha_inicio' => null,
            'fecha_termino' => '2026-07-01 00:00:00',
        ]);

        $response = $this->get(route('contrato.show', $contrato->id));

        $response->assertStatus(200);
        $response->assertSee('Resumen legible del contrato');
        $response->assertSee('Sin arrendador');
        $response->assertSee('Sin arrendatario');
        $response->assertSee('Sin corredor');
        $response->assertSee('Contrato sin relaciones completas');
        $response->assertSee('Unidad sin participantes');
        $response->assertSee('Sin fecha de inicio');
        $response->assertSee('01-07-2026');
        $response->assertSee('$0');
        $response->assertSee('No hay cobros asociados a este contrato.');
    }

    private function createActiveContractScenario(string $direccion, string $pendingState): array
    {
        $arrendador = Cliente::create(['nombre' => "Arrendador {$direccion}", 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => "Arrendatario {$direccion}", 'fecha_creacion' => now()]);
        $corredor = Cliente::firstOrCreate(['id' => 1], ['nombre' => 'Corredor Principal', 'fecha_creacion' => now()]);
        $propiedad = Propiedad::create(['direccion' => $direccion, 'propietario' => $arrendador->id]);
        $unidad = Unidad::create(['nombre' => "Unidad {$direccion}", 'Propiedad_id' => $propiedad->id]);
        $contrato = Contrato::create([
            'Unidad_id' => $unidad->id,
            'administracion' => true,
            'renta' => 500000,
            'garantia' => 500000,
            'fecha_inicio' => '2025-01-01 00:00:00',
        ]);

        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $corredor->id, 'rol' => 'Corredor']);

        $cobro = Cobro::create([
            'tipo' => 'Renta pendiente propiedad',
            'monto' => 120000,
            'estado' => $pendingState,
            'fecha_cobro' => '2026-01-15 10:00:00',
            'Contrato_id' => $contrato->id,
            'Propiedad_id' => $propiedad->id,
            'Unidad_id' => $unidad->id,
        ]);
        ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Deudor', 'monto' => 120000]);
        ParticipanteCobro::create(['Cobro_id' => $cobro->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Acreedor', 'monto' => 120000]);

        return compact('arrendador', 'arrendatario', 'corredor', 'propiedad', 'unidad', 'contrato', 'cobro');
    }
}
