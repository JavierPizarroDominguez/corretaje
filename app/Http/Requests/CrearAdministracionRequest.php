<?php

namespace App\Http\Requests;

use App\Rules\UniquePropiedadDireccion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CrearAdministracionRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize numeric inputs: strip leading zeros and non-numeric chars
        $numericFields = ['renta', 'comision_inicial', 'comision_mensual', 'egreso_renta', 'garantia', 'dia_pago'];
        $sanitized = [];
        foreach ($numericFields as $field) {
            if ($this->has($field) && $this->input($field) !== null && $this->input($field) !== '') {
                $raw = (string) $this->input($field);
                $cleaned = preg_replace('/[^0-9]/', '', $raw);
                $cleaned = ltrim($cleaned, '0');
                $sanitized[$field] = $cleaned === '' ? '0' : $cleaned;
            }
        }
        if (!empty($sanitized)) {
            $this->merge($sanitized);
        }

        // If no_comision_inicial is true, clear comision_inicial to avoid validation errors
        if ($this->boolean('no_comision_inicial')) {
            $this->merge([
                'comision_inicial' => null,
            ]);
        }

        // If no_comision_mensual is true, clear comision_mensual to avoid validation errors
        if ($this->boolean('no_comision_mensual')) {
            $this->merge([
                'comision_mensual' => null,
            ]);
        }

        // If no_garantia is true, clear garantia to avoid validation errors
        if ($this->boolean('no_garantia')) {
            $this->merge([
                'garantia' => null,
            ]);
        }

        // If sin_administracion is true, clear renta, dia_pago, comision_mensual, egreso_renta
        if ($this->boolean('sin_administracion')) {
            $this->merge([
                'renta' => null,
                'dia_pago' => null,
                'comision_mensual' => null,
                'egreso_renta' => null,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Arrendador (solo nombre, idéntico al legacy)
            'arrendador_nombre' => ['required', 'string', 'max:255'],
            'propiedad_corredor' => ['nullable', 'boolean'],

            // Arrendatario (solo nombre, idéntico al legacy)
            'arrendatario_nombre' => ['required', 'string', 'max:255'],

            // Propiedad
            'propiedad_direccion' => ['required_without:propiedad_id', 'nullable', 'string', 'max:500', new UniquePropiedadDireccion()],
            'propiedad_id' => ['nullable', 'integer'],
            'unidad_nombre' => ['nullable', 'string', 'max:255'],

            // Contrato (legacy step 4)
            'renta' => ['nullable', 'integer', 'min:1', 'required_if:sin_administracion,0', 'required_if:sin_administracion,null'],
            'dia_pago' => ['nullable', 'integer', 'between:1,28'],
            'sin_administracion' => ['nullable', 'boolean'],

            // Comisión Inicial (legacy step 5)
            'comision_inicial' => ['nullable', 'integer', 'min:1', 'lte:renta'],
            'cobrar_arrendador' => ['nullable', 'boolean'],
            'cobrar_arrendatario' => ['nullable', 'boolean'],
            'no_comision_inicial' => ['nullable', 'boolean'],

            // Egreso (legacy step 6)
            'egreso_renta' => ['nullable', 'integer', 'min:0'],
            'comision_mensual' => ['nullable', 'integer', 'min:1', 'lt:renta', 'lte:egreso_renta'],
            'no_comision_mensual' => ['nullable', 'boolean'],

            // Garantía (legacy step 7)
            'garantia' => ['nullable', 'integer', 'min:0'],
            'no_garantia' => ['nullable', 'boolean'],

            // Servicios (legacy step 8 - dynamic array)
            'servicios' => ['nullable', 'array'],
            'servicios.*.tipo' => ['required', 'string'],
            'servicios.*.dia' => ['nullable', 'integer', 'between:1,28'],
            'servicios.*.monto' => ['nullable'],

            // Fechas
            'fecha_firma' => ['nullable', 'date'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_termino' => ['nullable', 'date'],

            // Extras (legacy step 9 - contrato)
            'contrato_file' => ['nullable', 'file', 'max:10240'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'arrendador_nombre.required' => 'El nombre del arrendador es obligatorio.',
            'arrendatario_nombre.required' => 'El nombre del arrendatario es obligatorio.',
            'propiedad_direccion.required' => 'La dirección de la propiedad es obligatoria.',
            'renta.required' => 'El monto de la renta es obligatorio.',
            'renta.integer' => 'La renta debe ser un número entero.',
            'renta.min' => 'La renta debe ser mayor que 0.',
            'dia_pago.between' => 'El día de pago debe estar entre 1 y 28.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_termino.after' => 'La fecha de término debe ser posterior a la fecha de inicio.',
            'servicios.*.tipo.required' => 'El tipo de servicio es obligatorio.',
            'comision_mensual.lt' => 'La comisión mensual debe ser menor que la renta.',
            'comision_mensual.lte' => 'La comisión mensual no puede ser mayor que el egreso renta.',
            'comision_inicial.min' => 'La comisión inicial debe ser mayor que 0.',
            'comision_inicial.lte' => 'La comisión inicial no puede ser mayor que la renta.',
            'comision_mensual.min' => 'La comisión mensual debe ser mayor que 0.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            redirect()->back()
                ->withErrors($validator)
                ->withInput()
        );
    }
}
