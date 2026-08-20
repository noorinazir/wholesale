<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('inbox:check')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('followups:process')->everyTenMinutes()->withoutOverlapping();
Schedule::command('amazon:sync')->everySixHours()->withoutOverlapping()->skip(function () {
    return empty(\App\Models\SystemSetting::get('amazon_lwa_client_id'));
});
