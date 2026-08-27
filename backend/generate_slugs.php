<?php

require __DIR__ . '/vendor/autoload.php';

// Load Laravel environment
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Place;

$places = Place::all();

foreach ($places as $place) {
    // Generate slug from name
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $place->name)));
    
    // Remove multiple hyphens and trim
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // If slug is empty, use UUID
    if (empty($slug)) {
        $slug = 'place-' . $place->uuid;
    }
    
    // Check if slug already exists and append UUID if needed
    $existing = Place::where('slug', $slug)->where('id', '!=', $place->id)->first();
    if ($existing) {
        $slug = $slug . '-' . $place->uuid;
    }
    
    $place->slug = $slug;
    $place->save();
    
    echo "Updated: {$place->name} -> {$slug}\n";
}

echo "\nSlugs generated for all places.\n";
