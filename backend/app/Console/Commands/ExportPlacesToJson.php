<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Place;
use Illuminate\Support\Facades\File;

#[Signature('app:export-places-to-json')]
#[Description('Export approved places from database to campus.json')]
class ExportPlacesToJson extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Exporting approved places to campus.json...');

        $places = Place::where('status', 'approved')->get();

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
                    'images' => $place->images ?? [],
                    'tags' => $place->tags ?? [],
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $place->longitude, (float) $place->latitude],
                ],
            ];
        });

        $geoJson = [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];

        $jsonPath = database_path('data/campus.json');
        File::put($jsonPath, json_encode($geoJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Exported {$places->count()} places to {$jsonPath}");
        return Command::SUCCESS;
    }
}
