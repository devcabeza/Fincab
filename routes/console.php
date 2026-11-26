<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Record metrics snapshots for Laravel Horizon every 5 minutes
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// Prune old Telescope entries to keep database size healthy
Schedule::command('telescope:prune --hours=48')->daily();

// Check budget obligations and threshold alerts daily at 09:00
Schedule::command('budget:check-reminders')->dailyAt('09:00');

// Sync public exchange rates (BCV, Paralelo, Binance, COP) twice daily
Schedule::command('rates:sync')->twiceDaily(9, 13);

// Generate automated local budget backup weekly
Schedule::command('budget:backup')->weekly();
