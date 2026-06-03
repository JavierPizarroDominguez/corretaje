<?php

namespace App\Generator\Rendering;

use App\Generator\Schema\TableSchema;

class ComponentSplitter
{
    // Devuelve array: ['nombre-component' => ['campo1', 'campo2', ...]]
    // Si config dice '__all__', todos los campos van en ese component
    public function split(TableSchema $schema): array
    {
        $components = [];

        foreach ($schema->components as $componentName => $fieldNames) {
            if ($fieldNames === '__all__') {
                // Todos los campos no-PK en este component
                $components[$componentName] = array_map(
                    fn($col) => $col->name,
                    array_filter($schema->columns, fn($col) => !$col->isPrimaryKey)
                );
            } else {
                $components[$componentName] = $fieldNames;
            }
        }

        return $components;
    }
}
