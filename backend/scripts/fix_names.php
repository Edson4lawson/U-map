<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

echo "Fixing place names to match places_list.txt...\n\n";

// Name corrections needed
$corrections = [
    'Laboratoire d\'hydraulique Appliqué' => 'Laboratoire d\'hydraulique Appliqué',
    'Laboratoire des Sciences et Techniques de l\'Eau et de l\'Environnement' => 'Laboratoire des Sciences et Techniques de l\'Eau et de l\'Environnement',
    'École Nationale d\'Administartion et de Magistrature' => 'École Nationale d\'Administartion et de Magistrature',
    'École Polytechnique d\'Abomey Calavi' => 'École Polytechnique d\'Abomey Calavi',
];

$fixedCount = 0;

foreach ($corrections as $oldName => $newName) {
    $place = Place::where('name', 'like', '%' . str_replace(['\'', ' '], ['', '%'], $oldName) . '%')->first();
    
    if ($place) {
        echo "Found similar: '{$place->name}' -> '{$newName}'\n";
        $place->name = $newName;
        $place->save();
        $fixedCount++;
    } else {
        echo "Not found: {$oldName}\n";
    }
}

echo "\nFixed {$fixedCount} place names\n";
