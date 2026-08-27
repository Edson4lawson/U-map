<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

echo "=== VÉRIFICATION DU DOUBLON POTENTIEL: AMPHITHÉÂTRE THÉLÉTHON ===\n\n";

$targetLat = 6.4197174;
$targetLon = 2.3455629;

// Find all places with exact coordinates
$exactMatches = Place::where('status', 'approved')
    ->where('latitude', $targetLat)
    ->where('longitude', $targetLon)
    ->get(['uuid', 'name', 'category', 'latitude', 'longitude', 'description']);

echo "Lieux avec coordonnées exactes ({$targetLat}, {$targetLon}):\n";
echo "Total trouvé: {$exactMatches->count()}\n\n";

if ($exactMatches->count() > 0) {
    foreach ($exactMatches as $index => $place) {
        $num = $index + 1;
        echo "{$num}. UUID: {$place->uuid}\n";
        echo "   Nom: {$place->name}\n";
        echo "   Catégorie: {$place->category}\n";
        echo "   Coords: ({$place->latitude}, {$place->longitude})\n";
        echo "   Description: " . ($place->description ? substr($place->description, 0, 100) . '...' : 'NULL') . "\n\n";
    }
    
    if ($exactMatches->count() > 1) {
        echo "⚠️ VRAI DOUBLON DÉTECTÉ: {$exactMatches->count()} entrées avec mêmes coordonnées\n";
        echo "→ Il faut n'en garder qu'une et supprimer l'autre\n";
    } else {
        echo "✓ UNE SEULE ENTRÉE: Pas de doublon en base\n";
        echo "→ Retirer de la liste de suppression (différence d'accent dans le nom)\n";
    }
} else {
    echo "Aucun lieu trouvé avec ces coordonnées exactes\n";
}

// Also check for similar names
echo "\n=== LIEUX AVEC NOMS SIMILAIRES ===\n";

$similarNames = Place::where('status', 'approved')
    ->where('name', 'like', '%Théléthon%')
    ->orWhere('name', 'like', '%Telethon%')
    ->get(['uuid', 'name', 'category', 'latitude', 'longitude']);

echo "Lieux avec nom contenant 'Théléthon' ou 'Telethon':\n";
echo "Total trouvé: {$similarNames->count()}\n\n";

foreach ($similarNames as $index => $place) {
    $num = $index + 1;
    echo "{$num}. UUID: {$place->uuid}\n";
    echo "   Nom: {$place->name}\n";
    echo "   Coords: ({$place->latitude}, {$place->longitude})\n\n";
}
