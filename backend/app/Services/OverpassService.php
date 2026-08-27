<?php

namespace App\Services;

use App\DTO\OverpassPlace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OverpassService
{
    protected string $cachePath = 'overpass_cache.json';
    protected int $cacheTtl = 3600; // 1 hour in seconds

    /**
     * Fetch places from Overpass API for UAC campus area
     *
     * @param bool $refresh Force refresh cache
     * @return array<OverpassPlace>
     */
    public function fetchCampusPlaces(bool $refresh = false): array
    {
        // Try to load from cache first
        if (!$refresh && $this->isCacheValid()) {
            Log::info('Loading Overpass data from cache');
            return $this->loadFromCache();
        }

        // Fetch from API
        Log::info('Fetching Overpass data from API');
        $data = $this->fetchFromAPI();
        
        if ($data) {
            $this->saveToCache($data);
        }

        return $data;
    }

    /**
     * Check if cache is valid
     */
    protected function isCacheValid(): bool
    {
        if (!Storage::exists($this->cachePath)) {
            return false;
        }

        $cacheTime = Storage::lastModified($this->cachePath);
        return (time() - $cacheTime) < $this->cacheTtl;
    }

    /**
     * Load data from cache
     */
    protected function loadFromCache(): array
    {
        $content = Storage::get($this->cachePath);
        $data = json_decode($content, true);

        if (!$data || !isset($data['places'])) {
            return [];
        }

        return array_map(fn($place) => new OverpassPlace(
            osmId: $place['osm_id'],
            name: $place['name'],
            latitude: $place['latitude'],
            longitude: $place['longitude'],
            type: $place['type'],
            category: $place['category'],
            tags: $place['tags']
        ), $data['places']);
    }

    /**
     * Save data to cache
     */
    protected function saveToCache(array $places): void
    {
        $data = [
            'cached_at' => now()->toIso8601String(),
            'places' => array_map(fn($place) => $place->toArray(), $places)
        ];

        Storage::put($this->cachePath, json_encode($data, JSON_PRETTY_PRINT));
        Log::info('Overpass data cached', ['count' => count($places)]);
    }

    /**
     * Fetch data from Overpass API
     */
    protected function fetchFromAPI(): array
    {
        // Use the existing OverpassCacheService which works
        $cacheService = app(OverpassCacheService::class);
        
        // Use campus center coordinates
        $centerLat = 6.4145;
        $centerLng = 2.3410;
        $radius = 2000;
        
        $geoJsonData = $cacheService->getOSMPlaces($centerLat, $centerLng, $radius);
        
        if (!$geoJsonData) {
            return [];
        }
        
        // Convert GeoJSON to OverpassPlace objects
        $places = [];
        foreach ($geoJsonData as $feature) {
            if (!isset($feature['properties'])) {
                continue;
            }
            
            $props = $feature['properties'];
            $geometry = $feature['geometry'];
            
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
            
            $places[] = new OverpassPlace(
                osmId: $props['osmid'] ?? 0,
                name: $props['name'] ?? 'Unknown',
                latitude: $lat,
                longitude: $lon,
                type: $type,
                category: $props['amenity'] ?? $props['building'] ?? null,
                tags: $props
            );
        }
        
        return $places;
    }

    /**
     * Build Overpass query for UAC campus area
     */
    protected function buildCampusQuery(): string
    {
        // Use center point of campus with radius instead of bounding box
        // Center: lat=6.4145, lng=2.3410 (midpoint of the bounding box)
        $centerLat = 6.4145;
        $centerLng = 2.3410;
        $radius = 2000; // 2km radius to cover the campus area
        
        return "[out:json][timeout:60];(node[\"amenity\"~\"university|college|school|cafe|restaurant|library|hospital|pharmacy\"](around:{$radius},{$centerLat},{$centerLng});node[\"building\"~\"university|school|public|hospital\"](around:{$radius},{$centerLat},{$centerLng});way[\"amenity\"~\"university|college|school|cafe|restaurant|library|hospital|pharmacy\"](around:{$radius},{$centerLat},{$centerLng});way[\"building\"~\"university|school|public|hospital\"](around:{$radius},{$centerLat},{$centerLng});relation[\"amenity\"~\"university|college|school|cafe|restaurant|library|hospital|pharmacy\"](around:{$radius},{$centerLat},{$centerLng}););out center;";
    }

    /**
     * Parse Overpass response into OverpassPlace objects
     */
    protected function parseOverpassResponse(array $data): array
    {
        if (!isset($data['elements'])) {
            Log::warning('No elements in Overpass response');
            return [];
        }

        $places = [];
        foreach ($data['elements'] as $element) {
            // Don't skip places without names for now - include all amenities
            // if (!isset($element['tags']['name'])) {
            //     continue; // Skip places without names
            // }

            $places[] = OverpassPlace::fromOverpassElement($element);
        }

        Log::info('Parsed Overpass response', ['count' => count($places)]);
        return $places;
    }

    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        if (Storage::exists($this->cachePath)) {
            Storage::delete($this->cachePath);
            Log::info('Overpass cache cleared');
        }
    }
}
