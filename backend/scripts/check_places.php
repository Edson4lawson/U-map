<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

echo "Checking places in database vs places_list.txt...\n\n";

// IDs from places_list.txt (115 places)
$allowedIds = [
    343, 307, 341, 53, 309, 339, 340, 317, 310, 319, 318, 308, 326, 311, 320, 312, 327, 313, 111, 321,
    314, 304, 274, 359, 48, 328, 58, 59, 322, 342, 344, 345, 346, 347, 348, 349, 303, 329, 323, 110,
    251, 239, 358, 330, 315, 361, 350, 331, 55, 54, 352, 50, 51, 52, 299, 300, 56, 57, 93, 357,
    316, 356, 272, 353, 332, 278, 324, 333, 338, 325, 277, 334, 273, 354, 335, 336, 337, 301, 355,
    279, 280, 271, 270, 298, 302, 305, 233, 292, 282, 293, 283, 291, 284, 285, 286, 287, 288, 294,
    289, 295, 296, 290, 297, 275, 306, 360, 68, 127, 94, 281, 276, 22, 115, 351, 49, 92
];

$allowedIds = array_map(fn($id) => (string) $id, $allowedIds);

$places = Place::where('status', 'approved')->get(['uuid', 'name']);

echo "Total places in database: " . $places->count() . "\n";
echo "Total places in places_list.txt: " . count($allowedIds) . "\n\n";

echo "Current places in database:\n";
foreach ($places as $place) {
    echo "  {$place->uuid} - {$place->name}\n";
}

echo "\nChecking matches:\n";
$matching = 0;
$nonMatching = 0;

foreach ($places as $place) {
    if (in_array($place->uuid, $allowedIds)) {
        $matching++;
        echo "  ✓ {$place->uuid} - {$place->name}\n";
    } else {
        $nonMatching++;
        echo "  ✗ {$place->uuid} - {$place->name} (NOT IN LIST)\n";
    }
}

echo "\nSummary:\n";
echo "  Matching: {$matching}\n";
echo "  Non-matching: {$nonMatching}\n";
echo "  Total in DB: {$places->count()}\n";
echo "  Expected in list: " . count($allowedIds) . "\n";
