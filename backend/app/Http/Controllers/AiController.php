<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    /**
     * Assistant IA hybride (Groq Llama 3.3 ou Gemini avec fallback de secours).
     */
    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string|min:2|max:500',
        ]);

        $question = trim($request->input('question'));
        $groqKey = config('services.groq.key');
        $geminiKey = config('services.gemini.key');

        $isGroqValid = !empty($groqKey) && !str_contains($groqKey, 'your_groq_api_key');
        $isGeminiValid = !empty($geminiKey) && !str_contains($geminiKey, 'your_gemini_api_key');

        $systemInstruction = $this->buildSystemInstruction();

        // 1. Tenter Groq (Llama 3.3 70B) si configuré
        if ($isGroqValid) {
            $groqResponse = $this->askGroq($groqKey, $systemInstruction, $question);
            if ($groqResponse !== null) {
                return response()->json(['answer' => $groqResponse]);
            }
        }

        // 2. Tenter Gemini si configuré
        if ($isGeminiValid) {
            $geminiResponse = $this->askGemini($geminiKey, $systemInstruction, $question);
            if ($geminiResponse !== null) {
                return response()->json(['answer' => $geminiResponse]);
            }
        }

        // 3. Fallback intelligent basé sur la base de connaissances du campus
        $offlineAnswer = $this->smartCampusFallback($question);
        return response()->json([
            'answer' => $offlineAnswer,
        ]);
    }

    protected function buildSystemInstruction(): string
    {
        $placesJson = '';

        if (File::exists(database_path('data/campus.json'))) {
            $jsonContent = json_decode(File::get(database_path('data/campus.json')), true);
            $features = $jsonContent['features'] ?? [];
            $placesList = [];

            foreach (array_slice($features, 0, 50) as $f) {
                $props = $f['properties'] ?? [];

                if (!empty($props['name'])) {
                    $id = $props['id'] ?? '';
                    $placesList[] = '- '.$props['name'].' (ID: '.$id.') - '.($props['category'] ?? 'Général').' : '
                        .($props['description'] ?? '');
                }
            }

            $placesJson = implode("\n", $placesList);
        }

        return "Tu es l'assistant intelligent officiel de l'Université d'Abomey-Calavi (UAC) au Bénin. "
            ."Tu aides les étudiants sur la vie académique, quotidienne et l'orientation sur le campus. "
            ."Réponds en français, de manière structurée, chaleureuse et concise (3-4 phrases max).\n\n"
            ."IMPORTANT : Quand tu mentionnes un lieu du campus dans ta réponse, utilise TOUJOURS le format spécial suivant : "
            ."[LIEU:Nom du lieu|ID_du_lieu]. Par exemple : [LIEU:Bibliothèque Universitaire|bu_centrale] ou [LIEU:Zone Master|36]. "
            ."Cela permettra de créer un lien cliquable vers la carte. Ne donne JAMAIS les coordonnées GPS brutes.\n\n"
            ."Lieux officiels du campus avec leurs IDs :\n".$placesJson;
    }

    protected function askGroq(string $apiKey, string $systemInstruction, string $question): ?string
    {
        try {
            $response = Http::withToken($apiKey)
                ->timeout(15)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemInstruction],
                        ['role' => 'user', 'content' => $question],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ]);

            if ($response->successful()) {
                $answer = $response->json('choices.0.message.content');
                if (!empty($answer)) {
                    return trim($answer);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Groq AI API error: ' . $e->getMessage());
        }

        return null;
    }

    protected function askGemini(string $apiKey, string $systemInstruction, string $question): ?string
    {
        try {
            $response = Http::timeout(15)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}",
                [
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [['text' => $systemInstruction."\n\nQuestion : ".$question]],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 500,
                    ],
                ]
            );

            if ($response->successful()) {
                $answer = $response->json('candidates.0.content.parts.0.text');
                if (!empty($answer)) {
                    return trim($answer);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gemini AI API error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Recherche intelligente hors-ligne / fallback dans les données du campus.
     */
    protected function smartCampusFallback(string $question): string
    {
        $normalizedQ = $this->normalizeText($question);

        // Salutations courantes
        if (preg_match('/\b(bonjour|salut|hello|bonsoir|coucou|aide|aidez|aide-moi|qui es-tu|qui est tu|presentation)\b/ui', $normalizedQ)) {
            return "Bonjour ! 👋 Je suis l'assistant intelligent du campus de l'Université d'Abomey-Calavi (UAC).\n\n"
                ."Je peux vous orienter instantanément sur le campus. Posez-moi des questions comme :\n"
                ."• *« Où se trouve la Bibliothèque Centrale ? »*\n"
                ."• *« Où puis-je manger ? »*\n"
                ."• *« Comment trouver l'Amphi Houdégbé ? »*\n"
                ."• *« Où sont les résidences universitaires ? »*\n\n"
                ."Cliquez sur les lieux suggérés pour ouvrir directement l'itinéraire sur la carte !";
        }

        if (File::exists(database_path('data/campus.json'))) {
            $jsonContent = json_decode(File::get(database_path('data/campus.json')), true);
            $features = $jsonContent['features'] ?? [];

            // Dictionnaire sémantique de synonymes campus
            $synonyms = [
                'manger' => ['restaurant', 'resto', 'cafeteria', 'buvette', 'fast food', 'alimentation', 'nourriture', 'repas'],
                'faim' => ['restaurant', 'resto', 'cafeteria', 'buvette', 'fast food', 'alimentation'],
                'nourriture' => ['restaurant', 'resto', 'cafeteria', 'buvette'],
                'repas' => ['restaurant', 'resto', 'cafeteria', 'buvette'],
                'dejeuner' => ['restaurant', 'resto', 'cafeteria'],
                'diner' => ['restaurant', 'resto', 'cafeteria'],
                'cafe' => ['cafeteria', 'buvette', 'restaurant'],
                'boire' => ['buvette', 'cafeteria', 'restaurant'],
                'amphi' => ['amphi', 'amphitheatre', 'auditorium', 'salle'],
                'amphitheatre' => ['amphi', 'amphitheatre', 'auditorium'],
                'cours' => ['amphi', 'amphitheatre', 'salle', 'batiment', 'faculte', 'zone master'],
                'houdegbe' => ['houdegbe', 'amphi houdegbe', 'amphitheatre'],
                'etudier' => ['bibliotheque', 'bu', 'salle de lecture', 'espace d\'etude'],
                'reviser' => ['bibliotheque', 'bu', 'salle de lecture', 'espace d\'etude'],
                'livre' => ['bibliotheque', 'bu', 'documentation'],
                'bu' => ['bibliotheque', 'bu centrale', 'centre de documentation'],
                'biblio' => ['bibliotheque', 'bu'],
                'dormir' => ['residence', 'cite', 'chambre', 'logement', 'dortoir'],
                'chambre' => ['residence', 'cite', 'logement', 'chambre'],
                'residence' => ['residence', 'cite', 'logement'],
                'cite' => ['residence', 'cite', 'logement'],
                'logement' => ['residence', 'cite', 'logement'],
                'malade' => ['centre medical', 'infirmerie', 'sante', 'pharmacie', 'soin'],
                'soin' => ['centre medical', 'infirmerie', 'sante', 'pharmacie'],
                'sante' => ['centre medical', 'infirmerie', 'sante', 'pharmacie'],
                'medecin' => ['centre medical', 'infirmerie', 'sante'],
                'infirmerie' => ['centre medical', 'infirmerie', 'sante'],
                'sport' => ['terrain', 'stade', 'sport', 'gymnase', 'plateau'],
                'football' => ['terrain', 'stade', 'sport', 'football'],
                'basket' => ['terrain', 'plateau', 'sport', 'basketball'],
                'prier' => ['mosquee', 'chapelle', 'eglise', 'culte'],
                'priere' => ['mosquee', 'chapelle', 'eglise'],
                'mosquee' => ['mosquee', 'priere'],
                'eglise' => ['chapelle', 'eglise', 'paroisse'],
                'argent' => ['banque', 'gab', 'distributeur', 'retrait', 'ecobank', 'boa'],
                'retrait' => ['banque', 'gab', 'distributeur', 'retrait'],
                'distributeur' => ['banque', 'gab', 'distributeur'],
                'banque' => ['banque', 'gab', 'distributeur'],
                'admin' => ['rectorat', 'scolarite', 'administration', 'direction', 'decanat'],
                'rectorat' => ['rectorat', 'administration centrale', 'direction'],
                'scolarite' => ['scolarite', 'rectorat', 'inscriptions', 'administration'],
                'inscription' => ['scolarite', 'rectorat', 'administration'],
            ];

            // Stop words français à ignorer
            $stopWords = [
                'le', 'la', 'les', 'un', 'une', 'des', 'du', 'de', 'd', 'l',
                'ou', 'ou', 'se', 'ce', 'cet', 'cette', 'ces', 'est', 'sont', 'a',
                'pour', 'par', 'sur', 'dans', 'avec', 'sans', 'sous', 'vers', 'chez',
                'mon', 'ma', 'mes', 'ton', 'ta', 'tes', 'son', 'sa', 'ses',
                'je', 'tu', 'il', 'elle', 'on', 'nous', 'vous', 'ils', 'elles',
                'comment', 'trouver', 'chercher', 'aller', 'situe', 'situer', 'trouve', 'trouvent',
                'quel', 'quelle', 'quels', 'quelles', 'qui', 'que', 'quoi', 'dont', 'qu',
                'est-ce', 'peux', 'puis', 'pouvez', 'aimerais', 'voudrais', 'svp', 'merci'
            ];

            // Extraire les mots signifiants de la requête
            $rawWords = array_filter(
                preg_split('/[\s,\.\?!;:\'"]+/', $normalizedQ),
                fn($w) => mb_strlen($w) >= 3 && !in_array($w, $stopWords)
            );

            $searchKeywords = [];
            foreach ($rawWords as $w) {
                $searchKeywords[] = $w;
                if (isset($synonyms[$w])) {
                    foreach ($synonyms[$w] as $syn) {
                        $searchKeywords[] = $this->normalizeText($syn);
                    }
                }
            }
            $searchKeywords = array_unique(array_filter($searchKeywords, fn($k) => !empty($k) && !in_array($k, $stopWords)));

            $matchedPlaces = [];
            foreach ($features as $f) {
                $props = $f['properties'] ?? [];
                $name = $props['name'] ?? '';
                $desc = $props['description'] ?? '';
                $cat = $props['category'] ?? '';
                $type = $props['type'] ?? '';
                $id = $props['id'] ?? '';

                if (empty($name)) continue;

                $normName = $this->normalizeText($name);
                $normDesc = $this->normalizeText($desc);
                $normCat = $this->normalizeText($cat);
                $normType = $this->normalizeText($type);

                $score = 0;

                // Match direct sur le nom complet
                foreach ($rawWords as $rw) {
                    if (mb_strpos($normName, $rw) !== false) {
                        $score += 50;
                    }
                }

                // Match sur chaque mot-clé et synonyme
                foreach ($searchKeywords as $kw) {
                    if (empty($kw)) continue;

                    if (mb_strpos($normName, $kw) !== false) {
                        $score += 30;
                    }
                    if (mb_strpos($normCat, $kw) !== false || mb_strpos($normType, $kw) !== false) {
                        $score += 15;
                    }
                    if (!empty($normDesc) && mb_strpos($normDesc, $kw) !== false) {
                        $score += 8;
                    }
                }

                if ($score > 0) {
                    $matchedPlaces[] = [
                        'name' => $name,
                        'id' => $id,
                        'desc' => $desc,
                        'category' => $cat ?: $type,
                        'score' => $score,
                    ];
                }
            }

            if (!empty($matchedPlaces)) {
                usort($matchedPlaces, fn($a, $b) => $b['score'] <=> $a['score']);
                $top = array_slice($matchedPlaces, 0, 4);
                $links = [];
                foreach ($top as $place) {
                    $descPart = !empty($place['desc']) ? " : {$place['desc']}" : (!empty($place['category']) ? " ({$place['category']})" : '');
                    $links[] = "• [LIEU:{$place['name']}|{$place['id']}]{$descPart}";
                }

                return "Voici les lieux trouvés sur le campus de l'UAC correspondant à votre recherche :\n\n"
                    . implode("\n", $links)
                    . "\n\n💡 Cliquez sur l'un des lieux pour l'afficher instantanément sur la carte interactive !";
            }
        }

        return "Je suis l'assistant U-Map du campus de l'UAC. Je n'ai pas trouvé de lieu correspondant exactement à votre demande. "
            ."Essayez de préciser le nom du bâtiment, la faculté, le restaurant ou le service que vous recherchez !";
    }

    /**
     * Normalise un texte (suppression des accents, minuscules, ponctuation).
     */
    protected function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        
        $accents = [
            '/[áàâãäå]/u' => 'a',
            '/[éèêë]/u'    => 'e',
            '/[íìîï]/u'    => 'i',
            '/[óòôõö]/u'   => 'o',
            '/[úùûü]/u'    => 'u',
            '/[ýÿ]/u'      => 'y',
            '/[ç]/u'       => 'c',
            '/[ñ]/u'       => 'n',
        ];

        $text = preg_replace(array_keys($accents), array_values($accents), $text);
        // Supprimer caractères spéciaux
        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text);
        return preg_replace('/\s+/', ' ', $text);
    }
}
