<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$resolver = new App\Generator\Introspection\RelationResolver();
$scoped = $resolver->getScopedRelations(App\Models\Cobro::class);

echo "Found: " . count($scoped) . PHP_EOL;
print_r(array_keys($scoped));