<?php

namespace App\Console\Commands;

use App\Models\Place;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeneratePlaceDescriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campus:generate-descriptions
                            {--commit : Apply changes to database (dry-run by default)}
                            {--limit=0 : Limit number of places to process (0 = all)}
                            {--only-empty : Only generate for places with empty descriptions}
                            {--names= : Noms spécifiques de lieux à traiter (séparés par des virgules)}
                            {--delay=2000 : Delay in ms between API calls}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate short place descriptions for UAC campus via Groq API';

    /**
     * Groq API endpoint template.
     */
    private const GROQ_ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    /**
     * List models endpoint.
     */
    private const GEMINI_MODELS_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $apiKey = config('services.groq.key');

        if (empty($apiKey)) {
            $this->error('GROQ_API_KEY is not set in .env. Aborting.');
            return self::FAILURE;
        }

        $commit = $this->option('commit');
        $limit = (int) $this->option('limit');
        $onlyEmpty = $this->option('only-empty');
        $delayMs = (int) $this->option('delay');

        $this->info('=== Génération de descriptions pour les lieux du campus UAC (via Groq API) ===');
        $this->info($commit ? '🔴 MODE COMMIT — les descriptions seront écrites en base.' : '🟢 MODE DRY-RUN — aucune écriture. Utilisez --commit pour appliquer.');
        $this->newLine();

        // Build query
        $query = Place::query()->orderBy('name');

        if ($onlyEmpty) {
            $query->where(function ($q) {
                $q->whereNull('description')->orWhere('description', '');
            });
        }

        $names = $this->option('names');
        if (!empty($names)) {
            $nameArray = array_map('trim', explode(',', $names));
            $query->whereIn('name', $nameArray);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $places = $query->get();

        if ($places->isEmpty()) {
            $this->warn('Aucun lieu à traiter.');
            return self::SUCCESS;
        }

        $this->info("Lieux à traiter : {$places->count()}");
        $this->newLine();

        $generated = 0;
        $failed = 0;
        $skipped = 0;
        $previouslyGenerated = [];

        foreach ($places as $place) {
            // Determine image keyword for display
            $imageKeyword = $this->determineImageKeyword($place);

            $description = $this->generateDescription($place, $apiKey, $previouslyGenerated);

            if ($description === null) {
                $failed++;
                $this->error("  ✗ [{$place->id}] {$place->name} — Échec API Gemini");
                continue;
            }

            // Trim description
            $description = trim($description);

            if (empty($description)) {
                $failed++;
                $this->error("  ✗ [{$place->id}] {$place->name} — Réponse vide de Gemini");
                continue;
            }

            // Validate description is not just a category or "yes"
            $invalidPatterns = ['/^yes$/i', '/^non$/i', '/^oui$/i', '/^no$/i', '/^ - yes$/i', '/^bank$/i', '/^office$/i', '/^library$/i', '/^restaurant$/i', '/^fuel$/i', '/^atm$/i', '/^fast_food$/i', '/^cafe$/i'];
            foreach ($invalidPatterns as $pattern) {
                if (preg_match($pattern, $description)) {
                    $failed++;
                    $this->error("  ✗ [{$place->id}] {$place->name} — Réponse invalide: {$description}");
                    continue 2;
                }
            }

            // Validate minimum length (at least 10 characters)
            if (strlen($description) < 10) {
                $failed++;
                $this->error("  ✗ [{$place->id}] {$place->name} — Description trop courte: {$description}");
                continue;
            }

            $generated++;

            // Display result
            $this->info("  ✓ [{$place->id}] {$place->name}");
            $this->line("    Catégorie : {$place->category}");
            $this->line("    Image Unsplash : {$imageKeyword}");
            $this->line("    Ancienne  : " . ($place->description ?: '(vide)'));
            $this->line("    Nouvelle  : {$description}");
            $this->newLine();

            // Track previously generated descriptions to inject variety in subsequent prompts
            $previouslyGenerated[] = $description;
            if (count($previouslyGenerated) > 5) {
                array_shift($previouslyGenerated);
            }

            // Write to DB if --commit
            if ($commit) {
                $place->description = $description;
                $place->save();
            }

            // Rate limit delay between calls
            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        $this->newLine();
        $this->info('=== Résumé ===');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Lieux traités', $places->count()],
                ['Descriptions générées', $generated],
                ['Échecs', $failed],
                ['Mode', $commit ? 'COMMIT' : 'DRY-RUN'],
            ]
        );

        if (!$commit && $generated > 0) {
            $this->newLine();
            $this->warn('💡 Relancez avec --commit pour appliquer les descriptions en base.');
        }

        return self::SUCCESS;
    }

    /**
     * Determine Unsplash image keyword based on place name and category.
     * Priority order: most specific to most general.
     */
    private function determineImageKeyword(Place $place): string
    {
        $name = strtolower($place->name);
        $category = strtolower($place->category);

        // Priority 1: Most specific keywords
        if (preg_match('/résidence|residence|dortoir|dormitory|foyer/i', $name)) {
            return 'student dormitory building';
        }
        if (preg_match('/restaurant|resteau/i', $name) || in_array($category, ['restaurant', 'cafe', 'fast_food'])) {
            return 'university cafeteria food court';
        }
        if (preg_match('/ecobank/i', $name) || in_array($category, ['bank', 'atm'])) {
            return 'bank branch atm';
        }
        if (preg_match('/station/i', $name) || $category === 'fuel') {
            return 'gas station fuel pump';
        }
        if (preg_match('/jardin botanique|zoologique/i', $name)) {
            return 'botanical garden zoo';
        }
        if (preg_match('/ferme/i', $name)) {
            return 'agricultural farm field';
        }
        if (preg_match('/serre/i', $name)) {
            return 'greenhouse plants';
        }
        if (preg_match('/rectorat/i', $name)) {
            return 'university administration building facade';
        }
        if (preg_match('/imprimerie/i', $name)) {
            return 'printing press workshop';
        }
        if (preg_match('/institut|école doctorale|centre de recherche|chaire|ifri|uva|cipma|confucius/i', $name)) {
            return 'research institute building';
        }
        if (preg_match('/département/i', $name)) {
            return 'university department office';
        }

        // Priority 2: Amphi (must come before Faculty to avoid "Amphi B EPAC" falling into Faculty)
        if (preg_match('/amphi/i', $name)) {
            return 'university lecture hall';
        }

        // Priority 3: Library
        if (preg_match('/bibliothèque|bibliotheque/i', $name)) {
            return 'university library';
        }

        // Priority 4: Laboratory
        if (preg_match('/laboratoire|labo/i', $name)) {
            return 'science laboratory';
        }

        // Priority 5: Administration/Decanat (must come before Faculty)
        if (preg_match('/administration|décanat|decanat|vice rectorat/i', $name)) {
            return 'university office building';
        }

        // Priority 6: Faculty/University
        if (preg_match('/faculté|faculty|université|university/i', $name) || $category === 'university') {
            return 'university faculty building campus';
        }

        // Priority 7: Zone Masters
        if (preg_match('/zone masters/i', $name)) {
            return 'university campus building modern';
        }

        // Fallback: generic university building
        return 'university campus building';
    }

    /**
     * Call Groq API to generate a description for a given place.
     */
    private function generateDescription(Place $place, string $apiKey, array $previouslyGenerated = []): ?string
    {
        // Determine image keyword based on place name and category (priority order: most specific to most general)
        $imageKeyword = $this->determineImageKeyword($place);

        // Determine type-specific formulations for description
        $typeFormulations = "";
        if (preg_match('/(administration|rectorat|décanat|decanat)/i', $place->name)) {
            $typeFormulations = "'Regroupe les services...' / 'Point de contact pour...' / 'Accueille les démarches de...' / 'Sert de guichet pour...'";
        } elseif (preg_match('/(amphi|amphithéâtre|amphitheatre)/i', $place->name)) {
            $typeFormulations = "'Accueille les cours magistraux...' / 'Salle réservée aux enseignements...' / 'Espace dédié aux cours et conférences...' / 'Lieu de rassemblement pour les enseignements...'";
        } elseif (preg_match('/(laboratoire|labo)/i', $place->name)) {
            $typeFormulations = "'Consacré aux travaux de recherche...' / 'Équipé pour les expériences et travaux pratiques...' / 'Dédié aux activités de recherche en...'";
        } elseif (preg_match('/(bibliothèque|bibliotheque|documentation)/i', $place->name)) {
            $typeFormulations = "'Met à disposition des ressources documentaires...' / 'Espace d\'étude et de consultation...'";
        } elseif (preg_match('/(résidence|residence|dortoir|dormitory|foyer)/i', $place->name)) {
            $typeFormulations = "'Logement pour les étudiants...' / 'Héberge les étudiants du campus...' / 'Résidence universitaire pour...'";
        } elseif (preg_match('/(restaurant|resteau|cafeteria|cantine)/i', $place->name) || in_array($place->category, ['restaurant', 'cafe', 'fast_food'])) {
            $typeFormulations = "'Espace de restauration pour les étudiants...' / 'Cafétéria servant des repas...' / 'Restaurant universitaire pour...'";
        } elseif (preg_match('/(ecobank|bank|banque|atm|dab)/i', $place->name) || in_array($place->category, ['bank', 'atm'])) {
            $typeFormulations = "'Services bancaires pour la communauté...' / 'Guichet bancaire et distributeur...' / 'Agence bancaire pour...'";
        } elseif (preg_match('/(station|fuel|essence|gazole)/i', $place->name) || $place->category === 'fuel') {
            $typeFormulations = "'Station-service pour véhicules...' / 'Point de ravitaillement en carburant...' / 'Station essence pour...'";
        } elseif (preg_match('/(jardin|botanique|zoologique|zoo)/i', $place->name)) {
            $typeFormulations = "'Espace vert pour la conservation...' / 'Jardin botanique et zoologique...' / 'Zone de conservation de la biodiversité...'";
        } elseif (preg_match('/(ferme|agricole|agriculture)/i', $place->name)) {
            $typeFormulations = "'Exploitation agricole pour la formation...' / 'Ferme pédagogique et de recherche...' / 'Site de production agricole...'";
        } elseif (preg_match('/(serre|greenhouse)/i', $place->name)) {
            $typeFormulations = "'Espace de culture sous abri...' / 'Serre pour les expériences botaniques...' / 'Structure de culture protégée...'";
        } elseif (preg_match('/(imprimerie|printing|press)/i', $place->name)) {
            $typeFormulations = "'Atelier d\'impression et de reprographie...' / 'Service d\'impression pour l\'université...' / 'Centre de reprographie pour...'";
        } elseif (preg_match('/(institut|école doctorale|centre de recherche|chaire|ifri|uva|cipma|confucius)/i', $place->name)) {
            $typeFormulations = "'Centre de recherche et formation...' / 'Institut spécialisé en...' / 'Structure de recherche en...'";
        } elseif (preg_match('/(département|department)/i', $place->name)) {
            $typeFormulations = "'Unité d\'enseignement et recherche...' / 'Département académique pour...' / 'Service départemental de...'";
        } elseif (preg_match('/(faculté|faculty|université|university)/i', $place->name) || $place->category === 'university') {
            $typeFormulations = "'Bâtiment d\'enseignement supérieur...' / 'Faculté pour les études de...' / 'Établissement universitaire pour...'";
        } else {
            $typeFormulations = "'Espace dédié à...' / 'Sert de lieu pour...' / 'Accueille les activités de...'";
        }

        $prompt = "Tu rédiges une description courte (1 phrase, 15-25 mots, en français) pour un lieu du campus de l'Université d'Abomey-Calavi (UAC), Bénin. "
            . "IMPORTANT : Le lieu est réel et vérifié. JAMAIS de mots comme 'probablement', 'sans doute', 'peut-être'. "
            . "INTERDICTION ABSOLUE : Ne réponds JAMAIS par 'yes', 'non', 'oui', 'no', ou par une catégorie seule (ex: 'Bank', 'Office', 'Library', 'Restaurant', 'Fuel', 'Atm', 'Fast_food', 'Cafe'). "
            . "INTERDICTION ABSOLUE : Ne réponds JAMAIS par ' - Yes' ou des formats similaires. "
            . "IMPORTANT : Varie la structure de chaque phrase. Utilise UNIQUEMENT ces formules d'ouverture adaptées au type de bâtiment, et alterne-les : "
            . $typeFormulations . ". "
            . "N'utilise jamais 'est situé', 'est un lieu', 'est un espace', 'est un bâtiment'. ";

        if (!empty($previouslyGenerated)) {
            $prompt .= "IMPORTANT - POUR ASSURER LA VARIÉTÉ : Voici les descriptions que tu as générées récemment. "
                . "Tu as l'INTERDICTION STRICTE d'utiliser la même formule d'ouverture que ces descriptions récentes : "
                . implode(" | ", $previouslyGenerated) . " ";
        }

        $prompt .= "IMPORTANT : Utilise ce glossaire d'acronymes exact : EPAC=École Polytechnique d'Abomey-Calavi, FAST=Faculté des Sciences et Techniques, FSA=Faculté des Sciences Agronomiques, FADESP=Faculté de Droit et de Sciences Politiques, FASEG=Faculté des Sciences Économiques et de Gestion, FLLAC=Faculté des Lettres Langues Arts et Communication, IFRI=Institut de Formation et de Recherche en Informatique. "
            . "N'invente aucun autre acronyme. Si inconnu, ignore-le et décris la fonction du lieu. "
            . "IMPORTANT : Pour les amphithéâtres et bâtiments dont le nom NE contient PAS l'acronyme d'une faculté (ex: 'Amphi A 1000', 'Amphi B 500'), n'invente PAS de rattachement à une faculté spécifique. Reste généraliste : 'cours magistraux et grands rassemblements étudiants'. ";

        // Extract capacity from name if it looks like an amphi with a number (e.g., "Amphi A 1000")
        if (preg_match('/(?:Amphi|Amphithéâtre).*?(\d+)/i', $place->name, $matches)) {
            $capacity = $matches[1];
            $prompt .= "IMPORTANT : Le chiffre '{$capacity}' dans le nom du lieu indique sa capacité d'accueil. Mentionne explicitement cette capacité d'environ {$capacity} places dans ta description pour le distinguer des autres amphis. ";
        }

        $prompt .= "N'applique un rattachement précis que si le nom contient déjà l'acronyme de la faculté (ex: 'Administration FAST', 'Bâtiment C EPAC'). "
            . "EXEMPLES DE BONNES DESCRIPTIONS : "
            . "'Accueille les cours magistraux avec une capacité d'environ 1000 places.' "
            . "'Regroupe les services administratifs de l'EPAC.' "
            . "'Met à disposition des ressources documentaires pour les étudiants.' "
            . "'Consacré aux travaux de recherche en chimie.' "
            . "'Salle réservée aux enseignements et événements universitaires d'environ 500 places.' "
            . "Lieu : nom = '{$place->name}', catégorie = '{$place->category}'. "
            . "Réponds uniquement avec la description finale, sans préambule ni guillemets.";

        $maxRetries = 3;
        $attempt = 0;

        while ($attempt <= $maxRetries) {
            try {
                $response = Http::timeout(30)->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post(self::GROQ_ENDPOINT, [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => 100,
                    'temperature' => 0.4,
                ]);

                if (!$response->successful()) {
                    if ($response->status() === 429) {
                        $attempt++;
                        if ($attempt > $maxRetries) {
                            $this->warn("    ⚠ Échec définitif après {$maxRetries} tentatives (Rate Limit).");
                            return null;
                        }

                        $waitTime = 60; // fallback
                        $retryAfter = $response->header('Retry-After');
                        if ($retryAfter) {
                            $waitTime = (int) $retryAfter;
                        } else {
                            $bodyData = $response->json();
                            $msg = $bodyData['error']['message'] ?? '';
                            if (preg_match('/Please try again in (?:(\d+)m)?([\d\.]+)s/', $msg, $matches)) {
                                $minutes = isset($matches[1]) && $matches[1] !== '' ? (int)$matches[1] : 0;
                                $seconds = (float)$matches[2];
                                $waitTime = (int) ceil(($minutes * 60) + $seconds);
                            }
                        }

                        // Add 2s buffer
                        $waitTime += 2;
                        $mins = floor($waitTime / 60);
                        $secs = $waitTime % 60;
                        $timeStr = $mins > 0 ? "{$mins}m{$secs}s" : "{$secs}s";

                        $this->warn("    ⏳ Quota Groq atteint. Tentative {$attempt}/{$maxRetries}. Reprise automatique dans {$timeStr}...");
                        
                        $bar = $this->output->createProgressBar($waitTime);
                        for ($i = 0; $i < $waitTime; $i++) {
                            sleep(1);
                            $bar->advance();
                        }
                        $bar->finish();
                        $this->newLine();
                        continue; // Retry
                    }

                    Log::warning('Groq API error', [
                        'place_id' => $place->id,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    $this->warn("    ⚠ HTTP {$response->status()} — {$response->body()}");
                    return null;
                }

                $data = $response->json();

                // Parse: choices[0].message.content
                $text = $data['choices'][0]['message']['content'] ?? null;

                if (!$text) {
                    Log::warning('Groq API: empty text in response', [
                        'place_id' => $place->id,
                        'response' => $data,
                    ]);
                    return null;
                }

                return trim($text);
            } catch (\Exception $e) {
                Log::error('Groq API exception', [
                    'place_id' => $place->id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("    ⚠ Exception : {$e->getMessage()}");
                return null;
            }
        }
        
        return null;
    }
}
