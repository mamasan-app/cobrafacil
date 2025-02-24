<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Taecontrol\Larvis\Commands\CheckHardwareHealthCommand;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('transactions:process')->everyFiveSeconds();

Schedule::command('subscriptions:send-reminders')->dailyAt('00:00');

Schedule::command(CheckHardwareHealthCommand::class)->daily();
