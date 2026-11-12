<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled automated Google Drive photo synchronization (evaluates each event's interval: daily, hourly, etc.)
Schedule::command('gdrive:sync-scheduled')->everyThirtyMinutes()->withoutOverlapping();
