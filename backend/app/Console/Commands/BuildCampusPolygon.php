<?php

namespace App\Console\Commands;

use App\Models\Place;
use App\Services\Geo\ConvexHull;
use App\Services\Geo\Haversine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BuildCampusPolygon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campus:build-polygon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build campus polygon from Overpass data using convex hull algorithm';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Construction du polygone du campus UAC ===');
        $this->newLine();

        // Parameters
        $maxDistanceFromCentroid = 2500; // meters
        $margin = 30; // meters

        // Campus-related keywords for filtering
        $keywords = ['UAC', 'Abomey-Calavi', 'EPAC', 'FAST', 'FSA', 'FASEG', 'FLLAC', 'FADESP', 'IFRI', 'BUAC', 'Faculté', 'Amphi', 'Institut', 'Rectorat', 'Résidence'];

        $this->info('Récupération des lieux depuis la base de données...');
        $this->info('Filtrage par mots-clés : ' . implode(', ', $keywords));
        $this->newLine();

        // Fetch places from database matching keywords
        $places = Place::where(function ($query) use ($keywords) {
            foreach ($keywords as $keyword) {
                $query->orWhere('name', 'like', '%' . $keyword . '%');
            }
        })->get();

        $this->info(count($places) . ' lieux correspondants trouvés');
        $this->newLine();

        // Convert to points
        $points = [];
        foreach ($places as $place) {
            $points[] = [
                $place->latitude,
                $place->longitude,
                'name' => $place->name,
            ];
        }

        $this->info(count($points) . ' points extraits');
        $this->newLine();

        if (count($points) < 3) {
            $this->error('Pas assez de points pour calculer un polygone (minimum 3 requis)');
            return self::FAILURE;
        }

        // Calculate centroid
        $centroid = ConvexHull::calculateCentroid($points);
        $this->info('Centroïde calculé : [' . number_format($centroid[0], 6) . ', ' . number_format($centroid[1], 6) . ']');
        $this->newLine();

        // Filter outliers
        $filteredPoints = [];
        $outliers = [];
        
        foreach ($points as $point) {
            $distance = Haversine::calculateInMeters(
                $centroid[0],
                $centroid[1],
                $point[0],
                $point[1]
            );

            if ($distance <= $maxDistanceFromCentroid) {
                $filteredPoints[] = $point;
            } else {
                $outliers[] = [
                    'name' => $point['name'] ?? 'Unknown',
                    'lat' => $point[0],
                    'lng' => $point[1],
                    'distance' => $distance,
                ];
            }
        }

        $this->info(count($filteredPoints) . ' points conservés (≤ ' . $maxDistanceFromCentroid . 'm du centroïde)');
        $this->info(count($outliers) . ' points exclus (outliers)');
        $this->newLine();

        // Show outliers
        if (count($outliers) > 0) {
            $this->warn('=== Points exclus (outliers) ===');
            $this->table(
                ['Nom', 'Latitude', 'Longitude', 'Distance (m)'],
                array_map(function ($outlier) {
                    return [
                        $outlier['name'],
                        number_format($outlier['lat'], 6),
                        number_format($outlier['lng'], 6),
                        number_format($outlier['distance'], 0),
                    ];
                }, $outliers)
            );
            $this->newLine();
        }

        // Calculate convex hull
        $this->info('Calcul de l\'enveloppe convexe (convex hull)...');
        $hull = ConvexHull::calculate($filteredPoints);
        $this->info('Convex hull : ' . count($hull) . ' points');
        $this->newLine();

        // Add margin
        $this->info('Ajout de marge de sécurité (' . $margin . 'm)...');
        $expandedHull = ConvexHull::addMargin($hull, $margin);
        $this->info('Polygone final : ' . count($expandedHull) . ' points');
        $this->newLine();

        // Save to config
        $this->info('Sauvegarde du polygone dans config/campus_polygon.php...');
        $this->savePolygonToConfig($expandedHull);
        $this->info('Polygone sauvegardé avec succès');
        $this->newLine();

        // Show polygon coordinates
        $this->info('=== Coordonnées du polygone ===');
        foreach ($expandedHull as $i => $point) {
            $this->line(sprintf(
                '%d. [%s, %s]',
                $i + 1,
                number_format($point[0], 6),
                number_format($point[1], 6)
            ));
        }

        return self::SUCCESS;
    }

    /**
     * Build Overpass query with regex for campus-related names
     */
    protected function buildOverpassQuery(float $south, float $west, float $north, float $east): string
    {
        // Campus-related keywords for regex
        $keywords = ['UAC', 'Abomey-Calavi', 'EPAC', 'FAST', 'FSA', 'FASEG', 'FLLAC', 'FADESP', 'IFRI', 'BUAC', 'Faculté', 'Amphi', 'Institut', 'Rectorat', 'Résidence'];
        $regex = implode('|', $keywords);

        return "[out:json][timeout:60];(
  node[\"amenity\"][\"name\"~\"{$regex}\", \"i\"]({$south},{$west},{$north},{$east});
  way[\"amenity\"][\"name\"~\"{$regex}\", \"i\"]({$south},{$west},{$north},{$east});
  node[\"building\"][\"name\"~\"{$regex}\", \"i\"]({$south},{$west},{$north},{$east});
  way[\"building\"][\"name\"~\"{$regex}\", \"i\"]({$south},{$west},{$north},{$east});
  node[\"office\"][\"name\"~\"{$regex}\", \"i\"]({$south},{$west},{$north},{$east});
  way[\"office\"][\"name\"~\"{$regex}\", \"i\"]({$south},{$west},{$north},{$east});
);
out center;";
    }

    /**
     * Fetch data from Overpass API using cURL
     */
    protected function fetchFromOverpass(string $query): ?array
    {
        $servers = [
            'https://overpass-api.de/api/interpreter',
            'https://overpass.kumi.systems/api/interpreter',
            'https://z.overpass-api.de/api/interpreter',
        ];

        foreach ($servers as $server) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $server);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['data' => $query]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 90);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json',
                ]);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $response) {
                    $data = json_decode($response, true);
                    if ($data) {
                        return $data;
                    }
                }

                $this->warn('Échec serveur ' . $server . ' : HTTP ' . $httpCode);
            } catch (\Exception $e) {
                $this->warn('Erreur serveur ' . $server . ' : ' . $e->getMessage());
            }
        }

        $this->error('Tous les serveurs Overpass ont échoué');
        return null;
    }

    /**
     * Parse points from Overpass data
     */
    protected function parsePoints(array $data): array
    {
        $points = [];

        foreach ($data['elements'] as $element) {
            $lat = null;
            $lng = null;
            $name = $element['tags']['name'] ?? null;

            if ($element['type'] === 'node') {
                $lat = $element['lat'];
                $lng = $element['lon'];
            } elseif ($element['type'] === 'way' && isset($element['center'])) {
                $lat = $element['center']['lat'];
                $lng = $element['center']['lon'];
            }

            if ($lat !== null && $lng !== null) {
                $points[] = [
                    $lat,
                    $lng,
                    'name' => $name,
                ];
            }
        }

        return $points;
    }

    /**
     * Save polygon to config file
     */
    protected function savePolygonToConfig(array $polygon): void
    {
        $configContent = "<?php\n\nreturn [\n";
        $configContent .= "    'polygon' => [\n";

        foreach ($polygon as $point) {
            $configContent .= sprintf(
                "        [%s, %s],\n",
                number_format($point[0], 6),
                number_format($point[1], 6)
            );
        }

        $configContent .= "    ],\n";
        $configContent .= "];\n";

        file_put_contents(config_path('campus_polygon.php'), $configContent);
    }
}
