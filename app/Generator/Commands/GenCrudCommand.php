<?php

namespace App\Generator\Commands;

use App\Generator\Commands\FkInterviewer;
use App\Generator\Config\ConfigLoader;
use App\Generator\Config\ConfigWriter;
use App\Generator\Introspection\ConstraintParser;
use App\Generator\Introspection\RelationResolver;
use App\Generator\Introspection\SchemaInspector;
use App\Generator\Rendering\ComponentSplitter;
use App\Generator\Rendering\StubRenderer;
use App\Generator\Schema\SchemaBuilder;
use App\Generator\Writers\FileWriter;
use App\Generator\Writers\RouteAppender;
use Illuminate\Console\Command;

class GenCrudCommand extends Command
{
    protected $signature = 'gen:crud
        {tabla : Nombre de la tabla MySQL}
        {--force : Sobreescribir archivos con cambios manuales sin preguntar}
        {--only= : Generar solo partes específicas: controller,routes,views,components (separadas por coma)}
        {--dry-run : Mostrar los archivos que se generarían sin escribir nada}';

    protected $description = 'Genera controller, rutas y vistas Blade para una tabla MySQL existente';

    public function __construct(
        private SchemaInspector  $inspector,
        private RelationResolver $relationResolver,
        private ConstraintParser $constraintParser,
        private ConfigLoader     $configLoader,
        private ConfigWriter     $configWriter,
        private StubRenderer     $renderer,
        private ComponentSplitter $splitter,
        private FileWriter       $writer,
        private RouteAppender    $routeAppender,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $table   = $this->argument('tabla');
        $force   = $this->option('force');
        $dryRun  = $this->option('dry-run');
        $only    = $this->parseOnly($this->option('only'));

        $this->info("Generando CRUD para tabla: {$table}");
        $this->newLine();

        // ── 1. Construir el schema completo ──────────────────────────
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

        $this->line("  Modelo:  {$schema->modelName}");
        $this->line("  PK:      " . implode(', ', $schema->primaryKeys));
        $this->line("  Campos:  " . count($schema->columns));
        $this->line("  Constraints: " . count($schema->checkConstraints));

        // ── 2. Entrevistar FKs (preguntas al inicio, antes de generar) ──
        if (!$dryRun) {
            $interviewer = new FkInterviewer($this->configLoader, $this->configWriter);
            $interviewer->interview($schema, $this);

            // Reconstruir schema con la config actualizada
            $schema = $builder->build($table);
        }

        $this->newLine();

        // ── 2. Controller ────────────────────────────────────────────
        if ($this->shouldGenerate('controller', $only)) {
            $this->line('<fg=cyan>Controller:</>');

            $content = $this->renderer->renderController($schema);
            $path    = "app/Http/Controllers/Crud/{$schema->modelName}Controller.php";

            $this->writeOrDry($path, $content, $force, $dryRun);
            $this->newLine();
        }

        // ── 3. Routes ────────────────────────────────────────────────
        if ($this->shouldGenerate('routes', $only)) {
            $this->line('<fg=cyan>Routes:</>');

            $content = $this->renderer->renderRoutes($schema);

            if ($dryRun) {
                $this->dryRunPreview('routes/generated.php', $content);
            } else {
                $this->routeAppender->append($schema->modelName, $content, $this);
            }

            $this->newLine();
        }

        // ── 4. Vistas ────────────────────────────────────────────────
        if ($this->shouldGenerate('views', $only)) {
            $this->line('<fg=cyan>Vistas:</>');

            $views = [
                "resources/views/{$schema->modelSnake}/show.blade.php"          => $this->renderer->renderShowView($schema),
                "resources/views/{$schema->modelSnake}/index.blade.php"         => $this->renderer->renderIndexView($schema),
                "resources/views/{$schema->modelSnake}/create.blade.php"        => $this->renderer->renderCreateView($schema),
                "resources/views/{$schema->modelSnake}/edit.blade.php"          => $this->renderer->renderEditView($schema),
                // Fix 5: modales en subcarpeta modal/
                "resources/views/{$schema->modelSnake}/modal/show.blade.php"    => $this->renderer->renderModalShow($schema),
                "resources/views/{$schema->modelSnake}/modal/create.blade.php"  => $this->renderer->renderModalCreate($schema),
            ];

            foreach ($views as $path => $content) {
                $this->writeOrDry($path, $content, $force, $dryRun);
            }

            $this->newLine();
        }

        // ── 5. Components Blade — eliminados (contenido va en show) ──
        // Fix 7: ya no se genera la carpeta components

        // ── 6. BuscadorController ────────────────────────────────────
        if ($this->shouldGenerate('controller', $only)) {
            $searchableModels = $this->collectSearchableModels();

            if (!empty($searchableModels)) {
                $this->line('<fg=cyan>BuscadorController:</>');
                $content = $this->renderer->renderBuscadorController($searchableModels);
                $this->writeOrDry('app/Http/Controllers/BuscadorController.php', $content, $force, $dryRun);

                // Agregar ruta del buscador una sola vez
                if (!$dryRun) {
                    $this->routeAppender->append(
                        'Buscador',
                        "use App\\Http\\Controllers\\BuscadorController;\n\nRoute::get('/buscador', [BuscadorController::class, 'index'])->name('buscador.index');",
                        $this
                    );
                }

                $this->newLine();
            }
        }

        // ── Resumen ──────────────────────────────────────────────────
        if ($dryRun) {
            $this->warn('Modo dry-run: ningún archivo fue escrito.');
        } else {
            $this->info('Generación completada.');

            // Limpiar config/generator.php después de generar
            $this->cleanConfigFile();

            if (!$this->configLoader->has($table)) {
                $this->newLine();
                $this->warn("No existe config para '{$table}' en config/generator.php.");
                $this->warn('Se usaron defaults automáticos. Revisa el resultado y ajusta la config si es necesario.');
            }
        }

        return self::SUCCESS;
    }

