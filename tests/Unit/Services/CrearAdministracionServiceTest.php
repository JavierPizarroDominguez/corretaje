<?php

namespace Tests\Unit\Services;

use App\Http\Requests\CrearAdministracionRequest;
use App\Models\Ciudad;
use App\Models\Cliente;
use App\Services\CrearAdministracionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CrearAdministracionServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected CrearAdministracionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CrearAdministracionService;
    }

    /**
     * Helper: build a valid CrearAdministracionRequest from array data.
     */
    protected function makeRequest(array $data): CrearAdministracionRequest
    {
        $defaults = $this->validData();

        return new CrearAdministracionRequest(array_merge($defaults, $data));
    }

    /**
     * Returns minimal valid data for a happy-path Contrato with administracion=true.
     */
    protected function validData(): array
    {
        return [
            'arrendador_nombre' => 'Juan Pérez',
            'arrendatario_nombre' => 'María López',
            'propiedad_direccion' => 'Av. Principal 123',
            'unidad_nombre' => 'Depto 101',
            'sin_administracion' => false,
            'renta' => 500000,
            'comision_mensual' => 50000,
            'dia_pago' => 5,
            'comision_inicial' => 1500000,
            'cobrar_arrendador' => true,
            'cobrar_arrendatario' => true,
            'no_comision_inicial' => false,
            'egreso_renta' => 450000,
            'no_comision_mensual' => false,
            'garantia' => 1500000,
            'no_garantia' => false,
            'fecha_firma' => '2026-06-01',
            'fecha_inicio' => '2026-06-15',
            'fecha_termino' => '2027-06-14',
            'servicios' => [
                ['tipo' => 'Luz', 'dia' => 5, 'monto' => null],
                ['tipo' => 'Agua', 'dia' => 10, 'monto' => null],
                ['tipo' => 'Gastos Comunes', 'dia' => 15, 'monto' => 25000],
            ],
        ];
    }

    public function test_crear_administracion_returns_contrato_with_3_participantes(): void
    {
        // Seed required Ciudad for foreign key (if schema requires it)
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest([]);
        $contrato = $this->service->crearAdministracion($request);

        $this->assertNotNull($contrato->id, 'Contrato must be persisted');
        $this->assertEquals(3, $contrato->participante_contratos()->count(), 'Contrato must have exactly 3 participantes');
    }

    public function test_crear_administracion_resolves_arrendador_by_nombre_or_creates_new(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        // Pre-existing arrendador
        $arrendadorExistente = Cliente::create([
            'nombre' => 'Juan Pérez',
            'fecha_creacion' => now(),
        ]);

        $request = $this->makeRequest([
            'arrendador_nombre' => 'Juan Pérez',
        ]);

        $contrato = $this->service->crearAdministracion($request);
        $participanteArrendador = $contrato->participante_contratos()
            ->where('rol', 'Arrendador')
            ->first();

        $this->assertEquals($arrendadorExistente->id, $participanteArrendador->Cliente_id);
    }

    public function test_crear_administracion_creates_propiedad_and_unidad(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest([
            'propiedad_direccion' => 'Calle Falsa 999',
            'unidad_nombre' => 'Oficina 5',
        ]);

        $contrato = $this->service->crearAdministracion($request);

        $this->assertNotNull($contrato->unidad, 'Contrato must have a Unidad');
        $this->assertNotNull($contrato->unidad->propiedad, 'Unidad must have a Propiedad');
        $this->assertEquals('Calle Falsa 999', $contrato->unidad->propiedad->direccion);
        $this->assertEquals('Oficina 5', $contrato->unidad->nombre);
    }

    public function test_crear_administracion_creates_ingreso_renta_arrendatario_cobro_when_administracion_true(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest(['sin_administracion' => false]);
        $contrato = $this->service->crearAdministracion($request);

        $cobroIngreso = $contrato->cobros()
            ->where('tipo', 'Ingreso Renta Arrendatario')
            ->first();

        $this->assertNotNull($cobroIngreso, 'Must create Ingreso Renta Arrendatario cobro');
        $this->assertEquals(500000, $cobroIngreso->monto);
    }

    public function test_crear_administracion_creates_egreso_renta_arrendador_when_arrendador_not_corredor(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        // Ensure corredor id=1 is different from arrendador
        $corredor = Cliente::findOrFail(1);

        $request = $this->makeRequest([
            'arrendador_nombre' => 'Arrendador Diferente',
        ]);

        $contrato = $this->service->crearAdministracion($request);

        $cobroEgreso = $contrato->cobros()
            ->where('tipo', 'Egreso Renta Arrendador')
            ->first();

        $this->assertNotNull($cobroEgreso, 'Must create Egreso when arrendador is not corredor');
        $this->assertEquals(450000, $cobroEgreso->monto);
    }

    public function test_crear_administracion_skips_egreso_cobros_when_corredor_is_arrendador(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        // Set arrendador to the existing corredor (id=1 seeded in DB)
        $corredor = Cliente::findOrFail(1);
        $request = $this->makeRequest([
            'arrendador_nombre' => $corredor->nombre,
        ]);

        $contrato = $this->service->crearAdministracion($request);

        $egresoCobros = $contrato->cobros()
            ->where('tipo', 'like', 'Egreso%')
            ->get();

        $this->assertCount(0, $egresoCobros, 'Must NOT create Egreso cobros when arrendador = corredor id=1');
    }

    public function test_crear_administracion_creates_comision_pairs_when_comision_inicial_provided(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest([
            'comision_inicial' => 1500000,
        ]);

        $contrato = $this->service->crearAdministracion($request);

        $comisionArrendador = $contrato->cobros()
            ->where('tipo', 'Comision inicial arrendador')
            ->first();

        $comisionArrendatario = $contrato->cobros()
            ->where('tipo', 'Comision inicial arrendatario')
            ->first();

        $this->assertNotNull($comisionArrendador, 'Must create Comision inicial arrendador');
        $this->assertNotNull($comisionArrendatario, 'Must create Comision inicial arrendatario');
        $this->assertEquals(1500000, $comisionArrendador->monto);
    }

    public function test_crear_administracion_creates_garantia_pairs_when_garantia_provided(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest([
            'garantia' => 1500000,
        ]);

        $contrato = $this->service->crearAdministracion($request);

        $ingresoGarantia = $contrato->cobros()
            ->where('tipo', 'Ingreso Garantía Arrendatario')
            ->first();

        $egresoGarantia = $contrato->cobros()
            ->where('tipo', 'Egreso Garantía Arrendador')
            ->first();

        $this->assertNotNull($ingresoGarantia, 'Must create Ingreso Garantía Arrendatario');
        $this->assertNotNull($egresoGarantia, 'Must create Egreso Garantía Arrendador');
    }

    public function test_crear_administracion_creates_no_cobros_when_sin_administracion_true(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest(['sin_administracion' => true]);

        $contrato = $this->service->crearAdministracion($request);

        $this->assertCount(0, $contrato->cobros, 'Must create no cobros when sin_administracion=true');
        $this->assertFalse($contrato->administracion);
    }

    public function test_crear_administracion_creates_servicios_from_array(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest([
            'sin_administracion' => false,
            'dia_pago' => 10,
            'servicios' => [
                ['tipo' => 'Luz', 'dia' => 5, 'monto' => null],
                ['tipo' => 'Agua', 'dia' => 10, 'monto' => null],
                ['tipo' => 'Gas', 'dia' => 15, 'monto' => 20000],
            ],
        ]);

        $contrato = $this->service->crearAdministracion($request);
        $propiedad = $contrato->unidad->propiedad;

        $luz = $propiedad->servicios()->where('tipo', 'Luz')->first();
        $agua = $propiedad->servicios()->where('tipo', 'Agua')->first();
        $gas = $propiedad->servicios()->where('tipo', 'Gas')->first();
        $gc = $propiedad->servicios()->where('tipo', 'Gastos Comunes')->first();

        $this->assertNotNull($luz, 'Must create Luz servicio');
        $this->assertNotNull($agua, 'Must create Agua servicio');
        $this->assertNotNull($gas, 'Must create Gas servicio');
        $this->assertNull($gc, 'Must NOT create Gastos comunes when not in array');
        $this->assertEquals(5, $luz->dia_pago);
        $this->assertEquals(20000, $gas->monto_fijo);
    }

    public function test_crear_administracion_creates_no_servicios_when_servicios_empty(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest([
            'sin_administracion' => false,
            'servicios' => [],
        ]);

        $contrato = $this->service->crearAdministracion($request);
        $propiedad = $contrato->unidad->propiedad;

        $this->assertCount(0, $propiedad->servicios, 'Must create no servicios when servicios array is empty');
    }

    public function test_crear_administracion_stores_all_contract_fields(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest([
            'renta' => 600000,
            'comision_mensual' => 60000,
            'dia_pago' => 15,
            'fecha_inicio' => '2026-07-01',
            'fecha_termino' => '2027-07-01',
        ]);

        $contrato = $this->service->crearAdministracion($request);

        $this->assertEquals(600000, $contrato->renta);
        $this->assertEquals(60000, $contrato->comision_mensual);
        $this->assertEquals(15, $contrato->dia_pago);
        $this->assertEquals('2026-07-01', $contrato->fecha_inicio->format('Y-m-d'));
        $this->assertEquals('2027-07-01', $contrato->fecha_termino->format('Y-m-d'));
        $this->assertTrue($contrato->administracion);
    }

    public function test_comision_inicial_is_ignored_when_sin_administracion_true(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest([
            'sin_administracion' => true,
            'comision_inicial' => 1500000,
            'garantia' => 1500000,
        ]);

        $contrato = $this->service->crearAdministracion($request);

        $this->assertCount(0, $contrato->cobros, 'Must ignore comision and garantia when sin_administracion=true');
    }

    public function test_garantia_is_ignored_when_null(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest([
            'sin_administracion' => false,
            'garantia' => null,
        ]);

        $contrato = $this->service->crearAdministracion($request);

        $garantiaCobros = $contrato->cobros()
            ->where('tipo', 'like', '%Garantía%')
            ->get();

        $this->assertCount(0, $garantiaCobros, 'Must not create garantia cobros when garantia is null');
    }

    public function test_no_comision_inicial_skips_comision_cobros(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest([
            'comision_inicial' => 1500000,
            'no_comision_inicial' => true,
        ]);

        $contrato = $this->service->crearAdministracion($request);

        $comisionCobros = $contrato->cobros()
            ->where('tipo', 'like', 'Comision inicial%')
            ->get();

        $this->assertCount(0, $comisionCobros, 'Must not create comision cobros when no_comision_inicial=true');
    }

    public function test_cobrar_flags_control_comision_creation(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest([
            'comision_inicial' => 1500000,
            'cobrar_arrendador' => false,
            'cobrar_arrendatario' => true,
        ]);

        $contrato = $this->service->crearAdministracion($request);

        $comisionArrendador = $contrato->cobros()
            ->where('tipo', 'Comision inicial arrendador')
            ->first();

        $comisionArrendatario = $contrato->cobros()
            ->where('tipo', 'Comision inicial arrendatario')
            ->first();

        $this->assertNull($comisionArrendador, 'Must NOT create Comision inicial arrendador when cobrar_arrendador=false');
        $this->assertNotNull($comisionArrendatario, 'Must create Comision inicial arrendatario when cobrar_arrendatario=true');
    }

    public function test_no_garantia_skips_garantia_cobros(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest([
            'garantia' => 1500000,
            'no_garantia' => true,
        ]);

        $contrato = $this->service->crearAdministracion($request);

        $garantiaCobros = $contrato->cobros()
            ->where('tipo', 'like', '%Garantía%')
            ->get();

        $this->assertCount(0, $garantiaCobros, 'Must not create garantia cobros when no_garantia=true');
    }

    public function test_no_comision_mensual_sets_comision_to_zero(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest([
            'renta' => 500000,
            'no_comision_mensual' => true,
        ]);

        $contrato = $this->service->crearAdministracion($request);

        $this->assertEquals(0, $contrato->comision_mensual);
    }

    public function test_cobros_have_propiedad_and_unidad_ids_on_creation(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        $request = $this->makeRequest([
            'propiedad_direccion' => 'Av. Italia 1234',
            'unidad_nombre' => 'Depto 5',
        ]);

        $contrato = $this->service->crearAdministracion($request);

        $propiedad = $contrato->unidad->propiedad;
        $unidad = $contrato->unidad;

        foreach ($contrato->cobros as $cobro) {
            $this->assertNotNull(
                $cobro->Propiedad_id,
                "Cobro [{$cobro->id}] tipo [{$cobro->tipo}] must have non-null Propiedad_id"
            );
            $this->assertEquals(
                $propiedad->id,
                $cobro->Propiedad_id,
                "Cobro [{$cobro->id}] tipo [{$cobro->tipo}] must have correct Propiedad_id"
            );
            $this->assertNotNull(
                $cobro->Unidad_id,
                "Cobro [{$cobro->id}] tipo [{$cobro->tipo}] must have non-null Unidad_id"
            );
            $this->assertEquals(
                $unidad->id,
                $cobro->Unidad_id,
                "Cobro [{$cobro->id}] tipo [{$cobro->tipo}] must have correct Unidad_id"
            );
        }
    }

    public function test_cobros_have_correct_fk_values_when_entities_reused(): void
    {
        Ciudad::create(['id' => 1, 'nombre' => 'Santiago']);

        // Pre-create propiedad and unidad (simulating reuse scenario)
        $propiedad = \App\Models\Propiedad::create([
            'direccion' => 'Reutilizada 555',
            'propietario' => 1,
        ]);
        $unidad = \App\Models\Unidad::create([
            'nombre' => 'Local 2',
            'Propiedad_id' => $propiedad->id,
        ]);

        // First contract using the pre-created entities via propiedad_id
        $request1 = $this->makeRequest([
            'propiedad_id' => $propiedad->id,
            'unidad_nombre' => 'Local 2',
        ]);
        $contrato1 = $this->service->crearAdministracion($request1);

        // Second contract reusing same entities
        $request2 = $this->makeRequest([
            'arrendador_nombre' => 'Otro Arrendador',
            'arrendatario_nombre' => 'Otro Arrendatario',
            'propiedad_id' => $propiedad->id,
            'unidad_nombre' => 'Local 2',
        ]);
        $contrato2 = $this->service->crearAdministracion($request2);

        foreach ($contrato2->cobros as $cobro) {
            $this->assertEquals(
                $propiedad->id,
                $cobro->Propiedad_id,
                "Reused propiedad: Cobro [{$cobro->id}] must have Propiedad_id={$propiedad->id}"
            );
            $this->assertEquals(
                $unidad->id,
                $cobro->Unidad_id,
                "Reused unidad: Cobro [{$cobro->id}] must have Unidad_id={$unidad->id}"
            );
        }
    }
}
