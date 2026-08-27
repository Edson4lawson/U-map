<?php

namespace App\Console\Commands;

use App\Models\Place;
use Illuminate\Console\Command;

class CheckInvalidCategories extends Command
{
    protected $signature = 'campus:check-invalid-categories';

    protected $description = 'Check places with invalid categories (yes, other, etc.)';

    public function handle(): int
    {
        $this->info('=== Lieux avec catégories invalides ===');
        $this->newLine();

        $invalidCategories = ['yes', 'other', '', 'N/A', 'University'];
        
        $places = Place::whereIn('category', $invalidCategories)
            ->orWhereNull('category')
            ->get();

        if ($places->isEmpty()) {
            $this->info('✅ Aucun lieu avec catégorie invalide trouvée.');
            return self::SUCCESS;
        }

        $this->warn("Lieux avec catégories invalides : {$places->count()}");
        $this->newLine();

        foreach ($places as $place) {
            $this->line("ID: {$place->id} | Nom: {$place->name} | Catégorie: '{$place->category}'");
        }

        $this->newLine();
        $this->info('Total: ' . $places->count() . ' lieux à corriger');

        return self::SUCCESS;
    }
}
