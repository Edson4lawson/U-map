<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Place;

echo "=== VÉRIFICATION DES 4 LIEUX SPÉCIFIQUES MENTIONNÉS ===\n\n";

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

$specificPlaces = [
    'Amphithéatre Théléthon' => ['lat' => 6.4197174, 'lon' => 2.3455629],
    'École Nationale d\'Administartion et de Magistrature' => ['lat' => 6.418572, 'lon' => 2.340408],
    'École Polytechnique d\'Abomey Calavi' => ['lat' => 6.4142818, 'lon' => 2.3423477],
    'Laboratoire des Sciences et Techniques de l\'Eau et de l\'Environnement' => ['lat' => 6.4126838, 'lon' => 2.3384459],
];

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000;
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

$dbPlaces = Place::where('status', 'approved')->get(['uuid', 'name', 'category', 'latitude', 'longitude']);

foreach ($specificPlaces as $name => $coords) {
    echo "=== {$name} ===\n";
    echo "Coords: ({$coords['lat']}, {$coords['lon']})\n";
    
    // Check if this place exists in DB
    $placeInDb = $dbPlaces->firstWhere('name', $name);
    if ($placeInDb) {
        echo "✓ Existe en base: UUID {$placeInDb->uuid}\n";
    } else {
        echo "✗ N'existe PAS en base\n";
    }
    
    // Check if name is in allowedNames
    if (in_array($name, $allowedNames)) {
        echo "✓ Dans places_list.txt (validé)\n";
    } else {
        echo "✗ PAS dans places_list.txt (non validé)\n";
    }
    
    // Find closest places in DB
    $closest = [];
    foreach ($dbPlaces as $dbPlace) {
        $distance = calculateDistance($coords['lat'], $coords['lon'], $dbPlace->latitude, $dbPlace->longitude);
        if ($distance < 50) { // Within 50 meters
            $closest[] = [
                'place' => $dbPlace,
                'distance' => $distance
            ];
        }
    }
    
    if (count($closest) > 0) {
        usort($closest, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });
        
        echo "\nLieux proches (< 50m):\n";
        foreach ($closest as $item) {
            $p = $item['place'];
            $dist = round($item['distance'], 2);
            $inAllowed = in_array($p->name, $allowedNames) ? '✓' : '✗';
            echo "  {$inAllowed} {$p->name} ({$p->uuid}) - {$dist}m - ({$p->latitude}, {$p->longitude})\n";
        }
    } else {
        echo "\nAucun lieu proche (< 50m) trouvé\n";
    }
    
    echo "\n";
}

// Check the two specific cases mentioned
echo "=== CAS PARTICULIERS À VÉRIFIER ===\n\n";

$specialCases = [
    'Amphithéâtre Etisalat' => ['lat' => 6.41871, 'lon' => 2.34521, 'compare' => 'amphi Etisalat'],
    'Amphithéâtre Houdegbe' => ['lat' => 6.41895, 'lon' => 2.34559, 'compare' => 'Amphi Houdegbe'],
];

foreach ($specialCases as $name => $data) {
    echo "=== {$name} ===\n";
    echo "Coords: ({$data['lat']}, {$data['lon']})\n";
    
    $placeInDb = $dbPlaces->firstWhere('name', $name);
    if ($placeInDb) {
        echo "✓ Existe en base: UUID {$placeInDb->uuid}\n";
    } else {
        echo "✗ N'existe PAS en base\n";
    }
    
    $comparePlace = $dbPlaces->firstWhere('name', $data['compare']);
    if ($comparePlace) {
        $distance = calculateDistance($data['lat'], $data['lon'], $comparePlace->latitude, $comparePlace->longitude);
        echo "Comparaison avec {$data['compare']}: {$distance}m\n";
        echo "  {$data['compare']} coords: ({$comparePlace->latitude}, {$comparePlace->longitude})\n";
        
        if ($distance < 20) {
            echo "  ⚠️ DOUBLON PROBABLE (distance < 20m)\n";
        }
    } else {
        echo "✗ {$data['compare']} non trouvé en base\n";
    }
    
    echo "\n";
}
