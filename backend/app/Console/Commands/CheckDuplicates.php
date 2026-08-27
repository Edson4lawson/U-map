<?php

namespace App\Console\Commands;

use App\Models\Place;
use Illuminate\Console\Command;

class CheckDuplicates extends Command
{
    protected $signature = 'campus:check-duplicates';

    protected $description = 'Check for potential duplicate places by GPS coordinates';

    public function handle(): int
    {
        $this->info('=== Vérification des doublons potentiels ===');
        $this->newLine();

        // Paire 1: Labo FAST (ID 316) vs Laboratoire FAST (ID 272)
        $this->info('Paire 1: Labo FAST (ID 316) vs Laboratoire FAST (ID 272)');
        $p1 = Place::find(316);
        $p2 = Place::find(272);
        
        if ($p1 && $p2) {
            $this->line("  Labo FAST: {$p1->name} | Lat: {$p1->latitude} | Lng: {$p1->longitude}");
            $this->line("  Laboratoire FAST: {$p2->name} | Lat: {$p2->latitude} | Lng: {$p2->longitude}");
            $dist1 = $this->calculateDistance($p1->latitude, $p1->longitude, $p2->latitude, $p2->longitude);
            $this->line("  Distance: " . round($dist1, 2) . " mètres");
            
            if ($dist1 < 20) {
                $this->warn("  ⚠️  DOUBLON DÉTECTÉ (distance < 20m)");
            } else {
                $this->info("  ✓ Lieux distincts");
            }
        } else {
            $this->warn("  ⚠️  Un des lieux n'existe pas");
        }

        $this->newLine();

        // Paire 2: Laboratoire d'Hydraulique Appliquée (ID 277) vs Laboratoire de l'Hydraulique Appliquée (ID 279)
        $this->info('Paire 2: Laboratoire d\'Hydraulique Appliquée (ID 277) vs Laboratoire de l\'Hydraulique Appliquée (ID 279)');
        $p3 = Place::find(277);
        $p4 = Place::find(279);
        
        if ($p3 && $p4) {
            $this->line("  Laboratoire d'Hydraulique Appliquée: {$p3->name} | Lat: {$p3->latitude} | Lng: {$p3->longitude}");
            $this->line("  Laboratoire de l'Hydraulique Appliquée: {$p4->name} | Lat: {$p4->latitude} | Lng: {$p4->longitude}");
            $dist2 = $this->calculateDistance($p3->latitude, $p3->longitude, $p4->latitude, $p4->longitude);
            $this->line("  Distance: " . round($dist2, 2) . " mètres");
            
            if ($dist2 < 20) {
                $this->warn("  ⚠️  DOUBLON DÉTECTÉ (distance < 20m)");
            } else {
                $this->info("  ✓ Lieux distincts");
            }
        } else {
            $this->warn("  ⚠️  Un des lieux n'existe pas");
        }

        return self::SUCCESS;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371e3; // Rayon de la Terre en mètres
        $φ1 = $lat1 * M_PI / 180;
        $φ2 = $lat2 * M_PI / 180;
        $Δφ = ($lat2 - $lat1) * M_PI / 180;
        $Δλ = ($lon2 - $lon1) * M_PI / 180;
        
        $a = sin($Δφ / 2) * sin($Δφ / 2) + cos($φ1) * cos($φ2) * sin($Δλ / 2) * sin($Δλ / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $R * $c;
    }
}
