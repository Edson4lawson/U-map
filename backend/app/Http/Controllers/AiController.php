<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class AiController extends Controller
{
    /**
     * Assistant IA hybride (Groq Llama 3.3 ou Gemini).
     */
    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string|min:2|max:500',
        ]);

        $question = $request->input('question');
        $groqKey = config('services.groq.key');
        $geminiKey = config('services.gemini.key');

        if (!$groqKey && !$geminiKey) {
            return response()->json([
                'answer' => "🤖 L'assistant U-Map est prêt ! Configurez GROQ_API_KEY ou GEMINI_API_KEY dans le .env du backend.",
            ]);
        }

        try {
            $systemInstruction = $this->buildSystemInstruction();

            if ($groqKey) {
                return $this->askGroq($groqKey, $systemInstruction, $question);
            }

            return $this->askGemini($geminiKey, $systemInstruction, $question);
        } catch (\Exception $e) {
            return response()->json([
                'answer' => "Oups, impossible de joindre l'intelligence artificielle pour le moment.",
            ]);
        }
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
                $coords = $f['geometry']['coordinates'] ?? [];

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
            ."Réponds en français, de manière structurée et concise (4-5 phrases max).\n\n"
            ."IMPORTANT : Quand tu mentionnes un lieu du campus dans ta réponse, utilise TOUJOURS le format spécial suivant : "
            ."[LIEU:Nom du lieu|ID_du_lieu]. Par exemple : [LIEU:Bibliothèque Universitaire|bu_centrale]. "
            ."Cela permettra de créer un lien cliquable vers la carte. Ne donne JAMAIS les coordonnées GPS.\n\n"
            ."Lieux officiels du campus avec leurs IDs :\n".$placesJson;
    }

    protected function askGroq(string $apiKey, string $systemInstruction, string $question)
    {
        $response = Http::withToken($apiKey)
            ->timeout(30)
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
            $answer = $response->json('choices.0.message.content', "Désolé, je n'ai pas pu générer de réponse.");

            return response()->json(['answer' => trim($answer)]);
        }

        return response()->json([
            'answer' => "Désolé, l'assistant rencontre des difficultés de connexion (Code ".$response->status().').',
        ]);
    }

    protected function askGemini(string $apiKey, string $systemInstruction, string $question)
    {
        $response = Http::timeout(30)->post(
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
            $answer = $response->json('candidates.0.content.parts.0.text', "Désolé, je n'ai pas pu générer de réponse.");

            return response()->json(['answer' => trim($answer)]);
        }

        return response()->json([
            'answer' => "Désolé, Gemini rencontre des difficultés de connexion (Code ".$response->status().').',
        ]);
    }
}
