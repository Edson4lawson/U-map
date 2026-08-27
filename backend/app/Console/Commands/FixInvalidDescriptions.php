<?php

namespace App\Console\Commands;

use App\Models\Place;
use Illuminate\Console\Command;

class FixInvalidDescriptions extends Command
{
    protected $signature = 'campus:fix-invalid-descriptions
                            {--commit : Apply changes to database (dry-run by default)}';

    protected $description = 'Fix invalid descriptions (yes, Bank, Office, etc.) with template-based descriptions';

    public function handle(): int
    {
        $commit = $this->option('commit');

        $this->info('=== Correction des descriptions invalides (sans API Groq) ===');
        $this->info($commit ? '🔴 MODE COMMIT — les descriptions seront écrites en base.' : '🟢 MODE DRY-RUN — aucune écriture. Utilisez --commit pour appliquer.');
        $this->newLine();

        // Find places with invalid descriptions
        $invalidPatterns = [' - Yes', 'Bank', 'Office', 'Library', 'Restaurant', 'Fuel', 'Atm', 'Fast_food', 'Cafe'];
        
        $query = Place::query();
        
        foreach ($invalidPatterns as $pattern) {
            $query->orWhere('description', 'like', '%' . $pattern . '%');
        }

        $places = $query->get();

        if ($places->isEmpty()) {
            $this->warn('Aucune description invalide trouvée.');
            return self::SUCCESS;
        }

        $this->info("Lieux avec descriptions invalides : {$places->count()}");
        $this->newLine();

        $fixed = 0;

        foreach ($places as $place) {
            $newDescription = $this->generateTemplateDescription($place);
            
            if ($newDescription === $place->description) {
                continue;
            }

            $this->info("  ✓ [{$place->id}] {$place->name}");
            $this->line("    Ancienne : {$place->description}");
            $this->line("    Nouvelle : {$newDescription}");
            $this->newLine();

            if ($commit) {
                $place->description = $newDescription;
                $place->save();
            }

            $fixed++;
        }

        $this->newLine();
        $this->info('=== Résumé ===');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Lieux traités', $places->count()],
                ['Descriptions corrigées', $fixed],
                ['Mode', $commit ? 'COMMIT' : 'DRY-RUN'],
            ]
        );

        if (!$commit && $fixed > 0) {
            $this->newLine();
            $this->warn('💡 Relancez avec --commit pour appliquer les corrections en base.');
        }

        return self::SUCCESS;
    }

    private function generateTemplateDescription(Place $place): string
    {
        $name = $place->name;
        $category = $place->category;

        // Extract capacity from amphi names
        $capacity = '';
        if (preg_match('/(?:Amphi|Amphithéâtre).*?(\d+)/i', $name, $matches)) {
            $capacity = " avec une capacité d'environ {$matches[1]} places";
        }

        // Template-based descriptions by type
        if (preg_match('/amphi|amphithéâtre/i', $name)) {
            return "Espace dédié aux cours magistraux et conférences{$capacity} pour les enseignements universitaires.";
        }

        if (preg_match('/résidence|residence|dortoir|foyer/i', $name)) {
            return "Résidence universitaire hébergeant les étudiants de l'Université d'Abomey-Calavi.";
        }

        if (preg_match('/restaurant|resteau|cafétéria|cantine/i', $name) || in_array($category, ['restaurant', 'cafe', 'fast_food'])) {
            return "Espace de restauration servant des repas variés aux étudiants et personnel de l'université.";
        }

        if (preg_match('/ecobank|bank|banque/i', $name) || in_array($category, ['bank', 'atm'])) {
            return "Agence bancaire offrant des services financiers à la communauté universitaire de l'UAC.";
        }

        if (preg_match('/station|fuel|essence/i', $name) || $category === 'fuel') {
            return "Point de ravitaillement en carburant pour les véhicules de la communauté universitaire.";
        }

        if (preg_match('/jardin|botanique|zoologique/i', $name)) {
            return "Espace vert de conservation de la biodiversité pour la recherche et l'éducation environnementale.";
        }

        if (preg_match('/ferme|agricole/i', $name)) {
            return "Exploitation agricole pour la formation pratique et la recherche en sciences agronomiques.";
        }

        if (preg_match('/serre|greenhouse/i', $name)) {
            return "Espace de culture sous abri pour les expériences botaniques et la recherche en biologie.";
        }

        if (preg_match('/rectorat/i', $name)) {
            return "Bâtiment administratif regroupant les services de direction de l'Université d'Abomey-Calavi.";
        }

        if (preg_match('/imprimerie|printing/i', $name)) {
            return "Atelier d'impression et de reprographie fournissant des services documentaires à l'université.";
        }

        if (preg_match('/institut|école doctorale|centre de recherche|chaire|ifri|uva|cipma|confucius/i', $name)) {
            return "Centre de recherche et de formation spécialisé dans les domaines académiques de l'UAC.";
        }

        if (preg_match('/département|department/i', $name)) {
            return "Unité d'enseignement et de recherche regroupant les activités académiques d'une discipline.";
        }

        if (preg_match('/bibliothèque|bibliotheque|documentation/i', $name)) {
            return "Espace de consultation et de mise à disposition de ressources documentaires pour les étudiants.";
        }

        if (preg_match('/laboratoire|labo/i', $name)) {
            return "Équipé pour les expériences scientifiques et travaux pratiques de recherche universitaire.";
        }

        if (preg_match('/administration|décanat|decanat|vice rectorat/i', $name)) {
            return "Regroupe les services administratifs et de gestion pour la communauté universitaire.";
        }

        if (preg_match('/faculté|faculty|université|university/i', $name) || $category === 'university') {
            return "Bâtiment d'enseignement supérieur accueillant les formations académiques de l'UAC.";
        }

        if (preg_match('/bâtiment|batiment/i', $name)) {
            return "Bâtiment universitaire accueillant les activités d'enseignement et de recherche.";
        }

        if (preg_match('/salle/i', $name)) {
            return "Espace dédié aux activités académiques et réunions de la communauté universitaire.";
        }

        // Generic fallback
        return "Espace universitaire dédié aux activités d'enseignement, de recherche et de vie étudiante.";
    }
}
