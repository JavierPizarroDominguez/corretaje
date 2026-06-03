<?php

namespace App\Generator\Commands;

use App\Generator\Config\ConfigLoader;
use App\Generator\Config\ConfigWriter;
use App\Generator\Introspection\ColumnMetadata;
use App\Generator\Schema\TableSchema;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FkInterviewer
{
    public function __construct(
        private ConfigLoader $configLoader,
        private ConfigWriter $configWriter,
    ) {}

    /**
     * Recorre todas las FK del schema y pregunta lo necesario.
     * Solo pregunta cuando falta información en config.
     * Persiste las respuestas en generator.php antes de continuar.
     *
     * Devuelve el array de config actualizado que SchemaBuilder debe usar.
     */
    public function interview(TableSchema $schema, Command $command): void
    {
        $command->newLine();
        $command->line('<fg=yellow>Configurando búsqueda...</>');

        $globalConfig  = $this->configLoader->load($schema->table);
        $displayFields = config('generator.display_fields', []);
        $hasChanges    = false;

        // ── Rutas de búsqueda para la tabla principal ──────────────────
        // Para tablas pivote, no tiene sentido un buscador propio
        $selfPaths = config("generator.search_paths.{$schema->table}", null);
        if (!$selfPaths) {
            // Las tablas pivote no tienen buscador propio (usan el de sus padres)
            if ($schema->isPivotTable) {
                $selfPaths = ['__none__'];
                $command->line("  <fg=yellow>Tabla pivote: sin buscador propio</>");
            } else {
                $selfPaths = $this->askSearchPaths(
                    $schema->table,
                    $schema->modelClass,
                    $command,
                    $schema->modelName
                );
            }

            $this->configWriter->saveSearchPaths($schema->table, $selfPaths);
            // display_field = último segmento del primer path
            if (!empty($selfPaths) && $selfPaths[0] !== '__none__') {
                $firstParts = explode('.', $selfPaths[0]);
                $this->configWriter->saveDisplayField($schema->table, end($firstParts));
            }
            $hasChanges = true;
            $command->line("  <fg=green>✓ Guardado: search_paths['{$schema->table}']</>");
        }

        // ── FK questions ──────────────────────────────────────────────
        // Para tablas pivote, las PK-FK SÍ necesitan display field (para el index)
        // Para tablas normales, excluimos las PK
        $shouldExcludePk = !$schema->isPivotTable;
        
        $fkColumns = array_filter(
            $schema->columns,
            fn(ColumnMetadata $col) => $col->isForeignKey
                && $col->sqlType !== 'special_relation'
                && (!$col->isPrimaryKey || $schema->isPivotTable)  // Incluir PK-FK solo para pivotes
                && $col->scopeColumn === null  // exclude scoped relations
        );

        foreach ($fkColumns as $col) {
            $referencedTable = $col->referencedTable;
            $relationsConfig = $globalConfig['relations'][$col->name] ?? [];
            $relModelClass   = $this->resolveModelClass($referencedTable);
            $refModelName    = class_basename($relModelClass);

            // ── 1. Display field ──────────────────────────────────────
            $savedDisplay  = $relationsConfig['display_field'] ?? null;
            $isGeneric     = in_array($savedDisplay, [null, 'id', '']);
            $globalDisplay = $displayFields[$referencedTable] ?? null;
            $displayField  = (!$isGeneric) ? $savedDisplay : ($globalDisplay ?? null);

            if (!$displayField) {
                $command->newLine();
                $command->line("  <fg=yellow>Tabla Principal: {$schema->modelName} → FK: <fg=cyan>{$col->name}</> → entidad '<fg=cyan>{$refModelName}</>'</>");
                $command->line("  ¿Cómo desea identificar visualmente un registro de '{$refModelName}' en la vista?");
                $command->line("  Puede ser un campo directo o navegar por relaciones (ej: unidad.propiedad.direccion)");

                // Usar árbol de navegación — retorna un array, tomamos el primero
                $displayPaths = $this->askSinglePath($referencedTable, $relModelClass, $command, $refModelName);
                $displayField = $displayPaths; // es un string con el path

                $this->configWriter->saveDisplayField($referencedTable, $displayField);
                $hasChanges = true;
                $command->line("  <fg=green>✓ display_fields['{$referencedTable}'] = '{$displayField}'</>");
            }

            // ── Rutas de búsqueda para la FK (BuscadorController) ────
            $fkSearchPaths = config("generator.search_paths.{$referencedTable}", null);
            if (!$fkSearchPaths) {
                $fkPaths = $this->askSearchPaths($referencedTable, $relModelClass, $command, $refModelName);
                $this->configWriter->saveSearchPaths($referencedTable, $fkPaths);
                $hasChanges = true;
                $command->line("  <fg=green>✓ search_paths['{$referencedTable}'] guardado</>");
            }

            // ── 2. Tipo de relación (auto por conteo) ──────────────────
            $relationType = $relationsConfig['type'] ?? null;

            if (!$relationType) {
                $count        = $this->countRecords($referencedTable);
                $threshold    = config('generator.select_threshold', 15);
                $relationType = $count <= $threshold ? 'select' : 'buscador';

                $command->line("  Registros en '{$referencedTable}': {$count} → tipo: <fg=yellow>{$relationType}</>");

                $this->configWriter->saveRelationType(
                    $schema->table, $col->name,
                    $relationType, $displayField, $referencedTable
                );
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $command->newLine();
            $command->line('<fg=green>Config guardada en config/generator.php</>');
            $this->reloadConfig();
            // Refrescar el ConfigLoader para que SchemaBuilder vea los valores nuevos
            $this->configLoader->refresh();
        }
    }

    /**
     * Navega el árbol de relaciones de forma interactiva para seleccionar
     * uno o más search paths para una entidad.
     *
     * Ejemplo de sesión para 'unidad':
     *   Nivel raíz: [Finalizar] [← Volver] nombre | propiedad→ | contratoVigente→
     *   Al elegir propiedad→: [Finalizar] [← Volver] direccion | cliente→
     *   Al elegir cliente→:   [Finalizar] [← Volver] nombre | rut | email
     *   Al elegir nombre: guarda 'propiedad.cliente.nombre'
     */
    private function askSearchPaths(string $table, string $modelClass, Command $command, string $entityName = ''): array
    {
        $entity   = $entityName ?: $table;
        $selected = [];
        $done     = false;
        $disabled = false;

        $command->newLine();
        $command->line("  <fg=yellow>¿Por cuál(es) atributo(s) desea buscar la entidad '{$entity}' en el buscador?</>");

        // Preguntar primero si quiere buscador o no
        $wantsBuscador = $command->choice(
            "  ¿Desea habilitar el buscador para '{$entity}'?",
            ['Sí, configurar buscador', 'No, sin buscador'],
            0
        );

        if ($wantsBuscador === 'No, sin buscador') {
            $command->line("  <fg=yellow>Sin buscador para '{$entity}'.</>");
            return ['__none__'];
        }

        $command->line("  (Navega por las relaciones. '→' indica una relación con subniveles.)");
        $command->line("  (En el nivel raíz puedes elegir '[✗ Deshabilitar buscador]' para cancelar.)");

        $this->navigateTree($modelClass, $table, [], $selected, $command, $entity, $done, $disabled);

        if ($disabled) {
            $command->line("  <fg=yellow>Buscador deshabilitado.</>");
            return ['__none__'];
        }

        if (empty($selected)) {
            $cols     = $this->getTableColumns($table);
            $selected = [$cols[0] ?? 'id'];
        }

        return $selected;
    }

    /**
     * Navegación recursiva del árbol de relaciones.
     * $currentPath = path acumulado, ej: ['propiedad', 'cliente']
     * $selected    = paths elegidos (por referencia)
     * $done        = flag que cuando es true propaga el Finalizar hacia arriba (por referencia)
     */
    private function navigateTree(
        string  $modelClass,
        string  $table,
        array   $currentPath,
        array   &$selected,
        Command $command,
        string  $entityName,
        bool    &$done     = false,
        bool    &$disabled = false
    ): void {
        $depth      = count($currentPath);
        $indent     = str_repeat('  ', $depth + 1);
        $pathPrefix = empty($currentPath) ? '' : implode('.', $currentPath) . '.';
        $breadcrumb = empty($currentPath) ? $entityName : $entityName . ' → ' . implode(' → ', $currentPath);

        $directCols = $this->getTableColumns($table);
        $relations  = $this->getDirectRelations($modelClass);

        do {
            $options = [];

            // [✓ Finalizar] solo si ya se eligió al menos un atributo
            if (!empty($selected)) {
                $options[] = '[✓ Finalizar selección]';
            }

            // [← Volver] solo en subniveles
            if ($depth > 0) {
                $options[] = '[← Volver]';
            }

            // [✗ Deshabilitar buscador] solo en el nivel raíz
            if ($depth === 0) {
                $options[] = '[✗ Deshabilitar buscador]';
            }

            foreach ($directCols as $col) {
                $options[] = $col;
            }
            foreach ($relations as $rel) {
                $options[] = $rel['name'] . ' →';
            }

            $selectedStr = empty($selected) ? '' : implode(', ', $selected);
            $prompt      = empty($selected)
                ? "{$indent}¿Qué atributo de '{$breadcrumb}'?"
                : "{$indent}Agregar más atributos para '{$breadcrumb}' (elegidos: {$selectedStr})";

            $choice = $command->choice($prompt, $options, 0);

            if ($choice === '[✓ Finalizar selección]') {
                $done = true;
                return;
            }

            if ($choice === '[✗ Deshabilitar buscador]') {
                $disabled = true;
                $done     = true;
                $selected = [];
                return;
            }

            if ($choice === '[← Volver]') {
                return;
            }

            if (str_ends_with($choice, ' →')) {
                $relName = rtrim($choice, ' →');
                $relInfo = collect($relations)->firstWhere('name', $relName);

                if ($relInfo) {
                    $this->navigateTree(
                        $relInfo['class'],
                        $relInfo['table'],
                        array_merge($currentPath, [$relName]),
                        $selected,
                        $command,
                        $entityName,
                        $done,
                        $disabled
                    );
                }

                if ($done) {
                    return;
                }
                continue;
            }

            // Campo directo elegido
            $fullPath = $pathPrefix . $choice;

            if (!in_array($fullPath, $selected)) {
                $selected[] = $fullPath;
                $command->line("{$indent}<fg=green>+ {$fullPath}</>");
            } else {
                $command->line("{$indent}<fg=yellow>'{$fullPath}' ya estaba elegido.</>");
            }

        } while (true);
    }

    /**
     * Devuelve las relaciones directas de un modelo (solo primer nivel).
     * Cada entrada: ['name' => 'propiedad', 'class' => 'App\Models\Propiedad', 'table' => 'propiedad']
     */
    private function getDirectRelations(string $modelClass): array
    {
        if (!class_exists($modelClass)) {
            return [];
        }

        $relations  = [];
        $reflection = new \ReflectionClass($modelClass);

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $modelClass) continue;
            if ($method->getNumberOfRequiredParameters() > 0) continue;

            try {
                $instance = $reflection->newInstanceWithoutConstructor();
                $result   = $method->invoke($instance);

                if (!($result instanceof \Illuminate\Database\Eloquent\Relations\Relation)) continue;

                $relatedClass = get_class($result->getRelated());
                $relatedTable = (new $relatedClass)->getTable();

                $relations[] = [
                    'name'  => $method->getName(),
                    'class' => $relatedClass,
                    'table' => $relatedTable,
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        return $relations;
    }

    private function getTableColumns(string $table): array
    {
        $database = config('database.connections.mysql.database');

        $rows = DB::select("
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME   = ?
              AND COLUMN_KEY  != 'PRI'
            ORDER BY ORDINAL_POSITION
            LIMIT 10
        ", [$database, $table]);

        return array_map(fn($r) => $r->COLUMN_NAME, $rows);
    }

    private function countRecords(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function askSinglePath(string $table, string $modelClass, Command $command, string $entityName): string
    {
        $command->newLine();

        while (true) {
            $result = $this->navigateTreeSingle($modelClass, $table, [], $command, $entityName);
            if ($result !== null) {
                return $result;
            }
            // null = Volver en nivel raíz (no debería ocurrir, pero por si acaso)
        }
    }

    /**
     * Versión single: retorna el path elegido como string, o null si se canceló.
     */
    private function navigateTreeSingle(
        string  $modelClass,
        string  $table,
        array   $currentPath,
        Command $command,
        string  $entityName
    ): ?string {
        $depth      = count($currentPath);
        $indent     = str_repeat('  ', $depth + 1);
        $pathPrefix = empty($currentPath) ? '' : implode('.', $currentPath) . '.';
        $breadcrumb = empty($currentPath) ? $entityName : $entityName . ' → ' . implode(' → ', $currentPath);

        $directCols = $this->getTableColumns($table);
        $relations  = $this->getDirectRelations($modelClass);

        while (true) {
            $options = [];

            if ($depth > 0) {
                $options[] = '[← Volver]';
            }

            // id siempre disponible como primera opción
            if ($depth === 0) {
                $options[] = 'id';
            }

            foreach ($directCols as $col) {
                if ($col !== 'id') { // evitar duplicar id
                    $options[] = $col;
                }
            }
            foreach ($relations as $rel) {
                $options[] = $rel['name'] . ' →';
            }

            $choice = $command->choice(
                "{$indent}¿Cómo mostrar '{$breadcrumb}' en las vistas?",
                $options,
                0
            );

            if ($choice === '[← Volver]') {
                return null; // señal de cancelar, el padre reintentará
            }

            if (str_ends_with($choice, ' →')) {
                $relName = rtrim($choice, ' →');
                $relInfo = collect($relations)->firstWhere('name', $relName);

                if ($relInfo) {
                    $result = $this->navigateTreeSingle(
                        $relInfo['class'],
                        $relInfo['table'],
                        array_merge($currentPath, [$relName]),
                        $command,
                        $entityName
                    );

                    if ($result !== null) {
                        return $result; // propagó una selección válida
                    }
                    // null = Volver desde subnivel, repetir en este nivel
                }
                continue;
            }

            // Campo directo elegido
            return $pathPrefix . $choice;
        }
    }

    private function resolveModelClass(string $table): string
    {
        $tableConfig = config("generator.{$table}", []);
        return $tableConfig['model']
            ?? config('generator.model_namespace', 'App\\Models\\') . \Illuminate\Support\Str::studly($table);
    }

    private function reloadConfig(): void
    {
        $path = config_path('generator.php');

        // Invalidar cache de opcache si está disponible
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }

        // PHP cachea require: usar include con una función anónima para forzar nueva lectura
        $fresh = (function() use ($path) {
            return include $path;
        })();

        config(['generator' => $fresh]);
    }
}
