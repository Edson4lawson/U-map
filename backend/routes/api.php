<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\EventController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/events', [EventController::class, 'index']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes pour les lieux
Route::get('/places', [PlaceController::class, 'index']);
Route::get('/places/search', [PlaceController::class, 'search']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/conversations', [MessageController::class, 'getConversations']);
    Route::get('/messages/unread-count', [MessageController::class, 'getUnreadCount']);
    Route::get('/messages/{receiverId}', [MessageController::class, 'getMessages']);
    Route::post('/messages', [MessageController::class, 'sendMessage']);
    Route::post('/places', [PlaceController::class, 'store']);
    Route::get('/students', function() {
        return \App\Models\User::where('id', '!=', auth()->id())->get();
    });
});

// Route pour l'IA (Proxy Gemini officiel)
Route::post('/ai/ask', function (Request $request) {
    $question = $request->input('question');
    
    if (empty($question)) {
        return response()->json(['error' => 'Question is required'], 400);
    }
    
    $apiKey = env('GEMINI_API_KEY');
    
    if (!$apiKey) {
        // Fallback informatif si la clé n'est pas encore configurée
        return response()->json([
            'answer' => "🤖 L'assistant U-Map est prêt ! Pour activer l'IA complète, configurez votre clé GEMINI_API_KEY dans le fichier .env du backend. (Vous avez demandé : \"$question\")"
        ]);
    }
    
    try {
        // Lecture dynamique des lieux pour enrichir le contexte de l'assistant
        $placesJson = '';
        if (\Illuminate\Support\Facades\File::exists(database_path('data/campus.json'))) {
            $jsonContent = json_decode(\Illuminate\Support\Facades\File::get(database_path('data/campus.json')), true);
            $features = $jsonContent['features'] ?? [];
            $placesList = [];
            foreach (array_slice($features, 0, 50) as $f) { // Limite pour éviter de surcharger le prompt
                $props = $f['properties'] ?? [];
                $coords = $f['geometry']['coordinates'] ?? [];
                if (!empty($props['name'])) {
                    $placesList[] = "- " . $props['name'] . " (" . ($props['category'] ?? 'Général') . ") : " . ($props['description'] ?? '') . " [GPS: " . implode(',', array_reverse($coords)) . "]";
                }
            }
            $placesJson = implode("\n", $placesList);
        }
        
        $systemInstruction = "Tu es l'assistant intelligent officiel de l'application U-Map pour l'Université d'Abomey-Calavi (UAC) au Bénin. " .
            "Ton rôle est d'aider, de guider et de renseigner chaleureusement les étudiants et visiteurs sur le campus (localisation des facultés, amphis, restaurants, bibliothèque, etc.).\n" .
            "Sois toujours accueillant, précis et réponds de manière concise (maximum 3-4 phrases par réponse si possible) en français.\n\n" .
            "Voici les lieux officiels du campus de l'UAC enregistrés dans la base de données U-Map pour t'aider :\n" . $placesJson;
            
        $response = \Illuminate\Support\Facades\Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemInstruction . "\n\nQuestion de l'étudiant/visiteur : " . $question]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 500,
            ]
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            $answer = $data['candidates'][0]['content']['parts'][0]['text'] ?? "Désolé, je n'ai pas pu traiter cette question.";
            return response()->json([
                'answer' => trim($answer)
            ]);
        } else {
            return response()->json([
                'answer' => "Désolé, l'assistant rencontre des difficultés de connexion à l'API Gemini (Code " . $response->status() . ")."
            ]);
        }
    } catch (\Exception $e) {
        return response()->json([
            'answer' => "Oups, impossible de joindre l'intelligence artificielle pour le moment. Erreur : " . $e->getMessage()
        ]);
    }
});
