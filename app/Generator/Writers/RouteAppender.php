<?php

namespace App\Generator\Writers;

use Illuminate\Console\Command;

class RouteAppender
{
    private string $routesFile;

    public function __construct()
    {
        // Archivo separado incluido desde web.php → evita ensuciar web.php
        // En web.php agregar: require __DIR__.'/generated.php';
        $this->routesFile = base_path('routes/generated.php');
    }

    /**
     * Agrega las rutas generadas a routes/generated.php.
     * Si ya existen rutas para este modelo, las reemplaza.
     * Si el archivo no existe, lo crea con el header correcto.
     */
    public function append(string $modelName, string $routesContent, Command $command = null): void
    {
        $this->ensureFileExists();

        $current = file_get_contents($this->routesFile);

        // Marcador de bloque por modelo
        $startMarker = "// [GEN:START:routes_{$modelName}]";
        $endMarker   = "// [GEN:END:routes_{$modelName}]";

        $block = implode("\n", [
            $startMarker,
            $routesContent,
            $endMarker,
        ]);

        // Si ya existe un bloque para este modelo, reemplazarlo
        $pattern = '/\/\/ \[GEN:START:routes_' . preg_quote($modelName, '/') . '\].*?\/\/ \[GEN:END:routes_' . preg_quote($modelName, '/') . '\]/s';

        if (preg_match($pattern, $current)) {
            $updated = preg_replace($pattern, $block, $current);
            file_put_contents($this->routesFile, $updated);
            $command?->info("  ~ Rutas actualizadas: {$modelName}");
        } else {
            // Agregar al final del archivo
            file_put_contents($this->routesFile, $current . "\n\n" . $block . "\n");
            $command?->info("  + Rutas agregadas: {$modelName}");
        }
    }

    /**
     * Crea routes/generated.php si no existe.
     * También agrega el require a web.php si no está.
     */
    private function ensureFileExists(): void
    {
        if (!file_exists($this->routesFile)) {
            file_put_contents($this->routesFile, implode("\n", [
                '<?php',
                '',
                '/*',
                ' * Rutas generadas automáticamente por el generador.',
                ' * No editar manualmente bloques marcados con [GEN:START/END].',
                ' * Para agregar rutas custom, hacerlo fuera de los bloques.',
                ' */',
                '',
            ]));
        }

        // Verificar que web.php incluya generated.php
        $webPhp = base_path('routes/web.php');

        if (file_exists($webPhp)) {
            $webContent = file_get_contents($webPhp);

            if (!str_contains($webContent, "require __DIR__.'/generated.php'")) {
                file_put_contents(
                    $webPhp,
                    $webContent . "\n\nrequire __DIR__.'/generated.php';\n"
                );
            }
        }
    }
}
