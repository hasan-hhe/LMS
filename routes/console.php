<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('borrowings:send-due-reminders')->dailyAt('08:00');
Schedule::command('fines:accrue-daily')->dailyAt('00:10');
Schedule::command('reservations:expire-unclaimed')->hourly();
Schedule::command('orders:expire-unclaimed')->hourly();
