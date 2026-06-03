<?php

namespace App\Generator;

use App\Generator\Commands\GenCrudCommand;
use App\Generator\Commands\GenSearchCommand;
use App\Generator\Config\ConfigLoader;
use App\Generator\Config\ConfigWriter;
use App\Generator\Introspection\ConstraintParser;
use App\Generator\Introspection\RelationResolver;
use App\Generator\Introspection\SchemaInspector;
use App\Generator\Rendering\ComponentSplitter;
use App\Generator\Rendering\StubRenderer;
use App\Generator\Writers\FileWriter;
use App\Generator\Writers\RouteAppender;
use Illuminate\Support\ServiceProvider;

class GeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singletons: una instancia por request (o por ejecución CLI)
        $this->app->singleton(SchemaInspector::class);
        $this->app->singleton(RelationResolver::class);
        $this->app->singleton(ConstraintParser::class);
        $this->app->singleton(ConfigLoader::class);
        $this->app->singleton(ConfigWriter::class);
        $this->app->singleton(StubRenderer::class);
        $this->app->singleton(ComponentSplitter::class);
        $this->app->singleton(FileWriter::class);
        $this->app->singleton(RouteAppender::class);
    }

    public function boot(): void
    {
        // Publicar config
        $this->publishes([
            __DIR__ . '/../../config/generator.php' => config_path('generator.php'),
        ], 'generator-config');

        // Registrar el comando Artisan solo en CLI
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenCrudCommand::class,
                GenSearchCommand::class,
            ]);
        }
    }
}
