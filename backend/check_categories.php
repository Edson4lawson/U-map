<?php

require __DIR__ . '/vendor/autoload.php';

// Load Laravel environment
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Place;

$places = Place::select('type', 'category')->get();
$categories = $places->pluck('category')->unique()->sort()->values();
$types = $places->pluck('type')->unique()->sort()->values();

echo "=== Categories ===\n";
foreach ($categories as $cat) {
    echo $cat . "\n";
}

echo "\n=== Types ===\n";
foreach ($types as $type) {
    echo $type . "\n";
}

echo "\n=== Count by Category ===\n";
$byCategory = $places->groupBy('category');
foreach ($byCategory as $category => $items) {
    echo $category . ': ' . $items->count() . "\n";
}
