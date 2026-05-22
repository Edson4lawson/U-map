<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('messages:cleanup')]
#[Description('Supprime les messages de plus de 7 jours (Ephémère)')]
class CleanupMessages extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = \App\Models\Message::where('created_at', '<', \Carbon\Carbon::now()->subDays(7))->delete();
        $this->info("$count messages expirés ont été supprimés.");
    }
}
