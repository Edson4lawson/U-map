<?php

require __DIR__ . '/vendor/autoload.php';

// Load Laravel environment
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Place;

$places = Place::select('name', 'latitude', 'longitude')->get();

$minLat = $places->min('latitude');
$maxLat = $places->max('latitude');
$minLng = $places->min('longitude');
$maxLng = $places->max('longitude');

echo "=== Bounds des lieux actuels dans la base ===\n";
echo "Total lieux: " . $places->count() . "\n";
echo "Min Lat: $minLat\n";
echo "Max Lat: $maxLat\n";
echo "Min Lng: $minLng\n";
echo "Max Lng: $maxLng\n";
echo "\n";

// Afficher quelques exemples de lieux
echo "=== Exemples de lieux ===\n";
foreach ($places->take(10) as $place) {
    echo sprintf("%s [%s, %s]\n", $place->name, $place->latitude, $place->longitude);
}
