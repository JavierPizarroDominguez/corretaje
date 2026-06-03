<?php
/**
 * Importa un archivo SQL a MySQL respetando DELIMITER y foreign keys.
 */

require __DIR__ . '/../vendor/autoload.php';

$sqlFile = 'C:/xampp/htdocs/src/corretaje-bd.sql';

if (!file_exists($sqlFile)) {
    die("ERROR: No se encontró el archivo SQL: {$sqlFile}\n");
}

$host = '127.0.0.1';
$port = 3307;
$db   = 'corretaje';
$user = 'root';
$pass = '1234';

try {
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conectado a MySQL.\n";

    // Desactivar foreign key checks temporalmente
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Crear schema
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$db}`");

    echo "Base de datos '{$db}' seleccionada.\n\n";

    // Leer y parsear el SQL manualmente
    $content = file_get_contents($sqlFile);

    // Normalizar DELIMITER
    $delimiter = ';';
    $statements = [];
    $currentStmt = '';
    $lines = explode("\n", $content);

    foreach ($lines as $line) {
        $line = trim($line);

        // Ignorar comentarios y líneas vacías
        if (empty($line) || strpos($line, '--') === 0 || strpos($line, '/*') === 0) {
            continue;
        }

        // Detectar DELIMITER
        if (preg_match('/^DELIMITER\s+(.+)$/i', $line, $matches)) {
            $delimiter = trim($matches[1]);
            continue;
        }

        $currentStmt .= $line . "\n";

        // Si la línea termina con el delimitador actual, ejecutar
        if (substr($line, -strlen($delimiter)) === $delimiter) {
            // Quitar el delimitador del final
            $stmt = substr($currentStmt, 0, -strlen($delimiter));
            $stmt = trim($stmt);

            if (!empty($stmt)) {
                $statements[] = $stmt;
            }
            $currentStmt = '';
        }
    }

    echo "Statements parseados: " . count($statements) . "\n\n";

    $success = 0;
    $skipped = 0;
    $failed  = 0;
    $errorMsgs = [];

    foreach ($statements as $i => $stmt) {
        try {
            $pdo->exec($stmt);
            $success++;
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            // Ignorar: tabla ya existe, schema ya existe, duplicate entry
            if (strpos($msg, 'already exists') !== false ||
                strpos($msg, 'Duplicate entry') !== false ||
                strpos($msg, '42S01') !== false ||
                strpos($msg, '23000') !== false) {
                $skipped++;
            } else {
                $failed++;
                $errorMsgs[] = "[Stmt {$i}] " . substr($msg, 0, 120);
            }
        }
    }

    // Reactivar foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "=== RESULTADO ===\n";
    echo "Éxito:   {$success}\n";
    echo "Ignorado (ya existe): {$skipped}\n";
    echo "Fallido: {$failed}\n";

    if (!empty($errorMsgs)) {
        echo "\nErrores:\n";
        foreach (array_slice($errorMsgs, 0, 10) as $err) {
            echo "  {$err}\n";
        }
    }

    // Verificar tablas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTablas totales: " . count($tables) . "\n";

    // Verificar tablas clave
    $expected = ['cliente', 'propiedad', 'contrato', 'cobro', 'participante_contrato', 'participante_cobro', 'servicio', 'unidad'];
    echo "\nVerificación de tablas clave:\n";
    foreach ($expected as $table) {
        $exists = in_array($table, $tables);
        echo "  [" . ($exists ? "✓" : "✗") . "] {$table}\n";
    }

    echo "\nImportación completada.\n";

} catch (PDOException $e) {
    die("ERROR: " . $e->getMessage() . "\n");
}
