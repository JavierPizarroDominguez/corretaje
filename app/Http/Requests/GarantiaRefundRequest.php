<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class GarantiaRefundRequest extends FormRequest
{
    private const DISCOUNT_CONCEPTS = [
        'Aseo Final',
        'Reparación',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descuentos' => ['sometimes', 'array'],
            'descuentos.*.concepto' => ['required_with:descuentos', 'string', 'max:100', Rule::in(self::DISCOUNT_CONCEPTS)],
            'descuentos.*.detalle' => ['nullable', 'string', 'max:255'],
            'descuentos.*.monto' => ['required_with:descuentos', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'descuentos.array' => 'Los descuentos deben enviarse como una lista',
            'descuentos.*.concepto.required_with' => 'El concepto del descuento es obligatorio',
            'descuentos.*.concepto.in' => 'El concepto del descuento no es válido',
            'descuentos.*.monto.required_with' => 'El monto del descuento es obligatorio',
            'descuentos.*.monto.integer' => 'El monto del descuento debe ser un entero',
            'descuentos.*.monto.min' => 'El monto del descuento no puede ser negativo',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'errors' => $validator->errors(),
        ], 422));
    }
}
