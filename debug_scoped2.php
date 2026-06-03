<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$resolver = new App\Generator\Introspection\RelationResolver();
$scoped = $resolver->getScopedRelations(App\Models\Cobro::class);

foreach ($scoped as $name => $rel) {
    echo "Relation: {$name}\n";
    echo "  isPivotTable: " . ($rel['isPivotTable'] ? 'YES' : 'NO') . "\n";
    echo "  related: " . $rel['related'] . "\n";
    echo "  pivotExtraFields: " . json_encode($rel['pivotExtraFields']) . "\n";
}