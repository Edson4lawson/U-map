<?php

namespace App\Console\Commands;

use App\Models\Place;
use Illuminate\Console\Command;

class ExportPlacesList extends Command
{
    protected $signature = 'campus:export-places
                            {--output= : Output file path (default: places_list.txt)}';

    protected $description = 'Export complete list of places to file';

    public function handle(): int
    {
        $outputFile = $this->option('output') ?: 'places_list.txt';
        
        $places = Place::orderBy('name')->get();
        
        $content = "ID | Nom | Catégorie | Description\n";
        $content .= str_repeat("-", 150) . "\n";
        
        foreach ($places as $place) {
            $content .= "{$place->id} | {$place->name} | {$place->category} | {$place->description}\n";
        }
        
        $content .= "\nTotal: {$places->count()} lieux\n";
        
        file_put_contents($outputFile, $content);
        
        $this->info("Liste exportée vers: {$outputFile}");
        $this->info("Total: {$places->count()} lieux");

        return self::SUCCESS;
    }
}
