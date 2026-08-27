<?php

namespace App\Services;

use App\DTO\OverpassPlace;
use App\Models\Place;
use App\Services\Geo\Haversine;
use App\Services\Geo\PointInPolygon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CampusImporter
{
    protected int $similarityThreshold = 80; // Levenshtein similarity threshold (0-100)
    protected int $distanceThreshold = 100; // Distance threshold in meters

    /**
     * Compare Overpass places with existing database places
     *
     * @param array<OverpassPlace> $overpassPlaces
     * @param bool $strictMode If true, delete all places not in Overpass results
     * @return array{to_create: array<OverpassPlace>, to_update: array{place: Place, overpass: OverpassPlace}, to_delete: array<Place>}
     */
    public function compare(array $overpassPlaces, bool $strictMode = false): array
    {
        $existingPlaces = Place::all();
        $result = [
            'to_create' => [],
            'to_update' => [],
            'to_delete' => [],
        ];

        // Track which existing places are matched
        $matchedPlaceIds = [];

        foreach ($overpassPlaces as $overpassPlace) {
            $match = $this->findMatchingPlace($overpassPlace, $existingPlaces);

            if ($match === null) {
                $result['to_create'][] = $overpassPlace;
            } else {
                $result['to_update'][] = [
                    'place' => $match,
                    'overpass' => $overpassPlace,
                ];
                $matchedPlaceIds[] = $match->id;
            }
        }

        // Identify places to delete
        foreach ($existingPlaces as $existingPlace) {
            if ($strictMode) {
                // In strict mode: delete all places not matched to Overpass
                if (!in_array($existingPlace->id, $matchedPlaceIds)) {
                    $result['to_delete'][] = $existingPlace;
                }
            } else {
                // In normal mode: only delete places outside campus polygon
                if (!PointInPolygon::isPointInCampus($existingPlace->latitude, $existingPlace->longitude)) {
                    $result['to_delete'][] = $existingPlace;
                }
            }
        }

        Log::info('Campus import comparison completed', [
            'to_create' => count($result['to_create']),
            'to_update' => count($result['to_update']),
            'to_delete' => count($result['to_delete']),
            'strict_mode' => $strictMode,
        ]);

        return $result;
    }

    /**
     * Find matching place in existing places
     *
     * @param OverpassPlace $overpassPlace
     * @param \Illuminate\Database\Eloquent\Collection $existingPlaces
     * @return Place|null
     */
    protected function findMatchingPlace(OverpassPlace $overpassPlace, $existingPlaces): ?Place
    {
        $overpassNormalizedName = $overpassPlace->getNormalizedName();

        // Try exact match first
        $exactMatch = $existingPlaces->first(function ($place) use ($overpassNormalizedName) {
            return $this->normalizeName($place->name) === $overpassNormalizedName;
        });

        if ($exactMatch) {
            return $exactMatch;
        }

        // Try fuzzy match with Levenshtein
        $bestMatch = null;
        $bestSimilarity = 0;

        foreach ($existingPlaces as $place) {
            $placeNormalizedName = $this->normalizeName($place->name);
            $similarity = $this->calculateSimilarity($overpassNormalizedName, $placeNormalizedName);

            if ($similarity > $bestSimilarity && $similarity >= $this->similarityThreshold) {
                $bestSimilarity = $similarity;
                $bestMatch = $place;
            }
        }

        // If fuzzy match found, verify it's within distance threshold
        if ($bestMatch) {
            $distance = Haversine::calculateInMeters(
                $overpassPlace->latitude,
                $overpassPlace->longitude,
                $bestMatch->latitude,
                $bestMatch->longitude
            );

            if ($distance <= $this->distanceThreshold) {
                return $bestMatch;
            }
        }

        return null;
    }

    /**
     * Normalize place name for comparison
     */
    protected function normalizeName(string $name): string
    {
        $normalized = mb_strtolower($name);
        $normalized = trim($normalized);
        $normalized = $this->removeAccents($normalized);
        return $normalized;
    }

    /**
     * Remove accents from string
     */
    protected function removeAccents(string $string): string
    {
        $accents = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ];

        return strtr($string, $accents);
    }

    /**
     * Calculate similarity between two strings using Levenshtein
     * Returns percentage (0-100)
     */
    protected function calculateSimilarity(string $str1, string $str2): int
    {
        if ($str1 === $str2) {
            return 100;
        }

        $len1 = mb_strlen($str1);
        $len2 = mb_strlen($str2);

        if ($len1 === 0 || $len2 === 0) {
            return 0;
        }

        $distance = levenshtein($str1, $str2);
        $maxLen = max($len1, $len2);

        return (int) ((1 - $distance / $maxLen) * 100);
    }

    /**
     * Create a new place from Overpass data
     *
     * @param OverpassPlace $overpassPlace
     * @return Place
     */
    public function createPlace(OverpassPlace $overpassPlace): Place
    {
        return Place::create([
            'uuid' => (string) $overpassPlace->osmId,
            'name' => $overpassPlace->name,
            'type' => $overpassPlace->type,
            'category' => $overpassPlace->category,
            'description' => $this->generateDescription($overpassPlace),
            'latitude' => $overpassPlace->latitude,
            'longitude' => $overpassPlace->longitude,
            'tags' => $overpassPlace->tags,
            'status' => 'approved',
        ]);
    }

    /**
     * Update an existing place with Overpass data
     *
     * @param Place $place
     * @param OverpassPlace $overpassPlace
     * @return Place
     */
    public function updatePlace(Place $place, OverpassPlace $overpassPlace): Place
    {
        $place->update([
            'latitude' => $overpassPlace->latitude,
            'longitude' => $overpassPlace->longitude,
            'type' => $overpassPlace->type,
            'category' => $overpassPlace->category,
        ]);

        return $place;
    }

    /**
     * Generate description from Overpass tags
     */
    protected function generateDescription(OverpassPlace $overpassPlace): string
    {
        $tags = $overpassPlace->tags;
        $description = '';

        if (isset($tags['amenity'])) {
            $description .= ucfirst($tags['amenity']);
        }

        if (isset($tags['building'])) {
            $description .= ' - ' . ucfirst($tags['building']);
        }

        if (isset($tags['office'])) {
            $description .= ' - ' . ucfirst($tags['office']);
        }

        return $description ?: 'Lieu importé depuis OpenStreetMap';
    }

    /**
     * Set similarity threshold
     */
    public function setSimilarityThreshold(int $threshold): void
    {
        $this->similarityThreshold = $threshold;
    }

    /**
     * Set distance threshold in meters
     */
    public function setDistanceThreshold(int $meters): void
    {
        $this->distanceThreshold = $meters;
    }

    /**
     * Backup all existing places to JSON file
     *
     * @return string Path to backup file
     */
    public function backupPlaces(): string
    {
        $places = Place::all();
        $backupData = [
            'backup_date' => now()->toIso8601String(),
            'total_places' => $places->count(),
            'places' => $places->map(function ($place) {
                return [
                    'id' => $place->id,
                    'uuid' => $place->uuid,
                    'name' => $place->name,
                    'type' => $place->type,
                    'category' => $place->category,
                    'description' => $place->description,
                    'latitude' => $place->latitude,
                    'longitude' => $place->longitude,
                    'images' => $place->images,
                    'tags' => $place->tags,
                    'opening_hours' => $place->opening_hours,
                    'added_by' => $place->added_by,
                    'status' => $place->status,
                    'created_at' => $place->created_at,
                    'updated_at' => $place->updated_at,
                ];
            })->toArray(),
        ];

        $backupPath = 'backups/marqueurs_backup_' . now()->format('Y-m-d_His') . '.json';
        
        // Ensure backup directory exists
        if (!Storage::disk('local')->exists('backups')) {
            Storage::disk('local')->makeDirectory('backups');
        }

        Storage::disk('local')->put($backupPath, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        Log::info('Places backup created', ['path' => $backupPath, 'count' => $places->count()]);

        return Storage::disk('local')->path($backupPath);
    }

    /**
     * Delete a place
     *
     * @param Place $place
     * @return bool
     */
    public function deletePlace(Place $place): bool
    {
        return $place->delete();
    }
}
