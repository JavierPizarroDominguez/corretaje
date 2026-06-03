<?php

namespace App\Generator\Writers;

use Illuminate\Console\Command;

class FileWriter
{
    private string $checksumFile;
    private array  $checksums;

    public function __construct()
    {
        $this->checksumFile = storage_path('generator-checksums.json');
        $this->checksums    = $this->loadChecksums();
    }

    // Escribe un archivo generado con protección anti-sobreescritura
    // $force = true → sobreescribe sin preguntar
    // $force = false → pregunta si detecta cambios manuales
    public function write(
        string  $path,
        string  $content,
        bool    $force = false,
        Command $command = null
    ): bool {
        $absolutePath = base_path($path);

        // Archivo nuevo: escribir directamente
        if (!file_exists($absolutePath)) {
            $this->writeFile($absolutePath, $content);
            $command?->info("  + Creado: {$path}");
            return true;
        }

        $currentContent  = file_get_contents($absolutePath);
        $currentChecksum = md5($currentContent);
        $knownChecksum   = $this->checksums[$path] ?? null;

        // Si el checksum actual difiere del guardado → hubo edición manual
        if ($knownChecksum && $currentChecksum !== $knownChecksum) {
            if (!$force) {
                $command?->warn("  ! Edición manual detectada: {$path}");

                if ($command && !$command->confirm("¿Sobreescribir {$path}?", false)) {
                    $command?->line("  - Saltando: {$path}");
                    return false;
                }
            }

            // Con --force o confirmación: intentar regeneración parcial con marcadores
            if ($this->hasGenMarkers($currentContent)) {
                $merged = $this->mergeWithMarkers($currentContent, $content);
                $this->writeFile($absolutePath, $merged);
                $command?->info("  ~ Regenerado (parcial): {$path}");
                return true;
            }
        }

        // Sin cambios manuales o forzado: sobreescribir completo
        $this->writeFile($absolutePath, $content);
        $command?->info("  ~ Actualizado: {$path}");
        return true;
    }

    // Regeneración parcial: reemplaza solo el contenido entre marcadores [GEN:START/END]
    // preservando todo el código manual que esté fuera de esos bloques
    private function mergeWithMarkers(string $existing, string $generated): string
    {
        // Extraer todos los bloques [GEN:START:nombre]...[GEN:END:nombre] del generated
        preg_match_all(
            '/\{\{--\s*\[GEN:START:(\w+)\].*?--\}\}(.*?)\{\{--\s*\[GEN:END:\1\].*?--\}\}/s',
            $generated,
            $generatedBlocks,
            PREG_SET_ORDER
        );

        $merged = $existing;

        foreach ($generatedBlocks as $block) {
            $blockName    = $block[1];
            $blockContent = $block[0]; // Bloque completo incluyendo marcadores

            // Reemplazar el bloque correspondiente en el archivo existente
            $pattern = '/\{\{--\s*\[GEN:START:' . preg_quote($blockName, '/') . '\].*?--\}\}.*?\{\{--\s*\[GEN:END:' . preg_quote($blockName, '/') . '\].*?--\}\}/s';

            if (preg_match($pattern, $merged)) {
                $merged = preg_replace($pattern, $blockContent, $merged);
            }
        }

        // Mismo proceso para comentarios PHP // [GEN:START:nombre]
        preg_match_all(
            '/\/\/ \[GEN:START:(\w+)\](.*?)\/\/ \[GEN:END:\1\]/s',
            $generated,
            $phpBlocks,
            PREG_SET_ORDER
        );

        foreach ($phpBlocks as $block) {
            $blockName    = $block[1];
            $blockContent = $block[0];

            $pattern = '/\/\/ \[GEN:START:' . preg_quote($blockName, '/') . '\].*?\/\/ \[GEN:END:' . preg_quote($blockName, '/') . '\]/s';

            if (preg_match($pattern, $merged)) {
                $merged = preg_replace($pattern, $blockContent, $merged);
            }
        }

        return $merged;
    }

    private function hasGenMarkers(string $content): bool
    {
        return str_contains($content, '[GEN:START:') && str_contains($content, '[GEN:END:');
    }

    private function writeFile(string $absolutePath, string $content): void
    {
        $dir = dirname($absolutePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($absolutePath, $content);

        // Guardar checksum del archivo recién escrito
        $relativePath = str_replace(base_path() . '/', '', $absolutePath);
        $this->checksums[$relativePath] = md5($content);
        $this->saveChecksums();
    }

    private function loadChecksums(): array
    {
        if (!file_exists($this->checksumFile)) {
            return [];
        }

        return json_decode(file_get_contents($this->checksumFile), true) ?? [];
    }

    private function saveChecksums(): void
    {
        file_put_contents(
            $this->checksumFile,
            json_encode($this->checksums, JSON_PRETTY_PRINT)
        );
    }
}
