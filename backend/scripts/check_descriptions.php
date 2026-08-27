<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

echo "=== ÉCHANTILLON DE 10 DESCRIPTIONS ACTUELLES EN BASE ===\n\n";

$places = Place::where('status', 'approved')->limit(10)->get(['uuid', 'name', 'description']);

foreach ($places as $index => $place) {
    $num = $index + 1;
    echo "{$num}. {$place->name} (UUID: {$place->uuid})\n";
    if ($place->description) {
        echo "   Description: {$place->description}\n";
    } else {
        echo "   Description: NULL\n";
    }
    echo "\n";
}

// Check for generic descriptions
echo "=== VÉRIFICATION DES DESCRIPTIONS GÉNÉRIQUES ===\n\n";

$genericPatterns = ['Lieu importé depuis OpenStreetMap', 'Importé depuis', 'OpenStreetMap', 'Import from'];

$genericCount = 0;
$allPlaces = Place::where('status', 'approved')->get(['uuid', 'name', 'description']);

foreach ($allPlaces as $place) {
    if ($place->description) {
        foreach ($genericPatterns as $pattern) {
            if (stripos($place->description, $pattern) !== false) {
                $genericCount++;
                echo "⚠️ {$place->name}: {$place->description}\n";
                break;
            }
        }
    }
}

if ($genericCount === 0) {
    echo "✓ Aucune description générique détectée\n";
} else {
    echo "\nTotal descriptions génériques: {$genericCount}\n";
}

// Check for name repetition in description
echo "\n=== VÉRIFICATION DES RÉPÉTITIONS DE NOM ===\n\n";

$nameRepetitionCount = 0;
foreach ($allPlaces as $place) {
    if ($place->description && stripos($place->description, $place->name) !== false) {
        $nameRepetitionCount++;
        echo "⚠️ {$place->name}: La description contient le nom\n";
        echo "   Description: {$place->description}\n";
    }
}

if ($nameRepetitionCount === 0) {
    echo "✓ Aucune répétition de nom dans les descriptions\n";
} else {
    echo "\nTotal répétitions de nom: {$nameRepetitionCount}\n";
}
