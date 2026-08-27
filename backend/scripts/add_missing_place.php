<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

echo "Adding missing place: Laboratoire d'hydraulique Appliqué\n\n";

// Check if similar place exists
$similar = Place::where('name', 'like', '%hydraulique%')->where('name', 'like', '%Appliqué%')->first();

if ($similar) {
    echo "Found similar place: {$similar->uuid} - {$similar->name}\n";
    echo "Updating name to match exactly...\n";
    $similar->name = "Laboratoire d'hydraulique Appliqué";
    $similar->save();
    echo "✅ Updated\n";
} else {
    echo "No similar place found. Creating new place...\n";
    
    // Create new place with default coordinates (will need to be updated)
    $newPlace = Place::create([
        'uuid' => '9999999999',
        'slug' => 'laboratoire-hydraulique-applique',
        'name' => "Laboratoire d'hydraulique Appliqué",
        'type' => 'laboratory',
        'category' => 'Laboratoire',
        'description' => 'Laboratoire d\'hydraulique appliqué pour les travaux pratiques.',
        'latitude' => 6.4281,
        'longitude' => 2.3456,
        'images' => [],
        'tags' => ['laboratory', 'hydraulics'],
        'status' => 'approved',
    ]);
    
    echo "✅ Created new place with UUID: {$newPlace->uuid}\n";
}
