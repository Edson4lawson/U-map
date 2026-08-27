<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

echo "=== LISTE COMPLÈTE DES 155 LIEUX EN BASE LOCALE ===\n\n";

$places = Place::where('status', 'approved')->orderBy('name')->get(['uuid', 'name', 'category', 'latitude', 'longitude']);

echo "Total: {$places->count()} lieux\n\n";

foreach ($places as $index => $place) {
    $num = $index + 1;
    echo "{$num}. {$place->name} | {$place->category}\n";
}

echo "\n=== FIN DE LA LISTE ===\n";
