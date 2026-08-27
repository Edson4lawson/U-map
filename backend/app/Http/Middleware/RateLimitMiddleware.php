<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $this->logRateLimitExceeded($request, $key);
            
            return response()->json([
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => $this->limiter->availableIn($key)
            ], 429);
        }

        $this->limiter->hit($key, $decayMinutes);

        $response = $next($request);

        // Ajouter les headers de rate limiting
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $maxAttempts - $this->limiter->attempts($key),
        ]);

        return $response;
    }

    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $route = $request->route()?->getName() ?? $request->path();

        return sha1(
            $request->ip().'|'.$route.'|'.($request->user()?->id ?? 'guest')
        );
    }

    /**
     * Log rate limit exceeded.
     */
    protected function logRateLimitExceeded(Request $request, string $key): void
    {
        Log::warning('Rate limit exceeded', [
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'route' => $request->route()?->getName(),
            'key' => $key,
        ]);
    }
}
