<?php

namespace App\Generator\Config;

class ConfigWriter
{
    private string $configPath;

    public function __construct()
    {
        $this->configPath = config_path('generator.php');
    }

    public function saveDisplayField(string $referencedTable, string $displayField): void
    {
        $this->modifyConfig(function (array &$config) use ($referencedTable, $displayField) {
            if (!isset($config['display_fields'])) {
                $config['display_fields'] = [];
            }
            $config['display_fields'][$referencedTable] = $displayField;
        });
    }

    public function saveSearchPaths(string $table, array $paths): void
    {
        $this->modifyConfig(function (array &$config) use ($table, $paths) {
            if (!isset($config['search_paths'])) {
                $config['search_paths'] = [];
            }
            $config['search_paths'][$table] = $paths;
        });
    }

    public function saveRelationType(
        string $table,
        string $fkColumn,
        string $type,
        string $displayField,
        string $referencedTable
    ): void {
        $this->modifyConfig(function (array &$config) use ($table, $fkColumn, $type, $displayField, $referencedTable) {
            if (!isset($config[$table])) {
                $config[$table] = [];
            }
            if (!isset($config[$table]['relations'])) {
                $config[$table]['relations'] = [];
            }
            $config[$table]['relations'][$fkColumn] = [
                'type'          => $type,
                'display_field' => $displayField,
                'relation_name' => $this->fkToRelationName($fkColumn),
                'related_route' => $referencedTable,
            ];
        });
    }

    private function modifyConfig(callable $modifier): void
    {
        $config = $this->loadAsArray();
        $modifier($config);
        $this->writeAsPhp($config);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($this->configPath, true);
        }
    }

    private function loadAsArray(): array
    {
        if (!file_exists($this->configPath)) {
            return [];
        }
        try {
            return require $this->configPath;
        } catch (\Throwable) {
            return [];
        }
    }

    private function writeAsPhp(array $config): void
    {
        $php  = "<?php\n\nreturn [\n\n";
        $php .= $this->exportArray($config, 1);
        $php .= "];\n";

        file_put_contents($this->configPath, $php);
    }

    private function exportArray(array $array, int $depth): string
    {
        $indent = str_repeat('    ', $depth);
        $output = '';

        foreach ($array as $key => $value) {
            $keyStr = is_string($key) ? "'{$key}'" : $key;

            if (is_array($value)) {
                if (empty($value)) {
                    $output .= "{$indent}{$keyStr} => [],\n";
                } else {
                    $output .= "{$indent}{$keyStr} => [\n";
                    $output .= $this->exportArray($value, $depth + 1);
                    $output .= "{$indent}],\n";
                }
            } elseif (is_bool($value)) {
                $output .= "{$indent}{$keyStr} => " . ($value ? 'true' : 'false') . ",\n";
            } elseif (is_null($value)) {
                $output .= "{$indent}{$keyStr} => null,\n";
            } elseif (is_int($value) || is_float($value)) {
                $output .= "{$indent}{$keyStr} => {$value},\n";
            } else {
                $escaped = str_replace("'", "\\'", (string) $value);
                $output .= "{$indent}{$keyStr} => '{$escaped}',\n";
            }
        }

        return $output;
    }

    private function fkToRelationName(string $fkColumn): string
    {
        return strtolower(preg_replace('/_id$/i', '', $fkColumn));
    }
}
