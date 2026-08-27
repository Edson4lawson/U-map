<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\OverpassCacheService;

// Load Laravel environment
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = new OverpassCacheService();

// Test complementary queries only - use actual campus polygon
$polygonCoords = [
    [6.405122, 2.345004],
    [6.428566, 2.350263],
    [6.441226, 2.352149],
    [6.439765, 2.345052],
    [6.430071, 2.329738],
    [6.415090, 2.332799],
];
$polygon = implode(' ', array_map(function ($coord) {
    return $coord[0] . ' ' . $coord[1];
}, $polygonCoords));

// Test each query individually
$queries = [
    'leisure=pitch' => <<<QL
[out:json][timeout:25];
(
  node["leisure"="pitch"](poly:"{$polygon}");
  way["leisure"="pitch"](poly:"{$polygon}");
);
out center tags;
QL
,
    'tourism=zoo' => <<<QL
[out:json][timeout:25];
(
  node["tourism"="zoo"](poly:"{$polygon}");
  way["tourism"="zoo"](poly:"{$polygon}");
);
out center tags;
QL
,
    'landuse=farmland+Ferme' => <<<QL
[out:json][timeout:25];
(
  node["landuse"="farmland"]["name"~"Ferme", i](poly:"{$polygon}");
  way["landuse"="farmland"]["name"~"Ferme", i](poly:"{$polygon}");
);
out center tags;
QL
,
    'amenity=fuel' => <<<QL
[out:json][timeout:25];
(
  node["amenity"="fuel"](poly:"{$polygon}");
  way["amenity"="fuel"](poly:"{$polygon}");
);
out center tags;
QL
,
    'amenity=bank+Ecobank' => <<<QL
[out:json][timeout:25];
(
  node["amenity"="bank"]["name"~"Ecobank", i](poly:"{$polygon}");
  way["amenity"="bank"]["name"~"Ecobank", i](poly:"{$polygon}");
);
out center tags;
QL
,
];

echo "=== Testing Complementary Overpass Queries ===\n\n";

$server = 'https://overpass-api.de/api/interpreter';

foreach ($queries as $name => $query) {
    echo "Testing query: $name\n";
    echo "Query:\n$query\n";
    
    // Use curl like OverpassCacheService
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $server);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: U-Map Campus Importer/1.0',
    ]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "✗ Error: $error\n";
    } elseif ($httpCode === 200) {
        $data = json_decode($response, true);
        $count = isset($data['elements']) ? count($data['elements']) : 0;
        echo "✓ Success! Found $count elements\n";
        
        if ($count > 0) {
            echo "Results:\n";
            foreach ($data['elements'] as $element) {
                $nameTag = $element['tags']['name'] ?? 'N/A';
                $type = $element['tags']['leisure'] ?? $element['tags']['tourism'] ?? $element['tags']['landuse'] ?? $element['tags']['amenity'] ?? 'N/A';
                echo "  - $nameTag (type: $type)\n";
            }
        }
    } else {
        echo "✗ Failed: HTTP $httpCode\n";
        echo "Response: $response\n";
    }
    
    echo "\n---\n\n";
}
