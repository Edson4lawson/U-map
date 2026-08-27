<?php

require __DIR__ . '/vendor/autoload.php';

use App\DTO\OverpassPlace;
use App\Services\CampusImporter;
use Illuminate\Support\Facades\DB;

// Load the filtered places (117 places)
$filteredPlaces = [
    ['name' => "Bibliothèque Centrale de L'UAC", 'category' => 'library', 'type' => 'amenity', 'latitude' => 6.4148789, 'longitude' => 2.3430109],
    ['name' => "École Nationale d'Administartion et de Magistrature", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.418572, 'longitude' => 2.340408],
    ['name' => "Faculté de Droit et Sciences Politiques", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.416003, 'longitude' => 2.3422],
    ['name' => "Faculté de Lettres, Arts et Sciences Humaines", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.4154003, 'longitude' => 2.3432936],
    ['name' => "Faculté des Sciences Agronomiques", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.416288, 'longitude' => 2.341917],
    ['name' => "Administration Faseg", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.4198676, 'longitude' => 2.3409163],
    ['name' => "Rectorat Annexe", 'category' => 'N/A', 'type' => 'other', 'latitude' => 6.414933, 'longitude' => 2.34406],
    ['name' => "Rectorat", 'category' => 'N/A', 'type' => 'other', 'latitude' => 6.4149892, 'longitude' => 2.3434687],
    ['name' => "Laboratoire FAST", 'category' => 'N/A', 'type' => 'other', 'latitude' => 6.4177955, 'longitude' => 2.3452764],
    ['name' => "Institut Confucius", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.4118343, 'longitude' => 2.3398385],
    ['name' => "Institut National de l'Eau", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.4127469, 'longitude' => 2.3410377],
    ['name' => "Bibliothèque EPAC", 'category' => 'library', 'type' => 'amenity', 'latitude' => 6.4142388, 'longitude' => 2.3421237],
    ['name' => "Bibliothèque Fadesp", 'category' => 'library', 'type' => 'amenity', 'latitude' => 6.4161199, 'longitude' => 2.3437931],
    ['name' => "Laboratoire de Cartographie(LaCarto)", 'category' => 'N/A', 'type' => 'other', 'latitude' => 6.4215756, 'longitude' => 2.341058],
    ['name' => "École Polytechnique d'Abomey Calavi", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.4142818, 'longitude' => 2.3423477],
    ['name' => "Institut de Formation et de Recherche en Informatique", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.4163194, 'longitude' => 2.3401684],
    ['name' => "Université Virtuelle Africaine (UVA)", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.4162884, 'longitude' => 2.3401975],
    ['name' => "Amphithéare Théléthon", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4197174, 'longitude' => 2.3455629],
    ['name' => "Bâtiment MIRD", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.4200337, 'longitude' => 2.3413847],
    ['name' => "Amphi MIRD", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.4199933, 'longitude' => 2.3414367],
    ['name' => "amphi Etisalat", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.4185722, 'longitude' => 2.3453709],
    ['name' => "SMEL-UAC", 'category' => 'N/A', 'type' => 'other', 'latitude' => 6.41572, 'longitude' => 2.341185],
    ['name' => "Vice rectorat", 'category' => 'N/A', 'type' => 'other', 'latitude' => 6.4156792, 'longitude' => 2.3411916],
    ['name' => "Amphithéâtre Idriss Déby ITNO (Université d'Abomey Calavi-Bénin)", 'category' => 'school', 'type' => 'amenity', 'latitude' => 6.434741, 'longitude' => 2.3385729],
    ['name' => "Fondation Vallet", 'category' => 'library', 'type' => 'amenity', 'latitude' => 6.4143314, 'longitude' => 2.3361493],
    ['name' => "Resteau-bar UAC", 'category' => 'cafe', 'type' => 'amenity', 'latitude' => 6.4139099, 'longitude' => 2.3432301],
    ['name' => "Laboratoire d'hydraulique Appliqué", 'category' => 'N/A', 'type' => 'other', 'latitude' => 6.4129091, 'longitude' => 2.3386272],
    ['name' => "Laboratoire d'Ecologie Appliquée", 'category' => 'N/A', 'type' => 'other', 'latitude' => 6.4153935, 'longitude' => 2.3392691],
    ['name' => "Laboratoire de l'Hydraulique Appliquée", 'category' => 'N/A', 'type' => 'other', 'latitude' => 6.4128933, 'longitude' => 2.3386506],
    ['name' => "Laboratoire des Sciences et Techniques de l'Eau et de l'Environnement", 'category' => 'N/A', 'type' => 'other', 'latitude' => 6.4126838, 'longitude' => 2.3384459],
    ['name' => "Centre Commercial EPAC", 'category' => 'fast_food', 'type' => 'amenity', 'latitude' => 6.4126933, 'longitude' => 2.3417289],
    ['name' => "CIPMA Chaire UNESCO", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.4204124, 'longitude' => 2.3397398],
    ['name' => "Amphi Etisalat", 'category' => 'driving_school', 'type' => 'amenity', 'latitude' => 6.4184667, 'longitude' => 2.3455087],
    ['name' => "Université d'Abomey-Calavi", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.4160904, 'longitude' => 2.341985],
    ['name' => "Résidence A2", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4127327, 'longitude' => 2.3443504],
    ['name' => "Résidence B2", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4120702, 'longitude' => 2.3442852],
    ['name' => "Résidence C2", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4114776, 'longitude' => 2.3441538],
    ['name' => "Résidence D", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4113521, 'longitude' => 2.3420552],
    ['name' => "Résidence D2", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4109818, 'longitude' => 2.3438431],
    ['name' => "Résidence E", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.410895, 'longitude' => 2.3420654],
    ['name' => "Résidence E2", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4105489, 'longitude' => 2.3436204],
    ['name' => "Résidence F2", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4101177, 'longitude' => 2.3434023],
    ['name' => "Résidence I", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4103889, 'longitude' => 2.3421367],
    ['name' => "Résidence C Français", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4124055, 'longitude' => 2.3428682],
    ['name' => "Centre commercial EPAC", 'category' => 'restaurant', 'type' => 'amenity', 'latitude' => 6.4128969, 'longitude' => 2.3417398],
    ['name' => "Résidence A", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.413275, 'longitude' => 2.3429122],
    ['name' => "Résidence B", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.41298, 'longitude' => 2.3428723],
    ['name' => "Résidence F", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.413553, 'longitude' => 2.3424162],
    ['name' => "Résidence G", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4132643, 'longitude' => 2.3423716],
    ['name' => "Résidence H", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4129691, 'longitude' => 2.3423248],
    ['name' => "Résidence MK2", 'category' => 'residential', 'type' => 'building', 'latitude' => 6.4119257, 'longitude' => 2.3421885],
    ['name' => "Restaurant Universitaire 1", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4140837, 'longitude' => 2.3430245],
    ['name' => "Imprimerie de l'UAC", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4156398, 'longitude' => 2.3411154],
    ['name' => "Laboratoire de Zoogéographie", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.416679, 'longitude' => 2.3382802],
    ['name' => "Restaurant Universitaire 2", 'category' => 'restaurant', 'type' => 'amenity', 'latitude' => 6.4176659, 'longitude' => 2.3410396],
    ['name' => "Bâtiment H FSA", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4163682, 'longitude' => 2.3406225],
    ['name' => "Amphi Uemoa", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.420964, 'longitude' => 2.3425072],
    ['name' => "Restaurant Universitaire 4", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.414713, 'longitude' => 2.344861],
    ['name' => "Salle des doctorant IFRI", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4156289, 'longitude' => 2.3445206],
    ['name' => "Administration FAST", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4178785, 'longitude' => 2.345273],
    ['name' => "Amphi B 750", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4213678, 'longitude' => 2.3423517],
    ['name' => "Amphi A 1000", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4199462, 'longitude' => 2.3409118],
    ['name' => "Amphi Alassane Dramane Ouattara", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4192652, 'longitude' => 2.345855],
    ['name' => "Amphi C 1000", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.421664, 'longitude' => 2.3411441],
    ['name' => "Amphi Houdegbe", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4194169, 'longitude' => 2.3453697],
    ['name' => "Amphi Idriss Deby", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4214419, 'longitude' => 2.3417296],
    ['name' => "Amphi MIRD", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4212657, 'longitude' => 2.3429475],
    ['name' => "Amphi etisalat", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4184714, 'longitude' => 2.3455236],
    ['name' => "Amphi Prof Géro AMOUSSAGA", 'category' => 'construction', 'type' => 'building', 'latitude' => 6.4200934, 'longitude' => 2.3461836],
    ['name' => "Département d'Espagnol", 'category' => 'office', 'type' => 'building', 'latitude' => 6.42047, 'longitude' => 2.3423357],
    ['name' => "Labo FAST", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4195706, 'longitude' => 2.3459236],
    ['name' => "Zone Masters", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.4215686, 'longitude' => 2.3401417],
    ['name' => "Amphi A 500", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4201071, 'longitude' => 2.3395339],
    ['name' => "Amphi B 500", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4195257, 'longitude' => 2.3398406],
    ['name' => "Amphi B 1000", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4198266, 'longitude' => 2.3388584],
    ['name' => "Amphi Codjovi", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.41874, 'longitude' => 2.3398926],
    ['name' => "Amphi Mensah", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4191216, 'longitude' => 2.3397295],
    ['name' => "Bibliothèque Vieyra", 'category' => 'library', 'type' => 'amenity', 'latitude' => 6.4182293, 'longitude' => 2.3405708],
    ['name' => "Bâtiment I FSA", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4167221, 'longitude' => 2.3404105],
    ['name' => "Laboratoire d'Enthomologie Appliquée", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4169817, 'longitude' => 2.3413689],
    ['name' => "Laboratoire d'Hydrologie Appliquée", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4197162, 'longitude' => 2.3394523],
    ['name' => "Amphi B EPAC", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4130251, 'longitude' => 2.3390684],
    ['name' => "Amphi IRAN/Administration IFRI", 'category' => 'office', 'type' => 'building', 'latitude' => 6.4163609, 'longitude' => 2.3401809],
    ['name' => "Bibliothèque Centre de Documentation", 'category' => 'library', 'type' => 'amenity', 'latitude' => 6.4160648, 'longitude' => 2.3409609],
    ['name' => "Bâtiment I EPAC", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.412296, 'longitude' => 2.3385063],
    ['name' => "Département Génie Civil", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4128994, 'longitude' => 2.3389422],
    ['name' => "EPAC Village", 'category' => 'university', 'type' => 'amenity', 'latitude' => 6.4125328, 'longitude' => 2.3386689],
    ['name' => "Laboratoire d' Hydraulique et de Maîtrise de l'Eau", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4163346, 'longitude' => 2.3408782],
    ['name' => "Laboratoire d'Hydrobiologie et d'Aquaculture", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4162466, 'longitude' => 2.3386427],
    ['name' => "Laboratoire de Biotechnologie Animale", 'category' => 'N/A', 'type' => 'other', 'latitude' => 6.4118422, 'longitude' => 2.3393685],
    ['name' => "Laboratoire de Pathologie Animale, Microbilogie et Immunologie", 'category' => 'N/A', 'type' => 'other', 'latitude' => 6.41199, 'longitude' => 2.3394378],
    ['name' => "Laboratoire de Physiologie", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4139008, 'longitude' => 2.3390389],
    ['name' => "Laboratoire de Recherche en Biologie Appliquée", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.413622, 'longitude' => 2.3412493],
    ['name' => "Laboratoire d'Hydrobiologie et de Recherche sur les Zones Humides (LHyReZ)", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4148303, 'longitude' => 2.33868],
    ['name' => "Amphi A 150", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4154272, 'longitude' => 2.3431961],
    ['name' => "Amphi A 400", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4154141, 'longitude' => 2.3428517],
    ['name' => "Administration Fadesp", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4160599, 'longitude' => 2.3420325],
    ['name' => "Bloc Laboratoire Polyvalent", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4168367, 'longitude' => 2.3423801],
    ['name' => "Administration EPAC", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4142688, 'longitude' => 2.3419698],
    ['name' => "Bâtiment B EPAC", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4146377, 'longitude' => 2.3416465],
    ['name' => "Bâtiment B FSA", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4163661, 'longitude' => 2.3416156],
    ['name' => "Bâtiment C EPAC", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4145207, 'longitude' => 2.3423923],
    ['name' => "Bâtiment C FSA", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4166401, 'longitude' => 2.3423727],
    ['name' => "Bâtiment D EPAC", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4148515, 'longitude' => 2.3420642],
    ['name' => "Bâtiment E EPAC", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4151792, 'longitude' => 2.3417367],
    ['name' => "Département de génétique et de biotechnologie", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4169944, 'longitude' => 2.3425559],
    ['name' => "École Doctorale Fadesp", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4162029, 'longitude' => 2.3434415],
    ['name' => "FAST", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.417052, 'longitude' => 2.3450672],
    ['name' => "Laboratoire FAST", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4170774, 'longitude' => 2.3433174],
    ['name' => "Laboratoire Labio", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4168447, 'longitude' => 2.3420219],
    ['name' => "Laboratoire de Microbiologie des Sols et d'Ecologie Microbiènne", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4167867, 'longitude' => 2.3425793],
    ['name' => "Laboratoire de génétique et de Biotechnologie", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4166762, 'longitude' => 2.342798],
    ['name' => "Laboratoire", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.416882, 'longitude' => 2.3426953],
    ['name' => "Décanat FLLAC & FASHS", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4200067, 'longitude' => 2.3419431],
    ['name' => "Amphithéâtre Jean PLIYA / Institut du Cadre de Vie UAC (ICaV)", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4198356, 'longitude' => 2.3460795],
    ['name' => "Serre du laboratoire de Génétique FAST", 'category' => 'greenhouse', 'type' => 'building', 'latitude' => 6.4167487, 'longitude' => 2.3426696],
    ['name' => "Département d'Etude Germanique", 'category' => 'yes', 'type' => 'building', 'latitude' => 6.4200382, 'longitude' => 2.342875],
];

