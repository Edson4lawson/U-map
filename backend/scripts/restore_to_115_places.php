<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;
use Illuminate\Support\Facades\DB;

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
$extraPlaces = Place::whereNotIn('uuid', $allowedPlaces)->get(['uuid', 'name']);

echo "\nExtra places to delete (" . $extraPlaces->count() . "):\n";
foreach ($extraPlaces as $place) {
    echo "  - {$place->name} ({$place->uuid})\n";
}

// Confirm deletion
echo "\nProceeding with deletion of " . $extraPlaces->count() . " extra places...\n";

// Delete using direct DB query for reliability
$deleted = DB::table('places')
    ->whereNotIn('uuid', $allowedPlaces)
    ->delete();

echo "Deleted $deleted places from database.\n";

// Verify the result
$newCount = Place::count();
echo "New total places: $newCount\n";

if ($newCount == count($allowedPlaces)) {
    echo "SUCCESS: Database now has exactly " . count($allowedPlaces) . " places.\n";
} else {
    echo "ERROR: Expected " . count($allowedPlaces) . " places but found $newCount.\n";
}
