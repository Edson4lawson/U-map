<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

echo "=== EXÉCUTION DE LA SUPPRESSION DES 40 LIEUX HORS-CAMPUS ===\n\n";

// Names from places_list.txt (115 places)
$allowedNames = [
    'Administration EPAC', 'Administration FAST', 'Administration Fadesp', 'Administration Faseg',
    'Amphi A 1000', 'Amphi A 150', 'Amphi A 400', 'Amphi A 500', 'Amphi Alassane Dramane Ouattara',
    'Amphi B 1000', 'Amphi B 500', 'Amphi B 750', 'Amphi B EPAC', 'Amphi C 1000', 'Amphi Codjovi',
    'Amphi Houdegbe', 'Amphi IRAN/ Administration IFRI', 'Amphi Idriss Deby', 'Amphi MIRD',
    'Amphi Mensah', 'Amphi Prof Géro AMOUSSAGA', 'Amphi Uemoa',
    'Amphithéâtre Jean PLIYA / Institut du Cadre de Vie UAC (ICaV)', 'Bibliothèque Centrale de L\'UAC',
    'Bibliothèque Centre de Documentation', 'Bibliothèque EPAC', 'Bibliothèque Fadesp', 'Bibliothèque Vieyra',
    'Bloc Laboratoire Polyvalent', 'Bâtiment B EPAC', 'Bâtiment B FSA', 'Bâtiment C EPAC', 'Bâtiment C FSA',
    'Bâtiment D EPAC', 'Bâtiment E EPAC', 'Bâtiment H FSA', 'Bâtiment I EPAC', 'Bâtiment I FSA',
    'Bâtiment MIRD', 'CIPMA Chaire UNESCO', 'Centre Commercial EPAC', 'Décanat FLLAC & FASHS',
    'Département Génie Civil', 'Département d\'Espagnol', 'Département d\'Etude Germanique',
    'Département de génétique et de biotechnologie', 'EPAC Village', 'Ecobank', 'Ecobank (distributeur automatique)',
    'FAST', 'Faculté de Droit et Sciences Politiques', 'Faculté de Lettres, Arts et Sciences Humaines',
    'Faculté des Sciences Agronomiques', 'Ferme d\'application et de production de la FSA', 'Imprimerie de l\'UAC',
    'Institut Confucius', 'Institut National de l\'Eau', 'Institut de Formation et de Recherche en Informatique',
    'Jardin Botanique et Zoologique Edouard Adjanohoun', 'Labo FAST', 'Laboratoire',
    'Laboratoire FAST', 'Laboratoire Labio', 'Laboratoire d\' Hydraulique et de Maîtrise de l\'Eau',
    'Laboratoire d\'Ecologie Appliquée', 'Laboratoire d\'Enthomologie Appliquée',
    'Laboratoire d\'Hydrobiologie et d\'Aquaculture', 'Laboratoire d\'Hydrobiologie et de Recherche sur les Zones Humides (LHyReZ)',
    'Laboratoire d\'Hydrologie Appliquée', 'Laboratoire d\'hydraulique Appliqué', 'Laboratoire de Biotechnologie Animale',
    'Laboratoire de Cartographie(LaCarto)', 'Laboratoire de Microbiologie des Sols et d\'Ecologie Microbiènne',
    'Laboratoire de Pathologie Animale, Microbilogie et Immunologie', 'Laboratoire de Physiologie',
    'Laboratoire de Recherche en Biologie Appliquée', 'Laboratoire de Zoogéographie',
    'Laboratoire de génétique et de Biotechnologie', 'Laboratoire de l\'Hydraulique Appliquée',
    'Laboratoire des Sciences et Techniques de l\'Eau et de l\'Environnement', 'Rectorat', 'Rectorat Annexe',
    'Restaurant Universitaire 1', 'Restaurant Universitaire 2', 'Restaurant Universitaire 4', 'Resteau-bar UAC',
    'Résidence A', 'Résidence A2', 'Résidence B', 'Résidence B2', 'Résidence C Français', 'Résidence C2',
    'Résidence D', 'Résidence D2', 'Résidence E', 'Résidence E2', 'Résidence F', 'Résidence F2', 'Résidence G',
    'Résidence H', 'Résidence I', 'Résidence MK2', 'SMEL-UAC', 'Salle des doctorant IFRI',
    'Serre du laboratoire de Génétique FAST', 'Station MRS', 'Station puma', 'Université Virtuelle Africaine (UVA)',
    'Université d\'Abomey-Calavi', 'Vice rectorat', 'Zone Master', 'amphi Etisalat', 'École Doctorale Fadesp',
    'École Nationale d\'Administartion et de Magistrature', 'École Polytechnique d\'Abomey Calavi'
];

// UUIDs to KEEP
$uuidsToKeep = [
    '3091038115', // École Nationale d'Administartion et de Magistrature
    '4401032990', // École Polytechnique d'Abomey Calavi
    '7946002363', // Laboratoire des Sciences et Techniques de l'Eau et de l'Environnement
    '4972015829', // Amphithéatre Théléthon
];

$dbPlaces = Place::where('status', 'approved')->get(['uuid', 'name', 'category', 'latitude', 'longitude']);

echo "Avant suppression: {$dbPlaces->count()} lieux\n\n";

// Identify places to delete
$placesToDelete = $dbPlaces->filter(function ($place) use ($allowedNames, $uuidsToKeep) {
    if (in_array($place->uuid, $uuidsToKeep)) {
        return false;
    }
    return !in_array($place->name, $allowedNames);
});

echo "Lieux à supprimer: {$placesToDelete->count()}\n\n";

// Perform deletion
$deletedCount = 0;
foreach ($placesToDelete as $place) {
    $place->delete();
    $deletedCount++;
    echo "✓ Supprimé: {$place->name} ({$place->uuid})\n";
}

echo "\n=== SUPPRESSION TERMINÉE ===\n";
echo "Total supprimé: {$deletedCount}\n";

// Verify count
$newCount = Place::where('status', 'approved')->count();
echo "Nouveau total: {$newCount}\n";
echo "Attendu: 115\n";

if ($newCount === 115) {
    echo "✓ COMPTE CORRECT\n";
} else {
    echo "⚠️ ÉCART DE COMPTE: " . (115 - $newCount) . "\n";
}
