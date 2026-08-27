<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

// Read the allowed places from places_list.txt
$allowedPlaces = [];
$lines = file(__DIR__ . '/places_list.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    if (preg_match('/^(\d+)\s+\|/', $line, $matches)) {
        $allowedPlaces[] = $matches[1];
    }
}

echo "Allowed places from places_list.txt: " . count($allowedPlaces) . "\n";
echo "Current database places: " . Place::count() . "\n";

// Find places in database that are NOT in the allowed list
$extraPlaces = Place::whereNotIn('uuid', $allowedPlaces)->get(['uuid', 'name', 'latitude', 'longitude']);

echo "\nExtra places to delete (" . $extraPlaces->count() . "):\n";
foreach ($extraPlaces as $place) {
    echo "  - {$place->name} ({$place->uuid}): {$place->latitude}, {$place->longitude}\n";
}

// Find places in allowed list that are NOT in database
$missingPlaces = array_diff($allowedPlaces, Place::pluck('uuid')->toArray());
echo "\nMissing places from database (" . count($missingPlaces) . "):\n";
foreach ($missingPlaces as $uuid) {
    echo "  - UUID: $uuid\n";
}
