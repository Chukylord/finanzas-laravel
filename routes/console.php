<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ✅ Scheduler (Laravel 12)
Schedule::command('iol:sync-prices')
->hourly()
->withoutOverlapping();
