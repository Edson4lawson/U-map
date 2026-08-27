<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    /**
     * Log an audit event.
     */
    public function log(array $data): void
    {
        try {
            AuditLog::create($data);
        } catch (\Exception $e) {
            // Log error but don't break the application
            Log::error('Failed to create audit log', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    /**
     * Log a successful action.
     */
    public function logSuccess(
        string $action,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?array $payload = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $this->log([
            'user_id' => Auth::id(),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload' => $this->sanitizePayload($payload),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'status' => 'success',
        ]);
    }

    /**
     * Log a failed action.
     */
    public function logFailure(
        string $action,
        string $errorMessage,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?array $payload = null
    ): void {
        $this->log([
            'user_id' => Auth::id(),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload' => $this->sanitizePayload($payload),
            'status' => 'failure',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Log a blocked action (security event).
     */
    public function logBlocked(
        string $action,
        string $reason,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?array $payload = null
    ): void {
        $this->log([
            'user_id' => Auth::id(),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload' => $this->sanitizePayload($payload),
            'status' => 'blocked',
            'error_message' => $reason,
        ]);
    }

    /**
     * Log a login event.
     */
    public function logLogin(?int $userId = null): void
    {
        $this->log([
            'user_id' => $userId ?? Auth::id(),
            'action' => 'login',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'success',
        ]);
    }

    /**
     * Log a logout event.
     */
    public function logLogout(?int $userId = null): void
    {
        $this->log([
            'user_id' => $userId ?? Auth::id(),
            'action' => 'logout',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'success',
        ]);
    }

    /**
     * Log a failed login attempt.
     */
    public function logFailedLogin(string $email, string $reason): void
    {
        $this->log([
            'user_id' => null,
            'action' => 'login_failed',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload' => ['email' => $email],
            'status' => 'failure',
            'error_message' => $reason,
        ]);
    }

    /**
     * Sanitize payload to remove sensitive data.
     */
    protected function sanitizePayload(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

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

        // Also check nested arrays
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->sanitizePayload($value);
            }
        }

        return $payload;
    }
}
