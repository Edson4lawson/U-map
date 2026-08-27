<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

$total = Place::count();
$approved = Place::where('status', 'approved')->count();
$pending = Place::where('status', 'pending')->count();

echo "Total places: $total\n";
echo "Approved places: $approved\n";
echo "Pending places: $pending\n";

// Check for default coordinates
$defaultCoords = Place::where('latitude', 6.4281)->where('longitude', 2.3456)->count();
echo "Places with default coordinates: $defaultCoords\n";

// List some approved places
echo "\nFirst 5 approved places:\n";
$places = Place::where('status', 'approved')->limit(5)->get(['uuid', 'name', 'latitude', 'longitude']);
foreach ($places as $place) {
    echo "  - {$place->name} ({$place->uuid}): {$place->latitude}, {$place->longitude}\n";
}
