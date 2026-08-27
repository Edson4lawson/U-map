<?php

namespace App\Console\Commands;

use App\Models\Place;
use Illuminate\Console\Command;

class CheckIndoorPlanPlaces extends Command
{
    protected $signature = 'campus:check-indoor-plan';

    protected $description = 'Check which places have indoor plan enabled';

    public function handle(): int
    {
        $this->info('=== Lieux avec Plan d\'Intérieur Interactif ===');
        $this->newLine();

        $places = Place::all();
        $indoorPlanPlaces = [];

        foreach ($places as $place) {
            $hasIndoorPlan = 
                strtolower($place->name) === 'bibliothèque' ||
                str_contains(strtolower($place->name), 'bibliothèque') ||
                str_contains(strtolower($place->name), 'bu') ||
                $place->id === 'bu_centrale' ||
                $place->id === '1' ||
                str_contains(strtolower($place->category), 'bibliothèque');

            if ($hasIndoorPlan) {
                $indoorPlanPlaces[] = [
                    'id' => $place->id,
                    'name' => $place->name,
                    'category' => $place->category,
                ];
            }
        }

        if (empty($indoorPlanPlaces)) {
            $this->info('Aucun lieu avec plan d\'intérieur détecté.');
            return self::SUCCESS;
        }

        foreach ($indoorPlanPlaces as $place) {
            $this->line("ID: {$place['id']} | Nom: {$place['name']} | Catégorie: {$place['category']}");
        }

        $this->newLine();
        $this->info('Total: ' . count($indoorPlanPlaces) . ' lieux');

        return self::SUCCESS;
    }
}
