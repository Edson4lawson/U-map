<?php

namespace App\Console\Commands;

use App\DTO\OverpassPlace;
use App\Services\CampusImporter;
use App\Services\OverpassCacheService;
use App\Services\Geo\Haversine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImporterLieuxCampusUAC extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campus:import
                            {--refresh : Force refresh Overpass cache}
                            {--commit : Execute database writes (dry-run by default)}
                            {--similarity=80 : Levenshtein similarity threshold (0-100)}
                            {--distance=100 : Distance threshold in meters}
                            {--stats : Show statistics only}
                            {--list : List all places retrieved from OSM}
                            {--manual-review : Show only places needing manual verification}
                            {--use-filtered : Use the 117 pre-filtered places instead of fetching from OSM}
                            {--strict : Delete all places not in Overpass results (cleanup mode)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import UAC campus places from OpenStreetMap via Overpass API';

    protected OverpassCacheService $overpassCacheService;
    protected CampusImporter $importer;

    public function __construct(OverpassCacheService $overpassCacheService, CampusImporter $importer)
    {
        parent::__construct();
        $this->overpassCacheService = $overpassCacheService;
        $this->importer = $importer;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Importation des lieux du campus UAC ===');
        $this->newLine();

        // Configure importer thresholds
        $this->importer->setSimilarityThreshold((int) $this->option('similarity'));
        $this->importer->setDistanceThreshold((int) $this->option('distance'));

        // Fetch places from Overpass or use pre-filtered places
        if ($this->option('use-filtered')) {
            $this->info('Utilisation des 117 lieux pré-filtrés...');
            $overpassPlaces = $this->getFilteredPlaces();
            $manualReviewPlaces = [];
        } else {
            $this->info('Récupération des lieux depuis OpenStreetMap...');
            
            // Use campus center coordinates from provided campus boundaries
            // Calculated from the campus LineString coordinates
            $centerLat = 6.41595; // Center of campus boundaries
            $centerLng = 2.341985; // Center of campus boundaries
            $radius = 800; // Reduced radius for precise campus area
            
            // Use campus coordinates
            $geoJsonData = $this->overpassCacheService->getOSMPlaces($centerLat, $centerLng, $radius);
            
            if (!$geoJsonData) {
                $this->warn('Aucune donnée récupérée depuis OpenStreetMap');
                return self::SUCCESS;
            }
            
            // Convert GeoJSON to OverpassPlace objects
            $overpassPlaces = [];
            $manualReviewPlaces = []; // Track places needing manual verification
            $seenPlaces = []; // Track seen places for deduplication
            
            foreach ($geoJsonData as $feature) {
                if (!isset($feature['properties'])) {
                    continue;
                }
                
                $props = $feature['properties'];
                $geometry = $feature['geometry'];
                
                // Skip places without names
                if (!isset($props['name']) || empty(trim($props['name']))) {
                    continue;
                }
                
                // Skip exact names (blacklist)
                $blacklistedNames = [
                    'Vikings fashion',
                    'Ligth Fashion',
                    'Bel Air Fashion',
                    'Kimel Fashions',
                    'Lieber fashion',
                    'Institut de micro-finance: Alodo Alomè',
                    'Institution de microfinance CIMMB',
                    "Clinique pédiatrique d'Abomey-Calavi",
                    'SOS Abomey-calavi',
                    "SOS Children's Village Abomey-Calavi",
                    "CEG D'Abomey-calavi",
                    'Institut universitaire Les Cours Sonou',
                    'École Superrieure de Commerce des Entreprises du Bénin (ESCAE-BENI)',
                    'African School of Economics',
                    'Instituit Paul de Tasse',
                    'Institut Ayoka adéola',
                    "Amphithéâtre Idriss Déby ITNO (Université d'Abomey Calavi-Bénin)",
                    'Fastfood',
                ];
                
                if (in_array($props['name'], $blacklistedNames)) {
                    continue;
                }
                
                // Skip irrelevant categories (never campus infrastructure)
                $blacklistedCategories = ['bench', 'waste_basket', 'recycling', 'drinking_water', 'water_point', 'toilets', 'motorcycle_parking', 'car_wash', 'dry_cleaning', 'hairdresser', 'beauty', 'place_of_worship', 'bakery', 'bar'];
                
                // Check if category is blacklisted
                $category = $props['amenity'] ?? null;
                if ($category && in_array($category, $blacklistedCategories)) {
                    // Exception for pharmacies: keep if name contains campus keywords
                    if ($category === 'pharmacy') {
                        $campusKeywords = ['UAC', 'EPAC', 'FAST', 'FSA', 'FASEG', 'FLLAC', 'FADESP', 'IFRI', 'BUAC', 'FASH', 'Faculté', 'Amphi', 'Institut', 'Rectorat', 'Résidence', 'Abomey-Calavi'];
                        $nameLower = strtolower($props['name']);
                        $hasCampusKeyword = false;
                        foreach ($campusKeywords as $keyword) {
                            if (stripos($nameLower, strtolower($keyword)) !== false) {
                                $hasCampusKeyword = true;
                                break;
                            }
                        }
                        if (!$hasCampusKeyword) {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }
                
                // Apply semantic filter
                $name = $props['name'];
                $category = $props['amenity'] ?? $props['building'] ?? null;
                
                // Campus keywords for name matching
                $campusKeywords = ['UAC', 'Abomey-Calavi', 'EPAC', 'FAST', 'FSA', 'FASEG', 'FLLAC', 'FADESP', 'IFRI', 'BUAC', 'FASH', 'Faculté', 'Amphi', 'Institut', 'Rectorat', 'Résidence', 'Bibliothèque', 'Laboratoire', 'Restaurant Universitaire', 'Resto U', 'Ecole Doctorale', 'Département', 'Ferme', 'Ecobank', 'Jardin', 'Zoo'];
                
                // Academic categories (explicitly campus infrastructure)
                $academicCategories = ['university', 'college', 'library', 'research_institute', 'dormitory', 'pitch', 'zoo', 'farmland', 'fuel'];
                
                // Ambiguous categories (need manual verification)
                $ambiguousCategories = ['clinic', 'restaurant', 'bank', 'atm'];
                
                // Check if name contains campus keyword
                $nameLower = strtolower($name);
                $hasCampusKeyword = false;
                foreach ($campusKeywords as $keyword) {
                    if (stripos($nameLower, strtolower($keyword)) !== false) {
                        $hasCampusKeyword = true;
                        break;
                    }
                }
                
                // Special handling for ENEAM - only if geographically on this campus
                if (stripos($nameLower, 'eneam') !== false) {
                    // ENEAM is in Cotonou, not Abomey-Calavi, so skip it
                    continue;
                }
                
                // Determine if place should be accepted, rejected, or flagged for manual review
                $isAcademicCategory = $category && in_array($category, $academicCategories);
                $isAmbiguousCategory = $category && in_array($category, $ambiguousCategories);
                
                // Accept if name has campus keyword OR category is explicitly academic
                if ($hasCampusKeyword || $isAcademicCategory) {
                    // Accept - proceed with processing
                } elseif ($isAmbiguousCategory) {
                    // Flag for manual verification
                    $manualReviewPlaces[] = [
                        'name' => $name,
                        'category' => $category,
                        'lat' => $geometry['coordinates'][1] ?? 0,
                        'lng' => $geometry['coordinates'][0] ?? 0,
                    ];
                    continue;
                } else {
                    // Reject - doesn't match any campus criteria
                    continue;
                }
                
                // Extract coordinates
                $lat = 0;
                $lon = 0;
                if (isset($geometry['coordinates']) && count($geometry['coordinates']) >= 2) {
                    $lon = $geometry['coordinates'][0];
                    $lat = $geometry['coordinates'][1];
                }
                
                // Determine type
                $type = 'other';
                if (isset($props['amenity'])) {
                    $type = 'amenity';
                } elseif (isset($props['building'])) {
                    $type = 'building';
                }
                
                // Deduplication: normalize name and check proximity
                $normalizedName = $this->normalizeName($props['name']);
                $isDuplicate = false;
                
                foreach ($seenPlaces as $seen) {
                    if ($seen['normalized'] === $normalizedName) {
                        // Check distance
                        $distance = Haversine::calculate($lat, $lon, $seen['lat'], $seen['lng']);
                        if ($distance < 15) { // 15 meters threshold
                            $isDuplicate = true;
                            break;
                        }
                    }
                }
                
                if ($isDuplicate) {
                    continue;
                }
                
                // Add to seen places
                $seenPlaces[] = [
                    'normalized' => $normalizedName,
                    'lat' => $lat,
                    'lng' => $lon,
                ];
                
                $overpassPlaces[] = new OverpassPlace(
                    osmId: $props['osmid'] ?? 0,
                    name: $props['name'],
                    latitude: $lat,
                    longitude: $lon,
                    type: $type,
                    category: $props['amenity'] ?? $props['building'] ?? null,
                    tags: $props
                );
            }
            
            $this->info(count($overpassPlaces) . ' lieux récupérés depuis OpenStreetMap');
            $this->info(count($manualReviewPlaces) . ' lieux à vérifier manuellement (catégories ambiguës)');
            $this->newLine();
        }

        // List all places if requested
        if ($this->option('list')) {
            $this->info('=== Liste des lieux récupérés ===');
            $this->newLine();
            
            // Write to file to avoid truncation
            $listData = [
                'total' => count($overpassPlaces),
                'places' => array_map(function ($place) {
                    return [
                        'name' => $place->name,
                        'category' => $place->category ?? 'N/A',
                        'type' => $place->type,
                        'latitude' => $place->latitude,
                        'longitude' => $place->longitude,
                    ];
                }, $overpassPlaces)
            ];
            
            $listPath = 'osm_places_list_' . now()->format('Y-m-d_His') . '.json';
            Storage::disk('local')->put($listPath, json_encode($listData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $fullPath = Storage::disk('local')->path($listPath);
            
            $this->info('Liste sauvegardée dans : ' . $fullPath);
            $this->info('Total lieux : ' . count($overpassPlaces));
            $this->newLine();
            
            // Show first 10 as preview
            $this->info('Aperçu (10 premiers) :');
            foreach (array_slice($overpassPlaces, 0, 10) as $place) {
                $this->line(sprintf(
                    '• %s (%s) - %s [%s, %s]',
                    $place->name,
                    $place->category ?? 'N/A',
                    $place->type,
                    number_format($place->latitude, 6),
                    number_format($place->longitude, 6)
                ));
            }
            $this->newLine();
        }

        // Show manual review places
        if (count($manualReviewPlaces) > 0) {
            $this->info('=== Lieux à vérifier manuellement (catégories ambiguës) ===');
            $this->table(
                ['Nom', 'Catégorie', 'Lat', 'Lng'],
                array_map(function ($place) {
                    return [
                        $place['name'],
                        $place['category'],
                        number_format($place['lat'], 6),
                        number_format($place['lng'], 6),
                    ];
                }, $manualReviewPlaces)
            );
            $this->newLine();
        }

        // If manual-review option is set, show only manual review places and exit
        if ($this->option('manual-review')) {
            $this->info('Total lieux à vérifier manuellement : ' . count($manualReviewPlaces));
            return self::SUCCESS;
        }

        // Compare with existing places
        $this->info('Comparaison avec les lieux existants en base...');
        $strictMode = $this->option('strict');
        if ($strictMode) {
            $this->warn('MODE STRICT : Tous les lieux non présents dans Overpass seront supprimés');
        }
        $comparison = $this->importer->compare($overpassPlaces, $strictMode);

        $this->info(count($comparison['to_create']) . ' nouveaux lieux à créer');
        $this->info(count($comparison['to_update']) . ' lieux à mettre à jour');
        $this->info(count($comparison['to_delete']) . ' lieux à supprimer (hors campus)');
        $this->newLine();

        // Show statistics only mode
        if ($this->option('stats')) {
            $this->showStatistics($comparison);
            return self::SUCCESS;
        }

        // Show preview
        $this->showPreview($comparison);

        // Check if commit flag is set
        if (!$this->option('commit')) {
            $this->warn('MODE DRY-RUN : Aucune écriture en base de données.');
            $this->warn('Utilisez --commit pour appliquer les changements.');
            return self::SUCCESS;
        }

        // Execute import with backup
        $this->newLine();
        
        // Create backup before any changes
        $this->info('Création du backup JSON...');
        $backupPath = $this->importer->backupPlaces();
        $this->info('Backup créé : ' . $backupPath);
        $this->newLine();

        // Confirm changes
        $confirmMessage = sprintf(
            'Confirmer %d mises à jour, %d créations, %d suppressions (hors campus) ? (y/n)',
            count($comparison['to_update']),
            count($comparison['to_create']),
            count($comparison['to_delete'])
        );
        
        if (!$this->confirm($confirmMessage, false)) {
            $this->warn('Importation annulée.');
            $this->info('Backup conservé : ' . $backupPath);
            return self::SUCCESS;
        }

        return $this->executeImport($comparison);
    }

    /**
     * Show preview of changes
     */
    protected function showPreview(array $comparison): void
    {
        if (count($comparison['to_create']) > 0) {
            $this->info('--- Nouveaux lieux à créer ---');
            $this->table(
                ['Nom', 'Type', 'Catégorie', 'Lat', 'Lng'],
                array_slice(array_map(fn($place) => [
                    $place->name,
                    $place->type,
                    $place->category ?? 'N/A',
                    $place->latitude,
                    $place->longitude,
                ], $comparison['to_create']), 0, 10)
            );

            if (count($comparison['to_create']) > 10) {
                $this->info('... et ' . (count($comparison['to_create']) - 10) . ' autres');
            }
        }

        if (count($comparison['to_update']) > 0) {
            $this->newLine();
            $this->info('--- Lieux à mettre à jour ---');
            $this->table(
                ['Nom actuel', 'Nom OSM', 'Distance (m)'],
                array_slice(array_map(fn($item) => [
                    $item['place']->name,
                    $item['overpass']->name,
                    $this->calculateDistance($item['place'], $item['overpass']),
                ], $comparison['to_update']), 0, 10)
            );

            if (count($comparison['to_update']) > 10) {
                $this->info('... et ' . (count($comparison['to_update']) - 10) . ' autres');
            }
        }

        if (count($comparison['to_delete']) > 0) {
            $this->newLine();
            $this->info('--- Lieux à supprimer (hors campus) ---');
            $this->table(
                ['Nom', 'Type', 'Catégorie', 'Lat', 'Lng'],
                array_slice(array_map(fn($place) => [
                    $place->name,
                    $place->type,
                    $place->category ?? 'N/A',
                    $place->latitude,
                    $place->longitude,
                ], $comparison['to_delete']), 0, 10)
            );

            if (count($comparison['to_delete']) > 10) {
                $this->info('... et ' . (count($comparison['to_delete']) - 10) . ' autres');
            }
        }
    }

    /**
     * Show statistics
     */
    protected function showStatistics(array $comparison): void
    {
        $this->info('=== Statistiques ===');
        $this->info('Total lieux OSM : ' . count($comparison['to_create']) + count($comparison['to_update']));
        $this->info('Nouveaux lieux : ' . count($comparison['to_create']));
        $this->info('Mises à jour : ' . count($comparison['to_update']));
        
        // Category breakdown
        $categories = [];
        foreach ($comparison['to_create'] as $place) {
            $cat = $place->category ?? 'unknown';
            $categories[$cat] = ($categories[$cat] ?? 0) + 1;
        }
        
        if (!empty($categories)) {
            $this->newLine();
            $this->info('--- Répartition par catégorie ---');
            foreach ($categories as $category => $count) {
                $this->info("  {$category}: {$count}");
            }
        }
    }

    /**
     * Execute the import
     */
    protected function executeImport(array $comparison): int
    {
        $this->info('Exécution de l\'importation...');
        $this->newLine();

        $created = 0;
        $updated = 0;
        $deleted = 0;
        $errors = 0;

        DB::beginTransaction();

        try {
            // Create new places
            foreach ($comparison['to_create'] as $overpassPlace) {
                try {
                    $this->importer->createPlace($overpassPlace);
                    $this->line("  ✓ Créé: {$overpassPlace->name}");
                    $created++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Erreur création {$overpassPlace->name}: {$e->getMessage()}");
                    $errors++;
                }
            }

            // Update existing places
            foreach ($comparison['to_update'] as $item) {
                try {
                    $this->importer->updatePlace($item['place'], $item['overpass']);
                    $this->line("  ✓ Mis à jour: {$item['place']->name}");
                    $updated++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Erreur mise à jour {$item['place']->name}: {$e->getMessage()}");
                    $errors++;
                }
            }

            // Delete places outside campus
            foreach ($comparison['to_delete'] as $place) {
                try {
                    $this->importer->deletePlace($place);
                    $this->line("  ✓ Supprimé (hors campus): {$place->name}");
                    $deleted++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Erreur suppression {$place->name}: {$e->getMessage()}");
                    $errors++;
                }
            }

            DB::commit();

            $this->newLine();
            $this->info('=== Importation terminée ===');
            $this->info("Créés: {$created}");
            $this->info("Mis à jour: {$updated}");
            $this->info("Supprimés (hors campus): {$deleted}");
            $this->info("Erreurs: {$errors}");

            Log::info('Campus import completed', [
                'created' => $created,
                'updated' => $updated,
                'deleted' => $deleted,
                'errors' => $errors,
            ]);

            return $errors > 0 ? self::FAILURE : self::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Erreur lors de l\'importation: ' . $e->getMessage());
            Log::error('Campus import failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }

    /**
     * Calculate distance between place and overpass place
     */
    protected function calculateDistance($place, OverpassPlace $overpassPlace): string
    {
        $distance = \App\Services\Geo\Haversine::calculateInMeters(
            $place->latitude,
            $place->longitude,
            $overpassPlace->latitude,
            $overpassPlace->longitude
        );

        return round($distance, 2) . ' m';
    }

    /**
     * Normalize name for deduplication (case-insensitive, accent-insensitive)
     */
    protected function normalizeName(string $name): string
    {
        // Transliterate accents to ASCII equivalents
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        // Convert to lowercase
        $name = strtolower($name);
        // Remove spaces, hyphens, apostrophes, and other non-alphanumeric characters
        $name = preg_replace('/[^a-z0-9]/', '', $name);
        return $name;
    }

    /**
     * Get the pre-filtered places with deduplication
     */
    protected function getFilteredPlaces(): array
    {
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

        // Apply deduplication with normalizeName and proximity check
        $seenPlaces = [];
        $deduplicatedPlaces = [];
        
        // Category priority for deduplication (higher = better)
        $categoryPriority = [
            'university' => 5,
            'library' => 4,
            'school' => 3,
            'restaurant' => 3,
            'cafe' => 3,
            'driving_school' => 3,
            'office' => 2,
            'greenhouse' => 2,
            'construction' => 1,
            'yes' => 1,
            'N/A' => 0,
            'other' => 0,
        ];
        
        foreach ($filteredPlaces as $place) {
            $normalizedName = $this->normalizeName($place['name']);
            $isDuplicate = false;
            $replaceIndex = -1;
            
            foreach ($seenPlaces as $index => $seen) {
                if ($seen['normalized'] === $normalizedName) {
                    // Check distance
                    $distance = Haversine::calculate($place['latitude'], $place['longitude'], $seen['lat'], $seen['lng']);
                    if ($distance < 15) { // 15 meters threshold
                        $isDuplicate = true;
                        
                        // Check if current place has better category
                        $currentPriority = $categoryPriority[$place['category']] ?? 0;
                        $seenPriority = $categoryPriority[$seen['category']] ?? 0;
                        
                        if ($currentPriority > $seenPriority) {
                            $replaceIndex = $index;
                        }
                        break;
                    }
                }
            }
            
            if (!$isDuplicate) {
                $deduplicatedPlaces[] = $place;
                $seenPlaces[] = [
                    'normalized' => $normalizedName,
                    'lat' => $place['latitude'],
                    'lng' => $place['longitude'],
                    'category' => $place['category'],
                ];
            } elseif ($replaceIndex >= 0) {
                // Replace with better category
                $deduplicatedPlaces[$replaceIndex] = $place;
                $seenPlaces[$replaceIndex] = [
                    'normalized' => $normalizedName,
                    'lat' => $place['latitude'],
                    'lng' => $place['longitude'],
                    'category' => $place['category'],
                ];
            }
        }

        // Convert to OverpassPlace objects
        $overpassPlaces = [];
        foreach ($deduplicatedPlaces as $place) {
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

        return $overpassPlaces;
    }
}
