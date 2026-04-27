<?php

declare(strict_types=1);

use App\Chat\Jobs\RefreshDailyLimits;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new RefreshDailyLimits)->dailyAt('00:05')->name('refresh-daily-limits');

Schedule::command('characters:recalc-popularity')
    ->everyFiveMinutes()
    ->name('recalc-popularity')
    ->withoutOverlapping();

Schedule::command('users:gc-guests')
    ->daily()
    ->name('gc-guest-users')
    ->withoutOverlapping();
