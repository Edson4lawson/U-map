<?php

namespace App\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class UserFriendlyLogger extends AbstractProcessingHandler
{
    /**
     * {@inheritDoc}
     */
    protected function write(LogRecord $record): void
    {
        // Log the full technical details to the server logs
        // This is for debugging purposes on the server side
        \Log::channel('technical')->log(
            $record->level->getName(),
            $record->message,
            $record->context
        );

        // For user-facing responses, we only log sanitized information
        $sanitizedContext = $this->sanitizeContext($record->context);
        
        \Log::channel('user')->log(
            $record->level->getName(),
            $this->getUserFriendlyMessage($record->message),
            $sanitizedContext
        );
    }

    /**
     * Sanitize context to remove sensitive information
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password',
            'token',
            'api_key',
            'secret',
            'authorization',
            'cookie',
            'session',
            'csrf',
            'sql',
            'query',
            'stack_trace',
            'file',
            'line',
            'trace'
        ];

        $sanitized = [];
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeContext($value);
            } elseif (is_string($value)) {
                $lowerKey = strtolower($key);
                foreach ($sensitiveKeys as $sensitiveKey) {
                    if (str_contains($lowerKey, $sensitiveKey)) {
                        $sanitized[$key] = '[REDACTED]';
                        continue 2;
                    }
                }
                $sanitized[$key] = $value;
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Convert technical messages to user-friendly messages
     */
    private function getUserFriendlyMessage(string $message): string
    {
        // Map technical errors to user-friendly messages
        $errorMap = [
            'SQLSTATE' => 'Une erreur de base de données est survenue',
            'Connection refused' => 'Impossible de se connecter au serveur',
            'Timeout' => 'Le serveur met trop longtemps à répondre',
            '404' => 'La ressource demandée n\'existe pas',
            '401' => 'Vous devez vous connecter pour accéder à cette ressource',
            '403' => 'Vous n\'avez pas les droits pour accéder à cette ressource',
            '422' => 'Les données fournies sont invalides',
            '500' => 'Une erreur serveur est survenue',
            '503' => 'Le service est temporairement indisponible',
            'PDOException' => 'Une erreur de base de données est survenue',
            'QueryException' => 'Une erreur de base de données est survenue',
            'ValidationException' => 'Les données fournies sont invalides',
            'AuthenticationException' => 'Erreur d\'authentification',
            'AuthorizationException' => 'Vous n\'avez pas les droits nécessaires',
        ];

        foreach ($errorMap as $technical => $friendly) {
            if (str_contains($message, $technical)) {
                return $friendly;
            }
        }

        // If no match, return a generic user-friendly message
        return 'Une erreur est survenue. Veuillez réessayer.';
    }
}
