<?php

namespace App\Console\Commands;

use App\Models\Place;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchPlaceImages extends Command
{
    protected $signature = 'campus:fetch-images
                            {--commit : Apply changes to database (dry-run by default)}
                            {--limit=0 : Limit number of places to process (0 = all)}
                            {--names= : Specific place names to process (comma-separated)}
                            {--per-page=3 : Number of Unsplash results to fetch per keyword}';

    protected $description = 'Fetch relevant images from Unsplash API for UAC campus places';

    private const UNSPLASH_ENDPOINT = 'https://api.unsplash.com/search/photos';

    /**
     * Keyword-to-type mapping for place categories.
     * Each type gets a specific Unsplash search keyword and a pool of cached image URLs.
     * Priority order: most specific to most general.
     */
    private array $typeKeywords = [
        'résidence'      => 'student dormitory building',
        'restaurant'     => 'university cafeteria food court',
        'banque'         => 'bank branch atm',
        'station'        => 'gas station fuel pump',
        'jardin'         => 'botanical garden zoo',
        'ferme'          => 'agricultural farm field',
        'serre'          => 'greenhouse plants',
        'rectorat'       => 'university administration building facade',
        'imprimerie'     => 'printing press workshop',
        'institut'       => 'research institute building',
        'département'    => 'university department office',
        'amphi'          => 'university lecture hall',
        'bibliothèque'   => 'university library',
        'laboratoire'    => 'science laboratory',
        'administration' => 'university office building',
        'faculté'        => 'university faculty building campus',
        'zone_masters'   => 'university campus building modern',
        'default'        => 'university campus building',
    ];

    /**
     * Cache of fetched image URLs per keyword, to avoid redundant API calls.
     */
    private array $imageCache = [];

    /**
     * Track which image index to use next for each keyword (round-robin).
     */
    private array $imageIndex = [];

    public function handle(): int
    {
        $apiKey = config('services.unsplash.key');

        if (empty($apiKey)) {
            $this->error('UNSPLASH_ACCESS_KEY is not set in .env. Aborting.');
            $this->info('Get a free key at https://unsplash.com/developers');
            return self::FAILURE;
        }

        $commit = $this->option('commit');
        $limit = (int) $this->option('limit');
        $perPage = (int) $this->option('per-page');

        $this->info('=== Récupération d\'images Unsplash pour les lieux du campus UAC ===');
        $this->info($commit ? '🔴 MODE COMMIT — les images seront écrites en base.' : '🟢 MODE DRY-RUN — aucune écriture. Utilisez --commit pour appliquer.');
        $this->newLine();

        // Build query
        $query = Place::query()->orderBy('name');

        $names = $this->option('names');
        if (!empty($names)) {
            $nameArray = array_map('trim', explode(',', $names));
            $query->whereIn('name', $nameArray);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $places = $query->get();

        if ($places->isEmpty()) {
            $this->warn('Aucun lieu à traiter.');
            return self::SUCCESS;
        }

        $this->info("Lieux à traiter : {$places->count()}");
        $this->newLine();

        $updated = 0;
        $failed = 0;
        $cached = 0;

        foreach ($places as $place) {
            $placeType = $this->detectPlaceType($place);
            $keyword = $this->typeKeywords[$placeType] ?? $this->typeKeywords['default'];

            // Check if we already have images cached for this keyword
            if (!isset($this->imageCache[$placeType])) {
                // Fetch from Unsplash API
                $images = $this->fetchFromUnsplash($keyword, $apiKey, $perPage);
                if (empty($images)) {
                    $failed++;
                    $this->error("  ✗ [{$place->id}] {$place->name}");
                    $this->line("    Type détecté : {$placeType}");
                    $this->line("    Mot-clé : {$keyword}");
                    $this->line("    Aucune image trouvée sur Unsplash.");
                    $this->newLine();
                    continue;
                }
                $this->imageCache[$placeType] = $images;
                $this->imageIndex[$placeType] = 0;
            } else {
                $cached++;
            }

            // Round-robin: pick the next image in the pool
            $pool = $this->imageCache[$placeType];
            $idx = $this->imageIndex[$placeType] % count($pool);
            $selectedImage = $pool[$idx];
            $this->imageIndex[$placeType] = $idx + 1;

            $imageUrl = $selectedImage['url'];
            $photographer = $selectedImage['photographer'];

            $updated++;

            $this->info("  ✓ [{$place->id}] {$place->name}");
            $this->line("    Type détecté   : {$placeType}");
            $this->line("    Mot-clé        : {$keyword}");
            $this->line("    Image URL      : {$imageUrl}");
            $this->line("    Photographe    : {$photographer}");
            $currentImages = $place->images ?? [];
            $this->line("    Images actuelles : " . (empty($currentImages) ? '(aucune)' : count($currentImages) . ' image(s)'));
            $this->newLine();

            if ($commit) {
                $place->images = [$imageUrl];
                $place->save();
            }
        }

        $this->newLine();
        $this->info('=== Résumé ===');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Lieux traités', $places->count()],
                ['Images attribuées', $updated],
                ['Réutilisations cache', $cached],
                ['Échecs', $failed],
                ['Appels API Unsplash', count($this->imageCache)],
                ['Mode', $commit ? 'COMMIT' : 'DRY-RUN'],
            ]
        );

        if (!$commit && $updated > 0) {
            $this->newLine();
            $this->warn('💡 Relancez avec --commit pour appliquer les images en base.');
        }

        return self::SUCCESS;
    }

    /**
     * Detect the place type from its name and category.
     * Priority order: most specific to most general.
     */
    private function detectPlaceType(Place $place): string
    {
        $name = mb_strtolower($place->name);
        $category = mb_strtolower($place->category);

        // Priority 1: Most specific keywords
        if (preg_match('/résidence|residence|dortoir|dormitory|foyer/i', $name)) {
            return 'résidence';
        }
        if (preg_match('/restaurant|resteau/i', $name) || in_array($category, ['restaurant', 'cafe', 'fast_food'])) {
            return 'restaurant';
        }
        if (preg_match('/ecobank/i', $name) || in_array($category, ['bank', 'atm'])) {
            return 'banque';
        }
        if (preg_match('/station/i', $name) || $category === 'fuel') {
            return 'station';
        }
        if (preg_match('/jardin botanique|zoologique/i', $name)) {
            return 'jardin';
        }
        if (preg_match('/ferme/i', $name)) {
            return 'ferme';
        }
        if (preg_match('/serre/i', $name)) {
            return 'serre';
        }
        if (preg_match('/rectorat/i', $name)) {
            return 'rectorat';
        }
        if (preg_match('/imprimerie/i', $name)) {
            return 'imprimerie';
        }
        if (preg_match('/institut|école doctorale|centre de recherche|chaire|ifri|uva|cipma|confucius/i', $name)) {
            return 'institut';
        }
        if (preg_match('/département/i', $name)) {
            return 'département';
        }

        // Priority 2: Amphi (must come before Faculty to avoid "Amphi B EPAC" falling into Faculty)
        if (preg_match('/amphi/i', $name)) {
            return 'amphi';
        }

        // Priority 3: Library
        if (preg_match('/bibliothèque|bibliotheque/i', $name)) {
            return 'bibliothèque';
        }

        // Priority 4: Laboratory
        if (preg_match('/laboratoire|labo/i', $name)) {
            return 'laboratoire';
        }

        // Priority 5: Administration/Decanat (must come before Faculty)
        if (preg_match('/administration|décanat|decanat|vice rectorat/i', $name)) {
            return 'administration';
        }

        // Priority 6: Faculty/University
        if (preg_match('/faculté|faculty|université|university/i', $name) || $category === 'university') {
            return 'faculté';
        }

        // Priority 7: Zone Masters
        if (preg_match('/zone masters/i', $name)) {
            return 'zone_masters';
        }

        // Fallback: generic university building
        return 'default';
    }

    /**
     * Fetch images from Unsplash API with retry on rate limit.
     */
    private function fetchFromUnsplash(string $keyword, string $apiKey, int $perPage): array
    {
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt <= $maxRetries) {
            try {
                $response = Http::timeout(15)->withHeaders([
                    'Authorization' => 'Client-ID ' . $apiKey,
                    'Accept' => 'application/json',
                ])->get(self::UNSPLASH_ENDPOINT, [
                    'query' => $keyword,
                    'per_page' => $perPage,
                    'orientation' => 'landscape',
                ]);

                if (!$response->successful()) {
                    if ($response->status() === 403 || $response->status() === 429) {
                        $attempt++;
                        if ($attempt > $maxRetries) {
                            $this->warn("    ⚠ Échec définitif après {$maxRetries} tentatives (Rate Limit Unsplash).");
                            return [];
                        }

                        $waitTime = 60;
                        $retryAfter = $response->header('Retry-After');
                        if ($retryAfter) {
                            $waitTime = (int) $retryAfter;
                        }
                        $waitTime += 2;

                        $this->warn("    ⏳ Quota Unsplash atteint. Tentative {$attempt}/{$maxRetries}. Reprise dans {$waitTime}s...");
                        sleep($waitTime);
                        continue;
                    }

                    Log::warning('Unsplash API error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    $this->warn("    ⚠ HTTP {$response->status()} — {$response->body()}");
                    return [];
                }

                $data = $response->json();
                $results = $data['results'] ?? [];

                if (empty($results)) {
                    return [];
                }

                $images = [];
                foreach ($results as $photo) {
                    $images[] = [
                        'url' => $photo['urls']['regular'] ?? $photo['urls']['small'] ?? null,
                        'photographer' => $photo['user']['name'] ?? 'Unknown',
                    ];
                }

                return array_filter($images, fn($img) => $img['url'] !== null);

            } catch (\Exception $e) {
                Log::error('Unsplash API exception', [
                    'error' => $e->getMessage(),
                ]);
                $this->warn("    ⚠ Exception : {$e->getMessage()}");
                return [];
            }
        }

        return [];
    }
}
