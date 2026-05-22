<?php

namespace App\Http\Controllers;

use App\Models\Place;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    /**
     * Retourne tous les lieux au format GeoJSON attendu par le frontend.
     */
    public function index()
    {
        $user = auth('sanctum')->user();
        
        // On affiche les lieux approuvés OU les lieux en attente créés par l'utilisateur actuel
        $places = Place::where('status', 'approved')
            ->when($user, function($query) use ($user) {
                return $query->orWhere(function($q) use ($user) {
                    $q->where('status', 'pending')->where('added_by', $user->name);
                });
            })
            ->get();
        
        $features = $places->map(function ($place) {
            return [
                'type' => 'Feature',
                'properties' => [
                    'id' => $place->uuid,
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
                        (float)$place->longitude,
                        (float)$place->latitude,
                    ]
                ]
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features
        ]);
    }

    /**
     * Enregistre un nouveau lieu ajouté par un utilisateur.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = auth()->user();

        $place = Place::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
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
        $query = $request->query('q');
        $places = Place::where('name', 'like', "%$query%")
                      ->orWhere('type', 'like', "%$query%")
                      ->get();
        
        return response()->json($places);
    }
}
