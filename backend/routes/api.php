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

// Route pour l'IA (Proxy)
Route::post('/ai/ask', function (Request $request) {
    $question = $request->input('question');
    
    // Ici on ferait l'appel à l'API Gemini avec la clé API
    // Pour l'instant on garde une réponse simulée côté serveur
    return response()->json([
        'answer' => "Je suis le serveur Laravel. Tu as demandé : '$question'. Bientôt je serai connecté à Gemini !"
    ]);
});
