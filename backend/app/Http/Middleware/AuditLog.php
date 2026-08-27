<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuditLog
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log authenticated requests
        if (Auth::check()) {
            $this->logRequest($request, $response);
        }

        return $response;
    }

    /**
     * Log the request.
     */
    protected function logRequest(Request $request, Response $response): void
    {
        $action = $this->determineAction($request);
        $resourceType = $this->determineResourceType($request);
        $resourceId = $this->determineResourceId($request);

        // Don't log GET requests for performance (except sensitive ones)
        if ($request->isMethod('GET') && !$this->isSensitiveGet($request)) {
            return;
        }

        $status = $response->isSuccessful() ? 'success' : 'failure';
        $errorMessage = !$response->isSuccessful() ? $response->status() : null;

        $this->auditLogService->log([
            'user_id' => Auth::id(),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $this->sanitizePayload($request->all()),
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Determine the action based on request method.
     */
    protected function determineAction(Request $request): string
    {
        $method = $request->method();
        $path = $request->path();

        return match($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            'GET' => 'view',
            default => $method,
        };
    }

    /**
     * Determine the resource type from the request path.
     */
    protected function determineResourceType(Request $request): ?string
    {
        $path = $request->path();
        
        // Extract resource type from path
        if (preg_match('#api/(\w+)#', $path, $matches)) {
            return ucfirst(str_replace('-', '', $matches[1]));
        }

        return null;
    }

    /**
     * Determine the resource ID from the request.
     */
    protected function determineResourceId(Request $request): ?int
    {
        $path = $request->path();
        
        // Extract ID from path (e.g., api/messages/123)
        if (preg_match('#/(\d+)$#', $path, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Check if this is a sensitive GET request that should be logged.
     */
    protected function isSensitiveGet(Request $request): bool
    {
        $sensitivePaths = [
            'api/users',
            'api/admin',
            'api/audit',
            'api/reports',
        ];

        foreach ($sensitivePaths as $path) {
            if (str_starts_with($request->path(), $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize payload to remove sensitive data.
     */
    protected function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'current_password',
            'token', 'api_key', 'secret', 'access_token', 'refresh_token',
            'credit_card', 'ssn', 'social_security_number',
            'content', // Never log message content
        ];

        foreach ($sensitiveKeys as $key) {
            if (isset($payload[$key])) {
                $payload[$key] = '[REDACTED]';
            }
        }

        return $payload;
    }
}
