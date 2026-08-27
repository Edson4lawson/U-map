<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;
use Illuminate\Support\Facades\DB;

// The 40 extra places to delete (UUIDs from the previous analysis)
$extraUuids = [
    '1', '2', '3', '4', '5', '6', '7', '8', '10', '12', '15', '16', '17', '18', '20', '25', '27', '28', '29', '30',
    '35', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51', '52', '53', '54', '55'
];

echo "Current database places: " . Place::count() . "\n";
echo "Places to delete: " . count($extraUuids) . "\n";

// Show what will be deleted
$placesToDelete = Place::whereIn('uuid', $extraUuids)->get(['uuid', 'name', 'latitude', 'longitude']);
echo "\nPlaces to be deleted:\n";
foreach ($placesToDelete as $place) {
    echo "  - {$place->name} ({$place->uuid}): {$place->latitude}, {$place->longitude}\n";
}

// Delete using direct DB query
echo "\nProceeding with deletion...\n";
$deleted = DB::table('places')->whereIn('uuid', $extraUuids)->delete();
echo "Deleted $deleted places from database.\n";

// Verify result
$newCount = Place::count();
echo "New total places: $newCount\n";

if ($newCount == 115) {
    echo "SUCCESS: Database now has exactly 115 places.\n";
} else {
    echo "WARNING: Expected 115 places but found $newCount.\n";
}

// Check for default coordinates
$defaultCoords = Place::where('latitude', 6.4281)->where('longitude', 2.3456)->count();
echo "Places with default coordinates: $defaultCoords\n";
