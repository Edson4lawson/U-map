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

        foreach ($data['features'] as $feature) {
            Place::create([
                'uuid' => $feature['properties']['id'],
                'name' => $feature['properties']['name'],
                'type' => $feature['properties']['type'],
                'category' => $feature['properties']['category'] ?? 'Général',
                'description' => $feature['properties']['description'] ?? null,
                'opening_hours' => $feature['properties']['openingHours'] ?? null,
                'latitude' => $feature['geometry']['coordinates'][1],
                'longitude' => $feature['geometry']['coordinates'][0],
                'images' => $feature['properties']['images'] ?? [],
                'tags' => $feature['properties']['tags'] ?? [],
            ]);
        }
    }
}
