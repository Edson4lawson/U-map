<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

echo "Correcting place names to match places_list.txt exactly...\n\n";

$corrections = [
    '7946002363' => 'Laboratoire des Sciences et Techniques de l\'Eau et de l\'Environnement',
    '3091038115' => 'École Nationale d\'Administartion et de Magistrature',
    '4401032990' => 'École Polytechnique d\'Abomey Calavi',
];

$fixedCount = 0;

foreach ($corrections as $uuid => $correctName) {
    $place = Place::where('uuid', $uuid)->first();
    
    if ($place) {
        echo "Correcting: '{$place->name}' -> '{$correctName}'\n";
        $place->name = $correctName;
        $place->save();
        $fixedCount++;
    } else {
        echo "Not found with UUID: {$uuid}\n";
    }
}

echo "\nFixed {$fixedCount} place names\n";
