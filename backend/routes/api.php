<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LiveReportController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// ── Admin ─────────────────────────────────────────────────────
Route::post('/admin/login', [AdminController::class, 'login'])
    ->middleware('brute.force:5,15');

Route::prefix('admin')->middleware(\App\Http\Middleware\AdminAuth::class)->group(function () {
    Route::get('/verify', [AdminController::class, 'verify']);
    Route::get('/stats', [AdminController::class, 'stats']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::put('/users/{id}/restrict', [AdminController::class, 'toggleRestrictUser']);
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
    Route::get('/reports', [AdminController::class, 'reports']);
    Route::put('/reports/{id}/resolve', [AdminController::class, 'resolveReport']);
    Route::get('/places', [AdminController::class, 'places']);
    Route::put('/places/{id}/approve', [AdminController::class, 'approvePlace']);
    Route::delete('/places/{id}', [AdminController::class, 'deletePlace']);
    Route::get('/messages', [AdminController::class, 'messages']);
});

// ── Public ────────────────────────────────────────────────────
Route::middleware('throttle:60,1')->get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version', '1.0.0'),
        'environment' => app()->environment(),
    ]);
});

Route::middleware('throttle:60,1')->get('/events', [EventController::class, 'index']);
Route::middleware('throttle:60,1')->get('/places', [PlaceController::class, 'index']);
Route::middleware('throttle:60,1')->get('/places/{identifier}', [PlaceController::class, 'show']);
Route::middleware('throttle:30,1')->get('/places/search', [PlaceController::class, 'search']);
Route::middleware('throttle:30,1')->get('/places/osm', [PlaceController::class, 'getOSMPlaces']);

Route::middleware('throttle:30,1')->get('/live-reports', [LiveReportController::class, 'index']);

Route::post('/ai/ask', [AiController::class, 'ask'])
    ->middleware('throttle:20,1');

if (app()->environment('local')) {
    Route::get('/test', fn () => response()->json(['message' => 'API works']));
    Route::post('/test', fn () => response()->json(['message' => 'POST works']));
}

// ── Auth ──────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('brute.force:5,15');
Route::post('/register', [AuthController::class, 'register']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::post('/check-username', [AuthController::class, 'checkUsernameAvailability']);
Route::post('/check-email', [AuthController::class, 'checkEmailAvailability']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:3,5');
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/captcha', [AuthController::class, 'getCaptcha']);
Route::post('/2fa/verify', [AuthController::class, 'verify2fa'])
    ->middleware('throttle:5,1');
Route::post('/magic-link', [AuthController::class, 'sendMagicLink']);
Route::post('/magic-link/login', [AuthController::class, 'loginWithMagicLink']);
Route::post('/social-login', [AuthController::class, 'socialLogin']);

Route::middleware('auth:sanctum')->get('/user', fn (Request $request) => $request->user());

// ── Authenticated ─────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/2fa/enable', [AuthController::class, 'enable2fa']);
    Route::post('/2fa/confirm', [AuthController::class, 'confirm2fa']);
    Route::post('/2fa/disable', [AuthController::class, 'disable2fa']);
    
    Route::get('/devices', [\App\Http\Controllers\UserDeviceController::class, 'index']);
    Route::delete('/devices/{id}', [\App\Http\Controllers\UserDeviceController::class, 'destroy']);

    Route::get('/conversations', [MessageController::class, 'getConversations']);
    Route::get('/messages/unread-count', [MessageController::class, 'getUnreadCount']);
    Route::get('/messages/{receiverId}', [MessageController::class, 'getMessages']);
    Route::post('/messages', [MessageController::class, 'sendMessage'])
        ->middleware(['throttle:60,1', 'anti.spam']);

    Route::post('/places', [PlaceController::class, 'store']);

    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/users/{id}/report', [StudentController::class, 'report']);
    Route::post('/live-reports', [LiveReportController::class, 'store']);
    Route::put('/users/study-status', [StudentController::class, 'updateStudyStatus']);
    Route::get('/study-buddies', [StudentController::class, 'studyBuddies']);

    // Authentication route for private channels
    \Illuminate\Support\Facades\Broadcast::routes();
});
