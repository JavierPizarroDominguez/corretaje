<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = DB::select('SHOW TABLES');

echo "Database: " . DB::connection()->getDatabaseName() . "\n";
echo "Tables found: " . count($tables) . "\n\n";

foreach ($tables as $table) {
    $name = array_values((array)$table)[0];
    echo "  - {$name}\n";
}
