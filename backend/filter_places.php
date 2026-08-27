<?php

require __DIR__ . '/vendor/autoload.php';

// Load the JSON file
$jsonFile = __DIR__ . '/storage/app/private/osm_places_list_2026-07-20_015212.json';
$data = json_decode(file_get_contents($jsonFile), true);

// Blacklist of exact names to exclude
$blacklistedNames = [
    'Vikings fashion',
    'Ligth Fashion',
    'Bel Air Fashion',
    'Kimel Fashions',
    'Lieber fashion',
    'Institut de micro-finance: Alodo Alomè',
    'Institution de microfinance CIMMB',
    "Clinique pédiatrique d'Abomey-Calavi",
    'SOS Abomey-calavi',
    "SOS Children's Village Abomey-Calavi",
    "CEG D'Abomey-calavi",
    'Institut universitaire Les Cours Sonou',
    'École Superrieure de Commerce des Entreprises du Bénin (ESCAE-BENI)',
    'African School of Economics',
    'Instituit Paul de Tasse',
    'Institut Ayoka adéola',
    'Fastfood',
];

// Normalize name function
function normalizeName($name) {
    $name = strtolower($name);
    $name = preg_replace('/[\s\-\'\']/', '', $name);
    $name = preg_replace('/[^a-z0-9]/', '', $name);
    return $name;
}

// Haversine distance calculation
function haversine($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // meters
    
    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $deltaLat = deg2rad($lat2 - $lat1);
    $deltaLon = deg2rad($lon2 - $lon1);
    
    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLon / 2) * sin($deltaLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

// Filter out blacklisted places
$filteredPlaces = [];
foreach ($data['places'] as $place) {
    if (!in_array($place['name'], $blacklistedNames)) {
        $filteredPlaces[] = $place;
    }
}

// Deduplicate by normalized name + proximity
$deduplicatedPlaces = [];
$seenPlaces = [];

foreach ($filteredPlaces as $place) {
    $normalizedName = normalizeName($place['name']);
    $isDuplicate = false;
    
    foreach ($seenPlaces as $seen) {
        if ($seen['normalized'] === $normalizedName) {
            // Check distance
            $distance = haversine(
                $place['latitude'], $place['longitude'],
                $seen['lat'], $seen['lng']
            );
            if ($distance < 15) { // 15 meters threshold
                $isDuplicate = true;
                echo "Duplicate found: {$place['name']} (distance: " . round($distance, 2) . "m)\n";
                break;
            }
        }
    }
    
    if (!$isDuplicate) {
        $deduplicatedPlaces[] = $place;
        $seenPlaces[] = [
            'normalized' => $normalizedName,
            'lat' => $place['latitude'],
            'lng' => $place['longitude'],
        ];
    }
}

echo "\n=== Results ===\n";
echo "Original places: " . count($data['places']) . "\n";
echo "After blacklist filter: " . count($filteredPlaces) . "\n";
echo "After deduplication: " . count($deduplicatedPlaces) . "\n\n";

// Save filtered and deduplicated places
$outputData = [
    'total' => count($deduplicatedPlaces),
    'places' => $deduplicatedPlaces,
];

$outputFile = __DIR__ . '/storage/app/private/filtered_places_' . date('Y-m-d_His') . '.json';
file_put_contents($outputFile, json_encode($outputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Filtered places saved to: $outputFile\n";

// Display the list
echo "\n=== Filtered and Deduplicated Places ===\n";
foreach ($deduplicatedPlaces as $place) {
    echo sprintf(
        "%s (%s) - %s [%s, %s]\n",
        $place['name'],
        $place['category'],
        $place['type'],
        number_format($place['latitude'], 6),
        number_format($place['longitude'], 6)
    );
}
