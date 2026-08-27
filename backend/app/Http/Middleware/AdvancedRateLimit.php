<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\AuditLogService;
use Symfony\Component\HttpFoundation\Response;

class AdvancedRateLimit
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle an incoming request with advanced rate limiting.
     */
    public function handle(Request $request, Closure $next, string $maxRequests = '60', string $windowMinutes = '1'): Response
    {
        $maxRequests = (int) $maxRequests;
        $windowMinutes = (int) $windowMinutes;
        $windowSeconds = $windowMinutes * 60;

        $userId = $request->user()?->id;
        $ipAddress = $request->ip();
        $endpoint = $request->route()?->getName() ?? $request->path();

        // Check user-based rate limit
        if ($userId) {
            $userKey = "rate_limit:user:{$userId}:{$endpoint}";
            $userCount = Cache::get($userKey, 0);

            if ($userCount >= $maxRequests) {
                $this->auditLogService->logBlocked(
                    'rate_limit_exceeded',
                    'User rate limit exceeded',
                    null,
                    null,
                    [
                        'user_id' => $userId,
                        'endpoint' => $endpoint,
                        'requests' => $userCount,
                        'limit' => $maxRequests,
                    ]
                );

                return response()->json([
                    'error' => 'Too many requests',
                    'message' => 'Rate limit exceeded. Please try again later.',
                    'retry_after' => Cache::get($userKey . ':expires_at', 0) - time(),
                ], 429);
            }

            Cache::increment($userKey);
            Cache::put($userKey, $userCount + 1, $windowSeconds);
            Cache::put($userKey . ':expires_at', time() + $windowSeconds, $windowSeconds);
        }

        // Check IP-based rate limit (stricter)
        $ipKey = "rate_limit:ip:{$ipAddress}:{$endpoint}";
        $ipLimit = $maxRequests * 2; // Allow 2x more for IP
        $ipCount = Cache::get($ipKey, 0);

        if ($ipCount >= $ipLimit) {
            $this->auditLogService->logBlocked(
                'ip_rate_limit_exceeded',
                'IP rate limit exceeded',
                null,
                null,
                [
                    'ip_address' => $ipAddress,
                    'endpoint' => $endpoint,
                    'requests' => $ipCount,
                    'limit' => $ipLimit,
                ]
            );

            return response()->json([
                'error' => 'Too many requests',
                'message' => 'IP rate limit exceeded. Please try again later.',
                'retry_after' => Cache::get($ipKey . ':expires_at', 0) - time(),
            ], 429);
        }

        Cache::increment($ipKey);
        Cache::put($ipKey, $ipCount + 1, $windowSeconds);
        Cache::put($ipKey . ':expires_at', time() + $windowSeconds, $windowSeconds);

        // Add rate limit headers
        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', $maxRequests);
        $response->headers->set('X-RateLimit-Remaining', $maxRequests - ($userCount ?? 0) - 1);
        $response->headers->set('X-RateLimit-Reset', Cache::get($userKey . ':expires_at', 0));

        return $response;
    }
}
