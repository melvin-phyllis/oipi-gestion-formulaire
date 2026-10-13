<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('risfm:mission-reminders')
    ->dailyAt((string) config('risfm.mission_reminders.schedule_time', '07:00'))
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('risfm:backup-database')
    ->dailyAt((string) config('risfm.backups.schedule_time', '02:00'))
    ->withoutOverlapping()
    ->onOneServer();
