<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateApi extends Command
{
    protected $signature = 'generate:api';
    protected $description = 'Generar controllers CRUD + rutas automáticamente';

    public function handle()
    {
        $modelsPath = app_path('Models');
        $files = File::files($modelsPath);

        $stubPath = base_path('stubs/controller.api.stub');

        if (!File::exists($stubPath)) {
            $this->error('❌ No existe el stub en stubs/controller.api.stub');
            return;
        }

        $stub = File::get($stubPath);

        $routes = "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n";

        foreach ($files as $file) {
            $model = pathinfo($file, PATHINFO_FILENAME);

            if ($model === 'Model') {
                continue;
            }

            $controllerName = $model . 'Controller';
            $controllerPath = app_path("Http/Controllers/{$controllerName}.php");

            // Generar controller
            $content = str_replace('{{model}}', $model, $stub);
            File::put($controllerPath, $content);

            $this->info("✔ Controller: $controllerName");

            // Generar ruta
            $route = Str::kebab(Str::pluralStudly($model));

            $routes .= "Route::apiResource('$route', \\App\\Http\\Controllers\\{$controllerName}::class);\n";
        }

        // Guardar rutas
        File::put(base_path('routes/api.php'), $routes);

        $this->info('🚀 API generada completa');
    }
}
