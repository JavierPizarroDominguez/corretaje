<?php

namespace App\Generator\Commands;

use App\Generator\Config\ConfigLoader;
use App\Generator\Introspection\ConstraintParser;
use App\Generator\Introspection\RelationResolver;
use App\Generator\Introspection\SchemaInspector;
use App\Generator\Rendering\StubRenderer;
use App\Generator\Schema\SchemaBuilder;
use App\Generator\Schema\TableSchema;
use App\Generator\Writers\FileWriter;
use App\Generator\Writers\RouteAppender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenSearchCommand extends Command
{
    protected $signature = 'gen:search
        {tabla : Nombre de la tabla MySQL}
        {--force : Sobreescribir archivos con cambios manuales sin preguntar}
        {--dry-run : Mostrar los archivos que se generarían sin escribir nada}';

    protected $description = 'Genera buscador con filtros (panel colapsable + AJAX) para una tabla';

    public function __construct(
        private SchemaInspector  $inspector,
        private RelationResolver $relationResolver,
        private ConstraintParser $constraintParser,
        private ConfigLoader     $configLoader,
        private StubRenderer     $renderer,
        private FileWriter       $writer,
        private RouteAppender    $routeAppender,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $table  = $this->argument('tabla');
        $force  = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info("Generando filtros para tabla: {$table}");
        $this->newLine();

        // ── 1. Construir schema ────────────────────────────────────
        $this->line('Inspeccionando tabla y modelo...');

        try {
            $builder = new SchemaBuilder(
                $this->inspector,
                $this->relationResolver,
                $this->constraintParser,
                $this->configLoader,
            );
            $schema = $builder->build($table);
        } catch (\Throwable $e) {
            $this->error("Error al inspeccionar la tabla: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->line("  Modelo: {$schema->modelName}");
        $this->line("  Campos: " . count($schema->columns));

        // ── 2. Scoped relations: preguntar FK de filtro ──────────
        $scopedFilterData = $this->interviewScopedRelations($schema, $table);

        // ── 3. Ruta del FilterController ─────────────────────────
        $filterRoute = $this->buildFilterRoute($schema);

        if ($dryRun) {
            $this->dryRunPreview('routes/generated.php', $filterRoute);
        } else {
            $this->routeAppender->append(
                $schema->modelName . 'Filter',
                $filterRoute,
                $this
            );
        }

        // ── 4. FilterController ──────────────────────────────────
        $this->line('<fg=cyan>FilterController:</>');
        $content = $this->renderer->renderFilterController($schema, $scopedFilterData);
        $path    = "app/Http/Controllers/Crud/{$schema->modelName}FilterController.php";
        $this->writeOrDry($path, $content, $force, $dryRun);

        // ── 5. Filter view ───────────────────────────────────────
        $this->line('<fg=cyan>Filter view:</>');
        $content = $this->renderer->renderFilterView($schema, $scopedFilterData);
        $path    = "resources/views/{$schema->modelSnake}/filter.blade.php";
        $this->writeOrDry($path, $content, $force, $dryRun);

        // ── 6. Table partial (AJAX) ──────────────────────────────
        $this->line('<fg=cyan>Table partial:</>');
        $content = $this->renderer->renderTablePartial($schema);
        $path    = "resources/views/{$schema->modelSnake}/table.blade.php";
        $this->writeOrDry($path, $content, $force, $dryRun);

        // ── 7. Index view: agregar @include del filter ──────────
        $this->line('<fg=cyan>Index view:</>');
        $indexPath = base_path("resources/views/{$schema->modelSnake}/index.blade.php");

        if (!$dryRun && file_exists($indexPath)) {
            $this->ensureFilterIncludeInIndex($indexPath, $schema);
        }

        if ($dryRun) {
            $this->line("  [dry-run] resources/views/{$schema->modelSnake}/index.blade.php (modificado)");
        }

        // ── 8. JS init en index view ────────────────────────────
        if (!$dryRun && file_exists($indexPath)) {
            $this->ensureFilterJsInIndex($indexPath, $schema);
        }

        // ── 9. Asegurar filtros.js exista en public/js/ ─────────
        if (!$dryRun) {
            $this->ensureFiltrosJsExists();
        }

        $this->newLine();

        if ($dryRun) {
            $this->warn('Modo dry-run: ningún archivo fue escrito.');
        } else {
            $this->info('Generación de filtros completada.');
        }

        return self::SUCCESS;
    }

    /**
     * Para cada scoped relation (deudor, acreedor, etc.), pregunta al usuario
     * qué FK del modelo pivote usar para filtrar.
     *
     * Retorna array con:
     * [
     *   [
     *     'relation_name' => 'deudor',
     *     'pivot_model'   => 'App\Models\ParticipanteCobro',
     *     'filter_fk'     => 'Cliente_id',
     *     'related_model' => 'App\Models\Cliente',
     *     'display_field' => 'nombre',
     *     'label'         => 'Deudor',
     *   ],
     *   ...
     * ]
     */
    private function interviewScopedRelations(TableSchema $schema, string $table): array
    {
        $result = [];
        $scoped = $schema->scopedRelations();

        if (empty($scoped)) {
            return $result;
        }

        $this->newLine();
        $this->line('<fg=yellow>Configurando filtros para relaciones especiales...</>');
        $displayFields = config('generator.display_fields', []);

        foreach ($scoped as $col) {
            $pivotModel   = $col->pivotModel;
            $pivotFk      = $col->pivotFk; // FK hacia el modelo padre (ej: Cobro_id)
            $relationName = $col->relationName;
            $label        = $col->label;

            if (!$pivotModel || !class_exists($pivotModel)) continue;

            $this->newLine();
            $this->line("  Relación '<fg=cyan>{$relationName}</>' (<fg=yellow>{$label}</>)");
            $this->line("  Modelo pivote: {$pivotModel}");

            // Obtener FKs del modelo pivote (excluyendo la que apunta al padre)
            $pivotFks = $this->getPivotForeignKeys($pivotModel, $pivotFk);

            if (empty($pivotFks)) {
                $this->line("  <fg=yellow>No se encontraron FKs adicionales en el pivote. Se omite filtro.</>");
                continue;
            }

            if (count($pivotFks) === 1) {
                // Solo una opción: usarla directamente
                $fkInfo = $pivotFks[0];
            } else {
                // Múltiples opciones: preguntar
                $options = [];
                foreach ($pivotFks as $fk) {
                    $options[] = "{$fk['column']} → {$fk['related_model']} ({$fk['related_table']})";
                }
                $options[] = 'Ninguno (omitir filtro)';

                $choice = $this->choice(
                    "  ¿Qué FK de '{$pivotModel}' usar para filtrar por '{$relationName}'?",
                    $options,
                    0
                );

                if ($choice === 'Ninguno (omitir filtro)') {
                    continue;
                }

                $idx = array_search($choice, $options);
                $fkInfo = $pivotFks[$idx];
            }

            $relatedModel    = $fkInfo['related_model'];
            $filterFk        = $fkInfo['column'];
            $relatedTable    = $fkInfo['related_table'];
            $shortModel      = class_basename($relatedModel);

            // Obtener display_field desde config
            $displayField = $displayFields[$relatedTable] ?? $this->guessDisplayField($relatedTable);

            $this->line("  <fg=green>✓ Filtro '{$relationName}' → {$shortModel}.{$displayField} (via {$pivotModel}.{$filterFk})</>");

            $result[] = [
                'relation_name' => $relationName,
                'pivot_model'   => $pivotModel,
                'filter_fk'     => $filterFk,
                'related_model' => $relatedModel,
                'display_field' => $displayField,
                'label'         => $label,
            ];
        }

        return $result;
    }

    /**
     * Obtiene las FK del modelo pivote que NO son la que apunta al modelo padre.
     */
    private function getPivotForeignKeys(string $pivotModel, ?string $excludeFk): array
    {
        if (!class_exists($pivotModel)) return [];

        try {
            $instance = new $pivotModel();
            $table    = $instance->getTable();

            $database = config('database.connections.mysql.database');
            $rows = DB::select("
                SELECT
                    kcu.COLUMN_NAME,
                    kcu.REFERENCED_TABLE_NAME,
                    kcu.REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE kcu
                WHERE kcu.TABLE_SCHEMA           = ?
                  AND kcu.TABLE_NAME             = ?
                  AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
            ", [$database, $table]);

            $fks = [];
            foreach ($rows as $row) {
                if ($excludeFk && $row->COLUMN_NAME === $excludeFk) continue;

                $relatedModel = $this->tableToModelClass($row->REFERENCED_TABLE_NAME);
                $fks[] = [
                    'column'        => $row->COLUMN_NAME,
                    'related_table' => $row->REFERENCED_TABLE_NAME,
                    'related_model' => $relatedModel,
                ];
            }

            return $fks;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function tableToModelClass(string $table): string
    {
        $config = config("generator.{$table}", []);
        return $config['model']
            ?? config('generator.model_namespace', 'App\\Models\\') . Str::studly($table);
    }

    private function guessDisplayField(string $table): string
    {
        $commonFields = ['nombre', 'razon_social', 'nombre_completo', 'descripcion', 'titulo', 'name'];
        try {
            $database = config('database.connections.mysql.database');
            $columns = DB::select("
                SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION
                LIMIT 10
            ", [$database, $table]);

            $colNames = array_map(fn($c) => strtolower($c->COLUMN_NAME), $columns);
            foreach ($commonFields as $field) {
                if (in_array($field, $colNames)) {
                    return $field;
                }
            }
            return 'id';
        } catch (\Throwable) {
            return 'id';
        }
    }

    private function buildFilterRoute(TableSchema $schema): string
    {
        return implode("\n", [
            "use App\\Http\\Controllers\\Crud\\{$schema->modelName}FilterController;",
            "",
            "Route::get('/{$schema->routeBase}-filtrar', [{$schema->modelName}FilterController::class, 'index'])->name('{$schema->modelSnake}.filtrar');",
        ]);
    }

    /**
     * Agrega el @include del filter panel al index view si no existe.
     */
    private function ensureFilterIncludeInIndex(string $indexPath, TableSchema $schema): void
    {
        $content = file_get_contents($indexPath);
        $includeLine = "@include('{$schema->modelSnake}.filter')";

        if (str_contains($content, $includeLine)) {
            $this->line("  <fg=green>✓ @include ya existe en index</>");
            return;
        }

        // Buscar el botón "Agregar" o el título para insertar después
        $toggleBtn = '<button type="button" class="btn btn-outline-secondary btn-sm" id="btn-toggle-filter-'
            . $schema->modelSnake . '" data-bs-toggle="collapse" data-bs-target="#filter-panel-'
            . $schema->modelSnake . '" aria-expanded="false">'
            . '<i class="bi bi-funnel"></i> Filtrar'
            . '</button>';

        $searchPattern = '/<a href="\/' . preg_quote($schema->routeBase, '/') . '\/create" class="btn btn-primary">Agregar<\/a>/';

        if (preg_match($searchPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = $matches[0][1] + strlen($matches[0][0]);
            $content = substr($content, 0, $insertPos) . "\n        " . $toggleBtn . "\n\n        " . $includeLine . substr($content, $insertPos);
            file_put_contents($indexPath, $content);
            $this->line("  <fg=green>✓ @include y botón toggle agregados al index</>");
        } else {
            $this->line("  <fg=yellow>⚠ No se pudo ubicar el punto de inserción en el index. Agrega manualmente:</>");
            $this->line("     {$includeLine}");
        }
    }

    /**
     * Agrega el JS de inicialización del filtro al index view si no existe.
     */
    private function ensureFilterJsInIndex(string $indexPath, TableSchema $schema): void
    {
        $content = file_get_contents($indexPath);
        $initCall = "initFilters({";

        if (str_contains($content, $initCall)) {
            return;
        }

        // Agregar init JS dentro del @push('scripts') existente
        $jsInit = <<<JS

    initFilters({
        baseUrl: '/{$schema->routeBase}',
        tableSelector: '#table-{$schema->modelSnake}',
        filterPanel: '#filter-panel-{$schema->modelSnake}'
    });
JS;

        // Insertar antes del cierre del @push
        $pushEndPos = strrpos($content, '@endpush');
        if ($pushEndPos !== false) {
            $content = substr($content, 0, $pushEndPos) . $jsInit . "\n" . substr($content, $pushEndPos);
            file_put_contents($indexPath, $content);
            $this->line("  <fg=green>✓ initFilters() agregado al script del index</>");
        }
    }

    /**
     * Asegura que filtros.js exista en public/js/.
     */
    private function ensureFiltrosJsExists(): void
    {
        $targetPath = public_path('js/filtros.js');
        $sourcePath = base_path('public/js/filtros.js');

        if (file_exists($targetPath)) {
            return;
        }

        if (file_exists($sourcePath)) {
            copy($sourcePath, $targetPath);
            $this->line("  <fg=green>✓ filtros.js copiado a public/js/</>");
        }
    }

    private function writeOrDry(string $path, string $content, bool $force, bool $dryRun): void
    {
        if ($dryRun) {
            $this->dryRunPreview($path, $content);
            return;
        }

        $this->writer->write($path, $content, $force, $this);
    }

    private function dryRunPreview(string $path, string $content): void
    {
        $lines = count(explode("\n", $content));
        $this->line("  [dry-run] {$path} ({$lines} líneas)");
    }
}