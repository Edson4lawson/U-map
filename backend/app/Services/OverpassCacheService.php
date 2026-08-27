<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OverpassCacheService
{
    protected $cachePrefix = 'overpass:';
    protected $cacheDuration = 3600; // 1 hour in seconds

    /**
     * Get OSM places with caching
     */
    public function getOSMPlaces($lat = 6.44100, $lng = 2.35200, $radius = 1000)
    {
        // Generate cache key based on parameters
        $cacheKey = $this->generateCacheKey($lat, $lng, $radius);
        
        // Try to get from cache first
        $cachedData = Cache::get($cacheKey);
        if ($cachedData !== null) {
            Log::info('Overpass data retrieved from cache', [
                'lat' => $lat,
                'lng' => $lng,
                'radius' => $radius,
                'cache_key' => $cacheKey
            ]);
            return $cachedData;
        }

        // If not in cache, fetch from Overpass API
        $data = $this->fetchFromOverpass($lat, $lng, $radius);
        
        if ($data) {
            // Store in cache
            Cache::put($cacheKey, $data, $this->cacheDuration);
            Log::info('Overpass data cached successfully', [
                'lat' => $lat,
                'lng' => $lng,
                'radius' => $radius,
                'cache_key' => $cacheKey,
                'duration' => $this->cacheDuration
            ]);
        }

        return $data;
    }

    /**
     * Generate cache key for Overpass query
     */
    protected function generateCacheKey($lat, $lng, $radius)
    {
        // Round coordinates to 4 decimal places for better cache hits
        $roundedLat = round($lat, 4);
        $roundedLng = round($lng, 4);
        $roundedRadius = round($radius, 0);
        
        return $this->cachePrefix . md5("{$roundedLat}_{$roundedLng}_{$roundedRadius}");
    }

    /**
     * Fetch data from Overpass API
     */
    protected function fetchFromOverpass($lat, $lng, $radius)
    {
        $query = $this->buildOverpassQuery($lat, $lng, $radius);
        
        // Try different Overpass API servers
        $servers = [
            'https://overpass-api.de/api/interpreter',
            'https://overpass.kumi.systems/api/interpreter',
            'https://z.overpass-api.de/api/interpreter',
        ];
        
        foreach ($servers as $server) {
            try {
                // Use curl directly to avoid Laravel Http issues
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $server);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded',
                    'User-Agent: U-Map Campus Importer/1.0',
                ]);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($error) {
                    Log::warning('Curl error for ' . $server, ['error' => $error]);
                    continue;
                }

                if ($httpCode === 200 && $response) {
                    $data = json_decode($response, true);
                    
                    // Convert to GeoJSON format
                    return $this->convertToGeoJSON($data);
                } else {
                    Log::warning('Overpass API request failed for ' . $server, [
                        'status' => $httpCode,
                        'body' => substr($response, 0, 200)
                    ]);
                    continue;
                }
            } catch (\Exception $e) {
                Log::warning('Exception fetching from ' . $server, [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }
        
        Log::error('All Overpass API servers failed');
        return null;
    }

    /**
     * Build Overpass query with campus polygon from config
     */
    protected function buildOverpassQuery($lat, $lng, $radius)
    {
        // Read polygon from config file
        $polygonCoords = config('campus_polygon.polygon', []);
        
        if (empty($polygonCoords)) {
            // Fallback to hardcoded coordinates if config is empty
            $polygonCoords = [
                [6.4230374, 2.3387855],
                [6.4229378, 2.338885],
                [6.4216166, 2.3424224],
                [6.4203826, 2.3467],
                [6.4206797, 2.3466299],
                [6.4124412, 2.3453902],
                [6.4087872, 2.3435561],
                [6.4087312, 2.3436309],
                [6.4099069, 2.3402295],
                [6.4109479, 2.339071],
                [6.411877, 2.3372926],
                [6.4118353, 2.3372709],
                [6.4231707, 2.3386968],
            ];
        }
        
        // Convert to Overpass polygon format (lat lng lat lng...)
        $polygon = implode(' ', array_map(function ($coord) {
            return $coord[0] . ' ' . $coord[1];
        }, $polygonCoords));
        
        return "[out:json][timeout:60];(
  node[\"amenity\"][\"name\"](poly:\"{$polygon}\");
  way[\"amenity\"][\"name\"](poly:\"{$polygon}\");
  node[\"building\"][\"name\"](poly:\"{$polygon}\");
  way[\"building\"][\"name\"](poly:\"{$polygon}\");
  node[\"office\"][\"name\"](poly:\"{$polygon}\");
  way[\"office\"][\"name\"](poly:\"{$polygon}\");
  node[\"leisure\"][\"name\"](poly:\"{$polygon}\");
  way[\"leisure\"][\"name\"](poly:\"{$polygon}\");
  node[\"healthcare\"][\"name\"](poly:\"{$polygon}\");
  node[\"shop\"][\"name\"](poly:\"{$polygon}\");
  node[\"amenity\"=\"parking\"](poly:\"{$polygon}\");
  node[\"leisure\"=\"pitch\"](poly:\"{$polygon}\");
  way[\"leisure\"=\"pitch\"](poly:\"{$polygon}\");
  node[\"tourism\"=\"zoo\"](poly:\"{$polygon}\");
  way[\"tourism\"=\"zoo\"](poly:\"{$polygon}\");
  node[\"landuse\"=\"farmland\"][\"name\"~\"Ferme\", i](poly:\"{$polygon}\");
  way[\"landuse\"=\"farmland\"][\"name\"~\"Ferme\", i](poly:\"{$polygon}\");
  node[\"amenity\"=\"fuel\"](poly:\"{$polygon}\");
  way[\"amenity\"=\"fuel\"](poly:\"{$polygon}\");
  node[\"amenity\"=\"bank\"][\"name\"~\"Ecobank\", i](poly:\"{$polygon}\");
  way[\"amenity\"=\"bank\"][\"name\"~\"Ecobank\", i](poly:\"{$polygon}\");
);
out center tags;";
    }

    /**
     * Convert Overpass data to GeoJSON format
     */
    protected function convertToGeoJSON($data)
    {
        if (!isset($data['elements'])) {
            return [];
        }

        $features = [];

        foreach ($data['elements'] as $element) {
            $feature = [
                'type' => 'Feature',
                'properties' => [
                    'osmid' => $element['id'],
                    'name' => $element['tags']['name'] ?? null,
                    'amenity' => $element['tags']['amenity'] ?? null,
                    'building' => $element['tags']['building'] ?? null,
                    'type' => $this->categorizePlace($element['tags']),
                ],
                'geometry' => null
            ];

            // Handle geometry based on element type
            if ($element['type'] === 'node') {
                $feature['geometry'] = [
                    'type' => 'Point',
                    'coordinates' => [$element['lon'], $element['lat']]
                ];
            } elseif ($element['type'] === 'way' || $element['type'] === 'relation') {
                if (isset($element['center'])) {
                    $feature['geometry'] = [
                        'type' => 'Point',
                        'coordinates' => [$element['center']['lon'], $element['center']['lat']]
                    ];
                }
            }

            if ($feature['geometry']) {
                $features[] = $feature;
            }
        }

        return $features;
    }

    /**
     * Categorize place based on tags
     */
    protected function categorizePlace($tags)
    {
        $amenity = $tags['amenity'] ?? '';
        $building = $tags['building'] ?? '';

        if (in_array($amenity, ['university', 'college', 'school']) || 
            in_array($building, ['university', 'school'])) {
            return 'academic';
        }

        if (in_array($amenity, ['cafe', 'restaurant', 'fast_food'])) {
            return 'food';
        }

        if (in_array($amenity, ['library'])) {
            return 'library';
        }

        if (in_array($amenity, ['hospital', 'pharmacy', 'clinic'])) {
            return 'health';
        }

        return 'other';
    }

    /**
     * Clear cache for a specific area
     */
    public function clearCache($lat, $lng, $radius)
    {
        $cacheKey = $this->generateCacheKey($lat, $lng, $radius);
        Cache::forget($cacheKey);
        
        Log::info('Overpass cache cleared', [
            'cache_key' => $cacheKey
        ]);
    }

    /**
     * Clear all Overpass cache
     */
    public function clearAllCache()
    {
        // This would need a more sophisticated implementation in production
        // For now, we'll just log it
        Log::info('All Overpass cache cleared requested');
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats()
    {
        // This would need Redis or similar for accurate stats
        return [
            'prefix' => $this->cachePrefix,
            'duration' => $this->cacheDuration,
            'status' => 'active'
        ];
    }
}
