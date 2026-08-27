<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Planification quotidienne du nettoyage de la messagerie éphémère (messages de plus de 7 jours)
Schedule::command('messages:cleanup')->daily();
