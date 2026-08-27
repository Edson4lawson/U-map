<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

echo "Searching for similar place names...\n\n";

$missingNames = [
    'Laboratoire d\'hydraulique Appliqué',
    'Laboratoire des Sciences et Techniques de l\'Eau et de l\'Environnement',
    'École Nationale d\'Administartion et de Magistrature',
    'École Polytechnique d\'Abomey Calavi',
];

$allPlaces = Place::where('status', 'approved')->get(['uuid', 'name']);

foreach ($missingNames as $missingName) {
    echo "Searching for: {$missingName}\n";
    
    $keywords = explode(' ', $missingName);
    $found = false;
    
    foreach ($allPlaces as $place) {
        $matchCount = 0;
        foreach ($keywords as $keyword) {
            if (stripos($place->name, $keyword) !== false) {
                $matchCount++;
            }
        }
        
        if ($matchCount >= 2) {
            echo "  Similar found: {$place->uuid} - {$place->name}\n";
            $found = true;
        }
    }
    
    if (!$found) {
        echo "  No similar place found\n";
    }
    echo "\n";
}
