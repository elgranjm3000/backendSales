<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');




Schedule::command('sessions:cleanup --hours=1')
    ->everyFifteenMinutes()  // O ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();



// Limpieza profunda diaria a las 3 AM (sesiones inactivas 48h)
Schedule::command('sessions:cleanup --hours=48')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer();