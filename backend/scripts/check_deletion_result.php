<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

echo "=== VÉRIFICATION POST-SUPPRESSION ===\n\n";

$total = Place::where('status', 'approved')->count();
echo "Total lieux en base: {$total}\n\n";

// Check if the deleted places still exist
$deletedNames = [
    'Rectorat - Université d\'Abomey-Calavi',
    'Faculté des Sciences et Techniques (FAST)',
    'Faculté des Sciences de la Santé (FSS)',
    'Faculté des Sciences Economiques et de Gestion (FASEG)',
    'Faculté des Lettres, Langues, Arts et Communication (FLLAC)',
    'Faculté de Droit et Sciences Politiques (FADESP)',
    'École Polytechnique d\'Abomey-Calavi (EPAC)',
    'Institut de Formation et de Recherche en Informatique (IFRI)',
    'Faculté des Sciences Agronomiques (FSA)',
    'Bibliothèque Universitaire Centrale (BUAC)',
];

echo "Vérification si les lieux supprimés existent encore:\n";
foreach ($deletedNames as $name) {
    $exists = Place::where('status', 'approved')->where('name', $name)->exists();
    echo ($exists ? "✗" : "✓") . " {$name}: " . ($exists ? "EXISTE ENCORE" : "SUPPRIMÉ") . "\n";
}

echo "\n=== DIAGNOSTIC ===\n";
echo "Si les lieux existent encore, la suppression a échoué.\n";
echo "Possible cause: SQLite transaction not committed or error in deletion logic.\n";
