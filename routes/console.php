<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('transactions:process')->everyFiveSeconds();

// Ejecutar el comando de recordatorio de suscripciones a la medianoche
Schedule::command('subscriptions:send-reminders')->dailyAt('00:00');
