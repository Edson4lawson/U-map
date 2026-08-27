<?php

namespace App\Console\Commands;

use App\Jobs\CleanupExpiredMessages;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('messages:cleanup')]
#[Description('Supprime les messages de plus de 7 jours (Ephémère) - Dispatch job queue')]
class CleanupMessages extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Dispatching cleanup job for expired messages...');
        
        // Dispatch le job pour traitement asynchrone via queue
        CleanupExpiredMessages::dispatch(
            daysToKeep: 7,
            chunkSize: 1000
        )->onQueue('messages-cleanup');
        
        $this->info('✅ Cleanup job dispatched successfully to queue: messages-cleanup');
        
        Log::info('Messages cleanup command executed', [
            'timestamp' => now()->toDateTimeString(),
            'days_to_keep' => 7
        ]);
        
        return Command::SUCCESS;
    }
}
