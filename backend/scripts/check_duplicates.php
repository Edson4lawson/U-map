<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

echo "=== VÉRIFICATION DES DOUBLONS PAR COORDONNÉES ===\n\n";

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

$dbPlaces = Place::where('status', 'approved')->get(['uuid', 'name', 'category', 'latitude', 'longitude']);

// Function to calculate distance in meters between two coordinates
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
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

echo "=== DOUBLONS EXACTS (mêmes coordonnées) ===\n\n";

$exactDuplicates = [];
$nearbyDuplicates = [];

foreach ($dbPlaces as $place1) {
    foreach ($dbPlaces as $place2) {
        if ($place1->uuid === $place2->uuid) continue;
        
        $distance = calculateDistance($place1->latitude, $place1->longitude, $place2->latitude, $place2->longitude);
        
        if ($distance < 1) { // Less than 1 meter - exact duplicate
            $key = min($place1->uuid, $place2->uuid) . '-' . max($place1->uuid, $place2->uuid);
            if (!isset($exactDuplicates[$key])) {
                $exactDuplicates[$key] = [
                    'place1' => $place1,
                    'place2' => $place2,
                    'distance' => $distance
                ];
            }
        } elseif ($distance < 20) { // Less than 20 meters - nearby
            $key = min($place1->uuid, $place2->uuid) . '-' . max($place1->uuid, $place2->uuid);
            if (!isset($nearbyDuplicates[$key])) {
                $nearbyDuplicates[$key] = [
                    'place1' => $place1,
                    'place2' => $place2,
                    'distance' => $distance
                ];
            }
        }
    }
}

if (count($exactDuplicates) > 0) {
    echo "Doublons exacts trouvés:\n";
    foreach ($exactDuplicates as $dup) {
        echo "  {$dup['place1']->name} ({$dup['place1']->uuid}) == {$dup['place2']->name} ({$dup['place2']->uuid})\n";
        echo "    Coords: ({$dup['place1']->latitude}, {$dup['place1']->longitude})\n";
        echo "    Distance: {$dup['distance']}m\n\n";
    }
} else {
    echo "Aucun doublon exact trouvé.\n\n";
}

echo "=== DOUBLONS PROCHES (< 20m) ===\n\n";

if (count($nearbyDuplicates) > 0) {
    echo "Doublons proches trouvés:\n";
    foreach ($nearbyDuplicates as $dup) {
        echo "  {$dup['place1']->name} ({$dup['place1']->uuid}) ≈ {$dup['place2']->name} ({$dup['place2']->uuid})\n";
        echo "    Coords1: ({$dup['place1']->latitude}, {$dup['place1']->longitude})\n";
        echo "    Coords2: ({$dup['place2']->latitude}, {$dup['place2']->longitude})\n";
        echo "    Distance: " . round($dup['distance'], 2) . "m\n\n";
    }
} else {
    echo "Aucun doublon proche trouvé.\n\n";
}

// Now identify which places to actually delete
$offCampusPlaces = $dbPlaces->filter(function ($place) use ($allowedNames) {
    return !in_array($place->name, $allowedNames);
});

echo "=== LIEUX HORS-CAMPUS (avant déduplication) ===\n";
echo "Total: {$offCampusPlaces->count()}\n\n";

// Remove exact duplicates from deletion list
$placesToDelete = [];
$uuidsToKeep = [];

foreach ($exactDuplicates as $dup) {
    // Keep the one that's in allowedNames, delete the other
    if (in_array($dup['place1']->name, $allowedNames)) {
        $uuidsToKeep[] = $dup['place1']->uuid;
        $placesToDelete[] = $dup['place2']->uuid;
    } elseif (in_array($dup['place2']->name, $allowedNames)) {
        $uuidsToKeep[] = $dup['place2']->uuid;
        $placesToDelete[] = $dup['place1']->uuid;
    }
}

echo "=== UUIDS À GARDER (doublons exacts) ===\n";
foreach ($uuidsToKeep as $uuid) {
    $place = $dbPlaces->firstWhere('uuid', $uuid);
    echo "  {$place->name} ({$uuid})\n";
}

echo "\n=== UUIDS À SUPPRIMER (doublons exacts) ===\n";
foreach ($placesToDelete as $uuid) {
    $place = $dbPlaces->firstWhere('uuid', $uuid);
    echo "  {$place->name} ({$uuid})\n";
}

echo "\n=== LIEUX HORS-CAMPUS FINAUX À SUPPRIMER ===\n";
$finalToDelete = $offCampusPlaces->filter(function ($place) use ($placesToDelete) {
    return !in_array($place->uuid, $placesToDelete);
});

echo "Total: {$finalToDelete->count()}\n\n";
foreach ($finalToDelete as $place) {
    echo "  {$place->name} | {$place->category} | ({$place->latitude}, {$place->longitude})\n";
}

echo "\n=== RÉSUMÉ ===\n";
echo "Lieux hors-campus identifiés: {$offCampusPlaces->count()}\n";
echo "Doublons exacts retirés de la suppression: " . count($placesToDelete) . "\n";
echo "Lieux à supprimer finalement: {$finalToDelete->count()}\n";
echo "Lieux restants après suppression: " . ($dbPlaces->count() - $finalToDelete->count()) . "\n";
