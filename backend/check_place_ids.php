<?php

require __DIR__ . '/vendor/autoload.php';

// Load Laravel environment
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Place;

$places = Place::select('id', 'uuid', 'name')->limit(10)->get();

echo "=== IDs des lieux dans la base ===\n";
foreach ($places as $place) {
    echo "ID: " . $place->id . " | UUID: " . $place->uuid . " | Nom: " . $place->name . "\n";
}