    private function cleanConfigFile(): void
    {
        $path    = config_path('generator.php');
        $current = config('generator', []);

        $keep = ['search_paths', 'display_fields'];
        $lines = ["<?php", "", "return [", "", "    'select_threshold' => 15,"];

        foreach ($keep as $key) {
            if (empty($current[$key])) continue;
            $lines[] = "";
            $lines[] = "    '{$key}' => " . $this->arrayToPhp($current[$key], 2) . ",";
        }

        $lines[] = "];";
        $lines[] = "";

        file_put_contents($path, implode("\n", $lines));

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
        $this->configLoader->refresh();
    }

    private function arrayToPhp(array $arr, int $indent = 2): string
    {
        $ind = str_repeat('    ', $indent);
        $inner = str_repeat('    ', $indent + 1);

        $parts = [];
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $parts[] = $inner . var_export($k, true) . ' => ' . $this->arrayToPhp($v, $indent + 1);
            } else {
                $parts[] = $inner . var_export($k, true) . ' => ' . var_export($v, true);
            }
        }
        return "[\n" . implode(",\n", $parts) . "\n{$ind}]";
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Lee config/generator.php y recopila todos los modelos que tienen
     * al menos una relación de tipo 'buscador' — esos son los que van al BuscadorController.
     */
    private function collectSearchableModels(): array
    {
        $allConfig     = config('generator', []);
        $displayFields = $allConfig['display_fields'] ?? [];
        $searchPaths   = $allConfig['search_paths']   ?? [];
        $searchable    = [];
        $seen          = [];

        foreach ($searchPaths as $table => $paths) {
            if (isset($seen[$table])) continue;
            $seen[$table] = true;

            // __none__ = sin buscador para esta tabla
            $normalPaths = is_array($paths) ? $paths : [$paths];
            if ($normalPaths === ['__none__']) continue;

            $modelClass = $allConfig[$table]['model']
                ?? config('generator.model_namespace', 'App\\Models\\') . \Illuminate\Support\Str::studly($table);
            $routeBase  = $allConfig[$table]['route_base'] ?? $table;

            $searchable[] = [
                'class'         => $modelClass,
                'display_field' => $displayFields[$table] ?? 'id',
                'route_base'    => $routeBase,
                'model_snake'   => $table,
                'search_paths'  => $normalPaths,
            ];
        }

        return $searchable;
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

    private function shouldGenerate(string $part, array $only): bool
    {
        if (empty($only)) {
            return true;
        }

        return in_array($part, $only);
    }

    private function parseOnly(?string $only): array
    {
        if (!$only) {
            return [];
        }

        return array_map('trim', explode(',', $only));
    }
}
