<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Place;
use Illuminate\Support\Facades\File;

class PlaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = File::get(database_path('data/campus.json'));
        $data = json_decode($json, true);

        // Delete all existing approved places to ensure clean sync
        Place::where('status', 'approved')->delete();

        foreach ($data['features'] as $feature) {
            $name = $feature['properties']['name'];
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');

            Place::create([
                'uuid' => (string) $feature['properties']['id'],
                'slug' => $slug,
                'name' => $name,
                'type' => $feature['properties']['type'],
                'category' => $feature['properties']['category'] ?? 'Général',
                'description' => $feature['properties']['description'] ?? null,
                'opening_hours' => $feature['properties']['openingHours'] ?? null,
                'latitude' => $feature['geometry']['coordinates'][1],
                'longitude' => $feature['geometry']['coordinates'][0],
                'images' => $feature['properties']['images'] ?? [],
                'tags' => $feature['properties']['tags'] ?? [],
                'status' => 'approved',
            ]);
        }
    }
}
