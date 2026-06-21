<?php

namespace App\Http\Requests;

use App\Models\Contrato;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class TerminarContratoRequest extends FormRequest
{
    public const ALLOWED_DISCOUNT_CONCEPTS = [
        'Aseo Final',
        'Reparación',
        'Extra',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descuentos' => ['sometimes', 'array'],
            'descuentos.*.concepto' => ['required_with:descuentos', 'string', Rule::in(self::ALLOWED_DISCOUNT_CONCEPTS)],
            'descuentos.*.detalle' => ['nullable', 'string', 'max:500'],
            'descuentos.*.monto' => ['required_with:descuentos', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $contrato = $this->route('contrato');
            $garantia = $contrato instanceof Contrato ? (int) $contrato->garantia : 0;
            $totalDescuentos = collect($this->input('descuentos', []))->sum(function ($descuento): int {
                return (int) ($descuento['monto'] ?? 0);
            });

            if ($totalDescuentos > $garantia) {
                $validator->errors()->add('descuentos', 'El total de descuentos no puede superar la garantía.');
            }
        });
    }

    public function discounts(): array
    {
        return $this->validated('descuentos', []);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'errors' => $validator->errors(),
        ], 422));
    }
}
