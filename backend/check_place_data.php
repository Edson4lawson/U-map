<?php

require __DIR__ . '/vendor/autoload.php';

// Load Laravel environment
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Place;

$place = Place::find(3091038117);

if ($place) {
    echo "=== Données du lieu ID 3091038117 ===\n";
    echo "ID: " . $place->id . "\n";
    echo "UUID: " . $place->uuid . "\n";
    echo "Nom: " . $place->name . "\n";
    echo "Type: " . $place->type . "\n";
    echo "Catégorie: " . $place->category . "\n";
    echo "Description: " . $place->description . "\n";
    echo "Latitude: " . $place->latitude . "\n";
    echo "Longitude: " . $place->longitude . "\n";
    echo "Horaires: " . $place->opening_hours . "\n";
    echo "Tags: " . json_encode($place->tags) . "\n";
    echo "Images: " . json_encode($place->images) . "\n";
    echo "Statut: " . $place->status . "\n";
} else {
    echo "Lieu non trouvé\n";
}
