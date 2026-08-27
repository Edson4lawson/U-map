<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlaceRequest;
use App\Models\Place;
use App\Services\OverpassCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PlaceController extends Controller
{
    protected OverpassCacheService $overpassService;

    public function __construct(OverpassCacheService $overpassService)
    {
        $this->overpassService = $overpassService;
    }

    /**
     * Retourne tous les lieux au format GeoJSON attendu par le frontend.
     */
    public function index()
    {
        $user = auth('sanctum')->user();

        $places = Place::where('status', 'approved')
            ->when($user, function ($query) use ($user) {
                return $query->orWhere(function ($q) use ($user) {
                    $q->where('status', 'pending')->where('added_by', $user->name);
                });
            })
            ->get();

        $features = $places->map(function ($place) {
            return [
                'type' => 'Feature',
                'properties' => [
                    'id' => $place->uuid,
                    'slug' => $place->slug,
                    'name' => $place->name,
                    'type' => $place->type,
                    'category' => $place->category,
                    'description' => $place->description,
                    'openingHours' => $place->opening_hours,
                    'images' => $place->images,
                    'tags' => $place->tags,
                    'added_by' => $place->added_by,
                    'status' => $place->status,   // ← exposé pour colorer les pins "pending"
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float) $place->longitude,
                        (float) $place->latitude,
                    ],
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    /**
     * Enregistre un nouveau lieu ajouté par un utilisateur.
     */
    public function store(StorePlaceRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();

        // Generate slug from name
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $validated['name'])));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        if (empty($slug)) {
            $slug = 'place-' . Str::uuid();
        }
        
        // Check if slug already exists and append UUID if needed
        $existing = Place::where('slug', $slug)->first();
        if ($existing) {
            $slug = $slug . '-' . Str::uuid();
        }

        $place = Place::create([
            'uuid' => (string) Str::uuid(),
            'slug' => $slug,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'category' => $validated['category'] ?? 'Divers',
            'description' => $validated['description'] ?? '',
            'opening_hours' => 'Non renseigné',
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'added_by' => $user ? $user->name : 'Anonyme',
            'status' => 'pending',
            'images' => json_encode([]),
            'tags' => json_encode([]),
        ]);

        return response()->json($place, 201);
    }

    /**
     * Recherche de lieux.
     */
    public function search(Request $request)
    {
        $query = $request->query('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $places = Place::where('status', 'approved')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('type', 'like', "%{$query}%")
                    ->orWhere('category', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

        return response()->json($places);
    }

    /**
     * Get OSM places with caching
     */
    public function getOSMPlaces(Request $request)
    {
        $lat = $request->query('lat', 6.44100);
        $lng = $request->query('lng', 2.35200);
        $radius = $request->query('radius', 1000);

        $osmPlaces = $this->overpassService->getOSMPlaces($lat, $lng, $radius);

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $osmPlaces,
        ]);
    }

    /**
     * Get a place by ID or slug
     */
    public function show($identifier)
    {
        // Check if identifier is a valid UUID format
        $isUuid = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $identifier);

        if ($isUuid) {
            $place = Place::where('uuid', $identifier)->first();
        } else {
            $place = Place::where('slug', $identifier)->first();
        }

        if (!$place) {
            return response()->json(['message' => 'Place not found'], 404);
        }

        return response()->json([
            'type' => 'Feature',
            'properties' => [
                'id' => $place->uuid,
                'slug' => $place->slug,
                'name' => $place->name,
                'type' => $place->type,
                'category' => $place->category,
                'description' => $place->description,
                'openingHours' => $place->opening_hours,
                'images' => $place->images,
                'tags' => $place->tags,
                'added_by' => $place->added_by,
            ],
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [
                    (float) $place->longitude,
                    (float) $place->latitude,
                ],
            ],
        ]);
    }

    /**
     * Clear OSM cache for a specific area
     */
    public function clearOSMCache(Request $request)
    {
        $lat = $request->query('lat', 6.44100);
        $lng = $request->query('lng', 2.35200);
        $radius = $request->query('radius', 1000);

        $this->overpassService->clearCache($lat, $lng, $radius);

        return response()->json([
            'message' => 'Cache cleared successfully'
        ]);
    }
}