// Convert to OverpassPlace objects
$overpassPlaces = [];
foreach ($filteredPlaces as $place) {
    $overpassPlaces[] = new OverpassPlace(
        osmId: 0,
        name: $place['name'],
        latitude: $place['latitude'],
        longitude: $place['longitude'],
        type: $place['type'],
        category: $place['category'],
        tags: ['name' => $place['name'], 'amenity' => $place['category'], 'building' => $place['category']]
    );
}

// Initialize CampusImporter
$importer = new CampusImporter();

echo "=== Importation des 117 lieux filtrés ===\n\n";

// Compare with existing places
echo "Comparaison avec les lieux existants en base...\n";
$comparison = $importer->compare($overpassPlaces);

echo count($comparison['to_create']) . " nouveaux lieux à créer\n";
echo count($comparison['to_update']) . " lieux à mettre à jour\n";
echo count($comparison['to_delete']) . " lieux à supprimer (hors campus)\n\n";

// Backup existing places
echo "Sauvegarde des lieux existants...\n";
$backupFile = __DIR__ . '/storage/app/private/places_backup_' . date('Y-m-d_His') . '.json';
$allPlaces = \App\Models\Place::all();
file_put_contents($backupFile, json_encode($allPlaces, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Backup sauvegardé dans: $backupFile\n\n";

// Ask for confirmation
echo "=== Résumé des modifications ===\n";
echo "Nouveaux lieux à créer: " . count($comparison['to_create']) . "\n";
echo "Lieux à mettre à jour: " . count($comparison['to_update']) . "\n";
echo "Lieux à supprimer: " . count($comparison['to_delete']) . "\n\n";

echo "Confirmer l'importation ? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'y') {
    echo "Importation annulée.\n";
    exit(0);
}

// Execute import
echo "\n=== Exécution de l'importation ===\n\n";

DB::beginTransaction();
$created = 0;
$updated = 0;
$deleted = 0;
$errors = 0;

try {
    // Create new places
    foreach ($comparison['to_create'] as $overpassPlace) {
        try {
            $importer->createPlace($overpassPlace);
            echo "✓ Créé: {$overpassPlace->name}\n";
            $created++;
        } catch (\Exception $e) {
            echo "✗ Erreur création {$overpassPlace->name}: {$e->getMessage()}\n";
            $errors++;
        }
    }

    // Update existing places
    foreach ($comparison['to_update'] as $item) {
        try {
            $importer->updatePlace($item['place'], $item['overpass']);
            echo "✓ Mis à jour: {$item['place']->name}\n";
            $updated++;
        } catch (\Exception $e) {
            echo "✗ Erreur mise à jour {$item['place']->name}: {$e->getMessage()}\n";
            $errors++;
        }
    }

    // Delete places outside campus
    foreach ($comparison['to_delete'] as $place) {
        try {
            $importer->deletePlace($place);
            echo "✓ Supprimé (hors campus): {$place->name}\n";
            $deleted++;
        } catch (\Exception $e) {
            echo "✗ Erreur suppression {$place->name}: {$e->getMessage()}\n";
            $errors++;
        }
    }

    DB::commit();

    echo "\n=== Importation terminée ===\n";
    echo "Créés: $created\n";
    echo "Mis à jour: $updated\n";
    echo "Supprimés: $deleted\n";
    echo "Erreurs: $errors\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Erreur lors de l'importation: {$e->getMessage()}\n";
    exit(1);
}
