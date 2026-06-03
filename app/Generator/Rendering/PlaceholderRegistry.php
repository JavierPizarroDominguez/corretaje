<?php

namespace App\Generator\Rendering;

class PlaceholderRegistry
{
    // Convierte un nombre de columna en un label legible
    // comision_inicial → Comisión Inicial
    // Unidad_id        → Unidad
    // fecha_firma      → Fecha Firma
    public static function toLabel(string $fieldName): string
    {
        // Quitar sufijo _id
        $name = preg_replace('/_id$/i', '', $fieldName);

        // snake_case → palabras
        $words = str_replace('_', ' ', $name);

        // Capitalizar cada palabra
        $label = ucwords($words);

        // Acentos comunes en español
        $accents = [
            'Comision'  => 'Comisión',
            'Direccion' => 'Dirección',
            'Creacion'  => 'Creación',
            'Acion'     => 'Acción',
            'Habitacion' => 'Habitación',
        ];

        return strtr($label, $accents);
    }

    // Convierte nombre de campo/relación a PascalCase para IDs JS
    // arrendador → Arrendador
    // Ciudad_id  → CiudadId
    public static function toPascal(string $name): string
    {
        return str_replace('_', '', ucwords($name, '_'));
    }

    // Convierte nombre de campo a camelCase para variables PHP
    // Ciudad_id → ciudadId
    public static function toCamel(string $name): string
    {
        $pascal = self::toPascal($name);
        return lcfirst($pascal);
    }
}
