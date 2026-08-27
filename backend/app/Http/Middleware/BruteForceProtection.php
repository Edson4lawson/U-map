<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\AuditLogService;
use Symfony\Component\HttpFoundation\Response;

class BruteForceProtection
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle an incoming request with brute force protection.
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 5, int $decayMinutes = 15): Response
    {
        $ipAddress = $request->ip();
        $email = $request->input('email');

        // Check IP-based lockout
        $ipKey = "auth_lockout:ip:{$ipAddress}";
        if (Cache::has($ipKey)) {
            $this->auditLogService->logBlocked(
                'ip_locked_out',
                'IP locked out due to too many failed attempts',
                null,
                null,
                [
                    'ip_address' => $ipAddress,
                    'attempts' => Cache::get($ipKey . ':attempts', 0),
                ]
            );

            return response()->json([
                'error' => 'Too many attempts',
                'message' => 'Too many failed attempts. Please try again later.',
                'retry_after' => Cache::get($ipKey) - time(),
            ], 429);
        }

        // Check email-based lockout
        if ($email) {
            $emailKey = "auth_lockout:email:" . md5($email);
            if (Cache::has($emailKey)) {
                $this->auditLogService->logBlocked(
                    'email_locked_out',
                    'Email locked out due to too many failed attempts',
                    null,
                    null,
                    [
                        'email' => $email,
                        'attempts' => Cache::get($emailKey . ':attempts', 0),
                    ]
                );

                return response()->json([
                    'error' => 'Too many attempts',
                    'message' => 'Too many failed attempts for this account. Please try again later.',
                    'retry_after' => Cache::get($emailKey) - time(),
                ], 429);
            }
        }

        $response = $next($request);

        // Track failed attempts
        if ($response->isClientError() || $response->getStatusCode() === 401) {
            $this->trackFailedAttempt($ipAddress, $email, $maxAttempts, $decayMinutes);
        } elseif ($response->isSuccessful()) {
            // Clear failed attempts on success
            $this->clearFailedAttempts($ipAddress, $email);
        }

        return $response;
    }

    /**
     * Track failed authentication attempts.
     */
    protected function trackFailedAttempt(string $ipAddress, ?string $email, int $maxAttempts, int $decayMinutes): void
    {
        $ipKey = "auth_attempts:ip:{$ipAddress}";
        $ipAttempts = Cache::get($ipKey, 0) + 1;
        Cache::put($ipKey, $ipAttempts, $decayMinutes * 60);

        if ($ipAttempts >= $maxAttempts) {
            // Lock out IP
            Cache::put("auth_lockout:ip:{$ipAddress}", time() + ($decayMinutes * 60), $decayMinutes * 60);
            Cache::put("auth_lockout:ip:{$ipAddress}:attempts", $ipAttempts, $decayMinutes * 60);
        }

        if ($email) {
            $emailKey = "auth_attempts:email:" . md5($email);
            $emailAttempts = Cache::get($emailKey, 0) + 1;
            Cache::put($emailKey, $emailAttempts, $decayMinutes * 60);

            if ($emailAttempts >= $maxAttempts) {
                // Lock out email
                Cache::put("auth_lockout:email:" . md5($email), time() + ($decayMinutes * 60), $decayMinutes * 60);
                Cache::put("auth_lockout:email:" . md5($email) . ":attempts", $emailAttempts, $decayMinutes * 60);
            }
        }

        $this->auditLogService->logFailure(
            'auth_failed',
            'Authentication attempt failed',
            null,
            null,
            [
                'ip_address' => $ipAddress,
                'email' => $email,
                'ip_attempts' => $ipAttempts,
            ]
        );
    }

    /**
     * Clear failed authentication attempts on success.
     */
    protected function clearFailedAttempts(string $ipAddress, ?string $email): void
    {
        Cache::forget("auth_attempts:ip:{$ipAddress}");
        Cache::forget("auth_lockout:ip:{$ipAddress}");
        Cache::forget("auth_lockout:ip:{$ipAddress}:attempts");

        if ($email) {
            Cache::forget("auth_attempts:email:" . md5($email));
            Cache::forget("auth_lockout:email:" . md5($email));
            Cache::forget("auth_lockout:email:" . md5($email) . ":attempts");
        }
    }
}
