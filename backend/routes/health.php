<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;

// Health check endpoints (no authentication required)
Route::get('/health', [HealthController::class, 'health']);
Route::get('/health/detailed', [HealthController::class, 'healthDetailed']);
Route::get('/metrics', [HealthController::class, 'metrics']);
