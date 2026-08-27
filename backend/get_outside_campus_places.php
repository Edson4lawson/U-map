<?php

require __DIR__ . '/vendor/autoload.php';

// Load Laravel environment
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Place;
use App\Services\Geo\PointInPolygon;

$allPlaces = Place::all();
$outsideCampusPlaces = [];

foreach ($allPlaces as $place) {
    if (!PointInPolygon::isPointInCampus($place->latitude, $place->longitude)) {
        $outsideCampusPlaces[] = [
            'name' => $place->name,
            'category' => $place->category ?? 'N/A',
            'latitude' => $place->latitude,
            'longitude' => $place->longitude,
        ];
    }
}

echo "=== Lieux hors campus à supprimer ===\n";
echo "Total : " . count($outsideCampusPlaces) . " lieux\n\n";

foreach ($outsideCampusPlaces as $place) {
    echo sprintf(
        "%s | %s | %s | %s\n",
        $place['name'],
        $place['category'],
        $place['latitude'],
        $place['longitude']
    );
}

// Save to JSON file
$jsonPath = __DIR__ . '/storage/app/private/outside_campus_places_' . date('Y-m-d_His') . '.json';
file_put_contents($jsonPath, json_encode([
    'total' => count($outsideCampusPlaces),
    'places' => $outsideCampusPlaces
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\nListe sauvegardée dans : $jsonPath\n";
