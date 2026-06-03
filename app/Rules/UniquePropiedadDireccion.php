<?php

namespace App\Rules;

use App\Models\Propiedad;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class UniquePropiedadDireccion implements ValidationRule, DataAwareRule
{
    protected array $data = [];

    /**
     * Set the data under validation ( Laravel passes this automatically ).
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Determine if the direccion is unique (case-insensitive).
     *
     * Only applies when propiedad_id is empty (i.e., creating a new propiedad).
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        // Only validate uniqueness when creating a new propiedad
        if (!empty($this->data['propiedad_id'])) {
            return;
        }

        $exists = Propiedad::whereRaw('LOWER(direccion) = ?', [strtolower($value)])->exists();

        if ($exists) {
            $fail('La dirección ":input" ya existe. Seleccione la propiedad existente del listado.');
        }
    }
}
