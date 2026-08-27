<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

echo "Checking current database state...\n\n";

$total = Place::where('status', 'approved')->count();
echo "Total approved places: {$total}\n\n";

$defaultCoordsCount = Place::where('status', 'approved')
    ->where('latitude', 6.4281)
    ->where('longitude', 2.3456)
    ->count();

echo "Places with default coordinates (6.4281, 2.3456): {$defaultCoordsCount}\n\n";

$realCoordsCount = $total - $defaultCoordsCount;
echo "Places with real coordinates: {$realCoordsCount}\n\n";

if ($defaultCoordsCount > 0) {
    echo "⚠️ WARNING: Some places have default coordinates!\n\n";
    
    $defaultPlaces = Place::where('status', 'approved')
        ->where('latitude', 6.4281)
        ->where('longitude', 2.3456)
        ->get(['uuid', 'name', 'latitude', 'longitude']);
    
    echo "Places with default coordinates:\n";
    foreach ($defaultPlaces as $place) {
        echo "  {$place->uuid} - {$place->name} ({$place->latitude}, {$place->longitude})\n";
    }
} else {
    echo "✅ All places have real coordinates\n\n";
}

echo "\nSample of places with coordinates:\n";
$samples = Place::where('status', 'approved')->limit(5)->get(['uuid', 'name', 'latitude', 'longitude']);
foreach ($samples as $place) {
    echo "  {$place->uuid} - {$place->name} ({$place->latitude}, {$place->longitude})\n";
}
