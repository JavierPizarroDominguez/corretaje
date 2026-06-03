<?php

namespace App\Generator\Config;

use Illuminate\Support\Str;

class ConfigLoader
{
    private array $config;

    public function __construct()
    {
        $this->config = config('generator', []);
    }

    /**
     * Recarga la config desde Laravel (llamar después de ConfigWriter + reloadConfig).
     */
    public function refresh(): void
    {
        $this->config = config('generator', []);
    }

    /**
     * Devuelve la configuración completa de una tabla con defaults aplicados.
     * La config de generator.php sobreescribe los defaults campo a campo.
     */
    public function load(string $table): array
    {
        $tableConfig = $this->config[$table] ?? [];

        $defaults = $this->defaults($table);

        // Merge profundo para 'fields', 'relations', 'constraints', 'components'
        return array_merge($defaults, $tableConfig);
    }

    /**
     * Devuelve la config de un campo específico dentro de 'fields'.
     */
    public function fieldConfig(string $table, string $field): array
    {
        $config = $this->load($table);
        return $config['fields'][$field] ?? [];
    }

    /**
     * Devuelve la config de una FK específica dentro de 'relations'.
     */
    public function relationConfig(string $table, string $fkColumn): array
    {
        $config = $this->load($table);
        return $config['relations'][$fkColumn] ?? [];
    }

    /**
     * Devuelve las relaciones especiales (hasOne con where, etc.).
     */
    public function specialRelations(string $table): array
    {
        $config = $this->load($table);
        return $config['special_relations'] ?? [];
    }

    /**
     * ¿Existe config explícita para esta tabla?
     */
    public function has(string $table): bool
    {
        return isset($this->config[$table]);
    }

    /**
     * Defaults inteligentes para cualquier tabla sin config.
     */
    private function defaults(string $table): array
    {
        $modelName = $this->tableToModel($table);

        return [
            'model'             => config('generator.model_namespace', 'App\\Models\\') . $modelName,
            'route_base'        => $table,
            'pk'                => [],  // Vacío = detecta automáticamente desde la BD
            'eager_load'        => [],
            'fields'            => [],
            'relations'         => [],
            'special_relations' => [],
            'constraints'       => [],
            'components'        => [
                "datos-{$table}" => '__all__',
            ],
        ];
    }

    /**
     * contrato              → Contrato
     * participante_contrato → ParticipanteContrato
     */
    private function tableToModel(string $table): string
    {
        return Str::studly($table);
    }
}
