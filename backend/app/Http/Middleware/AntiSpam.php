<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\AuditLogService;
use Symfony\Component\HttpFoundation\Response;

class AntiSpam
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle an incoming request with anti-spam protection.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->user()?->id;
        $ipAddress = $request->ip();

        // Check for spam patterns
        if ($this->isSpamming($userId, $ipAddress, $request)) {
            $this->auditLogService->logBlocked(
                'spam_detected',
                'Spam activity detected',
                null,
                null,
                [
                    'user_id' => $userId,
                    'ip_address' => $ipAddress,
                    'endpoint' => $request->path(),
                ]
            );

            return response()->json([
                'error' => 'Spam detected',
                'message' => 'Your activity has been flagged as spam. Please slow down.',
            ], 429);
        }

        // Check for rapid message sending
        if ($request->is('api/messages*') && $request->isMethod('POST')) {
            if ($this->isSendingTooFast($userId, $ipAddress)) {
                $this->incrementSpamScore($userId, $ipAddress);

                $this->auditLogService->logBlocked(
                    'message_spam',
                    'Message spam detected',
                    'Message',
                    null,
                    [
                        'user_id' => $userId,
                        'ip_address' => $ipAddress,
                    ]
                );

                return response()->json([
                    'error' => 'Too many messages',
                    'message' => 'Please wait before sending another message.',
                ], 429);
            }
        }

        // Check for suspicious patterns
        if ($this->hasSuspiciousPattern($request)) {
            $this->incrementSpamScore($userId, $ipAddress);

            $this->auditLogService->logBlocked(
                'suspicious_activity',
                'Suspicious activity pattern detected',
                null,
                null,
                [
                    'user_id' => $userId,
                    'ip_address' => $ipAddress,
                    'user_agent' => $request->userAgent(),
                ]
            );

            return response()->json([
                'error' => 'Suspicious activity',
                'message' => 'Your activity has been flagged for review.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if user/IP is spamming.
     */
    protected function isSpamming(?int $userId, string $ipAddress, Request $request): bool
    {
        // Check IP spam score
        $ipSpamKey = "spam_score:ip:{$ipAddress}";
        $ipSpamScore = Cache::get($ipSpamKey, 0);

        if ($ipSpamScore > 10) {
            return true;
        }

        // Check user spam score
        if ($userId) {
            $userSpamKey = "spam_score:user:{$userId}";
            $userSpamScore = Cache::get($userSpamKey, 0);

            if ($userSpamScore > 5) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user is sending messages too fast and increment counters.
     */
    protected function isSendingTooFast(?int $userId, string $ipAddress): bool
    {
        // Check recent message count (last minute)
        if ($userId) {
            $recentKey = "message_count:user:{$userId}:" . now()->format('YmdHi');
            $recentCount = Cache::get($recentKey, 0);

            if ($recentCount >= 10) { // Max 10 messages per minute
                return true;
            }
            Cache::put($recentKey, $recentCount + 1, 60);
        }

        // IP-based check
        $ipRecentKey = "message_count:ip:{$ipAddress}:" . now()->format('YmdHi');
        $ipRecentCount = Cache::get($ipRecentKey, 0);

        if ($ipRecentCount >= 20) { // Max 20 messages per minute per IP
            return true;
        }
        Cache::put($ipRecentKey, $ipRecentCount + 1, 60);

        return false;
    }

    /**
     * Increment spam score in cache when suspicious behavior occurs.
     */
    protected function incrementSpamScore(?int $userId, string $ipAddress): void
    {
        $ipSpamKey = "spam_score:ip:{$ipAddress}";
        Cache::put($ipSpamKey, Cache::get($ipSpamKey, 0) + 1, 3600);

        if ($userId) {
            $userSpamKey = "spam_score:user:{$userId}";
            Cache::put($userSpamKey, Cache::get($userSpamKey, 0) + 1, 3600);
        }
    }

    /**
     * Check for suspicious patterns in request.
     */
    protected function hasSuspiciousPattern(Request $request): bool
    {
        // Check for suspicious user agents
        $suspiciousAgents = [
            'curl', 'wget', 'python-requests', 'bot', 'crawler', 'spider',
        ];

        $userAgent = strtolower($request->userAgent() ?? '');
        foreach ($suspiciousAgents as $agent) {
            if (str_contains($userAgent, $agent) && !$request->expectsJson()) {
                return true;
            }
        }

        // Check for missing headers
        if (!$request->header('User-Agent')) {
            return true;
        }

        // Check for rapid endpoint switching
        $ipAddress = $request->ip();
        $endpointKey = "endpoints:ip:{$ipAddress}";
        $endpoints = Cache::get($endpointKey, []);

        if (count($endpoints) > 50) { // More than 50 different endpoints in window
            return true;
        }

        $endpoints[] = $request->path();
        Cache::put($endpointKey, $endpoints, 300); // 5 minute window

        return false;
    }
}
