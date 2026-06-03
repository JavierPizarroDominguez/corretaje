<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\CrearAdministracionRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CrearAdministracionRequestTest extends TestCase
{
    /**
     * Helper: run validation and return the Validator instance.
     */
    protected function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new CrearAdministracionRequest;

        return Validator::make($data, $request->rules());
    }

    protected function validData(): array
    {
        return [
            'arrendador_nombre' => 'Juan Pérez',
            'arrendatario_nombre' => 'María López',
            'propiedad_direccion' => 'Av. Principal 123',
            'unidad_nombre' => 'Depto 101',
            'renta' => 500000,
            'dia_pago' => 5,
            'sin_administracion' => false,
            'comision_inicial' => 1500000,
            'cobrar_arrendador' => true,
            'cobrar_arrendatario' => true,
            'no_comision_inicial' => false,
            'egreso_renta' => 450000,
            'comision_mensual' => 50000,
            'no_comision_mensual' => false,
            'garantia' => 1500000,
            'no_garantia' => false,
            'fecha_inicio' => '2026-06-15',
            'fecha_termino' => '2027-06-14',
            'contrato_file' => null,
        ];
    }

    public function test_valid_data_passes_validation(): void
    {
        $validator = $this->validate($this->validData());
        $this->assertTrue($validator->passes(), 'Valid data must pass: '.json_encode($validator->errors()->all()));
    }

    public function test_arrendador_nombre_is_required(): void
    {
        $validator = $this->validate(array_merge($this->validData(), ['arrendador_nombre' => '']));
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('arrendador_nombre', $validator->errors()->toArray());
    }

    public function test_propiedad_corredor_is_optional_boolean(): void
    {
        $validator = $this->validate(array_merge($this->validData(), ['propiedad_corredor' => true]));
        $this->assertTrue($validator->passes(), 'propiedad_corredor must be optional boolean');
    }

    public function test_arrendatario_nombre_is_required(): void
    {
        $validator = $this->validate(array_merge($this->validData(), ['arrendatario_nombre' => '']));
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('arrendatario_nombre', $validator->errors()->toArray());
    }

    public function test_propiedad_direccion_is_required(): void
    {
        $validator = $this->validate(array_merge($this->validData(), ['propiedad_direccion' => '']));
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('propiedad_direccion', $validator->errors()->toArray());
    }

    public function test_renta_is_required_and_must_be_integer(): void
    {
        $validator = $this->validate(array_merge($this->validData(), ['renta' => 'not-a-number']));
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('renta', $validator->errors()->toArray());
    }

    public function test_renta_must_not_be_negative(): void
    {
        $validator = $this->validate(array_merge($this->validData(), ['renta' => -100]));
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('renta', $validator->errors()->toArray());
    }

    public function test_dia_pago_must_be_between_1_and_31(): void
    {
        $validator = $this->validate(array_merge($this->validData(), ['dia_pago' => 0]));
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('dia_pago', $validator->errors()->toArray());

        $validator = $this->validate(array_merge($this->validData(), ['dia_pago' => 32]));
        $this->assertTrue($validator->fails());

        $validator = $this->validate(array_merge($this->validData(), ['dia_pago' => 15]));
        $this->assertTrue($validator->passes(), 'dia_pago=15 must be valid');
    }

    public function test_fecha_inicio_is_optional(): void
    {
        $data = $this->validData();
        unset($data['fecha_inicio']);
        $validator = $this->validate($data);
        $this->assertTrue($validator->passes(), 'fecha_inicio must be optional');
    }

    public function test_sin_administracion_is_optional_boolean(): void
    {
        $data = $this->validData();
        unset($data['sin_administracion']);
        $validator = $this->validate($data);
        $this->assertTrue($validator->passes(), 'sin_administracion must be optional');
    }

    public function test_comision_inicial_and_garantia_are_optional(): void
    {
        $data = $this->validData();
        unset($data['comision_inicial']);
        unset($data['garantia']);
        $validator = $this->validate($data);
        $this->assertTrue($validator->passes(), 'comision_inicial and garantia must be nullable');
    }

    public function test_checkbox_fields_are_optional_booleans(): void
    {
        $data = $this->validData();
        unset($data['cobrar_arrendador']);
        unset($data['cobrar_arrendatario']);
        unset($data['no_comision_inicial']);
        unset($data['no_comision_mensual']);
        unset($data['no_garantia']);
        $validator = $this->validate($data);
        $this->assertTrue($validator->passes(), 'Boolean checkbox fields must be optional');
    }

    public function test_servicios_array_is_optional(): void
    {
        $data = $this->validData();
        $data['servicios'] = [
            ['tipo' => 'Luz', 'dia' => 5, 'monto' => 15000],
            ['tipo' => 'Agua', 'dia' => 10],
        ];
        $validator = $this->validate($data);
        $this->assertTrue($validator->passes(), 'Valid servicios array must pass');
    }

    public function test_comision_mensual_cannot_exceed_egreso_renta(): void
    {
        $validator = $this->validate(array_merge($this->validData(), [
            'egreso_renta' => 400000,
            'comision_mensual' => 500000,
        ]));
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('comision_mensual', $validator->errors()->toArray());
    }

    public function test_comision_mensual_equal_to_egreso_renta_passes(): void
    {
        $validator = $this->validate(array_merge($this->validData(), [
            'egreso_renta' => 250000,
            'comision_mensual' => 250000,
        ]));
        $this->assertTrue($validator->passes(), 'comision_mensual == egreso_renta must pass');
    }

    public function test_authorize_returns_true(): void
    {
        $request = new CrearAdministracionRequest;
        $this->assertTrue($request->authorize());
    }
}
