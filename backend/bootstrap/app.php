<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/health.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*')) {
                return null; // Retourne 401 pour les requêtes API
            }
            return route('login');
        });
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\AuditLog::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
            'advanced.rate' => \App\Http\Middleware\AdvancedRateLimit::class,
            'anti.spam' => \App\Http\Middleware\AntiSpam::class,
            'audit.log' => \App\Http\Middleware\AuditLog::class,
            'brute.force' => \App\Http\Middleware\BruteForceProtection::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\AuthenticationException) {
                return null;
            }

            return response()->json([
                'message' => app()->isProduction() ? 'Une erreur est survenue.' : $e->getMessage(),
            ], 500);
        });
    })->create();
