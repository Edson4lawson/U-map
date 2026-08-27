<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

// Read the allowed places from places_list.txt
$allowedNames = [];
$lines = file(__DIR__ . '/places_list.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    if (preg_match('/^\d+\s+\|\s*(.+?)\s*\|/', $line, $matches)) {
        $allowedNames[] = trim($matches[1]);
    }
}

echo "Allowed place names from places_list.txt: " . count($allowedNames) . "\n";
echo "Current database places: " . Place::count() . "\n";

// Find places in database by name
$allPlaces = Place::all(['uuid', 'name', 'latitude', 'longitude']);

echo "\nMatching places by name:\n";
$matchedUuids = [];
foreach ($allowedNames as $allowedName) {
    $found = false;
    foreach ($allPlaces as $place) {
        // Normalize names for comparison (remove accents, lowercase, trim)
        $normalizedName = strtolower(trim($place->name));
        $normalizedAllowed = strtolower(trim($allowedName));
        
        // Simple comparison - could be improved with accent removal
        if ($normalizedName === $normalizedAllowed || 
            str_replace(['é', 'è', 'ê', 'ë'], 'e', $normalizedName) === str_replace(['é', 'è', 'ê', 'ë'], 'e', $normalizedAllowed)) {
            $matchedUuids[] = $place->uuid;
            echo "  ✓ Matched: {$place->name} ({$place->uuid})\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "  ✗ NOT FOUND: $allowedName\n";
    }
}

echo "\nMatched " . count($matchedUuids) . " places by name\n";

// Find extra places
$extraPlaces = Place::whereNotIn('uuid', $matchedUuids)->get(['uuid', 'name', 'latitude', 'longitude']);
echo "\nExtra places to delete (" . $extraPlaces->count() . "):\n";
foreach ($extraPlaces as $place) {
    echo "  - {$place->name} ({$place->uuid}): {$place->latitude}, {$place->longitude}\n";
}
