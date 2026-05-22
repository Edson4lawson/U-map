<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ── Admin Routes ──────────────────────────────────────────────
Route::post('/admin/login', [AdminController::class, 'login']);

Route::prefix('admin')->middleware(function ($request, $next) {
    $token = $request->bearerToken();
    if (!$token || !cache()->get('admin_token_' . $token)) {
        return response()->json(['message' => 'Non autorisé.'], 401);
    }
    return $next($request);
})->group(function () {
    Route::get('/verify', [AdminController::class, 'verify']);
    Route::get('/stats', [AdminController::class, 'stats']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
    Route::get('/places', [AdminController::class, 'places']);
    Route::put('/places/{id}/approve', [AdminController::class, 'approvePlace']);
    Route::delete('/places/{id}', [AdminController::class, 'deletePlace']);
    Route::get('/messages', [AdminController::class, 'messages']);
});

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

// Route pour l'IA hybride (Gemma 2 via Groq ou Gemini officiel)
Route::post('/ai/ask', function (Request $request) {
    $question = $request->input('question');
    
    if (empty($question)) {
        return response()->json(['error' => 'Question is required'], 400);
    }
    
    $groqKey = env('GROQ_API_KEY');
    $geminiKey = env('GEMINI_API_KEY');
    
    if (!$groqKey && !$geminiKey) {
        return response()->json([
            'answer' => "🤖 L'assistant U-Map hybride est prêt ! Pour l'activer en production, configurez GROQ_API_KEY (pour Gemma 2) ou GEMINI_API_KEY (pour Gemini) dans votre .env du backend."
        ]);
    }
    
    try {
        // Lecture dynamique des lieux pour enrichir le contexte de l'assistant
        $placesJson = '';
        if (\Illuminate\Support\Facades\File::exists(database_path('data/campus.json'))) {
            $jsonContent = json_decode(\Illuminate\Support\Facades\File::get(database_path('data/campus.json')), true);
            $features = $jsonContent['features'] ?? [];
            $placesList = [];
            foreach (array_slice($features, 0, 50) as $f) {
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
            
        // OPTION 1 : Llama 3.3 via Groq (Prioritaire si la clé est fournie)
        if ($groqKey) {
            $response = \Illuminate\Support\Facades\Http::withToken($groqKey)
                ->post("https://api.groq.com/openai/v1/chat/completions", [
                    'model' => 'llama-3.3-70b-versatile', // Le modèle Llama 3.3 70B ultra-performant et ultra-rapide sur Groq
                    'messages' => [
                        ['role' => 'system', 'content' => $systemInstruction],
                        ['role' => 'user', 'content' => $question]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500
                ]);
                
            if ($response->successful()) {
                $data = $response->json();
                $answer = $data['choices'][0]['message']['content'] ?? "Désolé, je n'ai pas pu générer de réponse.";
                return response()->json([
                    'answer' => trim($answer)
                ]);
            } else {
                return response()->json([
                    'answer' => "Désolé, l'assistant rencontre des difficultés de connexion à Groq (Code " . $response->status() . ")."
                ]);
            }
        }
        
        // OPTION 2 : Gemini officiel (Fallback)
        if ($geminiKey) {
            $response = \Illuminate\Support\Facades\Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$geminiKey}", [
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
                $answer = $data['candidates'][0]['content']['parts'][0]['text'] ?? "Désolé, je n'ai pas pu générer de réponse avec Gemini.";
                return response()->json([
                    'answer' => trim($answer)
                ]);
            } else {
                return response()->json([
                    'answer' => "Désolé, Gemini rencontre des difficultés de connexion (Code " . $response->status() . ")."
                ]);
            }
        }
        
    } catch (\Exception $e) {
        return response()->json([
            'answer' => "Oups, impossible de joindre l'intelligence artificielle pour le moment. Erreur : " . $e->getMessage()
        ]);
    }
});
