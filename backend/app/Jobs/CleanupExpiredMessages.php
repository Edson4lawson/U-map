<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\AuditLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupExpiredMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de jours avant expiration des messages
     */
    private int $daysToKeep;

    /**
     * Taille du batch pour suppression par chunks
     */
    private int $chunkSize;

    /**
     * Service d'audit logging
     */
    protected AuditLogService $auditLogService;

    /**
     * Créer une nouvelle instance du job.
     */
    public function __construct(int $daysToKeep = 7, int $chunkSize = 1000)
    {
        $this->daysToKeep = $daysToKeep;
        $this->chunkSize = $chunkSize;
        $this->onQueue('messages-cleanup');
    }

    /**
     * Exécuter le job avec suppression sécurisée.
     */
    public function handle(AuditLogService $auditLogService): void
    {
        $this->auditLogService = $auditLogService;
        $cutoffDate = now()->subDays($this->daysToKeep);
        $totalDeleted = 0;
        $totalEncrypted = 0;

        Log::info('Starting secure cleanup of expired messages', [
            'cutoff_date' => $cutoffDate->toDateTimeString(),
            'days_to_keep' => $this->daysToKeep,
            'chunk_size' => $this->chunkSize,
        ]);

        // Suppression sécurisée par chunks - pas de logs de contenu
        Message::where('created_at', '<', $cutoffDate)
            ->orderBy('created_at', 'asc')
            ->chunkById($this->chunkSize, function ($messages) use (&$totalDeleted, &$totalEncrypted, $cutoffDate) {
                $count = $messages->count();
                $encryptedCount = 0;
                $messageIds = [];

                foreach ($messages as $message) {
                    $messageIds[] = $message->id;
                    if ($message->is_encrypted) {
                        $encryptedCount++;
                    }
                }
                
                // Suppression sécurisée en bloc (bulk delete)
                if (!empty($messageIds)) {
                    Message::whereIn('id', $messageIds)->delete();
                }
                
                $totalDeleted += $count;
                $totalEncrypted += $encryptedCount;

                // Log sans contenu de message
                Log::info('Deleted chunk of expired messages', [
                    'chunk_size' => $count,
                    'encrypted_count' => $encryptedCount,
                    'total_deleted' => $totalDeleted,
                    'message_ids' => $messageIds, // IDs seulement
                ]);

                // Audit log de la suppression (sans contenu)
                $this->auditLogService->logSuccess(
                    'cleanup_expired_messages',
                    'Message',
                    null,
                    [
                        'count' => $count,
                        'encrypted_count' => $encryptedCount,
                        'cutoff_date' => $cutoffDate->toDateTimeString(),
                    ]
                );
            });

        Log::info('Secure cleanup completed', [
            'total_deleted' => $totalDeleted,
            'total_encrypted' => $totalEncrypted,
            'encryption_rate' => $totalDeleted > 0 ? round(($totalEncrypted / $totalDeleted) * 100, 2) : 0,
        ]);

        // Audit log final
        $this->auditLogService->logSuccess(
            'cleanup_expired_messages_completed',
            null,
            null,
            [
                'total_deleted' => $totalDeleted,
                'total_encrypted' => $totalEncrypted,
                'days_to_keep' => $this->daysToKeep,
            ]
        );
    }

    /**
     * Gérer les échecs du job avec audit logging.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to cleanup expired messages', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->auditLogService->logFailure(
            'cleanup_expired_messages',
            $exception->getMessage(),
            null,
            null,
            ['days_to_keep' => $this->daysToKeep]
        );
    }
}
